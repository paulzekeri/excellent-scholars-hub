<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Enquiry;
use App\Models\Grade;
use App\Models\Learner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Grade entry and management (Academics → Grades).
 *
 * These rows fill every grade dropdown: learner registration, admin/CEO user
 * creation, assessment booking, enquiry capture and class creation.
 */
class GradeController extends Controller
{
    public function index()
    {
        $grades = Grade::withCount(['learners', 'classes', 'enquiries'])
            ->ordered()
            ->get();

        return view('admin.grades.index', compact('grades'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $grade = Grade::create($validated);

        return back()->with('success', $grade->label() . ' was added to the grade list.');
    }

    public function update(Request $request, Grade $grade)
    {
        $validated = $this->validated($request, $grade);
        $validated['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : $grade->is_active;

        $oldLabel = $grade->label();

        DB::transaction(function () use ($grade, $validated, $oldLabel) {
            $grade->update($validated);
            $newLabel = $grade->fresh()->label();

            // Keep the old text columns readable for existing screens/reports.
            if ($newLabel !== $oldLabel) {
                Learner::where('grade_id', $grade->id)->update(['grade_level' => $newLabel]);
                Classes::where('grade_id', $grade->id)->update(['grade_level' => $newLabel]);
                Enquiry::where('grade_id', $grade->id)->update(['child_grade_level' => $newLabel]);
            }
        });

        return back()->with('success', $grade->label() . ' was updated.');
    }

    public function destroy(Grade $grade)
    {
        if ($grade->learners()->exists() || $grade->classes()->exists() || $grade->enquiries()->exists()) {
            return back()->with(
                'error',
                $grade->label() . ' is still used by a learner, class or assessment booking, so it cannot be deleted. Switch it off instead.'
            );
        }

        $label = $grade->label();
        $grade->delete();

        return back()->with('success', $label . ' was removed from the grade list.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Grade $grade = null): array
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
        ]);

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('grades', 'name')->ignore($grade ? $grade->id : null),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Type the grade name, e.g. KG1 - Pre Primary.',
            'name.unique' => 'That grade is already in the list.',
        ]);
    }
}
