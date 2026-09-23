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
assets/js/site.js                  # shared runtime script
```

## Pointing the plugin at this repo

In WordPress: **Starter Templates → Templates repository → Remote base URL** set to
the raw URL of this repo's branch, e.g.

```
https://raw.githubusercontent.com/<org>/tema/main
```

Leave it empty to use the templates bundled with the plugin (if any). On every
import the plugin pulls the repo's current state; downloaded files are cached
under `uploads/arc-st-remote/` between requests.
