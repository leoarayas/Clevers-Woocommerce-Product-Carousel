# Contributing to Clevers Product Carousel

## Versioning Policy

This project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html):

- **MAJOR** (X.0.0): Breaking changes that require user action (e.g., removing a CPT, changing database schema)
- **MINOR** (0.X.0): New features that are backward-compatible (e.g., new card preset, new filter)
- **PATCH** (0.0.X): Bug fixes and maintenance (e.g., fixing a selector, improving error handling)

### Version Sources

The version must be consistent across three locations:

1. `clevers-product-carousel.php` — Plugin header `Version:`
2. `readme.txt` — `Stable tag:` field
3. Git tag — `vX.Y.Z` format

The `bin/bump-version.php` script updates all three automatically when creating a release.

## Commit Conventions

Use [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) format:

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

### Types

- `feat`: New feature (triggers MINOR bump)
- `fix`: Bug fix (triggers PATCH bump)
- `docs`: Documentation only
- `style`: Code style (formatting, missing semicolons, etc.)
- `refactor`: Code change that neither fixes a bug nor adds a feature
- `test`: Adding or updating tests
- `chore`: Build process, tooling, dependencies

## Pull Request Guidelines

- Branch from `main`. Use feature branches prefixed with `feat/`, `fix/`, or `chore/`.
- Keep PRs focused on a single change.
- Update the `readme.txt` `Changelog` section when applicable.
- Run `composer run lint` locally before opening the PR.
- Use **Squash merge** or **Rebase merge** when integrating.
- Avoid `Create a merge commit` (it pulls remote-tracking merges into the history).

## Coding Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).
- Prefix every global function, class, constant, hook, option, and post meta key with `clevers_product_carousel_` or `clv_`.
- All strings must use the `clevers-product-carousel` text domain.
- All output must be escaped (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).
- All input must be sanitized (`sanitize_text_field`, `absint`, `wp_unslash`).
- Every admin form must include a nonce (`wp_nonce_field`) and capability check (`current_user_can`).

## Reporting Issues

Use the GitHub issue tracker. Include:

- WordPress, WooCommerce, and PHP versions.
- Steps to reproduce (with a minimal scenario if possible).
- Expected vs. actual behavior.
- Relevant log entries (without secrets).
