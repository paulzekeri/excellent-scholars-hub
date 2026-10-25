<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\Learner;
use App\Models\ParentModel;
use App\Models\Classes;
use App\Models\ClassSchedule;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create Roles
        // firstOrCreate keeps the seeder re-runnable: the `ceo` role is also inserted by
        // a migration, and existing installs already have the other four roles.
        $ceoRole = Role::firstOrCreate(
            ['slug' => 'ceo'],
            ['name' => 'CEO', 'description' => 'Chief Executive Officer — full admin access plus CEO-only payments']
        );
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'admin', 'description' => 'Administrator']
        );
        $tutorRole = Role::firstOrCreate(
            ['slug' => 'tutor'],
            ['name' => 'tutor', 'description' => 'Tutor/Teacher']
        );
        $learnerRole = Role::firstOrCreate(
            ['slug' => 'learner'],
            ['name' => 'learner', 'description' => 'Student/Learner']
        );
        $parentRole = Role::firstOrCreate(
            ['slug' => 'parent'],
            ['name' => 'parent', 'description' => 'Parent/Guardian']
        );

        // Users are matched on email so re-seeding an existing install only adds
        // the accounts that are missing (e.g. the dedicated CEO account).
        $ceoUser = User::firstOrCreate(
            ['email' => 'ceo@esh.com'],
            [
                'name' => 'CEO',
                'password' => Hash::make('password'),
                'role_id' => $ceoRole->id,
                'phone' => '+2348000000000',
                'is_active' => true,
            ]
        );

        // Admin User
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@esh.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'phone' => '+2348000000001',
                'is_active' => true,
            ]
        );

        // Tutor User
        $tutorUser = User::firstOrCreate(
            ['email' => 'tutor@esh.com'],
            [
                'name' => 'John Tutor',
                'password' => Hash::make('password'),
                'role_id' => $tutorRole->id,
                'phone' => '+2348000000002',
                'is_active' => true,
            ]
        );

        $tutor = Tutor::firstOrCreate(
            ['user_id' => $tutorUser->id],
            [
                'qualification' => 'B.Ed Mathematics',
                'specialization' => 'Mathematics',
                'bio' => 'Experienced mathematics tutor with 5+ years.',
                'is_active' => true,
            ]
        );

        // Learner User
        $learnerUser = User::firstOrCreate(
            ['email' => 'learner@esh.com'],
            [
                'name' => 'Sarah Learner',
                'password' => Hash::make('password'),
                'role_id' => $learnerRole->id,
                'phone' => '+2348000000003',
                'is_active' => true,
            ]
        );

        $learner = Learner::firstOrCreate(
            ['user_id' => $learnerUser->id],
            [
                'grade_level' => 'Grade 10',
                'phone' => '+2348000000003',
                'date_of_birth' => '2008-05-15',
                'is_active' => true,
            ]
        );

        // Parent User
        $parentUser = User::firstOrCreate(
            ['email' => 'parent@esh.com'],
            [
                'name' => 'Paul Parent',
                'password' => Hash::make('password'),
                'role_id' => $parentRole->id,
                'phone' => '+2348000000004',
                'is_active' => true,
            ]
        );

        $parent = ParentModel::firstOrCreate(
            ['user_id' => $parentUser->id],
            [
                'phone' => '+2348000000004',
                'is_active' => true,
            ]
        );

        // Link parent to learner
        if (! $learner->parent_id) {
            $learner->update(['parent_id' => $parent->id]);
        }

        // Create a Class
        $class = Classes::firstOrCreate(
            ['code' => 'MATH101'],
            [
                'tutor_id' => $tutor->id,
                'name' => 'Mathematics 101',
                'subject' => 'Mathematics',
                'grade_level' => 'Grade 10',
                'description' => 'Basic mathematics for grade 10 students.',
                'max_students' => 30,
                'is_active' => true,
            ]
        );

        // Enroll learner in class
        $class->learners()->syncWithoutDetaching([$learner->id]);

        // Create a schedule with meeting link
        $schedule = ClassSchedule::firstOrCreate(
            [
                'class_id' => $class->id,
                'title' => 'Algebra Fundamentals',
            ],
            [
                'tutor_id' => $tutor->id,
                'description' => 'Introduction to algebraic expressions',
                'start_time' => now()->addDays(1)->setHour(10)->setMinute(0),
                'end_time' => now()->addDays(1)->setHour(11)->setMinute(30),
                'meeting_platform' => 'google_meet',
                'meeting_link' => 'https://meet.google.com/abc-defg-hij',
                'status' => 'scheduled',
            ]
        );
    }
}
