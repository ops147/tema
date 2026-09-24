ARC Starter Templates — theme overrides
=======================================

Files placed here replace the plugin's bundled template documents, using the
same override convention as WooCommerce templates:

    lienzo-astra/arc-starter-templates/<demo>/<page>.html

    e.g. arc-starter-templates/arc-site/index.html overrides the bundled
         templates/arc-site/index.html (the manifest "file" path relative to
         the plugin's templates/ directory).

The child theme is checked first, then the parent theme (the plugin resolves
overrides through locate_template()).

An override applies everywhere the template is used: the admin preview, the
import wizard, WP-CLI imports and the registered block patterns. Edit the
file, then re-import the demo to update already-imported pages.

Images referenced as Img/<file> inside an override are uploaded to the Media
Library from this folder too — drop them in arc-starter-templates/assets/img/
(or arc-starter-templates/img/).

See also: Appearance > Lienzo Astra > ARC Starter Templates, which lists the
overrides currently in effect.
