<section class="section home-videos" id="films">
  <div class="container">
    <div class="section-head center">
      @if($s['label'])<span class="section-label">{{ $s['label'] }}</span>@endif
      <h2 class="section-title">{{ $s['title'] ?: 'Featured Films' }}</h2>
    </div>
    @include('partials.video-cards', ['videos' => $s['items']])
    <div class="center" style="margin-top:2.5rem">
      <a href="{{ $s['button_url'] ?: '/videos' }}" class="btn btn--outline">{{ $s['button_label'] ?: 'Watch All Films' }}</a>
    </div>
  </div>
</section>
