@component('mail::message')
# {{ $assessment ? 'Assessment booked' : 'New assessment request' }}

{{ $assessment
    ? 'The school has booked the assessment below.'
    : 'A family has requested an assessment from the website. Follow up to agree a time.' }}

**Reference:** {{ $enquiry->reference }}
**Child:** {{ $enquiry->child_name }}
**Grade / class:** {{ $enquiry->child_grade_level ?: 'Not recorded' }}
@if($enquiry->child_dob)
**Date of birth:** {{ $enquiry->child_dob->format('M j, Y') }}
@endif

**Guardian:** {{ $enquiry->guardian_name }}
**Phone / WhatsApp:** {{ $enquiry->guardian_phone }}
**Email:** {{ $enquiry->guardian_email ?: 'Not provided' }}
**Source:** {{ $enquiry->sourceLabel() }}
@if($enquiry->preferred_at)
**Preferred time:** {{ $enquiry->preferred_at->format('D, M j, Y g:i a') }}
@endif

@if($assessment)
@component('mail::panel')
**Scheduled:** {{ $assessment->scheduleLabel() }}
**Duration:** {{ $assessment->duration_minutes }} minutes
**Platform:** {{ $assessment->platformLabel() }}
**Tutor:** {{ optional(optional($assessment->tutor)->user)->name ?: 'To be assigned' }}
**Join link:** {{ $assessment->meeting_link ?: 'Phone / in person' }}
@endcomponent
@endif

@if($enquiry->notes)
**Notes from the family:**

{{ $enquiry->notes }}
@endif

@component('mail::button', ['url' => route('admin.enquiries.show', $enquiry)])
Open the enquiry
@endcomponent

This is an internal notification from {{ config('app.name') }}.
@endcomponent
