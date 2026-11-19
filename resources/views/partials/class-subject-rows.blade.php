{{--
    "Subjects & Teachers" block shared by the admin class create and edit forms.

    A class can teach any number of subjects, and each subject has its own
    teacher. The same tutor may be selected on several rows (for example John
    Tutor for both Mathematics and Physics), and a subject may be left without a
    teacher until one is decided.
--}}
@php
    $rows = isset($subjectRows) && is_array($subjectRows) && $subjectRows !== []
        ? array_values($subjectRows)
        : [['subject_id' => '', 'subject' => '', 'tutor_id' => '']];
    $subjectChoices = $subjects ?? collect();
@endphp

<div class="md:col-span-2">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <span class="block text-sm font-medium text-gray-700">Subjects &amp; Teachers</span>
        <div class="flex flex-wrap items-center gap-4">
            <a href="{{ route('admin.subjects.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                <i class="fas fa-list mr-1"></i>Manage subject list
            </a>
            <button type="button" id="add-subject-row" class="inline-flex items-center text-sm font-semibold text-blue-700 hover:text-blue-900">
                <i class="fas fa-plus mr-1"></i>Add another subject
            </button>
        </div>
    </div>
    <p class="text-xs text-gray-500 mt-1 mb-3">
        Pick each subject from the list and choose its teacher. Choose <strong>+ Add a new subject…</strong>
        if it is not in the list yet — it is saved for next time. The same teacher can take more than one subject,
        and a subject can be left without a teacher until one is decided.
    </p>

    <div id="subject-rows" class="space-y-3">
        @foreach($rows as $index => $row)
            @include('partials.class-subject-row', ['index' => $index, 'row' => $row, 'tutors' => $tutors, 'subjects' => $subjectChoices])
        @endforeach
    </div>

    <template id="subject-row-template">
        @include('partials.class-subject-row', ['index' => '__INDEX__', 'row' => [], 'tutors' => $tutors, 'subjects' => $subjectChoices])
    </template>

    @php
        // Row errors arrive as "subjects.0.subject", so collect them together.
        $subjectRowErrors = collect($errors->getMessages())
            ->filter(function ($messages, $key) {
                return $key === 'subjects' || \Illuminate\Support\Str::startsWith($key, 'subjects.');
            })
            ->flatten();
    @endphp

    @if($subjectRowErrors->isNotEmpty())
        <ul class="mt-2 space-y-1">
            @foreach($subjectRowErrors as $message)
                <li class="text-xs text-orange-700">{{ $message }}</li>
            @endforeach
        </ul>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rows = document.getElementById('subject-rows');
        const template = document.getElementById('subject-row-template');
        const addButton = document.getElementById('add-subject-row');

        if (!rows || !template || !addButton) {
            return;
        }

        // Show the "type a new subject" box only when "+ Add a new subject…"
        // is chosen. A hidden box is disabled so it never posts a stray name.
        function syncNewSubject(row) {
            const select = row.querySelector('[data-subject-select]');
            const wrap = row.querySelector('[data-new-subject-wrap]');
            const input = row.querySelector('[data-new-subject]');

            if (!select || !wrap || !input) {
                return;
            }

            const isNew = select.value === '__new__';

            wrap.classList.toggle('hidden', !isNew);
            input.disabled = !isNew;
            input.required = isNew;

            if (!isNew) {
                input.value = '';
            }
        }

        // Continue numbering after any rows restored by old() input.
        let nextIndex = 0;
        rows.querySelectorAll('[data-subject-row]').forEach(function (row) {
            const field = row.querySelector('[name^="subjects["]');
            const match = field ? field.name.match(/subjects\[(\d+)\]/) : null;

            if (match) {
                nextIndex = Math.max(nextIndex, parseInt(match[1], 10) + 1);
            }

            syncNewSubject(row);
        });

        rows.addEventListener('change', function (event) {
            const select = event.target.closest ? event.target.closest('[data-subject-select]') : null;

            if (!select) {
                return;
            }

            const row = select.closest('[data-subject-row]');

            if (row) {
                syncNewSubject(row);
            }
        });

        addButton.addEventListener('click', function () {
            const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
            nextIndex += 1;

            rows.insertAdjacentHTML('beforeend', html);

            const added = rows.lastElementChild;
            const first = added ? added.querySelector('select') : null;

            if (added) {
                syncNewSubject(added);
            }

            if (first) {
                first.focus();
            }
        });

        document.addEventListener('click', function (event) {
            const button = event.target.closest ? event.target.closest('.remove-subject-row') : null;

            if (!button || !rows.contains(button)) {
                return;
            }

            const row = button.closest('[data-subject-row]');

            // Always leave one row so the form still explains itself.
            if (row && rows.querySelectorAll('[data-subject-row]').length > 1) {
                row.remove();
            } else if (row) {
                row.querySelectorAll('input, select').forEach(function (field) {
                    field.value = '';
                });

                syncNewSubject(row);
            }
        });
    });
</script>
@endpush
