<?php

use function Tests\compile_html;
use function Tests\render;
use function Tests\yoyo_view;

beforeAll(function () {
    yoyo_view();
});

function bench_run(string $label, int $iterations, Closure $fn): array
{
    // Warmup
    for ($i = 0; $i < min(10, $iterations); $i++) {
        $fn();
    }

    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $fn();
    }
    $elapsed = (hrtime(true) - $start) / 1e6;
    $perOp = $elapsed / $iterations;

    return ['label' => $label, 'iterations' => $iterations, 'total_ms' => $elapsed, 'per_op_ms' => $perOp];
}

// ---------------------------------------------------------------------------
//  Helper: build a compiled child component (as the parent compiler sees it)
// ---------------------------------------------------------------------------

function childComponent(string $name, int $id, string $innerHtml, array $vals = []): string
{
    $valsJson = json_encode(array_merge(['yoyo-id' => "{$name}-{$id}"], $vals));

    return '<div id="'.$name.'-'.$id.'" yoyo="" hx-get="render" class="yoyo-wrapper" yoyo:name="'.$name.'" hx-ext="yoyo" hx-include="this" hx-trigger="refresh" hx-target="this" hx-vals=\''.$valsJson.'\'>'.$innerHtml.'</div>';
}

// ---------------------------------------------------------------------------
//  Realistic templates
// ---------------------------------------------------------------------------

// 1. Simple interactive component — counter with 2 buttons
$counterHtml = <<<'HTML'
<div id="counter" yoyo:val.count="0">
    <button yoyo:get="decrement">-</button>
    <span>0</span>
    <button yoyo:get="increment">+</button>
</div>
HTML;

// 2. Form with validation — registration/contact form
$formHtml = <<<'HTML'
<form id="register-form" yoyo:post="register" yoyo:on="submit">
    <div>
        <label for="name">Name</label>
        <input id="name" name="name" type="text" value="" />
        <span data-error="name">Name is required</span>
    </div>
    <div>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="" />
        <span data-error="email">Invalid email</span>
    </div>
    <div>
        <label for="message">Message</label>
        <textarea id="message" name="message"></textarea>
    </div>
    <button type="submit">Submit</button>
</form>
HTML;

// 3. Listing list with nested child components per row (JReviews pattern)
//    Parent Yoyo component renders the list; each row has 3 child Yoyo
//    components already compiled: favorite, mylist, comparison
function buildListingList(int $rows): string
{
    $html = '<div id="listing-list" yoyo:val.page="1" yoyo:val.sort="newest">';
    $html .= '<div class="toolbar">';
    $html .= '<select yoyo:on="change" yoyo:get="refresh" name="sort">';
    $html .= '<option value="newest">Newest</option><option value="rating">Rating</option><option value="title">Title</option>';
    $html .= '</select>';
    $html .= '<select yoyo:on="change" yoyo:get="refresh" name="perpage">';
    $html .= '<option value="10">10</option><option value="25">25</option><option value="50">50</option>';
    $html .= '</select>';
    $html .= '</div>';

    $html .= '<div class="listing-rows">';
    for ($i = 1; $i <= $rows; $i++) {
        $html .= '<div class="listing-row">';
        $html .= '<div class="listing-photo"><img src="listing-'.$i.'.jpg" /></div>';
        $html .= '<div class="listing-info">';
        $html .= '<h3><a href="/listing/'.$i.'">Business Name '.$i.'</a></h3>';
        $html .= '<p class="category">Category &gt; Subcategory</p>';
        $html .= '<div class="rating"><span class="stars">★★★★☆</span> <span class="count">(42 reviews)</span></div>';
        $html .= '<p class="address">123 Main St, City, ST 12345</p>';
        $html .= '</div>';

        // 3 nested Yoyo child components (already compiled, as the parent sees them)
        $html .= '<div class="listing-actions">';
        $html .= childComponent('favorite', $i,
            '<button hx-post="toggle" id="favorite-'.$i.'-1" class="btn-fav">♡</button>',
            ['listingId' => $i, 'isFavorite' => 0]
        );
        $html .= childComponent('mylist', $i,
            '<button hx-post="toggle" id="mylist-'.$i.'-1" class="btn-list">+ My List</button><span class="count">3 lists</span>',
            ['listingId' => $i, 'inList' => 0]
        );
        $html .= childComponent('compare', $i,
            '<button hx-post="toggle" id="compare-'.$i.'-1" class="btn-compare">Compare</button>',
            ['listingId' => $i, 'inCompare' => 0]
        );
        $html .= '</div>';

        $html .= '</div>';
    }
    $html .= '</div>';

    // Pagination
    $html .= '<nav class="pagination">';
    for ($p = 1; $p <= 5; $p++) {
        $html .= '<a yoyo:get="render" yoyo:val.page="'.$p.'" class="page-link">'.$p.'</a>';
    }
    $html .= '</nav>';
    $html .= '</div>';

    return $html;
}

