<section class="counters">
  <div class="container counters__grid">
    @foreach($s['items'] as $i => $stat)
      <div class="counter" data-reveal="up" style="--d:{{ $i * 0.1 }}s">
        <span class="counter__num" data-count="{{ $stat['value'] }}" data-suffix="{{ $stat['suffix'] }}">{{ number_format($stat['value']) }}{{ $stat['suffix'] }}</span>
        <span class="counter__label">{{ $stat['label'] }}</span>
      </div>
    @endforeach
  </div>
</section>
