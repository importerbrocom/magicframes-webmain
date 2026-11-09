@php $c = $site['contact']; @endphp
@if(collect($c)->except('map_embed_url')->filter()->isNotEmpty())
<div class="contact-info contact-info--compact">
  @if($c['address'])<div class="contact-info__item"><i class="fa-solid fa-location-dot"></i><div><h4>Studio</h4><p>{!! nl2br(e($c['address'])) !!}</p></div></div>@endif
  @if($c['email'])<div class="contact-info__item"><i class="fa-solid fa-envelope"></i><div><h4>Email</h4><p><a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></p></div></div>@endif
  @if($c['phone'])<div class="contact-info__item"><i class="fa-solid fa-phone"></i><div><h4>Phone</h4><p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $c['phone']) }}">{{ $c['phone'] }}</a></p></div></div>@endif
  @if($c['whatsapp'])<div class="contact-info__item"><i class="fa-brands fa-whatsapp"></i><div><h4>WhatsApp</h4><p><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $c['whatsapp']) }}" target="_blank" rel="noopener">{{ $c['whatsapp'] }}</a></p></div></div>@endif
  @if($c['hours'])<div class="contact-info__item"><i class="fa-solid fa-clock"></i><div><h4>Hours</h4><p>{{ $c['hours'] }}</p></div></div>@endif
</div>
@endif
