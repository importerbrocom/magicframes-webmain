@extends('layouts.app')
@section('content')
  @include('partials.banner', ['banner' => $page['banner']])
  <section class="section">
    <div class="container">
      @if(filled($page['content']))<div class="block__narrow prose" style="margin:0 auto 3rem">{!! rich($page['content']) !!}</div>@endif
      <div class="g-toolbar">
        <div class="filters" role="group" aria-label="Filter by category">
          <a href="/blog" class="filter {{ $activeCategory ? '' : 'active' }}">All</a>
          @foreach($categories as $c)<a href="/blog?category={{ urlencode($c) }}" class="filter {{ $activeCategory === $c ? 'active' : '' }}">{{ $c }}</a>@endforeach
        </div>
        <form class="blog-search" action="/blog" method="get" role="search">
          <input type="search" name="q" value="{{ $search }}" placeholder="Search articles" aria-label="Search articles" maxlength="100" />
          <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
      </div>

      @if($posts)
      <div class="blog-grid">
        @foreach($posts as $i => $p)
          <article class="post" data-reveal="up" style="--d:{{ ($i % 3) * 0.1 }}s">
            <a href="/blog/{{ $p['slug'] }}" class="post__media" tabindex="-1" aria-hidden="true">@if($p['cover_url'])<img src="{{ img_url($p['cover_url'], 1280) }}" alt="{{ $p['cover_alt'] }}" loading="lazy" decoding="async" />@endif</a>
            <div class="post__body">
              <span class="post__date">{{ $p['published_label'] }}@if($p['category']) · {{ $p['category'] }}@endif</span>
              <h3 class="post__title"><a href="/blog/{{ $p['slug'] }}">{{ $p['title'] }}</a></h3>
              <p class="post__excerpt">{{ $p['excerpt'] }}</p>
              <a href="/blog/{{ $p['slug'] }}" class="post__more">Read More</a>
            </div>
          </article>
        @endforeach
      </div>
      @if($meta['last_page'] > 1)
      <nav class="pager" aria-label="Pagination">
        @php $qs = fn ($pg) => '/blog?'.http_build_query(array_filter(['category' => $activeCategory, 'q' => $search, 'page' => $pg > 1 ? $pg : null])); @endphp
        @if($meta['current_page'] > 1)<a href="{{ $qs($meta['current_page'] - 1) }}" rel="prev"><i class="fa-solid fa-arrow-left"></i> Newer</a>@else<span></span>@endif
        <span class="pager__info">Page {{ $meta['current_page'] }} of {{ $meta['last_page'] }}</span>
        @if($meta['current_page'] < $meta['last_page'])<a href="{{ $qs($meta['current_page'] + 1) }}" rel="next">Older <i class="fa-solid fa-arrow-right"></i></a>@else<span></span>@endif
      </nav>
      @endif
      @else
        <p class="center section-lead">{{ ($search || $activeCategory) ? 'No articles match your search.' : 'Our first articles are on their way.' }}</p>
      @endif
    </div>
  </section>
  @include('partials.blocks', ['sections' => $page['sections'] ?? []])
@endsection
