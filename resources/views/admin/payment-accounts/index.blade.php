@extends('layouts.app')

@section('title', 'School Bank Accounts')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <a href="{{ route('admin.payments.index') }}" class="text-blue-700 hover:text-blue-900 text-sm"><i class="fas fa-arrow-left mr-1"></i> Back to payments</a>
            <h1 class="text-3xl font-bold text-gray-800 mt-2">School bank accounts</h1>
            <p class="text-gray-600 mt-1">The accounts parents transfer fees into. Only switched-on accounts appear on the payment form.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-lg border-l-4 border-red-500 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold"><i class="fas fa-triangle-exclamation mr-1"></i> Please check the form</p>
            <ul class="mt-1 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-green-600">
            <p class="text-gray-500 text-sm">Visible to parents</p>
            <p class="text-2xl font-bold text-gray-800">{{ $activeCount }}</p>
            <p class="text-xs text-gray-500 mt-1">Switched-on account{{ $activeCount === 1 ? '' : 's' }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-600">
            <p class="text-gray-500 text-sm">Accounts on file</p>
            <p class="text-2xl font-bold text-gray-800">{{ $accounts->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Including switched-off accounts</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 {{ $paystackReady ? 'border-green-600' : 'border-gray-400' }}">
            <p class="text-gray-500 text-sm">Card payments</p>
            <p class="text-2xl font-bold text-gray-800">{{ $paystackReady ? 'Ready' : 'Coming soon' }}</p>
            <p class="text-xs text-gray-500 mt-1">Powered by Paystack</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-lg font-semibold text-gray-800"><i class="fas fa-building-columns text-blue-600 mr-2"></i>Accounts</h2>

            @forelse($accounts as $account)
                <div class="bg-white rounded-xl shadow-md p-5 {{ $account->is_active ? '' : 'opacity-80' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-gray-800">{{ $account->bank_name }}</p>
                                @if($account->is_active)
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800"><i class="fas fa-eye mr-1"></i>Visible to parents</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700"><i class="fas fa-eye-slash mr-1"></i>Switched off</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 mt-1">{{ $account->account_name }}</p>
                            <p class="font-mono text-lg font-semibold text-gray-900 mt-1">{{ $account->account_number }}</p>
                            @if($account->instructions)
                                <p class="text-xs text-gray-500 mt-2"><i class="fas fa-circle-info mr-1"></i>{{ $account->instructions }}</p>
                            @endif
                            <p class="text-xs text-gray-400 mt-2">
                                Added {{ $account->created_at->format('M d, Y') }}
                                @if($account->createdBy) by {{ $account->createdBy->name }} @endif
                                · {{ $account->payments_count }} payment{{ $account->payments_count === 1 ? '' : 's' }} recorded
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <form method="POST" action="{{ route('admin.payment-accounts.toggle', $account) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition {{ $account->is_active ? 'border-gray-300 text-gray-700 hover:bg-gray-50' : 'border-green-600 text-green-700 hover:bg-green-50' }}">
                                    @if($account->is_active)
                                        <i class="fas fa-eye-slash mr-1"></i> Switch off
                                    @else
                                        <i class="fas fa-eye mr-1"></i> Switch on
                                    @endif
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.payment-accounts.destroy', $account) }}"
                                  onsubmit="return confirm('Delete {{ $account->bank_name }} {{ $account->account_number }}? Payments already recorded keep the account name and number.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 transition">
                                    <i class="fas fa-trash mr-1"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>

                    <details class="mt-3">
                        <summary class="cursor-pointer select-none text-xs font-semibold text-blue-700 hover:text-blue-900"><i class="fas fa-pen mr-1"></i> Edit details</summary>
                        <form method="POST" action="{{ route('admin.payment-accounts.update', $account) }}" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Bank</label>
                                <input type="text" name="bank_name" value="{{ $account->bank_name }}" required maxlength="100" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Account name</label>
                                <input type="text" name="account_name" value="{{ $account->account_name }}" required maxlength="150" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Account number</label>
                                <input type="text" name="account_number" value="{{ $account->account_number }}" required inputmode="numeric" maxlength="20" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Note for parents</label>
                                <input type="text" name="instructions" value="{{ $account->instructions }}" maxlength="500" placeholder="e.g. Use your child's full name as narration" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="sm:col-span-2 flex flex-wrap items-center gap-3">
                                <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-800 transition"><i class="fas fa-check mr-1"></i> Save changes</button>
                                <span class="text-xs text-gray-500">Use “Switch off” to hide it from parents.</span>
                            </div>
                        </form>
                    </details>
                </div>
            @empty
                <div class="bg-white rounded-xl shadow-md p-8 text-center">
                    <i class="fas fa-building-columns text-gray-300 text-5xl mb-3"></i>
                    <p class="text-gray-600 font-medium">No school bank account yet</p>
                    <p class="text-sm text-gray-500 mt-1">Add the first account so parents can pay by transfer.</p>
                </div>
            @endforelse
        </div>


        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-md p-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-1"><i class="fas fa-plus-circle text-green-600 mr-2"></i>Add an account</h2>
                <p class="text-xs text-gray-500 mb-4">Parents see the bank, account name and number — keep them exactly as the bank has them.</p>

                <form method="POST" action="{{ route('admin.payment-accounts.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label for="bank_name" class="block text-sm font-medium text-gray-700">Bank <span class="text-red-500">*</span></label>
                        <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name') }}" required maxlength="100" placeholder="e.g. GTBank" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="account_name" class="block text-sm font-medium text-gray-700">Account name <span class="text-red-500">*</span></label>
                        <input type="text" id="account_name" name="account_name" value="{{ old('account_name', 'Excellent Scholars Academy') }}" required maxlength="150" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="account_number" class="block text-sm font-medium text-gray-700">Account number <span class="text-red-500">*</span></label>
                        <input type="text" id="account_number" name="account_number" value="{{ old('account_number') }}" required inputmode="numeric" maxlength="20" placeholder="e.g. 0123456789" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Digits only; spaces and dashes are removed automatically.</p>
                    </div>
                    <div>
                        <label for="instructions" class="block text-sm font-medium text-gray-700">Note for parents</label>
                        <input type="text" id="instructions" name="instructions" value="{{ old('instructions') }}" maxlength="500" placeholder="e.g. Use your child's full name as narration" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        Show this account to parents straight away
                    </label>
                    <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700 transition"><i class="fas fa-plus mr-1"></i> Add account</button>
                </form>
            </div>


            <div class="rounded-xl border p-5 {{ $paystackReady ? 'border-green-200 bg-green-50' : 'border-orange-200 bg-orange-50' }}">
                <h2 class="text-sm font-semibold {{ $paystackReady ? 'text-green-900' : 'text-orange-900' }}"><i class="fas fa-credit-card mr-1"></i> Card payments (Paystack)</h2>
                @if($paystackReady)
                    <p class="mt-2 text-sm text-green-800">Paystack keys detected. Card payments can be wired to the parent form next; until then parents pay by transfer or cash.</p>
                @else
                    <p class="mt-2 text-sm text-orange-800">Not connected yet — parents see <strong>Card — coming soon</strong> on the payment form.</p>
                    <p class="mt-2 text-xs text-orange-900">Add these to <code class="rounded bg-white/70 px-1 py-0.5">.env</code> and run <code class="rounded bg-white/70 px-1 py-0.5">php artisan config:clear</code>:</p>
                    <ul class="mt-1 space-y-1 text-xs font-mono text-orange-900">
                        @foreach($paystackMissing as $key)
                            <li>{{ $key }}=</li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs text-orange-900">See <code class="rounded bg-white/70 px-1 py-0.5">App\Services\PaystackService</code> for the flow.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

