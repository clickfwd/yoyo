<?php

require __DIR__.'/bootstrap.php';

it('preserves PHP null prop after request roundtrip', function () {
    $this->visit(BASE_URL.'/null-prop')
        ->assertVisible('#null-prop')
        ->assertAttribute('[data-icon-slot-type]', 'data-icon-slot-type', 'NULL')
        ->assertSeeIn('[data-icon-slot-display]', 'NULL_OK')
        ->click('[data-action="increment"]')
        ->assertSeeIn('[data-clicks]', '1')
        ->assertAttribute('[data-icon-slot-type]', 'data-icon-slot-type', 'NULL')
        ->assertSeeIn('[data-icon-slot-display]', 'NULL_OK');
});

it('preserves PHP false prop after request roundtrip', function () {
    $this->visit(BASE_URL.'/null-prop')
        ->assertAttribute('[data-enabled-type]', 'data-enabled-type', 'boolean')
        ->assertSeeIn('[data-enabled-display]', 'FALSE_OK')
        ->click('[data-action="increment"]')
        ->assertSeeIn('[data-clicks]', '1')
        ->assertAttribute('[data-enabled-type]', 'data-enabled-type', 'boolean')
        ->assertSeeIn('[data-enabled-display]', 'FALSE_OK');
});

it('emits JSON null and false in button hx-vals', function () {
    $this->visit(BASE_URL.'/null-prop')
        ->assertAttributeContains('[data-action="increment"]', 'hx-vals', '"iconSlot":null')
        ->assertAttributeContains('[data-action="increment"]', 'hx-vals', '"enabled":false')
        ->assertAttributeDoesntContain('[data-action="increment"]', 'hx-vals', '"iconSlot":"null"')
        ->assertAttributeDoesntContain('[data-action="increment"]', 'hx-vals', '"enabled":"false"');
});
