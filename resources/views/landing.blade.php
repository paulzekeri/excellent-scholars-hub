<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excellent Scholars Hub — Learning that fits your child</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#1d4ed8', dark: '#1e3a8a', light: '#dbeafe' },
                        accent: { DEFAULT: '#f97316', dark: '#ea580c', light: '#ffedd5' },
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html { scroll-behavior: smooth; }
        section[id] { scroll-margin-top: 5rem; }
        @media (min-width: 640px) { section[id] { scroll-margin-top: 6rem; } }
        #esh-mobile-menu summary::-webkit-details-marker { display: none; }
        #esh-mobile-menu[open] summary { background-color: #f9fafb; }
        .slide { display: none; }
        .slide.active { display: block; animation: fade 0.6s ease-in-out; }
        @keyframes fade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .dot.active { background-color: #f97316; }
    </style>
</head>
<body class="bg-white text-gray-800 antialiased">
<header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-gray-100 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center gap-2 sm:gap-3 sm:h-20">
            <!-- Brand: logo always visible, wordmark shrinks instead of pushing the actions off-screen -->
            <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-2 sm:gap-3">
                <img src="{{ asset('images/logo.jpg') }}" alt="Excellent Scholars Hub logo" class="h-9 w-auto shrink-0 sm:h-11">
                <span class="min-w-0 font-bold text-base leading-tight text-brand-dark sm:text-lg">
                    <span class="block truncate">Excellent</span>
                    <span class="block truncate text-accent">Scholars Hub</span>
                </span>
            </a>

            <!-- Desktop section links -->
            <nav class="hidden lg:flex ml-auto items-center gap-8 text-sm font-medium text-gray-600">
                <a href="#about" class="hover:text-brand">About us</a>
                <a href="#programmes" class="hover:text-brand">Programmes</a>
                <a href="#how" class="hover:text-brand">How it works</a>
                <a href="#book-assessment" class="hover:text-brand font-semibold text-brand">Book assessment</a>
                <a href="#testimonials" class="hover:text-brand">Testimonials</a>
                <a href="#contact" class="hover:text-brand">Contact</a>
            </nav>

            <div class="ml-auto flex shrink-0 items-center gap-2 lg:ml-8">
                <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center text-brand font-semibold px-3 py-2 sm:px-4 rounded-lg border border-brand-light hover:bg-brand-light transition">Log in</a>
                <a href="{{ route('register') }}" class="inline-flex items-center bg-accent hover:bg-accent-dark text-white font-semibold px-3 py-2 sm:px-5 sm:py-2.5 rounded-lg transition shadow">
                    <i class="fas fa-user-plus mr-2 hidden sm:inline"></i>Register
                </a>

                <!-- Small-screen menu: compact button, panel opens under the header -->
                <details id="esh-mobile-menu" class="relative lg:hidden">
                    <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-lg border border-gray-200 text-brand-dark transition hover:bg-gray-50" aria-label="Open section menu">
                        <i class="fas fa-bars"></i>
                    </summary>
                    <div class="absolute right-0 top-full mt-2 w-56 rounded-xl border border-gray-100 bg-white p-2 shadow-xl">
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
        <div class="rounded-xl border-l-4 p-4 {{ session('success') ? 'border-green-500 bg-green-50 text-green-800' : 'border-red-500 bg-red-50 text-red-800' }}" role="alert">
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

<section class="relative overflow-hidden bg-gradient-to-br from-brand-dark via-brand to-blue-400">
    <div class="absolute inset-0 opacity-20 pointer-events-none">
        <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-white"></div>
        <div class="absolute -bottom-24 right-0 h-96 w-96 rounded-full bg-accent"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
        @foreach($slides as $index => $slide)
            <div class="slide {{ $index === 0 ? 'active' : '' }}" data-slide="{{ $index }}">
                <div class="grid lg:grid-cols-2 gap-10 items-center">
                    <div class="text-white">
                        <span class="inline-flex items-center bg-white/15 text-white text-xs uppercase tracking-widest font-semibold px-3 py-1 rounded-full">
                            <span class="h-2 w-2 bg-accent rounded-full mr-2"></span>{{ $slide['eyebrow'] }}
                        </span>
                        <h1 class="mt-4 text-4xl sm:text-5xl font-extrabold leading-tight">{{ $slide['title'] }}</h1>
                        <p class="mt-4 text-lg text-blue-50 max-w-xl">{{ $slide['text'] }}</p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="#book-assessment" class="bg-white text-brand-dark hover:bg-brand-light font-semibold px-6 py-3 rounded-lg transition shadow-lg">
                                <i class="fas fa-video mr-2"></i>Book a free assessment
                            </a>
                            <a href="{{ route('register') }}" class="bg-accent hover:bg-accent-dark text-white font-semibold px-6 py-3 rounded-lg transition shadow-lg">
                                <i class="fas fa-arrow-right mr-2"></i>Register for classes
                            </a>
                            <a href="{{ route('login') }}" class="bg-white/10 border border-white/40 hover:bg-white/20 text-white font-semibold px-6 py-3 rounded-lg transition">
                                Existing account
                            </a>
                        </div>
                    </div>
                    <div class="hidden lg:flex justify-center">
                        <div class="bg-white/10 border border-white/30 rounded-3xl p-10">
                            <i class="fas {{ $slide['icon'] }} text-white text-8xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="flex items-center gap-2 mt-10">
            @foreach($slides as $index => $slide)
                <button type="button" class="dot h-3 w-3 rounded-full bg-white/50 transition" data-dot="{{ $index }}" aria-label="Go to slide {{ $index + 1 }}"></button>
            @endforeach
        </div>
    </div>
</section>

<!-- Quick stats -->
<section class="bg-brand-light">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
        <div>
            <p class="text-3xl font-extrabold text-brand-dark">{{ $stats['classes'] }}</p>
            <p class="text-sm text-gray-600 mt-1">Active classes</p>
        </div>
        <div>
            <p class="text-3xl font-extrabold text-brand-dark">{{ $stats['tutors'] }}</p>
            <p class="text-sm text-gray-600 mt-1">Tutors</p>
        </div>
        <div>
            <p class="text-3xl font-extrabold text-brand-dark">{{ $stats['learners'] }}</p>
            <p class="text-sm text-gray-600 mt-1">Learners</p>
        </div>
        <div>
            <p class="text-3xl font-extrabold text-accent">{{ $stats['parents'] }}</p>
            <p class="text-sm text-gray-600 mt-1">Parents on board</p>
        </div>
    </div>
</section>

<!-- Programmes -->
<section id="programmes" class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto">
            <p class="text-sm uppercase tracking-widest text-accent font-bold">Programmes</p>
            <h2 class="mt-2 text-3xl sm:text-4xl font-bold text-gray-900">Classes open for registration</h2>
            <p class="mt-3 text-gray-600">Small groups, subject specialists and a clear term-by-term plan for every learner.</p>
        </div>

        <div class="mt-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($programmes as $programme)
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-lg transition p-6">
                    <div class="flex items-center justify-between">
                        <span class="bg-brand-light text-brand-dark text-xs font-semibold px-3 py-1 rounded-full">{{ $programme->subject ?? 'General' }}</span>
                        <i class="fas fa-chalkboard text-accent text-xl"></i>
                    </div>
                    <h3 class="mt-4 text-xl font-bold text-gray-900">{{ $programme->name }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $programme->grade_level ?? 'All levels' }}</p>
                    @if($programme->description)
                        <p class="text-sm text-gray-600 mt-3">{{ \Illuminate\Support\Str::limit($programme->description, 110) }}</p>
                    @endif
                    <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
                        <span><i class="fas fa-user-tie mr-1"></i>{{ $programme->tutor->user->name ?? 'Tutor' }}</span>
                        <span><i class="fas fa-users mr-1"></i>{{ $programme->learners()->count() }} enrolled</span>
                    </div>
                    <a href="{{ route('register') }}" class="mt-5 inline-flex w-full items-center justify-center bg-brand hover:bg-brand-dark text-white font-semibold px-4 py-2.5 rounded-lg transition">
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
<section id="how" class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto">
            <p class="text-sm uppercase tracking-widest text-accent font-bold">How it works</p>
            <h2 class="mt-2 text-3xl sm:text-4xl font-bold text-gray-900">From registration to results in four steps</h2>
        </div>
        <div class="mt-10 grid grid-cols-1 md:grid-cols-4 gap-6">
            @foreach([
                ['icon' => 'fa-user-plus', 'title' => 'Register', 'text' => 'Create a learner, parent or tutor account in under a minute.'],
                ['icon' => 'fa-school', 'title' => 'Get placed', 'text' => 'The school links learners to the right class and their parent.'],
                ['icon' => 'fa-video', 'title' => 'Attend live classes', 'text' => 'Join Google Meet or Zoom sessions straight from the dashboard.'],
                ['icon' => 'fa-chart-line', 'title' => 'Track progress', 'text' => 'Grades, attendance and shared learning goals in one place.'],
            ] as $step)
                <div class="bg-white rounded-2xl shadow-sm p-6 text-center">
                    <div class="mx-auto h-12 w-12 rounded-full bg-accent-light text-accent flex items-center justify-center">
                        <i class="fas {{ $step['icon'] }}"></i>
                    </div>
                    <h3 class="mt-4 font-bold text-gray-900">{{ $step['title'] }}</h3>
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
            <p class="text-sm uppercase tracking-widest text-accent font-bold">Testimonials</p>
            <h2 class="mt-2 text-3xl sm:text-4xl font-bold text-gray-900">What our school community says</h2>
        </div>
        <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach([
                ['quote' => 'I can see my daughter\'s attendance and assignments without calling the school. The learning goals shared with me are a game changer.', 'name' => 'Mrs. A.', 'role' => 'Parent'],
                ['quote' => 'Scheduling a session, recording attendance and grading submissions now takes me minutes instead of an evening.', 'name' => 'Mr. O.', 'role' => 'Tutor'],
                ['quote' => 'I always know when my next class is and can join with one click from my dashboard.', 'name' => 'Tolu', 'role' => 'Learner'],
            ] as $testimonial)
                <figure class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="text-accent"><i class="fas fa-quote-left text-2xl"></i></div>
                    <blockquote class="mt-4 text-gray-700">{{ $testimonial['quote'] }}</blockquote>
                    <figcaption class="mt-4 text-sm text-gray-500">
                        <span class="font-semibold text-gray-800">{{ $testimonial['name'] }}</span> · {{ $testimonial['role'] }}
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>