$listingList10 = buildListingList(10);
$listingList25 = buildListingList(25);
$listingList50 = buildListingList(50);

// 4. Listing form — complex form with many interactive fields
$listingFormHtml = '<form id="listing-form" yoyo:post="save" yoyo:on="submit">';
$listingFormHtml .= '<div class="form-section"><h3>Basic Info</h3>';
$listingFormHtml .= '<input type="text" name="title" value="Business Name" />';
$listingFormHtml .= '<textarea name="description">Description here</textarea>';
$listingFormHtml .= '<select name="category" yoyo:on="change" yoyo:get="loadSubcategories"><option>Cat 1</option><option>Cat 2</option></select>';
$listingFormHtml .= '<select name="subcategory"><option>Sub 1</option></select>';
$listingFormHtml .= '</div>';
$listingFormHtml .= '<div class="form-section"><h3>Location</h3>';
$listingFormHtml .= '<input type="text" name="address" value="123 Main St" />';
$listingFormHtml .= '<input type="text" name="city" value="City" />';
$listingFormHtml .= '<input type="text" name="state" value="ST" />';
$listingFormHtml .= '<input type="text" name="zip" value="12345" />';
$listingFormHtml .= '<input type="text" name="phone" value="555-1234" />';
$listingFormHtml .= '<input type="url" name="website" value="https://example.com" />';
$listingFormHtml .= '</div>';
// Custom fields section with various interactive field types
$listingFormHtml .= '<div class="form-section"><h3>Custom Fields</h3>';
for ($f = 1; $f <= 8; $f++) {
    $listingFormHtml .= '<div class="field-group">';
    $listingFormHtml .= '<label>Custom Field '.$f.'</label>';
    if ($f % 3 === 0) {
        $listingFormHtml .= '<select name="field'.$f.'" yoyo:on="change" yoyo:get="fieldDependency" yoyo:val.field-id="'.$f.'">';
        $listingFormHtml .= '<option>Option A</option><option>Option B</option><option>Option C</option>';
        $listingFormHtml .= '</select>';
    } elseif ($f % 3 === 1) {
        $listingFormHtml .= '<input type="text" name="field'.$f.'" value="Value '.$f.'" />';
    } else {
        $listingFormHtml .= '<textarea name="field'.$f.'">Content '.$f.'</textarea>';
    }
    $listingFormHtml .= '</div>';
}
$listingFormHtml .= '</div>';
// Media upload section with nested Yoyo component
$listingFormHtml .= '<div class="form-section"><h3>Media</h3>';
$listingFormHtml .= childComponent('media-upload', 1,
    '<div class="dropzone" hx-post="upload" id="media-upload-1-1"><input type="file" name="photos[]" multiple /><p>Drop files here</p></div>'
    .'<div class="preview"><div class="thumb"><img src="photo1.jpg"/><button hx-post="removePhoto" id="media-upload-1-2">×</button></div></div>',
    ['listingId' => 1, 'maxFiles' => 10]
);
$listingFormHtml .= '</div>';
$listingFormHtml .= '<div class="form-actions">';
$listingFormHtml .= '<button type="submit">Save Listing</button>';
$listingFormHtml .= '<button type="button" yoyo:get="preview">Preview</button>';
$listingFormHtml .= '</div>';
$listingFormHtml .= '</form>';

