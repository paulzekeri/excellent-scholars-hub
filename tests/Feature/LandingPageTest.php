<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Guards the public landing page. The page is a standalone Blade view (Tailwind
 * CDN, no app layout), so a misplaced closing tag silently nests every section
 * inside the header and squeezes the whole page into the navigation bar.
 */
class LandingPageTest extends TestCase
{
    use DatabaseTransactions;

    private function makeTutor(): Tutor
    {
        $user = User::create([
            'name' => 'Landing Tutor',
            'email' => 'landing.tutor.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', 'tutor')->firstOrFail()->id,
        ]);

        return Tutor::create(['user_id' => $user->id]);
    }

    public function test_guests_see_the_landing_page_with_a_closed_header(): void
    {
        $html = $this->get('/')
            ->assertOk()
            ->assertSee('Learning that fits your child')
            ->assertSee('Classes open for registration')
            ->assertSee('From registration to results in four steps')
            ->assertSee('Ready to join the next term?')
            ->assertSee(route('login'), false)
            ->assertSee(route('register'), false)
            ->getContent();

        // Header and page content must be siblings, never nested.
        $headerEnd = strpos($html, '</header>');
        $firstSection = strpos($html, '<section');

        $this->assertNotFalse($headerEnd, 'The landing page header is never closed.');
        $this->assertNotFalse($firstSection, 'The landing page has no sections.');
        $this->assertLessThan($firstSection, $headerEnd, 'Page sections must render after </header>.');

        $this->assertSame(1, substr_count($html, '<header'), 'The landing page must contain exactly one header.');
        $this->assertSame(1, substr_count($html, '</header>'), 'The landing page must close its header exactly once.');
        $this->assertSame(1, substr_count($html, '</html>'), 'The landing page must not contain duplicate closing markup.');
    }

    public function test_header_collapses_into_a_menu_on_small_screens(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $start = strpos($html, '<header');
        $end = strpos($html, '</header>');
        $header = substr($html, $start, $end - $start);

        // Phones get a compact menu button instead of the full section-link row.
        $this->assertStringContainsString('id="esh-mobile-menu"', $header, 'Small screens need a collapsible section menu.');
        $this->assertStringContainsString('aria-label="Open section menu"', $header);
        $this->assertStringContainsString('hidden lg:flex ml-auto', $header, 'The section links must only show on desktop.');

        // The brand must be allowed to shrink; a nowrap/shrink-0 brand pushes the
        // Register button off the viewport on 320-390px phones.
        $this->assertStringNotContainsString('whitespace-nowrap', $header, 'The header brand must not be forced onto one line.');
        $this->assertStringContainsString('flex min-w-0 items-center gap-2', $header, 'The brand needs min-w-0 so it can shrink.');
    }

    public function test_only_active_classes_are_advertised(): void
    {
        $tutor = $this->makeTutor();

        Classes::create([
            'name' => 'JSS1 English Mastery',
            'code' => 'ENG-' . uniqid(),
            'tutor_id' => $tutor->id,
            'is_active' => true,
        ]);

        Classes::create([
            'name' => 'Retired Draft Class',
            'code' => 'DRAFT-' . uniqid(),
            'tutor_id' => $tutor->id,
            'is_active' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('JSS1 English Mastery')
            ->assertDontSee('Retired Draft Class')
            ->assertSee('Register for this class');
    }
}
