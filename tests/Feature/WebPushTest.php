<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use App\Services\WebPushCrypto;
use App\Services\WebPushService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phone notifications (Web Push): the RFC 8291 encryption, the VAPID signature,
 * the per-device subscriptions, the outbox + `push:send` delivery, the
 * /push/* endpoints and the wiring that puts the "Enable phone alerts" button and
 * the VAPID key on the page.
 *
 * The encryptor is pinned against the test vector published in RFC 8291
 * Appendix A, so a change here cannot silently make browsers unable to decrypt.
 */
class WebPushTest extends TestCase
{
    use DatabaseTransactions;

    /** @var string */
    private $publicKey = '';

    /** @var string */
    private $privateKey = '';

    protected function setUp(): void
    {
        parent::setUp();

        // Pin the dormant state instead of trusting .env: a developer who ran
        // `php artisan push:keys` must not make these tests depend on it.
        config(['webpush.enabled' => false, 'webpush.public_key' => null, 'webpush.private_key' => null]);

        $keys = WebPushCrypto::generateKeys();
        $this->publicKey = $keys['public'];
        $this->privateKey = $keys['private'];
    }

    private function makeUser(string $roleSlug, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(preg_replace('/[^a-z0-9]+/i', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
            'is_active' => true,
        ]);
    }

    /**
     * RFC 8291 Appendix A, byte for byte: header + ciphertext + tag.
     */
    public function test_the_encryptor_reproduces_the_rfc_8291_test_vector(): void
    {
        $decode = function ($value) {
            return WebPushCrypto::decode($value);
        };

        $userPublic = $decode('BCVxsr7N_eNgVRqvHtD0zTZsEc6-VV-JvLexhqUzORcx aOzi6-AYWXvTBHm4bjyPjs7Vd8pZGH6SRpkNtoIAiw4');
        $userPrivate = $decode('q1dXpw3UpT5VOmu_cf_v6ih07Aems3njxI-JWgLcM94');
        $serverPublic = $decode('BP4z9KsN6nGRTbVYI_c7VJSPQTBtkgcy27mlmlMoZIIg Dll6e3vCYLocInmYWAmS6TlzAC8wEqKK6PBru3jl7A8');
        $serverPrivate = $decode('yfWPiYE-n46HLnH0KqZOF1fJJU3MYrct3AELtAQ-oRw');
        $auth = $decode('BTBZMqHH6r4Tts7J_aSIgg');

        $this->assertSame(
            WebPushCrypto::encode($userPublic),
            WebPushCrypto::publicKeyFor(WebPushCrypto::encode($userPrivate)),
            'A P-256 scalar must export to the matching uncompressed point.'
        );
        $this->assertSame(
            WebPushCrypto::encode($serverPublic),
            WebPushCrypto::publicKeyFor(WebPushCrypto::encode($serverPrivate))
        );

        $body = (new WebPushCrypto())->encrypt(
            'When I grow up, I want to be a watermelon',
            WebPushCrypto::encode($userPublic),
            WebPushCrypto::encode($auth),
            [
                'salt' => $decode('DGv6ra1nlYgDCS1FRnbzlw'),
                'ephemeral_key' => WebPushCrypto::pemFromScalar($serverPrivate),
            ]
        );

        $expected = $decode('DGv6ra1nlYgDCS1FRnbzlwAAEABBBP4z9KsN6nGRTbVYI_c7VJSPQTBtkgcy27mlmlMoZIIgDll6e3vCYLocInmYWAmS6TlzAC8wEqKK6PBru3jl7A8')
            .$decode('8pfeW0KbunFT06SuDKoJH9Ql87S1QUrdirN6GcG7sFz1y1sqLgVi1VhjVkHsUoEsbI_0LpXMuGvnzQ');

        $this->assertSame(
            WebPushCrypto::encode($expected),
            WebPushCrypto::encode($body),
            'The aes128gcm body does not match RFC 8291 Appendix A - a browser would fail to decrypt it.'
        );
    }

