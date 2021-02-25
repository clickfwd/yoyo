<div id="counter" yoyo:val.count="{{ $count }}">
    <button yoyo:get="increment">+</button>
    <button yoyo:get="decrement">-</button>
    <span>{{ $count }}</span>
    <span>{{ $this->currentCount }}</span>
</div>