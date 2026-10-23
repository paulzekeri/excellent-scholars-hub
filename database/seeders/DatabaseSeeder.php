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
        $adminRole = Role::create(['name' => 'admin', 'slug' => 'admin', 'description' => 'Administrator']);
        $tutorRole = Role::create(['name' => 'tutor', 'slug' => 'tutor', 'description' => 'Tutor/Teacher']);
        $learnerRole = Role::create(['name' => 'learner', 'slug' => 'learner', 'description' => 'Student/Learner']);
        $parentRole = Role::create(['name' => 'parent', 'slug' => 'parent', 'description' => 'Parent/Guardian']);

        // Admin User
        $adminUser = User::create([
            'name' => 'Admin',
            'email' => 'admin@esh.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'phone' => '+2348000000001',
            'is_active' => true,
        ]);

        // Tutor User
        $tutorUser = User::create([
            'name' => 'John Tutor',
            'email' => 'tutor@esh.com',
            'password' => Hash::make('password'),
            'role_id' => $tutorRole->id,
            'phone' => '+2348000000002',
            'is_active' => true,
        ]);

        $tutor = Tutor::create([
            'user_id' => $tutorUser->id,
            'qualification' => 'B.Ed Mathematics',
            'specialization' => 'Mathematics',
            'bio' => 'Experienced mathematics tutor with 5+ years.',
            'is_active' => true,
        ]);

        // Learner User
        $learnerUser = User::create([
            'name' => 'Sarah Learner',
            'email' => 'learner@esh.com',
            'password' => Hash::make('password'),
            'role_id' => $learnerRole->id,
            'phone' => '+2348000000003',
            'is_active' => true,
        ]);

        $learner = Learner::create([
            'user_id' => $learnerUser->id,
            'grade_level' => 'Grade 10',
            'phone' => '+2348000000003',
            'date_of_birth' => '2008-05-15',
            'is_active' => true,
        ]);

        // Parent User
        $parentUser = User::create([
            'name' => 'Paul Parent',
            'email' => 'parent@esh.com',
            'password' => Hash::make('password'),
            'role_id' => $parentRole->id,
            'phone' => '+2348000000004',
            'is_active' => true,
        ]);

        $parent = ParentModel::create([
            'user_id' => $parentUser->id,
            'phone' => '+2348000000004',
            'is_active' => true,
        ]);

        // Link parent to learner
        $learner->update(['parent_id' => $parent->id]);

        // Create a Class
        $class = Classes::create([
            'tutor_id' => $tutor->id,
            'name' => 'Mathematics 101',
            'code' => 'MATH101',
            'subject' => 'Mathematics',
            'grade_level' => 'Grade 10',
            'description' => 'Basic mathematics for grade 10 students.',
            'max_students' => 30,
            'is_active' => true,
        ]);

        // Enroll learner in class
        $class->learners()->attach($learner->id);

        // Create a schedule with meeting link
        $schedule = ClassSchedule::create([
            'class_id' => $class->id,
            'tutor_id' => $tutor->id,
            'title' => 'Algebra Fundamentals',
            'description' => 'Introduction to algebraic expressions',
            'start_time' => now()->addDays(1)->setHour(10)->setMinute(0),
            'end_time' => now()->addDays(1)->setHour(11)->setMinute(30),
            'meeting_platform' => 'google_meet',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
            'status' => 'scheduled',
        ]);
    }
}
