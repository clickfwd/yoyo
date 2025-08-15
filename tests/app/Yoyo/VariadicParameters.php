<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class VariadicParameters extends Component
{
    public $result = '';
    
    /**
     * Test method with only variadic parameters
     */
    public function onlyVariadic(...$params)
    {
        $this->result = 'Received: ' . json_encode($params);
    }
    
    /**
     * Test method with regular and variadic parameters
     */
    public function mixedVariadic($first, ...$rest)
    {
        $this->result = "First: {$first}, Rest: " . json_encode($rest);
    }
    
    /**
     * Test method with optional and variadic parameters
     */
    public function optionalAndVariadic($required, $optional = 'default', ...$extra)
    {
        $this->result = "Required: {$required}, Optional: {$optional}, Extra: " . json_encode($extra);
    }
    
    public function render()
    {
        return $this->view('variadic-parameters', ['result' => $this->result]);
    }
}
