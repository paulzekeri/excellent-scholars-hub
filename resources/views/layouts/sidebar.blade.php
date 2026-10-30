@php
    $user = auth()->user();

    // Badges shown next to the matching sidebar links.
    $navUnreadMessages = $user->unreadMessagesCount();
    $navUnreadNotifications = $user->notifications()->where('is_read', false)->count();

    $navRoleLabel = $user->isCeo()
        ? 'CEO'
        : ($user->isAdministrator() ? 'Administrator'
            : ($user->isTutor() ? 'Tutor' : ($user->isParent() ? 'Parent' : 'Learner')));

    $navHome = $user->isAdministrator()
        ? route('admin.dashboard')
        : ($user->isTutor() ? route('tutor.dashboard') : ($user->isParent() ? route('parent.dashboard') : route('learner.dashboard')));

    /*
    |--------------------------------------------------------------------------
    | Menu definition
    |--------------------------------------------------------------------------
    | Add a new screen by dropping one line into the matching section - the
    | markup below builds the groups, active states and badges automatically.
    | `match` accepts a route name or an array of patterns for routeIs().
    */
    $navSections = [];

    if ($user->isAdministrator()) {
        $navSections[] = [
            'title' => 'Overview',
            'icon' => 'fa-gauge-high',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'fa-tachometer-alt', 'route' => 'admin.dashboard'],
            ],
        ];

        $navSections[] = [
            'title' => 'People',
            'icon' => 'fa-users',
            'items' => [
                ['label' => 'Users & Roles', 'icon' => 'fa-user-friends', 'route' => 'admin.users.index'],
                ['label' => 'Admissions', 'icon' => 'fa-user-plus', 'route' => 'admin.enquiries.index', 'match' => 'admin.enquiries.*'],
            ],
        ];

        $navSections[] = [
            'title' => 'Academics',
            'icon' => 'fa-graduation-cap',
            'items' => [
                ['label' => 'Classes', 'icon' => 'fa-chalkboard', 'route' => 'admin.classes.index'],
                ['label' => 'Schedules', 'icon' => 'fa-calendar-alt', 'route' => 'admin.schedules.index'],
                ['label' => 'Attendance', 'icon' => 'fa-clipboard-check', 'route' => 'admin.oversight.attendance'],
                ['label' => 'Assignment Review', 'icon' => 'fa-tasks', 'route' => 'admin.oversight.assignments'],
                ['label' => 'Materials', 'icon' => 'fa-folder-open', 'route' => 'admin.oversight.materials'],
            ],
        ];

        if ($user->isCeo()) {
            $navSections[] = [
                'title' => 'Finance',
                'icon' => 'fa-money-bill-wave',
                'items' => [
                    ['label' => 'Payments', 'icon' => 'fa-receipt', 'route' => 'admin.payments.index', 'match' => 'admin.payments.*', 'hint' => 'CEO'],
                    ['label' => 'Bank accounts', 'icon' => 'fa-building-columns', 'route' => 'admin.payment-accounts.index', 'match' => 'admin.payment-accounts.*', 'hint' => 'CEO'],
                ],
            ];
        }

        $communicationItems = [
            ['label' => 'Messages', 'icon' => 'fa-comment-dots', 'route' => 'messages.index', 'badge' => $navUnreadMessages],
            ['label' => 'Broadcasts', 'icon' => 'fa-bullhorn', 'route' => 'admin.messages.index'],
            ['label' => 'Notifications', 'icon' => 'fa-bell', 'route' => 'admin.notifications.index', 'badge' => $navUnreadNotifications],
            ['label' => 'Affirmations', 'icon' => 'fa-sun', 'route' => 'admin.affirmations.index', 'match' => 'admin.affirmations.*'],
        ];

        if ($user->isCeo()) {
            $communicationItems[] = ['label' => 'Testimonials', 'icon' => 'fa-star', 'route' => 'admin.testimonials.index', 'match' => 'admin.testimonials.*'];
        }

        $navSections[] = [
            'title' => 'Communication',
            'icon' => 'fa-comments',
            'items' => $communicationItems,
        ];

        $navSections[] = [
            'title' => 'Learning',
            'icon' => 'fa-bullseye',
            'items' => [
                ['label' => 'Learning Goals', 'icon' => 'fa-bullseye', 'route' => 'admin.learning-goals.index'],
            ],
        ];
    } elseif ($user->isTutor()) {
        $navSections[] = [
            'title' => 'Overview',
            'icon' => 'fa-gauge-high',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'fa-tachometer-alt', 'route' => 'tutor.dashboard'],
            ],
        ];

        $navSections[] = [
            'title' => 'Teaching',
            'icon' => 'fa-chalkboard-teacher',
            'items' => [
                ['label' => 'Schedules', 'icon' => 'fa-calendar-alt', 'route' => 'tutor.schedules.index'],
                ['label' => 'Assignments', 'icon' => 'fa-tasks', 'route' => 'tutor.assignments.index'],
                ['label' => 'Materials', 'icon' => 'fa-folder-open', 'route' => 'tutor.materials.index'],
                ['label' => 'Learning Goals', 'icon' => 'fa-bullseye', 'route' => 'tutor.learning-goals.index'],
                ['label' => 'Assessments', 'icon' => 'fa-video', 'route' => 'tutor.assessments.index', 'match' => 'tutor.assessments.*'],
            ],
        ];
        $navSections[] = [
            'title' => 'Communication',
            'icon' => 'fa-comments',
            'items' => [
                ['label' => 'Messages', 'icon' => 'fa-comment-dots', 'route' => 'messages.index', 'badge' => $navUnreadMessages],
                ['label' => 'Notifications', 'icon' => 'fa-bell', 'route' => 'notifications.index', 'badge' => $navUnreadNotifications],
            ],
        ];
    } elseif ($user->isParent()) {
        $navSections[] = [
            'title' => 'Overview',
            'icon' => 'fa-gauge-high',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'fa-tachometer-alt', 'route' => 'parent.dashboard'],
            ],
        ];

        // Payments this parent submitted and the CEO has not verified yet.
        $navPendingPayments = $user->parent
            ? \App\Models\Payment::pending()
                ->whereIn('learner_id', $user->parent->children()->pluck('id'))
                ->count()
            : 0;

        $navSections[] = [
            'title' => 'Finance',
            'icon' => 'fa-money-bill-wave',
            'items' => [
                ['label' => 'Fees & Payments', 'icon' => 'fa-file-invoice-dollar', 'route' => 'parent.payments.index', 'badge' => $navPendingPayments],
            ],
        ];

        $navSections[] = [
            'title' => 'Communication',
            'icon' => 'fa-comments',
            'items' => [
                ['label' => 'Messages', 'icon' => 'fa-comment-dots', 'route' => 'messages.index', 'badge' => $navUnreadMessages],
                ['label' => 'Notifications', 'icon' => 'fa-bell', 'route' => 'notifications.index', 'badge' => $navUnreadNotifications],
            ],
        ];
    } else {
        $navSections[] = [
            'title' => 'Overview',
            'icon' => 'fa-gauge-high',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'fa-tachometer-alt', 'route' => 'learner.dashboard'],
            ],
        ];

        $navSections[] = [
            'title' => 'My Learning',
            'icon' => 'fa-book-open',
            'items' => [
                ['label' => 'My Classes', 'icon' => 'fa-chalkboard', 'route' => 'learner.classes'],
                ['label' => 'Assignments', 'icon' => 'fa-tasks', 'route' => 'learner.assignments'],
                ['label' => 'Materials', 'icon' => 'fa-folder-open', 'route' => 'learner.materials'],
            ],
        ];

        $navSections[] = [
            'title' => 'Communication',
            'icon' => 'fa-comments',
            'items' => [
                ['label' => 'Messages', 'icon' => 'fa-comment-dots', 'route' => 'messages.index', 'badge' => $navUnreadMessages],
                ['label' => 'Notifications', 'icon' => 'fa-bell', 'route' => 'notifications.index', 'badge' => $navUnreadNotifications],
            ],
        ];
    }

    $navSections[] = [
        'title' => 'Support',
        'icon' => 'fa-life-ring',
        'items' => [
            ['label' => 'Help & User Guide', 'icon' => 'fa-question-circle', 'route' => 'help.user-guide'],
        ],
    ];
