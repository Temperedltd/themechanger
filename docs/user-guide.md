# Theme Changer User Guide

Theme Changer lets editors render selected content with a different installed theme from the active site theme.

## Permissions

Users need the `switch_themes` capability to manage Theme Changer defaults and the theme allow-list under Appearance.

Post, page, product, and public custom post type overrides require permission to edit the specific post and either `switch_themes` or the custom `use_theme_changer` capability. Users with `switch_themes` always have Theme Changer UI access.

The Role access section under Appearance > Theme Changer can add `use_theme_changer` to existing roles. Roles that already have `switch_themes` are listed as already able to use Theme Changer and do not need the custom capability.

Developers can add finer object-level control with the `tempered_themechanger_user_can_change_post_theme` filter. The filter receives the computed boolean decision, the post ID, and the user ID. It can narrow access, but it cannot grant access when the user fails the baseline post edit and `switch_themes` / `use_theme_changer` capability checks.

Term overrides still require permission to edit the specific term.

## Manage The Theme Allow-List

1. In wp-admin, go to Appearance > Theme Changer.
2. In Theme allow-list, select the themes that should be available in Theme Changer controls.
3. Select no themes to allow every installed and allowed theme.
4. Save changes.

When the allow-list has selected themes, only those themes can be selected for post overrides, post type defaults, and term overrides.

## Manage Role Access

1. In wp-admin, go to Appearance > Theme Changer.
2. In Role access, tick each role that should receive `use_theme_changer`.
3. Leave a role unticked to remove `use_theme_changer`.
4. Save changes.

Roles that already have `switch_themes` are shown for reference and cannot be changed in this section.

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

Show the theme allow-list:

```bash
wp theme-changer get-allow-list
```

Add a theme to the allow-list:

```bash
wp theme-changer add-allowed-theme child-theme
```

Remove a theme from the allow-list:

```bash
wp theme-changer remove-allowed-theme child-theme
```

The `set`, `set-default`, and allow-list commands require an installed and allowed theme stylesheet slug. An empty allow-list means all installed and allowed themes are available.

## Limitations

- Singular requests with an early post ID can fully load the selected theme, including theme PHP, `theme.json`, and block editor capabilities.
- Term archive requests currently switch stylesheet/template values and global styles, but selected-theme PHP may load too late for full term archive bootstrapping.
- Pretty permalink behavior could not be proven in the local wp-env run because generated pretty URLs returned 404 after rewrite flushing; query-string URLs were used for verification.
- Editor theme changes use save/preview refresh behavior for the first release, not instant live switching.
