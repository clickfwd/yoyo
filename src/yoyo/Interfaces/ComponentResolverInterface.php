<?php

namespace Clickfwd\Yoyo\Interfaces;

use Clickfwd\Yoyo\Component;
use Psr\Container\ContainerInterface;

interface ComponentResolverInterface
{
    public function __construct(ContainerInterface $container, $id, $name, $variables);

    public function source(): ?string;

    public function resolveDynamic($registered): ?Component;

    public function resolveAnonymous($registered): ?Component;

    public function resolveViewProvider(): ViewProviderInterface;
}
