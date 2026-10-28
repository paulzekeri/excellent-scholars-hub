<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ClassController as AdminClassController;
use App\Http\Controllers\Admin\ScheduleController as AdminScheduleController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PaymentAccountController as AdminPaymentAccountController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\OversightController as AdminOversightController;
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\Admin\LearningGoalController as AdminLearningGoalController;
use App\Http\Controllers\Tutor\LearningGoalController as TutorLearningGoalController;
use App\Http\Controllers\Tutor\DashboardController as TutorDashboard;
use App\Http\Controllers\Tutor\ScheduleController as TutorScheduleController;
use App\Http\Controllers\Tutor\AttendanceController as TutorAttendanceController;
use App\Http\Controllers\Tutor\AssignmentController as TutorAssignmentController;
use App\Http\Controllers\Tutor\MaterialController as TutorMaterialController;
use App\Http\Controllers\Parent\DashboardController as ParentDashboard;
use App\Http\Controllers\Parent\PaymentController as ParentPaymentController;
use App\Http\Controllers\Learner\DashboardController as LearnerDashboard;
use App\Http\Controllers\Learner\ClassController as LearnerClassController;
use App\Http\Controllers\Learner\AssignmentController as LearnerAssignmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MessageController;
use App\Models\Notification;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->isAdministrator()) return redirect()->route('admin.dashboard');
        if ($user->isTutor()) return redirect()->route('tutor.dashboard');
        if ($user->isParent()) return redirect()->route('parent.dashboard');
        if ($user->isLearner()) return redirect()->route('learner.dashboard');
    }

    // Public marketing page: sliders, programmes, testimonials, about, register.
    return view('landing', [
        'programmes' => \App\Models\Classes::with('tutor.user')
            ->where('is_active', true)
            ->orderBy('name')
            ->take(6)
            ->get(),
        'stats' => [
            'classes' => \App\Models\Classes::where('is_active', true)->count(),
            'tutors' => \App\Models\Tutor::count(),
            'learners' => \App\Models\Learner::count(),
            'parents' => \App\Models\ParentModel::count(),
        ],
    ]);
});

Auth::routes(['verify' => false, 'reset' => true, 'confirm' => false]);

// Shared notification routes
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    
    // User Guide
    Route::get('/help/user-guide', function () {
        return view('help.user-guide');
    })->name('help.user-guide');

    // In-app messaging (parents <-> CEO / administrators)
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{user}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{user}', [MessageController::class, 'store'])->name('messages.store');
});

// Admin Routes (Administrators and the CEO share this area)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,ceo'])->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');
    
    Route::resource('users', AdminUserController::class);
    Route::post('/users/{user}/password-reset', [AdminUserController::class, 'sendPasswordReset'])->name('users.password-reset');
    Route::resource('classes', AdminClassController::class);
    Route::post('/classes/{class}/enroll', [AdminClassController::class, 'enroll'])->name('classes.enroll');
    Route::delete('/classes/{class}/unenroll/{learner}', [AdminClassController::class, 'unenroll'])->name('classes.unenroll');

    Route::resource('schedules', AdminScheduleController::class);
    
    Route::resource('notifications', AdminNotificationController::class)->only(['index', 'create', 'store', 'destroy']);
    
    // Admin Oversight Routes - View attendance, assignments, materials across all classes
    Route::get('/attendance', [AdminOversightController::class, 'attendance'])->name('oversight.attendance');
    Route::get('/attendance/class/{classId}', [AdminOversightController::class, 'attendanceByClass'])->name('oversight.attendance.class');
    Route::get('/assignments', [AdminOversightController::class, 'assignments'])->name('oversight.assignments');
    Route::get('/assignments/class/{classId}', [AdminOversightController::class, 'assignmentsByClass'])->name('oversight.assignments.class');
    Route::get('/assignments/{assignment}/submissions', [AdminOversightController::class, 'submissions'])->name('oversight.assignments.submissions');
    Route::post('/submissions/{submission}/grade', [AdminOversightController::class, 'gradeSubmission'])->name('oversight.submissions.grade');
    Route::get('/submissions/{submission}/download', [AdminOversightController::class, 'downloadSubmission'])->name('oversight.submissions.download');
    Route::get('/materials', [AdminOversightController::class, 'materials'])->name('oversight.materials');
    Route::get('/materials/class/{classId}', [AdminOversightController::class, 'materialsByClass'])->name('oversight.materials.class');

    // Categorised messaging (parents / tutors / students / one class)
    Route::get('/messages', [AdminMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/broadcast', [AdminMessageController::class, 'create'])->name('messages.create');
    Route::post('/messages/broadcast', [AdminMessageController::class, 'store'])->name('messages.store');

    // Learning goals uploaded by tutors, shared with parents/tutors by the school
    Route::get('/learning-goals', [AdminLearningGoalController::class, 'index'])->name('learning-goals.index');
    Route::post('/learning-goals/{goal}/share', [AdminLearningGoalController::class, 'share'])->name('learning-goals.share');
    Route::get('/learning-goals/{goal}/download', [AdminLearningGoalController::class, 'download'])->name('learning-goals.download');
});

