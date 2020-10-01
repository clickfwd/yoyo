<?php

namespace Clickfwd\Yoyo;

use DOMDocument;
use DOMXpath;

class YoyoCompiler
{
    private $componentId;

    private $name;

    private $variables;

    private $attributes;

    private $spinning;

    private $idCounter = 1;

    public const HTMX_METHOD_ATTRIBUTES = [
        'boost',
        'delete',
        'get',
        'patch',
        'post',
        'put',
        'sse',
        'ws',
    ];

    public const YOYO_ATTRIBUTES = [
        'confirm',
        'encoding',
        'ext',
        'history-elt',
        'include',
        'indicator',
        'on',
        'params',
        'prompt',
        'push-url',
        'select',
        'swap-oob',
        'swap',
        'target',
        'vars',
    ];

    public const YOYO_TO_HX_ATTRIBUTE_REMAP = [
        'on' => 'trigger',
    ];

    public const COMPONENT_DEFAULT_ACTION = 'render';

    public const COMPONENT_WRAPPER_CLASS = 'yoyo-wrapper';

    public const YOYO_PREFIX = 'yoyo';

    public const HTMX_PREFIX = 'hx';

    public function __construct($componentId, $name, $variables, $attributes, $spinning)
    {
        $this->componentId = $componentId;

        $this->name = $name;

        $this->variables = $variables;

        $this->attributes = $attributes;

        $this->spinning = $spinning;
    }

    public function compile($html): string
    {
        if (! trim($html)) {
            return $html;
        }

        // For each yoyo: attribute found, add new yoyo attribute that can be
        // used by XPath to find the elements which cannot be found when using
        // colons in attribute names

        $prefix = self::YOYO_PREFIX;

        $html = preg_replace('/yoyo:(.*)="(.*)"/', "$prefix $prefix:\$1=\"\$2\"", $html);

        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        $dom = new DOMDocument();

        $internalErrors = libxml_use_internal_errors(true);

        $dom->loadHTML($html);

        libxml_use_internal_errors($internalErrors);

        if (! ($node = $this->getComponentRootNode($dom))) {
            $html = $this->getOuterHTML($dom);

            unset($dom);

            return $this->compile('<div>'.$html.'</div>');
        }

        $xpath = new DOMXPath($dom);

        $elements = $xpath->query('//form');

        foreach ($elements as $key => $element) {
            $this->addFormBehavior($element);
        }

        // Prevent infinite loop with on 'load' event on root node with outerHTML swap

        $this->removeOnLoadEventWhenSpinning($node);

        $this->addComponentRootAttributes($node);

        $this->addComponentChildrenAttributes($dom);

        $doOuterHtmlSwap = ! $this->elementHasAttributeWithValue($node, self::hxprefix('swap'), 'innerHTML');

        if ($this->spinning && ! $doOuterHtmlSwap) {
            $output = $this->getInnerHTML($dom);
        } else {
            $output = $this->getOuterHTML($dom);
        }

        return $output;
    }

    private function addComponentRootAttributes($element)
    {
        if ($element->hasAttribute(self::yoprefix('ignore'))) {
            $element->removeAttribute(self::yoprefix('ignore'));

            return;
        }

        $element->setAttribute(self::YOYO_PREFIX, '');

        // Discard generated component ID and use hardcoded one if found
        $id = $element->getAttribute('id');

        if ($id !== '') {
            $this->componentId = $id;
        }

        $this->addMethodAttribute($element);

        // Get default attributes

        $attributes = $this->getComponentAttributes($this->componentId);

        // Merge, or in some cases replace, defaults with existing attributes at the root node level

        if (! $element->hasAttribute('id')) {
            $element->setAttribute('id', $this->componentId);
        }

        // Add yoyo extension attribute and merge existing extensions

        if ($ext = $element->getAttribute(self::yoprefix('ext'))) {
            $element->removeAttribute(self::yoprefix('ext'));
            $attributes['ext'] .= ', '.$ext;
        }

        $class = $element->getAttribute('class');

        $element->setAttribute('class', self::COMPONENT_WRAPPER_CLASS.($class ? ' '.$class : ''));

        $element->setAttribute(self::yoprefix('name'), $this->name);

        if ($trigger = $element->getAttribute(self::yoprefix('on'))) {
            $attributes['on'] .= ', '.$trigger;
        }

        // Vars

        if ($vars = $element->getAttribute(self::yoprefix('vars'))) {
            $element->removeAttribute(self::yoprefix('vars'));

            $vars = YoyoHelpers::decode_vars($vars);

            $attributes['vars'] = array_merge($attributes['vars'], $vars);
        }

        $attributes['vars'] = YoyoHelpers::encode_vars($attributes['vars']);

        // Add all attributes

        foreach ($attributes as $attr => $value) {
            if (! $value) {
                $value = $element->getAttribute(self::yoprefix($attr));
            }

            if ($value) {
                $this->remapAndReplaceAttribute($element, $attr, $value);
            }
        }
    }

    private function addComponentChildrenAttributes($dom)
    {
        $xpath = new DOMXPath($dom);

        $elements = $xpath->query('//*[@'.self::YOYO_PREFIX.']');

        foreach ($elements as $key => $element) {
            // Skip the component root because it's processed separately
            if ($key == 0) {
                continue;
            }

            $element->removeAttribute('yoyo');

            $this->addMethodAttribute($element);

            $this->checkForIdAttribute($element);

            foreach (self::YOYO_ATTRIBUTES as $attr) {
                if ($value = $element->getAttribute(self::yoprefix($attr))) {
                    $this->remapAndReplaceAttribute($element, $attr, $value);
                }
            }
        }
    }

