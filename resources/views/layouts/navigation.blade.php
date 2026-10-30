@php
    $navUser = auth()->user();
    $navRoleLabel = $navUser->isCeo()
        ? 'CEO'
        : ($navUser->isAdministrator() ? 'Administrator'
            : ($navUser->isTutor() ? 'Tutor' : ($navUser->isParent() ? 'Parent' : 'Learner')));
    $navUnreadMessages = $navUser->unreadMessagesCount();
    $navUnreadNotifications = $navUser->notifications()->where('is_read', false)->count();
    $navHome = $navUser->isAdministrator()
        ? route('admin.dashboard')
        : ($navUser->isTutor() ? route('tutor.dashboard') : ($navUser->isParent() ? route('parent.dashboard') : route('learner.dashboard')));
@endphp

<header class="sticky top-0 z-30 border-b border-white/10 bg-gradient-to-r from-[#0a2550] to-[#0d3070] text-white shadow-lg">
    <div class="flex h-16 items-center gap-2 px-3 sm:gap-3 sm:px-6 lg:px-8">
        <!-- Mobile drawer toggle -->
        <button type="button" id="esh-drawer-toggle"
                class="shrink-0 rounded-lg p-2 text-blue-100 hover:bg-white/10 hover:text-white lg:hidden"
                aria-label="Open navigation menu" aria-controls="esh-sidebar" aria-expanded="false">
            <i class="fas fa-bars text-lg"></i>
        </button>

        <!-- Desktop rail toggle -->
        <button type="button" id="esh-sidebar-toggle"
                class="hidden shrink-0 rounded-lg p-2 text-blue-100 hover:bg-white/10 hover:text-white lg:inline-flex"
                title="Collapse or expand the menu">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Brand: the wordmark hides on phones so the actions never get pushed out -->
        <a href="{{ $navHome }}" class="flex min-w-0 items-center gap-2">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white p-1 sm:h-9 sm:w-9 lg:hidden">
                <img src="{{ asset('images/logo.jpg') }}" alt="Excellent Scholars Hub logo" class="h-full w-full object-contain">
            </span>
            <span class="hidden min-w-0 truncate text-sm font-semibold sm:block sm:text-base">Excellent Scholars Hub</span>
            <span class="hidden shrink-0 rounded-full bg-white/10 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-sun sm:inline">{{ $navRoleLabel }}</span>
        </a>

        <!-- Quick actions -->
        <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
            <a href="{{ route('messages.index') }}" title="Messages" aria-label="Messages"
               class="relative rounded-lg p-2 text-blue-100 transition hover:bg-white/10 hover:text-white">
                <i class="fas fa-comments text-lg"></i>
                @if($navUnreadMessages > 0)
                    <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-sun px-1 text-[10px] font-bold text-[#0a2550]">{{ $navUnreadMessages > 9 ? '9+' : $navUnreadMessages }}</span>
                @endif
            </a>

            <a href="{{ route('notifications.index') }}" title="Notifications" aria-label="Notifications"
               class="relative rounded-lg p-2 text-blue-100 transition hover:bg-white/10 hover:text-white">
                <i class="fas fa-bell text-lg"></i>
                @if($navUnreadNotifications > 0)
                    <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-sun px-1 text-[10px] font-bold text-[#0a2550]">{{ $navUnreadNotifications > 9 ? '9+' : $navUnreadNotifications }}</span>
                @endif
            </a>

            <a href="{{ route('profile.edit') }}" title="My profile" aria-label="My profile"
               class="rounded-lg p-2 text-blue-100 transition hover:bg-white/10 hover:text-white">
                @if($navUser->photoUrl())
                    <img src="{{ $navUser->photoUrl() }}" alt="{{ $navUser->name }}" class="h-6 w-6 rounded-full object-cover">
                @else
                    <i class="fas fa-user-circle text-lg"></i>
                @endif
            </a>

            <a href="{{ route('help.user-guide') }}" title="Help & User Guide" aria-label="Help and user guide"
               class="hidden rounded-lg p-2 text-blue-100 transition hover:bg-white/10 hover:text-white sm:inline-flex">
                <i class="fas fa-question-circle text-lg"></i>
            </a>

            <span class="mx-1 hidden h-8 w-px bg-white/15 sm:block"></span>

            <span class="hidden max-w-[12rem] truncate text-sm text-blue-100 md:block">{{ $navUser->name }}</span>

            <form method="POST" action="{{ route('logout') }}" class="ml-1">
                @csrf
                <button type="submit" title="Logout" aria-label="Logout"
                        class="flex items-center gap-2 rounded-lg bg-orange-500 px-2.5 py-2 text-sm font-semibold text-blue-950 transition hover:bg-orange-400 sm:px-3">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="hidden sm:inline">Logout</span>
                </button>
            </form>
        </div>
    </div>
</header>
