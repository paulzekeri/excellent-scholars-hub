@php
    $selectedResult = old('result', \App\Models\AssessmentSession::STATUS_COMPLETED);
    $selectedOutcome = old('outcome', $assessment->outcome);
    $selectedClassId = old('recommended_class_id', $assessment->recommended_class_id);
@endphp
<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-4">
    @csrf

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="result" class="block text-sm font-medium text-gray-700">Result</label>
            <select id="result" name="result" required
                class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="completed" {{ $selectedResult === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="no_show" {{ $selectedResult === 'no_show' ? 'selected' : '' }}>No show</option>
            </select>
        </div>
        <div>
            <label for="outcome" class="block text-sm font-medium text-gray-700">Outcome</label>
            <select id="outcome" name="outcome"
                class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Not recorded</option>
                @foreach(\App\Models\AssessmentSession::OUTCOMES as $value => $label)
                    <option value="{{ $value }}" {{ $selectedOutcome === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">Required when the assessment was completed.</p>
        </div>

        <div class="sm:col-span-2">
            <label for="strengths" class="block text-sm font-medium text-gray-700">Strengths</label>
            <textarea id="strengths" name="strengths" rows="2"
                class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="What the child did well">{{ old('strengths', $assessment->strengths) }}</textarea>
        </div>

        <div class="sm:col-span-2">
            <label for="focus_areas" class="block text-sm font-medium text-gray-700">Focus areas</label>
            <textarea id="focus_areas" name="focus_areas" rows="2"
                class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="What needs work">{{ old('focus_areas', $assessment->focus_areas) }}</textarea>
        </div>

        <div>
            <label for="recommended_class_id" class="block text-sm font-medium text-gray-700">Recommended class</label>
            <select id="recommended_class_id" name="recommended_class_id"
                class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Not decided yet</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ (string) $selectedClassId === (string) $class->id ? 'selected' : '' }}>
                        {{ $class->name }}{{ $class->subject ? ' — ' . $class->subject : '' }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">Choosing a class moves the enquiry to <strong>Awaiting enrolment</strong>.</p>
        </div>

        <div>
            <label for="recommended_level" class="block text-sm font-medium text-gray-700">Recommended level</label>
            <input id="recommended_level" type="text" name="recommended_level"
                value="{{ old('recommended_level', $assessment->recommended_level) }}"
                class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="e.g. Year 5, needs phonics support">
        </div>

        <div class="sm:col-span-2">
            <label for="assessment_result_notes" class="block text-sm font-medium text-gray-700">Internal notes</label>
            <textarea id="assessment_result_notes" name="notes" rows="2"
                class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Anything the school should know">{{ old('notes', $assessment->notes) }}</textarea>
        </div>

        <div class="sm:col-span-2">
            <label for="report" class="block text-sm font-medium text-gray-700">Report (optional)</label>
            <input id="report" type="file" name="report" accept=".pdf,.jpg,.jpeg,.png,.webp"
                class="mt-1 block w-full text-sm text-gray-600">
            <p class="text-xs text-gray-500 mt-1">PDF or image, up to 5 MB. Uploading replaces any previous report.</p>
        </div>
    </div>

    <button type="submit" class="bg-green-600 text-white px-5 py-2.5 rounded-lg hover:bg-green-700 transition">
        <i class="fas fa-check mr-1"></i> Save result
    </button>
</form>
