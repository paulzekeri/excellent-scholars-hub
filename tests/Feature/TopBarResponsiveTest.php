<?php

namespace Tests\Feature;

use App\Models\Learner;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Guards the authenticated top bar. On phones the drawer toggle is the only way
 * into the navigation, and the brand/quick actions must never overflow the
 * viewport (which is what happens when the wordmark cannot shrink).
 */
class TopBarResponsiveTest extends TestCase
{
    use DatabaseTransactions;

    private function makeLearner(): Learner
    {
        $user = User::create([
            'name' => 'Top Bar Learner',
            'email' => 'topbar.learner.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', 'learner')->firstOrFail()->id,
        ]);

        return Learner::create([
            'user_id' => $user->id,
            'grade_level' => 'JSS 2',
        ]);
    }

    private function header(): string
    {
        $html = $this->actingAs($this->makeLearner()->user)
            ->get(route('help.user-guide'))
            ->assertOk()
            ->getContent();

        $start = strpos($html, '<header');
        $end = strpos($html, '</header>');

        $this->assertNotFalse($start, 'The app layout must render a top bar.');
        $this->assertNotFalse($end, 'The top bar must be closed.');

        return substr($html, $start, $end - $start);
    }

    public function test_mobile_drawer_toggle_is_available_below_the_desktop_breakpoint(): void
    {
        $header = $this->header();

        $this->assertStringContainsString('id="esh-drawer-toggle"', $header);
        $this->assertStringContainsString('aria-controls="esh-sidebar"', $header, 'The drawer toggle must point at the sidebar.');
        $this->assertStringContainsString('lg:hidden', $header, 'The drawer toggle must be visible on phones and hidden on desktop.');
        $this->assertStringContainsString('id="esh-sidebar-toggle"', $header, 'Desktop keeps the collapse-to-rail button.');
    }

    public function test_top_bar_cannot_overflow_on_phones(): void
    {
        $header = $this->header();

        // Quick actions are pinned; the brand wordmark is the part that gives way.
        $this->assertStringContainsString('ml-auto flex shrink-0 items-center', $header, 'Quick actions must keep their size.');
        $this->assertStringContainsString('hidden min-w-0 truncate text-sm font-semibold sm:block', $header, 'The wordmark must hide on phones instead of squeezing the actions.');
        $this->assertStringNotContainsString('whitespace-nowrap', $header, 'No part of the top bar may be forced onto a single unshrinkable line.');
        $this->assertStringContainsString('<span class="hidden sm:inline">Logout</span>', $header, 'The logout label collapses to an icon on phones.');
    }

    public function test_icon_only_actions_stay_labelled_for_screen_readers(): void
    {
        $header = $this->header();

        $this->assertStringContainsString('aria-label="Messages"', $header);
        $this->assertStringContainsString('aria-label="Notifications"', $header);
        $this->assertStringContainsString('aria-label="Logout"', $header);
    }
}
