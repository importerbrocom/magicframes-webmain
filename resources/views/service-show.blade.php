@extends('layouts.app')
@push('head')
<script type="application/ld+json">{!! json_encode(array_filter([
  '@context' => 'https://schema.org', '@type' => 'Service', 'name' => $service['title'],
  'description' => $service['short_description'] ?: null, 'image' => $service['image_url'] ?: null,
  'provider' => ['@type' => 'Organization', 'name' => $site['name'], 'url' => url('/')],
  'url' => url('/services/'.$service['slug']),
]), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@if($service['faqs'])
<script type="application/ld+json">{!! json_encode([
  '@context' => 'https://schema.org', '@type' => 'FAQPage',
  'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']]], $service['faqs']),
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endif
@endpush
@section('content')
  @include('partials.banner', [
    'banner' => ['label' => 'Our Services', 'title' => $service['title'], 'image_url' => $service['image_url'] ?: ($service['photos'][0]['url'] ?? null), 'crumb' => $service['title']],
    'crumbs' => [['Our Services', '/services']],
  ])

  <section class="section svc">
    <div class="container block__narrow">
      @if($service['short_description'])<p class="section-lead center svc__lead" data-reveal="up">{{ $service['short_description'] }}</p>@endif
      @if(filled($service['description']))<div class="prose svc__body" data-reveal="up">{!! rich($service['description']) !!}</div>@endif
      @if($service['highlights'])
        <div class="svc__incl" data-reveal="up">
          <h2 class="svc__h">What is included</h2>
          <ul class="svc__list">
            @foreach($service['highlights'] as $point)<li><i class="fa-solid fa-circle-check"></i><span>{{ $point }}</span></li>@endforeach
          </ul>
        </div>
      @endif
    </div>
  </section>

  @if($service['photos'])
  <section class="section svc svc--alt">
    <div class="container">
      <div class="section-head center"><span class="section-label">Gallery</span><h2 class="section-title">{{ $service['title'] }} Photos</h2></div>
      <div class="masonry" id="gallery" data-gallery data-page="1" data-last="1">
        @foreach($service['photos'] as $ph)
          <a class="m-item" href="{{ img_url($ph['url'], 1800) }}" data-lightbox data-full="{{ img_url($ph['url'], 1800) }}"
             data-title="{{ $ph['title'] }}" data-caption="" style="aspect-ratio:4/3">
            <img src="{{ img_url($ph['url'], 700) }}" alt="{{ $ph['alt'] ?: $service['title'] }}" loading="lazy" decoding="async" />
            <span class="m-item__cap">@if($ph['title']){{ $ph['title'] }}@else<i class="fa-solid fa-magnifying-glass-plus"></i>@endif</span>
          </a>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  @if($service['videos'])
  <section class="section svc">
    <div class="container">
      <div class="section-head center"><span class="section-label">Films</span><h2 class="section-title">{{ $service['title'] }} Videos</h2></div>
      <div class="vgrid" id="videos" data-videos data-page="1" data-last="1">
        @foreach($service['videos'] as $v)
          <a class="vcard" href="#" data-video data-slug="" data-title="{{ $v['title'] }}" data-desc="{{ $v['description'] }}" data-embed="{{ $v['embed'] }}" data-file="{{ $v['file'] }}" data-poster="{{ $v['thumb'] }}">
            <span class="vcard__media">
              @if($v['thumb'])<img src="{{ img_url($v['thumb'], 800) }}" alt="" loading="lazy" decoding="async" />@endif
              <span class="vcard__play"><i class="fa-solid fa-play"></i></span>
            </span>
            <span class="vcard__title">{{ $v['title'] }}</span>
            @if($v['description'])<span class="vcard__desc">{{ \Illuminate\Support\Str::limit($v['description'], 110) }}</span>@endif
          </a>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  @if($service['faqs'])
  <section class="section svc svc--alt">
    <div class="container block__narrow">
      <div class="section-head center"><span class="section-label">Good to know</span><h2 class="section-title">Questions &amp; Answers</h2></div>
      <div class="svc__faq">
        @foreach($service['faqs'] as $f)
          <details class="svc__q" @if($loop->first) open @endif><summary>{{ $f['question'] }}</summary><div class="svc__a">{!! paragraphs($f['answer']) !!}</div></details>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  <section class="section svc svc__cta">
    <div class="container center">
      <h2 class="section-title">Interested in {{ $service['title'] }}?</h2>
      <p class="section-lead">Tell us about your day and we will get back to you.</p>
      <a href="/contact?service={{ $service['slug'] }}" class="btn">Enquire now</a>
    </div>
  </section>

  @if($others)
  <section class="services svc__more-services">
    <div class="container">
      <div class="section-head center"><span class="section-label">Explore</span><h2 class="section-title">Other Services</h2></div>
      @include('partials.service-cards', ['services' => array_slice($others, 0, 3)])
    </div>
  </section>
  @endif
@endsection
@push('scripts')<script src="{{ asset('js/media.js') }}?v={{ filemtime(public_path('js/media.js')) }}" defer></script>@endpush
