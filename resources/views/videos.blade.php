@extends('layouts.app')
@section('content')
  @include('partials.banner', ['banner' => $page['banner']])
  <section class="section">
    <div class="container">
      @if(filled($page['content']))<div class="block__narrow prose" style="margin:0 auto 3rem">{!! rich($page['content']) !!}</div>@endif
      @if($categories)
      <div class="g-toolbar">
        <div class="filters" role="group" aria-label="Filter by category">
          <a href="/videos" class="filter {{ $active ? '' : 'active' }}" data-filter="">All</a>
          @foreach($categories as $c)<a href="/videos?category={{ $c['slug'] }}" class="filter {{ $active === $c['slug'] ? 'active' : '' }}" data-filter="{{ $c['slug'] }}">{{ $c['name'] }}</a>@endforeach
        </div>
      </div>
      @endif
      @if($videos || $categories)
        <div class="vgrid" id="videos" data-videos data-endpoint="/api/v1/videos" data-category="{{ $active }}" data-page="{{ $meta['current_page'] }}" data-last="{{ $meta['last_page'] }}" data-play="{{ $play }}">
          @foreach($videos as $v)@include('partials.video-card')@endforeach
        </div>
        <p class="g-empty center section-lead" @if($videos) hidden @endif>No films in this category yet.</p>
        <div class="center"><button type="button" class="btn btn--outline g-more" @if($meta['current_page'] >= $meta['last_page']) hidden @endif>Load more</button></div>
      @else
        <p class="center section-lead">Our films will appear here soon.</p>
      @endif
    </div>
  </section>
  @include('partials.blocks', ['sections' => $page['sections'] ?? []])
@endsection
@push('scripts')<script src="{{ asset('js/media.js') }}?v={{ filemtime(public_path('js/media.js')) }}" defer></script>@endpush
