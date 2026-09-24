// index.js — landing page controller: every section is rendered from
// site.json (content) + templates/manifest.json (demo registry).
(function () {
  "use strict";
  const el = (id) => document.getElementById(id);
  const grid = el("grid");
  grid.innerHTML = '<div class="col-span-full py-12 text-center text-ink/60">…</div>';

  Tema.load()
    .then(({ site, manifest }) => {
      const P = site.pages.index;
      const esc = Tema.esc,
        fill = Tema.fill;
      const v = Tema.vars(site);

      document.title = P.title;
      Tema.mountChrome(site, "index");

      /* ---------- hero ---------- */
      el("hero-badge").textContent = P.hero.badge;
      el("hero-title").innerHTML = P.hero.title;
      el("hero-lead").innerHTML = P.hero.lead;
      el("hero-ctas").innerHTML = P.hero.ctas
        .map(
          (c) =>
            '<a href="' +
            esc(c.href) +
            '" class="' +
            (c.style === "primary" ? "btn-primary" : "btn-ghost") +
            '">' +
            esc(c.label) +
            "</a>"
        )
        .join("");

      el("intro").innerHTML = P.intro;

      const DEMOS = manifest.demos || {};
      const ids = Object.keys(DEMOS);

      /* ---------- demos ---------- */
      el("demos-title").textContent = fill(P.demos.title, { count: ids.length });
      el("demos-lead").innerHTML = P.demos.lead;

      const strip = Object.values(DEMOS)
        .map(
          (d) =>
            '<span class="flex items-center gap-2.5"><span class="w-1.5 h-1.5 rounded-full bg-blue"></span>' +
            esc(d.title || "") +
            "</span>"
        )
        .join("");
      el("marquee").innerHTML = strip + strip;

      grid.innerHTML = "";
      for (const [id, d] of Object.entries(DEMOS).slice(0, P.demos.featuredCount || 6)) {
        const a = document.createElement("a");
        a.href = "browse.html?d=" + encodeURIComponent(id);
        a.className =
          "group bg-white border border-line rounded-2xl overflow-hidden flex flex-col transition duration-300 hover:-translate-y-1 hover:border-blue hover:shadow-lg no-underline";
        a.innerHTML =
          '<div class="relative aspect-[16/10] overflow-hidden bg-soft">' +
          (d.image
            ? '<img loading="lazy" src="' +
              esc(d.image) +
              '" alt="" class="w-full h-full object-cover object-top transition duration-500 group-hover:scale-105" />'
            : "") +
          "</div>" +
          '<div class="flex flex-col gap-2 flex-1 px-5 pt-4 pb-5">' +
          '<div class="flex items-center gap-2">' +
          '<h3 class="text-base font-bold tracking-tight text-navy group-hover:text-blue transition">' +
          esc(d.title || id) +
          "</h3>" +
          '<span class="text-[10px] font-bold uppercase tracking-wide bg-blue/10 text-blue rounded-full px-2 py-0.5">' +
          esc(P.demos.badge) +
          "</span>" +
          "</div>" +
          '<p class="text-xs leading-relaxed text-ink/80 line-clamp-3"><strong class="text-navy">' +
          esc(P.demos.idealFor) +
          "</strong>" +
          esc(d.desc || "") +
          "</p></div>";
        grid.appendChild(a);
      }

      const more = el("demos-more");
      more.href = P.demos.moreHref;
      more.textContent = P.demos.moreLabel;

      /* ---------- features ---------- */
      el("feat-title").textContent = P.features.title;
      el("feat-lead").innerHTML = P.features.lead;
      el("features").innerHTML = P.features.items
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
        .join("");

      /* ---------- ecosystem ---------- */
      el("eco-title").textContent = P.ecosystem.title;
      el("eco-lead").innerHTML = P.ecosystem.lead;
      el("ecosystem").innerHTML = P.ecosystem.items
        .map(
          (s) =>
            '<div class="bg-white border border-line rounded-2xl p-6">' +
            '<div class="flex items-center gap-2.5">' +
            '<span class="w-7 h-7 rounded-full bg-blue text-white text-xs font-bold grid place-items-center shrink-0">' +
            esc(s.n) +
            "</span>" +
            '<span class="text-[10px] font-bold uppercase tracking-widest text-blue">' +
            esc(s.tag) +
            "</span></div>" +
            '<h3 class="mt-3 text-sm font-bold text-navy">' +
            esc(s.title) +
            "</h3>" +
            '<p class="mt-1.5 text-xs leading-relaxed text-ink/80">' +
            esc(s.text) +
            "</p></div>"
        )
        .join("");
      el("eco-note").innerHTML = P.ecosystem.note;

      /* ---------- setup ---------- */
      el("setup-title").textContent = P.setup.title;
      el("setup-lead").innerHTML = P.setup.lead;
      el("setup-urls").innerHTML = P.setup.urls
        .map(
          (u) =>
            '<div class="flex flex-wrap items-center gap-2.5 py-2">' +
            '<span class="text-xs font-semibold text-ink/70 w-36 uppercase tracking-wide">' +
            esc(u.label) +
            "</span>" +
            '<code class="flex-1 min-w-64 text-[13px] bg-soft border border-line rounded-lg px-3 py-2 text-navy overflow-x-auto whitespace-nowrap">' +
            esc(fill(u.value, v)) +
            "</code>" +
            '<button class="text-xs font-semibold px-3 py-2 rounded-lg border border-line text-ink hover:border-blue hover:text-blue transition cursor-pointer bg-white" data-copy="' +
            esc(fill(u.value, v)) +
            '">' +
            esc(P.setup.copyLabel) +
            "</button></div>"
        )
        .join("");
      el("steps").innerHTML = P.setup.steps
        .map(
          (s) =>
            '<li class="bg-white border border-line rounded-2xl p-5 list-none">' +
            '<span class="w-7 h-7 rounded-full bg-blue text-white text-xs font-bold grid place-items-center">' +
            esc(s.n) +
            "</span>" +
            '<h3 class="mt-3 text-sm font-bold text-navy">' +
            esc(s.title) +
            "</h3>" +
            '<p class="mt-1.5 text-xs leading-relaxed text-ink/80">' +
            esc(s.text) +
            "</p></li>"
        )
        .join("");
      Tema.bindCopy(el("setup-urls"), { copy: P.setup.copyLabel, copied: P.setup.copiedLabel });

      /* ---------- cta ---------- */
      el("cta-title").textContent = P.cta.title;
      el("cta-text").textContent = P.cta.text;
      const btn = el("cta-btn");
      btn.href = P.cta.button.href;
      btn.textContent = P.cta.button.label;
    })
    .catch((e) => {
      grid.innerHTML =
        '<div class="col-span-full py-12 text-center text-red-500">' + Tema.esc(e.message) + "</div>";
    });
})();
