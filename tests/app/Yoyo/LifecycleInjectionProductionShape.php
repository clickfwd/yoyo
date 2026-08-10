<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;
use Tests\App\CategoryModel;
use Tests\App\ListingModel;
use Tests\App\PageSettings;
use Tests\App\SiteRequestContract;

/**
 * The widest real signature: several container slots in one hook, one of them an
 * interface, one of them named "request" -- by far the most common slot name in
 * practice -- alongside a caller-supplied scalar.
 *
 * Each collaborator reports a distinct label so a partially applied filter shows up
 * as one wrong label rather than passing on a shared string.
 */
class LifecycleInjectionProductionShape extends Component
{
    public $slug = 'none';

    protected $labels = [];

    public function mount(
        SiteRequestContract $request,
        PageSettings $pageSettings,
        CategoryModel $category,
        ListingModel $listing,
        $slug = 'none'
    ) {
        $this->labels = [
            'request'      => $request->label(),
            'pageSettings' => $pageSettings->label(),
            'category'     => $category->label(),
            'listing'      => $listing->label(),
        ];

        $this->slug = $slug;
    }

    public function render()
    {
        $parts = [];

        foreach ($this->labels as $name => $label) {
            $parts[] = "{$name}={$label}";
        }

        $parts[] = "slug={$this->slug}";

        return $this->view('lifecycle-injection', ['result' => implode(' ', $parts)]);
    }
}
