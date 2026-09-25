// ui.js — reusable UI atoms for the Pages mirror. Every builder returns an
// HTML string; page.js composes them into typed layout blocks. Plain-text
// fields pass through Tema.esc; fields that intentionally carry markup
// (hero title/lead, section leads, intro/cta text) are injected raw.
// Requires app.js (window.Tema) to be loaded first.
window.Tema = window.Tema || {};
Tema.ui = (function () {
  "use strict";
  const esc = Tema.esc,
    fill = Tema.fill;

  /* hero button row */
  const ctas = (list, v) =>
    (list || [])
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

  /* stats row (inside hero) */
  const stats = (list) =>
    !list || !list.length
      ? ""
      : '<div class="mt-12 grid grid-cols-2 sm:grid-cols-4 gap-6 max-w-3xl mx-auto">' +
        list
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
          .join("") +
        "</div>";

  /* marquee band (sits at the bottom of the hero section) */
  const marquee = (items) => {
    if (!items || !items.length) return "";
    const strip = items
      .map(
        (t) =>
          '<span class="flex items-center gap-2.5"><span class="w-1.5 h-1.5 rounded-full bg-blue"></span>' +
          esc(t) +
          "</span>"
      )
      .join("");
    return (
      '<div class="border-t border-line bg-white overflow-hidden py-4">' +
      '<div class="marquee flex whitespace-nowrap gap-10 w-max text-sm font-semibold text-ink/60">' +
      strip +
      strip +
      "</div></div>"
    );
  };

  /* centered section title + lead */
  const sectionHead = (title, lead) =>
    '<h2 class="text-3xl sm:text-4xl font-extrabold text-navy tracking-tight text-center">' +
    esc(title) +
    "</h2>" +
    (lead ? '<p class="mt-3 text-ink/90 text-center max-w-2xl mx-auto">' + lead + "</p>" : "");

  /* trailing "see more" link under a section */
  const tail = (s, v) =>
    s.link
      ? '<p class="mt-8 text-center"><a class="text-sm font-semibold text-blue hover:underline no-underline" href="' +
        esc(fill(s.link.href, v)) +
        '">' +
        esc(s.link.label) +
        "</a></p>"
      : "";

  /* icon feature card */
  const iconCard = (f) =>
    '<div class="bg-white border border-line rounded-2xl p-6 hover:border-blue hover:shadow-md transition">' +
    '<svg class="w-7 h-7 stroke-blue" viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="' +
    esc(f.icon) +
    '"/></svg>' +
    '<h3 class="mt-4 text-sm font-bold text-navy">' +
    esc(f.title) +
    "</h3>" +
    '<p class="mt-2 text-xs leading-relaxed text-ink/80">' +
    esc(f.text) +
    "</p></div>";

  /* numbered step card (<li>) */
  const stepCard = (s) =>
    '<li class="bg-white border border-line rounded-2xl p-5 list-none">' +
    '<span class="w-7 h-7 rounded-full bg-blue text-white text-xs font-bold grid place-items-center">' +
    esc(s.n) +
    "</span>" +
    '<h3 class="mt-3 text-sm font-bold text-navy">' +
    esc(s.title) +
    "</h3>" +
    '<p class="mt-1.5 text-xs leading-relaxed text-ink/80">' +
    esc(s.text) +
    "</p></li>";

  /* numbered card with tag (ecosystem) — wrapped in a link when s.href */
  const tagCard = (s, v) => {
    const inner =
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
      "</p>" +
      (s.href && s.linkLabel
        ? '<span class="mt-3 inline-block text-xs font-semibold text-blue">' + esc(s.linkLabel) + "</span>"
        : "");
    return s.href
      ? '<a href="' +
          esc(fill(s.href, v)) +
          '"' +
          (/^https?:/.test(fill(s.href, v)) ? ' target="_blank" rel="noopener"' : "") +
          ' class="bg-white border border-line rounded-2xl p-6 block no-underline hover:border-blue hover:shadow-md transition">' +
          inner +
          "</a>"
      : '<div class="bg-white border border-line rounded-2xl p-6">' + inner + "</div>";
  };

  /* checklist card */
  const checkCard = (i) =>
    '<div class="flex gap-3.5 bg-white border border-line rounded-2xl p-5">' +
    '<svg class="w-5 h-5 stroke-blue shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>' +
    "<div>" +
    '<h3 class="text-sm font-bold text-navy">' +
    esc(i.title) +
    "</h3>" +
    '<p class="mt-1 text-xs leading-relaxed text-ink/80">' +
    esc(i.text) +
    "</p></div></div>";

  /* terminal window for command listings */
  const terminal = (lines) =>
    '<div class="mt-10 max-w-2xl mx-auto bg-navy rounded-2xl shadow-lg overflow-hidden">' +
    '<div class="flex gap-1.5 px-4 py-3 border-b border-white/10">' +
    '<span class="w-2.5 h-2.5 rounded-full bg-white/20"></span>'.repeat(3) +
    "</div>" +
    '<div class="px-5 py-4 font-mono text-[13px] leading-7 text-white/90 overflow-x-auto whitespace-nowrap">' +
    (lines || [])
      .map(
        (l) =>
          "<div>" +
          (l.startsWith("$")
            ? '<span class="text-white/40">$</span>' + esc(l.slice(1))
            : '<span class="text-white/50">' + esc(l) + "</span>") +
          "</div>"
      )
      .join("") +
    "</div></div>";

  /* copyable URL row (setup blocks) */
  const urlRow = (u, v, copyLabel) =>
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
    esc(copyLabel) +
    "</button></div>";

  /* demo card body — shared by the layout "demos" block (wrapped in a link)
     and the browse grid (wrapped in an <article> with click handlers). The
     caller's wrapper must carry the "group" class; o.previewCta adds the
     hover overlay, o.interactive adds cursor affordances. */
  const demoCard = (id, d, o) =>
    '<div class="thumb relative aspect-[16/10] overflow-hidden bg-soft' +
    (o.interactive ? " cursor-pointer" : "") +
    '">' +
    (d.image
      ? '<img loading="lazy" src="' +
        esc(d.image) +
        '" alt="' +
        esc(d.title || id) +
        '" class="w-full h-full object-cover object-top transition duration-500 group-hover:scale-105" />'
      : "") +
    (o.previewCta
      ? '<div class="absolute inset-0 grid place-items-center bg-navy/0 group-hover:bg-navy/40 transition">' +
        '<span class="opacity-0 translate-y-1.5 group-hover:opacity-100 group-hover:translate-y-0 transition bg-white text-navy text-xs font-bold rounded-full px-5 py-2.5 shadow-xl">' +
        esc(o.previewCta) +
        "</span></div>"
      : "") +
    "</div>" +
    '<div class="flex flex-col gap-2 flex-1 px-5 pt-4 pb-5">' +
    '<div class="flex items-center gap-2">' +
    '<h3 class="text-base font-bold tracking-tight text-navy group-hover:text-blue transition' +
    (o.interactive ? " cursor-pointer" : "") +
    '">' +
    esc(d.title || id) +
    "</h3>" +
    '<span class="text-[10px] font-bold uppercase tracking-wide bg-blue/10 text-blue rounded-full px-2 py-0.5">' +
    esc(o.badge) +
    "</span>" +
    "</div>" +
    '<p class="text-xs leading-relaxed text-ink/80 line-clamp-3"><strong class="text-navy">' +
    esc(o.idealFor) +
    "</strong>" +
    esc(d.desc || "") +
    "</p></div>";

  /* browse app skeleton — mounted into #page before bindings attach */
  const browseShell = () =>
    '<section class="bg-soft border-b border-line">' +
    '<div class="max-w-6xl mx-auto px-6 py-12 text-center">' +
    '<h1 id="browse-heading" class="text-3xl sm:text-4xl font-extrabold text-navy tracking-tight"></h1>' +
    '<p id="browse-sub" class="mt-3 text-ink/90"></p>' +
    '<div class="mt-6 max-w-md mx-auto flex items-center gap-2.5 bg-white border border-line rounded-xl px-3.5 py-2.5 focus-within:border-blue transition">' +
    '<svg class="shrink-0 stroke-ink/50" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>' +
    '<input id="q" type="search" class="flex-1 bg-transparent outline-none text-sm text-navy placeholder:text-ink/50" />' +
    "</div></div></section>" +
    '<div class="max-w-6xl mx-auto px-6 py-10 flex flex-col md:flex-row gap-6 md:gap-8 md:items-start">' +
    '<aside class="md:w-52 md:shrink-0">' +
    '<h3 id="cats-title" class="text-xs font-bold uppercase tracking-widest text-ink/60 mb-4 max-md:mb-2.5"></h3>' +
    '<form id="cats" class="flex md:flex-col gap-2.5 max-md:flex-row max-md:flex-wrap max-md:overflow-x-auto max-md:pb-1"></form></aside>' +
    '<main class="flex-1 min-w-0"><div id="grid" class="grid sm:grid-cols-2 xl:grid-cols-3 gap-6"></div></main>' +
    "</div>" +
    '<div id="modal" class="fixed inset-0 z-50 hidden">' +
    '<div class="absolute inset-0 bg-navy/60 backdrop-blur-sm" data-close></div>' +
    '<div class="absolute inset-[2.5vh_2vw] max-md:inset-0 bg-white border border-line max-md:border-0 rounded-2xl max-md:rounded-none overflow-hidden flex shadow-2xl">' +
    '<aside class="w-60 shrink-0 max-md:hidden flex flex-col border-r border-line bg-soft">' +
    '<div class="px-4 pt-4 pb-3.5 border-b border-line bg-white">' +
    '<h2 id="m-title" class="text-base font-bold tracking-tight text-navy"></h2>' +
    '<div id="m-cats" class="flex flex-wrap gap-1.5 mt-2.5"></div></div>' +
    '<nav id="m-pages" class="overflow-y-auto p-2.5 flex flex-col gap-0.5"></nav>' +
    '<div id="modal-note" class="mt-auto px-4 py-3.5 border-t border-line text-[11px] text-ink/70 leading-relaxed bg-white"></div></aside>' +
    '<div class="flex-1 flex flex-col min-w-0">' +
    '<div class="flex items-center gap-3 px-3.5 py-2 border-b border-line bg-white">' +
    '<select id="m-pages-mobile" class="md:hidden shrink-0 max-w-32 text-[12px] font-medium text-navy bg-soft border border-line rounded-lg px-2 py-1.5 cursor-pointer" aria-label="Demo page"></select>' +
    '<span id="m-file" class="flex-1 min-w-0 truncate font-mono text-[11px] text-ink/60 max-md:hidden"></span>' +
    '<span class="flex-1 md:hidden"></span>' +
    '<div id="devices" class="flex gap-1 bg-soft border border-line rounded-lg p-1"></div>' +
    '<a id="m-open" href="#" target="_blank" rel="noopener" class="text-[11px] font-semibold px-3 py-1.5 rounded-lg border border-line text-ink hover:text-blue hover:border-blue transition no-underline"></a>' +
    '<button id="m-close" data-close class="cursor-pointer border-0 bg-transparent text-ink/60 hover:text-navy hover:bg-soft text-base px-2 py-1 rounded-md transition">✕</button></div>' +
    '<div class="flex-1 overflow-auto flex justify-center bg-soft p-4 max-md:p-0">' +
    '<div id="m-wrap" class="w-full h-full transition-[width] duration-300">' +
    '<iframe id="m-frame" title="Template preview" class="w-full h-full border border-line rounded-xl max-md:rounded-none max-md:border-0 bg-white shadow-lg"></iframe>' +
    "</div></div></div></div></div>";

  /* preview app skeleton — mounted into #page (body is a flex column) */
  const previewShell = () =>
    '<div id="bar" class="flex items-center gap-4 px-4 py-2 bg-white border-b border-line text-xs text-ink">' +
    '<a id="back" href="browse.html" class="text-blue hover:underline no-underline font-medium"></a>' +
    '<span id="file" class="flex-1 min-w-0 truncate font-mono text-[11px] text-ink/60"></span>' +
    '<a id="raw" href="#" target="_blank" rel="noopener" class="text-blue hover:underline no-underline font-medium"></a></div>' +
    '<div id="err" class="hidden p-8 text-red-500 text-sm"></div>' +
    '<iframe id="frame" title="Template preview" class="flex-1 w-full border-0 bg-white"></iframe>';

  return {
    ctas, stats, marquee, sectionHead, tail,
    iconCard, stepCard, tagCard, checkCard, terminal, urlRow, demoCard,
    browseShell, previewShell,
  };
})();
