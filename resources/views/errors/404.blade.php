@php
    $site = \App\Services\SiteData::site();
    $seo = ['title' => 'Page not found — '.$site['name'], 'noindex' => true];
@endphp
@extends('layouts.app', ['site' => $site, 'seo' => $seo])
@section('content')
  <section class="notfound">
    <div class="container center">
      <span class="section-label">Oops</span>
      <p class="notfound__code">404</p>
      <h1 class="section-title">This page has wandered off</h1>
      <p class="section-lead">The page you are looking for doesn't exist or has moved.</p>
      <p><a href="/" class="btn">Back to Home</a> &nbsp; <a href="/contact" class="btn btn--outline">Contact us</a></p>
    </div>
  </section>
@endsection
