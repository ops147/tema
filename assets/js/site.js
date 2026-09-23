// Shared UI behavior for ARC template pages — loaded with defer.
// Globals are prefixed (arcSt*) and everything else lives inside an IIFE so
// this file can never collide with another plugin or theme script.

function arcStToggleMobileNav(button) {
  const nav = document.getElementById('mnav');
  if (!nav) return;
  nav.classList.toggle('hidden');
  button.setAttribute('aria-expanded', String(!nav.classList.contains('hidden')));
}

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