<!-- Book an assessment (public, no account needed) -->
<section id="book-assessment" class="py-16 bg-brand-light">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-2 gap-10 items-start">
        <div>
            <p class="text-sm uppercase tracking-widest text-accent font-bold">Free assessment</p>
            <h2 class="mt-2 text-3xl sm:text-4xl font-bold text-gray-900">Book a video-call assessment</h2>
            <p class="mt-4 text-gray-700">
                Before we place a child in a class, a tutor meets them on a short video call to understand
                their level and what they need. Request a slot below — <strong>no account is needed</strong>
                and the child does not have to be registered with us yet.
            </p>
            <ul class="mt-6 space-y-3 text-sm text-gray-700">
                <li><i class="fas fa-check-circle text-green-600 mr-2"></i>The school emails or WhatsApps you the Google Meet / Zoom link.</li>
                <li><i class="fas fa-check-circle text-green-600 mr-2"></i>About 30 minutes with the tutor who will teach the class.</li>
                <li><i class="fas fa-check-circle text-green-600 mr-2"></i>Join from any phone, tablet or computer — no app to install.</li>
                <li><i class="fas fa-check-circle text-green-600 mr-2"></i>No payment and no obligation.</li>
            </ul>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8">
            <h3 class="text-lg font-semibold text-gray-800">Request an assessment</h3>
            <p class="text-sm text-gray-500 mt-1">We reply within one working day to agree the time.</p>

            @if($errors->any())
                <div class="mt-4 rounded-lg border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-800" role="alert">
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

                <button type="submit" class="w-full bg-accent hover:bg-accent-dark text-white font-semibold px-6 py-3 rounded-lg transition shadow">
                    <i class="fas fa-video mr-2"></i>Request assessment
                </button>
                <p class="text-xs text-gray-500">By requesting a slot you agree to be contacted about your child's assessment.</p>
            </form>
        </div>
    </div>
