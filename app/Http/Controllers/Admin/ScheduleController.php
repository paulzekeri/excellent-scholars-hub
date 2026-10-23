<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Classes;
use App\Models\Tutor;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = ClassSchedule::with('class', 'tutor.user')
            ->orderBy('start_time')
            ->paginate(10);
        return view('admin.schedules.index', compact('schedules'));
    }

    public function create()
    {
        $classes = Classes::with('tutor.user')->where('is_active', true)->get();
        $tutors = Tutor::with('user')->where('is_active', true)->get();
        return view('admin.schedules.create', compact('classes', 'tutors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'tutor_id' => 'required|exists:tutors,id',
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'meeting_link' => 'nullable|url',
            'meeting_platform' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        ClassSchedule::create($request->all());

        return redirect()->route('admin.schedules.index')->with('success', 'Schedule created successfully.');
    }

    public function edit(ClassSchedule $schedule)
    {
        $classes = Classes::where('is_active', true)->get();
        $tutors = Tutor::with('user')->where('is_active', true)->get();
        return view('admin.schedules.edit', compact('schedule', 'classes', 'tutors'));
    }

    public function update(Request $request, ClassSchedule $schedule)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'tutor_id' => 'required|exists:tutors,id',
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'meeting_link' => 'nullable|url',
            'meeting_platform' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $schedule->update($request->all());

        return redirect()->route('admin.schedules.index')->with('success', 'Schedule updated successfully.');
    }

    public function destroy(ClassSchedule $schedule)
    {
        $schedule->delete();
        return redirect()->route('admin.schedules.index')->with('success', 'Schedule deleted successfully.');
    }
}
