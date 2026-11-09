<section class="gallery" id="gallery">
  <div class="container">
    <div class="section-head center">
      @if($s['label'])<span class="section-label">{{ $s['label'] }}</span>@endif
      <h2 class="section-title">{{ $s['title'] ?: 'Our Portfolio' }}</h2>
    </div>
  </div>
  <div class="gallery__grid">
    @foreach($s['items'] as $i => $img)
      <a class="gallery__item" data-reveal="up" style="--d:{{ ($i % 3) * 0.08 }}s" href="/gallery/{{ $img['category']['slug'] }}" aria-label="{{ $img['title'] ?: $img['category']['name'] }}">
        <img src="{{ $img['thumb_url'] }}" @if($img['srcset']) srcset="{{ $img['srcset'] }}" sizes="(max-width:700px) 50vw, 33vw" @endif alt="{{ $img['alt'] ?: $img['category']['name'] }}" loading="lazy" />
        <span class="gallery__plus"><i class="fa-solid fa-plus"></i></span>
      </a>
    @endforeach
  </div>
  <div class="center" style="margin-top:2.5rem">
    <a href="{{ $s['button_url'] ?: '/gallery' }}" class="btn btn--outline">{{ $s['button_label'] ?: 'View All Galleries' }}</a>
  </div>
</section>
