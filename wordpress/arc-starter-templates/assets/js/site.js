// Shared UI behavior for ARC template pages — loaded with defer.
// Globals are prefixed (arcSt*) and everything else lives inside an IIFE so
// this file can never collide with another plugin or theme script.

// ---------------------------------------------------------------------------
// Mobile nav — data-attribute driven (data-arc-nav-toggle / #mnav) so it keeps
// working even after the block editor strips inline onclick handlers.
// Slide animation is done with inline styles — no CSS dependency.
// ---------------------------------------------------------------------------
(function () {
  function setupNav() {
    const nav = document.getElementById('mnav');
    const btn = document.querySelector('[data-arc-nav-toggle]');
    if (!nav || !btn || nav.dataset.arcBound) return;
    nav.dataset.arcBound = '1';

    const icons = btn.querySelectorAll('[data-arc-icon]');
    const setIcon = (open) => {
      icons.forEach((i) => i.classList.toggle('hidden', (i.dataset.arcIcon === 'close') !== open));
    };

    const setExpanded = (open) => btn.setAttribute('aria-expanded', String(open));

    const slide = (open) => {
      nav.style.overflow = 'hidden';
      nav.style.transition = 'max-height .28s ease, opacity .22s ease';
      if (open) {
        nav.classList.remove('hidden');
        nav.style.maxHeight = '0px';
        nav.style.opacity = '0';
        requestAnimationFrame(() => {
          nav.style.maxHeight = nav.scrollHeight + 'px';
          nav.style.opacity = '1';
        });
      } else {
        nav.style.maxHeight = nav.scrollHeight + 'px';
        requestAnimationFrame(() => {
          nav.style.maxHeight = '0px';
          nav.style.opacity = '0';
        });
        nav.addEventListener('transitionend', function done(e) {
          if (e.propertyName !== 'max-height') return;
          nav.removeEventListener('transitionend', done);
          nav.classList.add('hidden');
          nav.style.cssText = '';
        });
      }
      setExpanded(open);
      setIcon(open);
    };

    const isOpen = () => !nav.classList.contains('hidden');
    const toggle = () => slide(!isOpen());
    const close = () => { if (isOpen()) slide(false); };

    btn.addEventListener('click', (e) => { e.stopPropagation(); toggle(); });
    // Close on nav link tap, outside click, Escape, or resize to desktop.
    nav.addEventListener('click', (e) => { if (e.target.closest('a')) close(); });
    document.addEventListener('click', (e) => { if (!nav.contains(e.target) && !btn.contains(e.target)) close(); });
    document.addEventListener('keydown', (e) => { if ('Escape' === e.key) close(); });
    window.addEventListener('resize', () => { if (window.innerWidth >= 1024) close(); });
  }

  if ('loading' === document.readyState) {
    document.addEventListener('DOMContentLoaded', setupNav);
  } else {
    setupNav();
  }
})();

// Header dark-mode toggle — flips the `dark` class on <html> and remembers
// the choice. Kept global for pages imported before data-arc-dark-toggle.
function arcStToggleDark() {
  const dark = document.documentElement.classList.toggle('dark');
  try { localStorage.setItem('arcStDark', dark ? '1' : '0'); } catch (e) {}
}

document.addEventListener('click', (e) => {
  if (e.target.closest('[data-arc-dark-toggle]')) arcStToggleDark();
});

// Back-compat for older imports still carrying inline onclick.
function arcStToggleMobileNav(button) {
  const nav = document.getElementById('mnav');
  if (!nav) return;
  nav.classList.toggle('hidden');
  if (button) button.setAttribute('aria-expanded', String(!nav.classList.contains('hidden')));
}

try {
  if ('1' === localStorage.getItem('arcStDark')) {
    document.documentElement.classList.add('dark');
  }
} catch (e) {}

// ---------------------------------------------------------------------------
// Floating chat bot — mounts on template pages (#arc-st-chat mount point or
// the arc-tpl body class on imported pages). Answers come from the plugin's
// Chat Bot admin screen via REST; a bundled set covers static/no-WP renders.
// Keyword matching folds accents and requires whole-word/whole-phrase hits,
// so "va" no longer fires inside "available" nor "hi" inside "this".
// ---------------------------------------------------------------------------