// Payments: parents record what they paid, the CEO verifies it against the bank.
// Regular administrators cannot view, verify or download payments.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:ceo'])->group(function () {
    Route::resource('payments', AdminPaymentController::class)->only(['index', 'show']);
    Route::post('/payments/{payment}/verify', [AdminPaymentController::class, 'verify'])->name('payments.verify');
    Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('payments.reject');
    Route::get('/payments/{payment}/receipt', [AdminPaymentController::class, 'receipt'])->name('payments.receipt');

    // The school bank accounts parents transfer into (add / edit / disable / delete).
    Route::get('/payment-accounts', [AdminPaymentAccountController::class, 'index'])->name('payment-accounts.index');
    Route::post('/payment-accounts', [AdminPaymentAccountController::class, 'store'])->name('payment-accounts.store');
    Route::patch('/payment-accounts/{paymentAccount}', [AdminPaymentAccountController::class, 'update'])->name('payment-accounts.update');
    Route::patch('/payment-accounts/{paymentAccount}/toggle', [AdminPaymentAccountController::class, 'toggle'])->name('payment-accounts.toggle');
    Route::delete('/payment-accounts/{paymentAccount}', [AdminPaymentAccountController::class, 'destroy'])->name('payment-accounts.destroy');
});

// Tutor Routes
Route::prefix('tutor')->name('tutor.')->middleware(['auth', 'role:tutor'])->group(function () {
    Route::get('/dashboard', [TutorDashboard::class, 'index'])->name('dashboard');
    
    Route::resource('schedules', TutorScheduleController::class)->only(['index', 'create', 'store']);
    Route::post('/schedules/{schedule}/complete', [TutorScheduleController::class, 'markCompleted'])->name('schedules.complete');
    
    Route::get('/schedules/{schedule}/attendance', [TutorAttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/schedules/{schedule}/attendance', [TutorAttendanceController::class, 'store'])->name('attendance.store');
    
    Route::resource('assignments', TutorAssignmentController::class)->only(['index', 'create', 'store']);
    Route::get('/assignments/{assignment}/submissions', [TutorAssignmentController::class, 'submissions'])->name('assignments.submissions');
    Route::post('/submissions/{submission}/grade', [TutorAssignmentController::class, 'grade'])->name('submissions.grade');
    Route::get('/submissions/{submission}/download', [TutorAssignmentController::class, 'downloadFile'])->name('submissions.download');
    
    Route::resource('materials', TutorMaterialController::class)->only(['index', 'create', 'store', 'destroy']);

    // Learning goals (visible to administrators, then shared with parents)
    Route::get('/learning-goals', [TutorLearningGoalController::class, 'index'])->name('learning-goals.index');
    Route::get('/learning-goals/create', [TutorLearningGoalController::class, 'create'])->name('learning-goals.create');
    Route::post('/learning-goals', [TutorLearningGoalController::class, 'store'])->name('learning-goals.store');
    Route::get('/learning-goals/{goal}/download', [TutorLearningGoalController::class, 'download'])->name('learning-goals.download');
    Route::delete('/learning-goals/{goal}', [TutorLearningGoalController::class, 'destroy'])->name('learning-goals.destroy');
});

// Parent Routes
Route::prefix('parent')->name('parent.')->middleware(['auth', 'role:parent'])->group(function () {
    Route::get('/dashboard', [ParentDashboard::class, 'index'])->name('dashboard');
    Route::get('/child/{learner}/progress', [ParentDashboard::class, 'childProgress'])->name('child.progress');

    // Fees: parents record their own payments; the CEO verifies them.
    Route::get('/payments', [ParentPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/create', [ParentPaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [ParentPaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{payment}/receipt', [ParentPaymentController::class, 'receipt'])->name('payments.receipt');
});

// Learner Routes
Route::prefix('learner')->name('learner.')->middleware(['auth', 'role:learner'])->group(function () {
    Route::get('/dashboard', [LearnerDashboard::class, 'index'])->name('dashboard');
    Route::get('/classes', [LearnerClassController::class, 'index'])->name('classes');
    Route::get('/class/{schedule}/join', [LearnerDashboard::class, 'joinClass'])->name('class.join');
    Route::get('/materials', [LearnerClassController::class, 'materials'])->name('materials');
    Route::get('/assignments', [LearnerAssignmentController::class, 'index'])->name('assignments');
    Route::get('/assignments/{assignment}/submit', [LearnerAssignmentController::class, 'submit'])->name('assignments.submit');
    Route::post('/assignments/{assignment}/submit', [LearnerAssignmentController::class, 'store'])->name('assignments.submit.store');
});
