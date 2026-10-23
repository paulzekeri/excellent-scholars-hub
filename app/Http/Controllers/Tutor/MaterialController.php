<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\Classes;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index()
    {
        $tutor = auth()->user()->tutor;
        $materials = LearningMaterial::where('tutor_id', $tutor->id)
            ->with('class')
            ->latest()
            ->paginate(10);
        return view('tutor.materials.index', compact('materials'));
    }

    public function create()
    {
        $tutor = auth()->user()->tutor;
        $classes = Classes::where('tutor_id', $tutor->id)->where('is_active', true)->get();
        return view('tutor.materials.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $tutor = auth()->user()->tutor;
        
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|max:102400',
        ]);

        $file = $request->file('file');
        $path = $file->store('materials', 'public');

        LearningMaterial::create([
            'class_id' => $request->class_id,
            'tutor_id' => $tutor->id,
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $path,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return redirect()->route('tutor.materials.index')->with('success', 'Material uploaded successfully.');
    }

    public function destroy(LearningMaterial $material)
    {
        $material->delete();
        return redirect()->back()->with('success', 'Material deleted successfully.');
    }
}
