<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The school's grade / class-level catalogue, e.g. "KG1 - Pre Primary",
 * "Primary 3", "JSS1 - Junior Secondary".
 *
 * Every dropdown (learner registration, admin user creation, assessment
 * booking, enquiry capture and class creation) reads from this table. A few
 * common grades are seeded so the dropdowns work immediately; the school can
 * rename, reorder, switch off or add its own on Academics → Grades.
 */
class CreateGradesTable extends Migration
{
    public function up()
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name');
            $table->index(['sort_order', 'name']);
        });

        // Starter catalogue. The office can edit every row afterwards.
        $defaults = [
            ['KG1', 'Pre Primary'],
            ['KG2', 'Pre Primary'],
            ['Nursery 1', 'Pre Primary'],
            ['Nursery 2', 'Pre Primary'],
            ['Primary 1', 'Primary'],
            ['Primary 2', 'Primary'],
            ['Primary 3', 'Primary'],
            ['Primary 4', 'Primary'],
            ['Primary 5', 'Primary'],
            ['Primary 6', 'Primary'],
            ['JSS1', 'Junior Secondary'],
            ['JSS2', 'Junior Secondary'],
            ['JSS3', 'Junior Secondary'],
            ['SS1', 'Senior Secondary'],
            ['SS2', 'Senior Secondary'],
            ['SS3', 'Senior Secondary'],
        ];

        $now = now();
        $order = 10;

        foreach ($defaults as [$name, $category]) {
            DB::table('grades')->insert([
                'name' => $name,
                'category' => $category,
                'sort_order' => $order,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $order += 10;
        }
    }

    public function down()
    {
        Schema::dropIfExists('grades');
    }
}
