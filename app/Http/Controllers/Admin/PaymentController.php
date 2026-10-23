<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Models\Learner;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with('user', 'learner.user')
            ->latest()
            ->paginate(10);
        return view('admin.payments.index', compact('payments'));
    }

    public function create()
    {
        $learners = Learner::with('user')->where('is_active', true)->get();
        return view('admin.payments.create', compact('learners'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'learner_id' => 'nullable|exists:learners,id',
            'payment_type' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'reference' => 'nullable|string|unique:payments',
            'status' => 'required|string',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        Payment::create($request->all());

        return redirect()->route('admin.payments.index')->with('success', 'Payment recorded successfully.');
    }

    public function show(Payment $payment)
    {
        return view('admin.payments.show', compact('payment'));
    }
}
