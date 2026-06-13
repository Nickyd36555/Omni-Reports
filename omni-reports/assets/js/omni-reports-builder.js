/* Omni Reports — Report Builder JS */
/* global jQuery, omniReports */

(function ($) {
	'use strict';

	var lastResults = [];
	var lastConfig  = {};

	function getConfig() {
		var metrics = [];
		$('#builder-metrics input[type=checkbox]:checked').each(function () {
			metrics.push($(this).val());
		});

		var filters = {};
		var minOrd = $('#filter-min-order').val();
		var maxOrd = $('#filter-max-order').val();
		if (minOrd) filters.min_order_value = minOrd;
		if (maxOrd) filters.max_order_value = maxOrd;

		return {
			metrics:   metrics,
			dimension: $('#builder-dimension').val(),
			date_from: $('#builder-date-from').val(),
			date_to:   $('#builder-date-to').val(),
			filters:   filters,
			limit:     500,
		};
	}

	function showNotice(msg, type) {
		$('#omni-builder-notice')
			.removeClass('notice-success notice-error')
			.addClass('notice-' + (type || 'success'))
			.html('<p>' + msg + '</p>')
			.show();
		setTimeout(function () { $('#omni-builder-notice').hide(); }, 4000);
	}

	function renderResults(data, chartType) {
		lastResults = data;
		var $tableWrap = $('#omni-builder-table-wrap');
		var $chartWrap = $('#omni-builder-chart-wrap');

		if (!data || !data.length) {
			$tableWrap.html('<p class="omni-empty-state">No data returned. Try a wider date range or different metrics.</p>');
			$chartWrap.hide();
			return;
		}

		// Always show table.
		omniReports.renderTable('omni-builder-table-wrap', data);

		// Chart.
		var cols     = Object.keys(data[0]);
		var dimKey   = cols[0]; // first col is always dimension
		var valueKey = cols[1]; // first metric

		if (chartType === 'table') {
			$chartWrap.hide();
		} else {
			$chartWrap.show();
			if (chartType === 'line') {
				omniReports.renderLineChart('omni-builder-chart', data, dimKey, valueKey, valueKey.replace(/_/g, ' '));
			} else if (chartType === 'bar') {
				omniReports.renderBarChart('omni-builder-chart', data, dimKey, valueKey, valueKey.replace(/_/g, ' '));
			} else if (chartType === 'pie') {
				omniReports.renderPieChart('omni-builder-chart', data, dimKey, valueKey);
			}
		}
	}

	function runReport() {
		var config    = getConfig();
		var chartType = $('#builder-chart-type').val();
		lastConfig    = config;

		$('#omni-builder-table-wrap').html('<div class="omni-loading">Loading</div>');
		$('#omni-builder-chart-wrap').hide();

		$.post(omniReports.ajaxUrl, {
			action: 'omni_run_builder',
			nonce:  omniReports.nonce,
			config: JSON.stringify(config),
		}, function (r) {
			if (!r.success) {
				showNotice('Error loading report.', 'error');
				$('#omni-builder-table-wrap').html('<p class="omni-empty-state">Error loading data.</p>');
				return;
			}
			renderResults(r.data, chartType);
		});
	}

	function loadSavedReports() {
		$.post(omniReports.ajaxUrl, {
			action: 'omni_get_saved_reports',
			nonce:  omniReports.nonce,
		}, function (r) {
			if (!r.success) return;
			var $sel = $('#omni-saved-select');
			$sel.find('option:not(:first)').remove();
			r.data.forEach(function (rpt) {
				$sel.append($('<option>').val(rpt.id).text(rpt.name).data('config', rpt.config));
			});
		});
	}

	$(function () {
		loadSavedReports();

		$('#omni-run-report').on('click', runReport);

		$('#omni-export-builder-csv').on('click', function () {
			omniReports.exportCsv('builder', null, lastConfig);
		});

		$('#omni-save-report').on('click', function () {
			var name = $('#omni-report-name').val().trim();
			if (!name) { showNotice('Please enter a report name.', 'error'); return; }

			$.post(omniReports.ajaxUrl, {
				action: 'omni_save_report',
				nonce:  omniReports.nonce,
				name:   name,
				config: JSON.stringify(lastConfig),
			}, function (r) {
				if (r.success) {
					showNotice('Report saved!');
					$('#omni-report-name').val('');
					loadSavedReports();
				} else {
					showNotice('Failed to save report.', 'error');
				}
			});
		});

		$('#omni-load-saved').on('click', function () {
			var $opt = $('#omni-saved-select option:selected');
			if (!$opt.val()) return;
			var config = $opt.data('config');
			if (!config) return;

			// Restore config to UI.
			if (config.date_from) $('#builder-date-from').val(config.date_from);
			if (config.date_to)   $('#builder-date-to').val(config.date_to);
			if (config.dimension) $('#builder-dimension').val(config.dimension);

			if (config.metrics) {
				$('#builder-metrics input[type=checkbox]').prop('checked', false);
				config.metrics.forEach(function (m) {
					$('#builder-metrics input[value="' + m + '"]').prop('checked', true);
				});
			}

			if (config.filters) {
				$('#filter-min-order').val(config.filters.min_order_value || '');
				$('#filter-max-order').val(config.filters.max_order_value || '');
			}

			lastConfig = config;
			runReport();
		});

		$('#omni-delete-saved').on('click', function () {
			var id = $('#omni-saved-select').val();
			if (!id) return;
			if (!confirm('Delete this saved report?')) return;

			$.post(omniReports.ajaxUrl, {
				action:    'omni_delete_report',
				nonce:     omniReports.nonce,
				report_id: id,
			}, function (r) {
				if (r.success) {
					showNotice('Report deleted.');
					loadSavedReports();
				}
			});
		});
	});

}(jQuery));
