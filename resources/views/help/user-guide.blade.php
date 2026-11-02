@extends('layouts.app')

@section('title', 'User Guide & Manual')

@section('content')
@php
    $guideUser = auth()->user();

    /*
    | The guide is role-aware: each account only sees the sections for screens it
    | can actually open. The CEO shares the admin area but also owns finance.
    */
    $guideIsCeo = $guideUser->isCeo();
    $guideIsAdmin = $guideUser->isAdministrator();   // administrator or CEO
    $guideIsTutor = $guideUser->isTutor();
    $guideIsParent = $guideUser->isParent();
    $guideIsLearner = $guideUser->isLearner();

    $guideRoleLabel = $guideIsCeo
        ? 'CEO'
        : ($guideUser->isAdmin()
            ? 'Administrator'
            : ($guideIsTutor ? 'Tutor' : ($guideIsParent ? 'Parent / Guardian' : 'Learner')));

    $guideRoleIcon = $guideIsAdmin
        ? 'fa-user-shield'
        : ($guideIsTutor ? 'fa-chalkboard-teacher' : ($guideIsParent ? 'fa-users' : 'fa-user-graduate'));
@endphp
<style>
    .guide-section {
        scroll-margin-top: 100px;
    }
    .guide-toc {
        position: sticky;
        top: 100px;
    }
    .guide-card {
        transition: all 0.3s ease;
    }
    .guide-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
    }
    .step-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #4f46e5;
        color: white;
        font-weight: bold;
        font-size: 14px;
        flex-shrink: 0;
    }
    .tip-box {
        background: #ecfdf5;
        border-left: 4px solid #10b981;
        padding: 16px;
        border-radius: 8px;
        margin: 16px 0;
    }
    .warning-box {
        background: #fffbeb;
        border-left: 4px solid #f59e0b;
        padding: 16px;
        border-radius: 8px;
        margin: 16px 0;
    }
    .info-box {
        background: #eff6ff;
        border-left: 4px solid #3b82f6;
        padding: 16px;
        border-radius: 8px;
        margin: 16px 0;
    }
