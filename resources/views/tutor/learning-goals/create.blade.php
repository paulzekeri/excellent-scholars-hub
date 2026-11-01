@extends('layouts.app')
@section('title', 'New Learning Goal')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('tutor.learning-goals.index') }}" class="text-blue-700 hover:text-blue-900 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">New learning goal</h1>
        <p class="text-sm text-gray-600 mb-6">Administrators and the CEO see every goal and decide which individual parents, learners and tutors receive it.</p>

        <form action="{{ route('tutor.learning-goals.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">@csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Goal <span class="text-orange-700">*</span></label>
                <textarea name="title" rows="8" required maxlength="5000" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500" placeholder="Write the goal in full — for example: Read one chapter aloud each week and explain the main idea in your own words.">{{ old('title') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Up to 5,000 characters — enough for the whole goal, not just a heading.</p>

                @if($wordingAssistant)
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <button type="button" id="polish-goal" class="inline-flex items-center rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-800 hover:bg-blue-100 transition">
                            <i class="fas fa-wand-magic-sparkles mr-1"></i> Improve wording (free AI)
                        </button>
                        <span class="text-xs text-gray-500">Spelling and grammar checked by LanguageTool — review the result before saving.</span>
                    </div>
                    <p id="polish-result" class="mt-2 hidden rounded-lg px-3 py-2 text-xs"></p>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Class</label>
                    <select name="class_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All classes / general goal</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Optional — the school chooses exactly who receives the goal.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Term</label>
                    <input type="text" name="term" value="{{ old('term') }}" list="goal-terms" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g. First Term">
                    <datalist id="goal-terms">
                        <option value="First Term"><option value="Second Term"><option value="Third Term">
                    </datalist>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Attach a file (optional)</label>
                <input type="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">PDF, Word or image, up to 8 MB — for example a learning plan or a sample of work.</p>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-700 text-white px-6 py-3 rounded-lg hover:bg-blue-800 transition"><i class="fas fa-save mr-1"></i> Save goal</button>
                <a href="{{ route('tutor.learning-goals.index') }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-200 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Free wording assistant: asks the school's server, which calls LanguageTool.
    document.addEventListener('DOMContentLoaded', function () {
        const button = document.getElementById('polish-goal');
        const goal = document.querySelector('textarea[name="title"]');
        const result = document.getElementById('polish-result');

        if (!button || !goal || !result) return;

        function show(message, ok) {
            result.textContent = message;
            result.classList.remove('hidden');
            result.className = 'mt-2 rounded-lg px-3 py-2 text-xs ' + (ok
                ? 'bg-green-50 text-green-800 border border-green-200'
                : 'bg-orange-50 text-orange-800 border border-orange-200');
        }

        button.addEventListener('click', function () {
            if (!goal.value.trim()) {
                show('Write the goal first, then ask for wording help.', false);
                return;
            }

            const original = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Checking…';

            fetch('{{ route('tutor.learning-goals.polish') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({ text: goal.value }),
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    button.disabled = false;
                    button.innerHTML = original;

                    if (data.ok) {
                        goal.value = data.corrected;
                    }

                    show(data.message || 'Wording checked.', data.ok);
                })
                .catch(function () {
                    button.disabled = false;
                    button.innerHTML = original;
                    show('The wording assistant could not be reached. Please try again.', false);
                });
        });
    });
</script>
@endpush
