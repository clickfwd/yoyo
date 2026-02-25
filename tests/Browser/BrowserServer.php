<?php

namespace Tests\Browser;

/**
 * Manages the PHP built-in server for browser tests.
 * Auto-starts if not already running, auto-stops on shutdown.
 */
class BrowserServer
{
    private static ?self $instance = null;

    /** @var resource|null */
    private $process = null;

    /** @var array<int, resource> */
    private array $pipes = [];

    public const HOST = 'localhost';

    public const PORT = 8765;

    private function __construct() {}

    public static function url(): string
    {
        return sprintf('http://%s:%d', self::HOST, self::PORT);
    }

    /**
     * Ensure the server is running. Safe to call multiple times.
     */
    public static function ensureRunning(): void
    {
        if (self::$instance !== null) {
            return;
        }

        self::$instance = new self();

        // Already running externally (manual start)?
        if (self::isListening()) {
            return;
        }

        self::$instance->start();

        register_shutdown_function([self::class, 'stop']);
    }

    /**
     * Start the PHP built-in server.
     */
    private function start(): void
    {
        $router = realpath(__DIR__.'/server/index.php');

        if ($router === false) {
            throw new \RuntimeException('Browser test server router not found at tests/Browser/server/index.php');
        }

        $cmd = sprintf(
            'php -S %s:%d %s',
            self::HOST,
            self::PORT,
            escapeshellarg($router)
        );

        $this->process = proc_open(
            $cmd,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $this->pipes,
            dirname($router)
        );

        if (! is_resource($this->process)) {
            throw new \RuntimeException('Failed to start browser test server');
        }

        // Wait for server to accept connections (up to 5 seconds)
        for ($i = 0; $i < 50; $i++) {
            if (self::isListening()) {
                return;
            }

            usleep(100_000); // 100ms
        }

        self::stop();
        throw new \RuntimeException(
            sprintf('Browser test server failed to start on %s:%d within 5 seconds', self::HOST, self::PORT)
        );
    }

    /**
     * Check if the server port is accepting connections.
     */
    public static function isListening(): bool
    {
        $sock = @fsockopen(self::HOST, self::PORT, $errno, $errstr, 0.5);

        if ($sock) {
            fclose($sock);

            return true;
        }

        return false;
    }

    /**
     * Stop the server if we started it.
     */
    public static function stop(): void
    {
        if (self::$instance === null) {
            return;
        }

        $instance = self::$instance;

        if (is_resource($instance->process)) {
            // Close stdin to signal shutdown
            if (isset($instance->pipes[0]) && is_resource($instance->pipes[0])) {
                fclose($instance->pipes[0]);
            }

            proc_terminate($instance->process);
            proc_close($instance->process);
            $instance->process = null;
        }

        self::$instance = null;
    }
}
