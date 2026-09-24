# Contributing

Thanks for your interest in improving this package!

- **Everyone** can open issues and pull requests from their own fork.
- **Only approved maintainers** can merge. The `main` branch is protected: every change needs a
  pull request, passing tests, and an approving review from the code owner.

## Workflow

1. Fork the repository and create a branch: `git checkout -b fix/short-description`
2. Make your change and add or adjust tests in `tests/`.
3. Run the test suite: `composer install && vendor/bin/phpunit`
4. Open a pull request against `main` describing **what** changed and **why**.

## Guidelines

- Follow PSR-12 / Laravel coding style.
- Keep backwards compatibility within a major version.
- Do not commit `vendor/` or `composer.lock`.
