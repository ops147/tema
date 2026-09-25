// browse.js — template library controller: grid, category filters, search and
// the demo viewer are all rendered from site.json + templates/manifest.json.
// Viewer state is mirrored to the URL (?d=<demo>&p=<page>) so every view is
// shareable and the back button closes/navigates the modal.
(function () {
  "use strict";
  const el = (id) => document.getElementById(id);
  el("page").innerHTML = Tema.ui.browseShell();
  const grid = el("grid");
  grid.innerHTML = '<div class="col-span-full py-12 text-center text-ink/60">…</div>';

  Tema.load("browse")
    .then(({ site, manifest, page: P }) => {
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
        // If the slug isn't registered in the manifest, fall back to the
        // first page that is — never hand preview.html an empty ?f=.
        if (!TPL[pageSlug]) pageSlug = (d.pages || []).find((p) => TPL[p]);
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
        const sel = el("m-pages-mobile"); // page switcher shown on small screens
        nav.innerHTML = "";
        if (sel) sel.innerHTML = "";
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
          if (sel) {
            const opt = document.createElement("option");
            opt.value = p;
            opt.textContent = TPL[p].name || p;
            opt.selected = on;
            sel.appendChild(opt);
          }
        }
        if (sel && !sel.dataset.bound) {
          sel.dataset.bound = "1";
          sel.addEventListener("change", () => {
            const cur = new URLSearchParams(location.search).get("d") || id;
            openDemo(cur, sel.value);
          });
        }
        const file = pageSlug && TPL[pageSlug] ? TPL[pageSlug].file : "";
        el("m-file").textContent = file ? "templates/" + file : "—";
        frame.src = file
          ? "preview.html?f=templates/" + encodeURIComponent(file) + "&embed=1"
          : "about:blank";
        el("m-open").href = file ? "preview.html?f=templates/" + encodeURIComponent(file) : "#";
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
        el2.innerHTML = Tema.ui.demoCard(id, d, {
          badge: P.badge,
          idealFor: P.idealFor,
          previewCta: P.previewCta,
          interactive: true,
        });
        el2.querySelector(".thumb").addEventListener("click", () => openDemo(id));
        el2.querySelector("h3").addEventListener("click", () => openDemo(id));
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
