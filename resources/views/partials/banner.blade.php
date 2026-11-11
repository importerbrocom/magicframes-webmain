{{-- Inner-page banner. Expects $banner = [label,title,image_url] and optional $crumbs = [[label,url],...] --}}
<section class="page-banner">
  @if(!empty($banner['image_url']))
    <div class="page-banner__bg" style="--bg:url('{{ img_url($banner['image_url'], 2560) }}');--bg-sm:url('{{ img_url($banner['image_url'], 1280) }}')"></div>
  @endif
  <div class="page-banner__overlay"></div>
  <div class="page-banner__content">
    @if(!empty($banner['label']))<span class="page-banner__label">{{ $banner['label'] }}</span>@endif
    <h1 class="page-banner__title">{{ $banner['title'] }}</h1>
    <p class="page-banner__crumbs">
      <a href="/">Home</a>
      @foreach(($crumbs ?? []) as [$cl, $cu])<span>/</span><a href="{{ $cu }}">{{ $cl }}</a>@endforeach
      <span>/</span>{{ $banner['crumb'] ?? $banner['title'] }}
    </p>
  </div>
</section>
