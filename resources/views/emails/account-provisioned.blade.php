@component('mail::message')
# Welcome to Excellent Scholars Hub

Dear {{ $name }},

@if($guardianOf)
A school account has been created for **{{ $guardianOf }}**. The login details are below — please share the password with your child privately.
@else
Your account has been created by the school. The login details are below.
@endif

## Login details

**Username:** `{{ $username }}`
**Temporary password:** `{{ $temporaryPassword }}`

@component('mail::button', ['url' => $loginUrl])
Sign in to Excellent Scholars Hub
@endcomponent

When you sign in you will be asked to **choose your own password**. The password above is temporary and stops working as soon as you set a new one.

@if(count($linkedAccounts))
## Accounts in your care

@foreach($linkedAccounts as $account)
- **{{ $account['name'] }}** — username `{{ $account['username'] }}`
@endforeach
@endif

Keep these details private. If you did not expect this account, please contact the school administration.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
