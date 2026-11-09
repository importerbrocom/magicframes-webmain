New enquiry received on {{ config('app.name') }}

Name:       {{ $enquiry->name }}
Email:      {{ $enquiry->email }}
Phone:      {{ $enquiry->phone ?: '—' }}
Event type: {{ $enquiry->event_type ?: '—' }}
Event date: {{ $enquiry->event_date?->format('d M Y') ?: '—' }}
Received:   {{ $enquiry->created_at->format('d M Y H:i') }}

Message:
{{ $enquiry->message }}

Manage it in the admin panel: {{ url('/admin/enquiries/'.$enquiry->id) }}
