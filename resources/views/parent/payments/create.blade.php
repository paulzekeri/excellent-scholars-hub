@extends('layouts.app')

@section('title', 'Record a Payment')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('parent.payments.index') }}" class="text-blue-700 hover:text-blue-900 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back to fees</a>

    <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Record a payment</h1>
        <p class="text-sm text-gray-600 mb-4">Tell us what you paid and how. Each record is one installment — submit the same fee again later for a part payment.</p>

        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-900 p-4 rounded-r-lg mb-6 text-sm">
            <p class="font-semibold"><i class="fas fa-clock mr-1"></i> Your payment starts as Pending</p>
            <p class="mt-1">The school checks every submission against the bank statement. Once the CEO verifies it, the payment is <strong>complete</strong> and your child's balance drops.</p>
        </div>

        @if($children->isEmpty())
            <div class="text-center py-8">
                <i class="fas fa-child text-gray-500 text-5xl mb-3"></i>
                <p class="text-gray-600">No children are linked to your account yet, so there is nothing to pay for.</p>
                <p class="text-sm text-gray-500">Please contact the administrator.</p>
            </div>
        @else
        <form action="{{ route('parent.payments.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">@csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <p class="block text-sm font-medium text-gray-700">Children covered by this payment <span class="text-orange-700">*</span></p>
                    <p class="text-xs text-gray-500 mt-1">
                        Tick every child this transfer covers. The payment is recorded once for the whole family — one line per
                        child and class below — so you do not submit a separate payment for each child.
                    </p>

                    @php
                        $checkedChildren = array_map('strval', old(
                            'learner_ids',
                            $selectedLearner !== '' ? [$selectedLearner] : [$children->first()->id]
                        ));
                    @endphp

                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($children as $learner)
                            <label class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                                <input type="checkbox" name="learner_ids[]" value="{{ $learner->id }}" data-child-check
                                    class="h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                                    {{ in_array((string) $learner->id, $checkedChildren, true) ? 'checked' : '' }}>
                                <span class="min-w-0">
                                    <span class="block font-medium text-gray-800">{{ $learner->user->name }}</span>
                                    <span class="block text-xs text-gray-500">{{ $learner->classes->count() }} {{ \Illuminate\Support\Str::plural('class', $learner->classes->count()) }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('learner_ids')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
                    @error('learner_ids.*')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="payment_type" class="block text-sm font-medium text-gray-700">Payment Type <span class="text-orange-700">*</span></label>
                    <select id="payment_type" name="payment_type" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                        @foreach(['tuition', 'registration', 'material', 'other'] as $type)
                            <option value="{{ $type }}" {{ old('payment_type', 'tuition') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="term" class="block text-sm font-medium text-gray-700">Term</label>
                    <input type="text" id="term" name="term" list="payment-terms" value="{{ old('term', $selectedTerm) }}" placeholder="e.g. First Term" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                    <datalist id="payment-terms">
                        @foreach($terms as $term)<option value="{{ $term }}">@endforeach
                    </datalist>
                    <p class="text-xs text-gray-500 mt-1">Payments sharing a child, term and class share one balance.</p>
                </div>
                <div class="md:col-span-2">
                    <p class="block text-sm font-medium text-gray-700">What are you paying for? <span class="text-orange-700">*</span></p>
                    <p class="text-xs text-gray-500 mt-1">
                        Type the amount paid against each row this transfer covers — one record is created per row, under the
                        same reference and receipt, so a single transfer can clear two or three children and subjects at once.
                        Tick <strong>general</strong> for registration, materials or anything not tied to a class.
                    </p>

                    @php
                        $oldClasses = collect(old('class_ids', []))->map(function ($id) {
                            return (string) $id;
                        })->all();
                        $oldAmounts = old('amounts', []);
                        $oldTotals = old('total_fees', []);
                    @endphp

                    <div class="mt-3 overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-[#EAF2FB]">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-[#0a2550] uppercase">Pay</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-[#0a2550] uppercase">Child / class</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-[#0a2550] uppercase">Amount paid now</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-[#0a2550] uppercase">Total fee (optional)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach($children as $learner)
                                    @php $generalKey = $learner->id . ':0'; @endphp
                                    <tr data-child-heading="{{ $learner->id }}" class="bg-[#EAF2FB]/70">
                                        <td colspan="4" class="px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-[#0a2550]">{{ $learner->user->name }}</td>
                                    </tr>
                                    <tr data-pay-row data-learner-row="{{ $learner->id }}">
                                        <td class="px-3 py-2 align-top">
                                            <input type="checkbox" name="class_ids[]" value="{{ $generalKey }}" data-pay-check
                                                class="mt-2 h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                                                {{ in_array($generalKey, $oldClasses, true) ? 'checked' : '' }}>
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <span class="font-medium text-gray-800">General payment</span>
                                            <span class="block text-xs text-gray-500">Not tied to one class</span>
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <input type="number" step="0.01" min="0" name="amounts[{{ $generalKey }}]" data-pay-amount
                                                value="{{ $oldAmounts[$generalKey] ?? '' }}" placeholder="0.00"
                                                class="w-32 rounded-lg border border-gray-300 px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                            @error('amounts.' . $generalKey)<span class="mt-1 block text-xs text-orange-700">{{ $message }}</span>@enderror
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <input type="number" step="0.01" min="0" name="total_fees[{{ $generalKey }}]"
                                                value="{{ $oldTotals[$generalKey] ?? '' }}" placeholder="optional"
                                                class="w-32 rounded-lg border border-gray-300 px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                    </tr>
                                    @foreach($learner->classes->sortBy('name') as $class)
                                        @php $rowKey = $learner->id . ':' . $class->id; @endphp
                                        <tr data-pay-row data-learner-row="{{ $learner->id }}">
                                            <td class="px-3 py-2 align-top">
                                                <input type="checkbox" name="class_ids[]" value="{{ $rowKey }}" data-pay-check
                                                    class="mt-2 h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                                                    {{ in_array($rowKey, $oldClasses, true) ? 'checked' : '' }}>
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <span class="font-medium text-gray-800">{{ $class->name }}</span>
                                                <span class="block text-xs text-gray-500">{{ $learner->user->name }}</span>
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <input type="number" step="0.01" min="0" name="amounts[{{ $rowKey }}]" data-pay-amount
                                                    value="{{ $oldAmounts[$rowKey] ?? '' }}" placeholder="0.00"
                                                    class="w-32 rounded-lg border border-gray-300 px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                                @error('amounts.' . $rowKey)<span class="mt-1 block text-xs text-orange-700">{{ $message }}</span>@enderror
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <input type="number" step="0.01" min="0" name="total_fees[{{ $rowKey }}]"
                                                    value="{{ $oldTotals[$rowKey] ?? '' }}" placeholder="optional"
                                                    class="w-32 rounded-lg border border-gray-300 px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-gray-500"><i class="fas fa-circle-info mr-1"></i>Only the classes of the children you ticked are listed. Amounts apply to the term chosen above.</p>
                        <p class="text-sm font-semibold text-[#0a2550]">This submission: <span id="pay-total">0.00</span></p>
                    </div>

                    @error('class_ids')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
                    @error('class_ids.*')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
                    @error('amounts')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
                </div>

            </div>

            <div>
                <p class="block text-sm font-medium text-gray-700 mb-2">How did you pay? <span class="text-orange-700">*</span></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-blue-600 bg-blue-50/40 p-4 ring-2 ring-blue-200 {{ $accounts->isEmpty() ? 'cursor-not-allowed opacity-60' : '' }}">
                        <input type="radio" name="payment_method" value="{{ \App\Models\Payment::METHOD_TRANSFER }}" class="sr-only" checked {{ $accounts->isEmpty() ? 'disabled' : '' }}>
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700"><i class="fas fa-building-columns"></i></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-800">Bank transfer</span>
                            <span class="block text-xs text-gray-500">Transfer into a school account</span>
                        </span>
                    </label>

                    <label class="flex cursor-not-allowed items-start gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 opacity-70" title="Card payments are coming soon">
                        <input type="radio" name="payment_method" value="{{ \App\Models\Payment::METHOD_CARD }}" class="sr-only" disabled>
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-200 text-gray-500"><i class="fas fa-credit-card"></i></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-700">Card <span class="rounded-full bg-orange-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-orange-700">Soon</span></span>
                            <span class="block text-xs text-gray-500">{{ $cardReady ? 'Paystack is connected — flow coming' : 'Paystack — coming soon' }}</span>
                        </span>
                    </label>
                </div>
                @error('payment_method')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror

                @if($accounts->isEmpty())
                    <p class="mt-2 rounded-lg bg-orange-50 px-3 py-2 text-xs text-orange-800">
                        <i class="fas fa-circle-info mr-1"></i>The school has not listed a bank account yet. Please contact the school and try again once it has been added.
                    </p>
                @endif
            </div>


            {{-- Bank transfer: the accounts the CEO added (only active ones) --}}
            <div>
                <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-4">
                    <h2 class="text-sm font-semibold text-blue-900"><i class="fas fa-building-columns mr-1"></i>School accounts you can transfer into</h2>
                    <p class="mt-1 text-xs text-blue-900">Transfer the exact amount, then pick the account you used below. Keep the reference the bank gives you.</p>

                    <div class="mt-3 space-y-2">
                        @foreach($accounts as $account)
                            <div class="rounded-lg border border-blue-200 bg-white p-3">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-800">{{ $account->bank_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $account->account_name }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-base font-semibold text-gray-900">{{ $account->account_number }}</span>
                                        <button type="button" data-copy="{{ $account->account_number }}" class="rounded-lg border border-blue-300 px-2 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-50 transition">
                                            <i class="fas fa-copy mr-1"></i> Copy
                                        </button>
                                    </div>
                                </div>
                                @if($account->instructions)
                                    <p class="mt-2 text-xs text-gray-500"><i class="fas fa-circle-info mr-1"></i>{{ $account->instructions }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <label for="payment_account_id" class="block text-sm font-medium text-gray-700">Which account did you pay into? <span class="text-orange-700">*</span></label>
                        <select id="payment_account_id" name="payment_account_id" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select the account you transferred to</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ (string) old('payment_account_id') === (string) $account->id ? 'selected' : '' }}>{{ $account->fullLabel() }}</option>
                            @endforeach
                        </select>
                        @error('payment_account_id')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="reference" class="block text-sm font-medium text-gray-700">Transfer reference / narration</label>
                    <input type="text" id="reference" name="reference" value="{{ old('reference') }}" placeholder="e.g. FBN-8821345" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">
                        The reference or narration on the transfer — this is what the school looks for on the statement.
                    </p>
                    @error('reference')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="payment_date" class="block text-sm font-medium text-gray-700">Payment Date <span class="text-orange-700">*</span></label>
                    <input type="date" id="payment_date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                    @error('payment_date')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="receipt" class="block text-sm font-medium text-gray-700">Receipt / Teller Slip</label>
                <input type="file" id="receipt" name="receipt" accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">
                    Attach the bank teller or transfer receipt if you have one (PDF or image, max 5 MB).
                    Only you and the school can download it.
                </p>
                @error('receipt')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">{{ old('notes') }}</textarea>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="bg-blue-700 text-white px-6 py-3 rounded-lg hover:bg-blue-800 transition"><i class="fas fa-paper-plane mr-1"></i> Submit for verification</button>
                <a href="{{ route('parent.payments.index') }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-200 transition">Cancel</a>
            </div>
        </form>
        @endif
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Copy an account number without leaving the form.
        document.querySelectorAll('[data-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                const value = button.dataset.copy || '';
                const original = button.innerHTML;

                function confirmCopy() {
                    button.innerHTML = '<i class="fas fa-check mr-1"></i> Copied';
                    setTimeout(function () { button.innerHTML = original; }, 1500);
                }

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(value).then(confirmCopy).catch(function () {});
                    return;
                }

                const helper = document.createElement('textarea');
                helper.value = value;
                document.body.appendChild(helper);
                helper.select();
                try { document.execCommand('copy'); confirmCopy(); } catch (error) {}
                document.body.removeChild(helper);
            });
        });

        // Multi-child payment rows: a family transfer can cover several children.
        // Only the ticked children's rows are enabled, typing an amount ticks its
        // row, and the submission total updates live.
        const childChecks = Array.prototype.slice.call(document.querySelectorAll('[data-child-check]'));
        const payRows = Array.prototype.slice.call(document.querySelectorAll('[data-pay-row]'));
        const childHeadings = Array.prototype.slice.call(document.querySelectorAll('[data-child-heading]'));
        const payTotal = document.getElementById('pay-total');

        function selectedChildren() {
            return childChecks.filter(function (box) { return box.checked; }).map(function (box) {
                return box.value;
            });
        }

        function refreshPayRows() {
            const chosen = selectedChildren();

            childHeadings.forEach(function (heading) {
                heading.classList.toggle('hidden', chosen.indexOf(heading.getAttribute('data-child-heading')) === -1);
            });

            let total = 0;

            payRows.forEach(function (row) {
                const owner = row.getAttribute('data-learner-row');
                const allowed = !owner || chosen.indexOf(owner) !== -1;

                row.classList.toggle('hidden', !allowed);

                Array.prototype.forEach.call(row.querySelectorAll('input'), function (input) {
                    input.disabled = !allowed;

                    if (!allowed && input.type === 'checkbox') {
                        input.checked = false;
                    }
                });

                const check = row.querySelector('[data-pay-check]');
                const amount = row.querySelector('[data-pay-amount]');
                const value = amount ? parseFloat(amount.value) || 0 : 0;

                if (allowed && check && amount && value > 0 && !check.checked) {
                    check.checked = true;
                }

                if (allowed && check && check.checked) {
                    total += value;
                }
            });

            if (payTotal) {
                payTotal.textContent = total.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }
        }

        payRows.forEach(function (row) {
            const check = row.querySelector('[data-pay-check]');
            const amount = row.querySelector('[data-pay-amount]');

            if (check && amount) {
                check.addEventListener('change', function () {
                    if (!check.checked) amount.value = '';
                    refreshPayRows();
                });
            }

            if (amount) amount.addEventListener('input', refreshPayRows);
        });

        childChecks.forEach(function (box) {
            box.addEventListener('change', refreshPayRows);
        });

        refreshPayRows();
    });
</script>
@endsection