    /**
     * A fresh key pair must produce a JWS a push service can verify: ES256 over
     * the base64url header and claims, 64 raw bytes, with `k` naming the same key.
     */
    public function test_the_vapid_header_carries_a_valid_es256_signature(): void
    {
        $header = (new WebPushCrypto())->vapidHeaders(
            'https://fcm.googleapis.com/wp/token',
            $this->publicKey,
            $this->privateKey,
            'mailto:test@example.com'
        );

        $this->assertArrayHasKey('Authorization', $header);
        $this->assertSame(1, preg_match('/^vapid t=([^,]+),k=(.+)$/', $header['Authorization'], $matches));

        [$joseHeader, $claims, $signature] = explode('.', $matches[1]);

        $this->assertSame(64, strlen(WebPushCrypto::decode($signature)), 'An ES256 signature is r||s, 64 bytes.');
        $this->assertSame($this->publicKey, $matches[2]);

        $payload = json_decode(WebPushCrypto::decode($claims), true);
        $this->assertSame('https://fcm.googleapis.com', $payload['aud']);
        $this->assertSame('mailto:test@example.com', $payload['sub']);
        $this->assertGreaterThan(time(), $payload['exp']);
        $this->assertSame(['typ' => 'JWT', 'alg' => 'ES256'], json_decode(WebPushCrypto::decode($joseHeader), true));

        // Verify it the way a push service does, from the public key alone.
        $key = openssl_pkey_get_public(WebPushCrypto::pemFromPoint(WebPushCrypto::decode($matches[2])));

        $this->assertSame(
            1,
            openssl_verify($joseHeader.'.'.$claims, $this->derSignature(WebPushCrypto::decode($signature)), $key, OPENSSL_ALGO_SHA256),
            'The VAPID JWT signature does not verify against the public key it advertises.'
        );
    }

    /**
     * Without keys the feature is inert: nothing queued, nothing sent, and no
     * public key for the page to subscribe with.
     */
    public function test_nothing_is_queued_or_sent_without_vapid_keys(): void
    {
        $push = app(WebPushService::class);

        $this->assertFalse($push->isConfigured());
        $this->assertNull($push->publicKey());
        $this->assertFalse($push->queue(11, 'info', 'Never sent'));
        $this->assertSame(0, (int) DB::table('push_messages')->count());

        $this->assertSame(
            ['sent' => 0, 'retried' => 0, 'failed' => 0, 'skipped' => 0, 'gone' => 0, 'devices' => 0],
            $push->sendPending(10)
        );
    }

    /* ------------------------------------------------------------------ *
     * Devices
     * ------------------------------------------------------------------ */

    /**
     * One browser is one row: subscribing again refreshes it, and a device that
     * has changed hands moves to the user who signed in on it.
     */
    public function test_a_subscription_is_stored_once_and_follows_the_user(): void
    {
        $this->configure();

        $push = app(WebPushService::class);
        $user = $this->makeUser('learner', 'Device Learner');
        $other = $this->makeUser('parent', 'Device Parent');
        $subscription = $this->subscription('https://push.example.test/one');

        $this->assertTrue($push->subscribe($user->id, $subscription, 'Chrome on Android'));
        $this->assertTrue($push->subscribe($user->id, $subscription, 'Chrome on Android'));

        $this->assertSame(1, (int) DB::table('push_subscriptions')->count(), 'Re-subscribing must update, not duplicate.');
        $this->assertSame(1, $push->devicesFor($user->id));
        $this->assertSame('Chrome on Android', DB::table('push_subscriptions')->value('user_agent'));

        // Same browser, another account: the endpoint belongs to whoever signs in.
        $this->assertTrue($push->subscribe($other->id, $subscription));

        $this->assertSame(1, (int) DB::table('push_subscriptions')->count());
        $this->assertSame(0, $push->devicesFor($user->id));
        $this->assertSame(1, $push->devicesFor($other->id));

        // Only the owner can remove it.
        $this->assertFalse($push->unsubscribe($user->id, $subscription['endpoint']));
        $this->assertTrue($push->unsubscribe($other->id, $subscription['endpoint']));
        $this->assertSame(0, $push->devicesFor($other->id));
    }

