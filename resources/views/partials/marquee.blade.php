{{-- Decorative band of gallery names. The 2nd copy is aria-hidden (only for the seamless loop). --}}
@php $set = array_values(array_merge($names, count($names) < 5 ? $names : [], count($names) < 3 ? $names : [])); @endphp
<div class="marquee" role="region" aria-label="Our galleries">
  <div class="marquee__track">
    @foreach([false, true] as $dup)
      <ul class="marquee__list" @if($dup) aria-hidden="true" @endif>
        @foreach($set as $n)<li><a href="/gallery" @if($dup) tabindex="-1" @endif>{{ $n }}</a></li>@endforeach
      </ul>
    @endforeach
  </div>
</div>
