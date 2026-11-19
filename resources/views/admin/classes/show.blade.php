@extends('layouts.app')
@section('title', 'Class Details - ' . $class->name)
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.classes.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back to Classes</a>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-orange-100 border border-orange-400 text-orange-800 px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
    @endif

    <!-- Class Info -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">{{ $class->name }}</h1>
                <p class="text-gray-600 mt-1">Code: <strong>{{ $class->code }}</strong></p>
                <p class="text-gray-600">Subject: {{ $class->subjectSummary() }} | Grade: {{ $class->grade_level ?? 'All' }}</p>
                <p class="text-gray-600">Lead Tutor: <strong>{{ $class->tutor->user->name ?? 'N/A' }}</strong></p>
                <p class="text-gray-500 text-sm mt-2">{{ $class->description }}</p>
            </div>
            <div class="text-right">
                <p class="text-3xl font-bold text-blue-600">{{ $class->learners->count() }} / {{ $class->max_students ?? 'No limit' }}</p>
                <p class="text-sm text-gray-500">Students Enrolled</p>
            </div>
        </div>
    </div>

    <!-- Subjects & Teachers: a class teaches several subjects, each with its own teacher -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <div class="px-6 py-4 bg-gray-50 border-b flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-semibold text-gray-800">Subjects &amp; Teachers ({{ $class->subjectAssignments->count() }})</h2>
            <a href="{{ route('admin.classes.edit', $class) }}" class="text-sm text-blue-600 hover:text-blue-800"><i class="fas fa-pen mr-1"></i> Edit all subjects</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Teacher</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($class->subjectAssignments as $assignment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium">{{ $assignment->subject->name ?? 'Subject' }}</td>
                        <td class="px-6 py-4 text-sm">
                            {{ $assignment->tutor->user->name ?? 'Not assigned yet' }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <form action="{{ route('admin.classes.subjects.destroy', [$class, $assignment]) }}" method="POST" class="inline" onsubmit="return confirm('Remove this subject from the class?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-orange-700 hover:text-orange-900 text-xs"><i class="fas fa-trash mr-1"></i> Remove</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-6 py-6 text-center text-gray-500">No subjects added yet. Use the form below to add the first one.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-5 border-t bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-800 mb-3">Add a subject</h3>
            <form action="{{ route('admin.classes.subjects.store', $class) }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:items-end">
                    <div>
                        <label for="new-subject" class="block text-sm font-medium text-gray-700">Subject</label>
                        <select id="new-subject" name="subject_id"
                            class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">— Choose a subject —</option>
                            @foreach($subjects as $subject)
                                @if($subject->is_active)
                                    <option value="{{ $subject->id }}" {{ (string) old('subject_id') === (string) $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                                @endif
                            @endforeach
                            <option value="__new__" {{ old('subject_id') === '__new__' ? 'selected' : '' }}>+ Add a new subject…</option>
                        </select>
                    </div>
                    <div>
                        <label for="new-subject-tutor" class="block text-sm font-medium text-gray-700">Teacher</label>
                        <select id="new-subject-tutor" name="tutor_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Not assigned yet</option>
                            @foreach($tutors as $tutor)
                                <option value="{{ $tutor->id }}" {{ (string) old('tutor_id') === (string) $tutor->id ? 'selected' : '' }}>{{ $tutor->user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="w-full md:w-auto bg-blue-600 text-white px-5 py-3 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-plus mr-1"></i> Add subject</button>
                    </div>
                </div>
                <div id="new-subject-name-wrap" class="mt-4 hidden">
                    <label for="new-subject-name" class="block text-sm font-medium text-gray-700">New subject name</label>
                    <input id="new-subject-name" type="text" name="subject" value="{{ old('subject') }}" placeholder="e.g. Biology"
                        class="mt-1 block w-full md:max-w-md border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">It is added to the subject list automatically.</p>
                </div>
                @error('subject_id')<p class="mt-2 text-xs text-orange-700">{{ $message }}</p>@enderror
                @error('subject')<p class="mt-2 text-xs text-orange-700">{{ $message }}</p>@enderror
            </form>
            <p class="text-xs text-gray-500 mt-2">
                Adding a subject the class already teaches updates its teacher.
                <a href="{{ route('admin.subjects.index') }}" class="text-blue-600 hover:text-blue-800">Manage the subject list</a>.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Enrolled Learners List -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">Enrolled Learners ({{ $class->learners->count() }})</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-[#EAF2FB]">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Grade</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($class->learners as $learner)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium">{{ $learner->user->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $learner->user->email }}</td>
                                <td class="px-6 py-4 text-sm">{{ $learner->grade_level ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <form action="{{ route('admin.classes.unenroll', [$class, $learner]) }}" method="POST" class="inline" onsubmit="return confirm('Unenroll this learner?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-orange-700 hover:text-orange-900 text-xs"><i class="fas fa-user-minus mr-1"></i> Remove</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">No learners enrolled yet. Use the form on the right to enroll learners.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Enroll Form -->
        <div>
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Enroll a Learner</h2>
                @if($class->max_students === null || $class->learners->count() < $class->max_students)
                <form action="{{ route('admin.classes.enroll', $class) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Learner</label>
                        <select name="learner_id" required class="block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Choose a learner --</option>
                            @foreach($learners as $learner)
                                @unless($class->learners->contains('id', $learner->id))
                                <option value="{{ $learner->id }}">{{ $learner->user->name }} ({{ $learner->user->email }})</option>
                                @endunless
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-user-plus mr-1"></i> Enroll Learner</button>
                </form>
                @else
                <div class="bg-sun-light border border-sun-edge text-sun-ink px-4 py-3 rounded-lg">
                    <i class="fas fa-exclamation-triangle mr-1"></i> This class has reached maximum capacity ({{ $class->max_students }} students).
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('new-subject');
        const wrap = document.getElementById('new-subject-name-wrap');
        const input = document.getElementById('new-subject-name');

        if (!select || !wrap || !input) {
            return;
        }

        function sync() {
            const isNew = select.value === '__new__';

            wrap.classList.toggle('hidden', !isNew);
            input.disabled = !isNew;
            input.required = isNew;

            if (!isNew) {
                input.value = '';
            }
        }

        select.addEventListener('change', sync);
        sync();
    });
</script>
@endpush
