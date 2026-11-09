<section class="rsvp" id="contact">
  <div class="container rsvp__grid">
    <div class="rsvp__text" data-reveal="left">
      @if($s['label'])<span class="section-label">{{ $s['label'] }}</span>@endif
      <h2 class="section-title">{{ $s['title'] ?: 'Get in touch' }}</h2>
      @if($s['body'])<p class="section-lead">{{ $s['body'] }}</p>@endif
      @include('partials.contact-lines')
    </div>
    @include('partials.enquiry-form', ['reveal' => true])
  </div>
</section>
