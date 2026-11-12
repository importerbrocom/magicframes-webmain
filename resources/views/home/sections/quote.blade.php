@if($s['title'] || $s['label'])
<section class="quote">
  <div class="quote__bg" data-parallax="0.2" @if($s['image_url']) style="--bg:url('{{ img_url($s['image_url'], 2560) }}');--bg-sm:url('{{ img_url($s['image_url'], 1280) }}')" @endif></div>
  <div class="quote__overlay"></div>
  <div class="container quote__content">
    @if($s['label'])<p class="quote__script reveal-scroll">{{ $s['label'] }}</p>@endif
    @if($s['title'])<h2 class="quote__title reveal-scroll">{{ $s['title'] }}</h2>@endif
  </div>
</section>
@endif
