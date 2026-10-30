@component('mail::message')
# Password reset for {{ $childName }}

A password reset was requested for **{{ $childName }}** on Excellent Scholars Hub.

Share this one-time link with them privately. It expires in {{ $expiresInMinutes }} minutes and can be used once.
Open it on your child's device — if you are signed in on this browser, use a private window or sign out first.

@component('mail::button', ['url' => $resetUrl])
Choose a new password
@endcomponent

If you did not expect this, you can ignore this email — the current password keeps working and nothing changes.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
