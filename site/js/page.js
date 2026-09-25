// page.js — generic layout engine for the content pages (index, plugin,
// theme). A page is just site/pages/<name>.json: a "layout" array of typed
// blocks that this file composes from Tema.ui atoms and mounts into
// <main id="page">. Attach with <script data-page="<name>">.
//
// Block types: hero (badge/title/lead/ctas/stats/marquee), intro, demos
// (manifest grid), features (icon cards), cards (numbered + optional link),
// steps, code (terminal), checks (checklist), setup (urls + copy + steps),
// cta (navy band). Common options: "soft": true → banded background,
// "id": "…" → anchor target with scroll offset.
(function () {
  "use strict";
  const el = (id) => document.getElementById(id);
  const page = (document.currentScript.dataset || {}).page || "index";
  const main = el("page");
  main.innerHTML = '<div class="py-12 text-center text-ink/60">…</div>';

  const GRID = {
    2: "sm:grid-cols-2",
    3: "sm:grid-cols-2 lg:grid-cols-3",
    4: "sm:grid-cols-2 lg:grid-cols-4",
  };

  Tema.load(page)
    .then(({ site, manifest, page: P }) => {
      const esc = Tema.esc,
        fill = Tema.fill,
        ui = Tema.ui;
      const v = Tema.vars(site);

      document.title = P.title;
      Tema.mountChrome(site, page);

      // Default padded wrapper for content sections.
      const wrap = (inner, s) => {
        const sec = document.createElement("section");
        if (s.soft) sec.className = "bg-soft border-y border-line";
        if (s.id) {
          sec.id = s.id;
          sec.classList.add("scroll-mt-20");
        }
        sec.innerHTML = '<div class="max-w-6xl mx-auto px-6 py-16">' + inner + "</div>";
        return sec;
      };

      const marqueeItems = (src) =>
        src === "demos"
          ? Object.values(manifest.demos || {}).map((d) => d.title || "")
          : Array.isArray(src)
            ? src
            : [];

      const BLOCKS = {
        /* hero — bg-soft band; optional stats row and marquee strip inside */
        hero(s) {
          const sec = document.createElement("section");
          sec.className = "bg-soft border-b border-line";
          sec.innerHTML =
            '<div class="max-w-6xl mx-auto px-6 pt-14 pb-12 sm:pt-20 sm:pb-14 text-center">' +
            '<span class="inline-block text-[11px] font-bold uppercase tracking-widest text-blue bg-blue/10 rounded-full px-3.5 py-1.5 mb-5">' +
            esc(s.badge) +
            "</span>" +
            '<h1 class="text-4xl sm:text-5xl lg:text-[3.4rem] font-extrabold text-navy tracking-tight leading-[1.08]">' +
            s.title +
            "</h1>" +
            '<p class="mt-5 text-base sm:text-lg text-ink/90 max-w-2xl mx-auto">' +
            s.lead +
            "</p>" +
            '<div class="mt-8 flex flex-wrap justify-center gap-3">' +
            ui.ctas(s.ctas, v) +
            "</div>" +
            ui.stats(s.stats) +
            "</div>" +
            ui.marquee(marqueeItems(s.marquee));
          return sec;
        },

        /* intro — centered paragraph strip */
        intro(s) {
          const sec = document.createElement("section");
          sec.innerHTML =
            '<div class="max-w-4xl mx-auto px-6 py-14 text-center">' +
            '<p class="text-base sm:text-lg text-ink/90 leading-relaxed">' +
            s.text +
            "</p></div>";
          return sec;
        },

        /* demos — featured grid rendered from templates/manifest.json */
        demos(s) {
          const DEMOS = manifest.demos || {};
          const ids = Object.keys(DEMOS);
          const cards = ids
            .slice(0, s.featuredCount || 6)
            .map(
              (id) =>
                '<a href="browse.html?d=' +
                encodeURIComponent(id) +
                '" class="group bg-white border border-line rounded-2xl overflow-hidden flex flex-col transition duration-300 hover:-translate-y-1 hover:border-blue hover:shadow-lg no-underline">' +
                ui.demoCard(id, DEMOS[id], { badge: s.badge, idealFor: s.idealFor }) +
                "</a>"
            )
            .join("");
          return wrap(
            ui.sectionHead(fill(s.title, { count: ids.length }), s.lead) +
              '<div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">' +
              cards +
              "</div>" +
              '<div class="mt-10 text-center"><a href="' +
              esc(fill(s.moreHref, v)) +
              '" class="btn-ghost">' +
              esc(s.moreLabel) +
              "</a></div>",
            s
          );
        },

        /* features — icon card grid */
        features(s) {
          return wrap(
            ui.sectionHead(s.title, s.lead) +
              '<div class="mt-10 grid ' +
              (GRID[s.cols || 4] || GRID[4]) +
              ' gap-5">' +
              (s.items || []).map(ui.iconCard).join("") +
              "</div>" +
              ui.tail(s, v),
            s
          );
        },

        /* cards — numbered cards with tag, optional link + note (ecosystem) */
        cards(s) {
          return wrap(
            ui.sectionHead(s.title, s.lead) +
              '<div class="mt-10 grid sm:grid-cols-3 gap-5">' +
              (s.items || []).map((i) => ui.tagCard(i, v)).join("") +
              "</div>" +
              (s.note ? '<p class="mt-8 text-xs text-ink/60 text-center">' + esc(s.note) + "</p>" : "") +
              ui.tail(s, v),
            s
          );
        },

        /* steps — numbered card row */
        steps(s) {
          return wrap(
            ui.sectionHead(s.title, s.lead) +
              '<ol class="mt-10 max-w-4xl mx-auto grid sm:grid-cols-' +
              Math.min((s.items || []).length, 3) +
              ' gap-5 text-sm">' +
              (s.items || []).map(ui.stepCard).join("") +
              "</ol>" +
              ui.tail(s, v),
            s
          );
        },

        /* code — terminal window + optional note */
        code(s) {
          return wrap(
            ui.sectionHead(s.title, s.lead) +
              ui.terminal(s.lines) +
              (s.note ? '<p class="mt-4 text-xs text-ink/60 text-center">' + esc(s.note) + "</p>" : "") +
              ui.tail(s, v),
            s
          );
        },

        /* checks — checklist card grid */
        checks(s) {
          return wrap(
            ui.sectionHead(s.title, s.lead) +
              '<div class="mt-10 max-w-4xl mx-auto grid sm:grid-cols-2 gap-5">' +
              (s.items || []).map(ui.checkCard).join("") +
              "</div>" +
              ui.tail(s, v),
            s
          );
        },

        /* setup — copyable URL rows + numbered steps */
        setup(s) {
          const sec = wrap(
            ui.sectionHead(s.title, s.lead) +
              '<div class="mt-8 max-w-3xl mx-auto bg-white border border-line rounded-2xl p-6 shadow-sm">' +
              (s.urls || []).map((u) => ui.urlRow(u, v, s.copyLabel)).join("") +
              "</div>" +
              '<ol class="mt-8 max-w-3xl mx-auto grid sm:grid-cols-3 gap-5 text-sm">' +
              (s.steps || []).map(ui.stepCard).join("") +
              "</ol>",
            s
          );
          Tema.bindCopy(sec, { copy: s.copyLabel, copied: s.copiedLabel });
          return sec;
        },

        /* cta — navy closing band */
        cta(s) {
          const sec = document.createElement("section");
          sec.className = "bg-navy";
          const b = s.button || {};
          sec.innerHTML =
            '<div class="max-w-6xl mx-auto px-6 py-14 text-center">' +
            '<h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">' +
            esc(s.title) +
            "</h2>" +
            '<p class="mt-3 text-white/70 max-w-xl mx-auto">' +
            s.text +
            "</p>" +
            '<a href="' +
            esc(fill(b.href, v)) +
            '" class="btn-primary mt-7 !bg-white !text-navy hover:!bg-soft">' +
            esc(b.label) +
            "</a></div>";
          return sec;
        },
      };

      main.innerHTML = "";
      for (const s of P.layout || []) {
        const r = BLOCKS[s.type];
        if (!r) continue;
        const sec = r(s);
        if (sec) main.appendChild(sec);
      }
    })
    .catch((e) => {
      main.innerHTML =
        '<div class="py-12 text-center text-red-500">' + Tema.esc(e.message) + "</div>";
    });
})();
