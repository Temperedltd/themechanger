# Theme changer

Theme changer is a WordPress utility plugin for rendering selected content with a different installed theme from the active site theme.

## Current Scope

The plugin supports:

- Per-post, page, public custom post type, and WooCommerce product theme overrides.
- Individual public taxonomy term archive overrides within the documented runtime limits below.
- Default theme selection by public post type under Appearance > Theme Changer.
- Classic editor and block editor controls for supported post types.
- WP-CLI commands for managing individual content overrides and post type defaults.
- Safe fallback when a stored theme is missing, deleted, or not allowed on the current site.
- Multisite-aware theme listing through WordPress' allowed-theme APIs.

## Structure

- `tempered-themechanger.php` - WordPress plugin entrypoint.
- `includes/` - PHP implementation files.
- `assets/` - Admin JavaScript assets.
- `languages/` - Translation files, when needed.
- `tests/` - PHPUnit test bootstrap and tests.
- `docs/user-guide.md` - User documentation for administrators and CLI users.

## Development

Install PHP tooling:

```bash
composer install
```

Install optional local WordPress tooling:

```bash
npm install
```

Run lint checks:

```bash
composer lint
```

Run tests:

```bash
composer test
```

Start a local WordPress environment:

```bash
npm run wp-env:start
```

The local wp-env setup mounts fixture themes used for manual compatibility checks.

## Known Limitations

- Singular requests with an early post ID can fully load the selected theme, including theme PHP, `theme.json`, and block editor capabilities.
- Term archive requests currently switch stylesheet/template values and global styles, but selected-theme PHP may load too late for full term archive bootstrapping.
- Pretty permalink behavior could not be proven in the local wp-env run because generated pretty URLs returned 404 after rewrite flushing; query-string URLs were used for verification.
- Editor theme changes use save/preview refresh behavior for the first release, not instant live switching.
- Theme switching is intentionally disabled during WP-CLI execution so command-line jobs do not load alternate themes.

## User Documentation

See `docs/user-guide.md` for wp-admin usage, WP-CLI commands, permissions, and limitations.
