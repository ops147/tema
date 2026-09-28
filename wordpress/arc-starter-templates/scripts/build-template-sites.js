/**
 * build-template-sites.js — regenerates every demo under templates/ as the
 * canonical ARC 7-page site (Home, About, Services, Virtual Services,
 * Playbooks, Foundation, Partners) with ARC messaging, keeping each demo's
 * own @theme tokens, Google fonts and image pool. Also rewrites
 * templates/manifest.json. Run: node scripts/build-template-sites.js
 */
'use strict';
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');
const TPL_DIR = path.join(ROOT, 'templates');
const IMG_DIR = path.join(ROOT, 'assets', 'img');
const MANIFEST = path.join(TPL_DIR, 'manifest.json');

const PAGES = ['home', 'about', 'services', 'virtual-services', 'playbooks', 'foundation', 'partners'];
/* Display name per template in the library — replaces the generic page label. */
/* Layout variants — same copy on every demo, different structure.
   Inspired by the SolaceWP starter sites: hero shape, card-grid density,
   split direction and CTA style combine per demo so no two demos share
   the same page skeleton. */
const VARIANTS = 3;

/* ---- structural helpers ------------------------------------------------ */
const grid3 = (c) => ({ 0: 'lg:grid-cols-3', 1: 'lg:grid-cols-2', 2: 'lg:grid-cols-1' }[c.gridV] || 'lg:grid-cols-3');
const grid4 = (c) => ({ 0: 'lg:grid-cols-4', 1: 'lg:grid-cols-2', 2: 'lg:grid-cols-1' }[c.gridV] || 'lg:grid-cols-4');
const grid2 = (c) => (2 === c.gridV ? 'md:grid-cols-1' : 'md:grid-cols-2');
const splitCols = (c) => (1 === c.gridV ? 'lg:grid-cols-1' : 'lg:grid-cols-2');
const dirA = (c) => (c.flip ? 'right' : 'left');
const dirB = (c) => (c.flip ? 'left' : 'right');
/* ordA/ordB: bare order tokens for use inside class="…"; clsA/clsB: full class attr for bare divs. */
const ordA = (c) => (c.flip ? 'lg:order-2' : '');
const ordB = (c) => (c.flip ? 'lg:order-1' : '');
const clsA = (c) => (c.flip ? ' class="lg:order-2"' : '');
const clsB = (c) => (c.flip ? ' class="lg:order-1"' : '');

const TPL_NAMES = {
    home: 'Home — Elite Talent',
    about: 'About — Built to Execute',
    services: 'Services — Back Office Stack',
    'virtual-services': 'Virtual Services — Off Your Plate',
    playbooks: 'Playbooks — Living Intranet',
    foundation: 'Foundation — Opportunity Engine',
    partners: 'Partners — Client Channel',
};

const NAV = [
    ['home', 'Home'],
    ['services', 'Services'],
    ['virtual-services', 'Virtual Services'],
    ['playbooks', 'Playbooks'],
    ['about', 'About'],
    ['foundation', 'Foundation'],
    ['partners', 'Partners'],
];

/* ===== ARC "River Systems" design tokens — applied to every demo so all of
   them read as Ash River Collective while keeping their own fonts/images. ===== */
const ARC_TOKENS = {
    'color-navy': '#0F1C2E',       // Deep Navy — fondo oscuro / autoridad
    'color-brand': '#59B8A9',      // River Teal — primary brand
    'color-brand-dark': '#3E8E80', // teal oscuro (hovers)
    'color-accent': '#D6F73A',     // ARC Lime — CTA / highlights only
    'color-leaf': '#7FB34D',       // River Green — acciones secundarias
    'color-mint': '#F5F8F6',       // Mist — background principal (light sections)
    'color-gold': '#E4CF43',       // River Gold — Foundation / impacto
    'color-cream': '#EAF1E8',      // Soft Sage — background alternativo
    'color-slate-600': '#53616B',  // Slate — body text
};

/* Shared component layer — normalized across demos (lime primary CTA, navy
   secondary, teal eyebrow/accents) per ARC button/accessibility rules. */
const ARC_COMPONENTS = `@layer components {
  .btn-primary { display:inline-flex; align-items:center; gap:.5rem; border-radius:9999px; background:var(--color-accent); padding:.8rem 1.75rem; font-size:.9rem; font-weight:700; letter-spacing:.02em; text-transform:uppercase; color:var(--color-navy); transition:all .2s; }
  .btn-primary:hover { background:var(--color-navy); color:#fff; transform:translateY(-2px); box-shadow:0 10px 15px -3px rgb(15 28 46 / .25); }
  .btn-outline { display:inline-flex; align-items:center; gap:.5rem; border-radius:9999px; border:1px solid var(--color-navy); padding:.8rem 1.75rem; font-size:.9rem; font-weight:600; color:var(--color-navy); transition:all .2s; }
  .btn-outline:hover { background:var(--color-navy); color:#fff; transform:translateY(-2px); }
  .btn-ghost-light { display:inline-flex; align-items:center; gap:.5rem; border-radius:9999px; border:1px solid rgba(255,255,255,.25); padding:.8rem 1.75rem; font-size:.9rem; font-weight:600; color:#fff; transition:all .2s; }
  .btn-ghost-light:hover { border-color:#fff; transform:translateY(-2px); }
  .eyebrow { font-size:.8rem; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:var(--color-brand); }
  .field { width:100%; border-radius:.6rem; border:1px solid #e2e8f0; background:#fff; padding:.8rem 1rem; font-size:.9rem; color:var(--color-navy); outline:none; transition:border-color .2s, box-shadow .2s; }
  .field:focus { border-color:var(--color-brand); box-shadow:0 0 0 3px rgb(89 184 169 / .22); }
  /* Hover-lift card — shared replacement for "transition hover:-translate-y-1 hover:shadow-lg". */
  .card-lift { transition:transform .25s ease, box-shadow .25s ease; }
  .card-lift:hover { transform:translateY(-4px); box-shadow:0 10px 15px -3px rgb(0 0 0 / .1), 0 4px 6px -4px rgb(0 0 0 / .1); }
}`;

// Per-demo identity. h1 accepts inline HTML (brand span).
const DEMOS = {
    'arc-site': { name: 'Ash River Collective', l1: 'ASH RIVER', l2: 'COLLECTIVE', logo: 'logo-green.webp', h1: 'Elite Talent. <span class="text-brand">Bulletproof Systems.</span>' },
    'avira': { name: 'Avira', h1: 'Balanced teams. <span class="text-brand">Bulletproof books.</span>' },
    'blogify': { name: 'Blogify', h1: 'Stories are told. <span class="text-brand">Systems are built.</span>' },
    'commercecraft': { name: 'CommerceCraft', h1: 'Crafted teams. <span class="text-brand">Crafted systems.</span>' },
    'consultant': { name: 'Consultant', h1: 'People you trust. <span class="text-brand">Processes you repeat.</span>' },
    'corporate': { name: 'Corporate', h1: 'Teams that execute. <span class="text-brand">Systems that scale.</span>' },
    'edinburgh': { name: 'Edinburgh', h1: 'Sharp talent. <span class="text-brand">Sharper systems.</span>' },
    'flexiweb': { name: 'Flexiweb', h1: 'Flexible teams. <span class="text-brand">Bulletproof systems.</span>' },
    'gymnista': { name: 'Gymnista', h1: 'Strong teams. <span class="text-brand">Stronger systems.</span>' },
    'hirewell': { name: 'Hirewell', h1: 'The right hire. <span class="text-brand">Ready in days.</span>' },
    'kelion': { name: 'Kelion', h1: 'Talent worth <span class="text-brand">following.</span>' },
    'kindora': { name: 'Kindora', h1: 'People first. <span class="text-brand">Systems always.</span>' },
    'nerra': { name: 'Nerra', h1: 'Clean energy. <span class="text-brand">Cleaner operations.</span>' },
    'nomad': { name: 'Nomad', h1: 'World-class talent. <span class="text-brand">Zero borders.</span>' },
    'orbita': { name: 'Orbita', h1: 'Talent and systems, <span class="text-brand">in one orbit.</span>' },
    'ossian': { name: 'Ossian', h1: 'Naturally better <span class="text-brand">teams.</span>' },
    'printifix': { name: 'Printifix', h1: 'Precision talent. <span class="text-brand">Precision systems.</span>' },
    'renovation': { name: 'Renovation', h1: 'Rebuild the back office. <span class="text-brand">Bulletproof it.</span>' },
    'retailridge': { name: 'RetailRidge', h1: 'Retail-ready talent. <span class="text-brand">Repeatable systems.</span>' },
    'smile-dent': { name: 'Smile Dent', h1: 'Teams worth <span class="text-brand">smiling about.</span>' },
    'vitalia': { name: 'Vitalia', h1: 'Healthy books. <span class="text-brand">Healthier teams.</span>' },
};

const GENERIC_IMGS = [
    'business-people-sitting-together-on-couch-2023-11-27-04-52-21-utc.webp',
    'businessman-writing-on-clipboard-closeup-SMEP6T5.webp',
    'business-businessman-senior-mature-office-meeting-2023-11-27-05-34-55-utc-1.webp',
    'group-of-diverse-business-people-successful-teamwo-2023-11-27-05-12-43-utc.webp',
    'teamwork-with-business-people-analysis-cost-graph-3JFK4U7.webp',
    'happy-business-people-handshake-and-interview-in-m-6FPDS75.webp',
    'two-businessmen-working-together-in-office-2024-09-23-03-08-53-utc.webp',
    'looking-good-and-feeling-confident-XJ79UJL.webp',
];

