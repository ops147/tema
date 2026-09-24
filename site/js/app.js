// Shared runtime for the tema Pages site — every page renders its chrome from
// site/site.json, its content from site/pages/<page>.json and the demo registry
// from templates/manifest.json, so nothing is hardcoded. Exposes window.Tema;
// page controllers live alongside this file (index/browse/preview/product.js).
window.Tema = (function () {
  "use strict";

  const esc = (s) =>
    String(s).replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]));

  // Absolute URL of the repo root (…/tema) — used for the Pages mirror URL
  // and as <base> when previewing template documents.
  const root = new URL(".", location.href).href.replace(/\/$/, "");

  const j = (u) =>
    fetch(u).then((r) => {
      if (!r.ok) throw new Error(u + " → HTTP " + r.status);
      return r.json();
    });

  // load() → { site, manifest }; load("index") → { site, manifest, page }
  // where `page` is that page's content module (site/pages/<page>.json).
  let cache = null;
  function load(page) {
    if (!cache) {
      cache = Promise.all([j("site/site.json"), j("templates/manifest.json")]).then(
        ([site, manifest]) => ({ site, manifest })
      );
    }
    if (!page) return cache;
    return cache.then((base) =>
      j("site/pages/" + encodeURIComponent(page) + ".json").then((p) =>
        Object.assign({ page: p }, base)
      )
    );
  }

  // "{placeholder}" interpolation against a vars map (unknown keys kept as-is).
  const fill = (str, vars) =>
    String(str == null ? "" : str).replace(/\{(\w+)\}/g, (m, k) => (vars && vars[k] != null ? vars[k] : m));

  // Values available for interpolation anywhere in site.json.
  function vars(site, extra) {
    return Object.assign(
      {
        brand: site.brand.name,
        letter: site.brand.letter,
        github: site.repo.github,
        raw: site.repo.raw,
        root,
        pluginName: site.repo.pluginName,
        pluginSite: site.repo.pluginSite,
      },
      extra
    );
  }

  /* ---------- shared chrome (header + footer) ---------- */

  function headerHTML(site, active) {
    const links = site.nav
      .map((n) => {
        const on = n.page === active;
        return (
          '<a href="' +
          esc(n.href) +
          '" class="text-sm no-underline transition ' +
          (on ? "font-semibold text-blue" : "font-medium text-ink hover:text-blue") +
          '">' +
          esc(n.label) +
          "</a>"
        );
      })
      .join("");
    return (
      '<nav class="max-w-6xl mx-auto px-6 py-3.5 flex items-center gap-3">' +
      '<a href="index.html" class="flex items-center gap-2.5 no-underline">' +
      '<span class="w-8 h-8 rounded-lg bg-blue grid place-items-center font-extrabold text-sm text-white">' +
      esc(site.brand.letter) +
      "</span>" +
      '<span class="font-bold tracking-tight text-navy">' +
      esc(site.brand.name) +
      "</span></a>" +
      '<span class="flex-1"></span>' +
      '<div class="hidden sm:flex items-center gap-6">' +
      links +
      "</div>" +
      '<a href="' +
      esc(site.repo.github) +
      '" target="_blank" rel="noopener" class="ml-6 text-sm font-semibold bg-navy hover:bg-blue text-white rounded-lg px-4 py-2 no-underline transition">' +
      esc(site.githubButtonLabel) +
      "</a></nav>"
    );
  }

  function footerHTML(site) {
    const v = vars(site);
    const links = site.footer.links
      .map(
        (l) =>
          '<a class="text-blue hover:underline" href="' +
          esc(fill(l.href, v)) +
          '"' +
          (l.external ? ' target="_blank" rel="noopener"' : "") +
          ">" +
          esc(l.label) +
          "</a>"
      )
      .join(" · ");
    return fill(site.footer.text, v) + " · " + links;
  }

  // Inject header/footer — the mounts are auto-created around <main> when a
  // page doesn't declare them; chromeless pages (preview) never call this.
  function mountChrome(site, active) {
    let h = document.getElementById("site-header");
    if (!h) {
      h = document.createElement("header");
      h.id = "site-header";
      h.className = "sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-line";
      document.body.prepend(h);
    }
    let f = document.getElementById("site-footer");
    if (!f) {
      f = document.createElement("footer");
      f.id = "site-footer";
      f.className = "border-t border-line px-6 py-6 text-center text-xs text-ink/70";
      document.body.appendChild(f);
    }
    h.innerHTML = headerHTML(site, active);
    f.innerHTML = footerHTML(site);
  }

  // Clipboard copy buttons — elements carrying a data-copy attribute.
  function bindCopy(scope, labels) {
    (scope || document).querySelectorAll("[data-copy]").forEach((btn) => {
      btn.addEventListener("click", async () => {
        try {
          await navigator.clipboard.writeText(btn.dataset.copy);
        } catch (e) {}
        btn.textContent = labels.copied;
        btn.classList.add("!text-blue", "!border-blue");
        setTimeout(() => {
          btn.textContent = labels.copy;
          btn.classList.remove("!text-blue", "!border-blue");
        }, 1600);
      });
    });
  }

  return { esc, fill, root, load, vars, mountChrome, bindCopy };
})();
