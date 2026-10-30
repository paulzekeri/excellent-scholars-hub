<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Excellent Scholars Hub') - ESH LMS</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        esh: {
                            navy: '#0a2550',
                            blue: '#1d4ed8',
                            orange: '#F26B21',
                            sun: '#F7C94B',
                            ink: '#0f1f3a',
                        },
                        sun: { DEFAULT: '#F7C94B', light: '#FFF7DB', ink: '#8A6100', edge: '#F4DFA0' },
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @livewireStyles
</head>
<body class="bg-[#F5F8FC] font-sans antialiased">
    <style>
        /* Brand typography: Poppins headings, Inter body (utility classes still win). */
        h1, h2, h3 { font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif; letter-spacing: -0.015em; }

        /* App shell: blue sidebar + slim top bar (school colours) */
        .esh-shell { transition: padding-left .2s ease; }
        .esh-sidebar { transition: width .2s ease, transform .2s ease; }
        .esh-group > summary { list-style: none; }
        .esh-group > summary::-webkit-details-marker { display: none; }
        .esh-chevron { transition: transform .2s ease; }
        .esh-group[open] > summary .esh-chevron { transform: rotate(180deg); }

        @media (min-width: 1024px) {
            .esh-shell { padding-left: 16rem; }
            body.esh-collapsed .esh-shell { padding-left: 4.75rem; }
            body.esh-collapsed .esh-sidebar { width: 4.75rem; }
            body.esh-collapsed .esh-sidebar .esh-label { display: none; }
            body.esh-collapsed .esh-sidebar .esh-badge { display: none; }
            body.esh-collapsed .esh-sidebar .esh-item { justify-content: center; padding-left: .5rem; padding-right: .5rem; }
            body.esh-collapsed .esh-sidebar summary { justify-content: center; padding-left: 0; padding-right: 0; }
            body.esh-collapsed .esh-sidebar .esh-group > .esh-group-body { display: block !important; }
            body.esh-collapsed .esh-sidebar .esh-item { margin-top: .25rem; }
        }
    </style>

    @if(auth()->check())
        @include('layouts.sidebar')
    @endif

    <div class="{{ auth()->check() ? 'esh-shell' : '' }} flex min-h-screen flex-col">
        @if(auth()->check())
            @include('layouts.navigation')
        @endif

        <main class="min-w-0 flex-1">
            @if(session('success'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded" role="alert">
                        <p>{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-orange-100 border-l-4 border-orange-500 text-orange-800 p-4 rounded" role="alert">
                        <p>{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-orange-100 border-l-4 border-orange-500 text-orange-800 p-4 rounded" role="alert">
                        <p class="font-medium mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Please fix the following errors:</p>
                        <ul class="list-disc list-inside text-sm">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @livewireScripts
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const body = document.body;
            const sidebar = document.getElementById('esh-sidebar');
            const overlay = document.getElementById('esh-sidebar-overlay');
            const drawerToggle = document.getElementById('esh-drawer-toggle');
            const railToggle = document.getElementById('esh-sidebar-toggle');
            const groups = Array.prototype.slice.call(document.querySelectorAll('.esh-group'));

            // Mobile off-canvas drawer.
            function openDrawer() {
                if (!sidebar) return;
                sidebar.classList.remove('-translate-x-full');
                overlay && overlay.classList.remove('hidden');
                body.classList.add('overflow-hidden');
                drawerToggle && drawerToggle.setAttribute('aria-expanded', 'true');
            }

            function closeDrawer() {
                if (!sidebar) return;
                sidebar.classList.add('-translate-x-full');
                overlay && overlay.classList.add('hidden');
                body.classList.remove('overflow-hidden');
                drawerToggle && drawerToggle.setAttribute('aria-expanded', 'false');
            }

            drawerToggle && drawerToggle.addEventListener('click', openDrawer);
            overlay && overlay.addEventListener('click', closeDrawer);
            document.querySelectorAll('.esh-drawer-close').forEach(function (button) {
                button.addEventListener('click', closeDrawer);
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeDrawer();
            });

            // Desktop: collapse the sidebar to an icon rail, remembered per browser.
            if (localStorage.getItem('esh.nav.collapsed') === '1') {
                body.classList.add('esh-collapsed');
                groups.forEach(function (group) { group.open = true; });
            }

            railToggle && railToggle.addEventListener('click', function () {
                const collapsed = body.classList.toggle('esh-collapsed');
                localStorage.setItem('esh.nav.collapsed', collapsed ? '1' : '0');
                if (collapsed) groups.forEach(function (group) { group.open = true; });
            });

            // Remember which groups a user folded away.
            let savedGroups = {};
            try {
                savedGroups = JSON.parse(localStorage.getItem('esh.nav.groups') || '{}');
            } catch (error) {
                savedGroups = {};
            }

            groups.forEach(function (group) {
                const key = group.getAttribute('data-group');
                if (key && Object.prototype.hasOwnProperty.call(savedGroups, key)) {
                    group.open = savedGroups[key];
                }
                group.addEventListener('toggle', function () {
                    if (body.classList.contains('esh-collapsed') || !key) return;
                    savedGroups[key] = group.open;
                    localStorage.setItem('esh.nav.groups', JSON.stringify(savedGroups));
                });
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
