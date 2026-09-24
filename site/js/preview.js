// preview.js — template previewer controller. Mounts the shell, fetches a
// template document, applies the same Img/ Css/ Js/ rewrites the plugin
// applies at preview time, and keeps internal page links inside the
// previewer. UI strings come from site/pages/preview.json (with inline
// fallbacks so the viewer never breaks).
(function () {
  "use strict";
  const el = (id) => document.getElementById(id);
  el("page").innerHTML = Tema.ui.previewShell();
  const params = new URLSearchParams(location.search);
  const embed = params.get("embed") === "1";
  const f = params.get("f") || "";
  const err = (msg) => {
    const e = el("err");
    e.classList.remove("hidden");
    e.textContent = msg;
  };

  Tema.load("preview")
    .catch(() => ({ site: {}, manifest: { templates: {} }, page: {} }))
    .then(({ manifest, page: P }) => {
      document.title = P.title || "Template preview";
      el("back").textContent = P.back || "← templates";
      el("raw").textContent = P.raw || "open raw ⤥";
      if (embed) el("bar").style.display = "none";

      if (!/^templates\/[\w.\/-]+\.html$/.test(f) || f.includes("..")) {
        err(P.invalidParam || "Missing or invalid ?f= parameter (expected templates/<demo>/<page>.html).");
        return;
      }

      const dir = f.slice(0, f.lastIndexOf("/") + 1); // e.g. templates/gymnista/
      const demoDir = dir.replace(/^templates\//, ""); // e.g. gymnista/
      const root = Tema.root + "/";
      el("file").textContent = f;
      el("raw").href = f;

      fetch(f)
        .then((r) => {
          if (!r.ok) throw new Error("HTTP " + r.status);
          return r.text();
        })
        .then((html) => {
          // Same asset rewrites the plugin applies (assets_to_plugin_urls):
          // bundled Img/, Css/, Js/ placeholders -> repo asset paths.
          let out = html
            .split('="Img/').join('="assets/img/')
            .split('="Css/tailwind.css').join('="assets/css/tailwind.css')
            .split('="Js/site.js').join('="assets/js/site.js');
          out = out.replace(/<head(\s[^>]*)?>/i, (m) => m + '<base href="' + root + '">');
          // Internal href="<x>.html" links stay inside the previewer. Templates
          // link either by manifest slug (gymnista-all-collections.html) or by
          // same-dir filename (services.html) — resolve like the plugin does.
          const tpl = manifest.templates || {};
          const files = new Set(Object.values(tpl).map((t) => t.file));
          const q = embed ? "&embed=1" : "";
          out = out.replace(/href="([\w-]+)\.html"/g, (m, slug) => {
            let target;
            if (files.has(demoDir + slug + ".html")) target = dir + slug + ".html";
            else if (tpl[slug]) target = "templates/" + tpl[slug].file;
            else target = dir + slug + ".html";
            return 'href="preview.html?f=' + encodeURIComponent(target) + q + '"';
          });
          el("frame").srcdoc = out;
        })
        .catch((e) =>
          err(Tema.fill(P.loadError || "Could not load {file} — {error}", { file: f, error: e.message }))
        );
    });
})();
