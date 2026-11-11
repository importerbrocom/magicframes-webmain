<a class="vcard" href="/videos?play={{ $v['slug'] }}" data-video data-slug="{{ $v['slug'] }}"
   data-title="{{ $v['title'] }}" data-desc="{{ $v['description'] }}" data-embed="{{ $v['embed_url'] }}" data-file="{{ $v['file_url'] }}" data-poster="{{ $v['thumbnail_url'] }}">
  <span class="vcard__media">
    @if($v['thumbnail_url'])<img src="{{ img_url($v['thumbnail_url'], 1280) }}" alt="" loading="lazy" decoding="async" />@endif
    <span class="vcard__play"><i class="fa-solid fa-play"></i></span>
  </span>
  <span class="vcard__title">{{ $v['title'] }}</span>
  @if(!empty($v['category']))<span class="vcard__cat">{{ $v['category']['name'] }}</span>@endif
  @if($v['description'])<span class="vcard__desc">{{ \Illuminate\Support\Str::limit($v['description'], 110) }}</span>@endif
</a>
