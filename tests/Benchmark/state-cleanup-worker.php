<?php

use Clickfwd\Yoyo\ClassHelpers;
use Clickfwd\Yoyo\Component;
use Clickfwd\Yoyo\Request;
use Clickfwd\Yoyo\Services\Response;
use Clickfwd\Yoyo\View;
use Clickfwd\Yoyo\ViewProviders\YoyoViewProvider;
use Clickfwd\Yoyo\Yoyo;

[$script, $source, $vendor, $workload, $iterations, $output] = $argv;
$iterations = (int) $iterations;
$rowCount = ['empty' => 0, 'small' => 1, 'discarded' => 50, 'exposed' => 50][$workload];
spl_autoload_register(function (string $class) use ($source, $vendor): void {
    foreach (['Clickfwd\\Yoyo\\' => $source.'/src/yoyo/', 'Psr\\Container\\' => $vendor.'/psr/container/src/'] as $prefix => $path) {
        if (str_starts_with($class, $prefix)) {
            require $path.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
        }
    }
});
require $source.'/src/yoyo/helpers.php';

set_error_handler(function (int $severity, string $message, string $file, int $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
}, E_ALL);

class StateCleanupList extends Component
{
    public array $rows = [];
    public int $page = 1;

    protected $props = ['page'];

    public function choose(int $id)
    {
        throw new LogicException('This benchmark only renders action controls.');
    }
}

class ExposedStateCleanupList extends StateCleanupList
{
    protected $props = ['page', 'rows'];
}

$rows = [];
$cleanRows = [];
for ($i = 1; $i <= $rowCount; $i++) {
    $clean = [
        'id' => $i, 'title' => 'Item '.$i, 'address' => '123 Main Street', 'rating' => 4.5,
        'description' => str_repeat('Description of item. ', 8), 'category' => 'Travel',
        'country' => 'DO', 'city' => 'Santo Domingo', 'website' => 'https://example.test/'.$i,
        'phone' => '555-1234', 'reviews' => 42,
        'metadata' => ['nested' => ['active' => true, 'published' => true]], 'tags' => [],
    ];
    $row = $clean;
    $row['metadata'] = ['empty' => '', 'nullable' => null, 'nested' => ['active' => true, 'published' => true, 'unused' => []]];
    for ($j = 0; $j < 8; $j++) {
        $tag = ['id' => $j, 'label' => 'Tag '.$j, 'options' => ['visible' => true, 'color' => 'red']];
        $clean['tags'][] = $tag;
        $tag['options'] += ['nullable' => null, 'empty' => ''];
        $row['tags'][] = $tag;
    }
    $rows[] = $row;
    $cleanRows[] = $clean;
}

$yoyo = Yoyo::getInstance();
$yoyo->bindRequest(new Request());
$yoyo->registerViewProvider(new YoyoViewProvider(new View(__DIR__)));
Yoyo::registerComponent('state-cleanup-list', $workload === 'exposed' ? ExposedStateCleanupList::class : StateCleanupList::class);
$render = function () use ($rows): string {
    ClassHelpers::flushCache();
    Yoyo::request()->mock([], []);

    return Yoyo::getInstance()->mount('state-cleanup-list', ['rows' => $rows], ['id' => 'state-cleanup-list'])->render();
};
for ($i = 0; $i < 10; $i++) {
    $render();
}
$reference = $render();
$document = new DOMDocument();
$previous = libxml_use_internal_errors(true);
$document->loadHTML($reference);
libxml_clear_errors();
libxml_use_internal_errors($previous);
$xpath = new DOMXPath($document);
$values = json_decode($document->getElementById('state-cleanup-list')->getAttribute('hx-vals'), true, 512, JSON_THROW_ON_ERROR);
$expected = ['yoyo-id' => 'state-cleanup-list', 'page' => 1];
if ($workload === 'exposed') {
    $expected = ['yoyo-id' => 'state-cleanup-list', 'rows[]' => $cleanRows, 'page' => 1];
}
if ($values !== $expected
    || $xpath->query('//tr')->length !== $rowCount
    || $xpath->query('//span')->length !== $rowCount * 8
    || $xpath->query('//button[@hx-post="choose"]')->length !== $rowCount
    || $xpath->query('//button[@hx-get="render"]')->length !== 1) {
    throw new RuntimeException('Rendered rows, tags, action controls, or serialized state did not match.');
}
foreach ($xpath->query('//tr') as $index => $element) {
    if ($xpath->evaluate('string(td[2])', $element) !== 'Item '.($index + 1)) {
        throw new RuntimeException('Rendered row title did not match.');
    }
    foreach ($xpath->query('.//span', $element) as $tag => $label) {
        if ($label->textContent !== 'Tag '.$tag) {
            throw new RuntimeException('Rendered tag label did not match.');
        }
    }
}
file_put_contents($output, $reference);
$usageBefore = getrusage();
$start = hrtime(true);
$bytes = 0;
for ($i = 0; $i < $iterations; $i++) {
    $html = $render();
    if ($html !== $reference || Response::getInstance()->getStatusCode() !== 200 || Response::getInstance()->getHeaders() !== []) {
        throw new RuntimeException('Timed HTML, status, or headers did not match.');
    }
    $bytes += strlen($html);
}
$elapsed = hrtime(true) - $start;
$usageAfter = getrusage();
$cpuUs = 0;
foreach (['ru_utime', 'ru_stime'] as $clock) {
    $cpuUs += ($usageAfter[$clock.'.tv_sec'] - $usageBefore[$clock.'.tv_sec']) * 1000000
        + $usageAfter[$clock.'.tv_usec'] - $usageBefore[$clock.'.tv_usec'];
}
$renderer = (new ReflectionClass(Yoyo::class))->getFileName();
if (realpath($renderer) !== realpath($source.'/src/yoyo/Yoyo.php')) {
    throw new RuntimeException('The renderer was not loaded from the requested source tree.');
}
echo json_encode([
    'php' => PHP_VERSION, 'renderer_file' => $renderer, 'renderer_sha256' => hash_file('sha256', $renderer),
    'opcache_cli' => ini_get('opcache.enable_cli'), 'jit' => ini_get('opcache.jit'),
    'wall_us_per_render' => $elapsed / 1000 / $iterations, 'cpu_us_per_render' => $cpuUs / $iterations,
    'cpu_wall_ratio' => $cpuUs / ($elapsed / 1000), 'validated_renders' => $iterations, 'errors' => 0,
    'rows_rendered' => $iterations * $rowCount, 'tags_rendered' => $iterations * $rowCount * 8,
    'choose_controls_rendered' => $iterations * $rowCount, 'page_controls_rendered' => $iterations,
    'dispatched_actions' => 0, 'output_bytes' => $bytes, 'output_sha256' => hash('sha256', $reference),
    'status' => Response::getInstance()->getStatusCode(), 'headers' => Response::getInstance()->getHeaders(),
], JSON_THROW_ON_ERROR)."\n";
