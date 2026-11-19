{{--
    One "subject + teacher" row used by the class create/edit forms and by the
    add-another-subject template. $index may be "__INDEX__" for the template.

    The subject dropdown is filled from the subjects table. The last option lets
    the office type a brand-new subject, which is saved to that list on submit.
--}}
@php
    $rowSubjectId = (string) ($row['subject_id'] ?? '');
    $rowSubjectName = (string) ($row['subject'] ?? '');
    // A saved row with a name but no id came from the older free-text field.
    $isNewSubject = $rowSubjectId === '' && $rowSubjectName !== '';
    $rowTutor = (string) ($row['tutor_id'] ?? '');
    $subjectChoices = $subjects ?? collect();
@endphp
<div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-gray-50 p-3 md:flex-row md:items-start" data-subject-row>
    <div class="flex-1 space-y-2">
        <select name="subjects[{{ $index }}][subject_id]" data-subject-select aria-label="Subject"
            class="block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
            <option value="">— Choose a subject —</option>
            @foreach($subjectChoices as $subject)
                {{-- Inactive subjects stay visible on rows that already use them. --}}
                @if($subject->is_active || $rowSubjectId === (string) $subject->id)
                    <option value="{{ $subject->id }}" {{ $rowSubjectId === (string) $subject->id ? 'selected' : '' }}>
                        {{ $subject->name }}{{ $subject->is_active ? '' : ' (inactive)' }}
                    </option>
                @endif
            @endforeach
            <option value="__new__" {{ $isNewSubject ? 'selected' : '' }}>+ Add a new subject…</option>
        </select>
        <div data-new-subject-wrap class="{{ $isNewSubject ? '' : 'hidden' }}">
            <input type="text" name="subjects[{{ $index }}][subject]" value="{{ $isNewSubject ? $rowSubjectName : '' }}"
                data-new-subject aria-label="New subject name" placeholder="Type the new subject name, e.g. Biology"
                class="block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-xs text-gray-500">It is added to the subject list automatically when you save.</p>
        </div>
    </div>
    <div class="flex-1">
        <select name="subjects[{{ $index }}][tutor_id]" aria-label="Teacher for this subject"
            class="block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
            <option value="">Not assigned yet</option>
            @foreach($tutors as $tutor)
                <option value="{{ $tutor->id }}" {{ $rowTutor === (string) $tutor->id ? 'selected' : '' }}>{{ $tutor->user->name ?? 'Tutor' }}</option>
            @endforeach
        </select>
    </div>
    <button type="button" class="remove-subject-row inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-3 text-orange-700 hover:bg-white" title="Remove this subject">
        <i class="fas fa-trash"></i>
    </button>
</div>
