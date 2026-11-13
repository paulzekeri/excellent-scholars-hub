<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The notification feed, the notification manager and the broadcast report all
 * refresh themselves: each page polls a small JSON endpoint and swaps in the
 * freshly rendered partial, only when something actually changed.
 */
class LiveUpdatesTest extends TestCase
{
    use DatabaseTransactions;

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

    private function makeNotification(User $user, string $title, string $type = Notification::TYPE_INFO): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $title . ' — details inside.',
            'channel' => 'in_app',
            'is_read' => false,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function test_the_notification_feed_reports_a_new_notification(): void
    {
        $learner = $this->makeUser('learner', 'Live Learner');
        $notification = $this->makeNotification($learner, 'Live update title');

        $response = $this->actingAs($learner)
            ->getJson(route('notifications.updates', ['since' => 0, 'unread' => 0]))
            ->assertOk()
            ->assertJson(['changed' => true])
            ->assertJsonPath('last_id', $notification->id)
            ->assertJsonPath('unread_total', 1);

        $html = $response->json('html');
        $this->assertStringContainsString('Live update title', $html);
        $this->assertStringContainsString('Mark read', $html);

        // Same watermark → nothing to redraw.
        $this->actingAs($learner)
            ->getJson(route('notifications.updates', ['since' => $notification->id, 'unread' => 1]))
            ->assertOk()
            ->assertJson(['changed' => false])
            ->assertJsonMissingPath('html');

        // Marking it read changes the unread total, so the list refreshes once.
        $notification->update(['is_read' => true]);

        $this->actingAs($learner)
            ->getJson(route('notifications.updates', ['since' => $notification->id, 'unread' => 1]))
            ->assertOk()
            ->assertJson(['changed' => true])
            ->assertJsonPath('unread_total', 0);
    }

    public function test_the_notification_feed_respects_the_type_filter(): void
    {
        $learner = $this->makeUser('learner', 'Filter Learner');
        $this->makeNotification($learner, 'Alert about fees', Notification::TYPE_ALERT);
        $this->makeNotification($learner, 'Information about term dates');

        $response = $this->actingAs($learner)
            ->getJson(route('notifications.updates', ['type' => 'alert', 'since' => 0, 'unread' => 0]))
            ->assertOk()
            ->assertJson(['changed' => true]);

        $html = $response->json('html');
        $this->assertStringContainsString('Alert about fees', $html);
        $this->assertStringNotContainsString('Information about term dates', $html);
    }

    public function test_the_notification_page_hands_itself_to_the_poller(): void
    {
        $learner = $this->makeUser('learner', 'Poll Page Learner');
        $notification = $this->makeNotification($learner, 'Page notification');

        $this->actingAs($learner)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('id="notifications-list"', false)
            ->assertSee('data-since="' . $notification->id . '"', false)
            ->assertSee('data-updates-url', false)
            ->assertSee('setInterval', false)
            ->assertSee('Page notification');
    }

    public function test_the_admin_notification_manager_refreshes(): void
    {
        $admin = $this->makeUser('admin', 'Manager Admin');
        $learner = $this->makeUser('learner', 'Manager Learner');
        $notification = $this->makeNotification($learner, 'Manager row');

        $this->actingAs($admin)->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('id="admin-notification-rows"', false)
            ->assertSee('setInterval', false)
            ->assertSee('Manager row');

        $response = $this->actingAs($admin)
            ->getJson(route('admin.notifications.updates', ['since' => 0]))
            ->assertOk()
            ->assertJson(['changed' => true])
            ->assertJsonPath('last_id', $notification->id);

        $this->assertStringContainsString('Manager row', $response->json('html'));

        $this->actingAs($admin)
            ->getJson(route('admin.notifications.updates', ['since' => $notification->id]))
            ->assertOk()
            ->assertJson(['changed' => false]);
    }

    public function test_the_broadcast_report_refreshes_with_its_counters(): void
    {
        $admin = $this->makeUser('admin', 'Broadcast Admin');
        $parentA = $this->makeUser('parent', 'Broadcast Parent A');
        $parentB = $this->makeUser('parent', 'Broadcast Parent B');

        $batch = (string) Str::uuid();
        foreach ([$parentA, $parentB] as $receiver) {
            Message::create([
                'sender_id' => $admin->id,
                'receiver_id' => $receiver->id,
                'body' => 'Term fees are now due.',
                'audience' => Message::AUDIENCE_PARENTS,
                'batch' => $batch,
            ]);
        }

        $this->actingAs($admin)->get(route('admin.messages.index'))
            ->assertOk()
            ->assertSee('id="broadcast-rows"', false)
            ->assertSee('setInterval', false)
            ->assertSee('Term fees are now due.');

        $response = $this->actingAs($admin)
            ->getJson(route('admin.messages.updates', ['since' => 0, 'unread' => 0]))
            ->assertOk()
            ->assertJson(['changed' => true])
            ->assertJsonPath('stats.broadcasts', 1)
            ->assertJsonPath('stats.delivered', 2)
            ->assertJsonPath('stats.unread', 2);

        $html = $response->json('html');
        $this->assertStringContainsString('Term fees are now due.', $html);
        $this->assertStringContainsString('Parents / Guardians', $html);
        $this->assertStringContainsString('0 / 2', $html);

        $this->actingAs($admin)
            ->getJson(route('admin.messages.updates', [
                'since' => $response->json('last_id'),
                'unread' => 2,
            ]))
            ->assertOk()
            ->assertJson(['changed' => false])
            ->assertJsonMissingPath('html');
    }

    public function test_the_live_update_endpoints_are_admin_only_where_they_should_be(): void
    {
        $tutor = $this->makeUser('tutor', 'Live Tutor');

        $this->actingAs($tutor)->getJson(route('admin.messages.updates'))->assertForbidden();
        $this->actingAs($tutor)->getJson(route('admin.notifications.updates'))->assertForbidden();
    }
}
