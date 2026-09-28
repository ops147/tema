/**
 * Appearance > Lienzo Astra — settings screen helpers.
 * Live filter over the feature rows + bulk enable/disable.
 */
(function () {
	const search = document.getElementById('la-filter');
	const rows = Array.from(document.querySelectorAll('.la-row'));
	const groups = Array.from(document.querySelectorAll('.la-group'));

	if (search) {
		search.addEventListener('input', function () {
			const q = this.value.trim().toLowerCase();
			rows.forEach(function (row) {
				const hay = (row.dataset.search || '').toLowerCase();
				row.classList.toggle('is-hidden', q !== '' && !hay.includes(q));
			});
			groups.forEach(function (group) {
				const visible = group.querySelectorAll('.la-row:not(.is-hidden)').length;
				group.style.display = visible ? '' : 'none';
			});
		});
	}

	const setAll = function (checked) {
		rows.forEach(function (row) {
			const input = row.querySelector('input[type="checkbox"]');
			if (input) input.checked = checked;
		});
		syncStates();
	};

	const syncStates = function () {
		rows.forEach(function (row) {
			const input = row.querySelector('input[type="checkbox"]');
			const state = row.querySelector('.la-row__state');
			if (input && state) {
				const disabled = input.checked;
				state.textContent = disabled ? state.dataset.off : state.dataset.on;
				state.classList.toggle('is-off', disabled);
				state.classList.toggle('is-on', !disabled);
			}
		});
	};

	const onBtn = document.getElementById('la-enable-all');
	const offBtn = document.getElementById('la-disable-all');
	if (onBtn) onBtn.addEventListener('click', function () { setAll(false); });
	if (offBtn) offBtn.addEventListener('click', function () { setAll(true); });

	rows.forEach(function (row) {
		const input = row.querySelector('input[type="checkbox"]');
		if (input) input.addEventListener('change', syncStates);
	});
})();
