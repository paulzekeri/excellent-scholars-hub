@extends('layouts.app')

@section('title', 'Record a Payment')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('parent.payments.index') }}" class="text-blue-700 hover:text-blue-900 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back to fees</a>

    <div class="bg-white rounded-xl shadow-md p-6 sm:p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Record a payment</h1>
        <p class="text-sm text-gray-600 mb-4">Tell us what you paid and how. Each row is one installment — submit the same fee again later for a part payment.</p>

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
                <div>
                    <label for="term" class="block text-sm font-medium text-gray-700">Term</label>
                    <input type="text" id="term" name="term" list="payment-terms" value="{{ old('term', $selectedTerm) }}" placeholder="e.g. First Term" class="mt-1 block w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500">
                    <datalist id="payment-terms">
                        @foreach($terms as $term)<option value="{{ $term }}">@endforeach
                    </datalist>
                    <p class="text-xs text-gray-500 mt-1">Payments sharing a child, term and class share one balance.</p>
                </div>
                <div class="flex items-end">
                    <p class="text-xs text-gray-500">
                        One submission can pay for several children and several classes — add a row for each payment line.
                    </p>
                </div>
            </div>

            <div>
                <p class="block text-sm font-medium text-gray-700">Payments on this transfer <span class="text-orange-700">*</span></p>
                <p class="text-xs text-gray-500 mt-1">
                    One row per payment: choose the child, what the money is for, the class it belongs to, how much you paid and
                    — optionally — what it was for in your own words. One row can be tuition for a child while another row is
                    that child's or a sibling's registration.
                </p>

                @php
                    $types = ['tuition' => 'Tuition', 'registration' => 'Registration', 'material' => 'Materials', 'other' => 'Other'];
                    $formLines = old('lines');

                    if (! is_array($formLines) || $formLines === []) {
                        $formLines = [[
                            'learner_id' => $selectedLearner !== '' ? $selectedLearner : $children->first()->id,
                            'payment_type' => 'tuition',
                            'class_id' => '',
                            'amount' => '',
                            'total_fee' => '',
                            'purpose' => '',
                        ]];
                    }
                @endphp

                <div class="mt-3 overflow-x-auto rounded-xl border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-[#EAF2FB]">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-[#0a2550] uppercase">Child</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-[#0a2550] uppercase">Payment for</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-[#0a2550] uppercase">Class</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-[#0a2550] uppercase">Amount paid</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-[#0a2550] uppercase">Full fee (optional)</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-[#0a2550] uppercase">Purpose (optional)</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody id="payment-lines" class="divide-y divide-gray-100 bg-white">
                            @foreach($formLines as $index => $line)
                                <tr data-payment-row>
                                    <td class="px-3 py-2 align-top">
                                        <select name="lines[{{ $index }}][learner_id]" class="w-36 rounded-lg border border-gray-300 px-2 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                                            @foreach($children as $learner)
                                                <option value="{{ $learner->id }}" {{ (string) ($line['learner_id'] ?? '') === (string) $learner->id ? 'selected' : '' }}>{{ $learner->user->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('lines.' . $index . '.learner_id')<span class="mt-1 block text-xs text-orange-700">{{ $message }}</span>@enderror
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        <select name="lines[{{ $index }}][payment_type]" class="w-32 rounded-lg border border-gray-300 px-2 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                                            @foreach($types as $value => $label)
                                                <option value="{{ $value }}" {{ ($line['payment_type'] ?? 'tuition') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('lines.' . $index . '.payment_type')<span class="mt-1 block text-xs text-orange-700">{{ $message }}</span>@enderror
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        <select name="lines[{{ $index }}][class_id]" class="w-48 rounded-lg border border-gray-300 px-2 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">Choose a class</option>
                                            @foreach($classes as $class)
                                                <option value="{{ $class->id }}" {{ (string) ($line['class_id'] ?? '') === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('lines.' . $index . '.class_id')<span class="mt-1 block text-xs text-orange-700">{{ $message }}</span>@enderror
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        <input type="number" step="0.01" min="0" name="lines[{{ $index }}][amount]" data-line-amount
                                            value="{{ $line['amount'] ?? '' }}" placeholder="0.00"
                                            class="w-28 rounded-lg border border-gray-300 px-2 py-2 focus:ring-blue-500 focus:border-blue-500">
                                        @error('lines.' . $index . '.amount')<span class="mt-1 block text-xs text-orange-700">{{ $message }}</span>@enderror
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        <input type="number" step="0.01" min="0" name="lines[{{ $index }}][total_fee]"
                                            value="{{ $line['total_fee'] ?? '' }}" placeholder="optional"
                                            class="w-28 rounded-lg border border-gray-300 px-2 py-2 focus:ring-blue-500 focus:border-blue-500">
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        <input type="text" maxlength="500" name="lines[{{ $index }}][purpose]"
                                            value="{{ $line['purpose'] ?? '' }}" placeholder="e.g. First term tuition"
                                            class="w-52 rounded-lg border border-gray-300 px-2 py-2 focus:ring-blue-500 focus:border-blue-500">
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        <button type="button" data-remove-row class="mt-2 text-gray-500 hover:text-orange-700" title="Remove this row">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>

                <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                    <button type="button" id="add-payment-row" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 transition">
                        <i class="fas fa-plus mr-1"></i> Add another payment
                    </button>
                    <p class="text-sm font-semibold text-[#0a2550]">This submission: <span id="pay-total">0.00</span></p>
                </div>

                <p class="mt-2 text-xs text-gray-500">
                    <i class="fas fa-circle-info mr-1"></i>
                    The full fee is only needed for the balance bar on your fees page. Pick any class — including one your child
                    has not started yet; the school can add the learner after confirming the payment.
                </p>

                @error('lines')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
                @error('lines.*.class_id')<p class="mt-1 text-xs text-orange-700">{{ $message }}</p>@enderror
            </div>

            <template id="payment-row-template">
                <tr data-payment-row>
                    <td class="px-3 py-2 align-top">
                        <select name="lines[__INDEX__][learner_id]" class="w-36 rounded-lg border border-gray-300 px-2 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            @foreach($children as $learner)
                                <option value="{{ $learner->id }}">{{ $learner->user->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-3 py-2 align-top">
                        <select name="lines[__INDEX__][payment_type]" class="w-32 rounded-lg border border-gray-300 px-2 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            @foreach($types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-3 py-2 align-top">
                        <select name="lines[__INDEX__][class_id]" class="w-48 rounded-lg border border-gray-300 px-2 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Choose a class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-3 py-2 align-top">
                        <input type="number" step="0.01" min="0" name="lines[__INDEX__][amount]" data-line-amount placeholder="0.00" class="w-28 rounded-lg border border-gray-300 px-2 py-2 focus:ring-blue-500 focus:border-blue-500">
                    </td>
                    <td class="px-3 py-2 align-top">
                        <input type="number" step="0.01" min="0" name="lines[__INDEX__][total_fee]" placeholder="optional" class="w-28 rounded-lg border border-gray-300 px-2 py-2 focus:ring-blue-500 focus:border-blue-500">
                    </td>
                    <td class="px-3 py-2 align-top">
                        <input type="text" maxlength="500" name="lines[__INDEX__][purpose]" placeholder="e.g. First term tuition" class="w-52 rounded-lg border border-gray-300 px-2 py-2 focus:ring-blue-500 focus:border-blue-500">
                    </td>
                    <td class="px-3 py-2 align-top">
                        <button type="button" data-remove-row class="mt-2 text-gray-500 hover:text-orange-700" title="Remove this row">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>
            </template>

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

        // Payment lines: add a row per child/class, remove blanks, total the amounts.
        const lineBody = document.getElementById('payment-lines');
        const rowTemplate = document.getElementById('payment-row-template');
        const addRow = document.getElementById('add-payment-row');
        const payTotal = document.getElementById('pay-total');
        let nextLine = {{ count($formLines) }};

        function refreshTotal() {
            if (!lineBody || !payTotal) return;

            let total = 0;

            lineBody.querySelectorAll('[data-line-amount]').forEach(function (input) {
                total += parseFloat(input.value) || 0;
            });

            payTotal.textContent = total.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }

        if (lineBody) {
            lineBody.addEventListener('input', refreshTotal);

            lineBody.addEventListener('click', function (event) {
                const remove = event.target.closest('[data-remove-row]');

                if (!remove) return;

                const row = remove.closest('[data-payment-row]');
                if (row) row.remove();

                // Never leave the form without a row.
                if (!lineBody.querySelector('[data-payment-row]') && addRow) {
                    addRow.click();
                }

                refreshTotal();
            });
        }

        if (addRow && rowTemplate && lineBody) {
            addRow.addEventListener('click', function () {
                const html = rowTemplate.innerHTML.replace(/__INDEX__/g, nextLine++);
                lineBody.insertAdjacentHTML('beforeend', html);
                refreshTotal();
            });
        }

        refreshTotal();
    });
</script>
@endsection




