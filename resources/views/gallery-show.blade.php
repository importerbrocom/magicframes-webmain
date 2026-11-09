@extends('layouts.app')
@section('content')
  @include('partials.banner', [
    'banner' => ['label' => 'Gallery', 'title' => $category['name'], 'image_url' => $category['cover_url'], 'crumb' => $category['name']],
    'crumbs' => [['Portfolio', '/portfolio']],
  ])
  <section class="section">
    <div class="container">
      @if($category['description'])<p class="section-lead center" style="max-width:720px;margin:0 auto 3rem">{{ $category['description'] }}</p>@endif
      <div class="g-toolbar g-toolbar--end">
        <div class="layout-toggle" role="group" aria-label="Layout">
          <button type="button" data-layout="masonry" class="active" aria-label="Masonry layout"><i class="fa-solid fa-table-columns"></i></button>
          <button type="button" data-layout="grid" aria-label="Grid layout"><i class="fa-solid fa-table-cells"></i></button>
        </div>
      </div>
      <div class="masonry" id="gallery" data-gallery data-endpoint="/api/v1/gallery/images" data-category="{{ $category['slug'] }}" data-page="{{ $meta['current_page'] }}" data-last="{{ $meta['last_page'] }}" data-per-page="24">
        @foreach($images as $img)@include('partials.gallery-item')@endforeach
      </div>
      <div class="center"><button type="button" class="btn btn--outline g-more" @if($meta['current_page'] >= $meta['last_page']) hidden @endif>Load more</button></div>

      @if($prev || $next)
      <div class="gnav">
        @if($prev)<a class="gnav__prev" href="/gallery/{{ $prev['slug'] }}"><span class="gnav__dir"><i class="fa-solid fa-arrow-left"></i> Previous</span><span class="gnav__name">{{ $prev['name'] }}</span></a>@else<span></span>@endif
        @if($next)<a class="gnav__next" href="/gallery/{{ $next['slug'] }}"><span class="gnav__dir">Next <i class="fa-solid fa-arrow-right"></i></span><span class="gnav__name">{{ $next['name'] }}</span></a>@endif
      </div>
      @endif
    </div>
  </section>
@endsection
@push('scripts')<script src="{{ asset('js/media.js') }}?v={{ filemtime(public_path('js/media.js')) }}" defer></script>@endpush