    private function removeOnLoadEventWhenSpinning($element)
    {
        if ($this->spinning && $element->hasAttribute(self::yoprefix('on'))) {
            $on = $element->getAttribute(self::yoprefix('on'));

            $events = explode(',', $on);

            $events = array_filter($events, function ($event) {
                return $event !== 'load';
            });

            $element->setAttribute(self::yoprefix('on'), implode(',', $events));
        }
    }

    private function addFormBehavior($element)
    {
        if ($element->tagName == 'form' && ! $element->hasAttribute(self::yoprefix('on'))) {
            $element->setAttribute(self::YOYO_PREFIX, '');

            $element->setAttribute(self::yoprefix('on'), 'submit');

            // If the form has an upload input, set the encoding to multipart/form-data

            $xpath = new DOMXPath($element->ownerDocument);

            $inputs = $xpath->query('//*[@type="file"]', $element);

            if ($inputs->item(0)) {
                $element->setAttribute(self::yoprefix('encoding'), 'multipart/form-data');
            }
                        
            // If the form tag doesn't have a method set, set POST by default

            foreach ($element->attributes as $attr) {
                if (($parts = explode(':', $attr->name))[0] == self::YOYO_PREFIX && ! empty($parts[1])) {
                    if (in_array($parts[1], self::HTMX_METHOD_ATTRIBUTES)) {
                        return;
                    }
                }
            }

            $element->setAttribute(self::yoprefix('post'), self::COMPONENT_DEFAULT_ACTION);
        }
    }

    private function checkForIdAttribute($element)
    {
        if (! $element->hasAttribute('id')) {
            $element->setAttribute('id', $this->componentId.'-'.$this->idCounter++);
        }
    }

    private function remapAndReplaceAttribute($element, $attr, $value)
    {
        $element->removeAttribute(self::yoprefix($attr));

        $remappedAttr = self::YOYO_TO_HX_ATTRIBUTE_REMAP[$attr] ?? $attr;

        $element->setAttribute(self::hxprefix($remappedAttr), $value);
    }

    private function addMethodAttribute($element)
    {
        // Look for existing method attribute, otherwise set 'get' as default

        foreach (self::HTMX_METHOD_ATTRIBUTES as $attr) {
            $yoattr = self::yoprefix($attr);

            if ($value = $element->getAttribute($yoattr)) {
                $element->removeAttribute($yoattr);

                $element->setAttribute(self::hxprefix($attr), $value);

                return;
            }
        }

        $element->setAttribute(self::hxprefix('get'), self::COMPONENT_DEFAULT_ACTION);
    }

    private function getComponentAttributes($componentId): array
    {
        $variables = array_merge([self::yoprefix_value('id')=>$componentId], $this->variables);

        $attributes = array_merge(
            array_fill_keys(self::YOYO_ATTRIBUTES, ''),
            [
                'ext' => 'yoyo',
                // Adding refresh trigger to prevent default click trigger
                'on' => 'refresh',
                'target' => 'this',
                'include' => "#{$componentId} *",
                'vars' => $variables,
            ], $this->attributes
        );

        return $attributes;
    }

    public static function yoprefix($attr): string
    {
        return self::YOYO_PREFIX.':'.$attr;
    }

    public static function yoprefix_value($string): string
    {
        return self::YOYO_PREFIX.'-'.$string;
    }

    public static function hxprefix($attr): string
    {
        return self::HTMX_PREFIX.'-'.$attr;
    }

    private function getComponentRootNode($dom)
    {
        $xpath = new DOMXPath($dom);

        $count = 0;

        foreach ($xpath->query('/html/body/*') as $node) {
            if ($node->nodeType === XML_ELEMENT_NODE) {
                $count++;
            }
        }

        return $count == 1 ? $node : false;
    }

    private static function elementHasAttributeWithValue($element, $attr, $value)
    {
        if (! $element->hasAttribute($attr)) {
            return false;
        }

        $string = $element->getAttribute($attr);

        return strpos($value, $string) !== false;
    }

    private function getOuterHTML($dom): string
    {
        $output = '';

        $xpath = new DOMXpath($dom);

        $elements = $xpath->query("//*[starts-with(name(@*),'hx-')]");

        foreach ($elements as $node) {
            $setDefaultAction = true;

            foreach (['get', 'post', 'put', 'delete', 'patch', 'ws', 'sse'] as $verb) {
                if ($node->hasAttribute('hx-'.$verb)) {
                    $setDefaultAction = false;

                    break;
                }
            }

            if ($setDefaultAction) {
                $node->setAttribute('hx-get', 'render');
            }
        }

        foreach ($dom->getElementsByTagName('body')->item(0)->childNodes as $node) {
            $output .= $dom->saveHTML($node);
        }

        return $output;
    }

    private function getInnerHTML($dom): string
    {
        $output = '';

        $xpath = new DOMXPath($dom);

        $elements = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' ".self::COMPONENT_WRAPPER_CLASS." ')]");

        if (! $elements->length) {
            return $this->getOuterHTML($dom);
        }

        foreach ($elements->item(0)->childNodes as $node) {
            $output .= $dom->saveHTML($node);
        }

        return $output;
    }
}
