// product.js — generic product-page controller used by plugin.html and
// theme.html: renders hero, stats strip and a stack of typed sections,
// all from site.json (pages.<data-page>). Section types: features (icon
// card grid), steps (numbered cards), code (terminal box), checks
// (checklist cards). A section with "soft": true gets the soft band style.
(function () {
  "use strict";
  const el = (id) => document.getElementById(id);
  const page = (document.currentScript.dataset || {}).page || "plugin";
  const main = el("sections");
  main.innerHTML = '<div class="py-12 text-center text-ink/60">…</div>';

  Tema.load()
    .then(({ site }) => {
      const P = site.pages[page];
      if (!P) throw new Error("site.json → missing pages." + page);
      const esc = Tema.esc,
        fill = Tema.fill;
      const v = Tema.vars(site);

      document.title = P.title;
      Tema.mountChrome(site, page);

      /* ---------- hero ---------- */
      el("hero-badge").textContent = P.hero.badge;
      el("hero-title").innerHTML = P.hero.title;
      el("hero-lead").innerHTML = P.hero.lead;
      el("hero-ctas").innerHTML = P.hero.ctas
        .map(
          (c) =>
            '<a href="' +
            esc(fill(c.href, v)) +
            '" class="' +
            (c.style === "primary" ? "btn-primary" : "btn-ghost") +
            '">' +
            esc(c.label) +
            "</a>"
        )
        .join("");

      /* ---------- stats ---------- */
      el("hero-stats").innerHTML = (P.stats || [])
        .map(
          (s) =>
            "<div>" +
            '<div class="text-2xl sm:text-3xl font-extrabold text-navy tracking-tight">' +
            esc(s.value) +
            "</div>" +
            '<div class="mt-1 text-[11px] font-semibold uppercase tracking-widest text-ink/60">' +
            esc(s.label) +
            "</div></div>"
        )
        .join("");

      /* ---------- section renderers ---------- */
      const head = (s) =>
        '<h2 class="text-3xl font-extrabold text-navy tracking-tight text-center">' +
        esc(s.title) +
        "</h2>" +
        (s.lead ? '<p class="mt-3 text-ink/90 text-center max-w-2xl mx-auto">' + s.lead + "</p>" : "");

      const tail = (s) =>
        s.link
          ? '<p class="mt-8 text-center"><a class="text-sm font-semibold text-blue hover:underline no-underline" href="' +
            esc(fill(s.link.href, v)) +
            '">' +
            esc(s.link.label) +
            "</a></p>"
          : "";

      const RENDER = {
        features(s) {
          const cols =
            { 2: "sm:grid-cols-2", 3: "sm:grid-cols-2 lg:grid-cols-3", 4: "sm:grid-cols-2 lg:grid-cols-4" }[
              s.cols || 4
            ] || "sm:grid-cols-2 lg:grid-cols-4";
          return (
            head(s) +
            '<div class="mt-10 grid ' +
            cols +
            ' gap-5">' +
            s.items
              .map(
                (f) =>
                  '<div class="bg-white border border-line rounded-2xl p-6 hover:border-blue hover:shadow-md transition">' +
                  '<svg class="w-7 h-7 stroke-blue" viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="' +
                  esc(f.icon) +
                  '"/></svg>' +
                  '<h3 class="mt-4 text-sm font-bold text-navy">' +
                  esc(f.title) +
                  "</h3>" +
                  '<p class="mt-2 text-xs leading-relaxed text-ink/80">' +
                  esc(f.text) +
                  "</p></div>"
              )
              .join("") +
            "</div>" +
            tail(s)
          );
        },

        steps(s) {
          return (
            head(s) +
            '<ol class="mt-10 max-w-4xl mx-auto grid sm:grid-cols-' +
            Math.min(s.items.length, 3) +
            ' gap-5 text-sm">' +
            s.items
              .map(
                (i) =>
                  '<li class="bg-white border border-line rounded-2xl p-5 list-none">' +
                  '<span class="w-7 h-7 rounded-full bg-blue text-white text-xs font-bold grid place-items-center">' +
                  esc(i.n) +
                  "</span>" +
                  '<h3 class="mt-3 text-sm font-bold text-navy">' +
                  esc(i.title) +
                  "</h3>" +
                  '<p class="mt-1.5 text-xs leading-relaxed text-ink/80">' +
                  esc(i.text) +
                  "</p></li>"
              )
              .join("") +
            "</ol>" +
            tail(s)
          );
        },

        code(s) {
          const lines = (s.lines || [])
            .map(
              (l) =>
                "<div>" +
                (l.startsWith("$")
                  ? '<span class="text-white/40">$</span>' + esc(l.slice(1))
                  : '<span class="text-white/50">' + esc(l) + "</span>") +
                "</div>"
            )
            .join("");
          return (
            head(s) +
            '<div class="mt-10 max-w-2xl mx-auto bg-navy rounded-2xl shadow-lg overflow-hidden">' +
            '<div class="flex gap-1.5 px-4 py-3 border-b border-white/10">' +
            '<span class="w-2.5 h-2.5 rounded-full bg-white/20"></span>'.repeat(3) +
            "</div>" +
            '<div class="px-5 py-4 font-mono text-[13px] leading-7 text-white/90 overflow-x-auto whitespace-nowrap">' +
            lines +
            "</div></div>" +
            (s.note ? '<p class="mt-4 text-xs text-ink/60 text-center">' + esc(s.note) + "</p>" : "") +
            tail(s)
          );
        },

        checks(s) {
          return (
            head(s) +
            '<div class="mt-10 max-w-4xl mx-auto grid sm:grid-cols-2 gap-5">' +
            s.items
              .map(
                (i) =>
                  '<div class="flex gap-3.5 bg-white border border-line rounded-2xl p-5">' +
                  '<svg class="w-5 h-5 stroke-blue shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>' +
                  "<div>" +
                  '<h3 class="text-sm font-bold text-navy">' +
                  esc(i.title) +
                  "</h3>" +
                  '<p class="mt-1 text-xs leading-relaxed text-ink/80">' +
                  esc(i.text) +
                  "</p></div></div>"
              )
              .join("") +
            "</div>" +
            tail(s)
          );
        },
      };

      main.innerHTML = "";
      for (const s of P.sections || []) {
        const r = RENDER[s.type];
        if (!r) continue;
        const sec = document.createElement("section");
        if (s.soft) sec.className = "bg-soft border-y border-line";
        sec.innerHTML = '<div class="max-w-6xl mx-auto px-6 py-16">' + r(s) + "</div>";
        main.appendChild(sec);
      }

      /* ---------- cta ---------- */
      el("cta-title").textContent = P.cta.title;
      el("cta-text").innerHTML = P.cta.text;
      const btn = el("cta-btn");
      btn.href = fill(P.cta.button.href, v);
      btn.textContent = P.cta.button.label;
    })
    .catch((e) => {
      main.innerHTML =
        '<div class="py-12 text-center text-red-500">' + Tema.esc(e.message) + "</div>";
    });
})();
