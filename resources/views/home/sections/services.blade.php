<section class="services" id="services">
  <div class="container">
    <div class="section-head center">
      @if($s['label'])<span class="section-label">{{ $s['label'] }}</span>@endif
      <h2 class="section-title">{{ $s['title'] ?: 'Our Services' }}</h2>
      @if($s['body'])<p class="section-lead">{{ $s['body'] }}</p>@endif
    </div>
    @include('partials.service-cards', ['services' => $s['items']])
    @if($s['button_label'] && $s['button_url'])<div class="center" style="margin-top:2.5rem"><a href="{{ $s['button_url'] }}" class="btn btn--outline">{{ $s['button_label'] }}</a></div>@endif
  </div>
</section>
