<div>{{ $this->owner }}:before @yoyo("render-owner", ["owner" => "child", "value" => "child"]) @include("render-ownership.owner", ["value" => "partial"]) {{ $this->owner }}:after</div>
