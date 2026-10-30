<?php

namespace App\Console\Commands;

use App\Models\Learner;
use Illuminate\Console\Command;

/**
 * Reports learners who have no guardian, or whose guardian has no email.
 *
 * Those accounts cannot receive the guardian-delivered password reset (note 12),
 * so they need fixing from Admin → Users.
 */
class AuditGuardianLinks extends Command
{
    protected $signature = 'esh:audit-guardian-links {--learner= : Check a single learner id}';

    protected $description = 'Report learners without a guardian email (their reset link has nowhere to go)';

    public function handle()
    {
        $query = Learner::query()->with(['user', 'parent.user'])->orderBy('id');

        $learnerId = $this->option('learner');

        if ($learnerId) {
            $query->whereKey($learnerId);
        }

        $learners = $query->get();

        if ($learnerId && $learners->isEmpty()) {
            $this->error('No learner found with id ' . $learnerId . '.');

            return 1;
        }

        $broken = $learners->filter(function (Learner $learner) {
            $guardianEmail = optional(optional($learner->parent)->user)->email;

            return ! $learner->user || ! $guardianEmail;
        });

        if ($broken->isEmpty()) {
            $this->info('Every checked learner has a linked guardian with an email address.');

            return 0;
        }

        $this->table(
            ['Learner', 'Name', 'Username', 'Guardian email'],
            $broken->map(function (Learner $learner) {
                return [
                    $learner->id,
                    optional($learner->user)->name ?: '(no user record)',
                    optional($learner->user)->username ?: '—',
                    optional(optional($learner->parent)->user)->email ?: '—',
                ];
            })->all()
        );

        $this->error($broken->count() . ' learner(s) cannot receive a guardian-delivered reset. Fix them from Admin → Users.');

        return 1;
    }
}
