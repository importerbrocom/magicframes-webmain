@extends('layouts.app')
@section('content')
  @include('partials.banner', ['banner' => $page['banner']])
  @if(filled($page['content']))
    <section class="section"><div class="container block__narrow prose" data-reveal="up">{!! rich($page['content']) !!}</div></section>
  @endif
  @include('partials.blocks', ['sections' => $page['sections'] ?? []])
  @if(blank($page['content']) && empty($page['sections']))
    <section class="section"><div class="container center"><p class="section-lead">This page is being prepared. Please check back soon.</p><a class="btn btn--outline" href="/contact">Contact us</a></div></section>
  @endif
@endsection
