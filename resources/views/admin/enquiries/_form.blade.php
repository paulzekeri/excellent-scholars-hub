@php
    $selectedSource = old('source', $enquiry->source ?: \App\Models\Enquiry::SOURCE_WHATSAPP);
    $selectedOwner = old('assigned_to', $enquiry->assigned_to ?: auth()->id());
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="md:col-span-2 border-b border-gray-200 pb-2">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">The child</h2>
    </div>

    <div>
        <label for="child_name" class="block text-sm font-medium text-gray-700">Child's name <span class="text-red-500">*</span></label>
        <input id="child_name" type="text" name="child_name" value="{{ old('child_name', $enquiry->child_name) }}" required
            class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <div>
        <label for="child_grade_level" class="block text-sm font-medium text-gray-700">Class / year group</label>
        <input id="child_grade_level" type="text" name="child_grade_level" value="{{ old('child_grade_level', $enquiry->child_grade_level) }}"
            class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"
            placeholder="e.g. JSS1">
    </div>

    <div>
        <label for="child_dob" class="block text-sm font-medium text-gray-700">Date of birth</label>
        <input id="child_dob" type="date" name="child_dob" value="{{ old('child_dob', optional($enquiry->child_dob)->format('Y-m-d')) }}"
            class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <div>
        <label for="source" class="block text-sm font-medium text-gray-700">Where did they come from? <span class="text-red-500">*</span></label>
        <select id="source" name="source" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
            @foreach(\App\Models\Enquiry::SOURCES as $value => $label)
                <option value="{{ $value }}" {{ $selectedSource === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="md:col-span-2 border-b border-gray-200 pb-2 pt-2">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Parent / guardian</h2>
    </div>

    <div>
        <label for="guardian_name" class="block text-sm font-medium text-gray-700">Guardian's name <span class="text-red-500">*</span></label>
        <input id="guardian_name" type="text" name="guardian_name" value="{{ old('guardian_name', $enquiry->guardian_name) }}" required
            class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <div>
        <label for="guardian_phone" class="block text-sm font-medium text-gray-700">Phone (WhatsApp) <span class="text-red-500">*</span></label>
        <input id="guardian_phone" type="text" name="guardian_phone" value="{{ old('guardian_phone', $enquiry->guardian_phone) }}" required
            class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"
            placeholder="e.g. 0803 123 4567">
    </div>

    <div>
        <label for="guardian_email" class="block text-sm font-medium text-gray-700">Email (optional)</label>
        <input id="guardian_email" type="email" name="guardian_email" value="{{ old('guardian_email', $enquiry->guardian_email) }}"
            class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
        <p class="text-xs text-gray-500 mt-1">Needed before the child can be converted into a learner account.</p>
    </div>

    <div>
        <label for="assigned_to" class="block text-sm font-medium text-gray-700">Who is following up?</label>
        <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
            @foreach($owners as $owner)
                <option value="{{ $owner->id }}" {{ (string) $selectedOwner === (string) $owner->id ? 'selected' : '' }}>{{ $owner->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="md:col-span-2">
        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea id="notes" name="notes" rows="3"
            class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500"
            placeholder="What do they need? Any schedule constraints?">{{ old('notes', $enquiry->notes) }}</textarea>
    </div>
</div>
