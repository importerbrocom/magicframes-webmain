@extends('layouts.app')
@push('head')
<script type="application/ld+json">{!! json_encode(array_filter([
  '@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $post['title'],
  'datePublished' => $post['published_at'], 'image' => $post['cover_url'] ?: null,
  'author' => $post['author'] ? ['@type' => 'Person', 'name' => $post['author']] : null,
  'publisher' => ['@type' => 'Organization', 'name' => $site['name']],
  'mainEntityOfPage' => url('/blog/'.$post['slug']),
]), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endpush
@section('content')
  @include('partials.banner', [
    'banner' => ['label' => $post['category'] ?: 'Journal', 'title' => $post['title'], 'image_url' => $post['cover_url'], 'crumb' => \Illuminate\Support\Str::limit($post['title'], 40)],
    'crumbs' => [['Blog', '/blog']],
  ])
  <article class="section">
    <div class="container block__narrow">
      <p class="post__date center">{{ $post['published_label'] }}@if($post['author']) · {{ $post['author'] }}@endif · {{ $post['reading_minutes'] }} min read</p>
      <div class="prose post-content">{!! rich($post['content']) !!}</div>
      <div class="gnav"><a class="gnav__prev" href="/blog"><span class="gnav__dir"><i class="fa-solid fa-arrow-left"></i> Back</span><span class="gnav__name">All articles</span></a><a class="gnav__next" href="/contact"><span class="gnav__dir">Plan your day <i class="fa-solid fa-arrow-right"></i></span><span class="gnav__name">Get in touch</span></a></div>
    </div>
  </article>
@endsection
