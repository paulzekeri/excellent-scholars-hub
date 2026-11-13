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
use App\Models\Payment;
use App\Models\PaymentAccount;
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

        // Optional maintenance account: created once per environment at deploy time
        // (the workflow runs `migrate --force --seed`). Credentials come from the
        // server's .env, never from the repository. See MAINTENANCE-USER.md.
        $this->seedHiddenSuperAdmin($ceoRole);

        // Demo data must never reach the production database: every demo login uses the
        // password "password". Set SEED_DEMO_ACCOUNTS=true to force it (the live server uses this while testing).
        if (! $this->shouldSeedDemoAccounts()) {
            if ($this->command) {
                $this->command->warn('Demo users, class and schedule were skipped (SEED_DEMO_ACCOUNTS is off).');
                $this->command->line('Create the first real login with: php artisan esh:create-admin --role=ceo');
            }

            return;
        }

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

        // Demo fees: one installment the CEO already verified and one still waiting
        // for the bank check, so the parent area and the CEO queue both have content.
        $schoolAccount = PaymentAccount::firstOrCreate(
            ['account_number' => '0123456789'],
            [
                'bank_name' => 'GTBank',
                'account_name' => 'Excellent Scholars Academy',
                'instructions' => 'Use your child\'s full name as the transfer narration.',
                'is_active' => true,
                'created_by' => $ceoUser->id,
            ]
        );

        // Switched off on purpose: shows how the CEO hides an account from parents.
        PaymentAccount::firstOrCreate(
            ['account_number' => '1098765432'],
            [
                'bank_name' => 'Zenith Bank',
                'account_name' => 'Excellent Scholars Academy',
                'instructions' => 'Old account, kept for history only.',
                'is_active' => false,
                'created_by' => $ceoUser->id,
            ]
        );

        $verifiedPayment = Payment::firstOrCreate(
            ['reference' => 'DEMO-TUITION-001'],
            [
                'user_id' => $parentUser->id,
                'learner_id' => $learner->id,
                'payment_type' => 'tuition',
                'term' => 'First Term',
                'class_id' => $class->id,
                'amount' => 40000,
                'total_fee' => 100000,
                'currency' => 'NGN',
                'payment_method' => Payment::METHOD_TRANSFER,
                'payment_account_id' => $schoolAccount->id,
                'paid_into' => $schoolAccount->displayLabel(),
                'reference' => 'DEMO-TUITION-001',
                'status' => Payment::STATUS_COMPLETED,
                'payment_date' => now()->subDays(21),
            ]
        );

        if ($verifiedPayment->wasRecentlyCreated) {
            $verifiedPayment->forceFill([
                'verified_at' => now()->subDays(20),
                'verified_by' => $ceoUser->id,
            ])->save();
        }

        $pendingPayment = Payment::firstOrCreate(
            ['reference' => 'DEMO-TUITION-002'],
            [
                'user_id' => $parentUser->id,
                'learner_id' => $learner->id,
                'payment_type' => 'tuition',
                'term' => 'First Term',
                'class_id' => $class->id,
                'amount' => 20000,
                'total_fee' => 100000,
                'currency' => 'NGN',
                'payment_method' => Payment::METHOD_TRANSFER,
                'payment_account_id' => $schoolAccount->id,
                'paid_into' => $schoolAccount->displayLabel(),
                'reference' => 'DEMO-TUITION-002',
                'status' => Payment::STATUS_PENDING,
                'payment_date' => now()->subDays(2),
            ]
        );

        // Databases seeded before the school accounts existed still get the new
        // fields without resetting a status the CEO may have changed in the demo.
        foreach ([$verifiedPayment, $pendingPayment] as $demoPayment) {
            if (! $demoPayment->payment_account_id) {
                $demoPayment->forceFill([
                    'payment_account_id' => $schoolAccount->id,
                    'paid_into' => $schoolAccount->displayLabel(),
                ])->save();
            }
        }

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

    /**
     * Create the hidden maintenance account when SUPER_ADMIN_* is set in .env.
     * `exists` semantics: an existing account (and a rotated password) is never
     * overwritten, so this is safe to run on every deploy.
     */
    private function seedHiddenSuperAdmin(Role $ceoRole): void
    {
        $email = strtolower(trim((string) env('SUPER_ADMIN_EMAIL')));
        $password = (string) env('SUPER_ADMIN_PASSWORD');
        $name = trim((string) env('SUPER_ADMIN_NAME')) ?: 'Maintenance';

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            return;
        }

        if (User::where('email', $email)->exists()) {
            return;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $ceoRole->id,
            'is_active' => true,
            'is_hidden' => true,
            'must_change_password' => false,
        ]);

        if ($this->command) {
            $this->command->info("Hidden maintenance account created: {$user->email}");
        }
    }

    /**
     * Demo accounts are seeded on local/testing installs; the live server must opt
     * in explicitly (SEED_DEMO_ACCOUNTS=true) because the demo passwords are public.
     */
    private function shouldSeedDemoAccounts(): bool
    {
        return filter_var(env('SEED_DEMO_ACCOUNTS', ! app()->environment('production')), FILTER_VALIDATE_BOOLEAN);
    }
}
