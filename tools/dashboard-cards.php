<?php

/**
 * One-off dashboard rewrite (Tier 2, item 5 of the LMS colour pass).
 *
 * Swaps the per-card `border-l-4` rainbow on the admin and tutor dashboards for
 * the shared `partials.stat-card` component: neutral card + one coloured icon
 * chip whose tone carries meaning (blue people, navy academics, orange finance,
 * sun attention). Run once with:
 *
 *     php tools/dashboard-cards.php
 */

$base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;

function replaceBlock(string $file, string $start, string $end, string $replacement): void
{
    $contents = file_get_contents($file);
    $from = strpos($contents, $start);

    if ($from === false) {
        echo "SKIP (start not found): {$file}\n";

        return;
    }

    $to = strpos($contents, $end, $from);

    if ($to === false) {
        echo "SKIP (end not found): {$file}\n";

        return;
    }

    file_put_contents($file, substr($contents, 0, $from) . $replacement . substr($contents, $to));
    echo "OK: {$file}\n";
}

$adminGrid = <<<'BLADE'
    <!-- Stats cards: neutral surfaces, one coloured chip per meaning -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
        @include('partials.stat-card', ['label' => 'Total Users', 'value' => $totalUsers, 'icon' => 'fa-users', 'tone' => 'blue'])
        @include('partials.stat-card', ['label' => 'Total Tutors', 'value' => $totalTutors, 'icon' => 'fa-chalkboard-teacher', 'tone' => 'blue'])
        @include('partials.stat-card', ['label' => 'Total Learners', 'value' => $totalLearners, 'icon' => 'fa-user-graduate', 'tone' => 'blue'])
        @include('partials.stat-card', ['label' => 'Total Classes', 'value' => $totalClasses, 'icon' => 'fa-chalkboard', 'tone' => 'navy'])
        @include('partials.stat-card', ['label' => 'Schedules', 'value' => $totalSchedules, 'icon' => 'fa-calendar', 'tone' => 'navy'])
        @if(auth()->user()->canManagePayments())
            @include('partials.stat-card', ['label' => 'Payments', 'value' => $totalPayments, 'icon' => 'fa-money-bill', 'tone' => 'orange'])
        @endif
        @include('partials.stat-card', ['label' => 'Unread Messages', 'value' => $unreadMessages, 'icon' => 'fa-comments', 'tone' => 'sun', 'href' => route('messages.index')])
        @include('partials.stat-card', ['label' => 'New Enquiries', 'value' => $newEnquiries, 'icon' => 'fa-user-plus', 'tone' => 'sun', 'href' => route('admin.enquiries.index', ['status' => 'new'])])
        @include('partials.stat-card', ['label' => 'Enquiries In Progress', 'value' => $contactedEnquiries, 'icon' => 'fa-phone', 'tone' => 'sun', 'href' => route('admin.enquiries.index', ['status' => 'contacted'])])
    </div>

BLADE;

$tutorGrid = <<<'BLADE'
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
        @include('partials.stat-card', ['label' => 'My Classes', 'value' => $myClasses, 'icon' => 'fa-chalkboard', 'tone' => 'navy'])
        @include('partials.stat-card', ['label' => 'Assignments', 'value' => $totalAssignments, 'icon' => 'fa-tasks', 'tone' => 'blue'])
        @include('partials.stat-card', ['label' => 'Materials', 'value' => $totalMaterials, 'icon' => 'fa-folder-open', 'tone' => 'green'])
    </div>

BLADE;

$learnerGrid = <<<'BLADE'
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-5 mb-8">
        @include('partials.stat-card', ['label' => 'My Classes', 'value' => $myClasses->count(), 'icon' => 'fa-chalkboard', 'tone' => 'navy', 'href' => route('learner.classes')])
        @include('partials.stat-card', ['label' => 'Pending Assignments', 'value' => $pendingAssignments, 'icon' => 'fa-tasks', 'tone' => 'blue', 'href' => route('learner.assignments')])
        @include('partials.stat-card', ['label' => 'Materials', 'value' => $materials->count(), 'icon' => 'fa-folder-open', 'tone' => 'blue', 'href' => route('learner.materials')])
        @include('partials.stat-card', ['label' => 'Upcoming Sessions', 'value' => $upcomingCount, 'icon' => 'fa-calendar', 'tone' => 'sun', 'href' => route('learner.classes')])
    </div>
BLADE;

replaceBlock(
    $base . 'learner' . DIRECTORY_SEPARATOR . 'dashboard.blade.php',
    '    <div class="grid grid-cols-1 sm:grid-cols-4 gap-6 mb-8">',
    '    @php',
    $learnerGrid . PHP_EOL . PHP_EOL
);

replaceBlock(
    $base . 'admin' . DIRECTORY_SEPARATOR . 'dashboard.blade.php',
    '    <!-- Stats Cards -->',
    "    @include('partials.affirmation')",
    $adminGrid
);

replaceBlock(
    $base . 'tutor' . DIRECTORY_SEPARATOR . 'dashboard.blade.php',
    '    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">',
    "    <!-- Today's assessment calls -->",
    $tutorGrid
);
