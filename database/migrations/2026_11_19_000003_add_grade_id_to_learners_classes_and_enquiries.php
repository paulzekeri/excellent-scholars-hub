<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Links the existing free-text grade columns to the new grades catalogue.
 *
 * The text columns stay because older screens, reports and imports still read
 * them; they are kept equal to the grade's label whenever a form saves.
 */
class AddGradeIdToLearnersClassesAndEnquiries extends Migration
{
    public function up()
    {
        Schema::table('learners', function (Blueprint $table) {
            $table->unsignedBigInteger('grade_id')->nullable()->after('grade_level');
            $table->foreign('grade_id')->references('id')->on('grades')->nullOnDelete();
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->unsignedBigInteger('grade_id')->nullable()->after('grade_level');
            $table->foreign('grade_id')->references('id')->on('grades')->nullOnDelete();
        });

        Schema::table('enquiries', function (Blueprint $table) {
            $table->unsignedBigInteger('grade_id')->nullable()->after('child_grade_level');
            $table->foreign('grade_id')->references('id')->on('grades')->nullOnDelete();
        });

        $this->backfill();
    }

    private function backfill(): void
    {
        if (! Schema::hasTable('grades')) {
            return;
        }

        // Build a lookup of every label the seeded catalogue already offers.
        $grades = DB::table('grades')->get(['id', 'name', 'category']);
        $lookup = [];
        $maxOrder = (int) DB::table('grades')->max('sort_order');

        foreach ($grades as $grade) {
            $lookup[mb_strtolower(trim($grade->name))] = $grade->id;
            $lookup[mb_strtolower(trim($grade->name . ($grade->category ? ' - ' . $grade->category : '')))] = $grade->id;
        }

        $sources = [
            ['table' => 'learners', 'column' => 'grade_level'],
            ['table' => 'classes', 'column' => 'grade_level'],
            ['table' => 'enquiries', 'column' => 'child_grade_level'],
        ];

        foreach ($sources as $source) {
            $values = DB::table($source['table'])
                ->whereNotNull($source['column'])
                ->where($source['column'], '<>', '')
                ->distinct()
                ->pluck($source['column']);

            foreach ($values as $value) {
                $value = trim((string) $value);

                if ($value === '') {
                    continue;
                }

                $key = mb_strtolower($value);

                if (! isset($lookup[$key])) {
                    // A grade the school already used but that is not in the
                    // seeded list: keep it as a catalogue entry of its own.
                    $maxOrder += 10;
                    $id = DB::table('grades')->insertGetId([
                        'name' => $value,
                        'category' => null,
                        'sort_order' => $maxOrder,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $lookup[$key] = $id;
                }

                DB::table($source['table'])
                    ->where($source['column'], $value)
                    ->update(['grade_id' => $lookup[$key]]);
            }
        }
    }

    public function down()
    {
        Schema::table('learners', function (Blueprint $table) {
            $table->dropForeign(['grade_id']);
            $table->dropColumn('grade_id');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['grade_id']);
            $table->dropColumn('grade_id');
        });

        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropForeign(['grade_id']);
            $table->dropColumn('grade_id');
        });
    }
}