@endphp

<aside id="esh-sidebar"
       class="esh-sidebar fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-gradient-to-b from-[#0a2550] via-[#0d3070] to-[#0a2550] text-blue-50 shadow-2xl transition-transform duration-200 lg:translate-x-0">

    <!-- Brand -->
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-4">
        <a href="{{ $navHome }}" class="flex min-w-0 items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white p-1 shadow">
                <img src="{{ asset('images/logo.jpg') }}" alt="Excellent Scholars Hub logo" class="h-full w-full object-contain">
            </span>
            <span class="esh-label min-w-0 leading-tight">
                <span class="block truncate text-sm font-bold text-white">Excellent Scholars Hub</span>
                <span class="block text-[11px] font-semibold uppercase tracking-wider text-orange-300">{{ $navRoleLabel }}</span>
            </span>
        </a>
        <button type="button" class="esh-drawer-close ml-auto rounded-lg p-2 text-blue-100 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Close menu">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Grouped menu -->
    <nav class="flex-1 space-y-2 overflow-y-auto px-3 py-4">
        @foreach($navSections as $section)
            <details class="esh-group" data-group="{{ \Illuminate\Support\Str::slug($section['title']) }}" open>
                <summary class="flex cursor-pointer select-none items-center gap-2 rounded-lg px-2 py-2 text-[11px] font-bold uppercase tracking-widest text-orange-300/80 hover:bg-white/5 hover:text-orange-200">
                    <i class="fas {{ $section['icon'] }} w-4 text-center text-[10px]"></i>
                    <span class="esh-label flex-1">{{ $section['title'] }}</span>
                    <i class="fas fa-chevron-down esh-label esh-chevron text-[9px]"></i>
                </summary>
                <div class="esh-group-body mt-1 space-y-1">
                    @foreach($section['items'] as $item)
                        @php
                            $patterns = (array) ($item['match'] ?? $item['route']);
                            $active = request()->routeIs(...$patterns);
                        @endphp
                        <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                           class="esh-item relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-white/15 text-white ring-1 ring-white/10' : 'text-blue-100/80 hover:bg-white/10 hover:text-white' }}">
                            @if($active)
                                <span class="absolute bottom-1.5 left-0 top-1.5 w-1 rounded-r bg-orange-400"></span>
                            @endif
                            <i class="fas {{ $item['icon'] }} w-5 shrink-0 text-center text-[15px] {{ $active ? 'text-orange-300' : 'text-blue-200/70' }}"></i>
                            <span class="esh-label flex-1 truncate">{{ $item['label'] }}</span>
                            @if(!empty($item['badge']))
                                <span class="esh-badge rounded-full bg-orange-500 px-2 py-0.5 text-[11px] font-bold text-white">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                            @elseif(!empty($item['hint']))
                                <span class="esh-label rounded bg-white/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-orange-200">{{ $item['hint'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </details>
        @endforeach
    </nav>

    <!-- Signed in as -->
    <div class="border-t border-white/10 p-3">
        <div class="flex items-center gap-3 rounded-lg bg-white/5 p-2">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-orange-500 text-sm font-bold text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            <div class="esh-label min-w-0 flex-1 leading-tight">
                <p class="truncate text-sm font-semibold text-white">{{ $user->name }}</p>
                <p class="truncate text-[11px] text-blue-200/80">{{ $navRoleLabel }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg p-2 text-blue-100 hover:bg-red-500 hover:text-white" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
    </div>
</aside>

<div id="esh-sidebar-overlay" class="fixed inset-0 z-40 hidden bg-slate-900/60 lg:hidden"></div>