const SUB = (d) => `Build the team your business needs without adding unnecessary overhead. ${d.name} places trained accounting and administrative professionals inside your business — then helps document the processes they run so execution doesn't depend on one person.`;
/* Same hero copy on every demo — only the structure changes. */
const ARC_H1 = 'Elite Talent. <span class="text-brand">Bulletproof Systems.</span>';

/* ------------------------------------------------ extraction ------------- */

function skinOf(dir, homeFile) {
    const html = fs.readFileSync(path.join(dir, homeFile), 'utf8');
    let style = (html.match(/<style type="text\/tailwindcss">[\s\S]*?<\/style>/) || [''])[0];
    // Normalize every demo to the ARC "River Systems" palette + component
    // layer. Fonts (@theme --font-*) and images stay per-demo.
    for (const [k, v] of Object.entries(ARC_TOKENS)) {
        const re = new RegExp(`--${k}:\\s*[^;]+;`);
        if (re.test(style)) style = style.replace(re, `--${k}: ${v};`);
        else style = style.replace(/@theme \{/, `@theme {\n  --${k}: ${v};`);
    }
    if (/@layer components \{[\s\S]*?\n\}/.test(style)) {
        style = style.replace(/@layer components \{[\s\S]*?\n\}/, ARC_COMPONENTS);
    } else {
        style = style.replace('</style>', `${ARC_COMPONENTS}\n    </style>`);
    }
    // Guarantee the tokens the shared skeleton relies on.
    const grab = (k) => (style.match(new RegExp(`--${k}:\\s*([^;]+);`)) || [])[1];
    const need = {
        'color-accent': (grab('color-leaf') || grab('color-brand') || '#38bdf8').trim(),
        'color-cream': (grab('color-cream') || '#f1f6ee').trim(),
        'font-display': (grab('font-display') || grab('font-sans') || 'ui-sans-serif').trim(),
    };
    let inject = '';
    for (const [k, v] of Object.entries(need)) {
        if (!new RegExp(`--${k}:`).test(style)) inject += `  --${k}: ${v};\n`;
    }
    if (inject) style = style.replace(/@theme \{/, `@theme {\n${inject}`);
    const fonts = html.match(/<link[^>]*(?:fonts\.googleapis|fonts\.gstatic)[^>]*>/g) || [];
    const root = (html.match(/<style>:root\{[^}]*\}<\/style>/) || [''])[0];
    return { style, fonts: [...new Set(fonts)], root };
}

const EXCLUDE_IMGS = (name) => name === 'logo-green.webp' || /^new-logocarousel/.test(name);

// Free stock photos (Unsplash CDN) downloaded to assets/img/arc-stock-NN.jpg —
// business/talent themed, shared across demos in rotated windows so each site
// keeps a distinct look while staying on-message.
function stockPool() {
    return fs.readdirSync(IMG_DIR)
        .filter((f) => /^arc-stock-\d+\.(jpe?g|webp|png)$/.test(f))
        .sort();
}

function imagePool(dir, demoKey, logo, demoIdx, demoCount) {
    if ('arc-site' === demoKey) {
        // The flagship keeps its own branded photography.
        const pool = [];
        for (const f of fs.readdirSync(dir).filter((f) => f.endsWith('.html')).sort()) {
            const html = fs.readFileSync(path.join(dir, f), 'utf8');
            for (const m of html.matchAll(/Img\/([^"'\s>]+)/g)) {
                const name = decodeURIComponent(m[1]);
                if (EXCLUDE_IMGS(name)) continue;
                if (fs.existsSync(path.join(IMG_DIR, name)) && !pool.includes(name)) pool.push(name);
            }
        }
        return pool.length ? pool : GENERIC_IMGS.slice(0, 6);
    }
    const stock = stockPool();
    if (!stock.length) return GENERIC_IMGS.slice(0, 6);
    // Rotated window of 9 — demo i starts 3 slots later than demo i-1.
    const pool = [];
    for (let k = 0; k < 9; k++) pool.push(stock[(demoIdx * 3 + k * 5) % stock.length]);
    return pool;
}

/* ------------------------------------------------ shared partials -------- */

function head(cfg, page, title, desc) {
    const fav = cfg.logo ? `    <link rel="icon" type="image/png" href="Img/${cfg.logo}" />\n` : '';
    return `<!-- ARC Starter Templates: bundled template document. Edit this file directly, then re-import in wp-admin. -->
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>${title}</title>
    <meta name="description" content="${desc}" />
${fav}    <script src="Js/site.js" defer></script>
    <link rel="stylesheet" href="Css/tailwind.css" onerror="this.onerror=null;var s=document.createElement('script');s.src='https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4';document.head.appendChild(s);" />
${cfg.fonts.map((f) => '    ' + f).join('\n')}
${cfg.style}
  </head>
  <body class="font-sans text-slate-600 antialiased">
${cfg.root ? '    ' + cfg.root + '\n' : ''}`;
}

function logoMark(cfg) {
    if (cfg.logo) {
        return `<img src="Img/${cfg.logo}" alt="${cfg.name}" class="h-9 w-9 shrink-0 object-contain sm:h-10 sm:w-10" />`;
    }
    return `<span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-accent font-display text-sm font-bold text-navy sm:h-10 sm:w-10">${cfg.name[0]}</span>`;
}

function header(cfg, active) {
    const l1 = cfg.l1 || cfg.name.toUpperCase();
    const l2 = cfg.l2 || 'TALENT · SYSTEMS · PLAYBOOKS';
    const links = NAV.map(([slug, label]) => {
        const cls = slug === active ? 'font-semibold text-white' : 'transition hover:text-white';
        return `          <a href="${cfg.key}-${slug}.html" class="${cls}">${label}</a>`;
    }).join('\n');
    const mLinks = NAV.map(([slug, label], i) => {
        const base = 'rounded-lg px-3 py-2.5 transition hover:bg-white/5 hover:text-white';
        const cls = slug === active ? `${base} font-semibold text-white` : base;
        return `          <a href="${cfg.key}-${slug}.html" class="${cls}${i < NAV.length - 1 ? ' border-b border-white/10' : ''}">${label}</a>`;
    }).join('\n');
    return `    <!-- ===== Header ===== -->
    <header class="sticky top-0 z-50 border-b border-white/10 bg-navy/95 backdrop-blur" data-reveal="down">
      <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 py-3 lg:px-8">
        <a href="${cfg.key}-home.html" class="flex min-w-0 shrink-0 items-center gap-2.5">
          ${logoMark(cfg)}
          <span class="hidden min-[400px]:block leading-none">
            <span class="block text-[15px] font-extrabold tracking-wide text-white font-display">${l1}</span>
            <span class="block text-[10px] font-bold tracking-[0.3em] text-brand">${l2}</span>
          </span>
        </a>
        <nav class="hidden items-center gap-3 whitespace-nowrap text-[12px] font-medium text-slate-300 lg:flex xl:gap-5 xl:text-[13px]">
${links}
        </nav>
        <button type="button" class="hidden lg:inline-flex items-center justify-center rounded-full p-2 text-slate-400 transition hover:bg-white/10 hover:text-white" aria-label="Toggle dark mode" data-arc-dark-toggle>
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
        </button>
        <a href="${cfg.key}-partners.html" class="btn-primary hidden lg:inline-flex">Build Your Team</a>
        <button type="button" class="inline-flex items-center justify-center rounded-lg p-2 text-slate-300 transition hover:bg-white/10 hover:text-white lg:hidden" aria-label="Toggle menu" aria-expanded="false" aria-controls="mnav" data-arc-nav-toggle>
          <svg data-arc-icon="open" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
          <svg data-arc-icon="close" class="hidden h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
      </div>
      <div id="mnav" class="hidden border-t border-white/10 bg-navy lg:hidden">
        <nav class="flex flex-col px-4 sm:px-6 py-3 text-[15px] font-medium text-slate-200">
${mLinks}
          <a href="${cfg.key}-partners.html" class="btn-primary mt-2 mb-3 justify-center">Build Your Team</a>
        </nav>
      </div>
    </header>
`;
}

function crumb(cfg, label) {
    return `        <p class="mt-8 text-sm text-slate-400" data-reveal="fade" data-reveal-delay="360">
          <a href="${cfg.key}-home.html" class="font-medium text-brand transition hover:text-white">Home</a>
          <span class="mx-2 text-slate-500">/</span> ${label}
        </p>`;
}

function pageHero(cfg, label, h1, sub, btnText, btnHref) {
    const btn = btnText ? `        <a href="${btnHref}" class="btn-primary mt-8${1 === cfg.heroV ? ' mx-auto' : ''}" data-reveal data-reveal-delay="240">${btnText}</a>\n` : '';
    const blobs = `      <div class="pointer-events-none absolute -top-40 -right-40 h-[420px] w-[420px] rounded-full bg-brand/25 blur-3xl motion-safe:animate-pulse-slow"></div>
      <div class="pointer-events-none absolute -bottom-44 -left-32 h-[380px] w-[380px] rounded-full bg-accent/15 blur-3xl motion-safe:animate-float-slow"></div>`;
    if (1 === cfg.heroV) {
        // Centered editorial hero.
        return `    <!-- ===== Page hero ===== -->
    <section class="relative overflow-hidden bg-navy">
${blobs}
      <div class="relative mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 sm:py-20 lg:py-24">
        <h1 class="text-3xl font-bold text-white font-display sm:text-5xl" data-reveal>${h1}</h1>
        <p class="mx-auto mt-5 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg" data-reveal data-reveal-delay="120">${sub}</p>
${btn}${crumb(cfg, label)}
      </div>
    </section>
`;
    }
    if (2 === cfg.heroV) {
        // Split hero — copy left, image card right.
        return `    <!-- ===== Page hero ===== -->
    <section class="relative overflow-hidden bg-navy">
${blobs}
      <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-2 lg:px-8 lg:py-24">
        <div>
          <h1 class="max-w-xl text-3xl font-bold text-white font-display sm:text-5xl" data-reveal>${h1}</h1>
          <p class="mt-5 max-w-xl text-base leading-8 text-slate-300 sm:text-lg" data-reveal data-reveal-delay="120">${sub}</p>
${btn}${crumb(cfg, label)}
        </div>
        <div class="relative" data-reveal="right" data-reveal-delay="200">
          ${img(cfg, 2, label, 'mx-auto w-full max-w-md rounded-3xl object-cover shadow-xl')}
        </div>
      </div>
    </section>
`;
    }
    return `    <!-- ===== Page hero ===== -->
    <section class="relative overflow-hidden bg-navy">
${blobs}
      <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
        <h1 class="max-w-3xl text-3xl font-bold text-white font-display sm:text-5xl" data-reveal>${h1}</h1>
        <p class="mt-5 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg" data-reveal data-reveal-delay="120">${sub}</p>
${btn}${crumb(cfg, label)}
      </div>
    </section>
`;
}

/* Per-page final CTA — restructured ARC copy (V2/V3 blend, operator voice). */
const CTA_COPY = {
    home: ['Stop Hiring Around Broken Processes.', 'Get the people and operating structure your business needs to execute.', 'Build Your Team'],
    about: ['Show Us the Work That Should Run Better.', 'We will help determine whether the answer is talent, process, automation, or a combination.', 'Talk to ARC'],
    services: ['What Is the Most Expensive Work Still Being Done Manually?', 'Bring us the role, backlog, or broken workflow. We will help define the right operating response.', 'Talk to ARC'],
    'virtual-services': ['What Keeps Showing Up on Your To-Do List?', 'If it is recurring, necessary, and pulling you away from higher-value work, it may be ready to delegate.', 'Find My Virtual Professional'],
    playbooks: ['Ready in Days. Not Months.', 'Tell us the role, the responsibilities and the timeline. Qualified Latin American professionals — fluent English, your time zone — may be available within seven days.', 'Build Your Team'],
    foundation: ['Want to Participate in the Work?', 'Connect with the Foundation to learn about approved initiatives, partnerships, and ways to contribute.', 'Connect With the Foundation'],
    partners: ['Have a Client With an Operating Gap?', 'Send the introduction. We will quickly determine whether ARC is the right fit.', 'Become an ARC Partner'],
};

function cta(cfg, page) {
    const [h, p, b] = CTA_COPY[page] || CTA_COPY.home;
    if (1 === cfg.ctaV) {
        // Centered statement CTA.
        return `    <!-- ===== CTA ===== -->
    <section class="bg-navy">
      <div class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 sm:py-20 lg:px-8">
        <h2 class="text-3xl font-bold text-white font-display sm:text-4xl" data-reveal>${h}</h2>
        <p class="mx-auto mt-4 max-w-xl leading-7 text-slate-300" data-reveal data-reveal-delay="120">${p}</p>
        <a href="${cfg.key}-partners.html" class="btn-primary mx-auto mt-8" data-reveal data-reveal-delay="240">${b}</a>
      </div>
    </section>
`;
    }
    if (2 === cfg.ctaV) {
        // Navy card inside a light frame.
        return `    <!-- ===== CTA ===== -->
    <section class="bg-cream">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 py-14 lg:px-8">
        <div class="flex flex-col items-start gap-6 rounded-3xl bg-navy p-10 md:flex-row md:items-center md:justify-between lg:p-14">
          <div data-reveal="left">
            <h2 class="text-3xl font-bold text-white font-display sm:text-4xl">${h}</h2>
            <p class="mt-3 max-w-xl leading-7 text-slate-300">${p}</p>
          </div>
          <a href="${cfg.key}-partners.html" class="btn-primary shrink-0" data-reveal="right" data-reveal-delay="150">${b}</a>
        </div>
      </div>
    </section>
`;
    }
    return `    <!-- ===== CTA ===== -->
    <section class="bg-navy">
      <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-4 sm:px-6 py-14 md:flex-row md:items-center lg:px-8">
        <div data-reveal="left">
          <h2 class="text-3xl font-bold text-white font-display sm:text-4xl">${h}</h2>
          <p class="mt-3 max-w-xl leading-7 text-slate-300">${p}</p>
        </div>
        <a href="${cfg.key}-partners.html" class="btn-primary shrink-0" data-reveal="right" data-reveal-delay="150">${b}</a>
      </div>
    </section>
`;
}

/* Step-flow: chips joined by arrows (PERSON → … → EXECUTION).
   Vertical on gridV 2 demos for a different structural feel. */
function flowRow(items, dark, cfg) {
    const chip = dark
        ? 'border-white/15 bg-white/5 text-white'
        : 'border-slate-200 bg-white text-navy shadow-sm';
    const vertical = cfg && 2 === cfg.gridV;
    const arrow = `<svg class="h-4 w-4 shrink-0 text-brand${vertical ? ' rotate-90' : ''}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>`;
    const wrap = vertical ? 'mt-8 flex flex-col items-start gap-3' : 'mt-8 flex flex-wrap items-center gap-x-3 gap-y-4';
    return `<div class="${wrap}" data-reveal-stagger="80">`
        + items.map((t) => `<span class="inline-flex items-center rounded-xl border px-4 py-2 text-sm font-semibold ${chip}">${t}</span>`).join('\n              ' + arrow + '\n              ')
        + `</div>`;
}

function footer(cfg) {
    const year = new Date().getFullYear();
    return `    <!-- ===== Footer ===== -->
    <footer class="bg-navy text-slate-300">
      <div class="mx-auto grid max-w-7xl gap-10 border-t border-white/10 px-4 sm:px-6 py-16 md:grid-cols-2 lg:grid-cols-4 lg:px-8" data-reveal-stagger="120">
        <div>
          <a href="${cfg.key}-home.html" class="flex items-center gap-2.5">
            ${logoMark(cfg)}
            <span class="leading-none">
              <span class="block text-[15px] font-extrabold tracking-wide text-white font-display">${cfg.l1 || cfg.name.toUpperCase()}</span>
              <span class="block text-[10px] font-bold tracking-[0.3em] text-brand">${cfg.l2 || 'TALENT · SYSTEMS · PLAYBOOKS'}</span>
            </span>
          </a>
          <p class="mt-5 text-sm leading-6 text-slate-400">Trained Latin American professionals — fluent English, your time zone — plus the documented systems that make their work repeatable.</p>
        </div>
        <div>
          <h3 class="text-sm font-bold uppercase tracking-wider text-white">Site</h3>
          <ul class="mt-5 space-y-3 text-sm">
            <li><a href="${cfg.key}-home.html" class="transition hover:text-white">Home</a></li>
            <li><a href="${cfg.key}-about.html" class="transition hover:text-white">About</a></li>
            <li><a href="${cfg.key}-foundation.html" class="transition hover:text-white">Foundation</a></li>
            <li><a href="${cfg.key}-partners.html" class="transition hover:text-white">Partners</a></li>
          </ul>
        </div>
        <div>
          <h3 class="text-sm font-bold uppercase tracking-wider text-white">What we do</h3>
          <ul class="mt-5 space-y-3 text-sm">
            <li><a href="${cfg.key}-services.html" class="transition hover:text-white">Services</a></li>
            <li><a href="${cfg.key}-virtual-services.html" class="transition hover:text-white">Virtual Services</a></li>
            <li><a href="${cfg.key}-playbooks.html" class="transition hover:text-white">Playbooks</a></li>
          </ul>
        </div>
        <div>
          <h3 class="text-sm font-bold uppercase tracking-wider text-white">Get started</h3>
          <p class="mt-5 text-sm leading-6 text-slate-400">Qualified professionals in as little as 7 days. Documented playbooks within 90.</p>
          <a href="${cfg.key}-partners.html" class="btn-primary mt-5">Start a Conversation</a>
        </div>
      </div>
      <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 sm:px-6 py-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between lg:px-8">
          <p>© ${year} ${cfg.name}. All rights reserved.</p>
          <p>People. Process. Playbooks.</p>
        </div>
      </div>
    </footer>
  </body>
</html>
`;
}

function check(text) {
    return `<li class="flex gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-brand" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span class="text-sm leading-6">${text}</span>
              </li>`;
}

function statCard(v, l) {
    const m = String(v).match(/^(\d+)\s*(.*)$/);
    const val = m ? `<span data-count="${m[1]}">${m[1]}</span>${m[2] ? ' ' + m[2] : ''}` : v;
    return `<div class="rounded-xl border border-gray-200 bg-white p-5 text-center shadow-sm card-lift">
            <p class="font-display text-2xl font-bold text-navy">${val}</p>
            <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">${l}</p>
          </div>`;
}

const img = (cfg, n, alt, cls) => `<img src="Img/${cfg.pool[n % cfg.pool.length]}" alt="${alt}" class="${cls}" loading="lazy" />`;

/* ------------------------------------------------ page bodies ------------ */

function homeHero(cfg) {
    const blobs = `      <div class="pointer-events-none absolute -top-40 -right-40 h-[480px] w-[480px] rounded-full bg-brand/25 blur-3xl motion-safe:animate-pulse-slow"></div>
      <div class="pointer-events-none absolute -bottom-52 -left-32 h-[420px] w-[420px] rounded-full bg-accent/15 blur-3xl motion-safe:animate-float-slow"></div>`;
    const chips = ['Fluent English', 'Your time zone', 'Ready in 7 days']
        .map((t) => `<span class="flex items-center gap-2 text-sm font-semibold text-white"><svg class="h-4 w-4 text-brand" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>${t}</span>`)
        .join('\n            ');
    const badge = `          <div class="absolute bottom-6 left-2 rounded-2xl bg-white p-5 shadow-xl sm:left-6 motion-safe:animate-float">
            <p class="text-3xl font-bold text-navy font-display"><span data-count="90">90</span> <span class="text-brand">days</span></p>
            <p class="mt-1 text-sm font-medium text-slate-500">to a documented, repeatable role</p>
          </div>`;

    if (1 === cfg.heroV) {
        // Centered hero — copy stacked, wide media below.
        return `    <!-- ===== Hero ===== -->
    <section class="relative overflow-hidden bg-navy">
${blobs}
      <div class="relative mx-auto max-w-3xl px-4 pt-20 text-center sm:px-6 lg:px-8 lg:pt-28">
        <h1 class="text-4xl leading-tight font-bold text-white font-display sm:text-5xl lg:text-6xl" data-reveal>${cfg.h1}</h1>
        <p class="mx-auto mt-6 max-w-xl text-lg leading-8 text-slate-300" data-reveal data-reveal-delay="120">${SUB(cfg)}</p>
        <div class="mt-9 flex flex-wrap items-center justify-center gap-4" data-reveal data-reveal-delay="240">
          <a href="${cfg.key}-partners.html" class="btn-primary">Build Your Team</a>
          <a href="${cfg.key}-services.html" class="btn-ghost-light">Explore Our Services
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
          </a>
        </div>
        <div class="mt-9 flex flex-wrap items-center justify-center gap-x-6 gap-y-3" data-reveal data-reveal-delay="360">
          ${chips}
        </div>
      </div>
      <div class="relative mx-auto max-w-5xl px-4 pb-20 sm:px-6 lg:px-8" data-reveal="up" data-reveal-delay="200">
        ${img(cfg, 0, `${cfg.name} team`, 'w-full rounded-3xl object-cover shadow-2xl')}
        <div class="hidden sm:block">${badge.replace('absolute bottom-6 left-2', 'absolute -bottom-6 left-8')}</div>
      </div>
    </section>`;
    }

    // v0: media right · v2: media left (mirrored split).
    return `    <!-- ===== Hero ===== -->
    <section class="relative overflow-hidden bg-navy">
${blobs}
      <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 py-20 lg:grid-cols-2 lg:px-8 lg:py-28">
        <div${clsA(cfg)}>
          <h1 class="text-4xl leading-tight font-bold text-white font-display sm:text-5xl lg:text-6xl" data-reveal="${dirA(cfg)}">${cfg.h1}</h1>
          <p class="mt-6 max-w-xl text-lg leading-8 text-slate-300" data-reveal="${dirA(cfg)}" data-reveal-delay="120">${SUB(cfg)}</p>
          <div class="mt-9 flex flex-wrap items-center gap-4" data-reveal="${dirA(cfg)}" data-reveal-delay="240">
            <a href="${cfg.key}-partners.html" class="btn-primary">Build Your Team</a>
            <a href="${cfg.key}-services.html" class="btn-ghost-light">Explore Our Services
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
          </div>
          <div class="mt-9 flex flex-wrap items-center gap-x-6 gap-y-3" data-reveal="${dirA(cfg)}" data-reveal-delay="360">
            ${chips}
          </div>
        </div>
        <div class="relative ${ordB(cfg)}" data-reveal="${dirB(cfg)}" data-reveal-delay="200">
          ${img(cfg, 0, `${cfg.name} team`, 'mx-auto w-full max-w-lg rounded-3xl object-cover')}
          ${badge}
        </div>
      </div>
    </section>`;
}

/* Home section orders — SolaceWP-style archetypes (trusted-strip first,
   stats early, why-us first, method-led…). Same copy, different skeleton. */
const HOME_ORDERS = [
    ['sol', 'speed', 'dif', 'mod', 'lat', 'aut', 'sta', 'par'],  // canonical (arc-site)
    ['par', 'dif', 'sol', 'speed', 'sta', 'mod', 'aut', 'lat'],  // printifix: partners strip after hero
    ['sol', 'sta', 'dif', 'mod', 'par', 'speed', 'lat', 'aut'],  // autorun: stats early
    ['dif', 'par', 'sol', 'mod', 'sta', 'lat', 'aut', 'speed'],  // morphix: why-us first
    ['speed', 'sol', 'mod', 'dif', 'par', 'aut', 'lat', 'sta'],  // banner-led
    ['sta', 'dif', 'sol', 'speed', 'mod', 'par', 'lat', 'aut'],  // counters up top
    ['mod', 'dif', 'sta', 'sol', 'speed', 'lat', 'par', 'aut'],  // model-first
];

function homeBody(cfg) {
    const SEC = {
        /* ===== Two primary solutions ===== */
        sol: `    <section class="bg-white">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 py-20 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
          <p class="eyebrow">What we do</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">The People You Need. The Systems Behind Them.</h2>
        </div>
        <div class="mt-14 grid gap-8 ${grid2(cfg)}" data-reveal-stagger="150">
          ${[
            ['Finance &amp; Accounting Talent', 'From transactional support through controller-level capacity — general accounting, AR/AP, reconciliations, month-end close and reporting.', 'Find Accounting Talent', `${cfg.key}-services.html`, 1],
            ['Virtual Assistants', 'Executive support, operations, client coordination and back-office execution — recurring work owned end to end, with a playbook behind it.', 'Find Your VA', `${cfg.key}-virtual-services.html`, 2],
          ].map(([h, p, a, href, i]) => `          <div class="group flex flex-col overflow-hidden rounded-2xl bg-slate-50 shadow-sm ring-1 ring-slate-100 card-lift">
            ${img(cfg, i, h, 'h-56 w-full object-cover')}
            <div class="flex flex-1 flex-col p-8">
              <h3 class="text-2xl font-bold text-navy font-display">${h}</h3>
              <p class="mt-3 flex-1 leading-7">${p}</p>
              <a href="${href}" class="btn-primary mt-6 w-fit">${a}</a>
            </div>
          </div>`).join('\n')}
        </div>
        <p class="mt-10 text-center text-sm font-medium text-slate-500" data-reveal>
          Systems &amp; Playbooks —
          <a href="${cfg.key}-playbooks.html" class="font-semibold text-brand transition hover:text-navy">the mechanism that makes both repeatable →</a>
        </p>
      </div>
    </section>`,
        /* ===== Speed banner ===== */
        speed: `    <section style="background:linear-gradient(135deg,#59B8A9 0%,#62A65F 55%,#7FB34D 100%)">
      <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-4 sm:px-6 py-14 md:flex-row md:items-center lg:px-8">
        <div data-reveal="left">
          <h2 class="text-3xl font-bold text-navy font-display sm:text-4xl">Ready in Days. Not Months.</h2>
          <p class="mt-3 max-w-xl leading-7 text-navy/85">As little as 7 days to staff a clear role. ${cfg.name} maintains a bench of trained professionals so adding capacity never takes a full recruiting cycle.</p>
        </div>
        <a href="${cfg.key}-partners.html" class="inline-flex shrink-0 items-center gap-2 rounded-full bg-white px-7 py-3.5 text-sm font-semibold text-navy transition hover:bg-slate-100" data-reveal="right" data-reveal-delay="150">Build Your Team</a>
      </div>
    </section>`,
        /* ===== System differentiator ===== */
        dif: `    <section class="bg-white">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 py-20 lg:px-8 lg:py-28">
        <div class="grid items-center gap-12 ${splitCols(cfg)}">
          <div class="relative ${ordA(cfg)}" data-reveal="${dirA(cfg)}">
            ${img(cfg, 4, 'Documented processes', 'w-full rounded-3xl object-cover')}
            <div class="absolute -bottom-6 -right-4 hidden rounded-2xl bg-navy p-6 text-white shadow-xl sm:block motion-safe:animate-float">
              <p class="text-3xl font-bold font-display"><span class="text-accent" data-count="90">90</span> days</p>
              <p class="mt-1 text-sm">to a documented, repeatable role</p>
            </div>
          </div>
          <div${clsB(cfg)} data-reveal="${dirB(cfg)}" data-reveal-delay="120">
            <p class="eyebrow">The difference</p>
            <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">People Are Only Half the Solution.</h2>
            <p class="mt-5 leading-7">A person can absorb work. A documented system keeps that work from becoming a new dependency. Within the first 90 days, we document the recurring workflows behind the role — reconciliations, AR, AP, reporting, approvals and other critical operations.</p>
            <p class="mt-4 leading-7">You get a person who can execute and a process your company can repeat — hosted in your own <a href="${cfg.key}-playbooks.html" class="font-semibold text-brand transition hover:text-navy">playbooks intranet</a>.</p>
            ${flowRow(['Person', 'Responsibilities', 'Workflow', 'Playbook', 'Repeatable Execution'], false, cfg)}
          </div>
        </div>
      </div>
    </section>`,
        /* ===== ARC operating model ===== */
        mod: `    <section class="bg-cream">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 py-20 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
          <p class="eyebrow">The ARC operating model</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">Talent + Process + Accountability.</h2>
        </div>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 ${grid3(cfg)}" data-reveal-stagger="100">
          ${[
            ['Talent', 'The right person in the seat — fluent English, your working hours, inside your existing team.'],
            ['Process', 'Every recurring task documented into a clear, repeatable playbook.'],
            ['Accountability', 'Clear ownership. The work is managed, measured and improved — not just done.'],
          ].map(([h, p]) => `          <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-100 card-lift">
            <h3 class="text-lg font-bold text-navy font-display">${h}</h3>
            <p class="mt-3 text-sm leading-6">${p}</p>
          </div>`).join('\n')}
        </div>
      </div>
    </section>`,
        /* ===== LATAM ===== */
        lat: `    <section class="bg-white">
      <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 py-20 ${splitCols(cfg)} lg:px-8 lg:py-28">
        <div${clsA(cfg)} data-reveal="${dirA(cfg)}">
          <p class="eyebrow">Latin American talent</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">Your Time Zone. Fluent English. No Compromise.</h2>
          <p class="mt-5 leading-7">Our LATAM professionals are selected for fluent English, compatible U.S. working hours, and the ability to operate inside your existing team. Real collaboration — not task dumping across time zones.</p>
          <ul class="mt-7 space-y-4" data-reveal-stagger="90">
            ${check('<strong class="font-semibold text-navy">Same-day overlap</strong> — full U.S. time-zone coverage, not overnight handoffs')}
            ${check('<strong class="font-semibold text-navy">Fluent English</strong> — written and spoken, client-facing ready')}
            ${check('<strong class="font-semibold text-navy">Trained before day one</strong> — the <a href="${cfg.key}-foundation.html" class="font-semibold text-brand">Foundation</a> prepares talent on tools, English and playbook discipline')}
          </ul>
        </div>
        <div${clsB(cfg)} data-reveal="${dirB(cfg)}" data-reveal-delay="150">
          ${img(cfg, 5, 'Latin American professionals collaborating', 'w-full rounded-3xl object-cover shadow-xl')}
        </div>
      </div>
    </section>`,
        /* ===== Automation ===== */
        aut: `    <section class="bg-navy">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 py-20 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
          <p class="eyebrow">Automation</p>
          <h2 class="mt-3 text-3xl font-bold text-white font-display sm:text-4xl">Automation That Protects Time and Margin.</h2>
          <p class="mt-4 leading-7 text-slate-300">Once the process is clear, we identify the manual steps that software should be doing instead of people. Less repetition, fewer errors, more output from the same capacity.</p>
        </div>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 ${grid3(cfg)}" data-reveal-stagger="100">
          ${[
            ['Identify', 'Every playbook review surfaces tasks that repeat on a schedule — the best candidates for automation.'],
            ['Implement', 'We connect the tools you already run — forms, spreadsheets, CRM, email — so data moves without retyping.'],
            ['Improve', 'Documented processes are measurable processes. We refine the workflow, then the automation, every quarter.'],
          ].map(([h, p]) => `          <div class="rounded-2xl border border-white/10 bg-white/5 p-7">
            <h3 class="text-lg font-bold text-white font-display">${h}</h3>
            <p class="mt-2 text-sm leading-6 text-slate-300">${p}</p>
          </div>`).join('\n')}
        </div>
      </div>
    </section>`,
        /* ===== Proof points ===== */
        sta: `    <section class="bg-white">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 py-16 lg:px-8">
        <div class="grid grid-cols-2 gap-4 ${grid4(cfg)}" data-reveal-stagger="90">
          ${statCard('7 days', 'To staff a clear role')}
          ${statCard('90 days', 'Playbook & SOP window')}
          ${statCard('LATAM', 'U.S. time-zone aligned')}
          ${statCard('Fluent', 'English — direct collaboration')}
        </div>
      </div>
    </section>`,
        /* ===== Partners strip ===== */
        par: `    <section class="bg-cream">
      <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-4 sm:px-6 py-14 md:flex-row md:items-center lg:px-8">
        <div${clsA(cfg)} data-reveal="${dirA(cfg)}">
          <p class="eyebrow">Partners</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">Built to Support Your Advisors Too.</h2>
          <p class="mt-3 max-w-xl leading-7">Fractional CFOs, accounting firms, consultants and operating partners use ${cfg.name} to give clients execution capacity — you keep the relationship, we help build the team.</p>
        </div>
        <a href="${cfg.key}-partners.html" class="btn-outline shrink-0 ${ordB(cfg)}" data-reveal="${dirB(cfg)}" data-reveal-delay="150">Become an ARC Partner</a>
      </div>
    </section>`,
    };
    return `${homeHero(cfg)}\n${(cfg.seq || HOME_ORDERS[0]).map((k) => SEC[k]).join('\n\n')}\n${cta(cfg, 'home')}`;
}

function aboutBody(cfg) {
    return `${pageHero(cfg, 'About', 'We Build Teams That Can <span class="text-brand">Actually Execute.</span>', `${cfg.name} helps companies run better by combining embedded talent, documented processes, and practical automation.`, 'See How We Work', `${cfg.key}-services.html`)}
    <!-- ===== Story ===== -->
    <section class="bg-white">
      <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 py-20 ${splitCols(cfg)} lg:px-8 lg:py-24">
        <div${clsA(cfg)} data-reveal="${dirA(cfg)}">
          ${img(cfg, 0, `${cfg.name} team at work`, 'w-full rounded-3xl object-cover shadow-xl')}
        </div>
        <div${clsB(cfg)} data-reveal="${dirB(cfg)}" data-reveal-delay="120">
          <p class="eyebrow">The belief</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">The Problem Is Usually Bigger Than the Hire.</h2>
          <p class="mt-5 leading-7">A company can hire a strong accountant and still have a slow close. It can hire an assistant and still bury work in Slack. It can buy software and still run on spreadsheets. The missing layer is the operating system around the people.</p>
          <p class="mt-4 leading-7">${cfg.name} brings those pieces together — people, process, and practical automation — so the business runs on documentation and ownership, not memory.</p>
        </div>
      </div>
    </section>

    <!-- ===== Operating spine ===== -->
    <section class="bg-cream">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 py-20 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
          <p class="eyebrow">What we optimize for</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">Less Founder Dependency. More Operating Leverage.</h2>
        </div>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 ${grid4(cfg)}" data-reveal-stagger="100">
          ${[
            ['People', 'Fluent-English accounting and administrative professionals aligned with your working hours — embedded, not offshore.'],
            ['Playbooks', 'Recurring work documented into SOPs and playbooks the business owns — trained, reviewed, improved.'],
            ['Automation', 'Automate after you understand the work — remove the steps nobody should be repeating.'],
            ['Leverage', 'Less founder follow-up, less manual repetition, faster onboarding, clearer ownership.'],
          ].map(([h, p]) => `          <div class="rounded-2xl bg-white p-7 shadow-sm ring-1 ring-slate-100 card-lift">
            <h3 class="text-lg font-bold text-navy font-display">${h}</h3>
            <p class="mt-2 text-sm leading-6">${p}</p>
          </div>`).join('\n')}
        </div>
      </div>
    </section>

    <!-- ===== Foundation / Partners teasers ===== -->
    <section class="bg-white">
      <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 py-20 ${splitCols(cfg)} lg:px-8 lg:py-24" data-reveal-stagger="150">
        <div class="flex flex-col overflow-hidden rounded-2xl bg-navy p-10 card-lift">
          <p class="eyebrow">Foundation</p>
          <h3 class="mt-3 text-2xl font-bold text-white font-display">Opportunity shouldn't depend on geography.</h3>
          <p class="mt-4 flex-1 leading-7 text-slate-300">The ${cfg.name} Foundation trains Latin American professionals for sustainable remote careers — business English, modern tools and playbook discipline — then connects the best graduates to real roles.</p>
          <a href="${cfg.key}-foundation.html" class="btn-primary mt-8 w-fit">See the Foundation</a>
        </div>
        <div class="flex flex-col overflow-hidden rounded-2xl border border-slate-100 bg-slate-50 p-10 card-lift">
          <p class="eyebrow">Partners</p>
          <h3 class="mt-3 text-2xl font-bold text-navy font-display">You keep the relationship. We help build the team.</h3>
          <p class="mt-4 flex-1 leading-7">Firms, agencies and platforms partner with ${cfg.name} to give their clients trained people plus documented systems — under their own brand, powered by ours.</p>
          <a href="${cfg.key}-partners.html" class="btn-primary mt-8 w-fit">Become a Partner</a>
        </div>
      </div>
    </section>
${cta(cfg, 'about')}`;
}

function servicesBody(cfg) {
    const blocks = [
        ['Finance &amp; Accounting Talent', 'Put the right level of finance talent in the seat — scale the team based on the work instead of carrying unnecessary overhead.',
            'General Accountant · Senior Accountant · AR/AP Specialist · Accounting Manager · Controller · Fractional Controller',
            ['From transactional support through controller-level capacity', 'Fluent English, U.S. working hours, inside your existing systems', 'Ready in as little as 7 days when the fit is clear'], 1],
        ['Virtual Assistants', 'Recurring administrative and operational work owned end to end — with a documented process behind every seat.',
            'Executive Support · Sales Support · Operations Support · Client Support',
            ['Recurring tasks become documented workflows', 'Manage, measure, transfer and scale the role', 'Ready to start in as little as 7 days'], 2],
        ['Systems &amp; Playbooks', 'Make the process transferable. Recurring work converted into clear SOPs and playbooks — hosted in your own intranet.',
            'SOP development · finance playbooks · process mapping · automation identification',
            ['First 90 days: document the workflows behind the role', 'The business keeps the operating knowledge — not trapped in one employee\'s head', 'Automations flagged where software beats manual work'], 3],
    ];
    return `${pageHero(cfg, 'Services', 'Install the Back Office <span class="text-brand">You Actually Need.</span>', `${cfg.name} builds support around the work — not around a fixed package. Add accounting capacity, administrative execution, documented processes, automation, or a combination.`, 'Build Your Support Plan', `${cfg.key}-partners.html`)}
    <div class="flex flex-col">
    <!-- ===== Service blocks ===== -->
    <section class="bg-white${cfg.swap ? ' order-2' : ''}">
      <div class="mx-auto max-w-7xl space-y-24 px-4 sm:px-6 py-20 lg:px-8 lg:py-28">
        ${blocks.map(([h, lead, tags, pts, i], idx) => `        <div class="grid items-center gap-12 ${splitCols(cfg)}">
          <div class="${((idx + (cfg.flip ? 1 : 0)) % 2) ? 'lg:order-2' : ''}" data-reveal="left">
            ${img(cfg, i, h.replace(/&amp;/g, '&'), 'w-full rounded-3xl object-cover shadow-xl')}
          </div>
          <div class="${((idx + (cfg.flip ? 1 : 0)) % 2) ? 'lg:order-1' : ''}" data-reveal="right" data-reveal-delay="120">
            <p class="eyebrow">Service 0${idx + 1}</p>
            <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">${h}</h2>
            <p class="mt-5 leading-7">${lead}</p>
            <p class="mt-4 text-sm font-medium text-navy">${tags}</p>
            <ul class="mt-6 space-y-4" data-reveal-stagger="90">
              ${pts.map((p) => check(p)).join('\n              ')}
            </ul>
          </div>
        </div>`).join('\n')}
      </div>
    </section>

    <!-- ===== Method ===== -->
    <section class="bg-navy${cfg.swap ? ' order-1' : ''}">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 py-20 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
          <p class="eyebrow">How we work</p>
          <h2 class="mt-3 text-3xl font-bold text-white font-display sm:text-4xl">Diagnose. Install. Document. Improve.</h2>
          <p class="mt-4 leading-7 text-slate-300">The same model every time: map the work, install the capacity, document the process, then keep improving the system around it.</p>
        </div>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 ${grid4(cfg)}" data-reveal-stagger="120">
          ${[
            ['01 · Diagnose', 'Diagnose the constraint — map the role, the recurring tasks and the systems involved.'],
            ['02 · Install', 'Install the right capacity — the trained professional who runs the work inside your team.'],
            ['03 · Document', 'Document the recurring process into playbooks inside your own intranet.'],
            ['04 · Improve', 'Improve the system around the work — review, refine and automate quarter after quarter.'],
          ].map(([h, p]) => `          <div class="rounded-2xl border border-white/10 bg-white/5 p-8">
            <h3 class="font-display text-lg font-bold text-brand">${h}</h3>
            <p class="mt-3 text-sm leading-6 text-slate-300">${p}</p>
          </div>`).join('\n')}
        </div>
      </div>
    </section>
    </div>
${cta(cfg, 'services')}`;
}

function virtualBody(cfg) {
    return `${pageHero(cfg, 'Virtual Services', 'Get the Work <span class="text-brand">Off Your Plate.</span>', `${cfg.name} virtual professionals take administrative and operational work off leadership's plate — with documented workflows that make delegation easier to manage and easier to scale.`, 'Build a Virtual Support Role', `${cfg.key}-partners.html`)}
    <!-- ===== Scope ===== -->
    <section class="bg-white">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 py-20 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
          <p class="eyebrow">What they take off your plate</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">If It Repeats Every Week, It Should Not Live With the Founder.</h2>
        </div>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 ${grid3(cfg)}" data-reveal-stagger="90">
          ${[
            ['Executive Support', 'Calendar, inbox, meetings and follow-up — run in your hours, not overnight.'],
            ['Sales Support', 'CRM upkeep, pipeline follow-up and outreach coordination — nothing falls through.'],
            ['Operations Support', 'Recurring reports, task tracking, data management and process documentation.'],
            ['Client Support', 'Scheduling, status updates and routine client communication, handled.'],
            ['Reporting &amp; Follow-up', 'Recurring reports built, checked and delivered on schedule, every time.'],
            ['Documentation', 'Every recurring responsibility converted into a playbook as it settles.'],
          ].map(([h, p]) => `          <div class="rounded-2xl border border-slate-100 bg-slate-50 p-7 card-lift">
            <h3 class="text-lg font-bold text-navy font-display">${h}</h3>
            <p class="mt-2 text-sm leading-6">${p}</p>
          </div>`).join('\n')}
        </div>
      </div>
    </section>

    <!-- ===== Playbook behind every seat ===== -->
    <section class="bg-cream">
      <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 py-20 ${splitCols(cfg)} lg:px-8 lg:py-24">
        <div${clsA(cfg)} data-reveal="${dirA(cfg)}">
          <p class="eyebrow">Why it scales</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">Every Repeating Task Should Become Easier to Hand Off.</h2>
          <p class="mt-5 leading-7">An undocumented assistant is a single point of failure. During the first 90 days, recurring responsibilities are documented into practical SOPs and playbooks hosted in your own <a href="${cfg.key}-playbooks.html" class="font-semibold text-brand">playbooks intranet</a> — clearer training, better continuity, less dependence on verbal instructions.</p>
          ${flowRow(['Tasks', 'Role', 'Documented Process', 'Accountability'], false, cfg)}
          <ul class="mt-7 space-y-4" data-reveal-stagger="90">
            ${check('Coverage when someone is out — the process survives the person')}
            ${check('Onboard the next hire in days, not months')}
            ${check('Quality stays consistent as you add seats')}
          </ul>
        </div>
        <div${clsB(cfg)} data-reveal="${dirB(cfg)}" data-reveal-delay="150">
          ${img(cfg, 2, 'Virtual assistant documentation', 'w-full rounded-3xl object-cover shadow-xl')}
        </div>
      </div>
    </section>

    <!-- ===== Strip ===== -->
    <section style="background:linear-gradient(135deg,#59B8A9 0%,#62A65F 55%,#7FB34D 100%)">
      <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 py-14 text-navy ${grid3(cfg)}" data-reveal-stagger="100">
        ${[
            ['Fluent English', 'Written and spoken — built for direct collaboration with U.S. teams.'],
            ['Your time zone', 'Compatible U.S. working hours — no overnight handoffs.'],
            ['7 days', 'Qualified professionals, ready to start.'],
        ].map(([h, p]) => `        <div class="text-center sm:text-left">
          <h3 class="font-display text-xl font-bold">${h}</h3>
          <p class="mt-1 text-sm text-navy/85">${p}</p>
        </div>`).join('\n')}
      </div>
    </section>
${cta(cfg, 'virtual-services')}`;
}

function playbooksBody(cfg) {
    return `${pageHero(cfg, 'Playbooks', 'Your Processes, <span class="text-brand">Documented — and Actually Used.</span>', 'Every playbook we write for you lives in your own secure intranet portal — searchable, organized by process, and scoped per company. Not a folder of forgotten docs.', 'See It In Person', `${cfg.key}-partners.html`)}
    <!-- ===== Intranet overview ===== -->
    <section class="bg-white">
      <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 py-20 ${splitCols(cfg)} lg:px-8 lg:py-24">
        <div${clsA(cfg)} data-reveal="${dirA(cfg)}">
          <p class="eyebrow">The intranet</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">One portal for your whole operation.</h2>
          <p class="mt-5 leading-7">Your intranet is a branded, per-company workspace where your documented processes live next to the tools that run them. Each company, department or client gets its own space with its own permissions — employees see exactly what they need, nothing they shouldn't.</p>
          <ul class="mt-7 space-y-4" data-reveal-stagger="90">
            ${check('<strong class="font-semibold text-navy">Playbooks / SOP library</strong> — every documented process, one place')}
            ${check('<strong class="font-semibold text-navy">Per-company permissions</strong> — scoped access for staff, clients and partners')}
            ${check('<strong class="font-semibold text-navy">Your branding</strong> — your logo, your colors, even your domain')}
          </ul>
        </div>
        <div${clsB(cfg)} data-reveal="${dirB(cfg)}" data-reveal-delay="150">
          ${img(cfg, 3, 'Playbooks intranet workspace', 'w-full rounded-3xl object-cover shadow-xl')}
        </div>
      </div>
    </section>

    <!-- ===== Modules ===== -->
    <section class="bg-cream">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 py-20 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
          <p class="eyebrow">Inside the portal</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">More than documents — the workspace around them.</h2>
        </div>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 ${grid4(cfg)}" data-reveal-stagger="80">
          ${[
            ['📚 Playbooks / SOP', 'Step-by-step processes with a flat library view and a "processes" view that groups every playbook in execution order.'],
            ['📁 Projects &amp; Files', 'Work organized per company with the files attached to the process that uses them.'],
            ['🕐 Time Clock &amp; Reports', 'Hours, sprints and activity reporting tied to the people running your playbooks.'],
            ['📨 Requests &amp; Tickets', 'Access requests, troubleshooting tickets and internal communications — routed, logged, answered.'],
          ].map(([h, p]) => `          <div class="rounded-2xl bg-white p-7 shadow-sm ring-1 ring-slate-100 card-lift">
            <h3 class="text-base font-bold text-navy font-display">${h}</h3>
            <p class="mt-2 text-sm leading-6">${p}</p>
          </div>`).join('\n')}
        </div>
      </div>
    </section>

    <!-- ===== Organized ===== -->
    <section class="bg-white">
      <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 py-20 ${splitCols(cfg)} lg:px-8 lg:py-24">
        <div${clsA(cfg)} data-reveal="${dirA(cfg)}">
          ${img(cfg, 6, 'Playbook library organized by process', 'w-full rounded-3xl object-cover shadow-xl')}
        </div>
        <div${clsB(cfg)} data-reveal="${dirB(cfg)}" data-reveal-delay="120">
          <p class="eyebrow">Find it in seconds</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">Organized the way your team works.</h2>
          <ul class="mt-7 space-y-4" data-reveal-stagger="90">
            ${check('<strong class="font-semibold text-navy">Filter by tag</strong> — area, sub-area, role or category')}
            ${check('<strong class="font-semibold text-navy">Processes view</strong> — see every playbook of a workflow in order, e.g. "Recruiting" = 6 steps end to end')}
            ${check('<strong class="font-semibold text-navy">Roles &amp; permissions</strong> — the right playbook in front of the right seat')}
          </ul>
        </div>
      </div>
    </section>

    <!-- ===== How we build ===== -->
    <section class="bg-navy">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 py-20 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
          <p class="eyebrow">The 90-day build</p>
          <h2 class="mt-3 text-3xl font-bold text-white font-display sm:text-4xl">From tribal knowledge to living playbooks.</h2>
        </div>
        <div class="mt-14 grid gap-6 ${grid3(cfg)}" data-reveal-stagger="120">
          ${[
            ['01 · Capture', 'We sit with the people doing the work and map every recurring workflow — reconciliations, AR, AP, reporting, approvals.'],
            ['02 · Document', 'Each process becomes a playbook: steps, screenshots, owners and the tools involved — written in plain English.'],
            ['03 · Run &amp; improve', 'Playbooks go live in your intranet. They get used, measured and refined — and flagged for automation where software wins.'],
          ].map(([h, p]) => `          <div class="rounded-2xl border border-white/10 bg-white/5 p-8">
            <h3 class="font-display text-lg font-bold text-brand">${h}</h3>
            <p class="mt-3 text-sm leading-6 text-slate-300">${p}</p>
          </div>`).join('\n')}
        </div>
      </div>
    </section>
${cta(cfg, 'playbooks')}`;
}

function foundationBody(cfg) {
    return `${pageHero(cfg, 'Foundation', 'Opportunity Shouldn\'t <span class="text-brand">Depend on Geography.</span>', `The ${cfg.name} Foundation trains and prepares Latin American professionals for sustainable remote careers — then connects the best graduates to real roles.`, 'Partner With the Foundation', `${cfg.key}-partners.html`)}
    <!-- ===== Mission ===== -->
    <section class="bg-white">
      <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 py-20 ${splitCols(cfg)} lg:px-8 lg:py-24">
        <div${clsA(cfg)} data-reveal="${dirA(cfg)}">
          ${img(cfg, 5, 'Foundation students training', 'w-full rounded-3xl object-cover shadow-xl')}
        </div>
        <div${clsB(cfg)} data-reveal="${dirB(cfg)}" data-reveal-delay="120">
          <p class="eyebrow">The mission</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">Talent is everywhere. Training isn't.</h2>
          <p class="mt-5 leading-7">Latin America is home to educated accounting, finance, business and administrative professionals with the ability to succeed in remote careers. What they often lack is the bridge: business English, modern tools and the discipline of documented work.</p>
          <p class="mt-4 leading-7">The Foundation builds that bridge — and feeds the same talent bench our clients hire from.</p>
        </div>
      </div>
    </section>

    <!-- ===== Training ===== -->
    <section class="bg-cream">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 py-20 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
          <p class="eyebrow">The model</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">Train. Prepare. Connect. Support.</h2>
        </div>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 ${grid4(cfg)}" data-reveal-stagger="100">
          ${[
            ['Train', 'Business English, modern tools and the discipline of documented work — accounting and finance fundamentals included.'],
            ['Prepare', 'Portfolio-ready skills, remote professionalism and playbook discipline for real client work.'],
            ['Connect', 'Introductions to real roles — the same talent bench our clients hire from.'],
            ['Support', 'Ongoing mentorship and community as graduates grow into sustainable remote careers.'],
          ].map(([h, p]) => `          <div class="rounded-2xl bg-white p-7 shadow-sm ring-1 ring-slate-100 card-lift">
            <h3 class="text-lg font-bold text-navy font-display">${h}</h3>
            <p class="mt-2 text-sm leading-6">${p}</p>
          </div>`).join('\n')}
        </div>
      </div>
    </section>

    <!-- ===== Pipeline + subscribe ===== -->
    <section class="bg-white">
      <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 py-20 ${splitCols(cfg)} lg:px-8 lg:py-24">
        <div${clsA(cfg)} data-reveal="${dirA(cfg)}">
          <p class="eyebrow">The pipeline</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">From classroom to client team.</h2>
          <p class="mt-5 leading-7">Top graduates join the ${cfg.name} talent bench — the same pool our clients hire from. When you work with us, you're working with people the Foundation trained, measured and vouched for.</p>
          <a href="${cfg.key}-services.html" class="btn-primary mt-8">See Our Services</a>
        </div>
        <div class="rounded-2xl bg-navy p-8 sm:p-10 ${ordB(cfg)}" data-reveal="${dirB(cfg)}" data-reveal-delay="120">
          <h3 class="text-2xl font-bold font-display" style="color:var(--color-gold)">Stay in the loop.</h3>
          <p class="mt-3 text-sm leading-6 text-slate-300">Follow the Foundation's progress — new cohorts, graduate stories and partnership opportunities.</p>
          <form class="arc-st-subscribe mt-6 flex flex-col gap-3 sm:flex-row">
            <input type="email" name="email" required placeholder="Work email" class="field flex-1" />
            <button type="submit" class="btn-primary justify-center">Subscribe</button>
          </form>
        </div>
      </div>
    </section>
${cta(cfg, 'foundation')}`;
}

function partnersBody(cfg) {
    return `${pageHero(cfg, 'Partners', 'You Keep the Relationship. <span class="text-brand">We Help Build the Team.</span>', `${cfg.name} partners with advisors and service providers who want a reliable place to send clients when finance, admin, process, or automation needs fall outside their core scope.`, 'Become an ARC Partner', '#contact-form')}
    <!-- ===== Who partners ===== -->
    <section class="bg-white">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 py-20 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
          <p class="eyebrow">Who we partner with</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">Different Expertise. Same Standard for the Client.</h2>
        </div>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 ${grid4(cfg)}" data-reveal-stagger="90">
          ${[
            ['Fractional CFOs', 'Give clients the accounting capacity behind your recommendations — without hiring yourself.'],
            ['Accounting firms', 'Staff client seats with fluent-English professionals working U.S. hours.'],
            ['Consultants &amp; agencies', 'Add execution capacity behind your strategy: admins, VAs and documented processes.'],
            ['Operating partners', 'A reliable place to send portfolio companies that need finance, admin or process support.'],
          ].map(([h, p]) => `          <div class="rounded-2xl border border-slate-100 bg-slate-50 p-7 card-lift">
            <h3 class="text-lg font-bold text-navy font-display">${h}</h3>
            <p class="mt-2 text-sm leading-6">${p}</p>
          </div>`).join('\n')}
        </div>
      </div>
    </section>

    <!-- ===== Benefits ===== -->
    <section class="bg-cream">
      <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 py-20 ${splitCols(cfg)} lg:px-8 lg:py-24">
        <div${clsA(cfg)} data-reveal="${dirA(cfg)}">
          <p class="eyebrow">What partners get</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">A Better Answer Than "We Don't Do That."</h2>
          <p class="mt-5 leading-7">When a client needs accounting capacity, virtual support, process documentation, or automation, introduce ${cfg.name} instead of leaving the need unresolved.</p>
          <ul class="mt-7 space-y-4" data-reveal-stagger="90">
            ${check('<strong class="font-semibold text-navy">A dedicated bench</strong> — trained professionals ready in as little as 7 days')}
            ${check('<strong class="font-semibold text-navy">Documented playbooks</strong> — every engagement documented inside a shared intranet')}
            ${check('<strong class="font-semibold text-navy">Automation roadmap</strong> — recurring work flagged for automation, saving your clients time and money')}
            ${check('<strong class="font-semibold text-navy">Trust first</strong> — clear scope and direct communication that protect the relationship you built')}
          </ul>
          ${flowRow(['Make the introduction', 'ARC confirms fit', 'We scope &amp; deliver', 'You keep the client'], false, cfg)}
        </div>
        <div${clsB(cfg)} data-reveal="${dirB(cfg)}" data-reveal-delay="150">
          ${img(cfg, 4, 'Partnership handshake', 'w-full rounded-3xl object-cover shadow-xl')}
        </div>
      </div>
    </section>

    <!-- ===== Contact split ===== -->
    <section class="bg-white">
      <div class="mx-auto grid max-w-7xl gap-14 px-4 py-16 sm:px-6 sm:py-20 ${splitCols(cfg)} lg:px-8 lg:py-24">
        <div${clsA(cfg)} data-reveal="${dirA(cfg)}">
          <p class="eyebrow">Get in touch</p>
          <h2 class="mt-3 text-3xl font-bold text-navy font-display sm:text-4xl">What Can We Help You Build?</h2>
          <p class="mt-5 leading-7">Tell us where your business needs more capacity — a role to staff, a process to document, a partnership to explore. We'll help determine the next step.</p>
          <div class="mt-10 space-y-6" data-reveal-stagger="120">
            <div class="rounded-2xl border border-brand/20 bg-brand/5 p-6">
              <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                  <h3 class="font-bold text-navy">Need Talent Quickly?</h3>
                  <p class="mt-2 text-sm leading-6">Tell us the role, responsibilities and timeline. Depending on the position, qualified candidates may be available within seven days.</p>
                </div>
              </div>
            </div>
            <div class="rounded-2xl bg-navy p-6 text-white">
              <h3 class="font-bold">The Right People. Clear Ownership. Repeatable Execution.</h3>
              <p class="mt-2 text-sm leading-6 text-slate-300">${cfg.name} puts the right people in the right seats — then builds the systems that make their work repeatable.</p>
            </div>
          </div>
        </div>

        <form id="contact-form" class="h-fit rounded-2xl border border-slate-100 bg-slate-50 p-6 shadow-sm sm:p-8 ${ordB(cfg)}" data-reveal="${dirB(cfg)}" data-reveal-delay="120">
          <div class="grid gap-5 sm:grid-cols-2">
            <div>
              <label for="first-name" class="mb-2 block text-sm font-semibold text-navy">First Name *</label>
              <input id="first-name" type="text" class="field" required />
            </div>
            <div>
              <label for="last-name" class="mb-2 block text-sm font-semibold text-navy">Last Name *</label>
              <input id="last-name" type="text" class="field" required />
            </div>
            <div>
              <label for="company" class="mb-2 block text-sm font-semibold text-navy">Company</label>
              <input id="company" type="text" class="field" />
            </div>
            <div>
              <label for="phone" class="mb-2 block text-sm font-semibold text-navy">Phone</label>
              <input id="phone" type="tel" class="field" />
            </div>
            <div class="sm:col-span-2">
              <label for="work-email" class="mb-2 block text-sm font-semibold text-navy">Work Email *</label>
              <input id="work-email" type="email" class="field" required />
            </div>
            <div class="sm:col-span-2">
              <label for="need" class="mb-2 block text-sm font-semibold text-navy">What do you need help with?</label>
              <select id="need" class="field">
                <option>Accounting Talent</option>
                <option>Controller</option>
                <option>AR/AP Support</option>
                <option>Virtual Assistant</option>
                <option>Playbooks / SOP Documentation</option>
                <option>Build a Team</option>
                <option>Partnership</option>
                <option>Foundation</option>
                <option>Other</option>
              </select>
            </div>
            <div>
              <label for="count" class="mb-2 block text-sm font-semibold text-navy">How many people do you need?</label>
              <select id="count" class="field">
                <option>1</option>
                <option>2–3</option>
                <option>4–5</option>
                <option>6+</option>
              </select>
            </div>
            <div>
              <label for="when" class="mb-2 block text-sm font-semibold text-navy">When do you need support?</label>
              <select id="when" class="field">
                <option>Immediately</option>
                <option>Within 30 days</option>
                <option>Within 60–90 days</option>
                <option>Exploring options</option>
              </select>
            </div>
            <div class="sm:col-span-2">
              <label for="details" class="mb-2 block text-sm font-semibold text-navy">Tell us what you need</label>
              <textarea id="details" rows="5" class="field" placeholder="Role, responsibilities, systems, timeline..."></textarea>
            </div>
          </div>
          <button type="submit" class="btn-primary mt-6 w-full justify-center">Start The Conversation</button>
        </form>
        <div id="form-success" class="hidden h-fit rounded-2xl border border-brand/20 bg-brand/5 p-8 text-center sm:p-10" data-reveal="zoom">
          <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand/10 text-brand">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </div>
          <h3 class="mt-5 text-xl font-bold text-navy">Message received.</h3>
          <p class="mt-2 text-sm leading-6">Thanks for reaching out. We will review what you need and get back to you shortly.</p>
        </div>
      </div>
    </section>
${cta(cfg, 'partners')}`;
}

const BODIES = {
    home: homeBody,
    about: aboutBody,
    services: servicesBody,
    'virtual-services': virtualBody,
    playbooks: playbooksBody,
    foundation: foundationBody,
    partners: partnersBody,
};

const PAGE_META = (cfg) => ({
    home: [`${cfg.name} — Elite Talent. Bulletproof Systems.`, SUB(cfg)],
    about: [`About – ${cfg.name}`, `${cfg.name} combines embedded talent, documented processes and practical automation — teams that can actually execute.`],
    services: [`Services – ${cfg.name}`, 'Finance & accounting talent, virtual assistants and documented playbooks — support built around the work, not a fixed package.'],
    'virtual-services': [`Virtual Services – ${cfg.name}`, 'Trained virtual professionals who own recurring admin and operations work — with a documented process behind every seat.'],
    playbooks: [`Playbooks – ${cfg.name}`, 'Your documented processes live in a branded, per-company intranet — searchable, permissioned and always used.'],
    foundation: [`Foundation – ${cfg.name}`, `The ${cfg.name} Foundation trains and prepares Latin American professionals for sustainable remote careers — train, prepare, connect, support.`],
    partners: [`Partners – ${cfg.name}`, 'You keep the relationship — we help build the team. For CFOs, firms, consultants and operating partners.'],
});

/* ------------------------------------------------ run -------------------- */

const manifest = JSON.parse(fs.readFileSync(MANIFEST, 'utf8'));
const order = Object.keys(manifest.demos).filter((d) => DEMOS[d]);
const newTemplates = {};

for (const key of order) {
    const dir = path.join(TPL_DIR, key);
    if (!fs.existsSync(dir)) { console.warn(`skip ${key}: no dir`); continue; }
    const demo = manifest.demos[key];
    const oldHomeSlug = demo.home;
    const homeFile = manifest.templates[oldHomeSlug] ? manifest.templates[oldHomeSlug].file : null;
    const cfg = Object.assign({ key }, DEMOS[key]);
    const srcHome = homeFile && fs.existsSync(path.join(dir, homeFile))
        ? homeFile
        : fs.readdirSync(dir).find((f) => /home|index/.test(f)) || fs.readdirSync(dir)[0];
    Object.assign(cfg, skinOf(dir, srcHome));
    const di = order.indexOf(key);
    // Independent 3-cycles → all 21 demos get a unique structural combo;
    // arc-site (di=0) keeps the canonical editorial layout.
    cfg.heroV = di % VARIANTS;                    // hero shape
    cfg.gridV = Math.floor(di / 3) % VARIANTS;    // card-grid density
    cfg.ctaV  = Math.floor(di / 7) % VARIANTS;    // closing CTA shape
    cfg.flip  = 1 === Math.floor(di / 3) % 2;     // mirrored split sections
    cfg.swap  = 2 === cfg.ctaV;                   // alternate section order
    cfg.seq   = HOME_ORDERS[Math.floor(di / 3) % HOME_ORDERS.length]; // home section order
    cfg.h1 = ARC_H1;
    cfg.pool = imagePool(dir, key, cfg.logo, di, order.length);
    if (!cfg.pool.length) cfg.pool = GENERIC_IMGS.slice(0, 4);

    // Emit canonical pages; drop anything else.
    const keep = new Set(PAGES.map((p) => `${p}.html`));
    for (const f of fs.readdirSync(dir).filter((f) => f.endsWith('.html') && !keep.has(f))) {
        fs.unlinkSync(path.join(dir, f));
    }
    const meta = PAGE_META(cfg);
    for (const page of PAGES) {
        const [title, desc] = meta[page];
        const html = head(cfg, page, title, desc) + header(cfg, page) + BODIES[page](cfg) + footer(cfg);
        fs.writeFileSync(path.join(dir, `${page}.html`), html, 'utf8');
        const slug = `${key}-${page}`;
        newTemplates[slug] = {
            name: TPL_NAMES[page] || NAV.find(([s]) => s === page)[1],
            title,
            description: desc,
            file: `${key}/${page}.html`,
            thumb: `assets/img/${cfg.pool[PAGES.indexOf(page) % cfg.pool.length]}`,
        };
    }

    demo.desc = 'Talent & systems site — Home, About, Services, Virtual Services, Playbooks, Foundation, Partners.';
    demo.image = `assets/img/${cfg.pool.includes('file.webp') ? 'file.webp' : cfg.pool[0]}`;
    demo.home = `${key}-home`;
    demo.pages = PAGES.map((p) => `${key}-${p}`);
    demo.footer = [`${key}-about`, `${key}-services`, `${key}-playbooks`, `${key}-partners`];
    console.log(`${key}: 7 pages ✓ (pool ${cfg.pool.length} imgs)`);
}

manifest.generated = new Date().toISOString();
manifest.templates = newTemplates;
fs.writeFileSync(MANIFEST, JSON.stringify(manifest, null, 4) + '\n', 'utf8');
console.log('\nmanifest.json rewritten —', Object.keys(newTemplates).length, 'templates across', order.length, 'demos');
