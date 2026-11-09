@extends('layouts.app')
@section('content')
  @include('partials.banner', ['banner' => $page['banner']])
  <section class="section">
    <div class="container">
      @if(filled($page['content']))<div class="block__narrow prose center-text" style="margin:0 auto 3rem">{!! rich($page['content']) !!}</div>@endif
      @if($categories)
        @include('partials.gallery-toolbar')
        <div class="masonry" id="gallery" data-gallery data-endpoint="/api/v1/gallery/images" data-category="{{ $active }}" data-page="{{ $meta['current_page'] }}" data-last="{{ $meta['last_page'] }}" data-per-page="24">
          @foreach($images as $img)@include('partials.gallery-item')@endforeach
        </div>
        <p class="g-empty center section-lead" @if($images) hidden @endif>No photographs in this category yet.</p>
        <div class="center"><button type="button" class="btn btn--outline g-more" @if($meta['current_page'] >= $meta['last_page']) hidden @endif>Load more</button></div>
        <noscript>@if($meta['current_page'] < $meta['last_page'])<p class="center">More photographs are available with JavaScript enabled.</p>@endif</noscript>
      @else
        <p class="center section-lead">Our photographs will appear here soon.</p>
      @endif
    </div>
  </section>
  @include('partials.blocks', ['sections' => $page['sections'] ?? []])
@endsection
@push('scripts')<script src="{{ asset('js/media.js') }}?v={{ filemtime(public_path('js/media.js')) }}" defer></script>@endpush
