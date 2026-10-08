=== ARC Starter Templates ===

Contributors: ashrivercollective
Tags: starter templates, elementor, templates, tailwind, page templates
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.9.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Imports the Ash River Collective marketing site as Elementor-editable starter
templates, block patterns, and classic pages.

== Description ==

ARC Starter Templates ships a library of starter sites built on the
Ash River Collective model — pick a demo card and a step-by-step wizard
imports everything.

Every demo is the same seven-page talent & systems site in its own visual
skin: Home, About, Services, Virtual Services, Playbooks, Foundation and
Partners. Pages are created as published WP pages with cross-links
rewritten and their images uploaded to the Media Library. With Elementor
active each page is a native container/widget layout on the Canvas
template — every heading, paragraph, image and button is editable; without
Elementor pages fall back to editable HTML inside your active theme.

== Features ==

* Starter Templates screen under "ARC Templates": demo card with modal
  preview, search, categories and last-import report.
* Step-by-step import wizard (Media, Pages, Setup) with progress, live
  log, per-page selection, dry-run mode and optional reset.
* Remote templates — host the manifest, template documents and images in a
  repository (e.g. GitHub) and keep the plugin lightweight. Set the remote
  base URL under ARC Templates → Templates repository and every import
  downloads the repo's current state on demand; downloaded files are
  cached under uploads/arc-st-remote/. Configurable via setting, the
  ARC_ST_REMOTE_BASE constant or the arc_st_remote_base filter.
* Resilient import — run lock, server-side progress with Resume/Retry,
  and per-file media error reporting.
* Elementor integration — flexbox containers, kit brand colors + Outfit
  typography as globals, CSS cache rebuild, native button widgets.
* Site setup — Home as front page, the demo's Main + Footer menus assigned
  to the theme's header/footer locations (existing menus are kept, just
  unassigned), custom logo and site icon. Imported pages are never
  auto-added to pre-existing menus.
* Working contact form — configurable recipient, honeypot, optional
  consent checkbox, full server-side sanitization.
* Block patterns — the same pages registered under the "ARC Starter
  Templates" pattern category.
* WP-CLI commands — `wp arc-st import`, `wp arc-st reset`,
  `wp arc-st status` for headless provisioning.
* Environment checks — warns about missing PHP extensions or a
  non-writable uploads directory instead of failing silently.

== Installation ==

1. Copy the `arc-starter-templates` folder to `wp-content/plugins/`.
2. Activate ARC Starter Templates in Plugins.
3. Open ARC Templates in the admin menu, click the template card and run the
   import wizard.

== Frequently Asked Questions ==

= Do I need Elementor? =

No. With Elementor active the pages are imported as editable Elementor
layouts; without it they are imported as native Gutenberg blocks inside
your active theme — every heading, paragraph, image and button is editable
in the block editor.

= Will it change my existing content? =

No. The wizard only creates new pages, a new "ARC Main" menu and uploads its
own images. It never edits or deletes content it did not create, unless you
check "Delete previously imported ARC pages first", which removes only pages
this plugin created.

= Can I re-import a template? =

Yes. Re-importing updates the same pages (same IDs and permalinks), so edits
made to those pages are overwritten.

= What does the wizard configure? =

It uploads the bundled images to the Media Library, creates the seven pages,
optionally sets the Home page as your front page and fills empty registered
menu locations with the "ARC Main" and "ARC Footer" menus. With Elementor
active it also enables flexbox containers and adds the brand colors to the
kit's custom colors.

= Where do contact form messages go? =

To the recipient configured under ARC Templates → Contact form settings
(defaults to the site admin email; filterable via arc_st_contact_recipient).
The form is spam-protected by a honeypot — no CAPTCHA service is required.

= Can I import with WP-CLI? =

Yes: `wp arc-st import` (flags: --reset, --pages=a,b, --no-front,
--dry-run), plus `wp arc-st reset` and `wp arc-st status`.

= What happens if the import is interrupted? =

The wizard stores progress server-side. Reopen the wizard and click
Resume Import to continue from where it stopped, or Start for a fresh run.
An import lock also prevents two admins from running imports at once.

== Screenshots ==

1. Starter Templates screen with the Ash River Collective demo card.
2. Import wizard showing the step list, progress bar and live log.
3. An imported page open in the Elementor editor.

== Changelog ==