</section>

<!-- About -->
<section id="about" class="bg-brand-dark py-16 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-2 gap-10 items-center">
        <div>
            <p class="text-sm uppercase tracking-widest text-accent font-bold">About us</p>
            <h2 class="mt-2 text-3xl sm:text-4xl font-bold">Excellent Scholars Hub</h2>
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
                <li><i class="fas fa-check-circle text-accent mr-2"></i>Small-group live classes with qualified tutors</li>
                <li><i class="fas fa-check-circle text-accent mr-2"></i>Termly reports, grades and shared learning goals</li>
                <li><i class="fas fa-check-circle text-accent mr-2"></i>Transparent payment records and receipts</li>
            </ul>
        </div>
        <div class="bg-white/10 border border-white/20 rounded-3xl p-8">
            <h3 class="text-xl font-bold text-white">Visit or call us</h3>
            <ul class="mt-4 space-y-3 text-blue-50 text-sm">
                <li><i class="fas fa-location-dot text-accent mr-3"></i>8, Alice Ogunsona Crescent, Kuti Street, Hilltop Estate, Radio, Ikorodu</li>
                <li><i class="fas fa-phone text-accent mr-3"></i>+234 806 135 5032</li>
                <li><i class="fas fa-envelope text-accent mr-3"></i>admin@lit-grp.com</li>
                <li><i class="fas fa-clock text-accent mr-3"></i>Monday – Saturday, 8:00am – 6:00pm</li>
            </ul>
            <a href="{{ route('register') }}" class="mt-6 inline-flex items-center bg-accent hover:bg-accent-dark text-white font-semibold px-6 py-3 rounded-lg transition">
                <i class="fas fa-user-plus mr-2"></i>Register for classes
            </a>
        </div>
    </div>
</section>

<!-- Closing CTA -->
<section id="contact" class="py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-gray-900">Ready to join the next term?</h2>
        <p class="mt-3 text-gray-600">Create an account as a learner, parent or tutor — the school will place you in the right class.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ route('register') }}" class="bg-accent hover:bg-accent-dark text-white font-semibold px-6 py-3 rounded-lg transition shadow">Register now</a>
            <a href="{{ route('login') }}" class="bg-white border border-brand text-brand font-semibold px-6 py-3 rounded-lg hover:bg-brand-light transition">Log in</a>
        </div>
    </div>
</section>

<footer class="bg-gray-900 text-gray-400">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm">
        <p>&copy; {{ date('Y') }} Excellent Scholars Hub. All rights reserved.</p>
        <p>Built by LIT Dynamics LTD</p>
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
