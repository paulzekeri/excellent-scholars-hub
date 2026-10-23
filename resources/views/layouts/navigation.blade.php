<nav class="bg-white shadow-lg border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isTutor() ? route('tutor.dashboard') : (auth()->user()->isParent() ? route('parent.dashboard') : route('learner.dashboard'))) }}" class="flex items-center">
                    <span class="text-xl font-bold text-indigo-600">ESH</span>
                    <span class="ml-1 text-gray-600 text-sm hidden sm:inline">Excellent Scholars Hub</span>
                </a>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden md:flex md:items-center md:space-x-4">
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-users mr-1"></i> Users
                    </a>
                    <!-- Academics Dropdown: Classes, Schedules, Payments -->
                    <div class="relative group">
                        <button class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                            <i class="fas fa-graduation-cap mr-1"></i> Academics
                            <i class="fas fa-chevron-down ml-1 text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-1 w-52 bg-white rounded-lg shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <a href="{{ route('admin.classes.index') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-t-lg">
                                <i class="fas fa-chalkboard mr-3 text-yellow-600 w-4"></i> Classes
                            </a>
                            <a href="{{ route('admin.schedules.index') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">
                                <i class="fas fa-calendar mr-3 text-purple-600 w-4"></i> Schedules
                            </a>
                            <a href="{{ route('admin.payments.index') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-b-lg">
                                <i class="fas fa-money-bill mr-3 text-pink-600 w-4"></i> Payments
                            </a>
                        </div>
                    </div>
                    <!-- Oversight Dropdown -->
                    <div class="relative group">
                        <button class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                            <i class="fas fa-binoculars mr-1"></i> Oversight
                            <i class="fas fa-chevron-down ml-1 text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-1 w-52 bg-white rounded-lg shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <a href="{{ route('admin.oversight.attendance') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-t-lg">
                                <i class="fas fa-clipboard-check mr-3 text-blue-500 w-4"></i> Attendance
                            </a>
                            <a href="{{ route('admin.oversight.assignments') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">
                                <i class="fas fa-tasks mr-3 text-orange-500 w-4"></i> Assignments
                            </a>
                            <a href="{{ route('admin.oversight.materials') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-b-lg">
                                <i class="fas fa-folder mr-3 text-purple-500 w-4"></i> Materials
                            </a>
                        </div>
                    </div>
                    <a href="{{ route('admin.notifications.index') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-bullhorn mr-1"></i> Notifications
                    </a>
                @elseif(auth()->user()->isTutor())
                    <a href="{{ route('tutor.dashboard') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                    <a href="{{ route('tutor.schedules.index') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-calendar mr-1"></i> Schedules
                    </a>
                    <a href="{{ route('tutor.assignments.index') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-tasks mr-1"></i> Assignments
                    </a>
                    <a href="{{ route('tutor.materials.index') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-folder mr-1"></i> Materials
                    </a>
                @elseif(auth()->user()->isParent())
                    <a href="{{ route('parent.dashboard') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                @elseif(auth()->user()->isLearner())
                    <a href="{{ route('learner.dashboard') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                    <a href="{{ route('learner.classes') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-chalkboard mr-1"></i> My Classes
                    </a>
                    <a href="{{ route('learner.assignments') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-tasks mr-1"></i> Assignments
                    </a>
                    <a href="{{ route('learner.materials') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-folder mr-1"></i> Materials
                    </a>
                @endif

                <!-- Notifications Bell (Icon only) -->
                <a href="{{ route('notifications.index') }}" class="text-gray-500 hover:text-indigo-600 px-2 py-2 rounded-md text-sm font-medium relative" title="View Notifications">
                    <i class="fas fa-bell text-lg"></i>
                    @php $unreadCount = auth()->user()->notifications()->where('is_read', false)->count(); @endphp
                    @if($unreadCount > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </a>

                <!-- Help Guide Link -->
                <a href="{{ route('help.user-guide') }}" class="text-gray-500 hover:text-indigo-600 px-2 py-2 rounded-md text-sm font-medium" title="User Guide">
                    <i class="fas fa-question-circle mr-1"></i> Help
                </a>

                <!-- User Dropdown -->
                <div class="relative ml-4">
                    <div class="flex items-center space-x-3">
                        <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                <i class="fas fa-sign-out-alt mr-1"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden flex items-center">
                <button id="mobile-menu-button" class="text-gray-700 hover:text-indigo-600 focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
        <div class="px-4 py-3 space-y-2">
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Dashboard</a>
                <a href="{{ route('admin.users.index') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Users</a>
                <a href="{{ route('admin.classes.index') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Classes</a>
                <a href="{{ route('admin.schedules.index') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Schedules</a>
                <a href="{{ route('admin.payments.index') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Payments</a>
                <a href="{{ route('admin.oversight.attendance') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Attendance</a>
                <a href="{{ route('admin.oversight.assignments') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Assignments</a>
                <a href="{{ route('admin.oversight.materials') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Materials</a>
                <a href="{{ route('admin.notifications.index') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Notifications</a>
            @elseif(auth()->user()->isTutor())
                <a href="{{ route('tutor.dashboard') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Dashboard</a>
                <a href="{{ route('tutor.schedules.index') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Schedules</a>
                <a href="{{ route('tutor.assignments.index') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Assignments</a>
                <a href="{{ route('tutor.materials.index') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Materials</a>
            @elseif(auth()->user()->isParent())
                <a href="{{ route('parent.dashboard') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Dashboard</a>
            @elseif(auth()->user()->isLearner())
                <a href="{{ route('learner.dashboard') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Dashboard</a>
                <a href="{{ route('learner.classes') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">My Classes</a>
                <a href="{{ route('learner.assignments') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Assignments</a>
                <a href="{{ route('learner.materials') }}" class="block text-gray-700 hover:bg-indigo-50 px-3 py-2 rounded">Materials</a>
            @endif

            <a href="{{ route('notifications.index') }}" class="block text-gray-500 hover:bg-indigo-50 px-3 py-2 rounded">
                <i class="fas fa-bell mr-1"></i> Notifications
                @php $unreadCountMobile = auth()->user()->notifications()->where('is_read', false)->count(); @endphp
                @if($unreadCountMobile > 0)
                    <span class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5 ml-2">{{ $unreadCountMobile }}</span>
                @endif
            </a>

            <a href="{{ route('help.user-guide') }}" class="block text-gray-500 hover:bg-indigo-50 px-3 py-2 rounded">
                <i class="fas fa-question-circle mr-1"></i> Help & User Guide
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left text-red-600 hover:bg-red-50 px-3 py-2 rounded">Logout</button>
            </form>
        </div>
    </div>
</nav>
