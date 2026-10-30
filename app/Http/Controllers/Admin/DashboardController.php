<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Classes;
use App\Models\ClassSchedule;
use App\Models\Learner;
use App\Models\Tutor;
use App\Models\Payment;
use App\Models\Enquiry;
use App\Models\AssessmentSession;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // "A monthly view/count of classes per tutor" — the month is selectable.
        $month = $request->query('month');
        try {
            $monthStart = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : Carbon::now()->startOfMonth();
        } catch (\Exception $exception) {
            $monthStart = Carbon::now()->startOfMonth();
        }
        $monthEnd = $monthStart->copy()->endOfMonth();

        $tutorMonthly = Tutor::with('user')
            ->withCount(['schedules as month_sessions' => function ($query) use ($monthStart, $monthEnd) {
                $query->whereBetween('start_time', [$monthStart, $monthEnd]);
            }])
            ->get()
            ->sortByDesc('month_sessions')
            ->values();

        $monthlyTrend = collect(range(5, 0))->map(function ($monthsAgo) {
            $start = Carbon::now()->startOfMonth()->subMonths($monthsAgo);

            return [
                'label' => $start->format('M'),
                'count' => ClassSchedule::whereBetween('start_time', [$start, $start->copy()->endOfMonth()])->count(),
            ];
        });

        $data = [
            'totalUsers' => User::count(),
            'totalTutors' => Tutor::count(),
            'totalLearners' => Learner::count(),
            'totalClasses' => Classes::count(),
            'totalSchedules' => ClassSchedule::count(),
            // Payments are CEO-only, so only the CEO sees payment figures.
            'totalPayments' => $user->canManagePayments() ? Payment::count() : null,
            'unreadMessages' => $user->unreadMessagesCount(),
            'recentUsers' => User::with('role')->latest()->take(5)->get(),
            'upcomingClasses' => ClassSchedule::with('class', 'tutor.user')
                ->where('start_time', '>', now())
                ->orderBy('start_time')
                ->take(5)
                ->get(),
            'tutorMonthly' => $tutorMonthly,
            'monthStart' => $monthStart,
            'monthSessions' => (int) $tutorMonthly->sum('month_sessions'),
            'maxMonthSessions' => max(1, (int) $tutorMonthly->max('month_sessions')),
            'monthlyTrend' => $monthlyTrend,
            // Admissions (note 13) — enquiries captured before a learner exists.
            'newEnquiries' => Enquiry::where('status', Enquiry::STATUS_NEW)->count(),
            'contactedEnquiries' => Enquiry::where('status', Enquiry::STATUS_CONTACTED)->count(),
            'todayAssessments' => AssessmentSession::with(['enquiry', 'tutor.user'])
                ->whereDate('scheduled_at', today())
                ->where('status', AssessmentSession::STATUS_SCHEDULED)
                ->orderBy('scheduled_at')
                ->get(),
            'upcomingAssessments' => AssessmentSession::where('scheduled_at', '>', now()->endOfDay())
                ->where('status', AssessmentSession::STATUS_SCHEDULED)
                ->count(),
        ];

        return view('admin.dashboard', $data);
    }
}
