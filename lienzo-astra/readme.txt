=== Lienzo Astra ===
Contributors: lienzo
Tags: elementor, woocommerce, block-patterns, custom-colors, translation-ready
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A lightweight, accessible base theme for Elementor or the block editor,
with an Astra-inspired Customizer design panel.

== Description ==

Lienzo Astra gives Elementor (or the block editor) a clean canvas: minimal
markup, a small reset, and full integration with Elementor's Theme Builder
and Site Settings — the same foundation as Lienzo, itself a rebuilt,
de-branded derivative of Hello Elementor 3.5.1.

On top of that base it adds a native "Design Options" panel in
Appearance > Customize, offering the kind of quick styling controls Astra
users are used to (primary/link/text/background colors, body & heading
fonts, base font size, container/content width, header logo position,
sticky header and footer colors) — implemented from scratch for this theme,
not by including Astra's own code.

== Features ==

* Registers every Elementor Theme Builder location (header, footer, single,
  archive, search, 404) via `elementor/theme/register_locations`.
* Adds "Lienzo Header" and "Lienzo Footer" tabs to Elementor > Site Settings
  (logo, tagline, menu, layout, width, colors, typography, copyright) with live
  preview in the editor.
* Ships a "Lienzo" Elementor widget category with 3 custom widgets:
  Breadcrumbs, Dark Mode Switcher and Reading Time — drag them into any
  Theme Builder template.
* Prints canonical links, meta descriptions, Open Graph / Twitter Card
  tags and a JSON-LD graph (Organization, WebSite with SearchAction,
  Article, BreadcrumbList) — stepping aside automatically when Yoast,
  Rank Math, SEOPress, AIOSEO or The SEO Framework is active.
* Site-wide dark mode (CSS variables + toggle, remembers the visitor's choice,
  respects `prefers-color-scheme`), a back-to-top button and a reading
  progress bar on posts.
* Breadcrumbs available as a PHP function, a `[lienzo_breadcrumbs]`
  shortcode and an Elementor widget.
* A handful of reversible performance tweaks (drops emoji scripts, RSD/
  wlwmanifest/shortlink links, dashicons on the front end) and security
  hardening (disables pingbacks, generic login error, restricts the REST API
  user list) — each behind its own filter.
* Falls back to plain, styled templates when Elementor is not active, and
  shows a one-time admin notice offering to install it.
* Respects the per-document "Hide Title" option.
* Accessible skip link, keyboard-safe mobile menu and -rtl.css
  stylesheet variants on right-to-left sites.
* Starter color palette and font-size presets in theme.json.
* WooCommerce, block editor styles, custom logo, wide alignment.
* Eight starter block patterns (hero, features, CTA, testimonials, pricing,
  FAQ, stats, landing page) under a "Lienzo Astra" category — all built with
  core blocks and the theme's global palette, usable with or without
  Elementor. The landing page pattern is offered automatically as a starting
  point when you create a new page.
* Preset color palettes in Design Options > Colors; the chosen colors are
  also synced into the block editor's global palette (theme.json), so
  blocks and patterns follow the Customizer like Kadence's global colors.
* Design Options > Blog: featured-image toggle for archives, post meta
  (date/author/categories) on single posts and excerpt length.
* Design Options > WooCommerce (when active): products per row, products
  per page and a header cart icon with live count badge; plus a dedicated
  woocommerce.php wrapper so shop pages use the theme's own markup.
* Same fonts/colors in the block editor as on the front end, preconnect
  hints for Google Fonts, and prev/next post navigation on single posts.
* Starter-site import pages are provided by the ARC Starter Templates
  plugin (or the PAge plugin as a fallback): when active, Appearance >
  Lienzo Astra lists every imported page with view/edit links; when
  inactive, the theme recommends enabling a provider.
* Full cooperation with ARC Starter Templates: imported pages render
  full-width inside the theme chrome — no boxed container, page title,
  breadcrumbs, comments or floating buttons — and the reset/theme
  stylesheets stay out of the way of the bundled Tailwind design system.

== Appearance > Lienzo ==

Switches to turn theme features off: description meta tag, skip link, theme
header & footer, page title, reset.css, theme.css, SEO meta tags, performance
tweaks, security hardening, dark mode, back-to-top button, reading progress
bar, breadcrumbs and WooCommerce extras.

== What changed compared to Hello Elementor 3.5.1 ==

Removed: the React admin app, conversion banner, remote notifications
package, Customizer/Site Settings upsells, Elementor logos/images and the
Composer autoloader (about 1.1 MB uncompressed -> about 150 KB).

Rewritten: bootstrap split into inc/ modules, readable frontend.js and
editor.js, native Settings API page, the Elementor "experiment" gate
replaced by the `lienzo_dynamic_header_footer` filter.

Kept as-is: templates, all CSS, theme.json, the Site Settings control set
and menu locations `menu-1` (Header) / `menu-2` (Footer).

