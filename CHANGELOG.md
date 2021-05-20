# Changelog

## [Unreleased](https://github.com/clickfwd/yoyo/compare/0.7.4...develop)

## [0.7.4 (2021-05-20)](https://github.com/clickfwd/yoyo/compare/0.7.3...0.7.4)

### Fixed

- Various fixes

## [0.7.3 (2021-04-04)](https://github.com/clickfwd/yoyo/compare/0.7.2...0.7.3)

### Fixed

- Allow component listeners to trigger the default `refresh` action.

    ```php
    protected $listeners = ['updated' => 'refresh'];
    ```

## [0.7.2 (2021-03-22)](https://github.com/clickfwd/yoyo/compare/0.7.1...0.7.2)

### Fixed

- Initial component history snapshot taken even for components that don't push changes to the URL via `queryString`.

## [0.7.1 (2021-03-14)](https://github.com/clickfwd/yoyo/compare/0.7.0...0.7.1)

### Added

- Updated htmx to v1.3.1
- Component `emitToWithSelector` method to differentiate from `emitTo`. `emitTo` targets Yoyo components specifically, while `emitToWithSelector` can target elements using a CSS selector.
- Component `skipRenderAndRemove` method to allow removing components from the page.
- Component `addSwapModifiers` method to dynamically set [swap modifers](https://htmx.org/attributes/hx-swap/) when updating components. 
- Additional component lifecycle hooks
    - initialize - on component initialization, allows adding properties, setting listeners, etc.
    - mount - after component initialization
    - rendering - before component render method
    - rendered - after component render method, receives component output
- Allow traits to implement lifecycle hooks which run after the component's equivalent. For example, a `trait WithValidation` in addition to adding its own properties and methods can also implement hooks with the format:
    - `initializeWithValidation`
    - `mountWithValidation`
    - `renderingWithValidation`
    - `renderedWithValidation`
- Depedency Injection for lifecycle hooks and listener methods.
- Yoyo\abort, Yoyo\abort_if, Yoyo\abort_unless functions allows throwing exceptions within components to stop execution while still sending any queued events back up to the browser.
- Namespace support for view templates and component classes
- Support for new htmx `hx-headers` attribute via `yoyo:headers`
- Tests for Blade and Twig

### Changed

- Automatically re-spawn dynamically created target elements if these are removed on swap. 

    When `yoyo:target="#some-element"` is used with an ID and the target element doesn't exist, Yoyo automatically creates an empty `<div id="some-element"></div>` and appends it to the document body.
- Refactored component resolver
- Events are sent to the browser even when throwing an exception within a component.
- Components are resolved from the container.

### Fixed

- Cannot use Array property as prop.
- Component props not persisted in POST request updates.
- Variables passed directly to `render` method leaking to component props.
