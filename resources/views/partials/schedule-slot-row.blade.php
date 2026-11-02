@php
    $rowDay = (string) ($row['day'] ?? '');
    $rowStart = (string) ($row['start'] ?? '');
    $rowEnd = (string) ($row['end'] ?? '');
    $rowRequired = ($repeatMode ?? 'none') === 'weekly' ? 'required' : '';
@endphp
<div class="slot-row flex flex-col gap-3 rounded-lg bg-white/70 p-3 sm:flex-row sm:items-end" data-slot-row>
    <div class="sm:w-48">
        <label class="block text-xs font-medium text-gray-600">Day</label>
        <select name="slots[{{ $index }}][day]" {{ $rowRequired }} class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">Choose a day</option>
            @foreach($weekdays as $number => $label)
                <option value="{{ $number }}" {{ $rowDay === (string) $number ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="sm:w-36">
        <label class="block text-xs font-medium text-gray-600">Starts</label>
        <input type="time" name="slots[{{ $index }}][start]" value="{{ $rowStart }}" {{ $rowRequired }} class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div class="sm:w-36">
        <label class="block text-xs font-medium text-gray-600">Ends</label>
        <input type="time" name="slots[{{ $index }}][end]" value="{{ $rowEnd }}" {{ $rowRequired }} class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <button type="button" class="remove-slot-row inline-flex items-center justify-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-orange-700 hover:bg-orange-50 hover:text-orange-900" title="Remove this day">
        <i class="fas fa-times"></i><span class="sm:hidden">Remove day</span>
    </button>
</div>
