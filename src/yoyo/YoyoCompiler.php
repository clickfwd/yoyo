<?php

namespace Clickfwd\Yoyo;

use DOMDocument;
use DOMXPath;

class YoyoCompiler
{
    protected $componentType;

    protected $componentId;

    protected $name;

    protected $variables;

    protected $attributes;

    protected $spinning;

    protected $listeners;

    protected $props;

    protected $withHistory;

    protected $idCounter = 1;

    public const HTMX_REQUEST_METHOD_ATTRIBUTES = [
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
        'disable',
        'disinherit',
        'encoding',
        'ext',
        'headers',
        'history-elt',
        'include',
        'indicator',
        'trigger',
        'on',
        'params',
        'preserve',
        'prompt',
        'push-url',
        'request',
        'select',
        'swap-oob',
        'swap',
        'sync',
        'target',
        'vals',
    ];

    public const YOYO_TO_HX_ATTRIBUTE_REMAP = [
        'on' => 'trigger',
    ];

    public const COMPONENT_DEFAULT_ACTION = 'render';

    public const COMPONENT_WRAPPER_CLASS = 'yoyo-wrapper';

    public const YOYO_PREFIX = 'yoyo';

    public const YOYO_PREFIX_FINDER = 'yoyo-finder';

    public const HTMX_PREFIX = 'hx';

    public function __construct($componentType, $componentId, $name, $variables, $attributes, $spinning)
    {
        $this->componentType = $componentType;

        $this->componentId = $componentId;

        $this->name = $name;

        $this->variables = $variables;

        $this->attributes = $attributes;

        $this->spinning = $spinning;
    }

    public function addComponentListeners($listeners = [])
    {
        $this->listeners = $listeners;

        return $this;
    }

    public function addComponentProps($props = [])
    {
        $this->props = $props;

        return $this;
    }

    public function withHistory($cacheHistory = false)
    {
        $this->withHistory = $cacheHistory;

        return $this;
    }

    public function compile($html): string
    {
        if (! trim($html)) {
            return $html;
        }

        // For each yoyo: attribute found, add new yoyo-wind attribute that can be
        // used by XPath to find the elements which cannot be found when using
        // colons in attribute names

        $prefix = self::YOYO_PREFIX;

        $prefix_finder = self::YOYO_PREFIX_FINDER;

        // U modifier needed to match children tags when there are no line breaks in the HTML code

        $html = preg_replace('/'.$prefix.':(.*)="(.*)"/U', "$prefix_finder $prefix:\$1=\"\$2\"", $html);
        $html = preg_replace('/' . $prefix . ':(.*)=\'(.*)\'/U', "{$prefix_finder} {$prefix}:\$1='\$2'", $html);

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
            if (! $element->hasAttribute(self::yoprefix('ignore'))) {
                $this->addFormBehavior($element);
            }
        }

        // Prevent infinite loop with on 'load' event on root node with outerHTML swap

        $this->removeOnLoadEventWhenSpinning($node);

        $this->addComponentRootAttributes($node);

        $this->addComponentChildrenAttributes($dom);

        // Cleanup
        $node->removeAttribute(self::YOYO_PREFIX_FINDER);

        $doOuterHtmlSwap = ! $this->elementHasAttributeWithValue($node, self::hxprefix('swap'), 'innerHTML');

        if ($this->spinning && ! $doOuterHtmlSwap) {
            $output = $this->getInnerHTML($dom);
        } else {
            $output = $this->getOuterHTML($dom);
        }

