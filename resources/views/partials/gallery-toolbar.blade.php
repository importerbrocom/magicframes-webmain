{{-- $categories, $active; links work without JS, media.js upgrades them to in-place filtering --}}
<div class="g-toolbar">
  <div class="filters" role="group" aria-label="Filter by category">
    <a href="/gallery" class="filter {{ $active ? '' : 'active' }}" data-filter="">All</a>
    @foreach($categories as $c)
      <a href="/gallery?category={{ $c['slug'] }}" class="filter {{ $active === $c['slug'] ? 'active' : '' }}" data-filter="{{ $c['slug'] }}">{{ $c['name'] }}</a>
    @endforeach
  </div>
  <div class="layout-toggle" role="group" aria-label="Layout">
    <button type="button" data-layout="masonry" class="active" aria-label="Masonry layout"><i class="fa-solid fa-table-columns"></i></button>
    <button type="button" data-layout="grid" aria-label="Grid layout"><i class="fa-solid fa-table-cells"></i></button>
  </div>
</div>
