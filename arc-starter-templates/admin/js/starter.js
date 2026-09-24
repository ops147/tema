/* ARC Starter Templates — library screen.
   The Import button is a real <a href> (works with zero JS). This script
   only adds keyboard support on the cards and the preview/search/remove
   niceties — none of it is required for the Import button itself to work,
   and a failure in one part must never block the others. */
(function () {
	'use strict';

	var cards = document.querySelectorAll('.arc-st-demo');
	var search = document.querySelector('.arc-st-search-input');
	var cats = document.querySelectorAll('.arc-st-cats input[type="checkbox"]');
	var modal = document.getElementById('arc-st-preview-modal');
	var iframe = modal ? modal.querySelector('.arc-st-modal-iframe') : null;
	var modalOpen = modal ? modal.querySelector('.arc-st-modal-open') : null;
	var modalTitle = modal ? modal.querySelector('.arc-st-modal-title') : null;
	var lastFocus = null;

	function wizardUrl(card) {
		if (card && card.getAttribute('data-wizard-url')) {
			return card.getAttribute('data-wizard-url');
		}
		if (typeof arcSt !== 'undefined' && arcSt.wizard) {
			var url = arcSt.wizard;
			return url + (url.indexOf('?') === -1 ? '?' : '&') + 'autorun=1';
		}
		return '';
	}

	// Cards are keyboard-operable: Enter/Space opens the wizard (the button
	// itself is a plain link and needs no JS to work).
	cards.forEach(function (card) {
		card.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				var target = e.target;
				// Don't hijack Enter/Space on the Preview button or the
				// Import/View/Edit links inside the card — let them act
				// on themselves.
				if (target && target.closest('a, button')) {
					return;
				}
				e.preventDefault();
				var url = wizardUrl(card);
				if (url) {
					window.location.href = url;
				}
			}
		});
	});

	/* ---------------- Preview modal ---------------- */

	function openModal(url, title) {
		if (!modal || !iframe) {
			window.open(url, '_blank', 'noopener');
			return;
		}
		lastFocus = document.activeElement;
		iframe.src = url;
		if (modalOpen) modalOpen.href = url;
		if (modalTitle && title) modalTitle.textContent = title;
		modal.hidden = false;
		document.body.classList.add('arc-st-modal-open-body');
		var closeBtn = modal.querySelector('.arc-st-modal-close');
		if (closeBtn) closeBtn.focus();
	}

	function closeModal() {
		if (!modal) return;
		modal.hidden = true;
		if (iframe) iframe.src = 'about:blank';
		document.body.classList.remove('arc-st-modal-open-body');
		if (lastFocus && lastFocus.focus) lastFocus.focus();
	}

	document.querySelectorAll('[data-preview]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var card = btn.closest('.arc-st-demo');
			openModal(btn.getAttribute('data-preview'), card ? card.getAttribute('data-name') : '');
		});
	});

	if (modal) {
		modal.querySelectorAll('[data-close]').forEach(function (el) {
			el.addEventListener('click', closeModal);
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && !modal.hidden) closeModal();
		});
	}

	/* ---------------- Remove imported site ---------------- */

	document.querySelectorAll('.arc-st-remove-site').forEach(function (link) {
		link.addEventListener('click', function (e) {
			var msg = (typeof arcSt !== 'undefined' && arcSt.i18n && arcSt.i18n.confirmDel)
				|| 'Delete every page, menu and setting this plugin imported? This cannot be undone.';
			if (!window.confirm(msg)) {
				e.preventDefault();
			}
		});
	});

	/* ---------------- Search / category filter ---------------- */

	function applyFilter() {
		var q = (search && search.value ? search.value : '').toLowerCase();
		var checked = Array.prototype.filter.call(cats, function (c) { return c.checked; })
			.map(function (c) { return c.value; });
		cards.forEach(function (card) {
			var name = (card.getAttribute('data-name') || '').toLowerCase();
			var cardCats = (card.getAttribute('data-cats') || '').split(' ');
			var okCats = checked.length === 0 || cardCats.some(function (c) { return checked.indexOf(c) !== -1; });
			var okText = !q || name.indexOf(q) !== -1;
			card.style.display = okCats && okText ? '' : 'none';
		});
	}

	if (search) search.addEventListener('keyup', applyFilter);
	cats.forEach(function (c) { c.addEventListener('change', applyFilter); });
})();
