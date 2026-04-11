<?php

require __DIR__.'/../layout.php';

$links = [
    'counter' => 'Counter',
    'live-search' => 'Live Search',
    'form' => 'Form',
    'todo-list' => 'Todo List',
    'pagination' => 'Pagination',
];

$html = '<h1>Yoyo Browser Test Components</h1><ul>';
foreach ($links as $path => $label) {
    $html .= "<li><a href=\"/$path\">$label</a></li>";
}
$html .= '</ul>';

render_page('Yoyo Browser Tests', $html);
