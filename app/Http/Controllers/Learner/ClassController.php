<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\LearningMaterial;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:learner');
    }

    public function index()
    {
        $learner = auth()->user()->learner;
        $classes = $learner->classes()->with('schedules')->get();
        return view('learner.classes', compact('classes'));
    }

    public function materials()
    {
        $learner = auth()->user()->learner;
        $classIds = $learner->classes()->pluck('classes.id');
        $materials = LearningMaterial::whereIn('class_id', $classIds)->latest()->paginate(12);
        return view('learner.materials', compact('materials'));
    }
}
