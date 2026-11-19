<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The grade is one free-text name ("KG1 - Pre Primary"), so the separate
 * category/stage column is no longer needed.
 *
 * Existing rows that were saved as name + category are merged first, e.g.
 * "KG1" + "Pre Primary" becomes "KG1 - Pre Primary". Learners, classes and
 * enquiries are updated to the merged label, and any duplicate that the merge
 * creates is collapsed onto the grade that already had that name.
 */
class MergeGradeCategoryIntoName extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('grades', 'category')) {
            return;
        }

        DB::transaction(function () {
            $grades = DB::table('grades')->orderBy('id')->get(['id', 'name', 'category']);

            foreach ($grades as $grade) {
                $name = trim((string) $grade->name);
                $category = trim((string) $grade->category);

                // Do not append a category the name already contains.
                if ($category !== '' && mb_stripos($name, $category) === false) {
                    $name = trim($name . ' - ' . $category);
                }

                if ($name === '' || $name === $grade->name) {
                    continue;
                }

                // The merged name may already belong to another grade row.
                $existingId = DB::table('grades')
                    ->where('name', $name)
                    ->where('id', '<>', $grade->id)
                    ->value('id');

                if ($existingId) {
                    $this->moveReferences($grade->id, $existingId);
                    DB::table('grades')->where('id', $grade->id)->delete();
                    $this->syncLegacyLabels($existingId, $name);
                } else {
                    DB::table('grades')->where('id', $grade->id)->update(['name' => $name]);
                    $this->syncLegacyLabels($grade->id, $name);
                }
            }
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    /** Repoint learners, classes and enquiries from one grade row to another. */
    private function moveReferences(int $fromId, int $toId): void
    {
        DB::table('learners')->where('grade_id', $fromId)->update(['grade_id' => $toId]);
        DB::table('classes')->where('grade_id', $fromId)->update(['grade_id' => $toId]);
        DB::table('enquiries')->where('grade_id', $fromId)->update(['grade_id' => $toId]);
    }

    /** Keep the old text columns equal to the grade's new name. */
    private function syncLegacyLabels(int $gradeId, string $label): void
    {
        DB::table('learners')->where('grade_id', $gradeId)->update(['grade_level' => $label]);
        DB::table('classes')->where('grade_id', $gradeId)->update(['grade_level' => $label]);
        DB::table('enquiries')->where('grade_id', $gradeId)->update(['child_grade_level' => $label]);
    }

    public function down()
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name');
        });
    }
}
