# Contributing to Greetup

Thank you for your interest in contributing to Greetup! This guide will help you get set up and ensure your contributions can be merged smoothly.

## Getting Started

1. Fork the repository and clone your fork.
2. Set up your local environment using the [Quick Start guide](README.md#quick-start-with-docker).
3. Create a new branch from `main` for your work.

## Branch Naming

Use descriptive branch names with a prefix:

- `feature/` — new functionality (e.g., `feature/event-recurring-series`)
- `fix/` — bug fixes (e.g., `fix/waitlist-promotion-race-condition`)
- `refactor/` — code improvements without behavior changes
- `docs/` — documentation changes
- `test/` — adding or improving tests

## Making Changes

1. **Search before building.** Check existing issues and PRs to avoid duplicating work.
2. **Keep PRs focused.** One feature or fix per pull request. Small PRs are reviewed faster.
3. **Write tests.** All new features need feature tests. Bug fixes should include a test that reproduces the bug.
4. **Follow existing patterns.** Check sibling files for naming conventions, structure, and approach before creating something new.

## Before Submitting a PR

Run the full quality check suite:

```bash
# Fix code style (required — CI will reject style violations)
vendor/bin/pint

# Static analysis
vendor/bin/phpstan analyse

# Run the full test suite
vendor/bin/pest --parallel
```

All three must pass. CI runs these automatically on every PR.

## Commit Messages

Write clear, concise commit messages:

- Use the imperative mood ("Add waitlist promotion" not "Added waitlist promotion")
- First line: short summary (under 72 characters)
- Optionally: blank line followed by a longer explanation of *why*, not *what*

Good:
```
Add automatic waitlist promotion when RSVP is cancelled

When a Going RSVP is cancelled, the next eligible waitlisted member
is promoted via a queued job. Members with guests are skipped if
there aren't enough spots for their full party.
```

## Pull Request Process

1. Fill in the PR template with a summary and test plan.
2. Ensure CI passes (lint, tests, browser tests).
3. A maintainer will review your PR. Address feedback and push updates to the same branch.
4. Once approved, a maintainer will merge via squash-and-merge.

## Reporting Bugs

Open an issue with:
- Steps to reproduce
- Expected behavior
- Actual behavior
- Environment details (PHP version, database, browser if relevant)

## Code of Conduct

Be respectful and constructive. We're building a community platform — let's model the community we want to see.
