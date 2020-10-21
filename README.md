# Yoyo

Yoyo is a full-stack PHP framework that you can use on any project to create rich dynamic interfaces using server-rendered HTML.

With Yoyo, you create reactive components that are seamlessly updated without the need to write any Javascript code.

Yoyo ships with a simple templating system, and  offers out-of-the-box support for [Blade](https://laravel.com/docs/8.x/blade), without having to use Laravel, and [Twig](https://twig.symfony.com/).

Inspired by [Laravel Livewire](https://laravel-livewire.com/) and [Sprig](https://putyourlightson.com/plugins/sprig), and using [htmx](https://htmx.org/).

## 🚀 Yoyo Demo Apps  

Check out the [Yoyo Demo App](https://app.getyoyo.dev) to get a better idea of what you can build with Yoyo. It showcases many different types of Yoyo components. You can also clone and install the demo apps:

- [Yoyo App with built-in templating](https://github.com/clickfwd/yoyo-app)
- [Yoyo Blade App](https://github.com/clickfwd/yoyo-blade-app)
- [Yoyo Twig App](https://github.com/clickfwd/yoyo-twig-app)

## Documentation 

- [How it Works](#how-it-works)
- [Installation](#installation)
- [Updating](#updating)
- [Creating Components](#creating-components)
- [Rendering Components](#rendering-components)
- [Properties](#properties)
- [Actions](#actions)
- [Computed Properties](#computed-properties)
- [Events](#events)
- [Redirecting](#redirecting)
- [Query String](#query-string)
- [Using Blade](#using-blade)
- [Using Twig](#using-twig)
- [License](#license)

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
}
```

**Component template**

```html
<!-- /app/resources/views/yoyo/counter.php -->

<div>

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

## Updating

After performing the usual `composer update`, remember to also update the `yoyo.js` script per the [Load Assets](#load-assets) instructions.

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

The example can be converted into a live search input, with a 300ms debounce to minimize the number of requests. Replace the `form` tag with:

```html
<input yoyo:on="keyup delay:300ms changed" type="text" name="query" value="<?php echo $query; ?>" />
```

The `yoyo:on="keyup delay:300ms change"` directive tells Yoyo to make a request on the keyup event, with a 300ms debounce, and only if the input text changed. 

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

```html
<!-- /app/resources/views/yoyo/search.php -->

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

### Rendering on Page Load

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
    <button yoyo:on="click" yoyo:get="helpful">Found Helpful</button>
</div>
```

All components automatically listen for the `refresh` event and trigger the `render` action to refresh the component state.

### Passing Data to Actions

You can include additional data to send to the server con component update requests using the `yoyo:vars` directive which accepts a separated list of `key:<expression>` values.

```html
<button yoyo:on="click" yoyo:get="helpful" yoyo:vars="reviewId:100">Found Helpful</button>
```

Yoyo will automatically track and send component public properties and input values with every request. 

```php
class Review extends Component {

	public $reviewId;

	public function helpful()
	{
		// access reviewId via $this->reviewId
	}
}
```

You can also pass extra parameters to an action as arguments using an expression, without having to define them as public properties in the component:

```html
<button yoyo:get="addToCart(<?php echo $productId; ?>, '<?php echo $style; ?>')">
    Add Todo
</button>
```

Extra parameters passed to an action are to the component method as regular arguments:

```php
public function addToCart($productId, $style)
{
    // ...
}
```

### Actions Without a Response

Sometimes you may want to use a component action only to make changes to a database and trigger events, without rendering a response. You can use the component `skipRender` method for this:

```php
public function savePost() 
{
	// Store the post to the database

	// Send event to the browser to close modal, or trigger a notification
	$this->emitSelf('PostSaved');

	// Skip template rendering
	$this->skipRender();
}
```

## Computed Properties

```php
class HelloWorld extends Component
{
	public $message = 'Hello World!';
	
   	// Computed Property
	public function getHelloWorldProperty()
	{
		return $message;
	}
}
```
	
Now, you can access `$this->hello_world` from either the component's class or template:

```php
<div>
	<h1><?php echo $this->hello_world ;?></h1>
	<!-- Will output "Hello World!" -->
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

## Loading States

Updating Yoyo components requires an Ajax request to the server and depending on what the component does, the response time will vary. The `yoyo:spinning` directive allows you to do all sorts of cool things when a component is updating to provide a visual indicator to end-users.

### Toggling Elements During Loading States

To show an element at the start of a Yoyo update request and hide it again when the update is complete:

```html
<div>
    <button yoyo:post="submit">Submit</button>

    <div yoyo:spinning>
        Processing your submission...
    </div>
</div>
```

Yoyo adds some CSS to the page to automatically hide the element with the `yoyo:spinning` directive.

To hide a visible element while the component is updating you can add the `remove` modifier:

```html
<div>
    <button yoyo:post="submit">Submit</button>

    <div yoyo:spinning.remove>
        Text hidden while updating ...
    </div>
</div>
```

## Delaying Loading States

Some actions may update quickly and showing a loading state in these cases may be more of a distraction. The `delay` modifier ensures that the loading state changes are applied only after 200ms if the component hasn't finished updating.

```html
<div>
    <button yoyo:post="submit">Submit</button>

    <div yoyo:spinning.delay>
        Processing your submission...
    </div>
</div>
```

### Targeting Specific Actions

If you need to toggle different indicators for different component actions, you can add the `yoyo:spin-on` directive and pass a comma separated list of action names. For example:

```html
<div>
	<button yoyo:get="edit">Edit</button>

	<button yoyo:get="like">Like</button>

    <div yoyo:spinning yoyo:spin-on="edit">
        Show for edit action
    </div>

    <div yoyo:spinning yoyo:spin-on="like">
        Show for like action
    </div>

    <div yoyo:spinning yoyo:spin-on="edit, like">
        Show for edit and like actions
    </div>

</div>
```

## Toggling Element CSS Classes

Instead of toggling the visibility of an element you can also add specific CSS classes while the component updates. Use the `class` modifier and include the space-separated class names as the attribute value:

```html
<div>
    <button yoyo:post="submit" yoyo:spinning.class="text-gray-300">
		Submit
	</button>
</div>
```

You can also remove specific class names by adding the `remove` modifier:

```html
<div>
    <button yoyo:post="submit" yoyo:spinning.class.remove="bg-blue-200" class="bg-blue-200">
		Submit
	</button>
</div>
```

## Toggling Element Attributes

Similar to CSS class toggling, you can also add or remove attributes while the component is updating.

```html
<div>
    <button yoyo:post="submit" yoyo:spinning.attr="disabled">
		Submit
	</button>
</div>
```

## Events

Events are a great way to establish communication between Yoyo components on the same page, where one or more components can listen to events fired by another component.

Events can be fired from component methods and templates using a variety of emit methods.

All emit methods accept any number of arguments that allow sending data (string, number, array) to listeners.

### Emitting an Event to All Yoyo Components

From a component method.

```php
public function increment()
{
	$this->count++;
		
	$this->emit('counter-updated', $count);
}
```

From a template

```php
<?php $this->emit('counter-updated', $count) ; ?>
```

### Emitting an Event to Parent Components

When dealing with nested components you can emit events to parents and not children or sibling components.

```php
$this->emitUp('postWascreated', $arg1, $arg2);
```

### Emitting an Event to a Specific Component

When you need to emit an event to a specific component using the component name (e.g. `cart`).

```php
$this->emitTo('cart', 'productAddedToCart', $arg1, $arg2);
```

#### Emitting an Event to Itself

When you need to emit an event on the same component.

```php
$this->emitSelf('productAddedToCart', $arg1, $arg2);
```

### Listening for Events

To register listeners in Yoyo, use the `$listeners` protected property of the component.

Listeners are a key->value pair where the key is the event to listen for, and the value is the method to call on the component. If the event and method are the same, you can leave out the key.

```php
class CounterBoard extends Component {

	public $message;

	protected $listeners = ['counter-updated' => 'showNewCount'];

	protected function showNewCount($count)
	{
		$this->message = "The new count is: $count";
	}
}
```

### Listening For Events In JavaScript

Yoyo allows registering event listeners for component emitted events:

```js
<script>
Yoyo.on('productAddedToCart', id => {
	alert('A product was added to the cart with ID:' + id
});
</script>
```

With this feature you can control toasters, alerts, modals, etc. directly from a component action on the server by emitting the event and listening for it on the browser.

### Dispatching Browser Events

In addition to allowing components to communicate with each other, you can also send browser window events directly from a component method or template:

```php
// passing single value
$this->dispatchBrowserEvent('counter-updated', $count);

// Passing an array
$this->dispatchBrowserEvent('counter-updated', ['count' => $count]);
```

And listen for the event anywhere on the page:

```js
<script>
window.addEventListener('counter-updated', event => {
	// Reading a single value
	alert('Counter is now: ' + event.detail);

	// Reading from an array
	alert('Counter is now: ' + event.detail.count);
})
</script>
```
## Redirecting

Sometimes you may want to redirect the user to a different page after performing an action within a Yoyo component.

```php
class Registration extends Component
{
    public function register()
    {
	// Create the user 

	$this->redirect('/welcome');
    }
}
```

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
    
- Computed properties

	```php
	class HelloWorld extends Component
	{
	    public $message = 'Hello World!';

	    public function getHelloWorldProperty()
	    {
		    return $message;
	    }
	}
	```

	```blade
	<div>
	    <h1>{{ $this->hello_world }}</h1>
	    <!-- Will output "Hello World!" -->
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

To load the necessary scripts in your Twig template you can use the `yoyo_scripts` function in the `<head>` tag:

```twig
{{ yoyo_scripts() }}
```

### Rendering a Twig View

You can use the Twig instance to render any Twig view.

```
$twig = \Clickfwd\Yoyo\Yoyo::getViewProvider()->getProviderInstance();

echo $twig->render('home');
```

### Rendering Yoyo Twig Components

To render Yoyo components inside Twig views, use the `yoyo` function.

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
	
- Computed properties

	```php
	class HelloWorld extends Component
	{
	    public $message = 'Hello World!';

	    public function getHelloWorldProperty()
	    {
		    return $this->message;
	    }
	}
	```
	
	```twig
	<div>
		<h1>{{ this.hello_world }}</h1>
		<!-- Will output "Hello World!" -->
	</div>
	```


## License

Copyright © ClickFWD

Yoyo is open-sourced software licensed under the [MIT license](LICENSE.md).
