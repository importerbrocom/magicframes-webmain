{{-- Admin-defined content blocks of a page --}}
@foreach($sections as $b)
  @switch($b['type'])
    @case('text')
      <section class="section block block--text"><div class="container block__narrow" data-reveal="up">
        @if($b['label'])<span class="section-label">{{ $b['label'] }}</span>@endif
        @if($b['heading'])<h2 class="section-title">{{ $b['heading'] }}</h2>@endif
        <div class="prose">{!! rich($b['body']) !!}</div>
        @if($b['button_label'] && $b['button_url'])<a href="{{ $b['button_url'] }}" class="btn btn--outline">{{ $b['button_label'] }}</a>@endif
      </div></section>
      @break
    @case('image_text')
      <section class="section block block--split"><div class="container story__grid {{ $b['image_right'] ? 'block--flip' : '' }}">
        <div class="block__media" data-reveal="{{ $b['image_right'] ? 'right' : 'left' }}">@if($b['image_url'])<img src="{{ img_url($b['image_url'], 1400) }}" alt="{{ $b['image_alt'] }}" loading="lazy" />@endif</div>
        <div class="story__text" data-reveal="{{ $b['image_right'] ? 'left' : 'right' }}">
          @if($b['label'])<span class="section-label">{{ $b['label'] }}</span>@endif
          @if($b['heading'])<h2 class="section-title">{{ $b['heading'] }}</h2>@endif
          <div class="prose">{!! rich($b['body']) !!}</div>
          @if($b['button_label'] && $b['button_url'])<a href="{{ $b['button_url'] }}" class="btn btn--outline">{{ $b['button_label'] }}</a>@endif
        </div>
      </div></section>
      @break
    @case('image')
      @if($b['image_url'])<section class="block block--image"><img src="{{ img_url($b['image_url'], 2560) }}" alt="{{ $b['image_alt'] }}" loading="lazy" data-reveal="up" /></section>@endif
      @break
    @case('video')
      <section class="section block block--video"><div class="container block__narrow">
        @if($b['label'])<span class="section-label">{{ $b['label'] }}</span>@endif
        @if($b['heading'])<h2 class="section-title">{{ $b['heading'] }}</h2>@endif
        @if($b['embed_url'])<div class="embed" data-reveal="up"><iframe src="{{ $b['embed_url'] }}" title="{{ $b['heading'] ?: 'Video' }}" loading="lazy" allowfullscreen allow="accelerometer; encrypted-media; picture-in-picture; fullscreen"></iframe></div>@endif
        <div class="prose">{!! rich($b['body']) !!}</div>
      </div></section>
      @break
    @case('cta')
      <section class="section block block--cta" style="background:var(--cream-2)"><div class="container center" data-reveal="up">
        @if($b['label'])<span class="section-label">{{ $b['label'] }}</span>@endif
        @if($b['heading'])<h2 class="section-title">{{ $b['heading'] }}</h2>@endif
        <div class="prose">{!! rich($b['body']) !!}</div>
        @if($b['button_label'] && $b['button_url'])<a href="{{ $b['button_url'] }}" class="btn">{{ $b['button_label'] }}</a>@endif
      </div></section>
      @break
    @case('team')
      @if($b['members'])
      <section class="section block block--team"><div class="container">
        <div class="section-head center" data-reveal="up">
          <span class="section-label">{{ $b['label'] ?: 'The people behind the lens' }}</span>
          <h2 class="section-title">{{ $b['heading'] ?: 'Our Team' }}</h2>
          @if(filled($b['body']))<div class="prose section-lead">{!! rich($b['body']) !!}</div>@endif
        </div>
        <div class="team__grid">
          @foreach($b['members'] as $i => $m)
            <article class="team__card" data-reveal="up" style="--d:{{ ($i % 4) * 0.1 }}s">
              <div class="team__photo">
                @if($m['photo_url'])<img src="{{ img_url($m['photo_url'], 640) }}" alt="{{ $m['name'] }}" loading="lazy" decoding="async" />
                @else<span class="team__initials" aria-hidden="true">{{ collect(preg_split('/\s+/', trim($m['name'])))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') }}</span>@endif
              </div>
              <h3 class="team__name">{{ $m['name'] }}</h3>
              @if($m['position'])<p class="team__role">{{ $m['position'] }}</p>@endif
            </article>
          @endforeach
        </div>
      </div></section>
      @endif
      @break
  @endswitch
@endforeach
