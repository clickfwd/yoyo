<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class DispatchListener extends Component
{
    public $message = '';

    protected $listeners = [
        'post-created' => 'handlePostCreated',
        'status-changed' => 'handleStatusChanged',
        'simple-refresh' => 'handleSimpleRefresh',
    ];

    public function handlePostCreated($postId)
    {
        $this->message = "Post created with ID: {$postId}";
    }

    public function handleStatusChanged($status, $reason = 'none')
    {
        $this->message = "Status: {$status}, Reason: {$reason}";
    }

    public function handleSimpleRefresh()
    {
        $this->message = 'Refreshed without params';
    }
}
