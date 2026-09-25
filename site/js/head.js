// head.js — injects the shared <head> assets (Google Fonts + Tailwind theme
// tokens and component classes) so each page's <head> is just meta + title.
// Must run as a synchronous script BEFORE the @tailwindcss/browser CDN tag —
// the compiler then picks up the injected <style type="text/tailwindcss">
// block during its initial scan, with no flash of unstyled content.
(function () {
  "use strict";
  const head = document.head;
  const add = (tag, attrs) => {
    const e = document.createElement(tag);
    for (const k in attrs) e.setAttribute(k, attrs[k]);
    return head.appendChild(e);
  };
  add("link", { rel: "preconnect", href: "https://fonts.googleapis.com" });
  add("link", { rel: "preconnect", href: "https://fonts.gstatic.com", crossorigin: "" });
  add("link", {
    rel: "stylesheet",
    href: "https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap",
  });
  add("style", { type: "text/tailwindcss" }).textContent = [
    "@theme {",
    "  --color-navy: #000f44;",
    "  --color-ink: #555555;",
    "  --color-line: #dedede;",
    "  --color-blue: #1d70db;",
    "  --color-blue-d: #155cb8;",
    "  --color-soft: #f5f7fb;",
    '  --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;',
    '  --font-mono: "JetBrains Mono", ui-monospace, monospace;',
    "}",
    "body { @apply bg-white text-ink font-sans antialiased; }",
    "code { @apply font-mono; }",
    ".btn-primary { @apply inline-flex items-center gap-2 bg-blue hover:bg-blue-d text-white text-sm font-semibold rounded-lg px-6 py-3 transition no-underline shadow-sm; }",
    ".btn-ghost { @apply inline-flex items-center gap-2 border border-line hover:border-navy text-navy text-sm font-semibold rounded-lg px-6 py-3 transition no-underline; }",
    ".marquee { animation: marquee 45s linear infinite; }",
    "@keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }",
  ].join("\n");
})();
