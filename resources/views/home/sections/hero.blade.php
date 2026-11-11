@php $slides = $home['hero']; @endphp
<section class="hero" id="home">
  <div class="hero__slides" id="heroSlides">
    @forelse($slides as $i => $slide)
      <div class="hero__slide {{ $i === 0 ? 'is-active' : '' }}" @if($slide['image_url']) style="--hero:url('{{ img_url($slide['image_url'], 1900) }}');--hero-sm:url('{{ img_url($slide['image_url'], 900) }}')" @endif>
        @if(!empty($slide['video_url']))
          <video class="hero__video" src="{{ $slide['video_url'] }}"
                 muted loop playsinline webkit-playsinline preload="{{ $i === 0 ? 'auto' : 'metadata' }}" @if($i === 0) autoplay @endif aria-hidden="true" tabindex="-1"></video>
        @elseif(!empty($slide['video_embed']))
          {{-- YouTube / Vimeo / Drive: only the visible slide loads its player (see main.js) --}}
          <iframe class="hero__embed hero__embed--{{ $slide['video_embed']['type'] }}" title="" aria-hidden="true" tabindex="-1" loading="eager" allow="autoplay; encrypted-media; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin"
                  data-src="{{ $slide['video_embed']['src'] }}" @if($i === 0) src="{{ $slide['video_embed']['src'] }}" @endif></iframe>
        @endif
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
  {{-- Sound controls: shown only while a video slide with controllable sound is on screen (see main.js) --}}
  <div class="hero__sound" id="heroSound" hidden>
    <button type="button" class="hero__mute" id="heroMute" aria-label="Turn sound on" aria-pressed="true"><i class="fa-solid fa-volume-xmark"></i></button>
    <input type="range" class="hero__vol" id="heroVol" min="0" max="100" value="70" aria-label="Volume" />
  </div>
  <a href="#after-hero" class="hero__scroll" aria-label="Scroll down"><i class="fa-solid fa-angle-down"></i></a>
</section>
<span id="after-hero"></span>
