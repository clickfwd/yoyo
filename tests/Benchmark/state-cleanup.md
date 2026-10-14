# State cleanup benchmark

Run from a checkout that contains this tool. Pass two checkouts or extracted source trees containing `src/yoyo`. Both sides use the same PHP binary and installed PSR container interfaces. Python 3 and PHP with DOM and OPcache are required.

```sh
python3 tests/Benchmark/state-cleanup.py /path/to/baseline /path/to/candidate \
    --vendor /path/to/candidate/vendor --php php --trials 10 --iterations 300 > results.json
```

The runner alternates baseline and candidate in separate processes for each workload. It measures full initial rendering with the native container and view provider, prebuilt rows, cold reflection metadata, warm bytecode, and concurrency one. It covers 50 discarded rows, 50 exposed rows, an empty list, and one row. Each row has eight tags and nested state containing empty values.

The timed region resets reflection metadata and request state, mounts and renders the component, compiles the HTML, consumes its bytes, and checks exact HTML, status, and headers. Input generation, ten warmups, output-shape validation, and reference-file writes are outside timing. The runner also compares exact output bytes between sides. Controls count rendered action attributes; no action handler is dispatched.

The JSON report includes source-tree and loaded renderer SHA-256 hashes, runtime flags, load averages, work and error counts, every trial, medians, ranges, and an exact two-sided rank-sum probability. A process failure, diagnostic, output mismatch, or source change fails the run. This local render measurement excludes database work, HTTP handling, transport, and browser work. Treat overlapping timings or a busy host as uncertainty, and profile separately before attributing a difference to one operation.

The standalone compatibility regression uses the current checkout's source and installed PSR interfaces without loading development dependencies. Run it with each supported PHP binary, including PHP 8.0 and PHP 8.5, to verify native rendering under a throwing error handler.

```sh
php tests/Compatibility/state-cleanup.php
```