// 5. Admin browse page — CP table with status toggles, edit actions per row
function buildAdminTable(int $rows): string
{
    $html = '<div id="browse-listings" yoyo:val.page="1">';
    $html .= '<div class="filters">';
    $html .= '<input type="search" name="title" yoyo:on="input" yoyo:get="refresh" yoyo:sync="this:replace" placeholder="Search" />';
    $html .= '<select name="category" yoyo:on="change" yoyo:get="refresh"><option>All</option><option>Cat 1</option><option>Cat 2</option></select>';
    $html .= '<select name="status" yoyo:on="change" yoyo:get="refresh"><option>All</option><option>Published</option><option>Unpublished</option></select>';
    $html .= '<select name="ordering" yoyo:on="change" yoyo:get="refresh"><option>Latest</option><option>Title</option><option>Rating</option></select>';
    $html .= '</div>';

    $html .= '<table><thead><tr><th>ID</th><th>Title</th><th>Author</th><th>Category</th><th>Reviews</th><th>Featured</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
    for ($i = 1; $i <= $rows; $i++) {
        $html .= '<tr>';
        $html .= '<td>'.$i.'</td>';
        $html .= '<td><a href="/admin/listing/'.$i.'">Business '.$i.'</a><div class="subtitle">Category &gt; Sub</div></td>';
        $html .= '<td>User '.$i.'<br/><small>Jan '.($i % 28 + 1).', 2025</small></td>';
        $html .= '<td>Category Name</td>';
        $html .= '<td>★ 4.'.($i % 10).' ('.$i.' reviews)</td>';
        $html .= '<td><button yoyo:post="toggleFeatured('.$i.')">'.($i % 3 === 0 ? '★' : '☆').'</button></td>';
        $html .= '<td><button yoyo:post="togglePublished('.$i.')">'.($i % 2 === 0 ? 'Published' : 'Unpublished').'</button></td>';
        $html .= '<td><button yoyo:post="edit('.$i.')">Edit</button> <button yoyo:delete="remove('.$i.')" yoyo:confirm="Delete this listing?">Delete</button></td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    $html .= '<nav>';
    for ($p = 1; $p <= 5; $p++) {
        $html .= '<a yoyo:get="render" yoyo:val.page="'.$p.'">'.$p.'</a>';
    }
    $html .= '</nav></div>';

    return $html;
}

$adminTable25 = buildAdminTable(25);
$adminTable50 = buildAdminTable(50);

// ---------------------------------------------------------------------------
//  Benchmarks
// ---------------------------------------------------------------------------

test('BENCH: counter — simple interactive component', function () use ($counterHtml) {
    $result = bench_run('Counter (2 buttons)', 1000, fn () => compile_html('counter', $counterHtml));
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: form — registration with validation', function () use ($formHtml) {
    $result = bench_run('Form (inputs + submit)', 1000, fn () => compile_html('form', $formHtml));
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: listing list — 10 rows × 3 child components', function () use ($listingList10) {
    $result = bench_run('Listing List (10×3 children)', 200, fn () => compile_html('listing-list', $listingList10));
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: listing list — 25 rows × 3 child components', function () use ($listingList25) {
    $result = bench_run('Listing List (25×3 children)', 200, fn () => compile_html('listing-list', $listingList25));
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: listing list — 50 rows × 3 child components', function () use ($listingList50) {
    $result = bench_run('Listing List (50×3 children)', 100, fn () => compile_html('listing-list', $listingList50));
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: listing form — complex form with fields + media upload', function () use ($listingFormHtml) {
    $result = bench_run('Listing Form (fields + media)', 500, fn () => compile_html('listing-form', $listingFormHtml));
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: admin table — 25 rows with toggles + actions', function () use ($adminTable25) {
    $result = bench_run('Admin Table (25 rows)', 200, fn () => compile_html('browse-listings', $adminTable25));
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: admin table — 50 rows with toggles + actions', function () use ($adminTable50) {
    $result = bench_run('Admin Table (50 rows)', 100, fn () => compile_html('browse-listings', $adminTable50));
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

// --- Summary ---

test('BENCH: summary', function () use ($counterHtml, $formHtml, $listingList10, $listingList25, $listingList50, $listingFormHtml, $adminTable25, $adminTable50) {
    $scenarios = [
        ['Counter (2 buttons)', 1000, fn () => compile_html('counter', $counterHtml)],
        ['Form (inputs + submit)', 1000, fn () => compile_html('form', $formHtml)],
        ['Listing List (10×3 children)', 200, fn () => compile_html('listing-list', $listingList10)],
        ['Listing List (25×3 children)', 200, fn () => compile_html('listing-list', $listingList25)],
        ['Listing List (50×3 children)', 100, fn () => compile_html('listing-list', $listingList50)],
        ['Listing Form (fields + media)', 500, fn () => compile_html('listing-form', $listingFormHtml)],
        ['Admin Table (25 rows)', 200, fn () => compile_html('browse-listings', $adminTable25)],
        ['Admin Table (50 rows)', 100, fn () => compile_html('browse-listings', $adminTable50)],
    ];

    fwrite(STDERR, "\n");
    fwrite(STDERR, "  ┌──────────────────────────────────────┬────────┬──────────────┐\n");
    fwrite(STDERR, "  │ Scenario                             │  ops   │    ms/op     │\n");
    fwrite(STDERR, "  ├──────────────────────────────────────┼────────┼──────────────┤\n");

    foreach ($scenarios as [$label, $iterations, $fn]) {
        $r = bench_run($label, $iterations, $fn);
        fwrite(STDERR, sprintf(
            "  │ %-36s │ %5d  │ %10.4f   │\n",
            $label,
            $r['iterations'],
            $r['per_op_ms']
        ));
    }

    fwrite(STDERR, "  └──────────────────────────────────────┴────────┴──────────────┘\n\n");

    expect(true)->toBeTrue();
})->group('benchmark');
