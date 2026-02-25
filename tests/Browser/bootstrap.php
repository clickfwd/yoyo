<?php

// Shared bootstrap for browser tests.
// Each test file requires this to start the server and define BASE_URL.

use Tests\Browser\BrowserServer;

BrowserServer::ensureRunning();

if (! defined('BASE_URL')) {
    define('BASE_URL', BrowserServer::url());
}
