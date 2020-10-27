<?php

namespace Clickfwd\Yoyo\Interfaces;

use Clickfwd\Yoyo\Component;

interface ComponentResolverInterface
{
    public function __construct($id, $name, $variables, $viewProviders);

    public function source(): ?string;

    public function resolveDynamic($registered): ?Component;

    public function resolveAnonymous($registered): ?Component;

    public function resolveViewProvider(): ViewProviderInterface;
}