(function () {
  const MOUNT_ID = 'arc-st-chat';
  const LOG_KEY = 'arcStChatLog';

  const FALLBACK_CONFIG = {
    enabled: 1,
    title: 'Ash River Collective',
    greeting: "Hi! I'm the ARC assistant. Ask me about our services, roles, process or pricing — or type \"human\" and we'll follow up by email.",
    fallback: "I can help with services, roles, process, pricing and timelines. For anything else, leave your email or use the contact form and the team will reply within one business day.",
    pairs: [
      { kw: 'services, service, what do you do, offer', a: 'ARC places trained Finance & Accounting talent and Virtual Assistants, and documents your processes into playbooks. Tell us what you need and we scope the role.' },
      { kw: 'accountant, accounting, bookkeeper, controller, finance, cfo', a: 'We place General Accountants, AR/AP Specialists, Senior Accountants, Accounting Managers, Controllers and Fractional Controllers — fluent English, U.S. hours, trained on your systems.' },
      { kw: 'virtual assistant, va, assistant, admin', a: 'ARC Virtual Assistants handle inbox, calendar, data entry, invoicing, CRM updates and reporting — with documented processes behind them, not improvisation.' },
      { kw: 'price, pricing, cost, rate, how much', a: 'Engagements are scoped to the work and priced for the role — dedicated, part-time or project-based. Use the contact form for a same-day quote.' },
      { kw: 'process, how it works, onboarding, start, timeline', a: 'Our model is Diagnose → Install → Execute: we map the work, document the playbook, then place talent that runs it. Most roles start within 7 days.' },
      { kw: 'contact, email, phone, human, talk, call', a: 'You can reach the team through the contact page or leave your work email here — a specialist replies within one business day.' },
      { kw: 'foundation, nonprofit, training', a: "The Ash River Foundation trains and connects talent in underserved communities — because opportunity shouldn't depend on geography." },
      { kw: 'hi, hello, hey, hola', a: 'Hello! Ask me about roles, pricing, timelines or how ARC works — or type "human" to reach the team.' },
    ],
    labels: {},
  };

  const CSS = `
#arc-st-chat, #arc-st-chat * { box-sizing: border-box; font-family: inherit; }
#arc-st-chat { position: fixed; inset-inline-end: 20px; bottom: 20px; z-index: 100000; }
#arc-st-chat .arc-chat-btn {
  width: 56px; height: 56px; border-radius: 50%; border: 0; cursor: pointer;
  background: #0F1C2E; color: #fff; display: flex; align-items: center; justify-content: center;
  box-shadow: 0 8px 24px rgba(15,28,46,.35); transition: transform .2s, background .2s;
}
#arc-st-chat .arc-chat-btn:hover { transform: scale(1.06); background: #438F69; }
#arc-st-chat .arc-chat-panel {
  position: absolute; bottom: 68px; inset-inline-end: 0; width: min(340px, calc(100vw - 40px));
  height: 440px; max-height: calc(100vh - 120px); background: #fff; border-radius: 14px;
  box-shadow: 0 16px 48px rgba(15,28,46,.28); display: none; flex-direction: column; overflow: hidden;
  border: 1px solid #EAF1E8;
}
#arc-st-chat.open .arc-chat-panel { display: flex; }
#arc-st-chat .arc-chat-head {
  background: #0F1C2E; color: #fff; padding: 14px 16px; display: flex; align-items: center; gap: 10px;
}
#arc-st-chat .arc-chat-head .dot { width: 8px; height: 8px; border-radius: 50%; background: #A8C85A; }
#arc-st-chat .arc-chat-head strong { font-size: 14px; font-weight: 600; flex: 1; }
#arc-st-chat .arc-chat-close { background: none; border: 0; color: #9fb0c3; font-size: 20px; cursor: pointer; line-height: 1; padding: 0 4px; }
#arc-st-chat .arc-chat-msgs { flex: 1; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 8px; background: #F5F8F4; }
#arc-st-chat .arc-msg { max-width: 82%; padding: 9px 12px; border-radius: 12px; font-size: 13px; line-height: 1.45; word-wrap: break-word; }
#arc-st-chat .arc-msg.bot { background: #fff; color: #1e293b; align-self: flex-start; border: 1px solid #EAF1E8; border-start-start-radius: 4px; }
#arc-st-chat .arc-msg.me { background: #438F69; color: #fff; align-self: flex-end; border-start-end-radius: 4px; }
#arc-st-chat .arc-msg-time { display: block; margin-top: 4px; font-size: 10px; opacity: .55; }
#arc-st-chat .arc-msg.typing { display: inline-flex; gap: 4px; align-items: center; }
#arc-st-chat .arc-msg.typing i { width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; animation: arcChatBlink 1.1s infinite; }
#arc-st-chat .arc-msg.typing i:nth-child(2) { animation-delay: .18s; }
#arc-st-chat .arc-msg.typing i:nth-child(3) { animation-delay: .36s; }
@keyframes arcChatBlink { 0%, 80%, 100% { opacity: .25; transform: translateY(0); } 40% { opacity: 1; transform: translateY(-3px); } }
#arc-st-chat .arc-chips { padding: 0 14px 10px; background: #F5F8F4; }
#arc-st-chat .arc-chips-label { font-size: 11px; color: #64748b; margin: 0 0 6px; }
#arc-st-chat .arc-chips-row { display: flex; flex-wrap: wrap; gap: 6px; }
#arc-st-chat .arc-chip {
  border: 1px solid #58B8A9; background: #fff; color: #0F1C2E; border-radius: 9999px;
  font-size: 12px; font-weight: 600; padding: 5px 11px; cursor: pointer; transition: all .15s;
}
#arc-st-chat .arc-chip:hover { background: #58B8A9; color: #fff; }
#arc-st-chat .arc-chat-form { display: flex; gap: 8px; padding: 10px; border-top: 1px solid #EAF1E8; background: #fff; }
#arc-st-chat .arc-chat-form input {
  flex: 1; border: 1px solid #dbe3db; border-radius: 8px; padding: 9px 12px; font-size: 13px;
  outline: none; background: #fff; color: #1e293b;
}
#arc-st-chat .arc-chat-form input:focus { border-color: #58B8A9; }
#arc-st-chat .arc-chat-form button {
  border: 0; border-radius: 8px; background: #A8C85A; color: #0F1C2E; font-weight: 700;
  padding: 0 14px; cursor: pointer; font-size: 13px;
}
#arc-st-chat .arc-chat-form button:hover { background: #0F1C2E; color: #fff; }
`;

  function restBase() {
    const link = document.querySelector('link[rel="https://api.w.org/"]');
    return link ? link.href.replace(/\/$/, '') + '/' : '';
  }

  function sessionId() {
    try {
      let s = sessionStorage.getItem('arcStChatSession');
      if (!s) {
        s = (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random()).replace(/[^a-zA-Z0-9-]/g, '');
        sessionStorage.setItem('arcStChatSession', s);
      }
      return s;
    } catch (e) {
      return 'anon' + Math.floor(Math.random() * 1e9);
    }
  }

  // Accent-folding word normalizer: "Cotización — precios!" -> " cotizacion
  // precios " so keyword checks compare whole words/phrases, never substrings
  // inside other words.
  function norm(s) {
    let out = String(s || '').toLowerCase();
    if (out.normalize) out = out.normalize('NFD').replace(/[̀-ͯ]/g, '');
    out = out.replace(/[^\p{L}\p{N}]+/gu, ' ').replace(/\s+/g, ' ').trim();
    return ' ' + out + ' ';
  }

  function answer(cfg, text) {
    const msg = norm(text);
    let best = null, bestScore = 0, bestLen = 0;
    (cfg.pairs || []).forEach((p) => {
      let score = 0, len = 0;
      String(p.kw || '')
        .split(',')
        .map((k) => norm(k).trim())
        .filter(Boolean)
        .forEach((k) => {
          if (msg.includes(' ' + k + ' ')) { score++; len += k.length; }
        });
      // Most keyword hits wins; equal hits → the pair with the longest total
      // match is the more specific answer.
      if (score > bestScore || (score === bestScore && score > 0 && len > bestLen)) {
        bestScore = score; bestLen = len; best = p;
      }
    });
    return best ? best.a : cfg.fallback;
  }

  function init() {
    const explicit = document.getElementById(MOUNT_ID);
    const isTpl = document.body && document.body.classList.contains('arc-tpl');
    if (!explicit && !isTpl) return;

    const base = restBase();
    const done = (cfg) => { if (cfg && Number(cfg.enabled)) build(cfg); };

    if (base) {
      fetch(base + 'arc-st/v1/chat/config', { credentials: 'same-origin' })
        .then((r) => (r.ok ? r.json() : null))
        .then((cfg) => done(cfg || FALLBACK_CONFIG))
        .catch(() => done(FALLBACK_CONFIG));
    } else {
      done(FALLBACK_CONFIG);
    }

    function log(role, message) {
      if (!base) return;
      try {
        fetch(base + 'arc-st/v1/chat/log', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({ session: sessionId(), role, message, page: location.href }),
        });
      } catch (e) {}
    }

    function build(cfg) {
      const L = Object.assign(
        {
          placeholder: 'Type your message…',
          send: 'Send',
          open: 'Open chat',
          close: 'Close',
          suggestions: 'You can ask about:',
        },
        cfg.labels || {}
      );

      const style = document.createElement('style');
      style.textContent = CSS;
      document.head.appendChild(style);

      const root = explicit || document.createElement('div');
      if (!explicit) {
        root.id = MOUNT_ID;
        document.body.appendChild(root);
      }

      root.innerHTML =
        '<div class="arc-chat-panel" role="dialog" aria-label="Chat">' +
          '<div class="arc-chat-head"><span class="dot"></span><strong></strong>' +
            '<button type="button" class="arc-chat-close"></button></div>' +
          '<div class="arc-chat-msgs" aria-live="polite"></div>' +
          '<div class="arc-chips" hidden><p class="arc-chips-label"></p><div class="arc-chips-row"></div></div>' +
          '<form class="arc-chat-form"><input type="text" autocomplete="off" />' +
            '<button type="submit"></button></form>' +
        '</div>' +
        '<button type="button" class="arc-chat-btn">' +
          '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a8 8 0 0 1-8 8H5l-2 2V12a8 8 0 0 1 8-8h2a8 8 0 0 1 8 8Z"/></svg>' +
        '</button>';

      root.querySelector('.arc-chat-head strong').textContent = cfg.title || 'Chat';
      const closeBtn = root.querySelector('.arc-chat-close');
      closeBtn.textContent = '×';
      closeBtn.setAttribute('aria-label', L.close);
      const openBtn = root.querySelector('.arc-chat-btn');
      openBtn.setAttribute('aria-label', L.open);

      const msgs = root.querySelector('.arc-chat-msgs');
      const chips = root.querySelector('.arc-chips');
      const form = root.querySelector('.arc-chat-form');
      const input = form.querySelector('input');
      const sendBtn = form.querySelector('button');
      input.placeholder = L.placeholder;
      input.setAttribute('aria-label', L.placeholder);
      sendBtn.textContent = L.send;

      // Transcript persistence — the session id already lives in
      // sessionStorage, so the log survives navigation between pages.
      let transcript = [];
      try { transcript = JSON.parse(sessionStorage.getItem(LOG_KEY) || '[]') || []; } catch (e) {}
      const persist = () => {
        try { sessionStorage.setItem(LOG_KEY, JSON.stringify(transcript.slice(-60))); } catch (e) {}
      };

      function stamp() {
        return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      }

      function addMsg(text, cls, noSave, ts) {
        const div = document.createElement('div');
        div.className = 'arc-msg ' + cls;
        div.appendChild(document.createTextNode(text));
        if (cls !== 'bot typing') {
          const t = document.createElement('time');
          t.className = 'arc-msg-time';
          t.textContent = ts || stamp();
          div.appendChild(t);
        }
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
        if (!noSave && cls !== 'bot typing') {
          transcript.push({ t: text, c: cls, ts: ts || stamp() });
          persist();
        }
        return div;
      }

      function typingBubble() {
        const div = document.createElement('div');
        div.className = 'arc-msg bot typing';
        div.innerHTML = '<i></i><i></i><i></i>';
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
        return div;
      }

      function sendText(text) {
        if (!text) return;
        addMsg(text, 'me');
        log('visitor', text);
        chips.hidden = true;
        const typing = typingBubble();
        const reply = answer(cfg, text);
        setTimeout(() => {
          typing.remove();
          addMsg(reply, 'bot');
          log('bot', reply);
        }, 550);
      }

      // Quick-reply chips: first keyword of each configured pair, so the
      // suggestions always track what the admin actually maintains.
      function showChips() {
        const seen = new Set();
        const row = chips.querySelector('.arc-chips-row');
        (cfg.pairs || []).slice(0, 8).forEach((p) => {
          const kw = String(p.kw || '').split(',').map((k) => k.trim()).filter(Boolean)[0];
          if (!kw || seen.has(kw.toLowerCase())) return;
          seen.add(kw.toLowerCase());
          const b = document.createElement('button');
          b.type = 'button';
          b.className = 'arc-chip';
          b.textContent = kw;
          b.addEventListener('click', () => sendText(kw));
          row.appendChild(b);
        });
        if (row.children.length) {
          chips.querySelector('.arc-chips-label').textContent = L.suggestions;
          chips.hidden = false;
        }
      }

      let opened = false;
      function open() {
        root.classList.add('open');
        if (opened) return;
        opened = true;
        if (transcript.length) {
          transcript.forEach((m) => addMsg(m.t, m.c, true, m.ts));
        } else {
          addMsg(cfg.greeting, 'bot');
          log('bot', cfg.greeting);
          showChips();
        }
      }

      openBtn.addEventListener('click', () =>
        root.classList.contains('open') ? root.classList.remove('open') : open()
      );
      closeBtn.addEventListener('click', () => root.classList.remove('open'));

      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        input.value = '';
        sendText(text);
      });
    }
  }

  if ('loading' === document.readyState) {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

// Forms without an action attribute are static previews — run the demo
// submit behavior. Imported pages get method/action from the importer, so
// those submissions pass through untouched to admin-post.php.
document.addEventListener('submit', (e) => {
  const form = e.target;
  if (!(form instanceof HTMLFormElement) || form.getAttribute('action')) return;
  if ('contact-form' === form.id) {
    e.preventDefault();
    arcStHandleContactSubmit(form);
  } else if (form.classList.contains('arc-st-subscribe')) {
    e.preventDefault();
    const input = form.querySelector('input[type="email"]');
    if (input) input.value = '';
    const note = document.createElement('p');
    note.className = 'mt-3 text-center text-sm font-semibold text-brand';
    note.textContent = 'Thanks for subscribing!';
    form.appendChild(note);
  }
});

// Contact page: swap the form for the success card (used when the page
// reloads with ?arc_contact=sent after the admin-post submission).
function arcStHandleContactSubmit(form) {
  form.classList.add('hidden');
  const success = document.getElementById('form-success');
  if (success) success.classList.remove('hidden');
}

(function () {
  const params = new URLSearchParams(window.location.search);
  const status = params.get('arc_contact');
  if (status) {
    const form = document.getElementById('contact-form');
    if (form) {
      if (status === 'sent') {
        arcStHandleContactSubmit(form);
        const ok = document.getElementById('form-success');
        if (ok) ok.scrollIntoView({ block: 'center', behavior: 'smooth' });
      } else if (status === 'error') {
        const note = document.createElement('p');
        note.className = 'mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700';
        note.textContent = 'Something went wrong sending your message. Please try again.';
        form.appendChild(note);
        form.scrollIntoView({ block: 'center' });
      }
    }
  }

  // Newsletter/notify forms: the page reloads with ?arc_subscribe=sent|error
  // after the admin-post submission — swap the form for an inline note.
  const sub = params.get('arc_subscribe');
  if (sub) {
    document.querySelectorAll('.arc-st-subscribe').forEach(function (form) {
      const note = document.createElement('p');
      if (sub === 'sent') {
        note.className = 'mt-3 text-center text-sm font-semibold text-brand';
        note.textContent = 'Thanks for subscribing!';
        const input = form.querySelector('input[type="email"]');
        if (input) input.value = '';
      } else {
        note.className = 'mt-3 text-center text-sm font-medium text-red-600';
        note.textContent = 'Something went wrong — please try again.';
      }
      form.appendChild(note);
      form.scrollIntoView({ block: 'center', behavior: 'smooth' });
    });
  }
})();

// ---------------------------------------------------------------------------
// Entrance animations — configured per element with data attributes:
//   data-reveal="<anim>"        entrance animation when scrolled into view
//   data-reveal-delay="<ms>"    delay before it plays
//   data-reveal-duration="<d>"  fast | slow | slower | ms | "0.9s"
//   data-reveal-stagger="<ms>"  on a container: children reveal one by one
//   data-reveal-group="<anim>"  shared entrance for those children
// Elements are pre-hidden by CSS only when html.js is set, so content never
// stays invisible if this file fails to load.
// ---------------------------------------------------------------------------

(function () {
  const revealAnimations = {
    up: 'animate-fade-in-up',
    down: 'animate-fade-in-down',
    left: 'animate-fade-in-left',
    right: 'animate-fade-in-right',
    zoom: 'animate-zoom-in',
    fade: 'animate-fade-in',
    blur: 'animate-blur-in',
    flip: 'animate-flip-in',
    bounce: 'animate-bounce-in',
  };

  const revealDurations = { fast: '450ms', slow: '1.1s', slower: '1.6s' };

  const canAnimate =
    window.matchMedia('(prefers-reduced-motion: no-preference)').matches &&
    'IntersectionObserver' in window;

  if (!canAnimate) return;

  document.documentElement.classList.add('js');

  // Expand stagger groups into per-child reveal settings.
  document.querySelectorAll('[data-reveal-stagger]').forEach((group) => {
    const step = parseInt(group.dataset.revealStagger, 10) || 120;
    const anim = group.dataset.revealGroup || 'up';
    Array.from(group.children).forEach((child, i) => {
      child.setAttribute('data-reveal', anim);
      child.setAttribute('data-reveal-delay', String(i * step));
    });
  });

  const revealObserver = new IntersectionObserver(
    (entries, observer) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        const el = entry.target;

        const delay = parseInt(el.dataset.revealDelay || '0', 10);
        if (delay > 0) el.style.animationDelay = `${delay}ms`;

        const duration = el.dataset.revealDuration;
        if (duration) {
          el.style.animationDuration =
            revealDurations[duration] || (/^\d+$/.test(duration) ? `${duration}ms` : duration);
        }

        const anim = revealAnimations[el.dataset.reveal] || revealAnimations.up;
        el.classList.add(anim);
        // Once the entrance ends, drop the animation class (its fill-mode would
        // otherwise pin the transform forever and block hover effects) and mark
        // the element as revealed so the CSS pre-hide rule stops applying.
        el.addEventListener(
          'animationend',
          () => {
            el.classList.remove(anim);
            el.classList.add('is-revealed');
          },
          { once: true }
        );
        observer.unobserve(el);
      }
    },
    { threshold: 0.15, rootMargin: '0px 0px -32px 0px' }
  );

  document.querySelectorAll('[data-reveal]').forEach((el) => revealObserver.observe(el));

  // Number reveals: <span data-count="90">90</span> counts 0 → target when it
  // scrolls into view. Optional data-count-duration / data-count-delay (ms).
  const countObserver = new IntersectionObserver(
    (entries, observer) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        const el = entry.target;
        observer.unobserve(el);

        const target = parseFloat(el.dataset.count);
        if (isNaN(target)) continue;
        const duration = parseInt(el.dataset.countDuration || '1200', 10);
        const delay = parseInt(el.dataset.countDelay || '0', 10);
        const start = () => {
          const t0 = performance.now();
          const tick = (now) => {
            const k = Math.min(1, (now - t0) / duration);
            const ease = 1 - Math.pow(1 - k, 3); // ease-out cubic
            el.textContent = String(Math.round(target * ease));
            if (k < 1) requestAnimationFrame(tick);
          };
          requestAnimationFrame(tick);
        };
        delay > 0 ? setTimeout(start, delay) : start();
      }
    },
    { threshold: 0.5 }
  );

  document.querySelectorAll('[data-count]').forEach((el) => countObserver.observe(el));
})();
