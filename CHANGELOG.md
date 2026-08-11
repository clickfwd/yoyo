# Changelog

## [Unreleased](https://github.com/clickfwd/yoyo/compare/0.15.1...develop)

## [0.15.1 (2026-08-10)](https://github.com/clickfwd/yoyo/compare/0.15.0...0.15.1)

### Fixed

- Request data no longer reaches a slot declared to hold an object. Lifecycle arguments
  are matched by name before the container consults the type, and public properties are
  populated from the same data, so a query string carrying a slot's name displaced the
  object the component asked for. This surfaced as a 500 on an ordinary page load from
  nothing but a URL. Values arrive JSON-decoded, so the displacing value could be an int,
  float, bool, array or null — and against a nullable slot, `null` satisfied the signature
  and displaced the object with no error at all. Caller-supplied variables still fill
  those slots, which is how a parent hands a model to a nested component, and slots that
  take a scalar are unchanged.
- `getMethodParametersWithTypes()` and `getMethodParameterNames()` no longer raise a fatal
  `Error` for a union or intersection parameter. Both called `isBuiltin()` on whatever
  `getType()` returned, and neither `ReflectionUnionType` nor `ReflectionIntersectionType`
  has that method. Composite types are now classified the way the container itself treats
  them: not container-resolved.
- `Request::all()` and `Request::get()` no longer corrupt a public property holding JSON
  scalar `null` or `false`. Both tested the RESULT of `test_json()` for truthiness, and a
  successful decode of those two values is falsy, so the raw strings `"null"` and `"false"`
  were kept instead of the decoded values and re-hydrated the component with them on every
  re-render. Decode success is now reported separately from the decoded value, via a by-ref
  flag reading `json_last_error()`. Single-argument callers of `test_json()` are unaffected.

## [0.15.0 (2026-04-10)](https://github.com/clickfwd/yoyo/compare/0.14.0...0.15.0)

### Added

- `Yoyo.dispatch()` and `Yoyo.dispatchTo()` JS API for triggering component events from JavaScript, matching Livewire 3's dispatch API. Thanks to @mucan54 for the original proposal in #35.
- Named parameter support for JS dispatch — object params like `{ postId: 2 }` map to listener method arguments by name.
- Required parameter validation for named dispatch params — throws `InvalidArgumentException` if a required parameter is missing.
- Documentation for all HTMX response header methods available via `$this->response` (`retarget`, `reswap`, `reselect`, `pushUrl`, `replaceUrl`, `location`, `redirect`, `refresh`, `trigger`, `triggerAfterSwap`, `triggerAfterSettle`).

### Fixed

- Fix `test_json` empty-params decode bug when `eventParams` is an empty JSON object (`'{}'`).
- Add defensive null-guard in `triggerServerEmittedEvent` to prevent TypeError when source element is not inside a component.

### Performance

- Add static cache to `getMethodParametersWithTypes()` for consistency with other `ClassHelpers` caches.
- Optimize props filtering in `YoyoCompiler` by replacing repeated `in_array` checks with a lookup map.
- Skip re-processing already compiled nested Yoyo child components (`yoyo:name` + `hx-vals`) during compile.

Benchmark update (5-run averages from `tests/Benchmark/RealWorldBenchmarkTest.php`):

- Listing List (10x3 children): `0.5064 -> 0.4488 ms/op` (`-11.4%`)
- Listing List (25x3 children): `1.1379 -> 1.0104 ms/op` (`-11.2%`)
- Listing List (50x3 children): `2.1507 -> 1.9204 ms/op` (`-10.7%`)

Other scenarios remained in the same range with normal benchmark variance.

## [0.14.0 (2025-10-27)](https://github.com/clickfwd/yoyo/compare/0.13.1...0.14.0)

### Breaking Changes

- Minimum PHP version is now 8.0+
- `illuminate/container` is now an optional dependency

### Added

- Built-in dependency injection container for standalone usage

### Changed

- Yoyo now automatically detects and uses `illuminate/container` if available, otherwise uses built-in container

### Migration Notes

If you're using Yoyo standalone and need advanced container features, install illuminate/container:
```bash
composer require illuminate/container
```

## [0.13.1 (2025-08-15)](https://github.com/clickfwd/yoyo/compare/0.13.0...0.13.1)

- Fix parameter validation to correctly handle optional parameters in component actions

## [0.13.0 (2025-08-15)](https://github.com/clickfwd/yoyo/compare/0.12.0...0.13.0)

- Fix spinners stop working when target is different than current element
- Add support for variadic parameters in component actions using PHP's `...$params` syntax
- Add automatic dependency injection for typed parameters in component actions
- Add new ClassHelpers methods: `methodHasVariadicParameter()` and `getMethodParametersWithTypes()`
- Enhanced ComponentManager to handle methods with variadic parameters
- Improved parameter validation to support methods with only typed (DI) parameters
- Container integration now properly handles mixed regular, typed, and variadic parameters

