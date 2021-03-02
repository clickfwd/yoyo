<div id="counter" yoyo:val.count="{{ $count }}">
    <button yoyo:get="increment">+</button>
    <span>{{ $count }}</span>
    <span>{{ $this->currentCount }}</span>
</div>