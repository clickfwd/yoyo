<?php

namespace Clickfwd\Yoyo\Interfaces;

interface ViewProviderInterface
{
    public function __construct($view);

    public function render($template, $vars = []): self;

    public function makeFromString($content, $vars = []): string;

    public function exists($template): bool;

    public function getProviderInstance();

    public function startYoyoRendering($component): void;

    public function stopYoyoRendering(): void;
}
