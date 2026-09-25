// Shared runtime for the tema Pages site — every page renders its chrome from
// site/site.json, its content from site/pages/<page>.json and the demo registry
// from templates/manifest.json, so nothing is hardcoded. Exposes window.Tema;
// page controllers live alongside this file (page/browse/preview.js).
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
    // Same links, stacked, for the mobile disclosure panel.
    const mobileLinks = site.nav
      .map((n) => {
        const on = n.page === active;
        return (
          '<a href="' +
          esc(n.href) +
          '" class="block px-6 py-3 text-sm no-underline border-t border-line/60 transition ' +
          (on ? "font-semibold text-blue bg-blue/5" : "font-medium text-ink hover:text-blue hover:bg-soft") +
          '">' +
          esc(n.label) +
          "</a>"
        );
      })
      .join("");
    return (
      '<nav class="max-w-6xl mx-auto px-6 py-3.5 flex items-center gap-3" aria-label="Main">' +
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
      '" target="_blank" rel="noopener" class="ml-6 max-sm:ml-0 text-sm font-semibold bg-navy hover:bg-blue text-white rounded-lg px-4 py-2 no-underline transition">' +
      esc(site.githubButtonLabel) +
      "</a>" +
      '<button id="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-mobile" aria-label="Menu"' +
      ' class="sm:hidden -mr-2 p-2 rounded-lg text-navy hover:bg-soft transition cursor-pointer border-0 bg-transparent">' +
      '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">' +
      '<path class="nav-ic-open" d="M4 7h16M4 12h16M4 17h16"/>' +
      '<path class="nav-ic-close hidden" d="M6 6l12 12M18 6L6 18"/>' +
      "</svg></button></nav>" +
      '<div id="nav-mobile" class="sm:hidden hidden border-t border-line bg-white" hidden>' +
      mobileLinks +
      "</div>"
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
    bindMobileNav(h);
  }

  // Hamburger disclosure for the mobile nav panel. Closes on link click,
  // outside tap and Escape; aria-expanded tracks visibility.
  function bindMobileNav(header) {
    const btn = header.querySelector("#nav-toggle");
    const panel = header.querySelector("#nav-mobile");
    if (!btn || !panel) return;

    const setOpen = (open) => {
      btn.setAttribute("aria-expanded", open ? "true" : "false");
      panel.classList.toggle("hidden", !open);
      panel.hidden = !open;
      const [icOpen, icClose] = [
        btn.querySelector(".nav-ic-open"),
        btn.querySelector(".nav-ic-close"),
      ];
      if (icOpen && icClose) {
        icOpen.classList.toggle("hidden", open);
        icClose.classList.toggle("hidden", !open);
      }
    };

    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      setOpen(panel.hidden);
    });
    panel.addEventListener("click", (e) => {
      if (e.target.closest("a")) setOpen(false);
    });
    document.addEventListener("click", (e) => {
      if (!panel.hidden && !header.contains(e.target)) setOpen(false);
    });
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && !panel.hidden) {
        setOpen(false);
        btn.focus();
      }
    });
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
