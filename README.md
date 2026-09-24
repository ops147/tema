# tema

Remote payload for the **ARC Starter Templates** WordPress plugin — demo manifest,
template documents and images, downloaded on demand at import time so the plugin
itself stays lightweight.

## Layout

The repo has three layers — the **remote payload** the plugin downloads, the
**Pages mirror** (this browsable site), and vendored copies of the other two
pieces of the system:

```
templates/manifest.json            # demo + page registry            ┐
templates/<demo>/<page>.html       # one HTML document per page      │ remote
assets/img/<file>                  # images referenced by templates   │ payload
assets/css/tailwind.css            # shared runtime stylesheet        │ (contract:
assets/js/site.js                  # shared runtime script            │ do not move)

index.html · browse.html · preview.html · plugin.html · theme.html   ┐ Pages
site/site.json                     # shared chrome: brand, nav, footer│ mirror
site/pages/<page>.json             # per-page content module          │
site/js/app.js                     # shared runtime (header, footer,  │
                                   #   data loading: Tema.load())    │
site/js/{index,browse,preview,product}.js  # page controllers        ┘

wordpress/arc-starter-templates/   # the plugin (source copy)
wordpress/lienzo-astra/            # the theme (source copy)
```

**Contract with the plugin** — the remote base URL must keep serving
`templates/manifest.json`, `templates/<demo>/<page>.html` and
`assets/img/<file>`; the previewer additionally relies on
`assets/css/tailwind.css` and `assets/js/site.js`. Those paths are frozen —
everything under `site/` is internal to the mirror and can be reorganized
freely.

**Adding a Pages page** — drop an `<name>.html` shell at root, a matching
`site/pages/<name>.json` content module and (if the generic product renderer
doesn't fit) a `site/js/<name>.js` controller; then add a `nav` entry in
`site/site.json`. `plugin.html`/`theme.html` show the minimal shell: chrome
mounts + `site/js/product.js` with `data-page="<name>"`.

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
