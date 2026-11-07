# EXCELLENT SCHOLARS HUB (ESH)
## Learning Management System — Client Quotation & Charge Breakdown

**Prepared For:** [Client Name]
**Prepared By:** [Your Name/Company]
**Date:** May 19, 2026
**Project:** Excellent Scholars Hub — Web-Based LMS
**Status:** ✅ **Completed & Ready for Deployment**

---

## 📋 Executive Summary

Excellent Scholars Hub (ESH) is a fully-functional, web-based Learning Management System (LMS) built on **Laravel 13** (PHP 8.4) with **MySQL** database and **Tailwind CSS** frontend. The platform serves **four user roles** — Administrators, Tutors, Learners (Students), and Parents/Guardians — with role-specific dashboards and capabilities.

---

## 💰 Total Project Cost

| # | Module / Component | Amount (₦) |
|---|-------------------|-----------:|
| 1 | System Architecture & Database Design | ₦150,000 |
| 2 | Authentication & Role-Based Access | ₦120,000 |
| 3 | Admin Dashboard & Management Suite | ₦350,000 |
| 4 | Tutor Module (Schedules, Attendance, Assignments, Materials) | ₦300,000 |
| 5 | Learner Module (Classes, Assignments, Materials, Virtual Class) | ₦250,000 |
| 6 | Parent Monitoring Module | ₦150,000 |
| 7 | Admin Oversight Module (Cross-Class Monitoring) | ₦180,000 |
| 8 | Payment Recording System | ₦100,000 |
| 9 | Notification System | ₦100,000 |
| 10 | Frontend UI/UX (Tailwind CSS, Responsive Design) | ₦200,000 |
| 11 | User Guide & Documentation | ₦80,000 |
| 12 | Email Integration (Parent Welcome Mail) | ₦70,000 |
| 13 | Deployment & Server Configuration | ₦150,000 |
| | **TOTAL** | **₦2,200,000** |

---

## 🔍 Detailed Module Breakdown

### 1. System Architecture & Database Design — ₦150,000

| Task | Description |
|------|------------|
| Database Schema Design | 13 database tables including roles, users, tutors, learners, parents, classes, schedules, attendance, assignments, submissions, materials, payments, notifications, activity logs |
| Model Relationships | Eloquent ORM relationships between all entities (belongsTo, hasMany, belongsToMany) |
| Migration Scripts | Version-controlled database migrations |
| Service Layer | App/Services structure for business logic |
| File Structure | Organized MVC architecture following Laravel best practices |

### 2. Authentication & Role-Based Access — ₦120,000

| Task | Description |
|------|------------|
| User Registration | Self-registration with role selection (Tutor, Learner, Parent) |
| Login System | Email/password authentication with "Remember Me" |
| Role Middleware | Custom `CheckRole` middleware enforcing role-based access at route level |
| Role-Based Redirection | Auto-redirect to correct dashboard after login based on role |
| 4 User Roles | Admin, Tutor, Learner, Parent — each with distinct permissions and access levels |

### 3. Admin Dashboard & Management Suite — ₦350,000

| Feature | Details |
|---------|---------|
| **Admin Dashboard** | Stats cards (Total Users, Tutors, Learners, Classes, Schedules, Payments), Recent Users table, Upcoming Classes widget |
| **User Management** | Full CRUD — Create, Read, Update, Delete users. Assign roles. Auto-creates profile records (Tutor, Learner, Parent) |
| **Class Management** | Full CRUD — Create classes with name, subject, grade level, tutor assignment, max students. Auto-generates class code |
| **Enrollment System** | Enroll/unenroll learners in classes with max capacity validation |
| **Schedule Management** | Full CRUD — Create class schedules with title, date/time, meeting links (Zoom/Google Meet) |
| **Payment Recording** | Record payments with type, amount, method, status, reference. Link to user and learner |

### 4. Tutor Module — ₦300,000

| Feature | Details |
|---------|---------|
| **Tutor Dashboard** | Overview of assigned classes, total assignments, materials count, upcoming schedules with quick actions |
| **Schedule Management** | Create class sessions, add meeting links, mark sessions as completed |
| **Attendance Tracking** | Take attendance per session with 4 statuses: Present, Absent, Late, Excused. Include notes |
| **Assignment Management** | Create assignments with title, description, due date, total points. View submissions |
| **Grading System** | Grade submissions with score and feedback. Download submitted files. Status tracking (submitted → graded) |
| **Material Upload** | Upload learning materials (files) for specific classes. Delete when needed |

### 5. Learner Module — ₦250,000

| Feature | Details |
|---------|---------|
| **Learner Dashboard** | Overview of enrolled classes, pending assignments count, upcoming sessions, recent materials |
| **My Classes** | View all enrolled classes with tutor info and upcoming schedules |
| **Virtual Class Join** | One-click "Join Class" button to launch Zoom/Google Meet links in new tab |
| **Assignment Submission** | View assignments, upload files with notes, track submission status and grades |
| **Learning Materials** | Browse and download materials shared by tutors (PDF, DOC, etc.) |

