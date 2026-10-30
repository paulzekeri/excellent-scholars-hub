<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excellent Scholars Hub — Learning that fits your child</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Inter:wght@400;500;600&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#1d4ed8', dark: '#0a2550', light: '#dbeafe' },
                        accent: { DEFAULT: '#F26B21', dark: '#D95A14', light: '#FFF1E6' },
                        sun: { DEFAULT: '#F7C94B', light: '#FFF7DB', ink: '#8A6100', edge: '#F4DFA0' },
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        script: ['Caveat', 'cursive'],
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        h1, h2, h3, h4 { font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif; letter-spacing: -0.02em; }
        section[id] { scroll-margin-top: 5rem; }
        @media (min-width: 640px) { section[id] { scroll-margin-top: 6rem; } }
        #esh-mobile-menu summary::-webkit-details-marker { display: none; }
        #esh-mobile-menu[open] summary { background-color: rgba(255, 255, 255, .14); }
        .slide { display: none; }
        .slide.active { display: block; animation: fade 0.6s ease-in-out; }
        @keyframes fade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .dot.active { background-color: #F26B21; }
    </style>
</head>
<body class="bg-[#FBFAF7] text-gray-800 antialiased">
<header class="sticky top-0 z-50 bg-[#0A2550]/95 backdrop-blur border-b border-white/10 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center gap-2 sm:gap-3 sm:h-20">
            <!-- Brand: logo always visible, wordmark shrinks instead of pushing the actions off-screen -->
            <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-2 sm:gap-3">
                <span class="shrink-0 rounded-md bg-white p-1 shadow-sm">
                    <img src="{{ asset('images/logo.jpg') }}" alt="Excellent Scholars Hub logo" class="h-8 w-auto sm:h-10">
                </span>
                <span class="min-w-0 font-bold text-base leading-tight text-white sm:text-lg">
                    <span class="block truncate">Excellent</span>
                    <span class="block truncate text-sun">Scholars Hub</span>
                </span>
            </a>

            <!-- Desktop section links -->
            <nav class="hidden lg:flex ml-auto items-center gap-8 text-sm font-medium text-blue-100">
                <a href="#about" class="transition hover:text-sun">About us</a>
                <a href="#programmes" class="transition hover:text-sun">Programmes</a>
                <a href="#how" class="transition hover:text-sun">How it works</a>
                <a href="#book-assessment" class="font-semibold text-sun transition hover:text-white">Book assessment</a>
                <a href="#testimonials" class="transition hover:text-sun">Testimonials</a>
                <a href="#contact" class="transition hover:text-sun">Contact</a>
            </nav>

            <div class="ml-auto flex shrink-0 items-center gap-2 lg:ml-8">
                <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center text-white font-semibold px-3 py-2 sm:px-4 rounded-lg border border-white/30 hover:bg-white/10 transition">Log in</a>
                <a href="{{ route('register') }}" class="inline-flex items-center bg-accent hover:bg-orange-400 text-blue-950 font-semibold px-3 py-2 sm:px-5 sm:py-2.5 rounded-lg transition shadow">
                    <i class="fas fa-user-plus mr-2 hidden sm:inline"></i>Register
                </a>

                <!-- Small-screen menu: compact button, panel opens under the header -->
                <details id="esh-mobile-menu" class="relative lg:hidden">
                    <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-lg border border-white/25 text-white transition hover:bg-white/10" aria-label="Open section menu">
                        <i class="fas fa-bars"></i>
                    </summary>
                    <div class="absolute right-0 top-full mt-2 w-56 rounded-xl border border-[#D7E0EC] bg-white p-2 shadow-xl">
                        <a href="#about" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-brand-light hover:text-brand">About us</a>
                        <a href="#programmes" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-brand-light hover:text-brand">Programmes</a>
                        <a href="#how" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-brand-light hover:text-brand">How it works</a>
                        <a href="#book-assessment" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-brand-light hover:text-brand">Book assessment</a>
                        <a href="#testimonials" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-brand-light hover:text-brand">Testimonials</a>
                        <a href="#contact" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-brand-light hover:text-brand">Contact</a>
                        <a href="{{ route('login') }}" class="mt-1 block rounded-lg border border-brand-light px-3 py-2 text-center text-sm font-semibold text-brand hover:bg-brand-light sm:hidden">Log in</a>
                    </div>
                </details>
            </div>
        </div>
    </div>
</header>

@if(session('success') || session('error'))
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
        <div class="rounded-xl border-l-4 p-4 {{ session('success') ? 'border-green-500 bg-green-50 text-green-800' : 'border-orange-500 bg-orange-50 text-orange-900' }}" role="alert">
            {{ session('success') ?: session('error') }}
        </div>
    </div>
@endif


@php
    $slides = [
        [
            'eyebrow' => 'Now enrolling',
            'title' => 'Learning that fits your child',
            'text' => 'Live classes, committed tutors and clear progress reports — all in one place for learners, parents and the school.',
            'icon' => 'fa-graduation-cap',
        ],
        [
            'eyebrow' => 'For parents',
            'title' => 'See the progress, not just the grades',
            'text' => 'Attendance, assignments, shared learning goals and direct messages with the school — no more guessing.',
            'icon' => 'fa-users',
        ],
        [
            'eyebrow' => 'For tutors',
            'title' => 'Teach, assess and report in minutes',
            'text' => 'Schedule sessions with Meet or Zoom links, record attendance, upload materials and grade submissions online.',
            'icon' => 'fa-chalkboard-teacher',
        ],
    ];
@endphp

<section class="relative overflow-hidden bg-[#FBFAF7]">
    <!-- Navy corner and wave, echoing the school flyer -->
    <svg aria-hidden="true" viewBox="0 0 600 600" fill="currentColor"
        class="pointer-events-none absolute -left-44 -top-52 h-[34rem] w-[34rem] text-[#0a2550]">
        <path d="M0 0h600C430 40 300 140 210 260 140 354 60 470 0 600V0Z"/>
    </svg>
    <div class="pointer-events-none absolute right-0 top-24 h-72 w-72 rounded-full bg-[#F7C94B]/25"></div>
    <svg aria-hidden="true" viewBox="0 0 1440 120" fill="currentColor"
        class="pointer-events-none absolute inset-x-0 bottom-0 h-14 w-full text-[#0a2550]">
        <path d="M0 52c240 54 480 66 720 32s480-54 720-18v54H0V52Z"/>
    </svg>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-28 lg:pt-14 lg:pb-32">
        <!-- Subject strip, like the flyer -->
        <div class="mb-10 flex flex-wrap items-center justify-center gap-x-3 gap-y-2 text-xs font-semibold uppercase tracking-wide text-[#0a2550] sm:text-sm lg:justify-start">
            @foreach(['Math', 'English', 'Sciences', 'SATs/11+', 'Coding & AI', 'Chess', 'Creative Writing'] as $subject)
                <span>{{ $subject }}</span>
                @if(! $loop->last)
                    <span class="text-accent">|</span>
                @endif
            @endforeach
        </div>

        @foreach($slides as $index => $slide)
            <div class="slide {{ $index === 0 ? 'active' : '' }}" data-slide="{{ $index }}">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-[#D7E0EC] bg-[#EAF2FB] px-3 py-1 text-xs font-semibold uppercase tracking-widest text-[#0a2550] shadow-sm">
                            <span class="h-2 w-2 rounded-full bg-accent"></span>{{ $slide['eyebrow'] }}
                        </span>
                        <h1 class="relative mt-5 font-display text-4xl font-extrabold leading-[1.05] tracking-tight text-[#0a2550] sm:text-5xl lg:text-6xl">
                            {{ $slide['title'] }}
                            <span aria-hidden="true" class="absolute -bottom-3 left-1 h-3 w-40 -rotate-1 rounded-full bg-gradient-to-r from-accent via-accent to-sun opacity-90"></span>
                        </h1>
                        <p class="mt-8 max-w-xl text-lg text-gray-600">{{ $slide['text'] }}</p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="#book-assessment" class="inline-flex items-center rounded-xl bg-[#0a2550] px-6 py-3 font-semibold text-white shadow-lg transition hover:bg-[#0d3070]">
                                <i class="fas fa-video mr-2"></i>Book a free assessment
                            </a>
                            <a href="{{ route('register') }}" class="inline-flex items-center rounded-xl bg-accent px-6 py-3 font-semibold text-blue-950 shadow-lg transition hover:bg-orange-400">
                                <i class="fas fa-arrow-right mr-2"></i>Register for classes
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center rounded-xl border border-[#0a2550]/25 px-6 py-3 font-semibold text-[#0a2550] transition hover:bg-[#0a2550] hover:text-white">
                                Existing account
                            </a>
                        </div>
                    </div>
                    <div class="relative mx-auto w-full max-w-md lg:max-w-lg">
                        <div class="relative rounded-[2rem] border border-white/10 bg-gradient-to-br from-[#0A2550] via-[#0C2B5D] to-[#123A73] p-6 shadow-2xl">
                            <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-[2rem]">
                                <div class="absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-accent/20 blur-2xl"></div>
                            </div>

                            <div class="absolute -left-3 -top-5 z-10 rotate-[-6deg] rounded-sm bg-sun px-4 py-3 shadow-lg sm:-left-6">
                                <p class="font-script text-2xl leading-none text-[#0a2550]">Bigger goals<br>brighter future</p>
                            </div>

                            <svg aria-hidden="true" viewBox="0 0 520 420" class="relative h-auto w-full" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="300" cy="190" r="150" fill="#164078"/>
                                <path d="M150 120c10-10 20-10 30 0M430 90c8-8 16-8 24 0M420 210c8-8 16-8 24 0"
                                    stroke="#F7C94B" stroke-width="6" stroke-linecap="round"/>
                                <polygon points="250,86 330,64 410,86 330,108" fill="#F7C94B"/>
                                <path d="M330 108v26" stroke="#F7C94B" stroke-width="5"/>
                                <circle cx="330" cy="140" r="7" fill="#F26B21"/>
                                <g transform="rotate(-2 200 315)">
                                    <rect x="62" y="292" width="286" height="34" rx="8" fill="#F7C94B"/>
                                    <text x="96" y="315" font-family="Poppins, sans-serif" font-size="17" font-weight="700" fill="#0A2550">EXCEL</text>
                                </g>
                                <g transform="rotate(1.5 200 278)">
                                    <rect x="56" y="258" width="286" height="34" rx="8" fill="#F26B21"/>
                                    <text x="96" y="281" font-family="Poppins, sans-serif" font-size="17" font-weight="700" fill="#0A2550">ACHIEVE</text>
                                </g>
                                <g transform="rotate(-1 200 244)">
                                    <rect x="66" y="224" width="286" height="34" rx="8" fill="#93C5FD"/>
                                    <text x="96" y="247" font-family="Poppins, sans-serif" font-size="17" font-weight="700" fill="#0A2550">GROW</text>
                                </g>
                                <g transform="rotate(2 200 210)">
                                    <rect x="58" y="190" width="286" height="34" rx="8" fill="#FFFFFF"/>
                                    <text x="96" y="213" font-family="Poppins, sans-serif" font-size="17" font-weight="700" fill="#0A2550">LEARN</text>
                                </g>
                                <rect x="320" y="250" width="150" height="96" rx="10" fill="#E7EEF8"/>
                                <rect x="332" y="262" width="126" height="72" rx="6" fill="#1D4ED8" opacity=".55"/>
                                <path d="M300 352h190l14 16H286l14-16Z" fill="#94A3B8"/>
                                <rect x="358" y="358" width="74" height="4" rx="2" fill="#64748B"/>
                                <path d="M440 140l6 14 14 6-14 6-6 14-6-14-14-6 14-6 6-14Z" fill="#F7C94B"/>
                                <path d="M180 150l4 10 10 4-10 4-4 10-4-10-10-4 10-4 4-10Z" fill="#F26B21"/>
                            </svg>
                        </div>

                        <span class="absolute -right-2 bottom-6 flex h-14 w-14 items-center justify-center rounded-full bg-accent text-blue-950 shadow-lg">
                            <i class="fas {{ $slide['icon'] }} text-xl"></i>
                        </span>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="mt-12 flex items-center gap-2">
            @foreach($slides as $index => $slide)
                <button type="button" class="dot h-3 w-3 rounded-full bg-[#0a2550]/20 transition" data-dot="{{ $index }}" aria-label="Go to slide {{ $index + 1 }}"></button>
            @endforeach
        </div>
    </div>
</section>

<!-- Quick stats -->
<section class="bg-[#EAF2FB]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
        <div>
            <p class="font-display text-3xl font-extrabold text-[#0a2550]">{{ $stats['classes'] }}</p>
            <p class="text-sm text-gray-600 mt-1">Active classes</p>
        </div>
        <div>
            <p class="font-display text-3xl font-extrabold text-[#0a2550]">{{ $stats['tutors'] }}</p>
            <p class="text-sm text-gray-600 mt-1">Tutors</p>
        </div>
        <div>
            <p class="font-display text-3xl font-extrabold text-[#0a2550]">{{ $stats['learners'] }}</p>
            <p class="text-sm text-gray-600 mt-1">Learners</p>
        </div>
        <div>
            <p class="font-display text-3xl font-extrabold text-orange-700">{{ $stats['parents'] }}</p>
            <p class="text-sm text-gray-600 mt-1">Parents on board</p>
        </div>
    </div>
</section>

<!-- Programmes -->
<section id="programmes" class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto">
            <p class="text-xs uppercase tracking-[0.2em] text-accent font-bold">Programmes</p>
            <h2 class="mt-3 font-display text-3xl font-extrabold text-[#0a2550] sm:text-4xl">Classes open for registration</h2>
            <p class="mt-3 text-gray-600">Small groups, subject specialists and a clear term-by-term plan for every learner.</p>
        </div>

        <div class="mt-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($programmes as $programme)
                <div class="bg-[#EAF2FB] border border-blue-100 rounded-2xl shadow-sm hover:shadow-lg transition p-6">
                    <div class="flex items-center justify-between">
                        <span class="bg-white text-[#0a2550] text-xs font-semibold px-3 py-1 rounded-full shadow-sm">{{ $programme->subject ?? 'General' }}</span>
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-orange-700 shadow-sm">
                            <i class="fas fa-chalkboard"></i>
                        </span>
                    </div>
                    <h3 class="mt-4 font-display text-xl font-bold text-[#0a2550]">{{ $programme->name }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $programme->grade_level ?? 'All levels' }}</p>
                    @if($programme->description)
                        <p class="text-sm text-gray-600 mt-3">{{ \Illuminate\Support\Str::limit($programme->description, 110) }}</p>
                    @endif
                    <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
                        <span><i class="fas fa-user-tie mr-1"></i>{{ $programme->tutor->user->name ?? 'Tutor' }}</span>
                        <span><i class="fas fa-users mr-1"></i>{{ $programme->learners()->count() }} enrolled</span>
                    </div>
                    <a href="{{ route('register') }}" class="mt-5 inline-flex w-full items-center justify-center bg-accent hover:bg-orange-400 text-blue-950 font-semibold px-4 py-2.5 rounded-lg transition">
                        Register for this class
                    </a>
                </div>
            @empty
                <div class="col-span-full text-center py-10 text-gray-500">
                    <i class="fas fa-chalkboard text-4xl text-gray-300 mb-3"></i>
                    <p>Classes will be published here shortly — create an account to be notified.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- How it works -->
<section id="how" class="bg-[#FBFAF7] py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto">
            <p class="text-xs uppercase tracking-[0.2em] text-accent font-bold">How it works</p>
            <h2 class="mt-3 font-display text-3xl font-extrabold text-[#0a2550] sm:text-4xl">From registration to results in four steps</h2>
        </div>
        <div class="mt-10 grid grid-cols-1 md:grid-cols-4 gap-6">
            @foreach([
                ['icon' => 'fa-user-plus', 'title' => 'Register', 'text' => 'Create a learner, parent or tutor account in under a minute.'],
                ['icon' => 'fa-school', 'title' => 'Get placed', 'text' => 'The school links learners to the right class and their parent.'],
                ['icon' => 'fa-video', 'title' => 'Attend live classes', 'text' => 'Join Google Meet or Zoom sessions straight from the dashboard.'],
                ['icon' => 'fa-chart-line', 'title' => 'Track progress', 'text' => 'Grades, attendance and shared learning goals in one place.'],
            ] as $step)
                <div class="bg-white border border-blue-50 rounded-2xl shadow-sm p-6 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#EAF2FB] text-accent">
                        <i class="fas {{ $step['icon'] }}"></i>
                    </div>
                    <h3 class="mt-4 font-display font-bold text-[#0a2550]">{{ $step['title'] }}</h3>
                    <p class="text-sm text-gray-600 mt-2">{{ $step['text'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Testimonials -->
<section id="testimonials" class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto">
            <p class="text-xs uppercase tracking-[0.2em] text-accent font-bold">Testimonials</p>
            <h2 class="mt-3 font-display text-3xl font-extrabold text-[#0a2550] sm:text-4xl">What our school community says</h2>
        </div>
        @php
            $items = $testimonials->map(function ($testimonial) {
                return [
                    'quote' => $testimonial->body,
                    'name' => optional($testimonial->user)->name ?? 'Member',
                    'role' => $testimonial->roleLabel(),
                    'photo' => $testimonial->photoUrl(),
                ];
            })->all();

            if (empty($items)) {
                $items = [
                    ['quote' => 'I can see my daughter\'s attendance and assignments without calling the school. The learning goals shared with me are a game changer.', 'name' => 'Mrs. A.', 'role' => 'Parent', 'photo' => null],
                    ['quote' => 'Scheduling a session, recording attendance and grading submissions now takes me minutes instead of an evening.', 'name' => 'Mr. O.', 'role' => 'Tutor', 'photo' => null],
                    ['quote' => 'I always know when my next class is and can join with one click from my dashboard.', 'name' => 'Tolu', 'role' => 'Learner', 'photo' => null],
                ];
            }
        @endphp
        <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($items as $testimonial)
                <figure class="bg-white border border-[#E3E9F1] rounded-2xl p-6 shadow-sm">
                    <div class="text-accent"><i class="fas fa-quote-left text-2xl"></i></div>
                    <blockquote class="mt-4 text-gray-700">{{ $testimonial['quote'] }}</blockquote>
                    <figcaption class="mt-5 flex items-center gap-3 text-sm text-gray-500">
                        @if($testimonial['photo'])
                            <img src="{{ $testimonial['photo'] }}" alt="{{ $testimonial['name'] }}" class="h-10 w-10 rounded-full object-cover ring-2 ring-white">
                        @else
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#EAF2FB] font-display text-sm font-bold text-[#0a2550]">{{ strtoupper(substr($testimonial['name'], 0, 1)) }}</span>
                        @endif
                        <span>
                            <span class="font-semibold text-[#0a2550]">{{ $testimonial['name'] }}</span> · {{ $testimonial['role'] }}
                        </span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>

<!-- Book an assessment (public, no account needed) -->
<section id="book-assessment" class="py-16 bg-[#EAF2FB]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-2 gap-10 items-start">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-accent font-bold">Free assessment</p>
            <h2 class="mt-3 font-display text-3xl font-extrabold text-[#0a2550] sm:text-4xl">Book a video-call assessment</h2>
            <p class="mt-4 text-gray-700">
                Before we place a child in a class, a tutor meets them on a short video call to understand
                their level and what they need. Request a slot below — <strong>no account is needed</strong>
                and the child does not have to be registered with us yet.
            </p>
            <ul class="mt-6 space-y-3 text-sm text-gray-700">
                <li><i class="fas fa-check-circle text-green-700 mr-2"></i>The school emails or WhatsApps you the Google Meet / Zoom link.</li>
                <li><i class="fas fa-check-circle text-green-700 mr-2"></i>About 30 minutes with the tutor who will teach the class.</li>
                <li><i class="fas fa-check-circle text-green-700 mr-2"></i>Join from any phone, tablet or computer — no app to install.</li>
                <li><i class="fas fa-check-circle text-green-700 mr-2"></i>No payment and no obligation.</li>
            </ul>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 border-t-4 border-accent">
            <h3 class="font-display text-lg font-bold text-[#0a2550]">Request an assessment</h3>
            <p class="text-sm text-gray-500 mt-1">We reply within one working day to agree the time.</p>

            @if($errors->any())
                <div class="mt-4 rounded-lg border-l-4 border-orange-500 bg-orange-50 p-3 text-sm text-orange-900" role="alert">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('assessments.request') }}" class="mt-5 space-y-4">
                @csrf
                <div class="hidden" aria-hidden="true">
                    <label for="website">Website</label>
                    <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div>
                    <label for="child_name" class="block text-sm font-medium text-gray-700">Child's name</label>
                    <input id="child_name" type="text" name="child_name" value="{{ old('child_name') }}" required
                        class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-accent focus:border-accent">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="guardian_name" class="block text-sm font-medium text-gray-700">Parent / guardian name</label>
                        <input id="guardian_name" type="text" name="guardian_name" value="{{ old('guardian_name') }}" required
                            class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-accent focus:border-accent">
                    </div>
                    <div>
                        <label for="guardian_phone" class="block text-sm font-medium text-gray-700">Phone / WhatsApp</label>
                        <input id="guardian_phone" type="text" name="guardian_phone" value="{{ old('guardian_phone') }}" required
                            class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-accent focus:border-accent">
                    </div>
                </div>

                <div>
                    <label for="guardian_email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input id="guardian_email" type="email" name="guardian_email" value="{{ old('guardian_email') }}" required
                        class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-accent focus:border-accent">
                    <p class="text-xs text-gray-500 mt-1">The call link is sent here (and by WhatsApp if you prefer).</p>
                </div>

                <div>
                    <label for="preferred_at" class="block text-sm font-medium text-gray-700">Preferred day and time (optional)</label>
                    <input id="preferred_at" type="datetime-local" name="preferred_at" value="{{ old('preferred_at') }}"
                        class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-accent focus:border-accent">
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Anything we should know? (optional)</label>
                    <textarea id="notes" name="notes" rows="3"
                        class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-accent focus:border-accent"
                        placeholder="e.g. Year 5, needs help with maths">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="w-full bg-accent hover:bg-orange-400 text-blue-950 font-semibold px-6 py-3 rounded-lg transition shadow">
                    <i class="fas fa-video mr-2"></i>Request assessment
                </button>
                <p class="text-xs text-gray-500">By requesting a slot you agree to be contacted about your child's assessment.</p>
            </form>
        </div>
    </div>
</section>

<!-- About -->
<section id="about" class="relative overflow-hidden bg-brand-dark py-16 text-white">
    <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-accent/10"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-2 gap-10 items-center">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-sun font-bold">About us</p>
            <h2 class="mt-3 font-display text-3xl font-extrabold sm:text-4xl">Excellent Scholars Hub</h2>
            <p class="mt-4 text-blue-100">
                We are a learning centre built on one belief: every child can excel when teaching, feedback and
                parental support work together. Our tutors run live small-group classes, record attendance and set
                clear learning goals each term.
            </p>
            <p class="mt-3 text-blue-100">
                This platform keeps the whole circle connected — learners join class with one click, parents follow
                progress in real time, and the school keeps every record in one place.
            </p>
            <ul class="mt-6 space-y-2 text-sm text-blue-50">
                <li><i class="fas fa-check-circle text-sun mr-2"></i>Small-group live classes with qualified tutors</li>
                <li><i class="fas fa-check-circle text-sun mr-2"></i>Termly reports, grades and shared learning goals</li>
                <li><i class="fas fa-check-circle text-sun mr-2"></i>Transparent payment records and receipts</li>
            </ul>
        </div>
        <div class="bg-white/10 border border-white/20 rounded-3xl p-8">
            <p class="font-script text-3xl text-sun">We are online</p>
            <h3 class="mt-1 font-display text-xl font-bold text-white">Talk to us</h3>
            <ul class="mt-4 space-y-3 text-blue-50 text-sm">
                <li><i class="fas fa-phone text-sun mr-3"></i><a href="tel:+2348119656244" class="transition hover:text-sun">+234 811 965 6244</a></li>
                <li><i class="fas fa-envelope text-sun mr-3"></i><a href="mailto:excellentscholars2@gmail.com" class="transition hover:text-sun">excellentscholars2@gmail.com</a></li>
                <li><i class="fas fa-video text-sun mr-3"></i>All classes and assessments run live online (Google Meet or Zoom)</li>
                <li><i class="fas fa-clock text-sun mr-3"></i>Monday – Saturday, 8:00am – 6:00pm</li>
            </ul>
            <a href="{{ route('register') }}" class="mt-6 inline-flex items-center bg-accent hover:bg-orange-400 text-blue-950 font-semibold px-6 py-3 rounded-lg transition">
                <i class="fas fa-user-plus mr-2"></i>Register for classes
            </a>
        </div>
    </div>
</section>

<!-- Closing CTA -->
<section id="contact" class="py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="font-display text-3xl font-extrabold text-[#0a2550] sm:text-4xl">Ready to join the next term?</h2>
        <p class="mt-3 text-gray-600">Create an account as a learner, parent or tutor — the school will place you in the right class.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ route('register') }}" class="bg-accent hover:bg-orange-400 text-blue-950 font-semibold px-6 py-3 rounded-lg transition shadow">Register now</a>
            <a href="{{ route('login') }}" class="bg-white border border-brand text-brand font-semibold px-6 py-3 rounded-lg hover:bg-brand-light transition">Log in</a>
        </div>
    </div>
</section>

<footer class="bg-[#0a2550] text-blue-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between text-sm">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/logo.jpg') }}" alt="Excellent Scholars Hub logo" class="h-10 w-auto rounded bg-white p-1">
            <span class="font-display font-semibold text-white">Excellent Scholars Hub</span>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-blue-200">Follow us</span>
            <a href="https://www.instagram.com/esh_tutoring" target="_blank" rel="noopener" aria-label="Excellent Scholars Hub on Instagram"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-accent hover:text-blue-950">
                <i class="fab fa-instagram"></i>
            </a>
            <a href="https://x.com/esh_tutoring" target="_blank" rel="noopener" aria-label="Excellent Scholars Hub on X"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-accent hover:text-blue-950">
                <i class="fab fa-x-twitter"></i>
            </a>
            <a href="https://www.tiktok.com/@esh_tutoring" target="_blank" rel="noopener" aria-label="Excellent Scholars Hub on TikTok"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-accent hover:text-blue-950">
                <i class="fab fa-tiktok"></i>
            </a>
            <a href="https://www.facebook.com/esh_tutoring" target="_blank" rel="noopener" aria-label="Excellent Scholars Hub on Facebook"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-accent hover:text-blue-950">
                <i class="fab fa-facebook-f"></i>
            </a>
            <span class="text-blue-200">@esh_tutoring</span>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-blue-200">
            <p>&copy; {{ date('Y') }} Excellent Scholars Hub. All rights reserved.</p>
            <p>Built by LIT Dynamics LTD</p>
        </div>
    </div>
</footer>

<script>
    (function () {
        var menu = document.getElementById('esh-mobile-menu');

        if (menu) {
            // Close the small-screen menu after following a section link.
            menu.addEventListener('click', function (event) {
                if (event.target.closest('a')) {
                    menu.removeAttribute('open');
                }
            });

            // Close it when tapping anywhere else on the page.
            document.addEventListener('click', function (event) {
                if (menu.open && !menu.contains(event.target)) {
                    menu.removeAttribute('open');
                }
            });
        }

        var slides = document.querySelectorAll('[data-slide]');
        var dots = document.querySelectorAll('[data-dot]');
        var current = 0;

        function show(index) {
            slides.forEach(function (slide, i) {
                slide.classList.toggle('active', i === index);
            });
            dots.forEach(function (dot, i) {
                dot.classList.toggle('active', i === index);
            });
            current = index;
        }

        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () { show(i); });
        });

        if (slides.length > 1) {
            setInterval(function () { show((current + 1) % slides.length); }, 6000);
        }

        show(0);
    })();
</script>
</body>
</html>
