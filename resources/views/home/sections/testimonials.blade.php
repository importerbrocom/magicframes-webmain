<section class="testi" id="testimonials">
  <div class="testi__bg" data-parallax="0.15" @if($s['image_url']) style="background-image:url('{{ img_url($s['image_url'], 1800) }}')" @endif></div>
  <div class="testi__overlay"></div>
  <div class="container">
    <div class="section-head center light">
      @if($s['label'])<span class="section-label">{{ $s['label'] }}</span>@endif
      <h2 class="section-title">{{ $s['title'] ?: 'Testimonials' }}</h2>
    </div>
    <div class="slider" id="slider">
      <div class="slider__track" id="sliderTrack">
        @foreach($s['items'] as $t)
          <figure class="slide">
            @if($t['photo_url'])<img class="slide__photo" src="{{ img_url($t['photo_url'], 160) }}" alt="{{ $t['client_name'] }}" loading="lazy" />@else<i class="fa-solid fa-quote-right slide__mark"></i>@endif
            <div class="slide__stars" aria-label="{{ $t['rating'] }} out of 5 stars">{!! str_repeat('<i class="fa-solid fa-star"></i>', $t['rating']) !!}</div>
            <blockquote>{{ $t['body'] }}</blockquote>
            <figcaption><strong>{{ $t['client_name'] }}</strong>@if($t['client_role'])<span>{{ $t['client_role'] }}</span>@endif</figcaption>
          </figure>
        @endforeach
      </div>
      <div class="slider__dots" id="sliderDots"></div>
    </div>
  </div>
</section>
