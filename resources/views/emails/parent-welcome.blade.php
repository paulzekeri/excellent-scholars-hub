@component('mail::message')
# Welcome to Excellent Scholars Hub!

Dear {{ $parentName }},

Your child, **{{ $learnerName }}**, has been registered on the **Excellent Scholars Hub** platform.

A parent/guardian account has been automatically created for you so you can monitor your child's academic progress.

## Your Login Credentials

**Email:** {{ $parentEmail }}
**Default Password:** `{{ $defaultPassword }}`

@component('mail::button', ['url' => url('/login')])
Login to Your Account
@endcomponent

For security reasons, please log in and change your password as soon as possible.

You will be able to:
- View your child's class schedules
- Track attendance records
- Monitor assignment submissions
- Check academic progress

If you have any questions, please contact the school administration.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
