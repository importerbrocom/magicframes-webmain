@php
    $c = $site['contact'];
    $hasContact = collect($c)->except('map_embed_url')->filter()->isNotEmpty();
@endphp
<footer class="footer">
  <div class="container footer__grid">
    <div class="footer__brand">
      <img class="logo-img--footer" src="{{ img_url($h['logo_url'], 600) }}" alt="{{ $h['logo_alt'] }}" loading="lazy" />
      @if($site['footer']['about'])<p>{{ $site['footer']['about'] }}</p>@endif
    </div>
    @if($site['menus']['footer'])
    <div class="footer__col">
      <h4>Explore</h4>
      <ul>@foreach($site['menus']['footer'] as $m)<li><a href="{{ $m['url'] }}" @if($m['new_tab']) target="_blank" rel="noopener" @endif>{{ $m['label'] }}</a></li>@endforeach</ul>
    </div>
    @endif
    @if($hasContact)
    <div class="footer__col">
      <h4>Visit</h4>
      <ul>
        @if($c['address'])<li>{!! nl2br(e($c['address'])) !!}</li>@endif
        @if($c['email'])<li><a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></li>@endif
        @if($c['phone'])<li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $c['phone']) }}">{{ $c['phone'] }}</a></li>@endif
        @if($c['hours'])<li>{{ $c['hours'] }}</li>@endif
      </ul>
    </div>
    @endif
    @if($site['social'])
    <div class="footer__col"><h4>Follow</h4>@include('partials.social')</div>
    @endif
  </div>
  <div class="footer__bottom"><p>© {{ date('Y') }} {{ $site['footer']['copyright'] ?: $site['name'] }}</p></div>
</footer>
