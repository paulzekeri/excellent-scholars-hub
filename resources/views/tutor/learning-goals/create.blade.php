@extends('layouts.app')
@section('title', 'New Learning Goal')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('tutor.learning-goals.index') }}" class="text-blue-700 hover:text-blue-900 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">New learning goal</h1>
        <p class="text-sm text-gray-600 mb-6">Administrators and the CEO see every goal and decide what to share with the parent.</p>

        <form action="{{ route('tutor.learning-goals.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">@csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Goal <span class="text-orange-700">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required maxlength="255" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g. Read one chapter aloud each week">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Learner</label>
                    <select name="learner_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Whole class</option>
                        @foreach($learners as $learner)
                            <option value="{{ $learner->id }}" {{ old('learner_id') == $learner->id ? 'selected' : '' }}>{{ $learner->user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Class</label>
                    <select name="class_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Not class-specific</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Choose a learner, a class, or both.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Term</label>
                    <input type="text" name="term" value="{{ old('term') }}" list="goal-terms" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g. First Term">
                    <datalist id="goal-terms">
                        <option value="First Term"><option value="Second Term"><option value="Third Term">
                    </datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">What success looks like</label>
                    <input type="text" name="target" value="{{ old('target') }}" maxlength="255" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g. 4 out of 5 weekly reads completed">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="description" rows="4" maxlength="2000" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500" placeholder="How you plan to support the learner">{{ old('description') }}</textarea>
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
