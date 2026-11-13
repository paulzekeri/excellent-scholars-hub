<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Learner;
use App\Models\Message;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The inbox used to be a bare list of names with a small grey line of help, so a
 * new parent or learner could land on /messages and not know what to do next.
 * It now opens with a "start by clicking a name" callout and every contact shows
 * a visible Send message / Open chat action.
 */
class MessageInboxTest extends TestCase
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

    private function makeTutor(string $name): Tutor
    {
        return Tutor::create(['user_id' => $this->makeUser('tutor', $name)->id]);
    }

    private function makeClass(Tutor $tutor, string $name): Classes
    {
        return Classes::create([
            'name' => $name,
            'code' => 'IN-' . strtoupper(uniqid()),
            'tutor_id' => $tutor->id,
            'is_active' => true,
        ]);
    }

    public function test_a_new_inbox_says_how_to_start_a_message(): void
    {
        $admin = $this->makeUser('admin', 'Guidance Admin');
        $parentUser = $this->makeUser('parent', 'Guidance Parent');

        $this->actingAs($parentUser)->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Want to send a message? Start by clicking a name below.')
            ->assertSee('Click a contact below to open the conversation and send a message.')
            ->assertSee('Guidance Admin')
            // Each contact carries a visible action, not just a hover effect.
            ->assertSee('Send message')
            ->assertSee('href="' . route('messages.show', $admin) . '"', false)
            ->assertSee('aria-label="Send a message to Guidance Admin"', false)
            // The page hands itself to the poller so new messages appear live.
            ->assertSee('data-updates-url', false)
            ->assertSee('data-since="0"', false)
            ->assertSee('setInterval', false);
    }

    public function test_an_existing_conversation_offers_open_chat_and_shows_unread_messages(): void
    {
        $admin = $this->makeUser('admin', 'Thread Admin');
        $parentUser = $this->makeUser('parent', 'Thread Parent');

        Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $parentUser->id,
            'body' => 'Welcome to the school portal.',
        ]);

        $this->actingAs($parentUser)->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Open chat')
            ->assertSee('1 new')
            ->assertSee('Welcome to the school portal.')
            ->assertSee('aria-label="Open chat with Thread Admin"', false);
    }

    public function test_a_learner_only_sees_the_school_and_their_own_tutors(): void
    {
        $learnerUser = $this->makeUser('learner', 'Inbox Learner');
        $learner = Learner::create([
            'user_id' => $learnerUser->id,
            'grade_level' => 'JSS 2',
        ]);

        $school = $this->makeUser('admin', 'Learner Inbox Admin');
        $ownTutor = $this->makeTutor('Own Tutor');
        $otherTutor = $this->makeTutor('Other Tutor');

        $ownClass = $this->makeClass($ownTutor, 'Inbox Maths');
        $learner->classes()->attach($ownClass->id);
        $this->makeClass($otherTutor, 'Inbox English');

        $this->actingAs($learnerUser)->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Learner Inbox Admin')
            ->assertSee('Own Tutor')
            ->assertSee('Send message')
            ->assertDontSee('Other Tutor')
            ->assertSee('href="' . route('messages.show', $school) . '"', false);
    }

    public function test_the_inbox_can_refresh_itself_when_a_message_arrives(): void
    {
        $admin = $this->makeUser('admin', 'Poll Admin');
        $parentUser = $this->makeUser('parent', 'Poll Parent');

        // Nothing has been said yet, so the page has nothing to redraw.
        $this->actingAs($parentUser)
            ->getJson(route('messages.updates.inbox', ['since' => 0, 'unread' => 0]))
            ->assertOk()
            ->assertJson(['changed' => false, 'last_id' => 0]);

        $message = Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $parentUser->id,
            'body' => 'Welcome to the new term.',
        ]);

        $response = $this->actingAs($parentUser)
            ->getJson(route('messages.updates.inbox', ['since' => 0, 'unread' => 0]))
            ->assertOk()
            ->assertJson(['changed' => true])
            ->assertJsonPath('last_id', $message->id)
            ->assertJsonPath('unread_total', 1);

        // The HTML is rendered from the same partial as the first page load.
        $html = $response->json('html');
        $this->assertStringContainsString('Poll Admin', $html);
        $this->assertStringContainsString('Welcome to the new term.', $html);
        $this->assertStringContainsString('1 new', $html);
        $this->assertStringContainsString('Send message', $html);

        // With the new watermark the poll is a no-op (no wasted rendering).
        $this->actingAs($parentUser)
            ->getJson(route('messages.updates.inbox', ['since' => $message->id, 'unread' => 1]))
            ->assertOk()
            ->assertJson(['changed' => false])
            ->assertJsonMissingPath('html');
    }

    public function test_an_open_conversation_refreshes_and_marks_new_messages_read(): void
    {
        $admin = $this->makeUser('admin', 'Thread Poll Admin');
        $parentUser = $this->makeUser('parent', 'Thread Poll Parent');

        $first = Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $parentUser->id,
            'body' => 'First note from the school.',
        ]);

        $response = $this->actingAs($parentUser)
            ->getJson(route('messages.updates.thread', ['user' => $admin, 'last' => 0]))
            ->assertOk()
            ->assertJson(['changed' => true])
            ->assertJsonPath('last_id', $first->id);

        $this->assertStringContainsString('First note from the school.', $response->json('html'));
        $this->assertTrue($first->fresh()->is_read);

        // Opening the thread page leaves the same watermark, so nothing redraws.
        $this->actingAs($parentUser)
            ->getJson(route('messages.updates.thread', ['user' => $admin, 'last' => $first->id]))
            ->assertOk()
            ->assertJson(['changed' => false])
            ->assertJsonMissingPath('html');

        // A reply while the page is open is picked up on the next poll.
        $second = Message::create([
            'sender_id' => $parentUser->id,
            'receiver_id' => $admin->id,
            'body' => 'Thank you, noted.',
        ]);

        $response = $this->actingAs($parentUser)
            ->getJson(route('messages.updates.thread', ['user' => $admin, 'last' => $first->id]))
            ->assertOk()
            ->assertJson(['changed' => true])
            ->assertJsonPath('last_id', $second->id);

        $html = $response->json('html');
        $this->assertStringContainsString('First note from the school.', $html);
        $this->assertStringContainsString('Thank you, noted.', $html);
    }

    public function test_the_update_endpoints_stay_inside_the_allowed_conversations(): void
    {
        $parentA = $this->makeUser('parent', 'Poll Parent A');
        $parentB = $this->makeUser('parent', 'Poll Parent B');

        $this->actingAs($parentA)
            ->getJson(route('messages.updates.thread', ['user' => $parentB, 'last' => 0]))
            ->assertForbidden();
    }

    public function test_the_conversation_page_hands_the_thread_to_the_poller(): void
    {
        $admin = $this->makeUser('admin', 'Poller Admin');
        $parentUser = $this->makeUser('parent', 'Poller Parent');

        $message = Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $parentUser->id,
            'body' => 'First message from the school.',
        ]);

        $this->actingAs($parentUser)->get(route('messages.show', $admin))
            ->assertOk()
            ->assertSee('id="messages-thread"', false)
            ->assertSee('messages/' . $admin->id . '/updates', false)
            ->assertSee('data-last="' . $message->id . '"', false)
            ->assertSee('setInterval', false)
            ->assertSee('First message from the school.');

        $this->assertTrue($message->fresh()->is_read);
    }
}
