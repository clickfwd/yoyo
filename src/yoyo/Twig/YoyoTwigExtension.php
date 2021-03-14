<?php

namespace Clickfwd\Yoyo\Twig;

use Clickfwd\Yoyo\Services\BrowserEventsService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Markup;
use Twig\TwigFunction;
use function Yoyo\yoyo_render;
use function Yoyo\yoyo_scripts;

class YoyoTwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function getFunctions()
    {
        return [
            $this->yoyo_scripts(),
            $this->yoyo(),
            $this->emit(),
            $this->emitTo(),
            $this->emitSelf(),
            $this->emitUp(),
        ];
    }

    public function getGlobals(): array
    {
        return [
        ];
    }

    private function yoyo_scripts()
    {
        return new TwigFunction('yoyo_scripts', function (): Markup {
            return self::raw(yoyo_scripts());
        });
    }

    private function yoyo()
    {
        return new TwigFunction('yoyo', function ($name, $variables = [], $attributes = []): Markup {
            $variables = $variables ?? [];

            $attributes = $attributes ?? [];

            $output = yoyo_render($name, $variables, $attributes);

            return self::raw($output);
        });
    }

    private function emit()
    {
        return new TwigFunction('emit', function ($eventName, $payload = []) {
            (BrowserEventsService::getInstance())->emit($eventName, $payload);
        });
    }

    private function emitTo()
    {
        return new TwigFunction('emitTo', function ($target, $eventName, $payload = []) {
            (BrowserEventsService::getInstance())->emitTo($target, $eventName, $payload);
        });
    }

    private function emitToWithSelector()
    {
        return new TwigFunction('emitToWithSelector', function ($target, $eventName, $payload = []) {
            (BrowserEventsService::getInstance())->emitToWithSelector($target, $eventName, $payload);
        });
    }

    private function emitSelf()
    {
        return new TwigFunction('emitSelf', function ($eventName, $payload = []) {
            (BrowserEventsService::getInstance())->emitSelf($eventName, $payload);
        });
    }

    private function emitUp()
    {
        return new TwigFunction('emitUp', function ($eventName, $payload = []) {
            (BrowserEventsService::getInstance())->emitUp($eventName, $payload);
        });
    }

    private static function raw($string)
    {
        return new Markup($string, 'UTF-8');
    }
}
