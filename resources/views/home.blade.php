@extends('layouts.app')
@section('content')
  @foreach($home['sections'] as $section)
    @include('home.sections.'.$section['key'], ['s' => $section])
  @endforeach
@endsection
