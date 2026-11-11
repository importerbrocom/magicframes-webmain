<div class="services__grid">
  @foreach($services as $i => $svc)
    <a class="service" href="/services/{{ $svc['slug'] }}" data-reveal="up" style="--d:{{ ($i % 4) * 0.12 }}s">
      @if($svc['image_url'])
        <span class="service__thumb"><img src="{{ img_url($svc['image_url'], 800) }}" alt="" loading="lazy" /></span>
      @else
        <span class="service__icon"><i class="{{ $svc['icon'] ?: 'fa-solid fa-wand-magic-sparkles' }}"></i></span>
      @endif
      <h3>{{ $svc['title'] }}</h3>
      @if($svc['short_description'])<p>{{ $svc['short_description'] }}</p>@endif
      <span class="service__more">Learn more <i class="fa-solid fa-arrow-right"></i></span>
    </a>
  @endforeach
</div>
