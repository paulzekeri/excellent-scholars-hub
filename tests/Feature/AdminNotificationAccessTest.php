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

    public function test_notification_form_lists_the_supported_types_with_guidance()
    {
        $admin = $this->makeUser('admin', 'Test Admin Type Form');

        $this->actingAs($admin)
            ->get(route('admin.notifications.create'))
            ->assertOk()
            ->assertSee('name="type"', false)
            ->assertSee('Information')
            ->assertSee('General news the recipient should know. No action required.')
            ->assertSee('Reminder')
            ->assertSee('A nudge about something still to be done, usually with a deadline.')
            ->assertSee('Alert')
            ->assertSee('Urgent or exceptional news that needs immediate attention.');
    }

    public function test_admin_must_pick_one_of_the_supported_notification_types()
    {
        $admin = $this->makeUser('admin', 'Test Admin Type Guard');
        $recipient = $this->makeUser('learner', 'Test Learner Type Guard');

        $this->actingAs($admin)
            ->post(route('admin.notifications.store'), [
                'title' => 'Unsupported type',
                'message' => 'This must never be stored.',
                'type' => 'announcement',
                'user_id' => $recipient->id,
            ])
            ->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('notifications', ['title' => 'Unsupported type']);
    }

    public function test_each_supported_type_is_saved_as_a_delivered_in_app_notification()
    {
        $admin = $this->makeUser('admin', 'Test Admin Type Sender');
        $recipient = $this->makeUser('learner', 'Test Learner Type Reader');

        foreach (Notification::COMPOSABLE_TYPES as $type) {
            $this->actingAs($admin)
                ->post(route('admin.notifications.store'), [
                    'title' => 'Typed notification ' . $type,
                    'message' => 'Body for the ' . $type . ' notification.',
                    'type' => $type,
                    'user_id' => $recipient->id,
                ])
                ->assertRedirect(route('admin.notifications.index'));

            $this->assertDatabaseHas('notifications', [
                'user_id' => $recipient->id,
                'type' => $type,
                'channel' => 'in_app',
                'status' => 'sent',
            ]);
        }
    }

    public function test_recipient_can_filter_notifications_by_type()
    {
        $learner = $this->makeUser('learner', 'Test Learner Filter');

        foreach (Notification::COMPOSABLE_TYPES as $type) {
            Notification::create([
                'user_id' => $learner->id,
                'type' => $type,
                'title' => 'Only ' . $type . ' title',
                'message' => 'Body for ' . $type . '.',
                'channel' => 'in_app',
                'is_read' => false,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        $this->actingAs($learner)
            ->get(route('notifications.index', ['type' => Notification::TYPE_ALERT]))
            ->assertOk()
            ->assertSee('Only alert title')
            ->assertDontSee('Only info title')
            ->assertDontSee('Only reminder title');

        $this->actingAs($learner)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Only info title')
            ->assertSee('Only reminder title')
            ->assertSee('Only alert title');
    }

    public function test_unknown_type_filter_falls_back_to_all_notifications()
    {
        $learner = $this->makeUser('learner', 'Test Learner Bad Filter');

        Notification::create([
            'user_id' => $learner->id,
            'type' => Notification::TYPE_INFO,
            'title' => 'Visible despite bad filter',
            'message' => 'Body.',
            'channel' => 'in_app',
            'is_read' => false,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('notifications.index', ['type' => 'bogus']))
            ->assertOk()
            ->assertSee('Visible despite bad filter');
    }

    public function test_direct_messages_notify_with_the_system_message_type()
    {
        $parent = $this->makeUser('parent', 'Test Parent Messenger');
        $admin = $this->makeUser('admin', 'Test Admin Messenger');

        $this->actingAs($parent)
            ->post(route('messages.store', $admin), ['body' => 'Hello from a parent.'])
            ->assertRedirect(route('messages.show', $admin));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => Notification::TYPE_MESSAGE,
            'channel' => 'in_app',
            'status' => 'sent',
        ]);
    }
}