## [0.12.0 (2025-07-18)](https://github.com/clickfwd/yoyo/compare/0.11.1...0.12.0)

- Add compatibility up to illuminate/container v12
- Fix deprecated error for  implicit nullable parameter value

## [0.11.1 (2025-05-28)](https://github.com/clickfwd/yoyo/compare/0.11.0...0.11.1)

- Improve parsing of yoyo attribute action arguments and fix error where they are incorreclty converted to null.

## [0.11.0 (2025-02-06)](https://github.com/clickfwd/yoyo/compare/0.10.0...0.11.0)

- Passing attributes to Yoyo\yoyo_render should only prefix HTMX attributes defined in `YoyoCompiler::YOYO_ATTRIBUTES`.

## [0.10.1 (2024-08-29)](https://github.com/clickfwd/yoyo/compare/0.10.0...0.10.1)

- Change default hx-include to `this` to improve event-to-request delay on forms with large number of elements.

## [0.10.0 (2024-06-20)](https://github.com/clickfwd/yoyo/compare/0.9.1...0.10.0)

- Merge PR to add Falcon framework implementation.

## [0.9.1 (2024-04-16)](https://github.com/clickfwd/yoyo/compare/0.9.0...0.9.1)

- Fix Safari/iOS errors due to evt.target and evt.srcElement now being null.
- Add support for port in UrlStateManagerService.php
- PHP 8.2/8.3 compat
- Fix ResponseHeaders::refresh error due to missing parameter.
- Fix headers already sent error when setting status code in response
- Ensure components are compiled only once.
- Bump htmx to v1.9.4 and include new config options.
- New Request::set, Request::triggerName and Request::header methods.
- New Response::reselect method for the HX-Reselect header.
- New New Yoyo::actionArgs method.

## [0.9.0 (2023-04-02)](https://github.com/clickfwd/yoyo/compare/0.8.1...0.9.0)

## New

- Added Component::actionMatches method.
- Add response HX header methods that can via accessed in Yoyo component via $this->response:
    - location
    - pushUrl
    - redirect
    - refresh
    - replace
    - reswap
    - retarget
    - trigger
    - triggerAfterSwap
    - triggerAfterSettle

## Changed

- Added composer support for illuminate/container v9.0

## Fixed 

- Regex replacement in Yoyo compiler causing issues due to incorrect replacements.

## [0.8.2 (2021-07-07)](https://github.com/clickfwd/yoyo/compare/0.8.1...0.8.2)

### Changed

    - Updated htmx to v1.8.4.
    - Add support for new htmx attributes to the Yoyo compiler: `replace-url`, `select-oob`, `validate`.
    - Add support for PHP 8.1 installs.
    - Add `yoyo:history="remove"` attribute to allow excluding elements from browser history cache snapshot.
    - Renamed `Component::addDynamicProperties` to `Component::getDynamicProperties` to make it consistent with other component methods.
    - Expose all htmx configuration options to Yoyo via `Clickfwd\Yoyo\Services\Configuration`.
    - Breaking change! Compiler no longer makes buttons, links or inputs reactive by default. Previously any button, link or input would automatically receive the yoyo:get="render" attribute unless it already had a different request attribute. Now it's necessary to explicitly add the yoyo:{method} attribute if you want to make the element reactive. You can also just add an empty `yoyo` attribute if you want to make a request to the default yoyo:get="render" attribute on the component.    

### Fixed

    - Allow queryString parameters with value of zero to be pushed to URL.
    - Javascript `Yoyo.on` throws undefined error when event detail is of type object.
    - Issues working with dynamic properties.
    - Lots of other changes and improvements.

## [0.8.1 (2021-07-07)](https://github.com/clickfwd/yoyo/compare/0.8.0...0.8.1)

### Added

- Support for adding dynamic properties to components via Component::addDynamicProperties method, which returns an array of property names. NOTE: `renamed to Component::getDynamicProperties` in next update.

    This can be useful when the names of the properties are not known in advanced (i.e. coming from the database). The code below shows how to use this together with queryStrings to push the dynamic property values to the URL. The dynamic properties are also available in templates like regular public properties.

    ```php
    public function addDynamicProperties() 
    {
        return ['width', 'length'];
    }

    public function getQueryString()
    {
        return array_merge($this->queryString, $this->addDynamicProperties());
    }
    ```


## [0.8.0 (2021-07-07)](https://github.com/clickfwd/yoyo/compare/0.7.5...0.8.0)

### Changed

- Links that trigger Yoyo requests now automatically update the browser URL and push the component state to the browser history.

## [0.7.5 (2021-05-28)](https://github.com/clickfwd/yoyo/compare/0.7.4...0.7.5)

### Fixed

- Error retrieving parameter names for component action.

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
