<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class TodoList extends Component
{
    public $filter = '';

    protected $props = ['filter'];

    protected $queryString = ['filter'];

    // Simple in-memory storage via session
    public function mount()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['todos'])) {
            $_SESSION['todos'] = [
                ['id' => 1, 'title' => 'Build a framework', 'completed' => false],
                ['id' => 2, 'title' => 'Buy groceries', 'completed' => false],
                ['id' => 3, 'title' => 'Write tests', 'completed' => true],
            ];
        }
    }

    public function add()
    {
        $title = trim($this->request->get('task', ''));

        if ($title) {
            $id = max(array_column($_SESSION['todos'], 'id')) + 1;
            $_SESSION['todos'][] = ['id' => $id, 'title' => $title, 'completed' => false];
        }
    }

    public function toggle()
    {
        $id = (int) $this->request->get('id', 0);

        foreach ($_SESSION['todos'] as &$todo) {
            if ($todo['id'] === $id) {
                $todo['completed'] = ! $todo['completed'];
                break;
            }
        }
    }

    public function delete()
    {
        $id = (int) $this->request->get('id', 0);

        $_SESSION['todos'] = array_values(array_filter($_SESSION['todos'], function ($todo) use ($id) {
            return $todo['id'] !== $id;
        }));
    }

    protected function getEntriesProperty()
    {
        $todos = $_SESSION['todos'] ?? [];

        if ($this->filter === 'active') {
            return array_filter($todos, fn ($t) => ! $t['completed']);
        }

        if ($this->filter === 'completed') {
            return array_filter($todos, fn ($t) => $t['completed']);
        }

        return $todos;
    }

    protected function getCountProperty()
    {
        return count($_SESSION['todos'] ?? []);
    }

    protected function getActiveCountProperty()
    {
        return count(array_filter($_SESSION['todos'] ?? [], fn ($t) => ! $t['completed']));
    }
}
