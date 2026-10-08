/* ARC Starter Templates — import wizard.
   Runs the staged AJAX import (begin → reset? → media → pages×N → setup →
   finish) with a progress bar, per-step states, a live log, dry-run mode,
   crash-safe resume (server-side run state) and retry — fully local. */
(function bootImportWizard() {
	'use strict';

	// WordPress can place localized scripts in the footer or load them through
	// an optimization layer. Wait for the DOM in either case so the button is
	// always bound when the wizard markup exists.
	if (document.readyState === 'loading') {
		if (!window.arcStImportWizardReadyBound) {
			window.arcStImportWizardReadyBound = true;
			document.addEventListener('DOMContentLoaded', bootImportWizard, { once: true });
		}
		return;
	}
	if (window.arcStImportWizardBooted) return;
	window.arcStImportWizardBooted = true;

	var root = document.getElementById('arc-st-wizard');
	if (!root) return;
	if (typeof arcSt === 'undefined') {
		// Fail loud instead of silently: a disabled Start button with no
		// explanation looks identical to "the plugin is broken".
		var startBtnEarly = document.getElementById('arc-st-start');
		if (startBtnEarly) {
			startBtnEarly.disabled = true;
			startBtnEarly.textContent = 'Error: script data missing — reload the page';
		}
		if (window.console) {
			console.error('ARC Starter Templates: arcSt is undefined — admin/js/import.js did not receive its localized data. Reload the page; if it persists, check for a script-loading conflict (caching/minification plugin).');
		}
		return;
	}

	var percentEl = root.querySelector('.arc-st-percent');
	var barEl = root.querySelector('.arc-st-bar');
	var fillEl = root.querySelector('.arc-st-bar-fill');
	var logEl = document.getElementById('arc-st-log');
	var startBtn = document.getElementById('arc-st-start');
	var resumeBtn = document.getElementById('arc-st-resume');
	var frontChk = document.getElementById('arc-st-front');
	var dryChk = document.getElementById('arc-st-dry');
	var pageChks = root.querySelectorAll('.arc-st-page-chk');
	var startBox = root.querySelector('.arc-st-wizard-start');
	var doneBox = root.querySelector('.arc-st-wizard-done');
	var doneActions = root.querySelector('.arc-st-done-actions');
	var donePages = root.querySelector('.arc-st-done-pages');

	var running = false;
	var pageLinks = [];
	var dryRun = false;
	var failedOnce = false;

	function setStep(name, state) {
		var li = root.querySelector('.arc-st-steps li[data-step="' + name + '"]');
		if (li) li.className = state ? 'is-' + state : '';
	}

	function log(text, cls) {
		var li = document.createElement('li');
		li.textContent = text;
		if (cls) li.className = cls;
		logEl.appendChild(li);
		logEl.scrollTop = logEl.scrollHeight;
	}

	var pctTarget = 0, pctShown = 0, pctTimer = null;
	function target(p) {
		pctTarget = p;
		if (pctTimer) return;
		pctTimer = setInterval(function () {
			if (pctShown >= pctTarget) { clearInterval(pctTimer); pctTimer = null; return; }
			pctShown = Math.min(pctTarget, pctShown + 1);
			percentEl.textContent = pctShown + '%';
			fillEl.style.width = pctShown + '%';
			barEl.setAttribute('aria-valuenow', String(pctShown));
		}, 12);
	}
	function jump(p) {
		pctShown = pctTarget = p;
		percentEl.textContent = p + '%';
		fillEl.style.width = p + '%';
		barEl.setAttribute('aria-valuenow', String(p));
	}

	function post(action, extra) {
		var body = new URLSearchParams();
		body.append('action', action);
		body.append('nonce', arcSt.nonce);
		if (dryRun) body.append('dry', '1');
		Object.keys(extra || {}).forEach(function (k) {
			var v = extra[k];
			if (Array.isArray(v)) {
				v.forEach(function (item) { body.append(k + '[]', item); });
			} else {
				body.append(k, v);
			}
		});
		return fetch(arcSt.ajax_url, { method: 'POST', body: body })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res || !res.success) {
					throw new Error((res && res.data && res.data.error) || 'Request failed');
				}
				return res.data;
			});
	}

	function selectedSlugs() {
		var out = [];
		pageChks.forEach(function (c) { if (c.checked) out.push(c.value); });
		return out;
	}

	function resetButtons() {
		running = false;
		startBtn.disabled = false;
		startBtn.textContent = failedOnce ? arcSt.i18n.retry : arcSt.i18n.start;
		if (resumeBtn) resumeBtn.disabled = false;
	}

	function finish(status) {
		// Best-effort close: releases the server lock + marks state/report.
		return post('arc_st_step_finish', { status: status }).catch(function () {});
	}

	function fail(err) {
		log('✖ ' + (err && err.message ? err.message : err), 'is-error');
		failedOnce = true;
		finish('failed').then(function () {
			resetButtons();
			// Retry resumes — the server-side run state knows what completed.
			if (resumeBtn) {
				resumeBtn.hidden = false;
				resumeBtn.textContent = arcSt.i18n.retry;
			}
		});
	}

	function done(data) {
		jump(100);
		if (dryRun) {
			log('✔ ' + arcSt.i18n.dryDone, 'is-ok');
		} else {
			log('✔ ' + arcSt.i18n.done, 'is-ok');
		}
		doneBox.hidden = false;

		var links = [];
		if (!dryRun) {
			links.push('<a class="button button-primary" href="' + arcSt.site + '" target="_blank" rel="noopener">' + arcSt.i18n.viewSite + '</a>');
			if (data && data.front_page) {
				links.push('<a class="button" href="' + arcSt.ajax_url.replace('admin-ajax.php', 'post.php?post=' + data.front_page + '&action=edit') + '">' + arcSt.i18n.editHome + '</a>');
			}
		}
		links.push('<a class="button" href="' + arcSt.library + '">' + arcSt.i18n.back + '</a>');
		doneActions.innerHTML = links.join(' ');
		if (donePages && pageLinks.length && !dryRun) {
			donePages.innerHTML = pageLinks.map(function (p) {
				return '<li><a href="' + p.link + '" target="_blank" rel="noopener">' + p.name + '</a>'
					+ ' <a class="arc-st-page-edit" href="' + p.edit + '">edit</a></li>';
			}).join('');
		}
		doneBox.focus();
		startBox.style.display = 'none';
	}

	function run(resume) {
		if (running) return;
		failedOnce = false;
		var slugs = selectedSlugs();
		if (!slugs.length) {
			log('✖ Select at least one page to import.', 'is-error');
			return;
		}
		running = true;
		dryRun = !!(dryChk && dryChk.checked);
		startBtn.disabled = true;
		startBtn.textContent = arcSt.i18n.importing;
		if (resumeBtn) resumeBtn.disabled = true;
		if (!resume) {
			logEl.innerHTML = '';
			doneBox.hidden = true;
			pageLinks = [];
			jump(0);
			setStep('reset', ''); setStep('media', ''); setStep('pages', ''); setStep('setup', '');
		}

		var totalSteps = slugs.length + 2; // media + pages + setup
		var doneSteps = 0;
		function tick() { doneSteps++; target(Math.round(doneSteps / totalSteps * 100)); }

		var chain = post('arc_st_step_begin', {
			pages: slugs,
			demo: arcSt.demo || '',
			set_front: (frontChk && frontChk.checked) ? '1' : '0',
			resume: resume ? '1' : '0'
		}).then(function (d) {
			var state = d.state || {};
			var pagesDone = resume ? (state.pages_done || []) : [];
			var pending = slugs.filter(function (s) { return pagesDone.indexOf(s) === -1; });
			var c = Promise.resolve();

			// reset — always runs on a fresh import: the new site replaces the
			// previously imported one (demo:'' = wipe every imported page).
			if (!(resume && state.reset_done)) {
				setStep('reset', 'active');
				c = c.then(function () {
					log(arcSt.i18n.stepReset + '…');
					return post('arc_st_step_reset', { demo: '' }).then(function (r) {
						setStep('reset', 'done');
						log('↳ ' + (r.deleted || 0) + ' removed');
					});
				});
			} else {
				setStep('reset', 'done');
			}

			// media (skipped on resume if already done).
			if (!(resume && state.media_done)) {
				c = c.then(function () {
					setStep('media', 'active');
					log(arcSt.i18n.stepMedia + '…');
					return post('arc_st_step_media', { pages: slugs, demo: arcSt.demo || '' }).then(function (r) {
						setStep('media', 'done');
						log('↳ ' + (r.images || 0) + ' images');
						if (r.errors) {
							Object.keys(r.errors).forEach(function (f) {
								log('↳ ⚠ ' + f + ': ' + r.errors[f], 'is-error');
							});
						}
						tick();
					});
				});
			} else {
				setStep('media', 'done');
				tick();
			}

			// pages — only the pending ones on resume.
			pending.forEach(function (slug) {
				c = c.then(function () {
					var label = (arcSt.names && arcSt.names[slug]) || slug;
					setStep('pages', 'active');
					log(arcSt.i18n.stepPages + ': ' + label + '…');
					return post('arc_st_step_page', { slug: slug }).then(function (r) {
						log('↳ ' + label + ' — #' + r.page_id);
						if (r.link) pageLinks.push({ name: label, link: r.link, edit: r.edit });
						tick();
					});
				});
			});
			// Account for already-done pages in the progress bar on resume.
			doneSteps += (slugs.length - pending.length);
			c = c.then(function () { setStep('pages', 'done'); });

			// setup (skipped on resume if already done).
			if (!(resume && state.setup_done)) {
				c = c.then(function () {
					setStep('setup', 'active');
					log(arcSt.i18n.stepSetup + '…');
					return post('arc_st_step_setup', {
						set_front: (frontChk && frontChk.checked) ? '1' : '0',
						demo: arcSt.demo || ''
					}).then(function (r) {
						setStep('setup', 'done');
						if (r.front_page) log('↳ front page → #' + r.front_page);
						if (r.menu_location) log('↳ menu → ' + r.menu_location);
						if (r.footer_location) log('↳ footer menu → ' + r.footer_location);
						if (r.logo && r.logo.length) log('↳ brand: ' + r.logo.join(', '));
						if (r.elementor) log('↳ Elementor: containers enabled');
						if (r.elementor_containers === false) log('↳ ⚠ Elementor containers NOT active — check Elementor settings', 'is-error');
						if (r.elementor_colors) log('↳ Elementor: brand colors + typography added');
						if (r.elementor_cache) log('↳ Elementor: CSS cache rebuilt');
						tick();
						return r;
					});
				});
			} else {
				setStep('setup', 'done');
				tick();
				c = c.then(function () { return {}; });
			}

			return c;
		});

		chain.then(function (setupData) {
			return finish('done').then(function () { done(setupData); });
		}).catch(fail);
	}

	if (startBtn) startBtn.addEventListener('click', function () { run(false); });
	if (resumeBtn) resumeBtn.addEventListener('click', function () { run(true); });

	var allBtn = document.getElementById('arc-st-pages-all');
	var noneBtn = document.getElementById('arc-st-pages-none');
	if (allBtn) allBtn.addEventListener('click', function () {
		pageChks.forEach(function (c) { c.checked = true; });
	});
	if (noneBtn) noneBtn.addEventListener('click', function () {
		pageChks.forEach(function (c) { c.checked = false; });
	});

	// One-click flow: the library card's "Import Site" lands here with
	// ?autorun=1 — run the complete import immediately (all pages + front
	// page), resuming an unfinished run when the server has one.
	if (/[?&]autorun=1/.test(window.location.search)) {
		log('▶ ' + (arcSt.i18n.autorun || 'Starting the full import automatically…'));
		run(!!resumeBtn);
	}
})();
