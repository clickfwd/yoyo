# Yoyo

## Public Methods

Component public methods are automatically available within templates and as actions for browser requests.

```php
class SomeComponent {
    public function entries() {}
}
```

Can be accessed in templates as 

```php
foreach ($entries() as $entry) {}
```

To make the method public to the template and private to the browser, prefix it with an underscore:

```php
class SomeComponent {
    public function _entries() {}
}
```

You can access the method as usual in template with `$entries`, and it will no longer be available as a component action for browser requests.

## Component Parameters

The `parameter` method is available within component classes and templates and returns the comma delimited variable string. 

## Server Emitted Events

The following methods are available within component classes and templates to trigger browser events.

- emit
- emitSelf
- emitTo
- emitUp

## Testing

### Render

```php
$yoyo = new Clickfwd\Yoyo\Yoyo();

echo $yoyo->mount('test')->hydrate(
    '<button yoyo yo-get="render" yo-swap="outerHTML" yo-trigger="click">Test</button>'
); 
```

### Refresh

```php
$yoyo = new Clickfwd\Yoyo\Yoyo();

$output = $yoyo->mount('test')->hydrate(
    '<button yoyo yo-get="render" yo-swap="outerHTML" yo-trigger="click">Blah</button>'
, $spinning = true); 
```