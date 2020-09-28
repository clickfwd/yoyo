# Yoyo

- [Introduction](#introduction)
- [How it Works](#how-it-works)
- [Installation](#installation)
- [Creating Components](#creating-components)
- [Rendering Components](#rendering-components)
- [Properties](#properties)
- [Actions](#actions)
- [Component Methods](#component-methods)
- [Events](#events)
- [Query String](#query-string)
- [Using Blade](#using-blade)
- [Using Twig](#using-twig)
- [License](#license)

## Introduction

Yoyo is a PHP framework you can use on any project to create rich dynamic interfaces using server-rendered HTML.

With Yoyo you create reactive components that are seamlessly updated without the need to write any Javascript code.

Yoyo ships with a simple templating system, and  offers out-of-the-box support for [Blade](https://laravel.com/docs/8.x/blade), without having to use Laravel, and [Twig](https://twig.symfony.com/).

Inspired by [Laravel Livewire](https://laravel-livewire.com/) and [Sprig](https://putyourlightson.com/plugins/sprig), and using [htmx](https://htmx.org/).

## How it Works

Yoyo components are rendered on page load and can be individually updated, without the need for page-reloads, based on user interaction and specific events.

Component update requests are sent directly to a Yoyo-designated route, where it processes the request and then sends the updated component partial back to the browser.

Yoyo can update the browser URL state and trigger browser events straight from the server.

Below you can see what a Counter component looks like:

**Component class**

```php
# /app/Yoyo/Counter.php

<?php 
namespace App\Yoyo;

use Clickfwd\Yoyo\Component;

class Counter extends Component
{
    public $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function decrement()
    {
        $this->count--;
    }
}
```

**Component template**

```html
<!-- /app/resources/views/yoyo/counter.php -->

<div>

	<button yoyo:get="decrement">-</button>
	
	<button yoyo:get="increment">+</button>
	
	<span><?php echo $count; ?></span>

</div>
```

Yes, it's that simple! 

## Installation

### Install the Package

```bash
composer require clickfwd/yoyo
```

### Configure Yoyo

It's necessary to bootstrap Yoyo with a few configuration settings. This code should run when rendering and updating components.

```php
use Clickfwd\Yoyo\Yoyo;
use Clickfwd\Yoyo\View;
use Clickfwd\Yoyo\ViewProviders\Yoyo as YoyoView;

$yoyo = new Yoyo();

$yoyo->configure([
  'url' => '/yoyo',
  'scriptsPath' => 'app/resources/assets/js/',
  'namespace' => 'App\\Yoyo\\'
]);

// Add the native Yoyo view provider 
// Pass the components' template directory path in the constructor

$view = new YoyoView(new View([
	__DIR__.'/resources/views/yoyo',
]));

$yoyo->setViewProvider($view);
```

**'url'**

Absolute or relative URL that will be used to request component updates.

**'scriptsPath'**

The location where you copied the `yoyo.js` script. 

**'namespace'**

This is the PHP class namespace that will be used to discover auto-loaded dynamic components (components that use a PHP class). 

If the namespace is not provided or components are in different namespaces, you need to register them manually:

```php
$yoyo->registerComponents([
    'counter' => App\Yoyo\Counter::class,
];
```

You are required to load the component classes at run time, either using a `require` statement to load the component's PHP class file, or by including your component namespaces in you project's `composer.json`.

Anonymous components don't need to be registered, but the template name needs to match the component name.

### Load Assets

Find `yoyo.js` in the following vendor path and copy it to your project's public assets directory.

```file
/vendor/clickfwd/yoyo/src/assets/js/yoyo.js 
```

To load the necessary scripts in your template add the following code inside the `<head>` tag:

```php
<?php yoyo_scripts(); ?>
```

## Creating Components

Dynamic components require a class and a template. When using the Blade and Twig view providers, you can also use inline views, where the component markup is returned directly in the component's `render` method.

Anonymous components allow creating components with just a template file.

To create a simple search component that retrieves results from the server and updates itself, create the component template:

```html
// resources/views/yoyo/search.php

<form>
    <input type="text" name="query" value="<?php echo $query ?? ''; ?>">
    <button type="submit">Submit</button>
</form>
```

Yoyo will render the component output and compile it to add the necessary attributes that makes it dynamic and reactive. 

When you submit the form, posted data is automatically made available within the component template. The template code can be expanded to show a list of results, or an empty state:

```php
<?php
$query = $query ?? '';
$entries = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
$results = array_filter($entries, function($entry) use ($query) {
    return $query && strpos($entry, $query) !== false;
});
?>
```

```html
<form>
    <input type="text" name="query" value="<?php echo $query; ?>">
    <button type="submit">Submit</button>
</form>
    
<ul>
    <?php if ($query && empty($results)): ?>
        <li>No results found</li>
    <?php endif; ?>
    
    <?php foreach ($results as $entry): ?>
        <li><?php echo $entry; ?></li>
    <?php endforeach; ?>
</ul>
```

The `$results` array can be populated from any source (i.e. database, API, etc.)

The example can be converted into a live search input, with a 200ms debounce to minimize the number of requests. Replace the `form` tag with:

```php
<input yoyo:on="keyup delay:300ms changed" type="text" name="query" value="<?php echo $query; ?>" />
```

The `yoyo:on="keyup delay:300ms change"` directive tells Yoyo to make a request on the keyup event, with a 300ms debounce, and only if the input text changed. 

The `id` attribute on the input is necessary to maintain the input focus and cursor position when updating the component. 

Now let's turn this into a dynamic component using a class.

```php
# /app/Yoyo/Search

<?php

namespace App\Yoyo;

use Clickfwd\Yoyo\Component;

class Search extends Component
{
	public $query;
	
	protected $queryString = ['query'];
	
	public function render()
	{
		$query = $this->query;
	
		// Perform your database query
		$entries = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
	
		$results = array_filter($entries, function($entry) use ($query) {
			return $query && stripos($entry, $query) !== false;
		});
	
	  // Render the component view
		return $this->view('search',['results' => $results]);
	}
}
```

And the template:

```php
# /app/resources/views/yoyo/search.php

<input yoyo:on="keyup delay:300ms changed" type="text" name="query" value="<?php echo $query; ?>" />

<ul yoyo:ignore>
    <?php if ($query && empty($results)): ?>
        <li>No results found</li>
    <?php endif; ?>
    
    <?php foreach ($results as $entry): ?>
        <li><?php echo $entry; ?></li>
    <?php endforeach; ?>
</ul>
```

A couple of things to note here that are covered in more detail in other sections.

1. The component class includes a `queryString` property that tells Yoyo to automatically include the queryString values in the browser URL after a component update. If you re-load the page with the `query` value in the URL, you'll automatically see the search results on the page.
2. Yoyo will automatically assign parameters to matching public properties. This allows using `$this->query` to access the search keyword in the component and `$query` in the template.

When you compare this search example to the counter example at the beginning, you can see that there are no action methods (i.e. increment, decrement). A component update will always default to the `render` method, unless an action is specified via one of the method attributes (i.e. yoyo:get, yoyo:post, etc.). In that case, the action method always runs before the render method.

## Rendering Components

There are two instances when components are rendered. On page load, and on component updates.

### Rendering on page load

To render any component on page load within your templates, use the `yoyo_render` function and pass the component name as the first parameter.

```php
<?php echo yoyo_render('search'); ?>
```

For dynamic components, the component name is a hyphenated version of the class name (i.e. LiveSearch → live-search). If you register components while bootstrapping Yoyo using the `registerComponents` method, then you can use the registered alias as the component name.

```php
$yoyo->registerComponent('search', App\Yoyo\LiveSearch::class);
```

For anonymous components, the component name should match the template name without the file extension. So if the template name is `form.php`, the component can be rendered with:

```php
<?php echo yoyo_render('form'); ?>
```

### Rendering on updates

Use the `yoyo_update` function to automatically process the component request and output the updated component.

```php
<?php echo yoyo_update(); ?>
```

You need to add this function call for requests routed to the Yoyo `url` used in the initial configuration.

## Properties

In dynamic components, all public properties in the component class are automatically made available to the view and tracked in component updates.

```php
class HelloWorld extends Component
{
    public $message = 'Hello World!';
}
```

```html
<div>
    <h1><?php echo $message; ?></h1>
    <!-- Will output "Hello World!" -->
</div>
```

Public properties should only be of type: `string`, `int`, `array`, `boolean`, and should not contain any sensitive information because they can be used in component requests to keep the data in sync.

### Initializing Properties

You can initialize properties using the `mount` method of your component which runs right after the component is instantiated, and before the `render` method.

```php
class HelloWorld extends Component
{
    public $message;

    public function mount()
    {
        $this->message = 'Hello World!';
    }
}
```

### Data Binding

You can automatically bind, or synchronize, the value of an HTML element with a component public property.

```php
class HelloWorld extends Component
{
    public $message = 'Hello World!';
}
```

```html
<div>
    <input yoyo name="message" type="text" value="<?php echo $message; ?>">
    <h1><?php echo $message;?></h1>
</div>
``` 

Adding the `yoyo` attribute to any input will instantly make it reactive. Any changes to the input will be updated in the component.

By the default, the natural event of an element will be used as the event trigger. 

- input, textarea and select elements are triggered on the change event.
- form elements are triggered on the submit event.
- All other elements are triggered on the click event.

You can modify this behavior using the `yoyo:on` directive which accepts multiple events separated by comma:

 ```html
 <input yoyo:on="keyup" name="message" type="text" value="<?php echo $message; ?>">
 ```

### Debouncing and Throttling Requests

The are several ways to limit the requests to update components.

**`delay`** - debounces the request so it's made only after the specified period passes after the last trigger.

```html
<input yoyo:on="keyup delay:300ms" name="message" type="text" value="<?php echo $message; ?>">
```

**`throttle`** limits request to one dwithin the specified interval.

```html
<input yoyo:on="input throttle:2s" name="message" type="text" value="<?php echo $message; ?>">
```

**`changed`** - only makes the request when the input value has changed.

```html
<input yoyo:on="keyup delay:300ms changed" name="message" type="text" value="<?php echo $message; ?>">
```

## Actions

An action is a request made to a Yoyo component method to update (re-render) it as a result of a user interaction or page event (click, mouseover, scroll, load, etc.).

The `render` method is the default action when one is not provided explicitly. You can also override it in the component class to change the template name or when you need to send additional variables to the template in addition to the public properties.

```php
public function render() 
{
	return $this->view($this->componentName, ['foo' => 'bar']);
}
```

To specify an action you use one of the available action directives with the name of the action as the value.

- `yoyo:get`
- `yoyo:post`
- `yoyo:put`
- `yoyo:patch`
- `yoyo:delete`

For example:

```php
class Review extends Component
{
    public Review $review;

    public function helpful()
    {
        $this->review->userFoundHelpful($userId);
    }
}
```

```html
<div>
    <button yoyo:on="click" yoyo:get="like">Found Helpful</button>
</div>
```

All components automatically listen for the `refresh` event and trigger the `render` action to refresh the component state.

### Passing Data to Actions

You can include additional data to send to the component on update requests using the `yoyo:vars` directive which accepts a separated list of name of `key:<expression>` values.

```
<button yoyo:on="click" yoyo:get="like" yoyo:vars="id:100">Found Helpful</button>
```

Yoyo will automatically track and send component public properties and input values with every request. The `yoyo:vars` directive allows including additional parameters.

## Component Methods

In addition to public properties being available to your component template, any public method on the component can also be executed on the template.

To differentiate component methods that you want to make available in the template from public actions that can be directly executed through a component request, prefix the method with a single underscore.

```php
class HelloWorld extends Component
{
	public $message = 'Hello World!';
	
	public function _helloTo($name)
	{
		return "Hello $name";
	}
}
```
	
```php
<div>
	<h1><?php echo $helloTo('Bob') ;?></h1>
	<!-- Will output "Hello Bob!" -->
</div>
```

## Query String

Components have the ability to automatically update the browser's query string on state changes. 

```php
class Search extends Component
{
	public $query;
	
	protected $queryString = ['query'];
}
```

Yoyo is smart enough to automatically remove the query string when the current state value matches the property's default value.

For example, in a pagination component, you don't need the `?page=1` query string to appear in the URL.

```php
class Posts extends Component
{
	public $page = 1;
	
	protected $queryString = ['page'];
}
```

## Events

Events are a great way to communicate between Yoyo components in the same page, where one component can listen to events fired by another component.

A component is not limited to listening to events from Yoyo. These are server emitted events that are made available in the global Javascript scope. So in fact, Yoyo can send events and listen to Javascript events from anything that lives on the the same page.

To listen for events in Yoyo components use the `yoyo:on` directive.

There are a couple of ways to fire events in Yoyo. 

### From a Component Method

```php
public function increment()
{
	$this->count++;
	
	// The first argument is the event name
	// The second (optional) argument is an array of data to send with the event.
	
	$this->emit('counter:updated', ['count' => $this->count]);
}
```

### From the Template

All the emit methods are available as closures in the template.

```php
<?php $emit('counter:updated', ['count' => $count]) ; ?>
```

### Using Javascript

When triggering Javascript notifications to Yoyo components, any data passed in the `params` key in the second argument will be automatically bound to the component's public property of the same name.

```js
Yoyo.trigger(window, 'counter', { params: { count: count} });
```

### Passing Data with Events

When a Yoyo component is listening to an event that includes parameters, these will be automatically included in the request.

### Scoping Events

#### Scoping To Parent Listeners

When dealing with nested components you can emit events to parents and not children or sibling components.

```php
$this->emitUp('postWascreated');
```

#### Scoping To Components By Name

Sometimes you may only want to emit an event to other components of the same type.

```php
$this->emitTo('cart', 'productAddedToCart');
```

This method also works with CSS selectors when using a ID or CSS class.

```php
$this->emitTo('#cart', 'productAddedToCart');
```

#### Scoping To Self

Sometimes you may only want to emit an event on the component that fired the event.

```php
$this->emitSelf('productAddedToCart');
```

### Listening For Events In JavaScript

```js
<script>
	Yoyo.on(window,'productAddedToCart', (event) => {
		alert('A product was added to the card with ID:' + event.detail.params.productId;
	});
</script>
```

The parameters are the target, eventName, and the event Object respectively.

With this feature you can control toasters, alerts, modals, etc. directly from a component action on the server by emitting the event and listening for it on the browser.

## Using Blade

You can use Yoyo with Laravel's [Blade](https://laravel.com/docs/8.x/blade) templating engine, without having to use Laravel.

### Installation

To get started install the following packages in your project:

```bash
composer require clickfwd/yoyo
composer require jenssegers/blade
```

### Configuration

Create a Blade instance and set it as the view provider for Yoyo. We also add the `YoyoServiceProvider` for Blade.

This code should run when rendering and updating components.

```php
<?php

use Clickfwd\Yoyo\Blade\Application;
use Clickfwd\Yoyo\Blade\YoyoServiceProvider;
use Clickfwd\Yoyo\ViewProviders\BladeViewProvider;
use Clickfwd\Yoyo\Yoyo;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Fluent;
use Jenssegers\Blade\Blade;

define('APP_PATH', __DIR__);

$yoyo = Yoyo::getInstance();

$yoyo->configure([
  'url' => 'yoyo',
  'scriptsPath' => APP_PATH.'/app/resources/assets/js/',
  'namespace' => 'App\\Yoyo\\',
]);

// Create a Blade instance

$app = Application::getInstance();

$app->bind(ApplicationContract::class, Application::class);

// Needed for Blade anonymous components

$app->alias('view', ViewFactory::class);

$app->extend('config', function (array $config) {
    return new Fluent($config);
});

$blade = new Blade(
    [
        APP_PATH.'/resources/views',
        APP_PATH.'/resources/views/yoyo',
        APP_PATH.'/resources/views/components',
    ],
    APP_PATH.'/../cache',
    $app
);

$app->bind('view', function () use ($blade) {
    return $blade;
});

(new YoyoServiceProvider($app))->boot();

// Register Blade components

$blade->compiler()->components([
    // 'button' => 'button',
]);

// Set Blade as the view provider for Yoyo

$yoyo->setViewProvider(new BladeViewProvider($blade));
```

### Load Assets

Find `yoyo.js` in the following vendor path and copy it to your project's public assets directory.

```file
/vendor/clickfwd/yoyo/src/assets/js/yoyo.js 
```

To load the necessary scripts in your Blade template you can use the `yoyo_scripts` directive in the `<head>` tag:

```blade
@yoyo_scripts
```

### Rendering a Blade View

You can use the Blade instance to render any Blade view.

```
$blade = \Clickfwd\Yoyo\Yoyo::getViewProvider()->getProviderInstance();

echo $blade->render('home');
```

### Rendering Yoyo Blade Components

To render Yoyo components inside Blade views, use the `@yoyo` directive.

```blade
@yoyo('search')
```

### Updating Yoyo Blade Components

To update Yoyo components in the Yoyo-designated route.

```php
echo (new \Clickfwd\Yoyo\Blade\Yoyo())->update();
```

### Inline Views

When dealing with simple templates, you can create components without a template file and instead return an inline view in the component's `render` method.

```php
class HelloWorld extends Component
{
    public $message = 'Hello World!';
}

public function render()
{
	return <<<'yoyo'
		<div>
		    <input yoyo name="message" type="text" value="{{ $message }}">
		    <h1>{{ $message }}</h1>
		</div>		
	yoyo;
}
```

### Other Blade Features

Yoyo implements several Blade directives that can be used within Yoyo component templates.

- `@spinning` and `@endspinning` - Check if a component is being re-rendered. 

	```blade
	@spinnning
	Component updated
	@endspinning
	
	@spinning($liked == 1)
	Component updated and liked == 1
	@endspinning
	```

- All event methods are available as directives within blade components

	```blade
	@emit('eventName', ['foo' => 'bar']);
	@emitUp('eventName', ['foo' => 'bar']);
	@emitSelf('eventName', ['foo' => 'bar']);
	@emitTo('component-name', 'eventName', ['foo' => 'bar']);
	```
    
- Run component public methods in template files.

	```php
	class HelloWorld extends Component
	{
	    public $message = 'Hello World!';
	    
	    public function _helloTo($name)
	    {
		    return "Hello $name";
	    }
	}
	```

	```blade
	<div>
	    <h1>{{ $helloTo('Bob') }}</h1>
	    <!-- Will output "Hello Bob!" -->
	</div>
	```

## Using Twig

You can use Yoyo with Symphony's [Twig](https://twig.symfony.com/) templating engine.

### Installation

To get started install the following packages in your project:

```bash
composer require clickfwd/yoyo
composer require twig/twig
```

### Configuration 

Create a Twig instance and set it as the view provider for Yoyo. We also add the `YoyoTwigExtension` to Twig.

This code should run when rendering and updating components.

```php
use Clickfwd\Yoyo\Twig\YoyoTwigExtension;
use Clickfwd\Yoyo\ViewProviders\TwigViewProvider;
use Clickfwd\Yoyo\Yoyo;
use Twig\Extension\DebugExtension;

define('APP_PATH', __DIR__);

$yoyo = new Yoyo();

$yoyo->configure([
  'url' => 'yoyo',
  'scriptsPath' => APP_PATH.'/app/resources/assets/js/',
  'namespace' => 'App\\Yoyo\\',
]);

$loader = new \Twig\Loader\FilesystemLoader([
  APP_PATH.'/resources/views',
  APP_PATH.'/resources/views/yoyo',
]);

$twig = new \Twig\Environment($loader, [
  'cache' => APP_PATH.'/../cache',
  'auto_reload' => true,
  // 'debug' => true
]);

// Add Yoyo's Twig Extension

$twig->addExtension(new YoyoTwigExtension());

$yoyo->setViewProvider(new TwigViewProvider($twig));
```

### Load Assets

Find `yoyo.js` in the following vendor path and copy it to your project's public assets directory.

```file
/vendor/clickfwd/yoyo/src/assets/js/yoyo.js 
```

To load the necessary scripts in your Blade template you can use the `yoyo.scripts` the `<head>` tag:

```twig
{{ yoyo.scripts }}
```

### Rendering a Twig View

You can use the Blade instance to render any Blade view.

```
$twig = \Clickfwd\Yoyo\Yoyo::getViewProvider()->getProviderInstance();

echo $twig->render('home');
```

### Rendering Yoyo Twig Components

To render Yoyo components inside Blade views, use the `yoyo` function.

```twig
yoyo('search')
```

### Updating Yoyo Twig Components

To update Yoyo components in the Yoyo-designated route.

```php
echo (new \Clickfwd\Yoyo\Yoyo())->update();
```

### Inline Views

When dealing with simple templates, you can create components without a template file and instead return an inline view in the component's `render` method.

```php
class HelloWorld extends Component
{
    public $message = 'Hello World!';
}

public function render()
{
	return <<<'twig'
		<div>
		    <input yoyo name="message" type="text" value="{{ message }}">
		    <h1>{{ message }}</h1>
		</div>		
	twig;
}
```

### Other Twig Features

Yoyo adds a few functions and variables that can be used within Yoyo component templates.

- The `spinning` variable can be used to heck if a component is being re-rendered. 

	```twig
	{% if spinning %}
	Component updated
	{% endif %}
	```	

- All event methods are available as functions within blade components
	
	```twig
	{{ emit('eventName', {'foo':'bar'}) }}
	{{ emitUp('eventName', {'foo':'bar'}) }}
	{{ emitSelf('eventName', {'foo':'bar'}) }}
	{{ emitTo('component-name', 'eventName', {'foo':'bar'}) }}
	```
	
- Run component public methods in template files.

		```php
		class HelloWorld extends Component
		{
		    public $message = 'Hello World!';
		    
		    public function _helloTo($name)
		    {
			    return "Hello $name";
		    }
		}
		```
	
	```twig
	<div>
		<h1>{{ yoyo.call('helloTo', 'Bob') }}</h1>
		<!-- Will output "Hello Bob!" -->
	</div>
	```


## License

Copyright © ClickFWD

Yoyo is open-sourced software licensed under the [MIT license](LICENSE.md).