<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminNotificationAccessTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser($roleSlug, $name)
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
        ]);
    }

    public function test_ceo_and_admin_can_open_the_notification_manager()
    {
        $ceo = $this->makeUser('ceo', 'Test Ceo');
        $admin = $this->makeUser('admin', 'Test Admin');

        $this->actingAs($ceo)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Send Notification');

        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Send Notification');
    }

    public function test_other_roles_cannot_open_the_notification_manager()
    {
        foreach (['tutor', 'learner', 'parent'] as $slug) {
            $user = $this->makeUser($slug, 'Test ' . ucfirst($slug));

            $this->actingAs($user)
                ->get(route('admin.notifications.index'))
                ->assertForbidden();
        }
    }

    public function test_ceo_can_read_the_full_message_of_a_sent_notification()
    {
        $ceo = $this->makeUser('ceo', 'Test Ceo Reader');
        $recipient = $this->makeUser('learner', 'Test Learner Recipient');

        $notification = Notification::create([
            'user_id' => $recipient->id,
            'type' => 'info',
            'title' => 'Fee reminder',
            'message' => "Please settle the term fee before Friday.\nThank you.",
            'channel' => 'in_app',
            'is_read' => false,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->actingAs($ceo)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('notification-read-btn', false)
            ->assertSee('id="notification-modal"', false)
            ->assertSee('data-message="' . e($notification->message) . '"', false);
    }
}
