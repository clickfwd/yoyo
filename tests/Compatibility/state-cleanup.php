<?php

use Clickfwd\Yoyo\Component;
use Clickfwd\Yoyo\Request;
use Clickfwd\Yoyo\Services\Response;
use Clickfwd\Yoyo\View;
use Clickfwd\Yoyo\ViewProviders\YoyoViewProvider;
use Clickfwd\Yoyo\Yoyo;

$root = dirname(__DIR__, 2);
spl_autoload_register(function (string $class) use ($root): void {
    foreach (['Clickfwd\\Yoyo\\' => '/src/yoyo/', 'Psr\\Container\\' => '/vendor/psr/container/src/'] as $prefix => $path) {
        if (str_starts_with($class, $prefix)) {
            require $root.$path.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
        }
    }
});
require $root.'/src/yoyo/helpers.php';

class CompatibleStateComponent extends Component
{
    public $rows = [];
    public $payload = [];
    public $rootAttributes = '';

    protected $props = ['payload'];

    public function render()
    {
        return $this->view('state-cleanup');
    }
}

$yoyo = Yoyo::getInstance();
$yoyo->bindRequest(new Request());
$yoyo->registerViewProvider(new YoyoViewProvider(new View($root.'/tests/app/resources/views/yoyo')));
Yoyo::registerComponent('compatible-state', CompatibleStateComponent::class);

set_error_handler(function (int $severity, string $message, string $file, int $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
}, E_ALL);

try {
    $html = $yoyo->mount('compatible-state', [
        'rows' => [['title' => 'Visible row', 'empty' => '']],
        'payload' => ['empty' => '', 'null' => null, 'zero' => 0, 'nested' => ['value' => 7, 'empty' => '']],
    ], ['id' => 'compatible-state'])->render();

    $document = new DOMDocument();
    $document->loadHTML($html);
    $root = $document->getElementById('compatible-state');
    $values = json_decode($root->getAttribute('hx-vals'), true, 512, JSON_THROW_ON_ERROR);
    if ($values !== ['yoyo-id' => 'compatible-state', 'payload[]' => ['zero' => 0, 'nested' => ['value' => 7]]]
        || ! str_contains($html, '<p>Visible row</p>')
        || Response::getInstance()->getStatusCode() !== 200
        || Response::getInstance()->getHeaders() !== []) {
        throw new RuntimeException('Rendered state, HTML, status, or headers did not match.');
    }
} finally {
    restore_error_handler();
}

echo 'PASS PHP '.PHP_VERSION." native render with strict error handling\n";
