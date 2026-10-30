<?php

namespace Tests\Feature;

use App\Models\Learner;
use App\Models\ParentModel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Operator commands that support note 12:
 *  - esh:backfill-usernames (dry-run by default, --apply writes)
 *  - esh:audit-guardian-links (reports children a guardian reset cannot reach)
 */
class MaintenanceCommandsTest extends TestCase
{
    use DatabaseTransactions;

    private function roleId(string $slug): int
    {
        return Role::where('slug', $slug)->firstOrFail()->id;
    }

    private function makeUser(string $roleSlug, string $name, array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $this->roleId($roleSlug),
            'is_active' => true,
        ], $attributes));
    }

    public function test_username_backfill_is_a_dry_run_by_default(): void
    {
        $user = $this->makeUser('learner', 'Backfill Dry Child', ['username' => null]);

        $this->artisan('esh:backfill-usernames', ['--role' => 'learner'])
            ->assertExitCode(0);

        $this->assertNull($user->fresh()->username);
    }

    public function test_username_backfill_writes_unique_usernames_when_applied(): void
    {
        $first = $this->makeUser('learner', 'Backfill Apply Child', ['username' => null]);
        $second = $this->makeUser('learner', 'Backfill Apply Child', ['username' => null]);

        $this->artisan('esh:backfill-usernames', ['--apply' => true, '--role' => 'learner'])
            ->assertExitCode(0);

        $firstName = $first->fresh()->username;
        $secondName = $second->fresh()->username;

        $this->assertNotNull($firstName);
        $this->assertNotNull($secondName);
        $this->assertStringStartsWith('backfill_apply_child', $firstName);
        $this->assertStringStartsWith('backfill_apply_child', $secondName);
        $this->assertNotSame($firstName, $secondName);
    }

    public function test_username_backfill_is_idempotent(): void
    {
        $user = $this->makeUser('learner', 'Backfill Repeat Child', ['username' => null]);

        $this->artisan('esh:backfill-usernames', ['--apply' => true, '--role' => 'learner'])
            ->assertExitCode(0);

        $generated = $user->fresh()->username;

        $this->artisan('esh:backfill-usernames', ['--apply' => true, '--role' => 'learner'])
            ->assertExitCode(0);

        $this->assertSame($generated, $user->fresh()->username);
    }

    public function test_username_backfill_respects_the_role_filter(): void
    {
        $learner = $this->makeUser('learner', 'Filtered Learner', ['username' => null]);
        $parentUser = $this->makeUser('parent', 'Filtered Parent', ['username' => null]);

        $this->artisan('esh:backfill-usernames', ['--apply' => true, '--role' => 'learner'])
            ->assertExitCode(0);

        $this->assertNotNull($learner->fresh()->username);
        $this->assertNull($parentUser->fresh()->username);
    }

    public function test_username_backfill_rejects_an_unknown_role(): void
    {
        $this->artisan('esh:backfill-usernames', ['--role' => 'wizard'])
            ->assertExitCode(1);
    }

    public function test_the_guardian_audit_reports_a_learner_without_a_guardian(): void
    {
        $learnerUser = $this->makeUser('learner', 'Orphan Learner');
        $learner = Learner::create(['user_id' => $learnerUser->id, 'parent_id' => null]);

        $this->artisan('esh:audit-guardian-links', ['--learner' => $learner->id])
            ->assertExitCode(1);
    }

    public function test_the_guardian_audit_passes_for_a_complete_family(): void
    {
        $guardian = $this->makeUser('parent', 'Audit Guardian');
        $parent = ParentModel::create(['user_id' => $guardian->id]);

        $childUser = $this->makeUser('learner', 'Audit Child');
        $learner = Learner::create(['user_id' => $childUser->id, 'parent_id' => $parent->id]);

        $this->artisan('esh:audit-guardian-links', ['--learner' => $learner->id])
            ->assertExitCode(0);
    }

    public function test_the_guardian_audit_rejects_an_unknown_learner_id(): void
    {
        $this->artisan('esh:audit-guardian-links', ['--learner' => 999999])
            ->assertExitCode(1);
    }
}
