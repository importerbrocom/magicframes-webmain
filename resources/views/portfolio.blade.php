@extends('layouts.app')
@section('content')
  @include('partials.banner', ['banner' => $page['banner']])
  @if(filled($page['content']))<section class="section"><div class="container block__narrow prose" data-reveal="up">{!! rich($page['content']) !!}</div></section>@endif
  <section class="section">
    <div class="container">
      @if($categories)
      <div class="pf-grid">
        @foreach($categories as $i => $cat)
          <a class="pf-card" href="/gallery/{{ $cat['slug'] }}" data-reveal="up" style="--d:{{ ($i % 3) * 0.1 }}s">
            <div class="pf-card__media">
              @if($cat['cover_url'])<img src="{{ img_url($cat['cover_url'], 1400) }}" alt="{{ $cat['name'] }}" loading="lazy" decoding="async" />@endif
              <span class="pf-card__view"><i class="fa-solid fa-arrow-right"></i></span>
            </div>
            <div class="pf-card__body">
              <span class="pf-card__cat">Gallery</span>
              <h3 class="pf-card__title">{{ $cat['name'] }}</h3>
              <span class="pf-card__count">{{ $cat['images_count'] }} {{ \Illuminate\Support\Str::plural('Photo', $cat['images_count']) }}</span>
            </div>
          </a>
        @endforeach
      </div>
      @else
        <p class="center section-lead">Our galleries will appear here soon.</p>
      @endif
    </div>
  </section>
  @include('partials.blocks', ['sections' => $page['sections'] ?? []])
@endsection
