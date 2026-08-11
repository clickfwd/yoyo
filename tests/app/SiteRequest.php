<?php

namespace Tests\App;

class SiteRequest implements SiteRequestContract
{
    public function label(): string
    {
        return 'REQUEST-OBJECT';
    }
}
