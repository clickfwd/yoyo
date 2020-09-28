<?php

namespace Clickfwd\Yoyo\Interfaces;

interface View
{
    public function __construct($view);

    public function render($template, $vars = []): self;

    public function makeFromString($content, $vars = []): string;

    public function exists($template): bool;

    public function getProviderInstance();
}
