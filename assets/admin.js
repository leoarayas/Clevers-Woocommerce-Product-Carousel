/* global clevprcaCarouselAdmin */
(function () {
	'use strict';

	if (typeof window === 'undefined') {
		return;
	}

	function initPresetPreview() {
		var presetSelect = document.getElementById('clevprca-preset-select');
		if (!presetSelect) {
			return;
		}
		var inlinePreview = document.getElementById('clevprca-preset-preview-inline');
		var sidePreview = document.getElementById('clevprca-preset-preview-side');

		function syncPreview() {
			var preset = String(presetSelect.value || '1');
			if (inlinePreview) {
				inlinePreview.setAttribute('data-preset', preset);
			}
			if (sidePreview) {
				sidePreview.setAttribute('data-preset', preset);
			}
		}

		presetSelect.addEventListener('change', syncPreview);
		syncPreview();
	}

	function initCopyDiagnosticReport() {
		var btn = document.getElementById('clevprca-copy-diagnostic-report');
		var ta = document.getElementById('clevprca-diagnostic-report');
		if (!btn || !ta) {
			return;
		}
		var labels = (window.clevprcaCarouselAdmin && window.clevprcaCarouselAdmin.copyLabels) || {};
		var copied = labels.copied || 'Copied';
		var idle = labels.idle || 'Copy report';

		btn.addEventListener('click', function () {
			ta.focus();
			ta.select();
			try {
				document.execCommand('copy');
				btn.textContent = copied;
				setTimeout(function () {
					btn.textContent = idle;
				}, 1200);
			} catch (e) {
				/* copy not supported, ignore */
			}
		});
	}

	function initProductPicker() {
		var productPicker = document.getElementById('clevprca-product-picker');
		if (!productPicker) {
			return;
		}
		var searchInput = document.getElementById('clevprca-product-search');
		var resultsBox = document.getElementById('clevprca-product-search-results');
		var selectedBox = document.getElementById('clevprca-selected-products');
		var csvField = document.getElementById('clevprca-manual-product-ids-csv');
		var nonce = productPicker.getAttribute('data-nonce');
		var selected = [];
		var debounceTimer = null;

		function syncCsv() {
			if (csvField) {
				csvField.value = selected.join(', ');
			}
		}

		function renderSelected() {
			if (!selectedBox) {
				return;
			}
			selectedBox.innerHTML = '';
			selected.forEach(function (id) {
				var chip = document.createElement('span');
				chip.className = 'clevprca-chip';
				var label = document.createElement('span');
				label.textContent = '#' + id;
				chip.appendChild(label);

				var remove = document.createElement('button');
				remove.type = 'button';
				remove.setAttribute('aria-label', 'Remove #' + id);
				remove.textContent = '\u00d7';
				remove.addEventListener('click', function () {
					selected = selected.filter(function (v) {
						return v !== id;
					});
					renderSelected();
					syncCsv();
				});
				chip.appendChild(remove);
				selectedBox.appendChild(chip);
			});
		}

		function addSelected(id) {
			id = parseInt(id, 10);
			if (!id || selected.indexOf(id) !== -1) {
				return;
			}
			selected.push(id);
			renderSelected();
			syncCsv();
		}

		if (csvField && csvField.value.trim()) {
			csvField.value.split(',').forEach(function (v) {
				var id = parseInt(v.trim(), 10);
				if (id) {
					addSelected(id);
				}
			});
		}

		function renderResults(items) {
			if (!resultsBox) {
				return;
			}
			resultsBox.innerHTML = '';
			if (!items.length) {
				resultsBox.hidden = true;
				return;
			}
			items.forEach(function (item) {
				var resultBtn = document.createElement('button');
				resultBtn.type = 'button';
				resultBtn.textContent = item.label + ' (#' + item.id + ')';
				resultBtn.addEventListener('click', function () {
					addSelected(item.id);
					searchInput.value = '';
					resultsBox.hidden = true;
					resultsBox.innerHTML = '';
				});
				resultsBox.appendChild(resultBtn);
			});
			resultsBox.hidden = false;
		}

		function searchProducts(term) {
			if (!term || term.length < 2) {
				renderResults([]);
				return;
			}
			var url =
				(window.ajaxurl || '') +
				'?action=clevprca_search_products&_ajax_nonce=' +
				encodeURIComponent(nonce) +
				'&q=' +
				encodeURIComponent(term);
			fetch(url, { credentials: 'same-origin' })
				.then(function (r) {
					return r.json();
				})
				.then(function (json) {
					if (!json || !json.success || !Array.isArray(json.data)) {
						renderResults([]);
						return;
					}
					renderResults(json.data);
				})
				.catch(function () {
					renderResults([]);
				});
		}

		if (searchInput) {
			searchInput.addEventListener('input', function () {
				var term = searchInput.value.trim();
				window.clearTimeout(debounceTimer);
				debounceTimer = window.setTimeout(function () {
					searchProducts(term);
				}, 220);
			});
		}
		renderSelected();
	}

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	ready(function () {
		initPresetPreview();
		initCopyDiagnosticReport();
		initProductPicker();
	});
})();
