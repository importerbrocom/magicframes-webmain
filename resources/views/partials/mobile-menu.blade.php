<div class="mobile-menu" id="mobileMenu" role="dialog" aria-label="Menu">
  <button class="mobile-menu__close" id="mobileClose" aria-label="Close menu"><i class="fa-solid fa-xmark"></i></button>
  <ul>
    @foreach($site['menus']['mobile'] as $m)
      <li><a href="{{ $m['url'] }}" @if($m['new_tab']) target="_blank" rel="noopener" @endif>{{ $m['label'] }}</a></li>
    @endforeach
    @if($h['cta'])<li><a href="{{ $h['cta']['url'] }}">{{ $h['cta']['label'] }}</a></li>@endif
  </ul>
  @if($h['social_in_mobile_menu'] && $site['social'])
    @include('partials.social', ['wrapClass' => 'mobile-menu__social'])
  @endif
</div>
