@component('mail::message')
# Assessment booked for {{ $childName }}

Dear {{ $guardianName }},

The assessment for **{{ $childName }}** is booked for:

**{{ $scheduleLabel }}** ({{ $platformLabel }})

@if($meetingLink)
Join the video call from any phone, tablet or computer — no account or app is needed:

@component('mail::button', ['url' => $meetingLink])
Join the assessment
@endcomponent

If the button does not work, copy this link: {{ $meetingLink }}
@else
The school will call you at the agreed time.
@endif

**Tutor:** {{ $tutorName }}
**Reference:** {{ $reference }}

If the time no longer works, reply to this email or message the school on WhatsApp and we will rearrange it.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
