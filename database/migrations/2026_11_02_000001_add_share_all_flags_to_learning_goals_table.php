<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "I ticked specific people, but all parents / all learners / all tutors still
 * show as selected."
 *
 * The share form now records whether "every parent / learner / tutor the goal is
 * about" was actually ticked, instead of inferring it from the shared_with_*
 * flags. Those flags must stay on when somebody is picked by name because they
 * drive the list filters and the legacy visibility scopes, so they cannot carry
 * the "everyone" meaning on their own.
 */
class AddShareAllFlagsToLearningGoalsTable extends Migration
{
    /** @var array<int, string> */
    private const ROLES = ['parent', 'learner', 'tutor'];

    public function up()
    {
        Schema::table('learning_goals', function (Blueprint $table) {
            $table->boolean('share_all_parent')->default(false)->after('shared_with_learner');
            $table->boolean('share_all_learner')->default(false)->after('share_all_parent');
            $table->boolean('share_all_tutor')->default(false)->after('share_all_learner');
        });

        // For goals shared before this column existed, only assume the tick when
        // nobody of that role was picked by name.
        foreach (self::ROLES as $role) {
            DB::table('learning_goals')
                ->where('shared_with_' . $role, true)
                ->whereNotExists(function ($query) use ($role) {
                    $query->select(DB::raw(1))
                        ->from('learning_goal_shares')
                        ->whereColumn('learning_goal_shares.learning_goal_id', 'learning_goals.id')
                        ->where('learning_goal_shares.role_slug', $role);
                })
                ->update(['share_all_' . $role => true]);
        }
    }

    public function down()
    {
        Schema::table('learning_goals', function (Blueprint $table) {
            $table->dropColumn(['share_all_parent', 'share_all_learner', 'share_all_tutor']);
        });
    }
}
