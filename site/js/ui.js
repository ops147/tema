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

  return { ctas, stats, marquee, sectionHead, tail, iconCard, stepCard, tagCard, checkCard, terminal, urlRow };
})();