    /**
     * An alert is queued only for a user who has a device, which keeps the outbox
     * from collecting rows nobody can receive.
     */
    public function test_a_push_is_queued_only_for_a_user_with_a_device(): void
    {
        $this->configure();

        $push = app(WebPushService::class);
        $user = $this->makeUser('learner', 'Queued Learner');

        $this->assertFalse($push->queue($user->id, 'info', 'You have news'));
        $this->assertSame(0, (int) DB::table('push_messages')->count());

        $push->subscribe($user->id, $this->subscription());

        $this->assertTrue($push->queue($user->id, 'info', 'You have news', 'Details inside', '/notifications'));

        $row = DB::table('push_messages')->first();
        $this->assertSame('queued', $row->status);
        $this->assertSame('/notifications', $row->url);
        $this->assertSame($user->id, (int) $row->user_id);
        $this->assertSame(0, (int) $row->attempts);
    }

    /* ------------------------------------------------------------------ *
     * Delivery
     * ------------------------------------------------------------------ */

    /**
     * 201 Created: the push service took it. The row is done and the device is
     * stamped.
     */
    public function test_an_accepted_push_marks_the_message_sent(): void
    {
        $this->configure();

        $push = $this->push([new Response(201)]);
        $user = $this->makeUser('learner', 'Accepted Learner');
        $push->subscribe($user->id, $this->subscription());
        $push->queue($user->id, 'info', 'New class resource', 'Fractions video', '/notifications');

        $summary = $push->sendPending(10);

        $this->assertSame(1, $summary['sent']);
        $this->assertSame(1, $summary['devices']);

        $row = DB::table('push_messages')->first();
        $this->assertSame('sent', $row->status);
        $this->assertSame(1, (int) $row->attempts);
        $this->assertNotNull($row->sent_at);
        $this->assertNotNull(DB::table('push_subscriptions')->value('last_success_at'));
    }

    /**
     * 410 Gone: the browser threw the subscription away (app uninstalled, site
     * data cleared). The device is disabled so the next run does not try again.
     */
    public function test_a_revoked_subscription_is_disabled(): void
    {
        $this->configure();

        $push = $this->push([new Response(410)]);
        $user = $this->makeUser('learner', 'Revoked Learner');
        $push->subscribe($user->id, $this->subscription());
        $push->queue($user->id, 'info', 'New class resource');

        $summary = $push->sendPending(10);

        $this->assertSame(1, $summary['gone']);
        $this->assertSame(0, $summary['sent']);
        $this->assertNotNull(DB::table('push_subscriptions')->value('disabled_at'));
        $this->assertSame(0, $push->devicesFor($user->id), 'A disabled device must stop counting.');
    }

    /**
     * Anything else (a 500 here) is retried up to `webpush.max_attempts` and then
     * given up on, so one dead endpoint cannot block the outbox for ever.
     */
    public function test_a_failing_push_service_is_retried_then_failed(): void
    {
        $this->configure(['webpush.max_attempts' => 2]);

        $push = $this->push([new Response(500), new Response(500)]);
        $user = $this->makeUser('learner', 'Retry Learner');
        $push->subscribe($user->id, $this->subscription());
        $push->queue($user->id, 'info', 'New class resource');

        $first = $push->sendPending(10);
        $this->assertSame(1, $first['retried']);
        $this->assertSame('queued', DB::table('push_messages')->value('status'));
        $this->assertNotNull(DB::table('push_messages')->value('send_after'), 'A retry has to wait for its backoff.');

        // The cron would not pick it up again before send_after; clear it to
        // simulate the next window.
        DB::table('push_messages')->update(['send_after' => null]);

        $second = $push->sendPending(10);
        $this->assertSame(1, $second['failed']);
        $this->assertSame('failed', DB::table('push_messages')->value('status'));
        $this->assertStringContainsString('HTTP 500', (string) DB::table('push_messages')->value('error'));
    }

