@php $ratio = ($img['width'] && $img['height']) ? $img['width'].'/'.$img['height'] : '4/3'; @endphp
<a class="m-item" href="{{ $img['large_url'] }}" data-lightbox data-full="{{ $img['large_url'] }}"
   data-title="{{ $img['title'] }}" data-caption="{{ $img['caption'] }}" style="aspect-ratio:{{ $ratio }}">
  <img src="{{ $img['thumb_url'] }}" @if($img['srcset']) srcset="{{ $img['srcset'] }}" sizes="(max-width:600px) 50vw, (max-width:1000px) 33vw, 25vw" @endif
       alt="{{ $img['alt'] ?: ($img['category']['name'] ?? 'Photograph') }}" @if($img['width']) width="{{ $img['width'] }}" height="{{ $img['height'] }}" @endif loading="lazy" decoding="async" />
  <span class="m-item__cap">@if($img['title']){{ $img['title'] }}@else<i class="fa-solid fa-magnifying-glass-plus"></i>@endif</span>
</a>
