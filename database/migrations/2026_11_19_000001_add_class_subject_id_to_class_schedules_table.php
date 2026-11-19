<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A session now belongs to one subject of the class, not just to the class.
 *
 *   Test Class WAEC / Mathematics / John  → Monday 9:00 am
 *   Test Class WAEC / Physics     / Ada   → Monday 11:00 am
 *   Test Class WAEC / English     / Tunde → Wednesday 4:00 pm
 *
 * `class_subject_id` points at the class_subject row, so the subject and the
 * teacher come from the same place the class page uses. `class_id` and
 * `tutor_id` stay on the row (and are kept in step by the controllers) so old
 * schedules, reports and queries written before this feature keep working.
 *
 * Existing sessions are matched to the subject row with the same class and
 * tutor. When that is ambiguous (a tutor takes two subjects in one class) the
 * class's original `subject` column decides; otherwise the link is left empty
 * and the office can pick the subject when editing the session.
 */
class AddClassSubjectIdToClassSchedulesTable extends Migration
{
    public function up()
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('class_subject_id')->nullable()->after('class_id');

            // Removing a subject from a class keeps its past sessions (the
            // subject link is cleared) instead of deleting history.
            $table->foreign('class_subject_id')
                ->references('id')
                ->on('class_subject')
                ->nullOnDelete();
        });

        $this->backfill();
    }

    private function backfill(): void
    {
        if (! Schema::hasTable('class_subject') || ! Schema::hasTable('subjects')) {
            return;
        }

        $subjectRows = DB::table('class_subject')
            ->get(['id', 'class_id', 'subject_id', 'tutor_id'])
            ->groupBy('class_id');

        DB::table('class_schedules')
            ->select('id', 'class_id', 'tutor_id')
            ->orderBy('id')
            ->chunkById(200, function ($schedules) use ($subjectRows) {
                foreach ($schedules as $schedule) {
                    $rows = $subjectRows->get($schedule->class_id);

                    if (! $rows || $rows->isEmpty()) {
                        continue;
                    }

                    // Same class and same tutor is the strongest match.
                    $match = $rows->first(function ($row) use ($schedule) {
                        return (int) $row->tutor_id === (int) $schedule->tutor_id;
                    });

                    // A class with a single subject is unambiguous.
                    if (! $match && $rows->count() === 1) {
                        $match = $rows->first();
                    }

                    // A tutor with several subjects: use the class's legacy
                    // subject column (the first subject saved on the class).
                    if (! $match) {
                        $legacyName = DB::table('classes')->where('id', $schedule->class_id)->value('subject');
                        $subjectId = $legacyName
                            ? DB::table('subjects')->where('name', $legacyName)->value('id')
                            : null;

                        if ($subjectId) {
                            $match = $rows->first(function ($row) use ($subjectId) {
                                return (int) $row->subject_id === (int) $subjectId;
                            });
                        }
                    }

                    if ($match) {
                        DB::table('class_schedules')
                            ->where('id', $schedule->id)
                            ->update(['class_subject_id' => $match->id]);
                    }
                }
            });
    }

    public function down()
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropForeign(['class_subject_id']);
            $table->dropColumn('class_subject_id');
        });
    }
}
