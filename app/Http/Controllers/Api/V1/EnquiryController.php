<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\NewEnquiryMail;
use App\Models\Enquiry;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnquiryController extends Controller
{
    /** Event types for the contact form dropdown. */
    public function options(): JsonResponse
    {
        return response()->json(['data' => ['event_types' => config('enquiry.event_types')]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-.\s]{6,40}$/'],
            'event_type' => ['nullable', 'string', 'max:60', 'in:'.implode(',', config('enquiry.event_types'))],
            'event_date' => ['nullable', 'date', 'after_or_equal:today', 'before:+10 years'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'source_page' => ['nullable', 'string', 'max:255'],
            // Honeypot: real visitors never see or fill this field.
            'website' => ['nullable', 'string', 'max:255'],
        ], [
            'phone.regex' => 'Please enter a valid phone number.',
            'event_date.after_or_equal' => 'The event date cannot be in the past.',
        ]);

        // Bots that fill the hidden field get a normal-looking success and nothing is stored.
        if (filled($data['website'] ?? null)) {
            return response()->json(['message' => 'Thank you! We will get back to you soon.'], 201);
        }

        $enquiry = Enquiry::create([
            'name' => strip_tags($data['name']),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'event_type' => $data['event_type'] ?? null,
            'event_date' => $data['event_date'] ?? null,
            'message' => strip_tags($data['message']),
            'source_page' => isset($data['source_page']) ? mb_substr(strip_tags($data['source_page']), 0, 255) : null,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'status' => Enquiry::STATUS_NEW,
        ]);

        $this->notify($enquiry);

        return response()->json(['message' => 'Thank you! We will get back to you soon.'], 201);
    }

    /** Email alert; a mail failure must never lose the enquiry or break the form. */
    private function notify(Enquiry $enquiry): void
    {
        $to = Setting::get('contact.email') ?: config('enquiry.notify_email');
        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send(new NewEnquiryMail($enquiry));
        } catch (\Throwable $e) {
            Log::error('Enquiry notification failed: '.$e->getMessage(), ['enquiry_id' => $enquiry->id]);
        }
    }
}
