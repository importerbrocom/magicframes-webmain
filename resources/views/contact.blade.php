@extends('layouts.app')
@section('content')
  @include('partials.banner', ['banner' => $page['banner']])
  <section class="section">
    <div class="container contact-grid">
      <div class="contact-info" data-reveal="left">
        <div>
          <span class="section-label">Get in Touch</span>
          <h2 class="section-title">Let's plan<br/> something beautiful</h2>
          <p class="section-lead">Tell us about your day and we'll be in touch as soon as we can.</p>
        </div>
        @include('partials.contact-lines')
        @if($site['social'])@include('partials.social', ['wrapClass' => 'footer__social footer__social--dark'])@endif
      </div>
      @include('partials.enquiry-form', ['reveal' => true])
    </div>
    @if($site['contact']['map_embed_url'])
    <div class="container">
      <div class="map-embed" data-reveal="up">
        <iframe title="{{ $site['name'] }} location" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="{{ $site['contact']['map_embed_url'] }}"></iframe>
      </div>
    </div>
    @endif
  </section>
  @if(filled($page['content']))
    <section class="section"><div class="container block__narrow prose">{!! rich($page['content']) !!}</div></section>
  @endif
  @include('partials.blocks', ['sections' => $page['sections'] ?? []])
@endsection