        return trim($output);
    }

    protected function addComponentRootAttributes($element)
    {
        if ($element->hasAttribute(self::yoprefix('ignore'))) {
            $element->removeAttribute(self::yoprefix('ignore'));

            return;
        }

        $element->setAttribute(self::YOYO_PREFIX, '');

        $element->setAttribute(self::YOYO_PREFIX_FINDER, '');

        // Discard generated component ID and use hardcoded one if found
        $id = $element->getAttribute('id');

        if ($id !== '') {
            $this->componentId = $id;
        }

        $this->addRequestMethodAttribute($element, true);

        // Get default attributes

        $attributes = $this->getComponentAttributes($this->componentId);

        // Merge, or in some cases replace, defaults with existing attributes at the root node level

        if (! $element->hasAttribute('id')) {
            $element->setAttribute('id', $this->componentId);
        }

        foreach (['target', 'include'] as $attr) {
            if ($value = $element->getAttribute(self::yoprefix($attr))) {
                $attributes[$attr] = $value;
            }
        }

        // Add yoyo extension attribute and merge existing extensions

        if ($ext = $element->getAttribute(self::yoprefix('ext'))) {
            $element->removeAttribute(self::yoprefix('ext'));
            $attributes['ext'] .= ', '.$ext;
        }

        $class = $element->getAttribute('class');

        $element->setAttribute('class', self::COMPONENT_WRAPPER_CLASS.($class ? ' '.$class : ''));

        $element->setAttribute(self::yoprefix('name'), $this->name);

        if ($this->withHistory) {
            $element->setAttribute(self::yoprefix('history'), 1);
        }

        if ($trigger = $element->getAttribute(self::yoprefix('on'))) {
            $attributes['on'] .= ', '.$trigger;
        }

        // Process variables
        
        if ($vars = $element->getAttribute(self::yoprefix('vals'))) {
            $element->removeAttribute(self::yoprefix('vals'));

            $vars = YoyoHelpers::decode_vals($vars);

            $attributes['vals'] = array_merge($attributes['vals'], $vars);
        }

        // Process invididual variables added through yoyo:val.key

        $attributes['vals'] = array_merge($attributes['vals'] ?? [], $this->parseIndividualValAttributes($element));

        // Process public props

        if ($props = $element->getAttribute(self::yoprefix('props')) ?: []) {
            $props = explode(',', str_replace(' ', '', $props));
            $element->removeAttribute(self::yoprefix('props'));
        }

        $props = array_merge($props, $this->props, [
            self::yoprefix('resolver'),
            self::yoprefix('source'),
        ]);
        
        $variables = array_filter($this->variables, function ($key) use ($props) {
            return in_array($key, $props);
        }, ARRAY_FILTER_USE_KEY);
                
        $attributes['vals'] = array_merge($attributes['vals'], $variables);
        
        // Add all attributes

        $attributes['vals'] = YoyoHelpers::encode_vals($attributes['vals']);

        foreach ($attributes as $attr => $value) {
            if (! $value) {
                $value = $element->getAttribute(self::yoprefix($attr));
            }

            if ($value) {
                $this->remapAndReplaceAttribute($element, $attr, $value);
            }
        }
    }

    protected function addComponentChildrenAttributes($dom)
    {
        $xpath = new DOMXPath($dom);

        $elements = $xpath->query('//*[@'.self::YOYO_PREFIX.']|//*[@'.self::YOYO_PREFIX_FINDER.']');

        foreach ($elements as $key => $element) {
            // Skip the component root because it's processed separately
            if ($key == 0) {
                continue;
            }

            $this->addRequestMethodAttribute($element);

            foreach (self::YOYO_ATTRIBUTES as $attr) {
                if ($value = $element->getAttribute(self::yoprefix($attr))) {
                    $this->remapAndReplaceAttribute($element, $attr, $value);
                }
            }

            if ($vals = $this->parseIndividualValAttributes($element)) {
                $element->setAttribute(self::hxprefix('vals'), YoyoHelpers::encode_vals($vals));
            }

            // Cleanup

            $element->removeAttribute(self::YOYO_PREFIX);

            $element->removeAttribute(self::YOYO_PREFIX_FINDER);
        }
    }

    protected function parseIndividualValAttributes($element)
    {
        $attributes = [];

        foreach ($element->attributes as $attr) {
            $parts = explode('.', $attr->name);

            if (count($parts) == 1 || $parts[0] !== self::yoprefix('val')) {
                continue;
            }
            
            $attributes[YoyoHelpers::camel($parts[1], '-')] = YoyoHelpers::decode_val($attr->value);
            
            $element->removeAttribute($attr->name);
        }

        return $attributes;
    }

    protected function removeOnLoadEventWhenSpinning($element)
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

    protected function addFormBehavior($element)
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
                    if (in_array($parts[1], self::HTMX_REQUEST_METHOD_ATTRIBUTES)) {
                        return;
                    }
                }
            }

            $element->setAttribute(self::yoprefix('post'), self::COMPONENT_DEFAULT_ACTION);
        }
    }

    protected function checkForIdAttribute($element)
    {
        if (! $element->hasAttribute('id')) {
            $element->setAttribute('id', $this->componentId.'-'.$this->idCounter++);
        }
    }

    protected function remapAndReplaceAttribute($element, $attr, $value)
    {
        $element->removeAttribute(self::yoprefix($attr));

        $remappedAttr = self::YOYO_TO_HX_ATTRIBUTE_REMAP[$attr] ?? $attr;

        $element->setAttribute(self::hxprefix($remappedAttr), $value);
    }

    protected function addRequestMethodAttribute($element, $isRootNode = false)
    {
        // Skip if element already has an hx-[request] attribute (no yoyo:[request] which is processed below)
        
        foreach (self::HTMX_REQUEST_METHOD_ATTRIBUTES as $attr) {
            $hxattr = self::hxprefix($attr);
            if ($element->hasAttribute($hxattr)) {
                return;
            }
        }

        // Look for existing method attribute, otherwise set 'get' as default
        
        foreach (self::HTMX_REQUEST_METHOD_ATTRIBUTES as $attr) {
            $yoattr = self::yoprefix($attr);
            
            if ($value = $element->getAttribute($yoattr)) {
                $element->removeAttribute($yoattr);

                $element->setAttribute(self::hxprefix($attr), $value);

                // Add an ID attribute for elements that trigger requests
                if (! $isRootNode) {
                    $this->checkForIdAttribute($element);
                }

                return;
            }
        }

        // Automatically add the default hx-get="render" request to component root nodes and any child with the `yoyo` attribute
        if ($element->hasAttribute(self::YOYO_PREFIX)) {
            $element->setAttribute(self::hxprefix('get'), self::COMPONENT_DEFAULT_ACTION);
            if (! $isRootNode) {
                // Ensure re-active tags have an ID to improve swapping
                $this->checkForIdAttribute($element);
            }
        }
    }

    protected function getComponentAttributes($componentId): array
    {
        $attributes = array_merge(
            array_fill_keys(self::YOYO_ATTRIBUTES, ''),
            [
                'ext' => 'yoyo',
                // Adding refresh trigger to prevent default click trigger
                'on' => 'refresh',
                'target' => 'this',
                'include' => "#{$this->componentId} *",
                'vals' => [self::yoprefix_value('id') => $componentId],
            ],
            $this->attributes
        );

        // Include component listeners in trigger attribute

        if (! empty($this->listeners)) {
            $listeners = array_keys($this->listeners);

            $attributes['on'] .= ','.implode(',', $listeners);
        }

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

    protected function getComponentRootNode($dom)
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

    protected static function elementHasAttributeWithValue($element, $attr, $value)
    {
        if (! $element->hasAttribute($attr)) {
            return false;
        }

        $string = $element->getAttribute($attr);

        return strpos($string, $value) !== false;
    }

    protected function getOuterHTML($dom): string
    {
        $output = '';

        $xpath = new DOMXPath($dom);

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

    protected function getInnerHTML($dom): string
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
