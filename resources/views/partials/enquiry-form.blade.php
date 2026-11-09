{{-- Real enquiry form: submitted by public/js/site.js to POST /api/v1/enquiries --}}
@php $uid = 'f'.substr(md5(uniqid('', true)), 0, 6); @endphp
<form class="rsvp__form" data-enquiry novalidate @isset($reveal) data-reveal="right" @endisset>
  <div class="field"><input type="text" name="name" required minlength="2" maxlength="120" placeholder=" " id="{{ $uid }}-name" autocomplete="name" /><label for="{{ $uid }}-name">Your Name</label><small class="field__error" data-error="name"></small></div>
  <div class="field"><input type="email" name="email" required maxlength="190" placeholder=" " id="{{ $uid }}-email" autocomplete="email" /><label for="{{ $uid }}-email">Email Address</label><small class="field__error" data-error="email"></small></div>
  <div class="field"><input type="tel" name="phone" maxlength="40" placeholder=" " id="{{ $uid }}-phone" autocomplete="tel" /><label for="{{ $uid }}-phone">Phone (optional)</label><small class="field__error" data-error="phone"></small></div>
  <div class="field-row">
    <div class="field field--static"><select name="event_type" id="{{ $uid }}-type"><option value="">Select…</option>@foreach(config('enquiry.event_types') as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</select><label for="{{ $uid }}-type">Event Type</label><small class="field__error" data-error="event_type"></small></div>
    <div class="field field--static"><input type="date" name="event_date" min="{{ date('Y-m-d') }}" id="{{ $uid }}-date" /><label for="{{ $uid }}-date">Event Date</label><small class="field__error" data-error="event_date"></small></div>
  </div>
  <div class="field"><textarea name="message" rows="4" required minlength="10" maxlength="3000" placeholder=" " id="{{ $uid }}-msg"></textarea><label for="{{ $uid }}-msg">Tell us about your day</label><small class="field__error" data-error="message"></small></div>
  {{-- Honeypot: invisible to people, tempting to bots --}}
  <div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off" /></label></div>
  <button class="btn" type="submit">Send Request</button>
  <p class="form-status" role="status" aria-live="polite" data-status></p>
</form>
