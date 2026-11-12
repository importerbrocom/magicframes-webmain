<div class="vgrid">
  @foreach($videos as $i => $v)
    <a class="vcard" data-reveal="up" style="--d:{{ ($i % 3) * 0.1 }}s" href="/videos?play={{ $v['slug'] }}" aria-label="Play {{ $v['title'] }}">
      <span class="vcard__media">
        @if($v['thumbnail_url'])<img src="{{ img_url($v['thumbnail_url'], 1280) }}" alt="" loading="lazy" />@endif
        <span class="vcard__play"><i class="fa-solid fa-play"></i></span>
      </span>
      <span class="vcard__title">{{ $v['title'] }}</span>
      @if(!empty($v['category']))<span class="vcard__cat">{{ $v['category']['name'] }}</span>@endif
    </a>
  @endforeach
</div>
