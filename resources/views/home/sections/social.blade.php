@php $igPosts = app(\App\Services\InstagramFeed::class)->posts(); @endphp
@if(!empty($site['social']['instagram']) || $igPosts)
<section class="section social-band">
  <div class="container center">
    <div class="section-head center">
      @if($s['label'])<span class="section-label">{{ $s['label'] }}</span>@endif
      <h2 class="section-title">{{ $s['title'] ?: 'On Instagram' }}</h2>
      @if($s['body'])<p class="section-lead">{{ $s['body'] }}</p>@endif
    </div>
    @if($igPosts)
      <div class="ig-grid">
        @foreach($igPosts as $i => $post)
          <a class="ig-tile" href="{{ $post['permalink'] }}" target="_blank" rel="noopener noreferrer" data-reveal="up" style="--d:{{ ($i % 4) * 0.08 }}s"
             aria-label="{{ $post['caption'] ?: 'View this post on Instagram' }}">
            <img src="{{ \App\Services\InstagramFeed::url($post['image']) }}" alt="{{ $post['caption'] ?: 'Instagram post' }}" loading="lazy" width="320" height="320" />
            <span class="ig-tile__over"><i class="fa-brands fa-instagram"></i></span>
            @if($post['video'])<span class="ig-tile__badge" aria-hidden="true"><i class="fa-solid fa-play"></i></span>@endif
          </a>
        @endforeach
      </div>
    @endif
    @if(!empty($site['social']['instagram']))
      <a href="{{ $site['social']['instagram'] }}" class="btn btn--outline ig-follow" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-instagram"></i>&nbsp; {{ $s['button_label'] ?: 'Follow us' }}</a>
    @endif
  </div>
</section>
@endif
