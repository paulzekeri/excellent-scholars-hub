<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\ClassLibraryResource;

/**
 * My Learning → Class Library: the links the school and their tutors attached to
 * the classes this learner is enrolled in. Only links for their own classes are
 * fetched, and the URL is never printed — the title is the clickable link.
 */
class ClassLibraryController extends Controller
{
    public function index()
    {
        $learner = auth()->user()->learner;
        $classIds = $learner->classes()->pluck('classes.id');

        $resources = ClassLibraryResource::with(['class', 'addedBy'])
            ->whereIn('class_id', $classIds)
            ->orderByDesc('created_at')
            ->get();

        return view('learner.class-library', [
            'resources' => $resources,
        ]);
    }
}
