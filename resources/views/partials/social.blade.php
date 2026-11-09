@php $icons = ['instagram' => 'fa-instagram', 'facebook' => 'fa-facebook-f', 'youtube' => 'fa-youtube', 'pinterest' => 'fa-pinterest-p']; @endphp
<div class="{{ $wrapClass ?? 'footer__social' }}">
  @foreach($site['social'] as $network => $url)
    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ ucfirst($network) }}"><i class="fa-brands {{ $icons[$network] ?? 'fa-link' }}"></i></a>
  @endforeach
</div>
