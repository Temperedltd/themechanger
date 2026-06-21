# Tempered Themechanger

Version: 0.1.0

Tempered Themechanger is a WordPress utility plugin for rendering selected content with a different installed theme from the active site theme.

This means a site can keep one active theme while using another installed theme for selected posts, pages, products, custom post types, or taxonomy archives.

## Requirements

- WordPress 7.0 or later.
- PHP 8.2 or later.
- At least one additional installed and allowed theme.
- The `switch_themes` capability to manage default theme selections.

## Why

WordPress normally treats the active theme as a site-wide decision. That is usually the right model, but it is limiting when a site needs a specific section, campaign, product area, or archive to render with a different theme without moving it to a separate WordPress install.

Tempered Themechanger keeps that decision inside WordPress. Editors can choose a theme for individual supported content, and administrators can set sensible defaults by post type.

The plugin is intentionally conservative. It only offers installed themes WordPress reports as available for the current site, and it falls back safely when a stored theme is missing, deleted, or no longer allowed.

## What It Does

The plugin adds theme selection controls for supported WordPress content and resolves the selected theme during eligible requests.

- Supports per-post, page, public custom post type, and WooCommerce product theme overrides.
- Supports individual public taxonomy term archive overrides within the documented runtime limits.
- Adds default theme selection by public post type under Appearance > Theme Changer.
- Adds controls for the classic editor and block editor.
- Provides WP-CLI commands for managing individual content overrides and post type defaults.
- Uses WordPress' allowed-theme APIs so theme lists remain multisite-aware.
- Disables runtime theme switching during WP-CLI execution so command-line jobs do not load alternate themes.

### Runtime Limits

Singular requests with an early post ID can fully load the selected theme, including theme PHP, `theme.json`, and block editor capabilities.

Term archive requests currently switch stylesheet and template values, plus global styles, but selected-theme PHP may load too late for full term archive bootstrapping.

Pretty permalink behaviour could not be proven in the local wp-env run because generated pretty URLs returned 404 after rewrite flushing; query-string URLs were used for verification.

Editor theme changes use save and preview refresh behaviour for the first release, not instant live switching.

## Installation

1. Copy the plugin directory to `wp-content/plugins/tempered-themechanger`.
2. Activate Tempered Themechanger in WordPress.
3. Go to Appearance > Theme Changer to configure default themes by post type.
4. Edit supported posts, pages, products, custom post type items, or taxonomy terms to add individual theme overrides.

See `docs/user-guide.md` for wp-admin usage, WP-CLI commands, permissions, and limitations.

## Development

Install development dependencies with Composer:

```bash
composer install
```

Install optional local WordPress tooling:

```bash
npm install
```

Run the test suite:

```bash
composer test
```

Run WordPress Coding Standards checks:

```bash
composer lint
```

Start a local WordPress environment:

```bash
npm run wp-env:start
```

The local wp-env setup mounts fixture themes used for manual compatibility checks.

## Releases

Releases are driven by the plugin header version in `tempered-themechanger.php`. When preparing a release:

1. Update the `Version:` header.
2. Add a matching release section to `CHANGELOG.md`.
3. Push the version change to `main`.

The GitHub Actions release workflow builds a production ZIP artefact containing the plugin file, `includes/`, `assets/`, `languages/`, `README.md`, `SECURITY.md`, `CHANGELOG.md`, and `uninstall.php`.

## Security

Please report suspected vulnerabilities privately using the process in `SECURITY.md`.
