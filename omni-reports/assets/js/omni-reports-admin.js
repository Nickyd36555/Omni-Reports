/* Omni Reports — Admin JS */
/* global jQuery, Chart, omniReports */

(function ($) {
	'use strict';

	// Chart instances registry (so we can destroy before redraw).
	var charts = {};

	// ── Date management ──────────────────────────────────────────────────────

	var dateCallbacks = [];
	var activePreset  = '30days';

	function dateRange(preset) {
		var today = new Date();
		var fmt   = function (d) { return d.toISOString().slice(0, 10); };
		var from, to = fmt(today);

		switch (preset) {
			case 'today':
				from = fmt(today);
				break;
			case '7days':
				from = fmt(new Date(today - 7 * 864e5));
				break;
			case 'thismonth':
				from = fmt(new Date(today.getFullYear(), today.getMonth(), 1));
				break;
			case 'lastmonth':
				var lm = new Date(today.getFullYear(), today.getMonth() - 1, 1);
				from   = fmt(lm);
				to     = fmt(new Date(today.getFullYear(), today.getMonth(), 0));
				break;
			default: // 30days
				from = fmt(new Date(today - 30 * 864e5));
		}
		return { from: from, to: to };
	}

	function triggerDateChange(from, to) {
		dateCallbacks.forEach(function (cb) { cb(from, to); });
	}

	omniReports.currentFrom = function () {
		return $('#omni-date-from').val() || dateRange(activePreset).from;
	};

	omniReports.currentTo = function () {
		return $('#omni-date-to').val() || dateRange(activePreset).to;
	};

	omniReports.onDateChange = function (cb) {
		dateCallbacks.push(cb);
	};

	// ── Column visibility ────────────────────────────────────────────────────

	/**
	 * Returns true if a column key is enabled for a given report slug.
	 * Falls back to the default definitions if no saved columns exist.
	 */
	omniReports.colEnabled = function (slug, key) {
		var saved = (omniReports.reportColumns || {})[slug];
		if (saved && saved.length) {
			return saved.indexOf(key) !== -1;
		}
		// Fall back to default definitions.
		var defs = (omniReports.columnDefs || {})[slug] || [];
		for (var i = 0; i < defs.length; i++) {
			if (defs[i].key === key) return !! defs[i].on;
		}
		return true; // unknown column — show by default.
	};

	// ── Currency ─────────────────────────────────────────────────────────────

	omniReports.formatCurrency = function (val) {
		var n = parseFloat(val) || 0;
		return omniReports.currency + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	};

	// ── Chart helpers ────────────────────────────────────────────────────────

	var PALETTE = [
		'#2271b1','#f0a30a','#00a32a','#d63638','#8c8f94',
		'#50c8ff','#ffb900','#3fcf8e','#ff6b6b','#9b59b6'
	];

	function destroyChart(id) {
		if (charts[id]) { charts[id].destroy(); delete charts[id]; }
	}

	omniReports.renderLineChart = function (id, data, labelKey, valueKey, label) {
		destroyChart(id);
		var ctx = document.getElementById(id);
		if (!ctx || !data || !data.length) return;

		ctx.style.maxHeight = '240px';
		charts[id] = new Chart(ctx, {
			type: 'line',
			data: {
				labels: data.map(function (r) { return r[labelKey]; }),
				datasets: [{
					label: label || valueKey,
					data: data.map(function (r) { return parseFloat(r[valueKey]) || 0; }),
					borderColor: PALETTE[0],
					backgroundColor: PALETTE[0] + '22',
					fill: true,
					tension: 0.3,
					pointRadius: data.length > 60 ? 0 : 3,
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: { legend: { display: false } },
				scales: { y: { beginAtZero: true, ticks: { font: { size: 11 } } }, x: { ticks: { font: { size: 10 }, maxRotation: 45 }, grid: { display: false } } }
			}
		});
	};

	omniReports.renderBarChart = function (id, data, labelKey, valueKey, label) {
		destroyChart(id);
		var ctx = document.getElementById(id);
		if (!ctx || !data || !data.length) return;

		// Limit to top 20 for readability.
		var slice = data.slice(0, 20);

		ctx.style.maxHeight = '240px';
		charts[id] = new Chart(ctx, {
			type: 'bar',
			data: {
				labels: slice.map(function (r) { return r[labelKey]; }),
				datasets: [{
					label: label || valueKey,
					data: slice.map(function (r) { return parseFloat(r[valueKey]) || 0; }),
					backgroundColor: PALETTE[0],
					borderRadius: 3,
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: { legend: { display: false } },
				scales: { y: { beginAtZero: true, ticks: { font: { size: 11 } } }, x: { ticks: { font: { size: 10 }, maxRotation: 45 }, grid: { display: false } } }
			}
		});
	};

	omniReports.renderPieChart = function (id, data, labelKey, valueKey) {
		destroyChart(id);
		var ctx = document.getElementById(id);
		if (!ctx || !data || !data.length) return;

		var slice = data.slice(0, 10);

		ctx.style.maxHeight = '240px';
		charts[id] = new Chart(ctx, {
			type: 'doughnut',
			data: {
				labels: slice.map(function (r) { return r[labelKey] || 'Unknown'; }),
				datasets: [{
					data: slice.map(function (r) { return parseFloat(r[valueKey]) || 0; }),
					backgroundColor: PALETTE,
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: { legend: { position: 'right', labels: { font: { size: 11 }, boxWidth: 12 } } }
			}
		});
	};

	// ── Table renderer ───────────────────────────────────────────────────────

	omniReports.renderTable = function (containerId, data) {
		var $wrap = $('#' + containerId);
		if (!$wrap.length) return;

		if (!data || !data.length) {
			$wrap.html('<p class="omni-empty-state">No data for this period.</p>');
			return;
		}

		var cols  = Object.keys(data[0]);
		var html  = '<table class="omni-data-table"><thead><tr>';
		cols.forEach(function (c) {
			html += '<th data-col="' + c + '">' + c.replace(/_/g, ' ') + '</th>';
		});
		html += '</tr></thead><tbody>';

		data.forEach(function (row) {
			html += '<tr>';
			cols.forEach(function (c) {
				var v   = row[c] !== null && row[c] !== undefined ? row[c] : '';
				var cls = isNaN(parseFloat(v)) || c.indexOf('date') !== -1 || c.indexOf('name') !== -1 || c.indexOf('email') !== -1 || c.indexOf('country') !== -1 || c.indexOf('status') !== -1 || c.indexOf('method') !== -1 || c.indexOf('code') !== -1 ? '' : ' class="num"';
				html += '<td' + cls + '>' + esc(v) + '</td>';
			});
			html += '</tr>';
		});

		html += '</tbody></table>';
		$wrap.html(html);

		// Sort on header click.
		$wrap.find('th').on('click', function () {
			var col  = $(this).data('col');
			var asc  = $(this).hasClass('sort-asc');
			$wrap.find('th').removeClass('sort-asc sort-desc');
			$(this).addClass(asc ? 'sort-desc' : 'sort-asc');
			var sorted = data.slice().sort(function (a, b) {
				var av = isNaN(a[col]) ? String(a[col]) : parseFloat(a[col]);
				var bv = isNaN(b[col]) ? String(b[col]) : parseFloat(b[col]);
				return asc ? (av < bv ? 1 : -1) : (av > bv ? 1 : -1);
			});
			omniReports.renderTable(containerId, sorted);
		});
	};

	function esc(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	// ── CSV Export ───────────────────────────────────────────────────────────

	omniReports.exportCsv = function (reportType, group, config) {
		var form = $('<form method="POST" action="' + omniReports.ajaxUrl + '"></form>');
		var fields = {
			action:      'omni_export_csv',
			nonce:       omniReports.nonce,
			report_type: reportType,
			date_from:   omniReports.currentFrom(),
			date_to:     omniReports.currentTo(),
		};
		if (group)  fields.group  = group;
		if (config) fields.config = JSON.stringify(config);

		$.each(fields, function (k, v) {
			form.append($('<input type="hidden">').attr('name', k).val(v));
		});
		$('body').append(form);
		form.submit();
		form.remove();
	};

	// ── Date preset wiring ───────────────────────────────────────────────────

	$(function () {
		// Set initial date inputs.
		var initial = dateRange('30days');
		$('#omni-date-from').val(initial.from);
		$('#omni-date-to').val(initial.to);

		$(document).on('click', '.omni-preset', function () {
			$('.omni-preset').removeClass('active');
			$(this).addClass('active');
			activePreset = $(this).data('range');

			if (activePreset === 'custom') {
				$('#omni-custom-dates').show();
				return;
			}
			$('#omni-custom-dates').hide();
			var r = dateRange(activePreset);
			$('#omni-date-from').val(r.from);
			$('#omni-date-to').val(r.to);
			triggerDateChange(r.from, r.to);
		});

		$('#omni-apply-dates').on('click', function () {
			triggerDateChange($('#omni-date-from').val(), $('#omni-date-to').val());
		});
	});

}(jQuery));
