# Theme Changer

Version: 0.1.0

Theme Changer is a WordPress utility plugin for changing the theme for selected content with a different installed theme then from the active site theme.

This means a site can keep one active theme while using another (or go crazy and have many) installed theme(s) for selected posts, pages, products, custom post types, or taxonomy archives.

## Requirements

- WordPress 7.0 or later.
- PHP 8.2 or later.
- At least one additional installed and allowed theme.

## Why

WordPress by default has a single active theme at one time, if you change the theme it occurs sitewide. Theme Changer was born after a need to perform a slow migration from a classic theme to a FSE based solution with parts of the site on the old theme and parts on the new. However it can work for a range of scenarios, separating different brands, campaigns or teams.

Editors can choose a theme for individual supported content as long as they also have the ability to select a theme, and administrators can set defaults by post type.

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
2. Activate Theme Changer in WordPress.
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
