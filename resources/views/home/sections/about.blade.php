@php $hasImages = $s['image_url'] || $s['image_secondary_url']; @endphp
<section class="story" id="about">
  <div class="container story__grid {{ $hasImages ? '' : 'story__grid--single' }}">
    @if($hasImages)
    <div class="story__images">
      @if($s['image_url'])<div class="story__img story__img--one" data-reveal="left"><img src="{{ img_url($s['image_url'], 800) }}" alt="{{ $s['title'] ?: 'About '.$site['name'] }}" loading="lazy" /></div>@endif
      @if($s['image_secondary_url'])<div class="story__img story__img--two" data-reveal="up"><img src="{{ img_url($s['image_secondary_url'], 600) }}" alt="" loading="lazy" /></div>@endif
    </div>
    @endif
    <div class="story__text" data-reveal="right">
      @if($s['label'])<span class="section-label">{{ $s['label'] }}</span>@endif
      @if($s['title'])<h2 class="section-title">{{ $s['title'] }}</h2>@endif
      @if($s['body'])<div class="section-lead">{!! paragraphs($s['body']) !!}</div>@endif
      @if($s['body_secondary'])<div>{!! paragraphs($s['body_secondary']) !!}</div>@endif
      @if($s['button_label'] && $s['button_url'])<a href="{{ $s['button_url'] }}" class="btn btn--outline">{{ $s['button_label'] }}</a>@endif
    </div>
  </div>
</section>
