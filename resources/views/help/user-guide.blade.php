@extends('layouts.app')

@section('title', 'User Guide & Manual')

@section('content')
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
            <i class="fas fa-book-open text-indigo-600 mr-3"></i>User Guide & Manual
        </h1>
        <p class="text-lg text-gray-600 max-w-3xl mx-auto">
            Welcome to <strong>Excellent Scholars Hub (ESH)</strong> — your all-in-one Learning Management System. 
            This guide will walk you through everything you need to know, from getting started to mastering each feature.
        </p>
    </div>

    <div class="lg:grid lg:grid-cols-4 lg:gap-8">
        <!-- Table of Contents (Sidebar) -->
        <div class="lg:col-span-1">
            <div class="guide-toc bg-white rounded-xl shadow-md p-6 mb-8 lg:mb-0">
                <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-list mr-2 text-indigo-600"></i>Quick Navigation</h3>
                <nav class="space-y-2 text-sm">
                    <a href="#getting-started" class="block text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 py-2 rounded transition">
                        <i class="fas fa-flag mr-2"></i>Getting Started
                    </a>
                    <a href="#for-admins" class="block text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 py-2 rounded transition">
                        <i class="fas fa-user-shield mr-2"></i>For Administrators
                    </a>
                    <a href="#for-tutors" class="block text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 py-2 rounded transition">
                        <i class="fas fa-chalkboard-teacher mr-2"></i>For Tutors
                    </a>
                    <a href="#for-learners" class="block text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 py-2 rounded transition">
                        <i class="fas fa-user-graduate mr-2"></i>For Learners (Students)
                    </a>
                    <a href="#for-parents" class="block text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 py-2 rounded transition">
                        <i class="fas fa-users mr-2"></i>For Parents/Guardians
                    </a>
                    <a href="#notifications" class="block text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 py-2 rounded transition">
                        <i class="fas fa-bell mr-2"></i>Notifications
                    </a>
                    <a href="#faq" class="block text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 py-2 rounded transition">
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
                    <i class="fas fa-flag text-indigo-600 mr-2"></i>Getting Started
                </h2>

                <h3 class="text-lg font-semibold text-gray-700 mb-3">What is Excellent Scholars Hub?</h3>
                <p class="text-gray-600 mb-6">
                    Excellent Scholars Hub (ESH) is a web-based Learning Management System (LMS) designed to connect 
                    <strong>Administrators</strong>, <strong>Tutors</strong>, <strong>Learners (Students)</strong>, and 
                    <strong>Parents/Guardians</strong> in one seamless platform. It enables class management, assignment 
                    submissions, attendance tracking, learning material sharing, payment recording, and progress monitoring.
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
                            <p class="font-medium text-gray-800">Fill in Your Details</p>
                            <p class="text-sm text-gray-600">Enter your <strong>Full Name</strong>, <strong>Email</strong>, <strong>Phone</strong> (optional), and select your <strong>Role</strong> (Tutor, Learner, or Parent).</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="step-number mt-0.5">3</span>
                        <div>
                            <p class="font-medium text-gray-800">Parent/Guardian Info (for Learners only)</p>
                            <p class="text-sm text-gray-600">If you register as a <strong>Learner</strong>, you'll need to provide a parent/guardian's name and email. A parent account will be created automatically and login details sent to their email.</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="step-number mt-0.5">4</span>
                        <div>
                            <p class="font-medium text-gray-800">Create Password & Submit</p>
                            <p class="text-sm text-gray-600">Create a password (minimum 8 characters), confirm it, and click <strong>"Create Account"</strong>.</p>
                        </div>
                    </li>
                </ol>

                <div class="tip-box">
                    <p class="text-sm text-gray-700"><strong>💡 Tip:</strong> If you are an <strong>Administrator</strong>, your account will be created by the system owner directly — you won't register via the public form.</p>
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
                            <p class="text-sm text-gray-600">Enter your <strong>Email</strong> and <strong>Password</strong>.</p>
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
                    After logging in, you'll see a navigation bar at the top with links to different sections based on your role. 
                    The main menu includes your <strong>Dashboard</strong> (home page), along with role-specific sections like 
                    Classes, Assignments, Materials, etc. You can also access <strong>Notifications</strong> and use the 
                    <strong>Logout</strong> button to sign out. On mobile devices, use the hamburger menu (☰) to access navigation links.
                </p>
            </section>

            <!-- ========== FOR ADMINISTRATORS ========== -->
            <section id="for-admins" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-user-shield text-red-500 mr-2"></i>For Administrators
                </h2>
                <p class="text-gray-600 mb-6">
                    As an <strong>Administrator</strong>, you have full control over the platform. You can manage users, 
                    classes, schedules, payments, and send notifications. Your dashboard gives you an overview of key 
                    platform statistics.
                </p>
                <div class="info-box mb-6">
                    <p class="text-sm"><strong><i class="fas fa-map-signs mr-1"></i> Admin Navigation Tip:</strong></p>
                    <p class="text-sm mt-1">The admin menu uses <strong>hover dropdowns</strong> to keep the bar compact:</p>
                    <ul class="list-disc list-inside text-sm mt-2 space-y-1">
                        <li><strong>Academics ▼</strong> — Hover to access <em>Classes</em>, <em>Schedules</em>, and <em>Payments</em></li>
                        <li><strong>Oversight ▼</strong> — Hover to access <em>Attendance</em>, <em>Assignments</em>, and <em>Materials</em></li>
                    </ul>
                    <p class="text-sm mt-2">Standalone links: <strong>Dashboard</strong>, <strong>Users</strong>, <strong>Notifications</strong></p>
                </div>

                <!-- Admin Dashboard -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-tachometer-alt text-indigo-600 mr-2"></i>1. Admin Dashboard
                    </h3>
                    <p class="text-gray-600 mb-2">Your dashboard displays:</p>
                    <ul class="list-disc list-inside text-gray-600 space-y-1 mb-3">
                        <li>Total Users, Tutors, Learners, Classes, Schedules, and Payments</li>
                        <li>Recent Users (newest registrations)</li>
                        <li>Upcoming Classes (next scheduled sessions)</li>
                    </ul>
                    <p class="text-sm text-gray-500">Navigate to: <strong>Dashboard</strong> in the top menu</p>
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
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Click <strong>"Users"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">Click the <strong>"New User"</strong> button.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">Fill in the user details (name, email, role, password).</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">4.</span>
                            <span class="text-gray-600">Click <strong>"Create User"</strong> to save.</span>
                        </li>
                    </ol>

                    <p class="font-medium text-gray-700 mb-2">To edit or delete a user:</p>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        <li>Click the <strong>Edit</strong> (pencil) icon next to a user to modify their details.</li>
                        <li>Click the <strong>Delete</strong> (trash) icon to remove a user (requires confirmation).</li>
                    </ul>
                </div>

                <!-- Manage Classes -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-chalkboard text-yellow-600 mr-2"></i>3. Managing Classes
                    </h3>
                    <p class="text-gray-600 mb-3">Create and manage the classes (subjects/courses) on the platform.</p>

                    <p class="font-medium text-gray-700 mb-2">To create a class:</p>
                    <ol class="space-y-2 mb-3">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"Classes"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">Click <strong>"New Class"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">Fill in: <strong>Name</strong>, <strong>Subject</strong>, <strong>Grade Level</strong>, assign a <strong>Tutor</strong>, set <strong>Max Students</strong>, and add a <strong>Description</strong> (optional).</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">4.</span>
                            <span class="text-gray-600">Click <strong>"Create Class"</strong>.</span>
                        </li>
                    </ol>

                    <div class="tip-box">
                        <p class="text-sm text-gray-700"><strong>💡 Tip:</strong> Each class is automatically assigned a unique <strong>Class Code</strong> that learners can use for enrollment.</p>
                    </div>

                    <p class="font-medium text-gray-700 mb-2">Enrolling Learners in a Class:</p>
                    <ol class="space-y-2 mb-3">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Click the <strong>Users icon</strong> (👥) next to the class on the Classes list.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">On the class detail page, select a <strong>Learner</strong> from the dropdown and click <strong>"Enroll"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">To remove a learner, click <strong>"Unenroll"</strong> next to their name.</span>
                        </li>
                    </ol>
                </div>

                <!-- Manage Schedules -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-calendar text-purple-600 mr-2"></i>4. Managing Schedules
                    </h3>
                    <p class="text-gray-600 mb-3">Schedule class sessions with dates, times, and optional meeting links.</p>
                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"Schedules"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">Click <strong>"New Schedule"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">Fill in: <strong>Title</strong>, select the <strong>Class</strong> and <strong>Tutor</strong>, set <strong>Start/End Time</strong>, and optionally add a <strong>Meeting Link</strong> (e.g., Zoom/Google Meet URL).</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">4.</span>
                            <span class="text-gray-600">Click <strong>"Create Schedule"</strong>.</span>
                        </li>
                    </ol>
                </div>

                <!-- Manage Payments -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-money-bill text-pink-600 mr-2"></i>5. Managing Payments
                    </h3>
                    <p class="text-gray-600 mb-3">Record and track payments from learners/parents.</p>
                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"Payments"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">Click <strong>"Record Payment"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">Select the <strong>User</strong> (who made the payment), choose the <strong>Learner</strong> (if applicable), set <strong>Payment Type</strong> (tuition, registration, etc.), <strong>Amount</strong>, <strong>Method</strong>, and <strong>Status</strong>.</span>
                        </li>
                    </ol>
                </div>

                <!-- Send Notifications -->
                <div class="guide-card bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-bell text-green-600 mr-2"></i>6. Sending Notifications
                    </h3>
                    <p class="text-gray-600 mb-3">Send announcements to specific users or broadcast to everyone.</p>
                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to the <strong>Notifications</strong> section (Admin menu).</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">Click <strong>"Send Notification"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">Enter a <strong>Title</strong>, <strong>Message</strong>, select the <strong>Type</strong> (announcement, reminder, alert, etc.), and optionally choose a specific <strong>Recipient</strong> (leave blank to send to all users).</span>
                        </li>
                    </ol>
                </div>

                <!-- Admin Oversight -->
                <div class="guide-card bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-eye text-indigo-600 mr-2"></i>7. Admin Oversight — Monitoring Activities
                    </h3>
                    <p class="text-gray-600 mb-3">
                        As an Administrator, you have full visibility into what's happening across all classes. You can 
                        monitor <strong>Attendance</strong>, <strong>Assignments & Submissions</strong>, and 
                        <strong>Learning Materials</strong> for every class — giving you complete oversight over tutors and learners.
                    </p>

                    <h4 class="font-medium text-gray-700 mb-2">
                        <i class="fas fa-clipboard-check text-blue-500 mr-1"></i> Viewing Attendance Records
                    </h4>
                    <ol class="space-y-2 mb-4">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Click <strong>"Attendance"</strong> in the admin navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">See a complete table of attendance records — <strong>Learner name</strong>, <strong>Class</strong>, <strong>Date/Time</strong>, <strong>Status</strong> (Present, Absent, Late, Excused), and <strong>Notes</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">Use the <strong>"Filter by Class"</strong> dropdown to view attendance for a specific class.</span>
                        </li>
                    </ol>

                    <h4 class="font-medium text-gray-700 mb-2">
                        <i class="fas fa-tasks text-orange-500 mr-1"></i> Viewing Assignments & Submissions
                    </h4>
                    <ol class="space-y-2 mb-4">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Click <strong>"Assignments"</strong> in the admin navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">View a list of all assignments with their <strong>Title</strong>, <strong>Class</strong>, <strong>Tutor</strong>, <strong>Due Date</strong>, and <strong>Status</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">Each assignment card expands to show all <strong>learner submissions</strong> — who submitted, when, their <strong>Score</strong>, and whether it's been <strong>Graded</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">4.</span>
                            <span class="text-gray-600">Use the <strong>"Filter by Class"</strong> dropdown to focus on a particular class.</span>
                        </li>
                    </ol>

                    <h4 class="font-medium text-gray-700 mb-2">
                        <i class="fas fa-folder text-purple-500 mr-1"></i> Viewing Learning Materials
                    </h4>
                    <ol class="space-y-2 mb-3">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Click <strong>"Materials"</strong> in the admin navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">Browse all uploaded materials across classes — <strong>Title</strong>, <strong>Class</strong>, <strong>Uploaded By</strong> (tutor name), <strong>File Type</strong>, and <strong>Date</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">Click <strong>"Download"</strong> to view any file directly.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">4.</span>
                            <span class="text-gray-600">Use the <strong>"Filter by Class"</strong> dropdown to see materials for a specific class.</span>
                        </li>
                    </ol>

                    <div class="info-box">
                        <p class="text-sm text-gray-700"><strong>📌 Note:</strong> These oversight pages are <strong>read-only</strong>. They allow you to monitor activity across the platform without interfering with tutors' work.</p>
                    </div>
                </div>
            </section>

            <!-- ========== FOR TUTORS ========== -->
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
                        <i class="fas fa-tachometer-alt text-indigo-600 mr-2"></i>1. Tutor Dashboard
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

                <!-- Schedules -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-calendar text-purple-600 mr-2"></i>2. Managing Schedules
                    </h3>
                    <p class="text-gray-600 mb-3">Create and manage your class sessions.</p>
                    <ol class="space-y-2 mb-3">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"Schedules"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">Click <strong>"New Schedule"</strong> to create a new class session.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">Choose the <strong>Class</strong>, set the <strong>Title</strong>, <strong>Start/End Time</strong>, and optionally add a <strong>Meeting Link</strong> (for virtual classes).</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">4.</span>
                            <span class="text-gray-600">After a session is done, click <strong>"Mark as Completed"</strong> to update its status.</span>
                        </li>
                    </ol>
                    <div class="tip-box">
                        <p class="text-sm text-gray-700"><strong>💡 Tip:</strong> You can add a Zoom, Google Meet, or Microsoft Teams link so learners can join the virtual class with one click.</p>
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
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">On your dashboard or schedules page, click the <strong>"Attendance"</strong> button next to a scheduled session.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">For each enrolled learner, select their attendance status:</span>
                        </li>
                    </ol>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1 mb-3 ml-4">
                        <li><span class="text-green-600 font-medium">Present</span> — Learner attended the session</li>
                        <li><span class="text-red-600 font-medium">Absent</span> — Learner was absent</li>
                        <li><span class="text-yellow-600 font-medium">Late</span> — Learner arrived late</li>
                        <li><span class="text-blue-600 font-medium">Excused</span> — Absence was pre-approved/excused</li>
                    </ul>
                    <ol class="space-y-2" start="3">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">Optionally add <strong>Notes</strong> for each learner.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">4.</span>
                            <span class="text-gray-600">Click <strong>"Save Attendance"</strong> to submit.</span>
                        </li>
                    </ol>
                </div>

                <!-- Assignments -->
                <div class="guide-card bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-tasks text-orange-600 mr-2"></i>4. Creating & Grading Assignments
                    </h3>

                    <p class="font-medium text-gray-700 mb-2">To create an assignment:</p>
                    <ol class="space-y-2 mb-4">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"Assignments"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">Click <strong>"New Assignment"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">Fill in: <strong>Title</strong>, <strong>Description</strong>, select the <strong>Class</strong>, set <strong>Due Date</strong>, and <strong>Total Points</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">4.</span>
                            <span class="text-gray-600">Click <strong>"Create Assignment"</strong>.</span>
                        </li>
                    </ol>

                    <p class="font-medium text-gray-700 mb-2">To grade submissions:</p>
                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">On the Assignments list, click <strong>"View (number)"</strong> under Submissions to see all submitted work.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
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
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">Review the learner's uploaded file and notes, then enter a <strong>Score</strong> (out of total points) and optionally provide <strong>Feedback</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">4.</span>
                            <span class="text-gray-600">Click <strong>"Grade"</strong> to save the grade. The status will update from "submitted" to "graded".</span>
                        </li>
                    </ol>
                    <div class="info-box mt-3">
                        <p class="text-sm text-gray-700"><strong>📌 Note:</strong> After grading, the student's score and feedback are visible to both the learner and their parent/guardian on their respective dashboards.</p>
                    </div>
                </div>

                <!-- Materials -->
                <div class="guide-card bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-folder-open text-indigo-600 mr-2"></i>5. Uploading Learning Materials
                    </h3>
                    <p class="text-gray-600 mb-3">Share documents, presentations, and other resources with your learners.</p>
                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"Materials"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">Click <strong>"Upload Material"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">Select the <strong>Class</strong>, enter a <strong>Title</strong> and optional <strong>Description</strong>, then upload the <strong>File</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">4.</span>
                            <span class="text-gray-600">Click <strong>"Upload"</strong> to make it available to learners.</span>
                        </li>
                    </ol>
                    <div class="warning-box mt-4">
                        <p class="text-sm text-gray-700"><strong>⚠️ Note:</strong> Once uploaded, learners in the selected class can instantly view and download the material. You can also delete materials if needed.</p>
                    </div>
                </div>
            </section>

            <!-- ========== FOR LEARNERS ========== -->
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
                        <i class="fas fa-tachometer-alt text-indigo-600 mr-2"></i>1. Learner Dashboard
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
                        <i class="fas fa-chalkboard text-yellow-600 mr-2"></i>2. My Classes
                    </h3>
                    <p class="text-gray-600 mb-3">View your enrolled classes and their schedules.</p>
                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"My Classes"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">You'll see each class with its subject, grade level, and description.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
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
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"Assignments"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">You'll see a list of assignments. Each card shows the <strong>Title</strong>, <strong>Class</strong>, <strong>Due Date</strong>, and <strong>Points</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">For unsubmitted assignments, click <strong>"Submit Assignment"</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">4.</span>
                            <span class="text-gray-600">Upload your <strong>file</strong> and optionally add <strong>Notes</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">5.</span>
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
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to <strong>"Materials"</strong> in the navigation menu.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">Browse through the available materials. Each entry shows the <strong>Title</strong>, <strong>File Type</strong> (PDF, DOC, etc.), <strong>Class</strong>, <strong>Description</strong>, <strong>File Size</strong>, and upload date.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">Click the <strong>"Download"</strong> button to open or save the file.</span>
                        </li>
                    </ol>
                </div>

                <!-- Join Virtual Class -->
                <div class="guide-card bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-video text-red-600 mr-2"></i>5. Joining a Virtual Class
                    </h3>
                    <p class="text-gray-600 mb-3">When a tutor schedules a class with a meeting link:</p>
                    <ol class="space-y-2">
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">1.</span>
                            <span class="text-gray-600">Go to your <strong>Dashboard</strong> or <strong>My Classes</strong> page.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">2.</span>
                            <span class="text-gray-600">Find the upcoming session with a green <strong>"Join"</strong> button.</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm">
                            <span class="text-indigo-600 font-bold">3.</span>
                            <span class="text-gray-600">Click <strong>"Join"</strong> or <strong>"Join Class"</strong> — the meeting link will open in a new tab.</span>
                        </li>
                    </ol>
                    <div class="info-box mt-4">
                        <p class="text-sm text-gray-700"><strong>📌 Note:</strong> Sessions that haven't started yet will show as "Scheduled", and completed ones will show as "Completed". The Join button is only available for scheduled sessions with a meeting link.</p>
                    </div>
                </div>
            </section>

            <!-- ========== FOR PARENTS ========== -->
            <section id="for-parents" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-users text-yellow-600 mr-2"></i>For Parents / Guardians
                </h2>
                <p class="text-gray-600 mb-6">
                    As a <strong>Parent or Guardian</strong>, you can monitor your child's academic progress, view their 
                    attendance records, check assignment scores, and see upcoming class schedules — all from your dashboard.
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
                        <i class="fas fa-tachometer-alt text-indigo-600 mr-2"></i>1. Parent Dashboard
                    </h3>
                    <p class="text-gray-600 mb-3">
                        When you log in, your dashboard displays cards for each child linked to your account. 
                        Each card includes:
                    </p>
                    <ul class="list-disc list-inside text-gray-600 space-y-1 mb-3">
                        <li>Your child's <strong>Name</strong> and <strong>Grade Level</strong></li>
                        <li><strong>Email</strong> and <strong>Phone</strong> contact details</li>
                        <li>Number of <strong>Enrolled Classes</strong></li>
                        <li>A <strong>"View Progress"</strong> button to dive deeper</li>
                    </ul>
                </div>

                <!-- View Child Progress -->
                <div class="guide-card bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                        <i class="fas fa-chart-line text-green-600 mr-2"></i>2. Viewing Your Child's Progress
                    </h3>
                    <p class="text-gray-600 mb-3">
                        Click <strong>"View Progress"</strong> on any child's card to see a detailed report containing:
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="bg-white border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-clipboard-check text-blue-500 mr-2"></i>Attendance
                            </h4>
                            <p class="text-sm text-gray-600">A table showing each class date, the class name, and the attendance status:
                                <span class="text-green-600">Present</span>, 
                                <span class="text-yellow-600">Late</span>, 
                                <span class="text-blue-600">Excused</span>, or 
                                <span class="text-red-600">Absent</span>.
                            </p>
                        </div>
                        <div class="bg-white border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-tasks text-green-500 mr-2"></i>Assignments
                            </h4>
                            <p class="text-sm text-gray-600">A complete table of all assignments with: Title, Class, Due Date, 
                                Submission Status (Submitted ✅ / Missing ❌), Score, Status (Graded/Pending), and 
                                Teacher's Feedback.</p>
                        </div>
                        <div class="bg-white border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-calendar text-purple-500 mr-2"></i>Upcoming Classes
                            </h4>
                            <p class="text-sm text-gray-600">A list of upcoming scheduled sessions for your child, with the 
                                session title, class name, date, and time.</p>
                        </div>
                    </div>

                    <div class="tip-box">
                        <p class="text-sm text-gray-700"><strong>💡 Tip:</strong> If you have multiple children, you can view progress for each one individually by clicking their respective "View Progress" button from the dashboard.</p>
                    </div>
                </div>
            </section>

            <!-- ========== NOTIFICATIONS ========== -->
            <section id="notifications" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-bell text-indigo-600 mr-2"></i>Notifications
                </h2>
                <p class="text-gray-600 mb-4">
                    The notification system keeps everyone informed. Administrators can send announcements, reminders, 
                    and alerts. All users receive and manage their notifications in one place.
                </p>

                <h3 class="text-lg font-semibold text-gray-700 mb-3">How to Access Notifications</h3>
                <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                    <li>Click the <strong>Notifications</strong> link in the navigation menu (available to all logged-in users).</li>
                    <li>Administrators can also access the <strong>Notifications</strong> section under the admin menu to send notifications.</li>
                </ul>

                <h3 class="text-lg font-semibold text-gray-700 mb-3">Managing Your Notifications</h3>
                <ul class="list-disc list-inside text-gray-600 space-y-2">
                    <li><strong>Unread notifications</strong> are highlighted with a colored left border</li>
                    <li>Click <strong>"Mark read"</strong> on individual notifications to dismiss them</li>
                    <li>Use <strong>"Mark all as read"</strong> to clear all notifications at once</li>
                </ul>
            </section>

            <!-- ========== FAQ & TROUBLESHOOTING ========== -->
            <section id="faq" class="guide-section bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-question-circle text-indigo-600 mr-2"></i>FAQ & Troubleshooting
                </h2>

                <div class="space-y-6">
                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ I forgot my password. What should I do?</h3>
                        <p class="text-gray-600 text-sm">Please contact the system <strong>Administrator</strong> to have your password reset. Password reset via email is currently managed manually.</p>
                    </div>

                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ I registered but can't log in.</h3>
                        <p class="text-gray-600 text-sm">Your account may not have been activated yet. Please contact an <strong>Administrator</strong> to verify and activate your account. Make sure you're using the correct email and password.</p>
                    </div>

                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ I'm a parent and I haven't received my login details.</h3>
                        <p class="text-gray-600 text-sm">Parent accounts are created automatically when a Learner registers with parent information. Login details are sent to the parent's email. Check your spam/junk folder first. If you still haven't received it, contact an <strong>Administrator</strong>.</p>
                    </div>

                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ How do I join a virtual class?</h3>
                        <p class="text-gray-600 text-sm">Log in as a <strong>Learner</strong>, go to your Dashboard or My Classes page, find the upcoming session with a <strong>"Join"</strong> or <strong>"Join Class"</strong> button, and click it. The meeting link will open in a new browser tab. Make sure you have the necessary software (Zoom, Google Meet, etc.) installed if required.</p>
                    </div>

                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ I can't submit my assignment.</h3>
                        <p class="text-gray-600 text-sm">Make sure you're uploading a supported file format (PDF, DOC, DOCX, images, etc.). Check that the file isn't too large. If the due date has passed, the assignment may still accept submissions — contact your tutor if you have issues.</p>
                    </div>

                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ How do I see my child's grades?</h3>
                        <p class="text-gray-600 text-sm">Log in as a <strong>Parent</strong>, go to your Dashboard, and click <strong>"View Progress"</strong> on your child's card. You'll see a detailed breakdown of attendance, assignments, scores, and feedback.</p>
                    </div>

                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ I'm a tutor and I can't see my classes.</h3>
                        <p class="text-gray-600 text-sm">Classes need to be assigned to you by an <strong>Administrator</strong>. Please contact the admin to ensure you've been assigned to the appropriate classes.</p>
                    </div>

                    <div class="border-b pb-4">
                        <h3 class="font-semibold text-gray-800 mb-2">❓ The page looks strange or isn't working properly.</h3>
                        <p class="text-gray-600 text-sm">Try refreshing the page (F5 or Ctrl+R). Clear your browser cache if issues persist. Make sure you're using a modern browser (Chrome, Firefox, Edge, or Safari). If the problem continues, contact support.</p>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-800 mb-2">❓ How do I log out?</h3>
                        <p class="text-gray-600 text-sm">Click the <strong>"Logout"</strong> button at the top-right corner of any page (in the navigation bar). On mobile, tap the hamburger menu (☰) first, then click Logout.</p>
                    </div>
                </div>

                <div class="mt-8 p-6 bg-indigo-50 rounded-xl text-center">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">📞 Still Need Help?</h3>
                    <p class="text-gray-600">If you couldn't find an answer in this guide, please contact the system <strong>Administrator</strong> or the school's support team for further assistance.</p>
                </div>
            </section>

        </div>
    </div>
</div>
@endsection
