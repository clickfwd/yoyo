<?php

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

it('preserves the native template failure when buffer cleanup cannot complete normally', function ($mode, $failureType) {
    $process = new Process([
        PHP_BINARY,
        '-d', 'display_errors=0',
        '-d', 'log_errors=0',
        __DIR__.'/../fixtures/native-buffer-cleanup.php',
        $mode,
        $failureType,
    ]);
    $process->setTimeout(2);

    try {
        $process->run();
        expect($process->getExitCode())->toBe(0);

        $result = json_decode($process->getErrorOutput(), true, 512, JSON_THROW_ON_ERROR);
        expect($result['same_exception'])->toBeTrue()
            ->and($result['caller_buffer_preserved'])->toBeTrue()
            ->and($result['remaining_buffers'])->toBe($mode === 'nonremovable' ? 3 : 1)
            ->and($process->getOutput())->toBe($mode === 'nonremovable' ? 'callerpartialinner' : 'caller');
    } catch (ProcessTimedOutException $e) {
        $this->fail('Native render did not return within two seconds after a template failure.');
    } finally {
        $process->stop(0);
    }
})->with(['handler', 'nonremovable'])->with([RuntimeException::class, Error::class]);
