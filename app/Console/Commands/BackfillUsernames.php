<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Existing accounts pre-date usernames (note 12). This generates a unique
 * username for each account that does not have one, and prints the list so the
 * school can hand the logins to families.
 *
 * Dry-run by default; nothing is written until `--apply` is passed.
 */
class BackfillUsernames extends Command
{
    protected $signature = 'esh:backfill-usernames
        {--apply : Write the generated usernames (dry-run by default)}
        {--role= : Limit to one role slug, e.g. learner}';

    protected $description = 'Generate unique usernames for accounts created before usernames existed';

    public function handle()
    {
        $query = User::query()->whereNull('username')->orderBy('id');

        $roleSlug = strtolower(trim((string) $this->option('role')));

        if ($roleSlug !== '') {
            $role = Role::where('slug', $roleSlug)->first();

            if (! $role) {
                $this->error('Unknown role "' . $roleSlug . '". Try learner, parent, tutor, admin or ceo.');

                return 1;
            }

            $query->where('role_id', $role->id);
        }

        $users = $query->get(['id', 'name', 'email']);

        if ($users->isEmpty()) {
            $this->info('Every matching account already has a username.');

            return 0;
        }

        $taken = User::whereNotNull('username')->pluck('username')
            ->map(function ($value) {
                return strtolower($value);
            })
            ->all();

        $apply = (bool) $this->option('apply');
        $rows = [];

        foreach ($users as $user) {
            $username = $this->propose($user->name, $taken);
            $taken[] = $username;
            $rows[] = [$user->id, $user->name, $user->email ?: '—', $username];

            if ($apply) {
                User::whereKey($user->id)->update(['username' => $username]);
            }
        }

        $this->table(['ID', 'Name', 'Email', 'Username'], $rows);

        if ($apply) {
            $this->info(count($rows) . ' username(s) written. Share them with the families.');
        } else {
            $this->warn('Dry run — nothing was written. Re-run with --apply to save these usernames.');
        }

        return 0;
    }

    /** Propose a free username, remembering proposals already used in this run. */
    private function propose(string $name, array $taken): string
    {
        // Some imported accounts store an email address in the name field.
        $source = Str::contains($name, '@') ? Str::before($name, '@') : $name;

        $base = substr(Str::slug($source, '_') ?: 'user', 0, 25);
        $candidate = $base;
        $suffix = 1;

        while (in_array($candidate, $taken, true) || in_array($candidate, User::RESERVED_USERNAMES, true)) {
            $suffix++;
            $candidate = $base . '_' . $suffix;
        }

        return $candidate;
    }
}