== Migrating custom code from Hello Elementor ==

Functions, filters and kit control IDs were renamed. Menu assignments carry
over (same locations); Site Settings values for the old header/footer tabs
do not (control IDs changed from the `hello_header_`/`hello_footer_`
prefixes to `lienzo_header_`/`lienzo_footer_`).

Every function or filter was renamed keeping the same suffix —
`hello_elementor_page_title` -> `lienzo_page_title`.

== Renaming the theme ==

All identifiers use the `lienzo` / `Lienzo` / `LIENZO` prefix. To ship it under
your own name, rename the folder and run a case-preserving find & replace on
those three spellings (also in `languages/lienzo.pot` and `style.css`).

== Installation ==

1. Upload the `lienzo-astra` folder to `wp-content/themes/` (or zip it and
   use Appearance > Themes > Add New > Upload Theme).
2. Activate it under Appearance > Themes.
3. Optional: install Elementor to unlock the Theme Builder locations and
   the Lienzo Header/Footer Site Settings tabs — the theme works without it.
4. Open Appearance > Customize > Design Options to set colors, typography,
   layout, blog and WooCommerce options.

== Frequently Asked Questions ==

= Does it work without Elementor? =

Yes. All templates fall back to plain, styled markup, and the bundled block
patterns keep working in the block editor.

= Where are the starter templates? =

Two places: complete importable sites come from the ARC Starter Templates
plugin (or the PAge plugin — Cloned Pages screen); Appearance > Lienzo
Astra links to every imported page. To build pages yourself, see
Patterns > Lienzo Astra in the block inserter — the "Landing page" pattern
is also offered when you create a new page.

== Screenshots ==

1. screenshot.png — default look of the theme.

== Credits ==

Derived from Hello Elementor by the Elementor Team, licensed under
GPL-3.0-or-later. This derivative keeps the same license.

== Changelog ==

= 1.2.1 =
* New: theme template overrides for ARC Starter Templates — a modified
  template placed at arc-starter-templates/<demo>/<page>.html inside the
  theme wins over the plugin's bundled copy (locate_template() hierarchy),
  so templates can be edited in the theme and re-imported; Appearance >
  Lienzo Astra lists the overrides in effect and documents the workflow —
  imported pages are native Gutenberg blocks and the demo palette/Outfit
  font are synced into the block editor via the plugin's theme.json layer.
* Fix: the theme only steps aside on ARC template pages while the plugin
  is active — with the plugin off, imported pages get the theme styles
  back instead of rendering completely unstyled. Header layout: the
  custom logo is capped at 180px when no Logo width is set in Design
  Options (an uncapped logo pushed the nav onto a second line), the logo
  link renders as a block in the static header, and menu links get a
  slightly larger 500-weight style with more gap.

= 1.2.0 =
* New: ARC Starter Templates integration — imported ARC pages render
  full-width inside the theme chrome: the boxed .site-main container,
  page title, breadcrumbs, tags, post navigation, comment form, dark-mode
  toggle, back-to-top button and feature assets all step aside, and only
  the chrome stylesheet stays (the reset/theme sheets no longer fight the
  bundled Tailwind design).
* New: Appearance > Lienzo Astra lists the imported ARC pages with view/
  edit links and a button to the plugin's import wizard; the starter-sites
  admin notice prefers ARC Starter Templates when installed.
* Fix: the skip link keeps its visually-hidden styling even when the main
  theme stylesheet is not loaded (moved to the chrome stylesheet).

= 1.1.0 =
* New: 8 starter block patterns in a "Lienzo Astra" category — core
  blocks only, built on the theme palette; the landing page pattern
  appears in the start modal for new pages.
* New: preset color palettes in Design Options > Colors, synced into
  theme.json's palette so the block editor shares the global colors.
* New: Design Options > Blog (featured-image toggle, post meta, excerpt
  length) and Design Options > WooCommerce (products per row/page, header
  cart icon, woocommerce.php wrapper).
* New: prev/next post navigation, 404 search form, Google Fonts
  preconnect, editor typography/colors, theme.json spacing scale.
* New: PAge integration — starter sites listed on the settings screen,
  dismissible activation notice, page-clones-img-shield support.
* SEO upgrade: canonical URLs, OG/Twitter article & image metadata,
  JSON-LD graph, robots preview directives.

= 1.0.0 (Lienzo Astra) =
* New: unified theme built on Lienzo 1.1.0 — "Design Options" Customizer
  panel (colors, typography, layout, header & footer) inspired by Astra's
  UX, reimplemented with no Astra code. Identifiers/text domain moved to
  `lienzo-astra`.

= 1.1.0 (Lienzo) & 1.0.0 =
* Initial release rebuilt from Hello Elementor 3.5.1 + 3 Elementor
  widgets, SEO module, dark mode, back-to-top, reading progress,
  breadcrumbs, performance/security tweaks — each behind its own filter.
