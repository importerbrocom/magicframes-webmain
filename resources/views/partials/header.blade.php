@if($h['visible'])
@php
    $styleClass = match ($h['background_style']) { 'cream' => 'header--cream', 'transparent_on_top' => 'header--transparent', default => '' };
    $link = fn ($m) => '<li><a href="'.e($m['url']).'" class="nav__link'.(nav_active($m['url']) ? ' active' : '').'"'.($m['new_tab'] ? ' target="_blank" rel="noopener"' : '').'>'.e($m['label']).'</a></li>';
@endphp
<header class="header {{ $styleClass }} {{ $h['sticky'] ? '' : 'header--static' }}" id="header" data-style="{{ $h['background_style'] }}">
  <div class="header__inner">
    <div class="header__left">
      @if($h['cta'])<a href="{{ $h['cta']['url'] }}" class="rsvp-btn">{{ $h['cta']['label'] }}</a>@endif
      <nav class="nav" aria-label="Primary"><ul>{!! collect($site['menus']['left'])->map($link)->implode('') !!}</ul></nav>
    </div>
    <a href="/" class="logo-badge" aria-label="{{ $site['name'] }} home">
      <img class="logo-img" src="{{ img_url($h['logo_url'], 600) }}" alt="{{ $h['logo_alt'] }}" />
    </a>
    <div class="header__right">
      <nav class="nav" aria-label="Secondary"><ul>{!! collect($site['menus']['right'])->map($link)->implode('') !!}</ul></nav>
      <button class="burger" id="burger" aria-label="Open menu" aria-controls="mobileMenu" aria-expanded="false"><span></span><span></span><span></span></button>
    </div>
  </div>
</header>
@endif
