# Contributing to Yoyo

Contributions come in as pull requests. Issues and discussions are turned off for this
repository, so a pull request is also the way to report a bug: include a failing test
that shows it, with or without a fix.

To report a security vulnerability, do not open a pull request. Follow
[SECURITY.md](SECURITY.md).

## Branches

- Open every pull request against `develop`, the default branch.
- `master` holds released code only. It receives `develop` at each release, and a pull
  request against `master` is not merged. If you opened one by mistake, change its base
  branch to `develop`.

## What a pull request needs before it is merged

- One approving review from a maintainer. A push after the approval needs a new approval.
- Every review conversation resolved.
- Passing CI: code style, unit and feature tests on PHP 8.3 and 8.4, and the browser tests.
  CI runs on a pull request from a fork after a maintainer approves the run.

Keep each pull request to one change. A bug fix includes a test that fails without the
fix. A new feature includes tests and, if it changes how Yoyo is used, an update to
`README.md`.

## Running the checks locally

Install the dependencies:

```bash
composer install
npm ci
npx playwright install chromium
```

Run the same checks as CI:

```bash
vendor/bin/pest --testsuite="Unit Suite,Feature Suite"
vendor/bin/pest --group=browser
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --allow-risky=yes
```

The last command fixes code style in place. Commit its changes with your pull request.
Development tooling needs PHP 8.3 or newer. The library itself supports PHP 8.0, so do
not use newer language features in `src/`.

## Changelog

Add an entry to `CHANGELOG.md` under `Unreleased`, in the section that fits:
`Security`, `Breaking`, `Added`, `Changed` or `Fixed`. Describe what changes for someone
using Yoyo, not how the code changed.

## License

By contributing, you agree that your contribution is licensed under the
[MIT license](LICENSE.md).
