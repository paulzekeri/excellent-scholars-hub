@extends('layouts.app')
@section('title', 'Learning Goals Oversight')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Learning Goals</h1>
        <p class="text-gray-600 mt-1">Every goal written by tutors — share each one with individual parents, learners and tutors, or with everyone it is about.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-600">
            <p class="text-gray-500 text-sm">All goals</p>
            <p class="text-2xl font-bold text-gray-800">{{ $counts['total'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-400">
            <p class="text-gray-500 text-sm">Shared with parents</p>
            <p class="text-2xl font-bold text-gray-800">{{ $counts['parent'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-green-500">
            <p class="text-gray-500 text-sm">Shared with learners</p>
            <p class="text-2xl font-bold text-gray-800">{{ $counts['learner'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-orange-500">
            <p class="text-gray-500 text-sm">Shared with tutors</p>
            <p class="text-2xl font-bold text-gray-800">{{ $counts['tutor'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-sun">
            <p class="text-gray-500 text-sm">Chosen by name</p>
            <p class="text-2xl font-bold text-gray-800">{{ $counts['individual'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-gray-400">
            <p class="text-gray-500 text-sm">Awaiting review</p>
            <p class="text-2xl font-bold text-gray-800">{{ $counts['unshared'] }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.learning-goals.index') }}" class="bg-white rounded-xl shadow-md p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Tutor</label>
            <select name="tutor_id" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">All tutors</option>
                @foreach($tutors as $tutor)
                    <option value="{{ $tutor->id }}" {{ (string) $tutorId === (string) $tutor->id ? 'selected' : '' }}>{{ $tutor->user->name ?? 'Tutor #' . $tutor->id }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Sharing</label>
            <select name="shared" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Any</option>
                <option value="parent" {{ $shared === 'parent' ? 'selected' : '' }}>Shared with parent</option>
                <option value="learner" {{ $shared === 'learner' ? 'selected' : '' }}>Shared with learner</option>
                <option value="tutor" {{ $shared === 'tutor' ? 'selected' : '' }}>Shared with tutor</option>
                <option value="individual" {{ $shared === 'individual' ? 'selected' : '' }}>Shared with chosen people</option>
                <option value="unshared" {{ $shared === 'unshared' ? 'selected' : '' }}>Awaiting review</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-800 transition"><i class="fas fa-filter mr-1"></i> Filter</button>
            <a href="{{ route('admin.learning-goals.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Reset</a>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Goal</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Learner / class</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Tutor</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">File</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Share with</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Last shared</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($goals as $goal)
                        <tr class="hover:bg-blue-50/40 align-top">
                            <td class="px-6 py-4 text-sm max-w-sm">
                                @include('partials.goal-text', ['goal' => $goal, 'limit' => 220])
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $goal->learner->user->name ?? 'Whole class' }}
                                @if($goal->schoolClass)
                                    <span class="block text-xs text-gray-500">{{ $goal->schoolClass->name }}</span>
                                @endif
                                <span class="block text-xs text-gray-500">{{ $goal->term ?? 'No term' }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $goal->tutor->user->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm">
                                @if($goal->file_path)
                                    <a href="{{ route('admin.learning-goals.download', $goal) }}" class="text-orange-700 hover:text-orange-800"><i class="fas fa-download mr-1"></i> File</a>
                                @else
                                    <span class="text-gray-500 text-xs">None</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top">
                                @php $recipients = $goal->recipientsByRole(); @endphp
                                <details class="min-w-[220px] text-xs">
                                    <summary class="cursor-pointer font-semibold text-blue-700">Choose people</summary>
                                    <form action="{{ route('admin.learning-goals.share', $goal) }}" method="POST" class="mt-2 space-y-2">
                                        @csrf
                                        <input type="hidden" name="share_form" value="1">

                                        @foreach([
                                            ['label' => 'Parents', 'one' => 'parent', 'field' => 'parent_ids', 'all' => 'share_all_parent', 'options' => $parents, 'role' => 'parent', 'all_ticked' => $goal->sharesWithAll('parent')],
                                            ['label' => 'Learners', 'one' => 'learner', 'field' => 'learner_ids', 'all' => 'share_all_learner', 'options' => $learners, 'role' => 'learner', 'all_ticked' => $goal->sharesWithAll('learner')],
                                            ['label' => 'Tutors', 'one' => 'tutor', 'field' => 'tutor_ids', 'all' => 'share_all_tutor', 'options' => $tutorUsers, 'role' => 'tutor', 'all_ticked' => $goal->sharesWithAll('tutor')],
                                        ] as $group)
                                            <div>
                                                <p class="font-semibold text-gray-700">{{ $group['label'] }}</p>
                                                <select name="{{ $group['field'] }}[]" multiple size="3"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-2 py-1 focus:ring-blue-500 focus:border-blue-500">
                                                    @forelse($group['options'] as $person)
                                                        <option value="{{ $person->id }}"
                                                            {{ collect($recipients[$group['role']] ?? [])->contains('id', $person->id) ? 'selected' : '' }}>
                                                            {{ $person->name }}
                                                        </option>
                                                    @empty
                                                        <option disabled>No {{ strtolower($group['label']) }} yet</option>
                                                    @endforelse
                                                </select>
                                                <label class="mt-1 flex items-center gap-1 text-[11px] text-gray-600">
                                                    <input type="hidden" name="{{ $group['all'] }}" value="0">
                                                    <input type="checkbox" name="{{ $group['all'] }}" value="1" {{ $group['all_ticked'] ? 'checked' : '' }}>
                                                    <span>Every {{ $group['one'] }} the goal is about</span>
                                                </label>
                                            </div>
                                        @endforeach

                                        <button type="submit" class="bg-blue-700 text-white px-3 py-1 rounded hover:bg-blue-800 transition">Save sharing</button>
                                    </form>
                                </details>

                                @if($goal->isShared())
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach(['parent' => 'bg-blue-100 text-blue-800', 'learner' => 'bg-green-100 text-green-800', 'tutor' => 'bg-orange-100 text-orange-800'] as $roleKey => $chip)
                                            @foreach(($recipients[$roleKey] ?? collect()) as $person)
                                                <span class="rounded-full px-2 py-0.5 text-[11px] {{ $chip }}" title="{{ ucfirst($roleKey) }}">{{ $person->name }}</span>
                                            @endforeach
                                        @endforeach
                                        @if($goal->sharesWithAll('parent'))<span data-share-chip="all-parent" class="rounded-full bg-blue-50 text-blue-700 px-2 py-0.5 text-[11px]">All parents</span>@endif
                                        @if($goal->sharesWithAll('learner'))<span data-share-chip="all-learner" class="rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-[11px]">All learners</span>@endif
                                        @if($goal->sharesWithAll('tutor'))<span data-share-chip="all-tutor" class="rounded-full bg-orange-50 text-orange-700 px-2 py-0.5 text-[11px]">All tutors</span>@endif
                                    </div>
                                @else
                                    <p class="mt-1 text-[11px] text-gray-500">Not shared</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500">
                                @if($goal->shared_at)
                                    {{ $goal->shared_at->format('M d, Y') }}
                                    @if($goal->sharedBy)
                                        <span class="block">by {{ $goal->sharedBy->name }}</span>
                                    @endif
                                @else
                                    Not shared
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">No learning goals have been uploaded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $goals->links() }}</div>
    </div>
</div>
@endsection