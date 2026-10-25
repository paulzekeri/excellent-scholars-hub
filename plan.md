# ESH LMS - Implementation Plan

> Status: **delivered**. This checklist reflects the shipped system; the post-delivery
> enhancement phase at the bottom covers the CEO / oversight / messaging changes.

## Phase 1: Foundation Setup
- [x] Laravel 8 installed
- [x] Tailwind CSS via Laravel Mix (no Livewire dependency)
- [x] `.env` with database settings
- [x] MySQL database

## Phase 2: Database Schema (Migrations)
- [x] Roles table (custom `roles` table, no spatie dependency)
- [x] Users table with `role_id` + `is_active`
- [x] Learners, parents, tutors tables
- [x] Classes, class_schedules tables
- [x] Attendance table
- [x] Assignments, submissions tables
- [x] Learning_materials table
- [x] Payments table
- [x] Notifications table
- [x] Activity_logs table
- [x] `ceo` role migration (`2023_01_01_000014_add_ceo_role_to_roles_table`)
- [x] `messages` table (`2023_01_01_000015_create_messages_table`)

## Phase 3: Models & Relationships
- [x] User model with role helpers (`isCeo`, `isAdmin`, `isAdministrator`, `isTutor`, `isParent`, `isLearner`, `canManagePayments`, `unreadMessagesCount`)
- [x] Learner, ParentModel, Tutor models
- [x] Classes, ClassSchedule models
- [x] Attendance model
- [x] Assignment & Submission models
- [x] LearningMaterial model
- [x] Payment model
- [x] Notification model
- [x] Message model with `between()` scope
- [x] ActivityLog model

## Phase 4: Authentication & Middleware
- [x] Custom login/registration with roles
- [x] Role-based middleware (`CheckRole`) — supports comma-separated roles (`role:admin,ceo`)
- [x] Password reset functionality
- [x] Login redirect via `isAdministrator()` so the CEO lands on the admin dashboard

## Phase 5: Dashboard Module
- [x] Admin dashboard with stats (payments card and title adapt for the CEO)
- [x] Tutor dashboard
- [x] Parent dashboard (per-child stats, clickable detail tabs, missed-class alerts, "Message the School" + unread badge)
- [x] Learner dashboard

## Phase 6: Core CRUD Modules
- [x] User management (CRUD; `ceo` role hidden/locked for non-CEO admins)
- [x] Class management
- [x] Schedule management
- [x] Attendance tracking
- [x] Assignments management
- [x] Learning materials management
- [x] Payment management (CEO only)
- [x] Notification management
- [x] Messaging (parents ↔ CEO/administrators)

## Phase 7: Live Classroom
- [x] Schedule meetings with Google Meet/Zoom links
- [x] Join class with one click

## Phase 8: Frontend UI
- [x] Tailwind CSS layouts
- [x] Responsive mobile-first design
- [x] Navigation menus (hover dropdowns on desktop, accordion on mobile, unread badges)
- [x] Dashboard components
- [x] In-app user guide (`/help/user-guide`)

## Phase 9: Client-Requested Enhancements
- [x] **CEO-only payments** — `ceo` role, `role:ceo` route group, controller-level gate, hidden nav link + dashboard card
- [x] **Admin/teacher submission review** — oversight `submissions` / `gradeSubmission` / `downloadSubmission`, tutor ownership guard
- [x] **Parent ↔ CEO/admin in-app messaging** — `messages` table/model/controller/views, notifications on send, unread badges
- [x] `DatabaseSeeder` made idempotent (`firstOrCreate`) so existing installs create the dedicated CEO account with `php artisan db:seed`
- [x] **Admin learner ↔ parent linking** — optional `parent_id` on the learner create/edit screens (`exists:parents,id`), learner profile auto-created on role change, linked parent shown in the Users table and linked learners listed on the parent's edit page
- [x] **Parent dashboard improvements** — per-child counters (schedule / pending / completed / materials) that open tabbed details, attendance rate, missed-class alerts, unrecorded-session notice, child-ownership guard and feature tests
- [x] Documentation updated (`README.md`, in-app user guide)

