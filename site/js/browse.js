// browse.js — template library controller: grid, category filters, search and
// the demo viewer are all rendered from site.json + templates/manifest.json.
// Viewer state is mirrored to the URL (?d=<demo>&p=<page>) so every view is
// shareable and the back button closes/navigates the modal.
(function () {
  "use strict";
  const el = (id) => document.getElementById(id);
  const grid = el("grid");
  grid.innerHTML = '<div class="col-span-full py-12 text-center text-ink/60">…</div>';

  Tema.load()
    .then(({ site, manifest }) => {
      const P = site.pages.browse;
      const esc = Tema.esc,
        fill = Tema.fill;
      const TPL = manifest.templates || {};
      const DEMOS = manifest.demos || {};
      const activeCats = new Set();

      document.title = P.title;
      Tema.mountChrome(site, "browse");

      el("browse-heading").textContent = P.heading;
      el("q").placeholder = P.searchPlaceholder;
      el("cats-title").textContent = P.categoriesTitle;
      el("modal-note").innerHTML = P.modalNote;
      el("m-open").textContent = P.openLabel;
      el("m-close").title = P.closeTitle;

      /* ---------- device switcher (from config) ---------- */
      const DEV_W = {
        desktop: "w-full",
        tablet: "w-[768px] max-w-full",
        mobile: "w-[390px] max-w-full",
      };
      const devBar = el("devices");
      P.devices.forEach((d, i) => {
        const b = document.createElement("button");
        b.className =
          "dev text-[11px] font-semibold px-2.5 py-1 rounded-md transition cursor-pointer border-0 bg-transparent " +
          (i === 0 ? "on bg-white text-blue shadow-sm" : "text-ink");
        b.dataset.dev = d.id;
        b.textContent = d.label;
        devBar.appendChild(b);
      });
      devBar.querySelectorAll(".dev").forEach((b) =>
        b.addEventListener("click", () => {
          devBar.querySelectorAll(".dev").forEach((x) => {
            const on = x === b;
            x.classList.toggle("bg-white", on);
            x.classList.toggle("text-blue", on);
            x.classList.toggle("shadow-sm", on);
            x.classList.toggle("text-ink", !on);
          });
          el("m-wrap").className =
            "h-full transition-[width] duration-300 " + (DEV_W[b.dataset.dev] || "w-full");
        })
      );

      /* ---------- demo viewer ---------- */
      const modal = el("modal"),
        frame = el("m-frame");

      function urlFor(id, page) {
        const u = new URL(location.href);
        u.search = "";
        if (id) {
          u.searchParams.set("d", id);
          if (page) u.searchParams.set("p", page);
        }
        return u;
      }

      function openDemo(id, pageSlug, push) {
        const d = DEMOS[id];
        if (!d) return;
        if (!pageSlug) pageSlug = d.home || (d.pages || [])[0];
        el("m-title").textContent = d.title || id;
        el("m-cats").innerHTML = (d.categories || [])
          .map(
            (c) =>
              '<span class="text-[10px] font-semibold bg-blue/10 text-blue border border-blue/25 rounded-full px-2.5 py-0.5">' +
              esc(c) +
              "</span>"
          )
          .join("");
        const nav = el("m-pages");
        nav.innerHTML = "";
        for (const p of d.pages || []) {
          if (!TPL[p]) continue;
          const b = document.createElement("button");
          const on = p === pageSlug;
          b.className =
            "text-left cursor-pointer border-0 rounded-lg px-2.5 py-2 text-[13px] font-medium transition flex items-center gap-2 " +
            (on ? "bg-blue/10 text-blue" : "bg-transparent text-ink hover:bg-white hover:text-navy");
          b.innerHTML =
            (on ? '<span class="w-1.5 h-1.5 rounded-full bg-blue shrink-0"></span>' : "") +
            esc(TPL[p].name || p);
          b.addEventListener("click", () => openDemo(id, p));
          nav.appendChild(b);
        }
        const file = TPL[pageSlug] ? TPL[pageSlug].file : "";
        el("m-file").textContent = "templates/" + file;
        frame.src = "preview.html?f=templates/" + encodeURIComponent(file) + "&embed=1";
        el("m-open").href = "preview.html?f=templates/" + encodeURIComponent(file);
        modal.classList.replace("hidden", "block");
        document.body.classList.add("overflow-hidden");
        const state = { d: id, p: pageSlug };
        if (push === false) history.replaceState(state, "", urlFor(id, pageSlug));
        else history.pushState(state, "", urlFor(id, pageSlug));
      }

      function closeDemo(syncUrl) {
        modal.classList.replace("block", "hidden");
        document.body.classList.remove("overflow-hidden");
        frame.src = "about:blank";
        if (syncUrl !== false) history.replaceState({}, "", urlFor(null));
      }

      modal.addEventListener("click", (e) => {
        if (e.target.closest("[data-close]")) closeDemo();
      });
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeDemo();
      });
      window.addEventListener("popstate", (e) => {
        const s = e.state || {};
        if (s.d && DEMOS[s.d]) openDemo(s.d, s.p, false);
        else closeDemo(false);
      });

      /* ---------- library grid ---------- */
      function render() {
        const q = el("q").value.trim().toLowerCase();
        const shown = Object.entries(DEMOS).filter(([, d]) => {
          const catOk = !activeCats.size || (d.categories || []).some((c) => activeCats.has(c));
          const qOk = !q || (d.title + " " + (d.desc || "")).toLowerCase().includes(q);
          return catOk && qOk;
        });
        el("browse-sub").textContent = fill(P.subtitle, { count: shown.length });
        grid.innerHTML = "";
        if (!shown.length) {
          grid.innerHTML =
            '<div class="col-span-full py-12 text-center text-ink/60">' + esc(P.empty) + "</div>";
          return;
        }
        for (const [id, d] of shown) grid.appendChild(card(id, d));
      }

      function card(id, d) {
        const el2 = document.createElement("article");
        el2.className =
          "group bg-white border border-line rounded-2xl overflow-hidden flex flex-col transition duration-300 hover:-translate-y-1 hover:border-blue hover:shadow-lg";
        el2.innerHTML =
          '<div class="thumb relative aspect-[16/10] overflow-hidden bg-soft cursor-pointer">' +
          (d.image
            ? '<img loading="lazy" src="' +
              esc(d.image) +
              '" alt="" class="w-full h-full object-cover object-top transition duration-500 group-hover:scale-105" />'
            : "") +
          '<div class="absolute inset-0 grid place-items-center bg-navy/0 group-hover:bg-navy/40 transition">' +
          '<span class="opacity-0 translate-y-1.5 group-hover:opacity-100 group-hover:translate-y-0 transition bg-white text-navy text-xs font-bold rounded-full px-5 py-2.5 shadow-xl">' +
          esc(P.previewCta) +
          "</span></div></div>" +
          '<div class="flex flex-col gap-2 flex-1 px-5 pt-4 pb-5">' +
          '<div class="flex items-center gap-2">' +
          '<h2 class="text-base font-bold tracking-tight text-navy cursor-pointer hover:text-blue transition">' +
          esc(d.title || id) +
          "</h2>" +
          '<span class="text-[10px] font-bold uppercase tracking-wide bg-blue/10 text-blue rounded-full px-2 py-0.5">' +
          esc(P.badge) +
          "</span>" +
          "</div>" +
          '<p class="text-xs leading-relaxed text-ink/80 line-clamp-3"><strong class="text-navy">' +
          esc(P.idealFor) +
          "</strong>" +
          esc(d.desc || "") +
          "</p></div>";
        el2.querySelector(".thumb").addEventListener("click", () => openDemo(id));
        el2.querySelector("h2").addEventListener("click", () => openDemo(id));
        return el2;
      }

      /* ---------- categories + search ---------- */
      const cats = [...new Set(Object.values(DEMOS).flatMap((d) => d.categories || []))].sort();
      const catsEl = el("cats");
      for (const c of cats) {
        const label = document.createElement("label");
        label.className =
          "flex items-center gap-2.5 text-sm text-ink cursor-pointer select-none hover:text-navy";
        label.innerHTML =
          '<input type="checkbox" class="w-4 h-4 rounded accent-[#1d70db]" value="' +
          esc(c) +
          '" />' +
          esc(c);
        label.querySelector("input").addEventListener("change", (e) => {
          e.target.checked ? activeCats.add(c) : activeCats.delete(c);
          render();
        });
        catsEl.appendChild(label);
      }
      el("q").addEventListener("input", render);
      render();

      /* ---------- deep link: browse.html?d=<demo>&p=<page> ---------- */
      const qp = new URLSearchParams(location.search);
      const dd = qp.get("d"),
        pp = qp.get("p");
      if (dd && DEMOS[dd]) openDemo(dd, pp || undefined, false);
    })
    .catch((e) => {
      grid.innerHTML =
        '<div class="col-span-full py-12 text-center text-red-500">' + Tema.esc(e.message) + "</div>";
    });
})();
