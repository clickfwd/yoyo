<?php

namespace Clickfwd\Yoyo\Interfaces;

use Clickfwd\Yoyo\Component;
use Psr\Container\ContainerInterface;

interface ComponentResolverInterface
{
    public function __construct(ContainerInterface $container, array $registeredComponents, array $variables);

    public function source(): ?string;

    public function resolveDynamic($id, $name): ?Component;

    public function resolveAnonymous($id, $name): ?Component;

    public function resolveViewProvider(): ViewProviderInterface;
}