= 1.9.0 =
* New starter-site structures across the bundled demos: collage heroes,
  photo-strip page heroes, bento card grids, numbered media rows,
  steppers, timelines, a services accordion, navy stat bands and
  image-card CTAs — same ARC copy and palette, Elementor-editable markup.
* Automatic re-configuration over a previous install: re-importing a
  different demo now replaces the plugin-owned logo/site icon and syncs
  the ARC Elementor kit globals to the new palette, and removing imported
  pages no longer leaves the front page pointing at a deleted post.
  Imported demo cards gain an "Apply config" action that re-runs the
  automatic setup (front page, menus, logo, editor settings) without
  re-importing pages.

= 1.8.0 =
* Chat bot AI answers: an optional OpenAI-compatible provider (endpoint,
  key, model and business context on the Chat Bot screen) replies in
  natural, human language about the business. A circuit breaker keeps the
  bot answering with the default keyword answers when the provider fails,
  times out or runs out of credits.

= 1.7.0 =
* Unified demo structure: every starter site now ships the same seven
  pages — Home, About, Services, Virtual Services, Playbooks, Foundation
  and Partners — each in its own design skin (colors, fonts, imagery).
* New Playbooks page presents the intranet module: per-company SOP library
  with tag filters, a processes view, permissions and branding.
* The Partners page now hosts the working contact form; the standalone
  Contact and Finance Talent pages were folded into Partners and Services.
* Canonical template slugs (<demo>-<page>) and link wiring across all
  147 bundled documents; regenerate with npm run build:templates.

= 1.6.2 =
* Remote template source: a configured repository URL makes the plugin pull
  templates/manifest.json, the per-page HTML documents and the assets/img
  images from the repo on demand into a disk cache (uploads/arc-st-remote/),
  so the plugin can ship without the bundled payload. Fresh imports flush
  the cache; the bundled copy remains the fallback when the remote is
  unreachable.
* Menu fix: imported pages are no longer auto-appended to pre-existing
  menus (WordPress "auto-add" was merging the old menu with the demo's).
  The demo's Main menu now always takes the header location, the Footer
  menu can reuse an occupied footer location, and top-level items pointing
  at imported pages are pruned from foreign menus on re-import.

= 1.6.1 =
* Theme template overrides: the active theme can replace any bundled
  template document via arc-starter-templates/<file> inside the theme
  (locate_template() hierarchy, child theme first) — edit the HTML in the
  theme and re-import. Theme-bundled images under
  arc-starter-templates/assets/img|img/ are sideloaded the same way.
* Editor sync: the imported demo's kit colors and fonts are merged into the
  theme.json data layers (wp_theme_json_data_theme/_user filters), so the
  ARC palette and Outfit appear as real presets in every block control.
* Patterns are now registered as native editable blocks (same DOM→blocks
  conversion as the importer) instead of a single raw-HTML block.
* Block-editor canvas fix: ARC pages render full-width inside the iframed
  editor (assets/css/editor.css, enqueued through enqueue_block_assets).

= 1.4.1 =
* Cross-page links resolve in every import path; classic-mode imports store
  native editable Gutenberg blocks; preview navigation between template pages.

= 1.4.0 =
* One-click import from the library card; dependency-free lint/zip tooling.

= 1.3.x =
* Resilient imports (run lock, Resume/Retry), Astra footer menus, Elementor
  button widgets, site identity, WP-CLI, contact form submissions.

= 1.0.0 – 1.2.0 =
* First releases: page importer, block patterns, Media Library uploads,
  Starter Templates screen, footer menus, Elementor kit colors.

== Notes ==

* Re-importing a template overwrites that page's content and Elementor data.
* The Elementor layout uses flexbox containers — requires Elementor 3.16+
  (containers feature enabled).
* Contact submissions go to the configured recipient (filterable via
  arc_st_contact_recipient). Honeypot spam protection is built in; add a
  CAPTCHA plugin if the site needs stronger filtering.
* Updates: the plugin is not hosted on WordPress.org — updates ship as a
  ZIP (npm run zip) and are installed via Plugins → Add New → Upload.
  Dev tooling is dependency-free: npm run lint (PHP syntax check),
  npm run pot (regenerates languages/arc-starter-templates.pot) and
  npm run zip -- --slim (0.1 MB package without templates/images for
  installs that pull the payload from a configured remote repository).
