@if(!empty($site['social']['instagram']))
<section class="section social-band">
  <div class="container center">
    <div class="section-head center">
      @if($s['label'])<span class="section-label">{{ $s['label'] }}</span>@endif
      <h2 class="section-title">{{ $s['title'] ?: 'On Instagram' }}</h2>
      @if($s['body'])<p class="section-lead">{{ $s['body'] }}</p>@endif
    </div>
    <a href="{{ $site['social']['instagram'] }}" class="btn btn--outline" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-instagram"></i>&nbsp; {{ $s['button_label'] ?: 'Follow us' }}</a>
  </div>
</section>
@endif
