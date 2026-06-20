# Theme Changer User Guide

Theme Changer lets editors render selected content with a different installed theme from the active site theme.

## Permissions

Users need the `switch_themes` capability to manage Theme Changer defaults under Appearance. Post and term overrides still require permission to edit the specific post or term.

## Set A Default Theme By Post Type

1. In wp-admin, go to Appearance > Theme Changer.
2. Choose a theme for each public post type that should use a different default theme.
3. Leave a post type set to Active site theme to inherit the site's active theme.
4. Save changes.

Individual post, page, product, or custom post type selections override these defaults.

## Set A Theme For A Post Or Page

1. Edit the post, page, product, or public custom post type item.
2. Open the Theme Changer panel or meta box.
3. Choose an installed theme, or choose Active site theme to inherit the post type default or site theme.
4. Save or preview the content.

## Set A Theme For A Term Archive

1. Edit a public taxonomy term, such as a category, tag, product category, or product tag.
2. Choose a theme in the Theme Changer field.
3. Save the term.

Term archive switching has the runtime limitation described below.

## WP-CLI

Theme Changer provides a `theme-changer` WP-CLI namespace for management tasks. Runtime theme switching remains disabled during WP-CLI execution.

Set an individual content override:

```bash
wp theme-changer set 123 child-theme
```

Get an individual content override:

```bash
wp theme-changer get 123
```

Clear an individual content override:

```bash
wp theme-changer clear 123
```

Set a default theme for a post type:

```bash
wp theme-changer set-default post child-theme
```

Get a default theme for a post type:

```bash
wp theme-changer get-default post
```

Clear a default theme for a post type:

```bash
wp theme-changer clear-default post
```

The `set` and `set-default` commands require an installed and allowed theme stylesheet slug.

## Limitations

- Singular requests with an early post ID can fully load the selected theme, including theme PHP, `theme.json`, and block editor capabilities.
- Term archive requests currently switch stylesheet/template values and global styles, but selected-theme PHP may load too late for full term archive bootstrapping.
- Pretty permalink behavior could not be proven in the local wp-env run because generated pretty URLs returned 404 after rewrite flushing; query-string URLs were used for verification.
- Editor theme changes use save/preview refresh behavior for the first release, not instant live switching.
