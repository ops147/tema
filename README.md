# tema

Remote payload for the **ARC Starter Templates** WordPress plugin — demo manifest,
template documents and images, downloaded on demand at import time so the plugin
itself stays lightweight.

## Layout

```
templates/manifest.json            # demo + page registry
templates/<demo>/<page>.html       # one HTML document per page
assets/img/<file>                  # images referenced by the templates and manifest
assets/css/tailwind.css            # shared runtime stylesheet
assets/js/site.js                  # shared runtime script (template pages)
site.json                          # all site content/config for the Pages mirror
assets/js/app.js                   # shared renderer (header, footer, data loading)
assets/js/{index,browse,preview}.js # per-page controllers — all content is data-driven
assets/js/product.js               # generic product-page renderer (plugin.html, theme.html)
plugin.html / theme.html           # pages for the ARC Starter Templates plugin & Lienzo Astra theme
```

## Pointing the plugin at this repo

In WordPress: **Starter Templates → Templates repository → Remote base URL** set to
the raw URL of this repo's branch, e.g.

```
https://raw.githubusercontent.com/ops147/tema/main
```

Leave it empty to use the templates bundled with the plugin (if any). On every
import the plugin pulls the repo's current state; downloaded files are cached
under `uploads/arc-st-remote/` between requests.

## GitHub Pages mirror

The repo is published through GitHub Pages at

```
https://ops147.github.io/tema
```

It serves the same layout, so it also works as a **Remote base URL**, and adds
a browsable index (`index.html`) plus a previewer (`preview.html?f=templates/<demo>/<page>.html`)
that applies the same `Img/`, `Css/`, `Js/` rewrites the plugin applies at
preview time — every demo page is viewable in a browser without importing.

## Private repositories

If this repo is private, raw.githubusercontent.com answers 404 without auth —
either make the repo public, or paste a GitHub personal access token with
`repo` scope into the plugin's **Access token** field (sent as a Bearer
header; it is never echoed back in the UI).
