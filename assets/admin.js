(function () {
	var buttons = document.querySelectorAll('.wp2grav-export-btn');
	var resultsDiv = document.getElementById('wp2grav-results');
	var nonce = wp2gravAdmin.nonce;

	document.addEventListener('click', function (e) {
		if (!e.target.classList.contains('wp2grav-delete-btn')) return;

		var btn = e.target;
		var folder = btn.getAttribute('data-folder');

		if (!confirm('Delete export "' + folder + '"? This cannot be undone.')) return;

		btn.disabled = true;

		var data = new FormData();
		data.append('action', 'wp2grav_delete_export');
		data.append('_wpnonce', nonce);
		data.append('folder', folder);

		fetch(ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data
		})
			.then(function (response) { return response.json(); })
			.then(function (result) {
				if (result.success) {
					btn.closest('li').remove();
					resultsDiv.textContent = result.data.message;
				} else {
					resultsDiv.textContent = 'Error: ' + (result.data.message || 'Unknown error');
					btn.disabled = false;
				}
			})
			.catch(function (error) {
				resultsDiv.textContent = 'Request failed: ' + error.message;
				btn.disabled = false;
			});
	});

	buttons.forEach(function (button) {
		button.addEventListener('click', function (e) {
			e.preventDefault();
			var exporter = this.getAttribute('data-exporter');

			// Disable all buttons during export.
			buttons.forEach(function (b) { b.disabled = true; });
			resultsDiv.textContent = 'Running ' + exporter + ' export...';

			var data = new FormData();
			data.append('action', 'wp2grav_run_export');
			data.append('_wpnonce', nonce);
			data.append('exporter', exporter);

			fetch(ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				body: data
			})
				.then(function (response) { return response.json(); })
				.then(function (result) {
					if (result.success) {
						resultsDiv.textContent = result.data.message;
					} else {
						resultsDiv.textContent = 'Error: ' + (result.data.message || 'Unknown error');
					}
				})
				.catch(function (error) {
					resultsDiv.textContent = 'Request failed: ' + error.message;
				})
				.finally(function () {
					buttons.forEach(function (b) { b.disabled = false; });
				});
		});
	});
})();
