@extends('layouts.app')
@section('title', 'Learning Goals')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Learning Goals</h1>
            <p class="text-gray-600 mt-1">Write goals for your learners. Administrators and the CEO share them with individual parents, learners and tutors.</p>
        </div>
        <a href="{{ route('tutor.learning-goals.create') }}" class="bg-blue-700 text-white px-6 py-2 rounded-lg hover:bg-blue-800 transition"><i class="fas fa-plus mr-1"></i> New goal</a>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <div class="px-6 py-4 border-b"><h2 class="text-lg font-semibold text-gray-800">My goals</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Goal</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Learner / class</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Term</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">File</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Sharing</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($myGoals as $goal)
                        <tr class="hover:bg-blue-50/40">
                            <td class="px-6 py-4 text-sm">
                                <p class="font-medium text-gray-800 whitespace-pre-line">{{ $goal->goalExcerpt(220) }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $goal->learner->user->name ?? 'General goal' }}
                                @if($goal->schoolClass)
                                    <span class="block text-xs text-gray-500">{{ $goal->schoolClass->name }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $goal->term ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm">
                                @if($goal->file_path)
                                    <a href="{{ route('tutor.learning-goals.download', $goal) }}" class="text-orange-700 hover:text-orange-800"><i class="fas fa-download mr-1"></i> File</a>
                                @else
                                    <span class="text-gray-500 text-xs">None</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs">
                                @if($goal->shared_with_parent)
                                    <span class="px-2 py-1 rounded-full bg-blue-100 text-blue-800">Parent</span>
                                @endif
                                @if($goal->shared_with_learner)
                                    <span class="px-2 py-1 rounded-full bg-green-100 text-green-800">Learner</span>
                                @endif
                                @if($goal->shared_with_tutor)
                                    <span class="px-2 py-1 rounded-full bg-orange-100 text-orange-800">Tutor</span>
                                @endif
                                @if($goal->recipients->isNotEmpty())
                                    <span class="mt-1 block text-gray-500">{{ $goal->recipients->count() }} {{ \Illuminate\Support\Str::plural('person', $goal->recipients->count()) }} by name</span>
                                @endif
                                @unless($goal->isShared())
                                    <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-600">Pending review</span>
                                @endunless
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('tutor.learning-goals.destroy', $goal) }}" method="POST" onsubmit="return confirm('Remove this learning goal?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-orange-700 hover:text-orange-900 text-sm"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">No goals uploaded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $myGoals->links() }}</div>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">Shared with you by the school</h2>
            <p class="text-sm text-gray-500">Goals the administration chose to share with tutors.</p>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($sharedGoals as $goal)
                <div class="p-6 flex flex-wrap justify-between gap-3">
                    <div>
                        <p class="font-medium text-gray-800 whitespace-pre-line">{{ $goal->goalExcerpt(300) }}</p>
                        <p class="text-sm text-gray-600">{{ $goal->contextLabel() }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            Shared {{ $goal->shared_at ? $goal->shared_at->format('M d, Y') : '' }}
                            @if($goal->sharedBy) by {{ $goal->sharedBy->name }} @endif
                        </p>
                    </div>
                    @if($goal->file_path)
                        <a href="{{ route('tutor.learning-goals.download', $goal) }}" class="text-orange-700 hover:text-orange-800 text-sm self-start"><i class="fas fa-download mr-1"></i> Download</a>
                    @endif
                </div>
            @empty
                <p class="p-6 text-sm text-gray-500">Nothing has been shared with you yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
