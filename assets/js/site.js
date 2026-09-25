// Shared UI behavior for ARC template pages — loaded with defer.
// Globals are prefixed (arcSt*) and everything else lives inside an IIFE so
// this file can never collide with another plugin or theme script.

function arcStToggleMobileNav(button) {
  const nav = document.getElementById('mnav');
  if (!nav) return;
  nav.classList.toggle('hidden');
  button.setAttribute('aria-expanded', String(!nav.classList.contains('hidden')));
}

// Header dark-mode toggle — flips the `dark` class on <html> and remembers
// the choice. Templates can then style dark: variants (or a consumer theme
// can hook the same class) without extra wiring.
function arcStToggleDark() {
  const dark = document.documentElement.classList.toggle('dark');
  try { localStorage.setItem('arcStDark', dark ? '1' : '0'); } catch (e) {}
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
// ---------------------------------------------------------------------------

(function () {
  const MOUNT_ID = 'arc-st-chat';

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
#arc-st-chat .arc-msg.typing { color: #94a3b8; font-style: italic; }
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

  function answer(cfg, text) {
    const msg = ' ' + text.toLowerCase() + ' ';
    let best = null, bestScore = 0;
    (cfg.pairs || []).forEach((p) => {
      const score = String(p.kw || '')
        .split(',')
        .map((k) => k.trim().toLowerCase())
        .filter(Boolean)
        .reduce((n, k) => n + (msg.includes(k) ? 1 : 0), 0);
      if (score > bestScore) { bestScore = score; best = p; }
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
            '<button type="button" class="arc-chat-close" aria-label="Close">&times;</button></div>' +
          '<div class="arc-chat-msgs" aria-live="polite"></div>' +
          '<form class="arc-chat-form"><input type="text" placeholder="Type your message…" aria-label="Message" autocomplete="off" />' +
            '<button type="submit">Send</button></form>' +
        '</div>' +
        '<button type="button" class="arc-chat-btn" aria-label="Open chat">' +
          '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a8 8 0 0 1-8 8H5l-2 2V12a8 8 0 0 1 8-8h2a8 8 0 0 1 8 8Z"/></svg>' +
        '</button>';

      root.querySelector('.arc-chat-head strong').textContent = cfg.title || 'Chat';

      const msgs = root.querySelector('.arc-chat-msgs');
      const form = root.querySelector('.arc-chat-form');
      const input = form.querySelector('input');

      function addMsg(text, cls) {
        const div = document.createElement('div');
        div.className = 'arc-msg ' + cls;
        div.textContent = text;
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
        return div;
      }

      let greeted = false;
      function open() {
        root.classList.add('open');
        if (!greeted) {
          greeted = true;
          addMsg(cfg.greeting, 'bot');
          log('bot', cfg.greeting);
        }
      }

      root.querySelector('.arc-chat-btn').addEventListener('click', () =>
        root.classList.contains('open') ? root.classList.remove('open') : open()
      );
      root.querySelector('.arc-chat-close').addEventListener('click', () => root.classList.remove('open'));

      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        input.value = '';
        addMsg(text, 'me');
        log('visitor', text);
        const typing = addMsg('…', 'bot typing');
        const reply = answer(cfg, text);
        setTimeout(() => {
          typing.remove();
          addMsg(reply, 'bot');
          log('bot', reply);
        }, 450);
      });
    }
  }

  if ('loading' === document.readyState) {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

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
})();