</style>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">
            <i class="fas fa-book-open text-blue-600 mr-3"></i>User Guide & Manual
        </h1>
        <p class="text-lg text-gray-600 max-w-3xl mx-auto">
            Welcome to <strong>Excellent Scholars Hub (ESH)</strong> — your all-in-one Learning Management System.
            This guide walks you through the screens your role can use, from getting started to the day-to-day tasks.
        </p>
        <div class="mt-5 inline-flex flex-wrap items-center justify-center gap-2 rounded-full bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-800">
            <i class="fas {{ $guideRoleIcon }}"></i>
            <span>Showing the guide for your role: {{ $guideRoleLabel }}</span>
        </div>
        <p class="mt-2 text-xs text-gray-500">Sections for other roles are hidden — the menu only shows what your account can open.</p>
    </div>

    <div class="lg:grid lg:grid-cols-4 lg:gap-8">
        <!-- Table of Contents (Sidebar) -->
        <div class="lg:col-span-1">
            <div class="guide-toc bg-white rounded-xl shadow-md p-6 mb-8 lg:mb-0">
                <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-list mr-2 text-blue-600"></i>Quick Navigation</h3>
                <nav class="space-y-2 text-sm">
                    <a href="#getting-started" class="block text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-3 py-2 rounded transition">
                        <i class="fas fa-flag mr-2"></i>Getting Started
                    </a>
                    @if($guideIsAdmin)
                    <a href="#for-admins" class="block text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-3 py-2 rounded transition">
                        <i class="fas fa-user-shield mr-2"></i>For Administrators
                    </a>
                    @endif
                    @if($guideIsTutor)
                    <a href="#for-tutors" class="block text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-3 py-2 rounded transition">
                        <i class="fas fa-chalkboard-teacher mr-2"></i>For Tutors
                    </a>
                    @endif
                    @if($guideIsLearner)
                    <a href="#for-learners" class="block text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-3 py-2 rounded transition">
                        <i class="fas fa-user-graduate mr-2"></i>For Learners (Students)
                    </a>
                    @endif
                    @if($guideIsParent)
                    <a href="#for-parents" class="block text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-3 py-2 rounded transition">
                        <i class="fas fa-users mr-2"></i>For Parents/Guardians
                    </a>
                    @endif
                    <a href="#messages" class="block text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-3 py-2 rounded transition">
                        <i class="fas fa-comments mr-2"></i>Messages
                    </a>
                    <a href="#notifications" class="block text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-3 py-2 rounded transition">
                        <i class="fas fa-bell mr-2"></i>Notifications
                    </a>
                    <a href="#whats-new" class="block text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-3 py-2 rounded transition">
                        <i class="fas fa-star mr-2 text-orange-700"></i>What's New
                    </a>
                    @if($guideIsParent || $guideIsCeo)
                    <a href="#payments" class="block text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-3 py-2 rounded transition">
                        <i class="fas fa-file-invoice-dollar mr-2"></i>{{ $guideIsParent ? 'Fees & Payments' : 'Verifying Fees' }}
                    </a>
                    @endif
                    @if($guideIsAdmin || $guideIsTutor || $guideIsParent)
                    <a href="#learning-goals" class="block text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-3 py-2 rounded transition">
                        <i class="fas fa-bullseye mr-2"></i>Learning Goals
                    </a>
                    @endif
                    @if($guideIsAdmin)
                    <a href="#broadcasts" class="block text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-3 py-2 rounded transition">
                        <i class="fas fa-bullhorn mr-2"></i>Broadcasts
                    </a>
                    @endif
                    <a href="#faq" class="block text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-3 py-2 rounded transition">
                        <i class="fas fa-question-circle mr-2"></i>FAQ & Troubleshooting
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-3 space-y-10">

            <!-- ========== GETTING STARTED ========== -->
            <section id="getting-started" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-flag text-blue-600 mr-2"></i>Getting Started
                </h2>

                <h3 class="text-lg font-semibold text-gray-700 mb-3">What is Excellent Scholars Hub?</h3>
                <p class="text-gray-600 mb-6">
                    Excellent Scholars Hub (ESH) is a web-based Learning Management System (LMS) designed to connect
                    <strong>Administrators</strong>, the <strong>CEO</strong>, <strong>Tutors</strong>,
                    <strong>Learners (Students)</strong>, and <strong>Parents/Guardians</strong> in one seamless platform.
                    It enables class management, assignment submissions, attendance tracking, learning material sharing,
                    payment recording (parents record, the CEO verifies), messaging, and progress monitoring.
                </p>

                <h3 class="text-lg font-semibold text-gray-700 mb-3">How to Create an Account</h3>
                <ol class="space-y-3 mb-6">
                    <li class="flex items-start gap-3">
                        <span class="step-number mt-0.5">1</span>
                        <div>
                            <p class="font-medium text-gray-800">Go to the Registration Page</p>
                            <p class="text-sm text-gray-600">Click the <strong>"Register here"</strong> link on the login page, or navigate to <code class="bg-gray-100 px-2 py-0.5 rounded text-sm">{{ route('register') }}</code></p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="step-number mt-0.5">2</span>
                        <div>
                            <p class="font-medium text-gray-800">Choose "I am a" — Learner or Tutor</p>
                            <p class="text-sm text-gray-600">The <strong>"I am a"</strong> choice is the <strong>first field</strong> on the form and it decides how the rest of the form is worded. Pick <strong>Learner</strong> if you are enrolling a child, or <strong>Tutor</strong> if you want to teach. Parents and staff do not register here.</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="step-number mt-0.5">3</span>
                        <div>
                            <p class="font-medium text-gray-800">Fill in Your Details</p>
                            <p class="text-sm text-gray-600">As a learner the fields are labelled <strong>Learner Full Name</strong>, <strong>Learner Username</strong>, <strong>Learner Email</strong> (optional for a child with no email) and <strong>Learner Phone</strong> (optional) so they are never confused with the parent/guardian details below. A tutor simply sees Full Name, Username, Email (required) and Phone.</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="step-number mt-0.5">4</span>
                        <div>
                            <p class="font-medium text-gray-800">Parent/Guardian Details (for Learners only)</p>
                            <p class="text-sm text-gray-600">If you register as a <strong>Learner</strong>, the parent/guardian section appears and you must provide the <strong>Parent/Guardian Full Name</strong> and <strong>Parent/Guardian Email</strong>. The guardian email is <strong>required</strong> — it is where login details and password resets go when the child has no mailbox. A parent account is created automatically when that email is new and the login details are emailed to it.</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="step-number mt-0.5">5</span>
                        <div>
                            <p class="font-medium text-gray-800">Create Password & Submit</p>
                            <p class="text-sm text-gray-600">Create a password (minimum 8 characters), confirm it, and click <strong>"Create Account"</strong> (this reads <strong>"Create Learner Account"</strong> when you chose Learner).</p>
                        </div>
                    </li>
                </ol>

                <div class="tip-box">
                    <p class="text-sm text-gray-700"><strong>💡 Tip:</strong> If you are an <strong>Administrator</strong> or the <strong>CEO</strong>, your account will be created by the system owner directly — you won't register via the public form.</p>
                </div>

                <h3 class="text-lg font-semibold text-gray-700 mb-3">How to Log In</h3>
                <ol class="space-y-3 mb-6">
                    <li class="flex items-start gap-3">
                        <span class="step-number mt-0.5">1</span>
                        <div>
                            <p class="font-medium text-gray-800">Go to the Login Page</p>
                            <p class="text-sm text-gray-600">Navigate to <code class="bg-gray-100 px-2 py-0.5 rounded text-sm">{{ route('login') }}</code></p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="step-number mt-0.5">2</span>
                        <div>
                            <p class="font-medium text-gray-800">Enter Credentials</p>
                            <p class="text-sm text-gray-600">Enter your <strong>Email or username</strong> and your <strong>Password</strong>.</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="step-number mt-0.5">3</span>
                        <div>
                            <p class="font-medium text-gray-800">Click "Sign in"</p>
                            <p class="text-sm text-gray-600">You'll be automatically redirected to your role-specific dashboard.</p>
                        </div>
                    </li>
                </ol>

                <div class="tip-box">
                    <p class="text-sm text-gray-700"><strong>💡 Tip:</strong> Check the <strong>"Remember me"</strong> box to stay logged in on your personal device.</p>
                </div>

                <h3 class="text-lg font-semibold text-gray-700 mb-3">Navigating the Platform</h3>
                <p class="text-gray-600 mb-4">
                    After logging in, a <strong>blue navigation menu sits on the left</strong> and the top bar carries
                    your quick actions (Messages <i class="fas fa-comments"></i>, Notifications <i class="fas fa-bell"></i>,
                    Help <i class="fas fa-question-circle"></i> and <strong>Logout</strong>). The left menu is split into
                    labelled groups so it stays short and tidy as new screens are added:
                </p>
                <ul class="list-disc list-inside text-sm text-gray-600 mb-4 space-y-1">
                    <li><strong>Overview</strong> — your dashboard</li>
                    @if($guideIsAdmin)
                        <li><strong>Academics</strong> — classes, schedules, attendance, assignment review and materials</li>
                    @elseif($guideIsTutor)
                        <li><strong>Teaching</strong> — your schedules, assignments, materials and learning goals</li>
                    @elseif($guideIsLearner)
                        <li><strong>My Learning</strong> — your classes, assignments and materials</li>
                    @endif
                    @if($guideIsAdmin)
                        <li><strong>People</strong> — user and role management (administrators only)</li>
                    @endif
                    @if($guideIsParent)
                        <li><strong>Finance</strong> — <em>Fees &amp; Payments</em>: record what you paid and track the balance</li>
                    @elseif($guideIsCeo)
                        <li><strong>Finance</strong> — <em>Payments</em> (verify what parents recorded) and <em>Bank accounts</em></li>
                    @endif
                    <li><strong>Communication</strong> — messages, notifications{{ $guideIsAdmin ? ' and broadcasts' : '' }}</li>
                    <li><strong>Support</strong> — this user guide</li>
                </ul>
                <div class="tip-box">
                    <p class="text-sm text-gray-700"><strong>💡 Tip:</strong> Click a group heading (e.g. <em>Academics</em>) to fold it away — the menu remembers
                        your choice next time. On a computer you can also tap the <i class="fas fa-bars"></i> button in the top bar to shrink the menu
                        to an icon rail and give the page more room. On a phone the menu hides behind the same <i class="fas fa-bars"></i> button and
                        slides out from the left.</p>
                </div>

                <h3 class="text-lg font-semibold text-gray-700 mb-3 mt-6">My profile &amp; testimonials</h3>
                <p class="text-gray-600 mb-3">The <strong>person icon</strong> in the top bar opens <strong>My profile</strong> for every role.</p>
                <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                    <li>Update your <strong>name</strong>, <strong>email</strong> and <strong>profile photo</strong> (JPG/PNG/WebP, up to 4 MB; replace or remove it at any time).</li>
                    <li>Your <strong>username</strong> is shown read-only — an administrator can change it.</li>
                    <li>Write a <strong>testimonial</strong> (up to 1,000 characters) and tick <em>“Publish my profile photo with this testimonial”</em> if you want your photo beside it. That needs a photo on your profile first.</li>
                    <li>The CEO reviews every testimonial. Its status (<em>Waiting</em>, <em>Approved</em>, <em>Rejected</em>) and any CEO note appear on <strong>My profile</strong>. Editing an approved testimonial sends it back for approval.</li>
                </ul>
            </section>

            <!-- ========== FOR ADMINISTRATORS ========== -->
            @if($guideIsAdmin)
            <section id="for-admins" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-user-shield text-orange-700 mr-2"></i>For Administrators{{ $guideIsCeo ? ' & CEO' : '' }}
                </h2>
                @if($guideIsCeo)
                    <p class="text-gray-600 mb-6">
                        As the <strong>CEO</strong> you have full control over the platform: you manage users, classes,
                        schedules, oversight and notifications, and you alone can verify <strong>Payments</strong> and
                        maintain the school <strong>Bank accounts</strong>. Your dashboard gives you an overview of key
                        platform statistics.
                    </p>
                @else
                    <p class="text-gray-600 mb-6">
                        As an <strong>Administrator</strong> you have full control over the platform: you manage users,
                        classes, schedules, oversight and notifications. You also keep the school's
                        <strong>Bank accounts</strong> up to date, and you review member <strong>Testimonials</strong>.
                        Verifying payments stays with the CEO, so <strong>Payments</strong> is not part of your menu.
                        Your dashboard gives you an overview of key platform statistics.
                    </p>
                @endif
                <div class="info-box mb-6">
                    <p class="text-sm"><strong><i class="fas fa-map-signs mr-1"></i> Admin Navigation Tip:</strong></p>
                    <p class="text-sm mt-1">The admin menu lives in the <strong>left sidebar</strong>, grouped by area:</p>
                    <ul class="list-disc list-inside text-sm mt-2 space-y-1">
                        <li><strong>Academics</strong> — <em>Classes</em>, <em>Schedules</em>, <em>Attendance</em>, <em>Assignment Review</em> (including submission review &amp; grading) and <em>Materials</em></li>
                        @if($guideIsCeo)
                        <li><strong>Finance</strong> — <em>Payments</em> (verify what parents record) and <em>Bank accounts</em></li>
                        @endif
                        <li><strong>Communication</strong> — <em>Messages</em>, <em>Broadcasts</em> and <em>Notifications</em></li>
                        <li><strong>People</strong> — <em>Users &amp; Roles</em>, and <strong>Learning</strong> — <em>Learning Goals</em></li>
                    </ul>
                    <p class="text-sm mt-2">Standalone links: <strong>Dashboard</strong> and <strong>Help &amp; User Guide</strong>. The current page is highlighted in orange.</p>
                </div>

                <!-- Admin Dashboard -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-tachometer-alt text-blue-600 mr-2"></i>1. Admin Dashboard
                    </h3>
                    <p class="text-gray-600 mb-2">Your dashboard displays:</p>
                    <ul class="list-disc list-inside text-gray-600 space-y-1 mb-3">
                        <li>Total Users, Tutors, Learners, Classes, and Schedules</li>
                        @if($guideIsCeo)
                        <li>A <strong>Payments</strong> stat card — for what parents have paid and what is waiting</li>
                        @endif
                        <li>An <strong>Unread Messages</strong> card that links straight to your inbox</li>
                        <li>Recent Users (newest registrations)</li>
                        <li>Upcoming Classes (next scheduled sessions)</li>
                    </ul>
                    <p class="text-sm text-gray-500">Navigate to: <strong>Dashboard</strong> in the left menu</p>
                </div>

                <!-- Manage Users -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-users text-blue-600 mr-2"></i>2. Managing Users
                    </h3>
                    <p class="text-gray-600 mb-3">You can create, edit, and delete users on the platform.</p>
                    
                    <p class="font-medium text-gray-700 mb-2">To create a new user:</p>
                    <ol class="space-y-2 mb-3">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Click <strong>"Users"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">Click the <strong>"New User"</strong> button.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Fill in the user's name, a unique <strong>username</strong> (required for parents and learners — it is how they sign in), <strong>email</strong> (optional for a child with no mailbox), and a <strong>temporary password</strong>. The login details are emailed, and the owner picks their own password at first sign-in. For a <strong>Learner</strong>, a <strong>Parent / Guardian</strong> is required. To add a child to an existing family, open the parent's edit page and use the <strong>Add child</strong> form.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">4.</span>
                            <span class="text-gray-600">Click <strong>"Create User"</strong> to save.</span>
                        </li>
                    </ol>

                    <div class="tip-box">
                        <p class="text-sm text-gray-700"><strong>💡 Linking learners to parents:</strong> one parent can be linked to <strong>many learners</strong>, and each learner has one parent. Create the parent account first (role <strong>Parent</strong>), then link learners to that parent when you create or edit them. The <strong>Users</strong> list shows the linked parent next to each learner (or <em>Not linked</em>), and opening a parent's <strong>Edit</strong> page lists every learner linked to them.</p>
                    </div>

                    <p class="font-medium text-gray-700 mb-2">To edit or delete a user:</p>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        <li>Click the <strong>Edit</strong> (pencil) icon next to a user to modify their details.</li>
                        <li>Click the <strong>Delete</strong> (trash) icon to remove a user (requires confirmation).</li>
                    </ul>
                </div>

                <!-- Admissions -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-user-plus text-blue-600 mr-2"></i>Enquiries &amp; admissions
                    </h3>
                    <p class="text-gray-600 mb-3">
                        A prospective learner has no account yet. Capture the WhatsApp message or call under
                        <strong>People &rarr; Admissions &rarr; Capture enquiry</strong>: the child's name, the guardian's
                        name and phone, where they came from, and any notes.
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1 mb-3">
                        <li>The tabs show <strong>Open</strong>, <strong>All</strong>, <strong>New</strong>, <strong>Contacted</strong>, <strong>Declined</strong> and <strong>Archived</strong>; search by child, guardian, phone, email or reference, and filter by source.</li>
                        <li>Open an enquiry for the guardian's phone and email, a ready-to-send <strong>WhatsApp</strong> button, the status form and the <strong>timeline</strong> where every call, message and status change is logged.</li>
                        <li>Declining needs a reason; <strong>Archived</strong> is final. The assessment and enrolment steps arrive in the next release, so those moves are deliberately closed today.</li>
                        <li>Requests made through the <strong>“Book a video-call assessment”</strong> form on the public website arrive here as <strong>New</strong> (source <em>Website</em>) and every administrator/CEO is notified.</li>
                        <li>Once an enquiry is <strong>Contacted</strong>, use the <strong>Assessment calls</strong> card to book the call: pick the tutor, date/time, platform and paste the Google Meet/Zoom link. The guardian is emailed the join link (the WhatsApp button sends the same message), and you, the CEO and the tutor all join from it.</li>
                        <li>After the call, open the assessment and <strong>record the result</strong>. Completing it moves the enquiry to <strong>Assessed</strong>; choosing a recommended class moves it to <strong>Awaiting enrolment</strong>. A no-show sends it back to <strong>Contacted</strong> so you can rebook. Reports download from the same page.</li>
                        <li>When the child is <strong>Awaiting enrolment</strong>, click <strong>Convert to learner</strong>: confirm the child's username (their email is optional), the guardian's username (or reuse the existing parent matched by their email) and the class. Both accounts get temporary passwords, the child is enrolled, and the logins are emailed to the guardian — or shown to you when mail is not configured.</li>
                    </ul>
                    <p class="text-sm text-gray-600">The dashboard shows <strong>New Enquiries</strong> and <strong>Enquiries In Progress</strong> so nothing gets forgotten.</p>
                </div>

                <!-- Manage Classes -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-chalkboard text-sun-ink mr-2"></i>3. Managing Classes
                    </h3>
                    <p class="text-gray-600 mb-3">Create and manage the classes (subjects/courses) on the platform.</p>

                    <p class="font-medium text-gray-700 mb-2">To create a class:</p>
                    <ol class="space-y-2 mb-3">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"Classes"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">Click <strong>"New Class"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Fill in: <strong>Name</strong>, <strong>Class Code</strong> (optional), <strong>Subject</strong>, <strong>Grade Level</strong>, assign a <strong>Tutor</strong>, set <strong>Max Students</strong>, and add a <strong>Description</strong> (optional).</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">4.</span>
                            <span class="text-gray-600">Click <strong>"Create Class"</strong>.</span>
                        </li>
                    </ol>

                    <div class="tip-box">
                        <p class="text-sm text-gray-700"><strong>💡 Tip:</strong> <strong>Class Code is optional.</strong> Leave it blank and the school generates a short, unique code from the class name (for example <em>MATHS-7K2QP</em>) that learners can use for enrollment.</p>
                    </div>

                    <p class="font-medium text-gray-700 mb-2">Enrolling Learners in a Class:</p>
                    <ol class="space-y-2 mb-3">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Click the <strong>Users icon</strong> (👥) next to the class on the Classes list.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">On the class detail page, select a <strong>Learner</strong> from the dropdown and click <strong>"Enroll"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">To remove a learner, click <strong>"Unenroll"</strong> next to their name.</span>
                        </li>
                    </ol>
                </div>

                <!-- Manage Schedules -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-calendar text-orange-700 mr-2"></i>4. Managing Schedules
                    </h3>
                    <p class="text-gray-600 mb-3">Schedule class sessions with dates, times, and optional meeting links.</p>
                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"Schedules"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">Click <strong>"New Schedule"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Fill in: <strong>Title</strong>, select the <strong>Class</strong> and <strong>Tutor</strong>, then choose a <strong>Meeting Platform</strong> and, if you have one, a <strong>Meeting Link</strong> (Zoom/Google Meet). For a single lesson set the <strong>Start/End Time</strong>; for a repeating class use the weekly builder described below.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">4.</span>
                            <span class="text-gray-600">Click <strong>"Create Schedule"</strong>.</span>
                        </li>
                    </ol>

                    <div class="tip-box">
                        <p class="text-sm text-gray-700 mb-2"><strong>Repeat a weekly lesson:</strong> set <strong>Repeat</strong> to <em>Every week on the days &amp; times I choose</em>, then add one row per day &mdash; for example <strong>10:00 am Monday</strong>, <strong>2:00 pm Wednesday</strong> and <strong>6:00 pm Friday</strong>. Choose a <strong>Repeats from</strong> date and a <strong>Repeat until</strong> date: each row is created every week on its own day and time, so one form builds the whole timetable. Enrolled learners and the tutor see every session on their dashboards automatically.</p>
                        <p class="text-sm text-gray-700"><strong>Automatic video room:</strong> choose <strong>Automatic video room (Jitsi Meet)</strong> and leave the link blank &mdash; a free meeting link is generated for you and reused for every session in the series.</p>
                        <p class="text-sm text-gray-700"><strong>Find a certain day:</strong> the Schedules page has a filter bar &mdash; pick a <strong>Class</strong>, a <strong>Tutor</strong>, a <strong>Start Time (day)</strong> date and/or a <strong>Status</strong>, then click <strong>Filter</strong>. The <strong>Today</strong> link jumps straight to today's sessions, <strong>Any day</strong> clears only the date, and <strong>Reset</strong> clears everything. The count under the filter bar tells you how many sessions matched.</p>
                    </div>

                    <p class="font-medium text-gray-700 mt-4 mb-2">To tick a class as completed:</p>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        <li>After the scheduled end time, use the green <strong>tick</strong> on the Schedules list (or <strong>Mark completed</strong> on the session page).</li>
                        <li>The tick records who confirmed it and when, and the class starts counting on the monthly tutor report.</li>
                        <li>If it was ticked by mistake, use the yellow <strong>undo</strong> button (or <strong>Reopen</strong>) — it stops counting.</li>
                    </ul>
                </div>

                <!-- Verify Payments -->
                @if($guideIsCeo)
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-money-bill text-orange-700 mr-2"></i>5. Verifying Fees <span class="text-xs font-bold text-orange-700 align-middle">(CEO ONLY)</span>
                    </h3>
                    <div class="info-box mb-3">
                        <p class="text-sm text-gray-700">
                            <strong>🔒 Access:</strong> the school-side payment screens are restricted to the <strong>CEO</strong> account.
                            Regular administrators do not see the Payments link at all, and any attempt to open the payment pages
                            directly is refused with a <strong>"403 Forbidden"</strong> error. Parents do not record here — they use
                            their own <strong>Fees &amp; Payments</strong> screen.
                        </p>
                    </div>
                    <p class="text-gray-600 mb-3">
                        Parents record every payment (one parent paying for two or three children is quicker than the CEO entering
                        a hundred). Your job is to check the money reached the bank or the cash book.
                    </p>

                    <div class="bg-white border border-blue-200 rounded-lg p-4 mb-4">
                        <p class="text-sm font-semibold text-gray-800"><i class="fas fa-building-columns text-blue-600 mr-1"></i> School bank accounts — add, switch off, delete</p>
                        <p class="text-sm text-gray-600 mt-1">Parents see these accounts on their payment form, so keep them exactly as the bank has them.</p>
                        <ol class="mt-2 space-y-1 text-sm text-gray-600 list-decimal list-inside">
                            <li>Open <strong>Finance → Bank accounts</strong>.</li>
                            <li>Use <strong>Add an account</strong> for the bank name, account name and number, plus a note for parents (for example <em>"use your child's full name as narration"</em>). Spaces and dashes in the number are removed for you.</li>
                            <li><strong>Switch off</strong> an account to hide it from parents without losing its history; switch it back on whenever you need it.</li>
                            <li><strong>Delete</strong> removes an account completely. Payments already recorded keep the account name and number they were saved with.</li>
                            <li>The same page shows whether <strong>card payments (Paystack)</strong> are connected; until the keys are added, parents see card as <em>coming soon</em>.</li>
                        </ol>
                    </div>

                    <p class="text-sm text-gray-600 mb-2"><strong>How the three methods are confirmed</strong></p>
                    <ul class="list-disc list-inside text-sm text-gray-600 mb-4 space-y-1">
                        <li><strong>Bank transfer</strong> — the parent picks the school account the money went into; check the statement for that account and reference.</li>
                        <li><strong>Cash</strong> — check the cash book and the bursar's receipt before verifying.</li>
                        <li><strong>Card (future)</strong> — Paystack confirms the money itself, so those records arrive already verified.</li>
                    </ul>

                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Log in as the <strong>CEO</strong>, then go to <strong>Finance → Payments</strong>. The orange <strong>Awaiting verification</strong> button filters the queue; the <strong>Method</strong> filter narrows it to transfers or cash.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">Compare the amount, date and reference with the bank statement for a transfer — or with the cash book for cash. Open a record to see where the money was paid and the uploaded receipt.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Money is there → click <strong>"Payment received"</strong>. The installment becomes <strong>Verified</strong> and the learner's fee is marked complete once the balance reaches zero.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">4.</span>
                            <span class="text-gray-600">Money is not there → open the record, choose <strong>"Cannot match it in the bank?"</strong>, type the reason and click <strong>"Reject payment"</strong>. The parent is notified and the balance is untouched.</span>
                        </li>
                    </ol>
                </div>
                @endif

                <!-- Send Notifications -->
                <div class="guide-card bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-bell text-green-600 mr-2"></i>6. Sending Notifications
                    </h3>
                    <p class="text-gray-600 mb-3">Send notifications to specific users or broadcast to everyone.</p>
                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to the <strong>Notifications</strong> section (Admin menu).</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">Click <strong>"Send Notification"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Enter a <strong>Title</strong>, <strong>Message</strong>, select the <strong>Type</strong> (<strong>Information</strong> for general news, <strong>Reminder</strong> for something still to be done, <strong>Alert</strong> for urgent news), and optionally choose a specific <strong>Recipient</strong> (leave blank to send to all users).</span>
                        </li>
                    </ol>
                </div>

                <!-- Admin Oversight -->
                <div class="guide-card bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-eye text-blue-600 mr-2"></i>7. Admin Oversight — Monitoring Activities
                    </h3>
                    <p class="text-gray-600 mb-3">
                        As an Administrator (and as the CEO), you have full visibility into what's happening across all
                        classes. You can review <strong>Attendance</strong>, <strong>Assignments & Submissions</strong>
                        (including grading learner work), and <strong>Learning Materials</strong> for every class —
                        giving you complete oversight over tutors and learners.
                    </p>

                    <h4 class="font-medium text-gray-700 mb-2">
                        <i class="fas fa-clipboard-check text-blue-500 mr-1"></i> Viewing Attendance Records
                    </h4>
                    <ol class="space-y-2 mb-4">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Click <strong>"Attendance"</strong> in the admin navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">See a complete table of attendance records — <strong>Learner name</strong>, <strong>Class</strong>, <strong>Date/Time</strong>, <strong>Status</strong> (Present, Absent, Late, Excused), and <strong>Notes</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Use the <strong>"Filter by Class"</strong> dropdown to view attendance for a specific class.</span>
                        </li>
                    </ol>

                    <h4 class="font-medium text-gray-700 mb-2">
                        <i class="fas fa-tasks text-orange-700 mr-1"></i> Viewing Assignments & Submissions
                    </h4>
                    <ol class="space-y-2 mb-4">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Click <strong>"Assignments"</strong> in the admin navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">View a list of all assignments with their <strong>Title</strong>, <strong>Class</strong>, <strong>Tutor</strong>, <strong>Due Date</strong>, and <strong>Status</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Each assignment card expands to show all <strong>learner submissions</strong> — who submitted, when, their <strong>Score</strong>, and whether it's been <strong>Graded</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">4.</span>
                            <span class="text-gray-600">Use the <strong>"Filter by Class"</strong> dropdown to focus on a particular class.</span>
                        </li>
                    </ol>

                    <div class="info-box mb-4">
                        <p class="text-sm text-gray-700">
                            <strong>✅ New:</strong> Administrators and the CEO are no longer limited to viewing submissions —
                            they can open a learner's file, give it a <strong>score</strong>, add <strong>feedback</strong>,
                            and mark it as <strong>graded</strong>, exactly as the class tutor would.
                        </p>
                    </div>

                    <p class="font-medium text-gray-700 mb-2">Reviewing &amp; Grading a Submission:</p>
                    <ol class="space-y-2 mb-4">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Click <strong>"Review &amp; Grade Submissions (n)"</strong> on an assignment card.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">Click <strong>"Download"</strong> next to a learner to open their submitted file.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Enter the <strong>Score</strong> (out of the assignment's total points) and optional <strong>Feedback</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">4.</span>
                            <span class="text-gray-600">Click <strong>"Save Grade"</strong> — the submission status changes to <span class="text-green-600 font-medium">Graded</span> and the learner (and their parent) sees the score immediately.</span>
                        </li>
                    </ol>

                    <h4 class="font-medium text-gray-700 mb-2">
                        <i class="fas fa-folder text-orange-700 mr-1"></i> Viewing Learning Materials
                    </h4>
                    <ol class="space-y-2 mb-3">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Click <strong>"Materials"</strong> in the admin navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">Browse all uploaded materials across classes — <strong>Title</strong>, <strong>Class</strong>, <strong>Uploaded By</strong> (tutor name), <strong>File Type</strong>, and <strong>Date</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Click <strong>"Download"</strong> to view any file directly.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">4.</span>
                            <span class="text-gray-600">Use the <strong>"Filter by Class"</strong> dropdown to see materials for a specific class.</span>
                        </li>
                    </ol>

                    <div class="info-box">
                        <p class="text-sm text-gray-700"><strong>📌 Note:</strong> <strong>Attendance</strong> and <strong>Learning Materials</strong> oversight pages are <strong>read-only</strong> — they let you monitor activity without interfering with tutors' work. <strong>Assignment submissions</strong> are the exception: admins and the CEO can grade them. Tutors, in turn, can only open and grade assignments that belong to them, so a tutor can never edit another tutor's grades.</p>
                    </div>
                </div>

                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-sun text-orange-700 mr-2"></i>Affirmations
                    </h3>
                    <p class="text-gray-600 mb-2">
                        Open <strong>Communication &rarr; Affirmations</strong> to publish the weekly motivation (up to 500 characters).
                        The newest active post replaces the card on every dashboard immediately.
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        <li>Older posts stay in the history with their author and date.</li>
                        <li><strong>Switch off</strong> a post to fall back to the previous active one (or the built-in daily rotation); <strong>Switch on</strong> restores it.</li>
                        <li><strong>Delete</strong> removes it from the history.</li>
                    </ul>
                </div>

                @if($guideIsCeo)
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-star text-orange-700 mr-2"></i>Testimonials <span class="text-xs font-bold text-orange-700 align-middle">(ADMIN &amp; CEO)</span>
                    </h3>
                    <p class="text-gray-600 mb-2">
                        Members send testimonials either from <strong>Messages</strong> (choose the <em>Testimonial</em> message type)
                        or from <strong>My profile</strong>. Review them in <strong>Communication &rarr; Testimonials</strong>
                        (tabs: Waiting / Approved / Rejected / All).
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        <li><strong>Edit the wording</strong> — open the quote in the review card and save a tidier version; an approved quote stays approved.</li>
                        <li><strong>Approve for the website</strong> — the testimonial appears on the public landing page three at a time in a slider, with the member's photo only if they allowed it.</li>
                        <li><strong>Reject</strong> — add a reason; the member sees it on their profile.</li>
                        <li>Only approved testimonials are public. Typical testimonials are written by parents, tutors and learners.</li>
                    </ul>
                </div>
                @endif
            </section>
            @endif

            <!-- ========== FOR TUTORS ========== -->
            @if($guideIsTutor)
            <section id="for-tutors" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-chalkboard-teacher text-green-600 mr-2"></i>For Tutors
                </h2>
                <p class="text-gray-600 mb-6">
                    As a <strong>Tutor</strong>, you can manage your class schedules, take attendance, create assignments, 
                    grade submissions, and share learning materials with your students.
                </p>

                <!-- Tutor Dashboard -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-tachometer-alt text-blue-600 mr-2"></i>1. Tutor Dashboard
                    </h3>
                    <p class="text-gray-600 mb-2">Your dashboard shows:</p>
                    <ul class="list-disc list-inside text-gray-600 space-y-1 mb-3">
                        <li><strong>My Classes</strong> — number of classes assigned to you</li>
                        <li><strong>Assignments</strong> — total assignments you've created</li>
                        <li><strong>Materials</strong> — total learning materials uploaded</li>
                        <li><strong>Quick Actions</strong> — buttons to quickly create schedules, assignments, or upload materials</li>
                        <li><strong>Upcoming Classes</strong> — your upcoming scheduled sessions with attendance and join buttons</li>
                    </ul>
                </div>

                <!-- Assessment calls -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-video text-blue-600 mr-2"></i>Assessment calls
                    </h3>
                    <p class="text-gray-600 mb-2">
                        The school books a short video call with a prospective learner and assigns it to you. The child
                        may not have an account yet — that is normal for an assessment.
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        <li><strong>Today's assessment calls</strong> on your dashboard show the time, child and guardian, with a <strong>Join call</strong> button.</li>
                        <li><strong>Teaching &rarr; Assessments</strong> lists today, upcoming and past calls (yours only) with the same link, the guardian's phone number and the enquiry reference.</li>
                        <li>Everyone joins from the same Meet/Zoom link: the parent receives it by email/WhatsApp and the school sees it too.</li>
                        <li>If a parent cannot join, call the guardian's number on the card.</li>
                        <li>After the call, open it and <strong>record the result</strong>: Completed or No show, the outcome (ready / needs support / not ready), strengths, focus areas, a recommended class and level, notes, and an optional PDF/image report. Saving moves the enquiry to <strong>Assessed</strong> or <strong>Awaiting enrolment</strong> for the school.</li>
                    </ul>
                </div>

                <!-- Schedules -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-calendar text-orange-700 mr-2"></i>2. Managing Schedules
                    </h3>
                    <p class="text-gray-600 mb-3">Create and manage your class sessions.</p>
                    <ol class="space-y-2 mb-3">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"Schedules"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">Click <strong>"New Schedule"</strong> to create a new class session.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Choose the <strong>Class</strong>, set the <strong>Title</strong> and <strong>Meeting Platform</strong>. Set <strong>Repeat</strong> to <em>Every week on the days &amp; times I choose</em> and add a row per day (for example Monday 10:00 am, Wednesday 2:00 pm, Friday 6:00 pm), then choose <strong>Repeats from</strong> and <strong>Repeat until</strong> &mdash; each day repeats weekly at its own time.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">4.</span>
                            <span class="text-gray-600">After a session is done, click <strong>"Mark as Completed"</strong> to update its status.</span>
                        </li>
                    </ol>
                    <div class="tip-box">
                        <p class="text-sm text-gray-700"><strong>💡 Tip:</strong> Pick <strong>Automatic video room (Jitsi Meet)</strong> and leave the link blank &mdash; a free video link is generated for you and used for every session in the weekly series.</p>
                    </div>
                </div>

                <!-- Attendance -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-clipboard-check text-blue-600 mr-2"></i>3. Taking Attendance
                    </h3>
                    <p class="text-gray-600 mb-3">Record student attendance for your class sessions.</p>
                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">On your dashboard or schedules page, click the <strong>"Attendance"</strong> button next to a scheduled session.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">For each enrolled learner, select their attendance status:</span>
                        </li>
                    </ol>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1 mb-3 ml-4">
                        <li><span class="text-green-600 font-medium">Present</span> — Learner attended the session</li>
                        <li><span class="text-orange-700 font-medium">Absent</span> — Learner was absent</li>
                        <li><span class="text-sun-ink font-medium">Late</span> — Learner arrived late</li>
                        <li><span class="text-blue-600 font-medium">Excused</span> — Absence was pre-approved/excused</li>
                    </ul>
                    <ol class="space-y-2" start="3">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Optionally add <strong>Notes</strong> for each learner.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">4.</span>
                            <span class="text-gray-600">Click <strong>"Save Attendance"</strong> to submit.</span>
                        </li>
                    </ol>
                </div>

                <!-- Assignments -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-tasks text-orange-700 mr-2"></i>4. Creating & Grading Assignments
                    </h3>

                    <p class="font-medium text-gray-700 mb-2">To create an assignment:</p>
                    <ol class="space-y-2 mb-4">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"Assignments"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">Click <strong>"New Assignment"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Fill in: <strong>Title</strong>, <strong>Description</strong>, select the <strong>Class</strong>, set <strong>Due Date</strong>, and <strong>Total Points</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">4.</span>
                            <span class="text-gray-600">Click <strong>"Create Assignment"</strong>.</span>
                        </li>
                    </ol>

                    <p class="font-medium text-gray-700 mb-2">To grade submissions:</p>
                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">On the Assignments list, click <strong>"View (number)"</strong> under Submissions to see all submitted work.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">The submissions table now includes:</span>
                        </li>
                    </ol>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1 mb-3 ml-4">
                        <li><strong>Submitted File</strong> — Click <strong>"Download"</strong> to view/open the learner's uploaded file</li>
                        <li><strong>Notes</strong> — Any additional notes/comments the learner provided with their submission</li>
                        <li>Also visible: <strong>Student name</strong>, <strong>Submitted At</strong> date, <strong>Score</strong>, and <strong>Status</strong></li>
                    </ul>
                    <ol class="space-y-2" start="3">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Review the learner's uploaded file and notes, then enter a <strong>Score</strong> (out of total points) and optionally provide <strong>Feedback</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">4.</span>
                            <span class="text-gray-600">Click <strong>"Grade"</strong> to save the grade. The status will update from "submitted" to "graded".</span>
                        </li>
                    </ol>
                    <div class="info-box mt-3">
                        <p class="text-sm text-gray-700"><strong>📌 Note:</strong> After grading, the student's score and feedback are visible to both the learner and their parent/guardian on their respective dashboards.</p>
                    </div>
                    <div class="warning-box mt-3">
                        <p class="text-sm text-gray-700"><strong>⚠️ Own assignments only:</strong> You can only open and grade assignments that are assigned to <strong>you</strong>. If you try to open another tutor's assignment or submissions, the system refuses access and returns a <strong>"403 Forbidden"</strong> page. If a class needs to be reassigned, ask an administrator to update the class tutor.</p>
                    </div>
                </div>

                <!-- Materials -->
                <div class="guide-card bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-folder-open text-blue-600 mr-2"></i>5. Uploading Learning Materials
                    </h3>
                    <p class="text-gray-600 mb-3">Share documents, presentations, and other resources with your learners.</p>
                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"Materials"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">Click <strong>"Upload Material"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Select the <strong>Class</strong>, enter a <strong>Title</strong> and optional <strong>Description</strong>, then upload the <strong>File</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">4.</span>
                            <span class="text-gray-600">Click <strong>"Upload"</strong> to make it available to learners.</span>
                        </li>
                    </ol>
                    <div class="warning-box mt-4">
                        <p class="text-sm text-gray-700"><strong>⚠️ Note:</strong> Once uploaded, learners in the selected class can instantly view and download the material. You can also delete materials if needed.</p>
                    </div>
                </div>
            </section>
            @endif

            <!-- ========== FOR LEARNERS ========== -->
            @if($guideIsLearner)
            <section id="for-learners" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-user-graduate text-blue-600 mr-2"></i>For Learners (Students)
                </h2>
                <p class="text-gray-600 mb-6">
                    As a <strong>Learner</strong>, you can view your enrolled classes, attend online sessions, submit 
                    assignments, access learning materials, and track your academic progress.
                </p>

                <!-- Learner Dashboard -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-tachometer-alt text-blue-600 mr-2"></i>1. Learner Dashboard
                    </h3>
                    <p class="text-gray-600 mb-2">Your dashboard shows an overview of:</p>
                    <ul class="list-disc list-inside text-gray-600 space-y-1 mb-3">
                        <li><strong>My Classes</strong> — number of classes you're enrolled in</li>
                        <li><strong>Pending Assignments</strong> — assignments not yet submitted</li>
                        <li><strong>Materials</strong> — available learning resources</li>
                        <li><strong>Upcoming Sessions</strong> — your next scheduled classes</li>
                    </ul>
                    <p class="text-gray-600 mb-2">It also lists:</p>
                    <ul class="list-disc list-inside text-gray-600 space-y-1">
                        <li>Your enrolled classes with tutor names</li>
                        <li>Upcoming class sessions with date, time, and a <strong>"Join"</strong> button for virtual classes</li>
                    </ul>
                </div>

                <!-- My Classes -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-chalkboard text-sun-ink mr-2"></i>2. My Classes
                    </h3>
                    <p class="text-gray-600 mb-3">View your enrolled classes and their schedules.</p>
                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"My Classes"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">You'll see each class with its subject, grade level, and description.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Under each class, view the <strong>Upcoming Schedules</strong> with dates, times, and a <strong>"Join Class"</strong> button if the session has a meeting link.</span>
                        </li>
                    </ol>
                </div>

                <!-- Assignments -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-tasks text-green-600 mr-2"></i>3. Submitting Assignments
                    </h3>
                    <p class="text-gray-600 mb-3">View, submit, and track your assignment grades.</p>

                    <p class="font-medium text-gray-700 mb-2">To submit an assignment:</p>
                    <ol class="space-y-2 mb-4">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"Assignments"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">You'll see a list of assignments. Each card shows the <strong>Title</strong>, <strong>Class</strong>, <strong>Due Date</strong>, and <strong>Points</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">For unsubmitted assignments, click <strong>"Submit Assignment"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">4.</span>
                            <span class="text-gray-600">Upload your <strong>file</strong> and optionally add <strong>Notes</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">5.</span>
                            <span class="text-gray-600">Click <strong>"Submit"</strong> to send your work to the tutor.</span>
                        </li>
                    </ol>

                    <p class="font-medium text-gray-700 mb-2">After submission, you can see:</p>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        <li>A green checkmark and submission date</li>
                        <li><strong>Score</strong> (once graded by the tutor)</li>
                        <li><strong>Feedback</strong> from the tutor</li>
                    </ul>
                </div>

                <!-- Materials -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-folder-open text-blue-600 mr-2"></i>4. Accessing Learning Materials
                    </h3>
                    <p class="text-gray-600 mb-3">Download and review materials shared by your tutors.</p>
                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"Materials"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">Browse through the available materials. Each entry shows the <strong>Title</strong>, <strong>File Type</strong> (PDF, DOC, etc.), <strong>Class</strong>, <strong>Description</strong>, <strong>File Size</strong>, and upload date.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Click the <strong>"Download"</strong> button to open or save the file.</span>
                        </li>
                    </ol>
                </div>

                <!-- Join Virtual Class -->
                <div class="guide-card bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-video text-orange-700 mr-2"></i>5. Joining a Virtual Class
                    </h3>
                    <p class="text-gray-600 mb-3">When a tutor schedules a class with a meeting link:</p>
                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to your <strong>Dashboard</strong> or <strong>My Classes</strong> page.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">Find the upcoming session with a green <strong>"Join"</strong> button.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Click <strong>"Join"</strong> or <strong>"Join Class"</strong> — the meeting link will open in a new tab.</span>
                        </li>
                    </ol>
                    <div class="info-box mt-4">
                        <p class="text-sm text-gray-700"><strong>📌 Note:</strong> Sessions that haven't started yet will show as "Scheduled", and completed ones will show as "Completed". The Join button is only available for scheduled sessions with a meeting link.</p>
                    </div>
                </div>

                <!-- Learning Goals -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-bullseye text-orange-700 mr-2"></i>6. Learning Goals
                    </h3>
                    <p class="text-gray-600 mb-2">
                        Goals your tutor set — and the school shared with you — appear on your dashboard under
                        <strong>Goals shared with you</strong>, and in full on <strong>My Learning → Learning Goals</strong>.
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        <li>Each goal shows what you are aiming for, the class and term, and any attachment from your tutor.</li>
                        <li>Only goals the school has chosen to share with learners appear here.</li>
                        <li>Use the goals as your checklist for the term — tick them off with your tutor in class.</li>
                    </ul>
                </div>
            </section>
            @endif

            <!-- ========== FOR PARENTS ========== -->
            @if($guideIsParent)
            <section id="for-parents" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-users text-sun-ink mr-2"></i>For Parents / Guardians
                </h2>
                <p class="text-gray-600 mb-6">
                    As a <strong>Parent or Guardian</strong>, you can monitor your child's academic progress, view their
                    attendance records, check assignment scores, see upcoming class schedules, <strong>record the fees you
                    have paid</strong> (the school verifies them with the bank) and send a private message to the school's
                    CEO or an administrator — all from your dashboard.
                </p>

                <div class="info-box mb-6">
                    <p class="text-sm text-gray-700">
                        <strong>📌 How Parent Accounts Work:</strong> When a Learner registers with parent details, a parent 
                        account is automatically created. Login credentials are sent to the parent's email. You'll then be 
                        able to log in and see your child's activities.
                    </p>
                </div>

                <!-- Parent Dashboard -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-tachometer-alt text-blue-600 mr-2"></i>1. Parent Dashboard
                    </h3>
                    <p class="text-gray-600 mb-3">
                        When you log in, your dashboard displays cards for each child linked to your account. 
                        Each card includes:
                    </p>
                    <ul class="list-disc list-inside text-gray-600 space-y-1 mb-3">
                        <li>Your child's <strong>Name</strong>, <strong>Grade Level</strong>, <strong>Email</strong> and <strong>Phone</strong></li>
                        <li>Four clickable counters: <strong>Class Schedule</strong> (upcoming sessions), <strong>Assignments Pending</strong>,
                            <strong>Assignments Completed</strong> and <strong>Materials Shared</strong></li>
                        <li>An <strong>attendance rate</strong> with the number of sessions attended</li>
                        <li>A <strong>fees line</strong> — verified amount, outstanding balance and any pending submissions — that opens <strong>Fees &amp; Payments</strong></li>
                        <li>An orange <strong>"N missed"</strong> badge whenever a tutor has marked your child absent</li>
                        <li>A <strong>"View Full Details"</strong> button that opens the child's report</li>
                        <li>A <strong>"Message the School"</strong> button (with an orange badge when the school has replied unread)</li>
                    </ul>
                    <div class="tip-box">
                        <p class="text-sm text-gray-700"><strong>💡 Tip:</strong> Tap any counter to jump straight to that list — for example, tap <strong>Assignments Pending</strong> to see exactly which assignments are outstanding.</p>
                    </div>
                </div>

                <!-- View Child Progress -->
                <div class="guide-card bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-chart-line text-green-600 mr-2"></i>2. Viewing Your Child's Progress
                    </h3>
                    <p class="text-gray-600 mb-3">
                        Tap any counter on a child's card — or <strong>"View Full Details"</strong> — to open that child's report.
                        The report is split into tabs:
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-4">
                        <div class="bg-white border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-clipboard-check text-blue-500 mr-2"></i>Attendance
                            </h4>
                            <p class="text-sm text-gray-600">A table showing each class date, the class name, and the attendance status:
                                <span class="text-green-600">Present</span>, 
                                <span class="text-sun-ink">Late</span>, 
                                <span class="text-blue-600">Excused</span>, or 
                                <span class="text-orange-700">Absent</span>.
                            </p>
                        </div>
                        <div class="bg-white border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-tasks text-green-500 mr-2"></i>Assignments
                            </h4>
                            <p class="text-sm text-gray-600">Two tabs: <strong>Pending</strong> (not submitted yet — overdue ones in orange)
                                and <strong>Completed</strong> (submitted date, score, status and the tutor's feedback).</p>
                        </div>
                        <div class="bg-white border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-calendar text-orange-700 mr-2"></i>Upcoming Classes
                            </h4>
                            <p class="text-sm text-gray-600">A list of upcoming scheduled sessions for your child, with the 
                                session title, class name, date, and time.</p>
                        </div>
                        <div class="bg-white border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-folder-open text-blue-500 mr-2"></i>Materials
                            </h4>
                            <p class="text-sm text-gray-600">Every material shared with your child's classes — title, class,
                                tutor, size and date — with a <strong>Download</strong> button.</p>
                        </div>
                        <div class="bg-white border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-calendar-times text-orange-700 mr-2"></i>Missed Classes
                            </h4>
                            <p class="text-sm text-gray-600">Sessions your child was marked <span class="text-orange-700">Absent</span>
                                for, with the class, date and any tutor note. Past sessions the tutor has not marked yet are shown
                                separately, so unrecorded sessions are never counted as missed.</p>
                        </div>
                    </div>

                    <div class="tip-box">
                        <p class="text-sm text-gray-700"><strong>💡 Tip:</strong> If you have multiple children, each one has their own report. An orange <strong>"N missed"</strong> badge on the dashboard means a tutor has marked that child absent — open the <strong>Attendance</strong> tab to see exactly which sessions were missed.</p>
                    </div>
                </div>

                <!-- Parent Fees & Payments -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-file-invoice-dollar text-orange-700 mr-2"></i>3. Fees &amp; Payments
                    </h3>
                    <p class="text-gray-600 mb-3">
                        You record the payments you have made; the school checks the bank and confirms them. A parent paying
                        for two or three children can do it all from one screen.
                    </p>
                    <ol class="space-y-2 mb-3">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Open <strong>Finance → Fees &amp; Payments</strong> (or the fee line on your child's dashboard card) and click <strong>"Record a payment"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">
                                Tick <strong>every class</strong> the payment covers (plus <em>General payment</em> for registration or
                                materials) and type what you paid against each one. One balance is kept per class, so a single transfer
                                can clear two or three subjects at once. Only your own children's classes are offered.
                            </span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">
                                Pay by <strong>bank transfer</strong>:
                                <span class="mt-1 block text-xs text-gray-600">
                                    <span class="block">The school accounts are listed with a <strong>Copy</strong> button. Transfer the exact amount, then pick the account you used (this is required).</span>
                                    <span class="block">The school is online-only, so <strong>cash is not accepted</strong>; <strong>Card</strong> stays disabled until Paystack is connected.</span>
                                </span>
                                Then enter the <strong>total fee</strong> (if you know it), the <strong>amount paid now</strong>, the <strong>reference</strong> (the transfer narration), attach the receipt (PDF or image, 5 MB) and click <strong>"Submit for verification"</strong>.
                            </span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">4.</span>
                            <span class="text-gray-600">The payment shows as <strong>Pending verification</strong> — the CEO checks transfers against the bank statement and cash against the cash book. Verified money reduces the balance, and a fee whose balance reaches zero is marked <strong>Payment complete</strong>.</span>
                        </li>
                    </ol>
                    <div class="tip-box">
                        <p class="text-sm text-gray-700"><strong>💡 Tip:</strong> If the school cannot match a payment with the bank or cash book, it is marked <strong>Rejected</strong> and the reason is shown next to it — correct the details and submit it again. Receipts stay private: only you and the school can download them. The <strong>Finance → Fees &amp; Payments</strong> page also lists the school's active bank accounts, so you can pay before you start a record.</p>
                    </div>
                </div>

                <!-- Parent Messaging -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-comments text-blue-600 mr-2"></i>4. Messaging the School
                    </h3>
                    <p class="text-gray-600 mb-3">
                        You can contact the school directly from inside the app — no external email needed. Messages are
                        private between you and the person you are talking to.
                    </p>
                    <ol class="space-y-2 mb-3">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span class="text-gray-600">Click <strong>"Message the School"</strong> on your dashboard, or <strong>"Messages"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span class="text-gray-600">Under <strong>"School Administration"</strong>, pick the <strong>CEO</strong> or an <strong>administrator</strong> from the list.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span class="text-gray-600">Type your message (up to <strong>2,000 characters</strong>) and click <strong>"Send"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-blue-600 font-bold">4.</span>
                            <span class="text-gray-600">Replies appear in the same conversation thread. An orange <strong>"n new"</strong> badge shows unread replies, and the school also receives a notification in their bell menu.</span>
                        </li>
                    </ol>
                    <div class="tip-box">
                        <p class="text-sm text-gray-700"><strong>💡 Tip:</strong> Open a conversation to read it — messages are marked as read automatically, so the unread badges clear as soon as you open the thread.</p>
                    </div>
                </div>
            </section>
            @endif

            <!-- ========== MESSAGES ========== -->
            <section id="messages" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-comments text-blue-600 mr-2"></i>Messages — everyone connected to the school
                </h2>
                <p class="text-gray-600 mb-4">
                    The <strong>Messages</strong> feature gives every role a private, in-app channel. Messages stay inside
                    Excellent Scholars Hub, so there is a clear record of every conversation for both sides. Administrators
                    can also send one message to a whole category — see <a href="#broadcasts" class="text-blue-700 underline">Broadcasts</a>.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-50 border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-2 flex items-center">
                            <i class="fas fa-users text-orange-700 mr-2"></i> Who can message
                        </h4>
                        <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                            @if($guideIsParent)
                            <li><strong>Parents/Guardians</strong> can message the <strong>CEO</strong> and every <strong>administrator</strong>.</li>
                            @elseif($guideIsLearner)
                            <li><strong>Learners</strong> can message the school and their own <strong>tutors</strong>.</li>
                            @elseif($guideIsTutor)
                            <li><strong>Tutors</strong> can message the school and the <strong>learners in their classes</strong>.</li>
                            @elseif($guideIsAdmin)
                            <li><strong>CEO</strong> and <strong>administrators</strong> can message parents, tutors, learners and each other.</li>
                            @endif
                        </ul>
                    </div>
                    <div class="bg-gray-50 border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-2 flex items-center">
                            <i class="fas fa-bell text-blue-600 mr-2"></i> Notifications built in
                        </h4>
                        <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                            <li>Every message creates a notification for the recipient.</li>
                            <li>The <strong>Messages</strong> menu item shows an orange unread count badge.</li>
                            <li>Opening a conversation marks incoming messages as read.</li>
                        </ul>
                    </div>
                </div>

                @if($guideIsParent)
                <h3 class="text-lg font-semibold text-gray-700 mb-3">For Parents — Starting a Conversation</h3>
                <ol class="space-y-2 mb-6">
                    <li class="flex items-start gap-2 text-sm">
                        <span class="text-blue-600 font-bold">1.</span>
                        <span class="text-gray-600">Click <strong>"Message the School"</strong> on your dashboard or <strong>"Messages"</strong> in the menu.</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm">
                        <span class="text-blue-600 font-bold">2.</span>
                        <span class="text-gray-600">Choose the <strong>CEO</strong> (yellow badge) or an <strong>administrator</strong> (orange badge) from <strong>"School Administration"</strong>.</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm">
                        <span class="text-blue-600 font-bold">3.</span>
                        <span class="text-gray-600">Write your question or update and click <strong>"Send"</strong>. Your message history stays on this page for reference.</span>
                    </li>
                </ol>
                @endif

                @if($guideIsTutor || $guideIsLearner)
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Starting a conversation</h3>
                <ol class="space-y-2 mb-6">
                    <li class="flex items-start gap-2 text-sm">
                        <span class="text-blue-600 font-bold">1.</span>
                        <span class="text-gray-600">Click <strong>"Messages"</strong> in the menu — the orange badge shows unread replies.</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm">
                        <span class="text-blue-600 font-bold">2.</span>
                        <span class="text-gray-600">Pick a contact: {{ $guideIsLearner ? 'the school, or one of your own tutors' : 'the school, or a learner in one of your classes' }}.</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm">
                        <span class="text-blue-600 font-bold">3.</span>
                        <span class="text-gray-600">Write the message and click <strong>"Send"</strong>. Opening a thread marks it as read; replies stay in the same conversation.</span>
                    </li>
                </ol>
                @endif

                @if($guideIsAdmin)
                <h3 class="text-lg font-semibold text-gray-700 mb-3">For the CEO / Administrators — Replying to Parents</h3>
                <ol class="space-y-2 mb-4">
                    <li class="flex items-start gap-2 text-sm">
                        <span class="text-blue-600 font-bold">1.</span>
                        <span class="text-gray-600">Click <strong>"Messages"</strong> in the admin menu — an orange badge shows how many unread parent messages you have.</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm">
                        <span class="text-blue-600 font-bold">2.</span>
                        <span class="text-gray-600">Under <strong>"Parents &amp; Guardians"</strong>, the list shows each parent's last message preview, when it was sent, and their unread count.</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm">
                        <span class="text-blue-600 font-bold">3.</span>
                        <span class="text-gray-600">Open a parent to read the full thread (messages are marked read), then reply in the box at the bottom.</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm">
                        <span class="text-blue-600 font-bold">4.</span>
                        <span class="text-gray-600">The admin dashboard also shows an <strong>"Unread Messages"</strong> card linking straight to your inbox.</span>
                    </li>
                </ol>

                <div class="info-box">
                    <p class="text-sm text-gray-700">
                        <strong>📌 Note:</strong> Administrators and the CEO share the parent inbox — if a parent writes to
                        one administrator, that administrator can reply in the same thread. Only active accounts appear in
                        the contact list, so deactivated users cannot start or receive conversations.
                    </p>
                </div>
                @endif
            </section>

            <!-- ========== NOTIFICATIONS ========== -->
            <section id="notifications" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-bell text-blue-600 mr-2"></i>Notifications
                </h2>
                <p class="text-gray-600 mb-4">
                    The notification system keeps everyone informed. Administrators can send information, reminders, 
                    and alerts. All users receive and manage their notifications in one place.
                </p>
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Notification Types</h3>
                <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                    <li><strong>Information</strong> (<i class="fas fa-info-circle text-blue-600"></i> blue badge) &mdash; general news the recipient should know. No action required.</li>
                    <li><strong>Reminder</strong> (<i class="fas fa-clock text-sun-ink"></i> amber badge) &mdash; a nudge about something still to be done, usually with a deadline.</li>
                    <li><strong>Alert</strong> (<i class="fas fa-exclamation-triangle text-orange-700"></i> orange badge) &mdash; urgent or exceptional news that needs immediate attention.</li>
                    <li><strong>Message</strong> (grey badge) &mdash; created automatically when someone sends you a direct message; administrators cannot pick it.</li>
                </ul>



                <h3 class="text-lg font-semibold text-gray-700 mb-3">How to Access Notifications</h3>
                <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                    <li>Click the <strong>Notifications</strong> link in the navigation menu (available to all logged-in users).</li>
                    <li>Administrators can also access the <strong>Notifications</strong> section under the admin menu to send notifications.</li>
                </ul>

                <h3 class="text-lg font-semibold text-gray-700 mb-3">Managing Your Notifications</h3>
                <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                    <li><strong>Unread notifications</strong> are highlighted with a coloured left border that matches the type (blue, amber or orange)</li>
                    <li>Use the <strong>All</strong>, <strong>Information</strong>, <strong>Reminder</strong> and <strong>Alert</strong> tabs at the top of the page to show only one type</li>
                    <li>Click <strong>"Mark read"</strong> on individual notifications to dismiss them</li>
                    <li>Use <strong>"Mark all as read"</strong> to clear all notifications at once</li>
                </ul>

                <h3 class="text-lg font-semibold text-gray-700 mb-3">Message Notifications</h3>
                <ul class="list-disc list-inside text-gray-600 space-y-2">
                    <li>When a parent messages the school (or the school replies), the recipient gets a notification titled <strong>"New message from …"</strong> with a preview of the first 140 characters.</li>
                    <li>The <strong>Messages</strong> menu item separately shows an orange count of unread conversations.</li>
                    <li>The two counters are independent: opening a thread clears the Messages badge, while the notification itself is cleared with <strong>"Mark read"</strong> or <strong>"Mark all as read"</strong> in your Notifications page.</li>
                </ul>
            </section>

            <!-- ========== WHAT'S NEW ========== -->
            <section id="whats-new" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-star text-orange-700 mr-2"></i>What's new in this release
                </h2>
                <p class="text-gray-600 mb-6">
                    Everything below was added after the last review meeting. Each item links to the detailed section
                    further down this guide.
                </p>

                <div class="overflow-x-auto mb-8">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-[#EAF2FB]">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Area</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">What changed</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Who</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @if($guideIsParent || $guideIsCeo)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Fees</td><td class="px-4 py-3 text-gray-600">Parents record their own payments (installments, receipt upload, per term and per class) and choose <strong>bank transfer</strong>, <strong>cash</strong> or <strong>card</strong>. One transfer can be recorded <strong>once for several children</strong> — tick each child, then each class — and the CEO checks the bank or cash book and clicks <strong>Payment received</strong> — see <a href="#payments" class="text-blue-700 underline">Fees</a>.</td><td class="px-4 py-3 text-gray-600">Parents, CEO</td></tr>
                            @endif
                            @if($guideIsCeo)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Bank accounts</td><td class="px-4 py-3 text-gray-600">New <strong>Finance → Bank accounts</strong> screen: the CEO adds, edits, switches off or deletes the accounts parents transfer into. Only switched-on accounts appear on the parent form, with a copy-number button — see <a href="#payments" class="text-blue-700 underline">Fees</a>.</td><td class="px-4 py-3 text-gray-600">CEO</td></tr>
                            @endif
                            @if($guideIsAdmin)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Messages</td><td class="px-4 py-3 text-gray-600">Categorised sending to parents, tutors, students, one class or everyone — see <a href="#broadcasts" class="text-blue-700 underline">Broadcasts</a>.</td><td class="px-4 py-3 text-gray-600">Admin, CEO</td></tr>
                            @endif
                            @if($guideIsTutor || $guideIsLearner)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Inbox</td><td class="px-4 py-3 text-gray-600">Tutors and learners now have their own inbox and can reply.</td><td class="px-4 py-3 text-gray-600">Tutors, Learners</td></tr>
                            @endif
                            @if($guideIsAdmin || $guideIsTutor || $guideIsParent)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Learning goals</td><td class="px-4 py-3 text-gray-600">Tutors write goals in a <strong>large text area</strong> (up to 5,000 characters) with a free <strong>Improve wording</strong> AI assistant. The school shares each goal with <strong>individual parents, learners and tutors</strong> — see <a href="#learning-goals" class="text-blue-700 underline">Learning goals</a>. Picking people by name no longer ticks the <em>Every parent/learner/tutor</em> boxes, so the chips show only who was really chosen. Long goals show the opening lines with a <strong>Read the full goal</strong> toggle on the admin and tutor lists, and the learner/parent pages show the whole text. Shared goals appear on the parent's child page, the learner's dashboard and the tutor's Goals screen.</td><td class="px-4 py-3 text-gray-600">Tutors, Admin, CEO, Parents, Learners</td></tr>
                            @endif
                            @if($guideIsLearner)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Learner panel</td><td class="px-4 py-3 text-gray-600">"Live now / Up next" panel at the top of the dashboard — see <a href="#dashboard-extras" class="text-blue-700 underline">Dashboard extras</a>.</td><td class="px-4 py-3 text-gray-600">Learners</td></tr>
                            @endif
                            @if($guideIsAdmin || $guideIsTutor)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Assessment calls</td><td class="px-4 py-3 text-gray-600">Parents can request an assessment on the website; the school books the Meet/Zoom call, invites the tutor and emails/WhatsApps the guardian the join link. After the call the tutor or school records the result (outcome, strengths, focus areas, recommended class, report), which moves the enquiry to Assessed or Awaiting enrolment.</td><td class="px-4 py-3 text-gray-600">Admin, CEO, Tutors</td></tr>
                            @endif
                            @if($guideIsAdmin)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Admissions</td><td class="px-4 py-3 text-gray-600">Capture WhatsApp/phone enquiries before an account exists, track them on the pipeline and log every call on the timeline &mdash; <strong>People &rarr; Admissions</strong>.</td><td class="px-4 py-3 text-gray-600">Admin, CEO</td></tr>
                            @endif
                            @if($guideIsAdmin)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Conversion</td><td class="px-4 py-3 text-gray-600">Turn an <em>Awaiting enrolment</em> enquiry into parent + learner accounts, enrol the child in the recommended class and email the logins &mdash; <strong>Convert to learner</strong> on the enquiry.</td><td class="px-4 py-3 text-gray-600">Admin, CEO</td></tr>
                            @endif
                            @if($guideIsAdmin)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Reporting</td><td class="px-4 py-3 text-gray-600">Monthly classes per tutor with a month picker and a six-month trend, counting classes <strong>ticked as completed</strong> &mdash; tick them from Schedules.</td><td class="px-4 py-3 text-gray-600">Admin, CEO</td></tr>
                            @endif
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Profiles &amp; testimonials</td><td class="px-4 py-3 text-gray-600">Update your name, email and photo from <strong>My profile</strong> (person icon in the top bar) and submit a testimonial, choosing whether your photo may be published. The CEO approves what appears on the website.</td><td class="px-4 py-3 text-gray-600">Everyone</td></tr>
                            @if($guideIsCeo)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Testimonials review</td><td class="px-4 py-3 text-gray-600">Approve or reject member testimonials before they appear on the landing page &mdash; <strong>Communication &rarr; Testimonials</strong>.</td><td class="px-4 py-3 text-gray-600">CEO</td></tr>
                            @endif
                            @if($guideIsAdmin)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Affirmations</td><td class="px-4 py-3 text-gray-600">Publish the weekly motivation and switch older posts on/off &mdash; <strong>Communication &rarr; Affirmations</strong>.</td><td class="px-4 py-3 text-gray-600">Admin, CEO</td></tr>
                            @endif
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Affirmation</td><td class="px-4 py-3 text-gray-600">The school's weekly message (or a rotating fallback) appears in a colourful card on every dashboard.</td><td class="px-4 py-3 text-gray-600">Everyone</td></tr>
                            @if($guideIsAdmin || $guideIsTutor || $guideIsLearner || $guideIsParent)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Assignments</td><td class="px-4 py-3 text-gray-600">Tutors can attach up to six pictures to an assignment.</td><td class="px-4 py-3 text-gray-600">Tutors, Learners, Parents</td></tr>
                            @endif
                            @if($guideIsAdmin)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Classes</td><td class="px-4 py-3 text-gray-600">Maximum students and the <strong>Class Code</strong> are both optional (blank code = auto-generated, blank maximum = unlimited).</td><td class="px-4 py-3 text-gray-600">Admin, CEO</td></tr>
                            @endif
                            @if($guideIsAdmin || $guideIsTutor)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Schedules</td><td class="px-4 py-3 text-gray-600">Set a lesson once with <strong>Repeat &rarr; Every week on the days &amp; times I choose</strong>: add a row per day (10:00 am Monday, 2:00 pm Wednesday, 6:00 pm Friday) plus <em>Repeats from</em> / <em>Repeat until</em> dates, and each day repeats weekly at its own time. The list has a filter bar for <strong>Class</strong>, <strong>Tutor</strong>, <strong>Start Time (day)</strong> and <strong>Status</strong> (plus <em>Today</em> and <em>Reset</em> shortcuts). Choose <strong>Automatic video room (Jitsi Meet)</strong> to generate a free meeting link once and reuse it for the series. Weekly sessions are flagged on the Schedules list.</td><td class="px-4 py-3 text-gray-600">Admin, CEO, Tutors</td></tr>
                            @endif
                            @if($guideIsAdmin)
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Users</td><td class="px-4 py-3 text-gray-600">The Users page now filters by <strong>role</strong> (quick chips show the count per role), plus status and a name/username/email search.</td><td class="px-4 py-3 text-gray-600">Admin, CEO</td></tr>
                            @endif
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Website</td><td class="px-4 py-3 text-gray-600">Public landing page with sliders, programmes, testimonials and registration.</td><td class="px-4 py-3 text-gray-600">Visitors</td></tr>
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Branding</td><td class="px-4 py-3 text-gray-600">Blue, white and orange colour scheme across the whole platform.</td><td class="px-4 py-3 text-gray-600">Everyone</td></tr>
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Registration</td><td class="px-4 py-3 text-gray-600">The public sign-up form now opens with <strong>I am a — Learner / Tutor</strong> as its first field. Picking Learner re-words the shared inputs (<strong>Learner Full Name</strong>, <strong>Learner Username</strong>, <strong>Learner Email</strong>, <strong>Learner Phone</strong>) and reveals the separate <strong>Parent/Guardian Details</strong> block; picking Tutor keeps the tutor wording and hides the guardian block.</td><td class="px-4 py-3 text-gray-600">Visitors</td></tr>
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Usernames</td><td class="px-4 py-3 text-gray-600">Sign in with an email <strong>or</strong> a unique username. A child with no email still needs a guardian email, which receives their login details and password-reset links.</td><td class="px-4 py-3 text-gray-600">Everyone</td></tr>
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Passwords</td><td class="px-4 py-3 text-gray-600">Forgot-password reset by email (one-time link, 60 minutes), plus a school-administration fallback that generates the link from <strong>Users</strong> when mail is not configured. Accounts created by the school must choose their own password at first sign-in.</td><td class="px-4 py-3 text-gray-600">Everyone</td></tr>
                            <tr><td class="px-4 py-3 font-medium text-gray-800">Help</td><td class="px-4 py-3 text-gray-600">This guide is now role-aware: it only shows the sections and FAQ answers for the screens your account can open.</td><td class="px-4 py-3 text-gray-600">Everyone</td></tr>
                        </tbody>
                    </table>
                </div>

                @if($guideIsTutor || $guideIsAdmin)
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Pictures on assignments</h3>
                <ol class="space-y-2 mb-4">
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">1.</span><span class="text-gray-600">As a tutor open <strong>Assignments → New Assignment</strong>.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">2.</span><span class="text-gray-600">Fill in the title, class, due date and description as usual.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">3.</span><span class="text-gray-600">Under <strong>Pictures</strong> select up to six images (JPG, PNG or WebP, 2 MB each), then click <strong>Create Assignment</strong>.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">4.</span><span class="text-gray-600">Thumbnails appear on the learner's assignment cards, in the tutor's assignment list and in administrator oversight.</span></li>
                </ol>
                @endif

                @if($guideIsAdmin)
                <h3 class="text-lg font-semibold text-gray-700 mb-3 mt-6">Classes without a maximum</h3>
                <p class="text-gray-600 mb-4">
                    On <strong>Classes → Create / Edit</strong> you can now leave <strong>Max Students</strong> blank. Such a
                    class shows <strong>No limit</strong> in the list and detail pages, and enrolment is never blocked by capacity.
                </p>
                @endif

                <h3 class="text-lg font-semibold text-gray-700 mb-3">Public website</h3>
                <p class="text-gray-600 mb-6">
                    Visitors who are not signed in now land on the school's public page: a rotating hero slider, programme
                    cards built from your active classes, how-it-works steps, testimonials, the about/contact block and a
                    <strong>Register for classes</strong> button. Signed-in users still go straight to their dashboard.
                </p>

                @if($guideIsParent || $guideIsCeo)
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Parents record fees, the CEO verifies</h3>
                <p class="text-gray-600 mb-4">
                    @if($guideIsParent)
                        You now record your own payments from <strong>Finance → Fees &amp; Payments</strong>, choosing only
                        your own children and that child's classes. A submission waits as <strong>Pending verification</strong>
                        until the CEO checks the bank statement (transfer) or the cash book (cash); it then becomes
                        <strong>Verified</strong> and the fee is marked complete once its balance reaches zero. Each payment
                        records <strong>how</strong> you paid: a <strong>bank transfer</strong> into one of the school
                        accounts, <strong>cash</strong> at the office, or <strong>card</strong> (Paystack, coming soon).
                    @else
                        The <strong>Record Payment</strong> button has left your screen. Parents now enter what they paid from
                        <strong>Finance → Fees &amp; Payments</strong>, choosing only their own children and that child's classes;
                        the submission waits as <strong>Pending verification</strong>. You open
                        <strong>Finance → Payments</strong>, check the amount and reference against the bank or cash book and
                        click <strong>Payment received</strong> — the installment becomes <strong>Verified</strong> and the fee
                        is marked complete once its balance reaches zero. Anything that cannot be matched is rejected with a
                        reason, which the parent sees on their fee screen. Each payment also records <strong>how</strong> the
                        money was paid: a <strong>bank transfer</strong> into one of the school accounts, <strong>cash</strong>
                        at the office, or <strong>card</strong> (Paystack, coming soon). Maintain the transfer accounts under
                        <strong>Finance → Bank accounts</strong>.
                    @endif
                </p>
                @endif
            </section>


            <!-- ========== PAYMENTS, PART PAYMENTS & RECEIPTS ========== -->
            @if($guideIsParent || $guideIsCeo)
            <section id="payments" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-file-invoice-dollar text-orange-700 mr-2"></i>Payments, part payments &amp; receipts
                </h2>
                <div class="warning-box">
                    @if($guideIsParent)
                        <strong>You record it, the school confirms it.</strong> Submit what you paid and upload the receipt;
                        the <strong>CEO</strong> checks the bank statement (transfer) or the cash book (cash) and verifies it.
                        Only verified installments reduce the balance.
                    @else
                        <strong>Two screens, one flow.</strong> Parents record what they paid and upload the receipt; you
                        check the bank statement (transfer) or the cash book (cash) and verify it. Administrators still
                        cannot open any payment screen — the menu item and every payment URL stay closed to them.
                    @endif
                </div>

                @if($guideIsParent)
                <h3 class="text-lg font-semibold text-gray-700 mb-3 mt-6">Step 1 — record your payment</h3>
                <ol class="space-y-2 mb-4">
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">1.</span><span class="text-gray-600">Open <strong>Finance → Fees &amp; Payments</strong> and click <strong>Record a payment</strong>.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">2.</span><span class="text-gray-600">Tick <strong>every child</strong> this transfer covers (only your own children are ever listed), then type the amount paid against each child and class row &mdash; one transfer is recorded once for the whole family, not once per child.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">3.</span><span class="text-gray-600">Choose the <strong>payment type</strong> and <strong>term</strong>; each row takes the <strong>amount paid now</strong> and, if known, the <strong>total fee</strong>. A single installment can never be larger than the total fee.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">4.</span><span class="text-gray-600">Pay by <strong>bank transfer</strong> — the school accounts are listed with a copy button, and you must pick the one you used (cash is not accepted, as the school is online-only; card stays disabled as <em>coming soon</em> until Paystack is connected). Then add the reference (the transfer narration), the date and the <strong>receipt or teller slip</strong> (PDF or image, up to 5 MB).</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">5.</span><span class="text-gray-600">On submit the record is stored as <strong>Pending verification</strong> — you can never mark your own payment as paid.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">6.</span><span class="text-gray-600">The school is notified: <em>"Payment awaiting verification"</em>, with the total amount and every child and fee it covers.</span></li>
                </ol>
                @endif

                @if($guideIsCeo)
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Step 2 — the CEO verifies it against the bank</h3>
                <ol class="space-y-2 mb-6">
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">1.</span><span class="text-gray-600">Open <strong>Finance → Payments</strong> and click <strong>Awaiting verification</strong> to see the queue.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">2.</span><span class="text-gray-600">Compare the amount, date and reference with the bank statement for a transfer (the record shows the account it was paid into) — or with the cash book for cash. The parent's receipt is downloadable from the record.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">3.</span><span class="text-gray-600">Money is there → click <strong>Payment received</strong>. The installment becomes <strong>Verified</strong> (status <em>completed</em>), the fee balance drops with it, and a zero balance marks the learner's fee <strong>Payment complete</strong>.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">4.</span><span class="text-gray-600">Money is not there → open the record, use <strong>Cannot match it in the bank?</strong>, type the reason and click <strong>Reject payment</strong>. The parent is notified, the record turns <strong>Rejected</strong> and the balance is untouched.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">5.</span><span class="text-gray-600">Both actions stamp <strong>who verified or rejected it and when</strong>, shown in the <em>Check</em> column and on the detail page.</span></li>
                </ol>
                @endif

                @if($guideIsParent)
                <p class="text-gray-600 mb-6">
                    To add another part payment for the same fee, submit again with the same child, term and class.
                    Every record is one installment; the balance updates automatically once it is verified.
                </p>
                @endif

                @if($guideIsCeo)
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Reading the CEO payments screen</h3>
                <ul class="list-disc list-inside text-gray-600 space-y-1 mb-6">
                    <li><strong>Total Recorded</strong> — everything verified (completed), across all fees.</li>
                    <li><strong>Pending</strong> — money parents reported that is still waiting for the bank check; the tile counts the submissions.</li>
                    <li><strong>Outstanding</strong> — the remaining balance of every fee, worked out per learner, term and class.</li>
                    <li><strong>Receipts Uploaded</strong> — how many records have a downloadable receipt.</li>
                    <li>The <strong>Check</strong> column holds the <strong>Payment received</strong> button for pending rows and, afterwards, who verified or rejected the record and when.</li>
                    <li>Use the filters (learner, term, class, status, <strong>method</strong>) and click <strong>Filter</strong>; <strong>Reset</strong> clears them.</li>
                </ul>

                <p class="text-gray-600 mb-6">
                    <strong>Where the money went.</strong> A transfer shows the school account it was paid into
                    (for example <em>GTBank · 0123456789</em>); cash shows the school office. Those details are saved
                    with the payment, so renaming, switching off or deleting an account later never rewrites history.
                    Maintain the accounts under <strong>Finance → Bank accounts</strong>.
                </p>
                @endif

                @if($guideIsParent)
                <h3 class="text-lg font-semibold text-gray-700 mb-3">What the parent sees</h3>
                <ul class="list-disc list-inside text-gray-600 space-y-1 mb-6">
                    <li>Each child has a card per fee (<em>term · class</em>) with the total fee, the verified amount, the balance and a progress bar.</li>
                    <li><strong>Pending verification</strong> — submitted, waiting for the CEO; it never reduces the balance yet.</li>
                    <li><strong>Verified</strong> — confirmed by the school; it counts towards the fee immediately.</li>
                    <li><strong>Rejected</strong> — the reason is shown next to the record so you can correct and resubmit.</li>
                    <li><strong>Payment complete</strong> — a green badge appears as soon as the fee's balance reaches zero.</li>
                </ul>
                @endif

                @if($guideIsCeo)
                <h3 class="text-lg font-semibold text-gray-700 mb-3">The payment detail page</h3>
                <p class="text-gray-600 mb-3">
                    Click any reference number to open it. You get the full record, the pending bank-check panel with the
                    verify and reject actions, the fee balance with a progress bar, the receipt download, and the
                    <strong>installment history</strong> for that same fee with the current record highlighted.
                </p>
                @endif

                <div class="tip-box">
                    <strong>Receipts are private.</strong> They are stored outside the public folders.
                    @if($guideIsParent)
                        Only you and the school can download the receipts you upload for your own children.
                    @else
                        You can download every receipt from the payment screens.
                    @endif
                </div>
            </section>
            @endif


            <!-- ========== LEARNING GOALS ========== -->
            @if($guideIsAdmin || $guideIsTutor || $guideIsParent || $guideIsLearner)
            <section id="learning-goals" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-bullseye text-orange-700 mr-2"></i>Learning goals
                </h2>
                <p class="text-gray-600 mb-4">
                    @if($guideIsParent)
                        Your child's tutor records what they are working towards. The school decides which goals are
                        shared with parents — nothing reaches you automatically.
                    @elseif($guideIsTutor)
                        You record what each learner is working towards. Administrators and the CEO review those goals and
                        decide which ones are shared with the parent, the learner and/or you — nothing reaches a parent or
                        learner automatically.
                    @elseif($guideIsLearner)
                        Your tutor records what you are working towards. The school reviews those goals and decides which
                        ones you can see — they then appear on your dashboard and in <strong>My Learning → Learning Goals</strong>.
                    @else
                        Tutors write what each learner is working towards. You review those goals and choose <strong>individual
                        parents, learners and tutors</strong> to share each one with &mdash; or share it with everyone the goal
                        is about &mdash; and nothing reaches them automatically.
                    @endif
                </p>

                @if($guideIsTutor)
                <h3 class="text-lg font-semibold text-gray-700 mb-3">For tutors — uploading a goal</h3>
                <ol class="space-y-2 mb-6">
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">1.</span><span class="text-gray-600">Open <strong>Goals</strong> in the menu and click <strong>New goal</strong>.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">2.</span><span class="text-gray-600">Write the goal in the large <strong>Goal</strong> box &mdash; up to 5,000 characters, so the whole wording fits, not just a heading.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">3.</span><span class="text-gray-600">Optional: click <strong>Improve wording (free AI)</strong>. The free LanguageTool assistant fixes spelling, grammar and phrasing and puts the corrected text back in the box for you to review.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">4.</span><span class="text-gray-600">Optionally choose the <strong>class</strong> and the <strong>term</strong>, attach a PDF, Word document or image (maximum 8 MB) and click <strong>Save goal</strong>.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">5.</span><span class="text-gray-600">The sharing column shows <strong>Pending review</strong> until the school shares it.</span></li>
                </ol>
                <div class="warning-box">
                    You can delete a goal you uploaded at any time. Deleting it also removes the attachment and takes it
                    away from any parent who could already see it.
                </div>
                @endif

                @if($guideIsAdmin)
                <h3 class="text-lg font-semibold text-gray-700 mb-3 mt-6">For administrators and the CEO — sharing</h3>
                <ol class="space-y-2 mb-6">
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">1.</span><span class="text-gray-600">Open <strong>Goals</strong> in the admin menu. The tiles show all goals, how many are shared with parents, learners and tutors, and how many are awaiting review.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">2.</span><span class="text-gray-600">Filter by tutor or by sharing status if the list is long.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">3.</span><span class="text-gray-600">Open <strong>Choose people</strong> in the sharing column. Tick the <strong>individual parents</strong>, <strong>learners</strong> and <strong>tutors</strong> who should receive the goal (hold Ctrl/Cmd to pick several), or tick <em>Every parent/learner/tutor the goal is about</em> to share it more widely, then click <strong>Save sharing</strong>. Picking people by name never ticks those <em>Every …</em> boxes &mdash; the boxes stay exactly as you left them.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">4.</span><span class="text-gray-600">The chips beside the form show exactly who has the goal: one chip per person picked by name, plus an <strong>All parents / All learners / All tutors</strong> chip only for the roles you ticked <em>Every …</em> on. <strong>Last shared</strong> records who shared it and when. Unticking everyone withdraws it.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">5.</span><span class="text-gray-600">Use the <strong>Shared with chosen people</strong> filter to list every individually shared goal.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">6.</span><span class="text-gray-600">Goals can be up to 5,000 characters. The list shows the opening lines with a <strong>&ldquo;Read the full goal&rdquo;</strong> toggle &mdash; click it to open the whole text without leaving the page. The tutor who wrote the goal and everyone it was shared with read the full text on their own pages.</span></li>
                </ol>
                @endif

                @if($guideIsParent)
                <h3 class="text-lg font-semibold text-gray-700 mb-3">For parents — where to find them</h3>
                <p class="text-gray-600 mb-3">
                    On the parent dashboard open a child and choose the <strong>Learning Goals</strong> tab (the orange
                    counter). Only goals the school shared with you (by name, or with all parents of that class) appear
                    there, showing the goal, the tutor, the term and any attachment.
                </p>
                @endif

                @if($guideIsTutor)
                <h3 class="text-lg font-semibold text-gray-700 mb-3">For tutors — goals shared with you</h3>
                <p class="text-gray-600">
                    The bottom of the <strong>Goals</strong> screen lists goals another tutor wrote and the school shared
                    with you, including who shared it and when.
                </p>
                @endif

                @if($guideIsLearner)
                <h3 class="text-lg font-semibold text-gray-700 mb-3">For learners — where to find them</h3>
                <p class="text-gray-600">
                    Goals shared with you appear under <strong>Goals shared with you</strong> on your dashboard (the
                    latest three) and in full under <strong>My Learning → Learning Goals</strong>. Each card shows the
                    target, the class and term, and any attachment the tutor added.
                </p>
                @endif
            </section>
            @endif


            <!-- ========== CATEGORISED MESSAGES / BROADCASTS ========== -->
            @if($guideIsAdmin)
            <section id="broadcasts" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-bullhorn text-orange-700 mr-2"></i>Categorised messages (broadcasts)
                </h2>
                <p class="text-gray-600 mb-4">
                    Instead of writing to one parent at a time, administrators and the CEO can send the same message to a
                    whole category: <strong>Parents</strong>, <strong>Tutors</strong>, <strong>Students</strong>,
                    <strong>a single class</strong>, or <strong>everyone</strong>. Each recipient gets a private copy in their
                    inbox plus a notification — and can reply privately.
                </p>

                <h3 class="text-lg font-semibold text-gray-700 mb-3">Sending a categorised message</h3>
                <ol class="space-y-2 mb-6">
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">1.</span><span class="text-gray-600">Go to <strong>Messages → Message a category</strong> (or <strong>Broadcasts → New message</strong>).</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">2.</span><span class="text-gray-600">Choose the audience. The card shows how many active accounts will receive it.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">3.</span><span class="text-gray-600">For <strong>A single class</strong>, pick the class. The message reaches the learners, their linked parents and the class tutor.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">4.</span><span class="text-gray-600">Type the message (maximum 2,000 characters) and click <strong>Send message</strong>.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">5.</span><span class="text-gray-600">The confirmation tells you exactly how many recipients were reached.</span></li>
                </ol>

                <h3 class="text-lg font-semibold text-gray-700 mb-3">Tracking what you sent</h3>
                <ul class="list-disc list-inside text-gray-600 space-y-1 mb-6">
                    <li><strong>Broadcasts</strong> lists every send with its audience, message preview, recipient count and how many copies have been read.</li>
                    <li>The three tiles count broadcasts sent, delivered copies and unread copies.</li>
                    <li>Replies from recipients arrive as normal one-to-one conversations in <strong>Messages</strong>.</li>
                </ul>

                <h3 class="text-lg font-semibold text-gray-700 mb-3">Reading a message as a tutor or learner</h3>
                <p class="text-gray-600 mb-3">
                    Tutors and learners now have their own <strong>Messages</strong> menu (with an unread badge). Learners can
                    message the school and their own tutors; tutors can message the school and the learners in their classes.
                    Anything sent to a category appears in the same inbox.
                </p>

                <div class="info-box">
                    <strong>Filtering contacts:</strong> administrators can switch between <strong>Everyone</strong>,
                    <strong>Parents / Guardians</strong>, <strong>Tutors</strong> and <strong>Students</strong> at the top of the inbox.
                    A badge shows how many broadcasts you have sent.
                </div>
            </section>
            @endif


            <!-- ========== DASHBOARD EXTRAS ========== -->
            <section id="dashboard-extras" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-sun text-orange-700 mr-2"></i>Dashboard extras
                </h2>

                @if($guideIsLearner)
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Now / Up next panel</h3>
                <p class="text-gray-600 mb-3">
                    The very top of the learner dashboard now highlights the class that matters right now:
                </p>
                <ul class="list-disc list-inside text-gray-600 space-y-1 mb-6">
                    <li><strong>Live now</strong> (orange panel) — a session has already started and has not ended yet. Click <strong>Join class</strong> to open the meeting link.</li>
                    <li><strong>Up next</strong> (blue panel) — no session is running, so the nearest future session is shown with how long until it starts.</li>
                    <li>If no meeting link has been added yet the panel says so, and the session still appears in <strong>Upcoming Sessions</strong> below.</li>
                </ul>
                @endif

                <h3 class="text-lg font-semibold text-gray-700 mb-3">Affirmation of the day</h3>
                <p class="text-gray-600 mb-3">
                    Every dashboard (administrator, tutor, learner and parent) carries a short encouragement card with the
                    date. The message changes automatically each day, so there is nothing to set up.
                </p>

                @if($guideIsAdmin)
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Monthly classes per tutor</h3>
                <ol class="space-y-2 mb-4">
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">1.</span><span class="text-gray-600">Open the <strong>Admin</strong> or <strong>CEO dashboard</strong>.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">2.</span><span class="text-gray-600">In the <strong>Classes per tutor</strong> card, pick a month and click <strong>View</strong>.</span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-blue-600 font-bold">3.</span><span class="text-gray-600">Each tutor gets a bar showing how many classes were <strong>completed</strong> out of the sessions scheduled that month; the strip below shows the last six months of completed classes.</span></li>
                </ol>
                <div class="tip-box">
                    The count is based on classes ticked as <strong>completed</strong>. Tick a past class in <strong>Schedules</strong> (or reopen a mistake) so the report matches what the tutor is paid for; scheduled-but-not-completed classes are shown for context only.
                </div>
                @endif

                <h3 class="text-lg font-semibold text-gray-700 mb-3 mt-6">Colours</h3>
                <p class="text-gray-600">
                    The platform uses the school colours: <strong>deep navy and royal blue</strong> for structure and main
                    actions, <strong>white</strong> for cards and content, <strong class="text-orange-700">orange</strong> for
                    actions, warnings and attention, and <strong class="text-sun-ink">sun-yellow</strong> for highlights and
                    today's classes. <strong>Red is not used anywhere in the interface.</strong>
                </p>
            </section>


            <!-- ========== FAQ & TROUBLESHOOTING ========== -->
            <section id="faq" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-question-circle text-blue-600 mr-2"></i>FAQ & Troubleshooting
                </h2>

                <div class="space-y-6">
                    @if($guideIsAdmin || $guideIsParent)
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ A parent says they cannot see a learning goal.</h3>
                        <p class="text-gray-600 text-sm">Goals only reach people once an administrator or the CEO ticks <strong>Parent</strong>, <strong>Learner</strong> and/or <strong>Tutor</strong> in the <strong>Share with</strong> column on the Learning Goals screen (and clicks <strong>Save</strong>). Unticking a box withdraws the goal again — learners lose it from their dashboard and My Learning page immediately.</p>
                    </div>
                    @endif

                    @if($guideIsParent || $guideIsCeo)
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ I paid but the fee still shows a balance.</h3>
                        <p class="text-gray-600 text-sm">Only <strong>verified</strong> installments count. A parent submission arrives as <strong>Pending verification</strong>: the CEO has to check the bank statement (transfer) or the cash book (cash) and click <strong>Payment received</strong> on <strong>Finance → Payments</strong> before the balance drops. Also make sure the new installment uses exactly the same child, term and class as the fee you are paying.</p>
                    </div>
                    @endif

                    @if($guideIsParent || $guideIsCeo)
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ Where did the "Record Payment" button go?</h3>
                        <p class="text-gray-600 text-sm">It moved to the parent area. Parents open <strong>Finance → Fees &amp; Payments → Record a payment</strong>, because one parent paying for two or three children is quicker than the CEO entering every family. The CEO keeps the verification side: <strong>Finance → Payments → Payment received</strong> (or <strong>Reject</strong> with a reason). Before parents can transfer, add the school's accounts under <strong>Finance → Bank accounts</strong>.</p>
                    </div>
                    @endif

                    @if($guideIsCeo)
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ Where do I add the bank account parents should pay into?</h3>
                        <p class="text-gray-600 text-sm">As the CEO, open <strong>Finance → Bank accounts</strong> and use <strong>Add an account</strong> (bank, account name, number and an optional note for parents). Active accounts appear on the parent payment form with a copy button. <strong>Switch off</strong> hides an account without losing its history; <strong>Delete</strong> removes it, but payments already recorded keep the name and number they were saved with. Administrators cannot open this screen.</p>
                    </div>
                    @endif

                    @if($guideIsParent || $guideIsCeo)
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ Can parents pay by card?</h3>
                        <p class="text-gray-600 text-sm">Not yet — the <strong>Card</strong> option shows as <em>coming soon</em> and is powered by Paystack. Transfers and cash work today. Once the school's Paystack keys (<code>PAYSTACK_PUBLIC_KEY</code>, <code>PAYSTACK_SECRET_KEY</code>) are added, the card flow can be switched on; the bank-accounts screen shows whether Paystack is connected.</p>
                    </div>
                    @endif

                    @if($guideIsParent || $guideIsCeo)
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ A parent says their payment is marked rejected.</h3>
                        <p class="text-gray-600 text-sm">The CEO could not match it with the bank statement (or the cash book for a cash payment). The reason is shown next to the record on the parent's <strong>Fees &amp; Payments</strong> screen and in their notifications. The parent should check the amount, date, account and reference (or upload a clearer receipt) and submit the payment again.</p>
                    </div>
                    @endif

                    @if($guideIsTutor || $guideIsAdmin)
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ A tutor cannot see the Goals or Messages menu.</h3>
                        <p class="text-gray-600 text-sm">Those menus need an active tutor profile. If the account was created without one, ask an administrator to update the user's role on the Users screen — the tutor profile is created automatically.</p>
                    </div>
                    @endif
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ I forgot my password. What should I do?</h3>
                        <p class="text-gray-600 text-sm">Click <strong>Forgot your password?</strong> on the sign-in page, enter your <strong>email or username</strong>, and follow the one-time link we send (valid for 60 minutes). Choose a new password and you are signed in straight away. For a child with no email, the link is emailed to their parent/guardian instead — a parent can also send it from the <strong>Children &amp; logins</strong> panel on their dashboard. If no email arrives after a few minutes, check your spam folder, then ask the school administrator to generate a reset link for you from <strong>Admin → Users</strong>.</p>
                    </div>

                    @if($guideIsAdmin)
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ How do I reset another user's password?</h3>
                        <p class="text-gray-600 text-sm">Open <strong>Admin → Users</strong> and click <strong>Reset password</strong> on the user's row. When email is configured they receive the usual one-time link. If email is not configured (or the mail server is down), the page shows a one-time link you can copy and pass on privately — it expires in 60 minutes and works once. Inactive accounts must be activated first, and only the CEO can reset a CEO account.</p>
                    </div>
                    @endif

                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ I registered but can't log in.</h3>
                        <p class="text-gray-600 text-sm">Your account may not have been activated yet. Please contact an <strong>Administrator</strong> to verify and activate your account. Make sure you're using the correct email or username and password. Accounts created by the school sign in with the username on their welcome email.</p>
                    </div>

                    @if($guideIsParent)
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ I'm a parent and I haven't received my login details.</h3>
                        <p class="text-gray-600 text-sm">Parent accounts are created automatically when a Learner registers with parent information, or by the school from <strong>Admin → Users</strong> (and <strong>Add child</strong> on a parent's page). Login details are sent to the parent's email. The <strong>Children &amp; logins</strong> panel on your dashboard shows every child's username and lets you send a one-time reset link to your own address. Check your spam/junk folder first; if you still haven't received anything, contact an <strong>Administrator</strong>.</p>
                    </div>
                    @endif

                    @if($guideIsLearner)
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ How do I join a virtual class?</h3>
                        <p class="text-gray-600 text-sm">Go to your Dashboard or My Classes page, find the upcoming session with a <strong>"Join"</strong> or <strong>"Join Class"</strong> button, and click it. The meeting link will open in a new browser tab. Make sure you have the necessary software (Zoom, Google Meet, etc.) installed if required.</p>
                    </div>

                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ I can't submit my assignment.</h3>
                        <p class="text-gray-600 text-sm">Make sure you're uploading a supported file format (PDF, DOC, DOCX, images, etc.). Check that the file isn't too large. If the due date has passed, the assignment may still accept submissions — contact your tutor if you have issues.</p>
                    </div>
                    @endif

                    @if($guideIsParent)
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ How do I see my child's grades?</h3>
                        <p class="text-gray-600 text-sm">Go to your Dashboard and click <strong>"View Full Details"</strong> on your child's card (or tap the <strong>Assignments Completed</strong> counter). The <strong>Completed Assignments</strong> and <strong>Attendance</strong> tabs show scores, feedback and attendance history.</p>
                    </div>
                    @endif

                    @if($guideIsTutor)
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ I'm a tutor and I can't see my classes.</h3>
                        <p class="text-gray-600 text-sm">Classes need to be assigned to you by an <strong>Administrator</strong>. Please contact the admin to ensure you've been assigned to the appropriate classes.</p>
                    </div>
                    @endif

                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ The page looks strange or isn't working properly.</h3>
                        <p class="text-gray-600 text-sm">Try refreshing the page (F5 or Ctrl+R). Clear your browser cache if issues persist. Make sure you're using a modern browser (Chrome, Firefox, Edge, or Safari). If the problem continues, contact support.</p>
                    </div>

                    @if($guideUser->isAdmin())
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ I'm an administrator but I can't see the Payments menu.</h3>
                        <p class="text-gray-600 text-sm">Payments are restricted to the <strong>CEO</strong> account only. Administrators do not see the Payments link, and opening the payment pages directly returns a <strong>"403 Forbidden"</strong> error. If you believe you should have this access, ask the CEO about upgrading your role.</p>
                    </div>
                    @endif

                    @if($guideIsAdmin)
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ Can an administrator grade assignments, or only the tutor?</h3>
                        <p class="text-gray-600 text-sm">Both can. Tutors grade their own assignments, and <strong>Administrators / the CEO</strong> can grade any submission from <strong>Academics → Assignment Review → "Review &amp; Grade Submissions"</strong>. A tutor trying to open another tutor's assignment gets a "403 Forbidden" page.</p>
                    </div>
                    @endif

                    @if($guideIsParent)
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ How do I contact the school as a parent?</h3>
                        <p class="text-gray-600 text-sm">Click <strong>"Message the School"</strong> on your dashboard, or <strong>"Messages"</strong> in the menu, then choose the CEO or an administrator. Your conversation thread keeps the full history, and the school is notified instantly.</p>
                    </div>
                    @endif

                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ Who can I message?</h3>
                        <p class="text-gray-600 text-sm">
                            @if($guideIsParent)
                                You can message the <strong>CEO</strong> and every <strong>administrator</strong>. Replies stay in the same private thread.
                            @elseif($guideIsLearner)
                                You can message the school and <strong>your own tutors</strong>. Replies stay in the same private thread.
                            @elseif($guideIsTutor)
                                You can message the school and the <strong>learners in your classes</strong>. Replies stay in the same private thread.
                            @else
                                You can message parents, tutors, learners and the other administrators. Anyone outside your allowed contacts gets a "403 Forbidden" page.
                            @endif
                        </p>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-800 mb-2">❓ How do I log out?</h3>
                        <p class="text-gray-600 text-sm">Click the orange <strong>"Logout"</strong> button on the right of the blue top bar (there is also a logout icon at the bottom of the left menu). On mobile, tap the menu button <i class="fas fa-bars"></i> first, then use the logout icon at the bottom of the menu.</p>
                    </div>
                </div>

                <div class="mt-8 p-6 bg-blue-50 rounded-xl text-center">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">📞 Still Need Help?</h3>
                    <p class="text-gray-600">If you couldn't find an answer in this guide, please contact the system <strong>Administrator</strong> or the school's support team for further assistance.</p>
                </div>
            </section>

        </div>
    </div>
</div>
@endsection
