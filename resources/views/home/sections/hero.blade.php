@php $slides = $home['hero']; @endphp
<section class="hero" id="home">
  <div class="hero__slides" id="heroSlides">
    @forelse($slides as $i => $slide)
      <div class="hero__slide {{ $i === 0 ? 'is-active' : '' }}" @if($slide['image_url']) style="background-image:url('{{ img_url($slide['image_url'], 1900) }}')" @endif>
        <div class="hero__caption">
          @php $len = mb_strlen($slide['heading']); $wc = $len > 22 ? ' hero__word--xlong' : ($len > 8 ? ' hero__word--long' : ''); @endphp
          @if($i === 0)<h1 class="hero__word{{ $wc }}">{{ $slide['heading'] }}</h1>@else<h2 class="hero__word{{ $wc }}">{{ $slide['heading'] }}</h2>@endif
          @if($slide['subheading'])<p class="hero__meta"><span>{{ $slide['subheading'] }}</span></p>@endif
          @if($slide['description'])<p class="hero__desc">{{ $slide['description'] }}</p>@endif
          @if($slide['button_label'] && $slide['button_url'])<a href="{{ $slide['button_url'] }}" class="btn hero__btn">{{ $slide['button_label'] }}</a>@endif
        </div>
      </div>
    @empty
      {{-- No hero slides yet: a calm, on-brand fallback until the studio uploads hero images --}}
      <div class="hero__slide is-active hero__slide--plain">
        <div class="hero__caption">
          <h1 class="hero__word">{{ $site['name'] }}</h1>
          @if($site['tagline'])<p class="hero__meta"><span>{{ $site['tagline'] }}</span></p>@endif
        </div>
      </div>
    @endforelse
  </div>
  <div class="hero__dots" id="heroDots"></div>
  <a href="#after-hero" class="hero__scroll" aria-label="Scroll down"><i class="fa-solid fa-angle-down"></i></a>
</section>
<span id="after-hero"></span>
