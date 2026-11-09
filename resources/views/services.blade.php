@extends('layouts.app')
@section('content')
  @include('partials.banner', ['banner' => $page['banner']])
  @if(filled($page['content']))
    <section class="section"><div class="container block__narrow prose" data-reveal="up">{!! rich($page['content']) !!}</div></section>
  @endif
  <section class="services section">
    <div class="container">
      @if($services)
        @include('partials.service-cards', ['services' => $services])
      @else
        <p class="center section-lead">Our services will be listed here shortly.</p>
      @endif
    </div>
  </section>
  @include('partials.blocks', ['sections' => $page['sections'] ?? []])
@endsection
