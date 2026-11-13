<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Installable app (PWA): the manifest, its icons, the service worker and the
 * "Install app" button in the top bar.
 */
class ProgressiveWebAppTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'PWA Learner',
            'email' => 'pwa.learner.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', 'learner')->firstOrFail()->id,
            'is_active' => true,
        ]);
    }

    /**
     * The manifest is valid JSON, is installable (standalone) and every icon it
     * points at really exists on disk.
     */
    public function test_manifest_is_valid_and_every_icon_exists(): void
    {
        $path = public_path('manifest.webmanifest');

        $this->assertFileExists($path);

        $manifest = json_decode(file_get_contents($path), true);

        $this->assertIsArray($manifest, 'manifest.webmanifest is not valid JSON.');
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertNotEmpty($manifest['icons'], 'A manifest needs at least one icon.');

        foreach ($manifest['icons'] as $icon) {
            $this->assertArrayHasKey('src', $icon);
            $this->assertFileExists(
                public_path(ltrim($icon['src'], '/')),
                "The manifest references a missing icon: {$icon['src']}"
            );
        }

        $this->assertContains(
            'maskable',
            array_column($manifest['icons'], 'purpose'),
            'Android crops the launcher icon, so a maskable icon is required.'
        );
    }

    /**
     * The signed-in shell links the manifest, loads the PWA script (which
     * registers the worker and captures `beforeinstallprompt`) and renders the
     * install button.
     */
    public function test_signed_in_pages_link_the_manifest_and_offer_the_install_button(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('assets/js/esh-pwa.js', false)
            ->assertSee('data-pwa-install', false)
            ->assertSee('data-pwa-install-button', false)
            ->assertSee('esh:install-available', false)
            ->assertSee('Add to Home Screen', false);
    }

    /**
     * The worker keeps the handlers that make the app installable and the push
     * notifications clickable.
     */
    public function test_service_worker_handles_install_fetch_and_notification_clicks(): void
    {
        $worker = file_get_contents(public_path('service-worker.js'));

        $this->assertStringContainsString("addEventListener('install'", $worker);
        $this->assertStringContainsString("addEventListener('activate'", $worker);
        $this->assertStringContainsString("addEventListener('fetch'", $worker);
        $this->assertStringContainsString("addEventListener('notificationclick'", $worker);
        $this->assertStringContainsString('clients.openWindow', $worker);
    }
}