    /**
     * A queued alert whose device has gone (unsubscribed between queue and send)
     * is dropped instead of retried.
     */
    public function test_a_message_for_a_user_without_a_device_is_skipped(): void
    {
        $this->configure();

        DB::table('push_messages')->insert([
            'user_id' => 99,
            'type' => 'info',
            'title' => 'Nobody is listening',
            'status' => 'queued',
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $summary = $this->push([])->sendPending(10);

        $this->assertSame(1, $summary['skipped']);
        $this->assertSame('skipped', DB::table('push_messages')->value('status'));
    }

    /* ------------------------------------------------------------------ *
     * The endpoints the page calls
     * ------------------------------------------------------------------ */

    /**
     * A guest cannot file a device, an incomplete payload is rejected, and a valid
     * one lands under the signed-in user - never under an id from the request.
     */
    public function test_the_subscribe_route_files_the_device_under_the_signed_in_user(): void
    {
        $this->configure();
        $user = $this->makeUser('learner', 'Push Learner');

        $payload = [
            'endpoint' => 'https://push.example.test/route',
            'keys' => [
                'p256dh' => $this->publicKey,
                'auth' => WebPushCrypto::encode(random_bytes(16)),
            ],
            'contentEncoding' => 'aes128gcm',
        ];

        $this->postJson(route('push.subscribe'), $payload)->assertStatus(401);

        $this->actingAs($user)
            ->postJson(route('push.subscribe'), ['endpoint' => 'https://push.example.test/route'])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('push.subscribe'), $payload)
            ->assertOk()
            ->assertJson(['subscribed' => true, 'devices' => 1]);

        $stored = DB::table('push_subscriptions')->first();

        $this->assertSame($user->id, (int) $stored->user_id);
        $this->assertSame(hash('sha256', $payload['endpoint']), $stored->endpoint_hash);

        $this->actingAs($user)
            ->getJson(route('push.status'))
            ->assertOk()
            ->assertJson(['configured' => true, 'subscribed' => true, 'devices' => 1])
            ->assertJsonPath('publicKey', $this->publicKey);

        $this->actingAs($user)
            ->postJson(route('push.unsubscribe'), ['endpoint' => $payload['endpoint']])
            ->assertOk()
            ->assertJson(['removed' => true]);

        $this->assertSame(0, (int) DB::table('push_subscriptions')->count());
    }

    /**
     * A server without keys says so instead of pretending a device is registered.
     */
    public function test_the_subscribe_route_says_so_when_the_server_cannot_push(): void
    {
        $user = $this->makeUser('learner', 'No Keys Learner');

        $this->actingAs($user)
            ->getJson(route('push.status'))
            ->assertOk()
            ->assertJson(['configured' => false, 'subscribed' => false, 'publicKey' => null]);

        $this->actingAs($user)
            ->postJson(route('push.subscribe'), [
                'endpoint' => 'https://push.example.test/route',
                'keys' => ['p256dh' => $this->publicKey, 'auth' => WebPushCrypto::encode(random_bytes(16))],
            ])
            ->assertStatus(409);
    }

    /**
     * The bell's alerts reach the outbox without any call site being touched: an
     * in-app notification now also queues the same alert for the phone.
     */
    public function test_an_in_app_notification_also_queues_a_push(): void
    {
        $this->configure();
        $user = $this->makeUser('learner', 'Notified Learner');

        app(WebPushService::class)->subscribe($user->id, $this->subscription());

        Notification::create([
            'user_id' => $user->id,
            'type' => Notification::TYPE_INFO,
            'title' => 'Term dates published',
            'message' => 'The new term starts on Monday.',
            'channel' => 'in_app',
            'is_read' => false,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $row = DB::table('push_messages')->first();

        $this->assertNotNull($row, 'The in-app notification must come with a queued push.');
        $this->assertSame($user->id, (int) $row->user_id);
        $this->assertSame(Notification::TYPE_INFO, $row->type);
        $this->assertSame('Term dates published', $row->title);
        $this->assertSame('/notifications', $row->url);
        $this->assertSame('queued', $row->status);
    }

    /* ------------------------------------------------------------------ *
     * The browser side
     * ------------------------------------------------------------------ */

    /**
     * The worker must show a pushed payload (that is the whole feature) while
     * keeping the handlers the install button and notification clicks rely on.
     */
    public function test_the_service_worker_shows_pushed_notifications(): void
    {
        $worker = file_get_contents(public_path('service-worker.js'));

        $this->assertStringContainsString("addEventListener('push'", $worker);
        $this->assertStringContainsString('showNotification', $worker);
        $this->assertStringContainsString("addEventListener('pushsubscriptionchange'", $worker);
        $this->assertStringContainsString('esh:push-resubscribe', $worker);

        $this->assertStringContainsString("addEventListener('install'", $worker);
        $this->assertStringContainsString("addEventListener('activate'", $worker);
        $this->assertStringContainsString("addEventListener('fetch'", $worker);
        $this->assertStringContainsString("addEventListener('notificationclick'", $worker);
    }

    /**
     * Every signed-in page hands the script the push endpoints and the VAPID key,
     * and /notifications renders the button that asks for permission.
     */
    public function test_the_layout_hands_the_push_endpoints_to_the_page(): void
    {
        $this->configure();
        $user = $this->makeUser('learner', 'Push Page Learner');

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('name="esh-push-key"', false)
            ->assertSee($this->publicKey, false)
            ->assertSee('esh-push-subscribe-url', false)
            ->assertSee(route('push.subscribe'), false)
            ->assertSee('data-phone-alerts-button', false)
            ->assertSee('assets/js/esh-pwa.js', false);
    }

    /**
     * A server without a VAPID pair - every server until `push:keys` has been run
     * and .env edited - must still serve every page, with no button that cannot
     * work. The install button is independent of push and stays.
     */
    public function test_the_pages_stay_up_without_a_vapid_pair(): void
    {
        $user = $this->makeUser('learner', 'Dormant Learner');

        $this->get('/login')->assertOk();

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee('esh-push-key', false)
            ->assertDontSee('data-phone-alerts-button', false)
            ->assertSee('data-pwa-install-button', false);
    }

    /* ------------------------------------------------------------------ *
     * Helpers
     * ------------------------------------------------------------------ */

    /** Pretend this server has a VAPID pair. */
    private function configure(array $overrides = []): void
    {
        config(array_merge([
            'webpush.enabled' => true,
            'webpush.public_key' => $this->publicKey,
            'webpush.private_key' => $this->privateKey,
            'webpush.subject' => 'mailto:test@example.com',
            'webpush.max_attempts' => 3,
            'webpush.retry_seconds' => 300,
        ], $overrides));
    }

    /**
     * A service whose push service answers with exactly these responses, in order.
     *
     * @param  array<int, \GuzzleHttp\Psr7\Response>  $responses
     */
    private function push(array $responses): WebPushService
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler($responses))]);

        return new WebPushService(new WebPushCrypto(), $client);
    }

    /**
     * What PushSubscription.getKey() would have produced for this browser.
     *
     * @return array<string, string>
     */
    private function subscription(string $endpoint = 'https://push.example.test/device'): array
    {
        return [
            'endpoint' => $endpoint,
            'p256dh' => $this->publicKey,
            'auth' => WebPushCrypto::encode(random_bytes(16)),
            'content_encoding' => 'aes128gcm',
        ];
    }

    /**
     * r||s back to the DER SEQUENCE openssl_verify() wants (the inverse of what
     * WebPushCrypto does when it signs).
     */
    private function derSignature(string $raw): string
    {
        $integer = function ($bytes) {
            $bytes = ltrim($bytes, "\x00");

            if ($bytes === '' || ord($bytes[0]) >= 0x80) {
                $bytes = "\x00".$bytes;
            }

            return "\x02".chr(strlen($bytes)).$bytes;
        };

        $body = $integer(substr($raw, 0, 32)).$integer(substr($raw, 32));

        return "\x30".chr(strlen($body)).$body;
    }
}
