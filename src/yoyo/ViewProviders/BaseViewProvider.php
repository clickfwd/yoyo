<?php

namespace Clickfwd\Yoyo\ViewProviders;

abstract class BaseViewProvider
{
    protected $view;

    protected $yoyoComponent;

    protected $hasYoyoComponent = false;

    private $preparingComponents = [];

    private $isPendingRender = false;

    private $cloneable;

    public function startYoyoRendering($component): void
    {
        $this->preparingComponents[] = [$this->yoyoComponent, $this->hasYoyoComponent];
        $this->yoyoComponent = $component;
        $this->hasYoyoComponent = true;
    }

    public function stopYoyoRendering(): void
    {
        [$this->yoyoComponent, $this->hasYoyoComponent] = array_pop($this->preparingComponents) ?? [null, false];
    }

    protected function pendingRender(): static
    {
        if ($this->isPendingRender) {
            return $this;
        }

        $this->cloneable ??= (new \ReflectionObject($this))->isCloneable();

        if (! $this->cloneable) {
            // Subclasses that forbid cloning retain the shared, legacy render value.
            $this->preparingComponents = [];

            return $this;
        }

        $pending = clone $this;
        $pending->isPendingRender = true;
        $pending->preparingComponents = [];
        [$this->yoyoComponent, $this->hasYoyoComponent] = array_pop($this->preparingComponents) ?? [null, false];

        return $pending;
    }

    public function getProviderInstance()
    {
        return $this->view;
    }
}
