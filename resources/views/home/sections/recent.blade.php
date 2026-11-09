<section class="section recent" id="recent" style="background:var(--cream-2)">
  <div class="container">
    <div class="section-head center">
      @if($s['label'])<span class="section-label">{{ $s['label'] }}</span>@endif
      <h2 class="section-title">{{ $s['title'] ?: 'Recent Projects' }}</h2>
    </div>
    <div class="pf-grid">
      @foreach($s['items'] as $i => $cat)
        <a class="pf-card" href="/gallery/{{ $cat['slug'] }}" data-reveal="up" style="--d:{{ ($i % 3) * 0.1 }}s">
          <div class="pf-card__media">
            @if($cat['cover_url'])<img src="{{ img_url($cat['cover_url'], 900) }}" alt="{{ $cat['name'] }}" loading="lazy" />@endif
            <span class="pf-card__view"><i class="fa-solid fa-arrow-right"></i></span>
          </div>
          <div class="pf-card__body">
            <h3 class="pf-card__title">{{ $cat['name'] }}</h3>
            <span class="pf-card__count">{{ $cat['images_count'] }} {{ \Illuminate\Support\Str::plural('Photo', $cat['images_count']) }}</span>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</section>