### 6. Parent Monitoring Module — ₦150,000

| Feature | Details |
|---------|---------|
| **Parent Dashboard** | View all linked children (students) with enrolled classes count |
| **Child Progress Report** | Detailed view per child showing: |
| | → Attendance records with status and dates |
| | → Assignment scores, submission status, grades, and tutor feedback |
| | → Upcoming class schedules |
| **Automatic Parent Account** | Parent account auto-created when Learner registers. Login credentials sent via email |

### 7. Admin Oversight Module (Cross-Class Monitoring) — ₦180,000

| Feature | Details |
|---------|---------|
| **Attendance Oversight** | View all attendance records across all classes. Filter by class |
| **Assignment Oversight** | View all assignments, submissions, and grades across all classes. Filter by class |
| **Materials Oversight** | Browse all uploaded learning materials across the platform. Filter by class |
| **Read-Only Access** | Monitor without interfering with tutors' work |

### 8. Payment Recording System — ₦100,000

| Feature | Details |
|---------|---------|
| Payment creation with user, learner, type, amount, method, reference |
| Status tracking (paid, pending, overdue, cancelled) |
| Payment history listing with pagination |

### 9. Notification System — ₦100,000

| Feature | Details |
|---------|---------|
| Admin can send notifications with title, message, type (information, reminder, alert) |
| Type-aware badges, colours and per-type filtering on the recipient page |
| Targeted or broadcast (all users) delivery |
| Unread count badge on bell icon |
| Mark individual or all notifications as read |
| Index page for viewing all notifications |

### 10. Frontend UI/UX (Tailwind CSS) — ₦200,000

| Feature | Details |
|---------|---------|
| Responsive mobile-first design with Tailwind CSS |
| Role-specific navigation menus with dropdowns |
| Interactive dashboard with stat cards and tables |
| Mobile hamburger menu for smaller screens |
| Consistent design language across all 40+ views |
| Font Awesome icons throughout |
| Color-coded status badges and role labels |

### 11. User Guide & Documentation — ₦80,000

| Feature | Details |
|---------|---------|
| Comprehensive in-app user guide (help.user-guide route) |
| Section for each role: Admin, Tutor, Learner, Parent |
| Step-by-step instructions with screenshots-ready formatting |
| FAQ & Troubleshooting section |
| Quick navigation table of contents |

### 12. Email Integration — ₦70,000

| Feature | Details |
|---------|---------|
| ParentWelcomeMail — automated email to parents when Learner registers |
| Custom email template with login credentials |

### 13. Deployment & Server Configuration — ₦150,000

| Task | Details |
|------|---------|
| Server setup and configuration |
| Domain and SSL certificate setup |
| Database migration and seeding |
| Environment configuration |
| File storage configuration for uploads |
| Testing and quality assurance |

---

## 📊 Technology Stack

| Component | Technology |
|-----------|-----------|
| Backend Framework | Laravel 13 (PHP 8.4) |
| Database | MySQL |
| Frontend | Tailwind CSS, Blade Templates |
| UI Icons | Font Awesome 5 |
| Authentication | Laravel Auth (session-based) |
| File Storage | Laravel Storage (public disk) |
| Email | Laravel Mail (SMTP) |
| Architecture | MVC (Model-View-Controller) |

---

## 📁 What's Included in Delivery

- ✅ Complete source code (Laravel application)
- ✅ MySQL database migration files
- ✅ Database seeders for initial roles and admin user
- ✅ Role-based middleware and authentication
- ✅ 40+ Blade view templates (responsive, mobile-friendly)
- ✅ 20+ Controllers with full CRUD operations
- ✅ 15 Eloquent Models with relationships
- ✅ Email integration for parent welcome
- ✅ In-app user guide and documentation
- ✅ Deployment support

---

## 🚀 Post-Deployment Support Options

| Plan | Duration | Cost | Inclusions |
|------|----------|------|-----------|
| **Basic Support** | 1 Month | ₦100,000 | Bug fixes, minor adjustments |
| **Standard Support** | 3 Months | ₦250,000 | Bug fixes, minor adjustments, usage guidance |
| **Premium Support** | 6 Months | ₦450,000 | Bug fixes, minor feature additions, priority support |

---

## 💳 Payment Terms

| Milestone | % | Amount | Trigger |
|-----------|---|--------|--------|
| **Initial Deposit** | 50% | ₦1,100,000 | Project kickoff |
| **Final Payment** | 50% | ₦1,100,000 | Upon delivery and deployment |

**OR**

| Payment Plan | Amount | Due |
|-------------|-------|-----|
| Full Payment (One-off) | ₦1,980,000 *(10% discount)* | Upon delivery |

---

## 📞 Contact

For any questions or adjustments to this quotation, please contact:

**Developer:** [Your Name]
**Email:** [your@email.com]
**Phone:** [Your Phone Number]

---

*This quotation is valid for 14 days from the date of issue.*
