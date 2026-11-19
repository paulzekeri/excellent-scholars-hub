<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Carries existing classes over to the new subject rows.
 *
 * Every class saved before this change has one free-text `subject` column and
 * one lead tutor. That becomes a normal subject + teacher row, so the class
 * form, the learner pages and the tutor views show the same information they
 * showed before — and the school can start adding the other subjects.
 */
class BackfillClassSubjectsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('subjects') || ! Schema::hasTable('class_subject')) {
            return;
        }

        DB::table('classes')
            ->select('id', 'subject', 'tutor_id')
            ->whereNotNull('subject')
            ->where('subject', '<>', '')
            ->orderBy('id')
            ->chunkById(100, function ($classes) {
                foreach ($classes as $class) {
                    $name = trim((string) $class->subject);

                    if ($name === '') {
                        continue;
                    }

                    $subjectId = DB::table('subjects')->where('name', $name)->value('id');

                    if (! $subjectId) {
                        $subjectId = DB::table('subjects')->insertGetId([
                            'name' => $name,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $exists = DB::table('class_subject')
                        ->where('class_id', $class->id)
                        ->where('subject_id', $subjectId)
                        ->exists();

                    if (! $exists) {
                        DB::table('class_subject')->insert([
                            'class_id' => $class->id,
                            'subject_id' => $subjectId,
                            'tutor_id' => $class->tutor_id ?: null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    public function down()
    {
        // The data stays: dropping the tables in the previous migration's down()
        // removes it, and the legacy `classes.subject` column was never changed.
    }
}
