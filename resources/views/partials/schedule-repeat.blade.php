{{--
    "Repeat" block shared by the admin and tutor schedule forms.

    One-off lesson: a single date and time (start_time / end_time).
    Weekly series : any number of day + start + end rows (slots). Each slot
                    repeats on its own weekday and time, weekly, from
                    "Repeats from" until "Repeat until".
--}}
@php
    $repeatMode = old('repeat') === 'weekly' ? 'weekly' : 'none';

    $slotRows = old('slots');

    if (! is_array($slotRows) || $slotRows === []) {
        $slotRows = [['day' => '', 'start' => '', 'end' => '']];
    }
@endphp

<div>
    <label for="schedule-repeat" class="block text-sm font-medium text-gray-700">Repeat</label>
    <select name="repeat" id="schedule-repeat" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
        <option value="none" {{ $repeatMode === 'none' ? 'selected' : '' }}>Does not repeat (one-off lesson)</option>
        <option value="weekly" {{ $repeatMode === 'weekly' ? 'selected' : '' }}>Every week on the days &amp; times I choose</option>
    </select>
    <p class="text-xs text-gray-500 mt-1">A weekly repeat can run on several days, each at its own time.</p>
</div>

{{-- One-off lesson: a single date and time --}}
<div id="one-off-fields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="start_time" class="block text-sm font-medium text-gray-700">Start Time</label>
        <input id="start_time" type="datetime-local" name="start_time" value="{{ old('start_time') }}" @if($repeatMode === 'none') required @endif class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
        @error('start_time')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="end_time" class="block text-sm font-medium text-gray-700">End Time</label>
        <input id="end_time" type="datetime-local" name="end_time" value="{{ old('end_time') }}" @if($repeatMode === 'none') required @endif class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
        @error('end_time')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
    </div>
</div>

{{-- Weekly series: several day/time slots, each repeating weekly --}}
<div id="weekly-fields" class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50/60 p-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="repeats_from" class="block text-sm font-medium text-gray-700">Repeats from</label>
            <input id="repeats_from" type="date" name="repeats_from" value="{{ old('repeats_from', now()->toDateString()) }}" @if($repeatMode === 'weekly') required @endif class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
            <p class="text-xs text-gray-600 mt-1">The first lesson of each chosen day falls on or after this date.</p>
            @error('repeats_from')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="repeat_until" class="block text-sm font-medium text-gray-700">Repeat until</label>
            <input id="repeat_until" type="date" name="repeat_until" value="{{ old('repeat_until') }}" @if($repeatMode === 'weekly') required @endif class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
            @error('repeat_until')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="block text-sm font-medium text-gray-700">Days &amp; times</span>
            <button type="button" id="add-slot-row" class="inline-flex items-center text-sm font-semibold text-blue-700 hover:text-blue-900">
                <i class="fas fa-plus mr-1"></i>Add another day
            </button>
        </div>
        <p class="text-xs text-gray-600 mt-1">For example 10:00 am Monday, 2:00 pm Wednesday and 6:00 pm Friday &mdash; each one repeats every week at its own time.</p>

        <div id="slot-rows" class="mt-3 space-y-3">
            @foreach($slotRows as $index => $row)
                @include('partials.schedule-slot-row', ['index' => $index, 'row' => $row])
            @endforeach
        </div>

        <template id="slot-row-template">
            @include('partials.schedule-slot-row', ['index' => '__INDEX__', 'row' => []])
        </template>

        @php
            // Day/time row errors arrive as "slots.0.day", so collect every
            // slot-related message and show them together under the rows.
            $slotErrors = collect($errors->getMessages())
                ->filter(function ($messages, $key) {
                    return $key === 'slots' || \Illuminate\Support\Str::startsWith($key, 'slots.');
                })
                ->flatten();
        @endphp

        @if($slotErrors->isNotEmpty())
            <ul class="mt-2 space-y-1">
                @foreach($slotErrors as $message)
                    <li class="text-xs text-orange-700">{{ $message }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const repeat = document.getElementById('schedule-repeat');
        const oneOff = document.getElementById('one-off-fields');
        const weekly = document.getElementById('weekly-fields');
        const rows = document.getElementById('slot-rows');
        const template = document.getElementById('slot-row-template');
        const addButton = document.getElementById('add-slot-row');

        // Continue numbering after any rows restored by old() input.
        let nextIndex = 0;
        if (rows) {
            rows.querySelectorAll('[data-slot-row]').forEach(function (row) {
                const field = row.querySelector('[name^="slots["]');
                const match = field ? field.name.match(/slots\[(\d+)\]/) : null;

                if (match) {
                    nextIndex = Math.max(nextIndex, parseInt(match[1], 10) + 1);
                }
            });
        }

        function slotFields() {
            return rows ? Array.prototype.slice.call(rows.querySelectorAll('select, input')) : [];
        }

        function sync() {
            const isWeekly = !!repeat && repeat.value === 'weekly';

            if (oneOff) {
                oneOff.classList.toggle('hidden', isWeekly);

                oneOff.querySelectorAll('input').forEach(function (field) {
                    field.required = !isWeekly;
                    field.disabled = isWeekly;
                });
            }

            if (weekly) {
                weekly.classList.toggle('hidden', !isWeekly);

                weekly.querySelectorAll('input, select').forEach(function (field) {
                    // Slot rows inside #slot-rows get their own required flag below.
                    if (!rows || !rows.contains(field)) {
                        field.disabled = !isWeekly;
                    }
                });
            }

            slotFields().forEach(function (field) {
                field.required = isWeekly;
                field.disabled = !isWeekly;
            });
        }

        function addRow() {
            if (!rows || !template) {
                return;
            }

            const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
            nextIndex += 1;

            rows.insertAdjacentHTML('beforeend', html);

            const added = rows.lastElementChild;
            const first = added ? added.querySelector('select, input') : null;
            if (first) {
                first.focus();
            }

            sync();
        }

        if (addButton) {
            addButton.addEventListener('click', addRow);
        }

        document.addEventListener('click', function (event) {
            const button = event.target.closest ? event.target.closest('.remove-slot-row') : null;

            if (!button || !rows || !rows.contains(button)) {
                return;
            }

            const row = button.closest('[data-slot-row]');

            // Always leave one row so the form still explains itself.
            if (row && rows.querySelectorAll('[data-slot-row]').length > 1) {
                row.remove();
            } else if (row) {
                row.querySelectorAll('select, input').forEach(function (field) {
                    field.value = '';
                });
            }

            sync();
        });

        if (repeat) {
            repeat.addEventListener('change', sync);
        }

        sync();
    });
</script>
@endpush
