<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class DispatchListener extends Component
{
    public $message = '';

    public $postId = 0;

    public $status = 'idle';

    protected $listeners = [
        'post-created' => 'handlePostCreated',
        'status-changed' => 'handleStatusChanged',
        'simple-refresh' => 'handleSimpleRefresh',
        'multi-param' => 'handleMultiParam',
    ];

    public function handlePostCreated($postId)
    {
        $this->postId = $postId;
        $this->message = "Post created with ID: {$postId}";
    }

    public function handleStatusChanged($status, $reason = 'none')
    {
        $this->status = $status;
        $this->message = "Status: {$status}, Reason: {$reason}";
    }

    public function handleSimpleRefresh()
    {
        $this->message = 'Refreshed without params';
    }

    public function handleMultiParam($title, $body, $categoryId)
    {
        $this->message = "Title: {$title}, Body: {$body}, Category: {$categoryId}";
    }
}
