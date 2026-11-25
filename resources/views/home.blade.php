@extends('layouts.app')
@section('content')
  @foreach($home['sections'] as $section)
    @include('home.sections.'.$section['key'], ['s' => $section])
    @if($section['key'] === 'hero' && $site['effects'] && count($home['marquee']) >= 2)
      @include('partials.marquee', ['names' => $home['marquee']])
    @endif
  @endforeach
@endsection
