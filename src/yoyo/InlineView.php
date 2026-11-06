<?php

namespace Clickfwd\Yoyo;

/**
 * A template a component asks to compile from source, returned by Component::inline().
 * A plain string returned from render() is finished HTML and is never compiled.
 */
final class InlineView
{
    public function __construct(public string $template)
    {
    }
}
