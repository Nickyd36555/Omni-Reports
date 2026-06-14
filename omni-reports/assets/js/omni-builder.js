/**
 * Omni Reports — Drag-and-Drop Report Builder
 *
 * OmniBuilder class handles the canvas-based report builder.
 * Loaded on all omni-reports pages; only activates when #omni-dnd-builder exists.
 */
(function ($) {
	'use strict';

	if (!window.omniReports) return;

	// ── Utility ─────────────────────────────────────────────────────────────────

	var widgetIdCounter = 0;
	function genId() { return 'w' + (++widgetIdCounter) + '_' + Date.now(); }

	var widgetMeta = {
		'kpi-revenue':      { label: 'Revenue',          icon: 'dashicons-chart-line',    group: 'kpi' },
		'kpi-orders':       { label: 'Orders',           icon: 'dashicons-list-view',     group: 'kpi' },
		'kpi-aov':          { label: 'Avg Order Value',  icon: 'dashicons-cart',          group: 'kpi' },
		'kpi-profit':       { label: 'Profit',           icon: 'dashicons-chart-area',    group: 'kpi' },
		'kpi-refund-rate':  { label: 'Refund Rate',      icon: 'dashicons-undo',          group: 'kpi' },
		'kpi-customers':    { label: 'New Customers',    icon: 'dashicons-groups',        group: 'kpi' },
		'kpi-stock-value':  { label: 'Stock Value',      icon: 'dashicons-archive',       group: 'kpi' },
		'kpi-coupon-usage': { label: 'Coupon Usage',     icon: 'dashicons-tag',           group: 'kpi' },
		'chart-line':       { label: 'Line Chart',       icon: 'dashicons-chart-line',    group: 'chart' },
		'chart-bar':        { label: 'Bar Chart',        icon: 'dashicons-chart-bar',     group: 'chart' },
		'chart-pie':        { label: 'Pie / Donut',      icon: 'dashicons-chart-pie',     group: 'chart' },
		'chart-area':       { label: 'Area Chart',       icon: 'dashicons-chart-area',    group: 'chart' },
		'table-products':   { label: 'Products Table',   icon: 'dashicons-products',      group: 'table' },
		'table-customers':  { label: 'Customers Table',  icon: 'dashicons-groups',        group: 'table' },
		'table-orders':     { label: 'Orders Table',     icon: 'dashicons-list-view',     group: 'table' },
		'table-categories': { label: 'Categories Table', icon: 'dashicons-category',      group: 'table' },
		'table-coupons':    { label: 'Coupons Table',    icon: 'dashicons-tag',           group: 'table' },
		'layout-divider':   { label: 'Divider',          icon: 'dashicons-minus',         group: 'layout' },
		'layout-heading':   { label: 'Heading',          icon: 'dashicons-editor-paragraph', group: 'layout' },
		'layout-spacer':    { label: 'Spacer',           icon: 'dashicons-editor-kitchensink', group: 'layout' },
	};

	var colorMap = {
		teal:   '#00D4AA',
		blue:   '#0099FF',
		purple: '#9F7AEA',
		green:  '#48BB78',
		orange: '#ED8936',
		red:    '#FC8181',
	};

	// ── OmniBuilder Class ────────────────────────────────────────────────────────

	function OmniBuilder() {
		this.$builder  = $('#omni-dnd-builder');
		this.$canvas   = $('#omni-builder-canvas');
		this.$library  = $('#omni-widget-library');
		this.$config   = $('#omni-builder-config-panel');
		this.layout    = [];      // Array of widget state objects
		this.selected  = null;   // Currently selected widget id
		this.sortable  = null;
		this.reportId  = null;

		if (!this.$builder.length) return;

		this._initDragFromLibrary();
		this._initDropZone();
		this._initSortable();
		this._initToolbar();
		this._initConfig();
		this._loadSavedReports();
	}

	// ── Drag from Library ────────────────────────────────────────────────────────

	OmniBuilder.prototype._initDragFromLibrary = function () {
		var self = this;
		this.$library.find('.omni-widget-card').each(function () {
			var el = this;
			el.addEventListener('dragstart', function (e) {
				e.dataTransfer.effectAllowed = 'copy';
				e.dataTransfer.setData('text/plain', $(el).data('widget-type'));
				$(el).addClass('omni-dragging');
			});
			el.addEventListener('dragend', function () {
				$(el).removeClass('omni-dragging');
			});
		});
	};

	// ── Drop Zone ────────────────────────────────────────────────────────────────

	OmniBuilder.prototype._initDropZone = function () {
		var self = this;
		var canvas = this.$canvas[0];

		canvas.addEventListener('dragover', function (e) {
			e.preventDefault();
			e.dataTransfer.dropEffect = 'copy';
			$(canvas).addClass('omni-drag-over');
		});

		canvas.addEventListener('dragleave', function (e) {
			if (!canvas.contains(e.relatedTarget)) {
				$(canvas).removeClass('omni-drag-over');
			}
		});

		canvas.addEventListener('drop', function (e) {
			e.preventDefault();
			$(canvas).removeClass('omni-drag-over');
			var type = e.dataTransfer.getData('text/plain');
			if (type && widgetMeta[type]) {
				self.addWidget(type, {});
			}
		});
	};

	// ── Sortable ─────────────────────────────────────────────────────────────────

	OmniBuilder.prototype._initSortable = function () {
		var self = this;
		if (typeof Sortable === 'undefined') return;
		this.sortable = Sortable.create(this.$canvas[0], {
			animation: 150,
			handle: '.omni-canvas-widget-drag-handle',
			filter: '.omni-canvas-empty-state',
			onEnd: function () {
				self._syncLayoutFromDOM();
			},
		});
	};

	// ── Toolbar ──────────────────────────────────────────────────────────────────

	OmniBuilder.prototype._initToolbar = function () {
		var self = this;

		$('#omni-builder-save').on('click', function () { self.saveReport(); });
		$('#omni-builder-preview').on('click', function () { self.previewReport(); });
		$('#omni-builder-clear').on('click', function () {
			if (confirm('Clear all widgets from the canvas?')) {
				self.clearCanvas();
			}
		});

		$('#omni-builder-grid-width').on('change', function () {
			var val = $(this).val();
			self.$canvas.removeClass('omni-canvas-full omni-canvas-2col omni-canvas-3col');
			self.$canvas.addClass('omni-canvas-' + val);
		});
	};

	// ── Config Panel ─────────────────────────────────────────────────────────────

	OmniBuilder.prototype._initConfig = function () {
		var self = this;

		$('#omni-config-close').on('click', function () {
			self.deselectAll();
		});

		$('#omni-config-apply').on('click', function () {
			if (!self.selected) return;
			var item = self._findItem(self.selected);
			if (!item) return;
			item.config.title  = $('#omni-config-title-input').val();
			item.config.width  = $('#omni-config-width').val();
			item.config.color  = $('#omni-config-color').val();
			item.config.chartType = $('#omni-config-chart-type').val();
			item.config.metric    = $('#omni-config-metric').val();
			self._updateWidgetEl(self.selected, item);
		});

		$(document).on('click', '.omni-canvas-widget-remove', function () {
			var id = $(this).closest('.omni-canvas-widget').data('widget-id');
			self.removeWidget(id);
		});

		$(document).on('click', '.omni-canvas-widget', function (e) {
			if ($(e.target).hasClass('omni-canvas-widget-remove')) return;
			var id = $(this).data('widget-id');
			self.selectWidget(id);
		});
	};

	// ── Load Saved Reports (for legacy dropdown) ──────────────────────────────────

	OmniBuilder.prototype._loadSavedReports = function () {
		$.post(omniReports.ajaxUrl, {
			action: 'omni_get_saved_reports',
			nonce:  omniReports.nonce,
		}, function (res) {
			if (!res.success) return;
			var $sel = $('#omni-saved-select').empty().append('<option value="">— Load saved report —</option>');
			(res.data || []).forEach(function (r) {
				$sel.append($('<option>', { value: r.id, text: r.name }));
			});
		});

		$('#omni-load-saved').on('click', function () {
			var id = $('#omni-saved-select').val();
			if (!id) return;
			$('#omni-legacy-results').show();
		});

		$('#omni-close-legacy').on('click', function () {
			$('#omni-legacy-results').hide();
		});

		$('#omni-delete-saved').on('click', function () {
			var id = $('#omni-saved-select').val();
			if (!id) return;
			if (!confirm('Delete this saved report?')) return;
			$.post(omniReports.ajaxUrl, {
				action:    'omni_delete_report',
				nonce:     omniReports.nonce,
				report_id: id,
			}, function (res) {
				if (res.success) location.reload();
			});
		});
	};

	// ── Core API ─────────────────────────────────────────────────────────────────

	OmniBuilder.prototype.addWidget = function (type, config) {
		var id   = genId();
		var meta = widgetMeta[type] || { label: type, icon: 'dashicons-layout', group: 'other' };
		var item = { id: id, widget: type, config: $.extend({ width: '1/2', color: 'teal', title: meta.label }, config) };
		this.layout.push(item);
		var $el = this._buildWidgetEl(item, meta);
		this.$canvas.find('.omni-canvas-empty-state').hide();
		this.$canvas.append($el);
		return id;
	};

	OmniBuilder.prototype.removeWidget = function (id) {
		this.layout = this.layout.filter(function (i) { return i.id !== id; });
		this.$canvas.find('[data-widget-id="' + id + '"]').remove();
		if (this.selected === id) this.deselectAll();
		if (!this.layout.length) this.$canvas.find('.omni-canvas-empty-state').show();
	};

	OmniBuilder.prototype.selectWidget = function (id) {
		this.deselectAll();
		this.selected = id;
		this.$canvas.find('[data-widget-id="' + id + '"]').addClass('selected');
		this.$config.show();
		var item = this._findItem(id);
		if (!item) return;
		var meta  = widgetMeta[item.widget] || {};
		$('#omni-config-title').text((meta.label || item.widget) + ' Config');
		$('#omni-config-title-input').val(item.config.title || '');
		$('#omni-config-width').val(item.config.width || '1/2');
		$('#omni-config-color').val(item.config.color || 'teal');
		$('#omni-config-chart-type').val(item.config.chartType || 'bar');
		$('#omni-config-metric').val(item.config.metric || 'revenue');

		var isChart = meta.group === 'chart';
		var isKpi   = meta.group === 'kpi';
		$('.omni-config-chart-only').toggle(isChart);
		$('.omni-config-metric-only').toggle(isKpi);
	};

	OmniBuilder.prototype.deselectAll = function () {
		this.selected = null;
		this.$canvas.find('.omni-canvas-widget').removeClass('selected');
		this.$config.hide();
	};

	OmniBuilder.prototype.clearCanvas = function () {
		this.layout = [];
		this.selected = null;
		this.$config.hide();
		this.$canvas.find('.omni-canvas-widget').remove();
		this.$canvas.find('.omni-canvas-empty-state').show();
	};

	OmniBuilder.prototype.saveReport = function () {
		var self = this;
		var name = $('#omni-builder-report-name').val() || 'Untitled Report';
		this._syncLayoutFromDOM();
		$.post(omniReports.ajaxUrl, {
			action: 'omni_save_builder_report',
			nonce:  omniReports.nonce,
			name:   name,
			id:     self.reportId || '',
			layout: JSON.stringify(self.layout),
		}, function (res) {
			if (res.success) {
				self.reportId = res.data.id;
				self._showNotice('Report saved successfully.', 'success');
			} else {
				self._showNotice('Error saving report.', 'error');
			}
		});
	};

	OmniBuilder.prototype.previewReport = function () {
		this._showNotice('Preview renders live data — ensure date filters are applied above.', 'warning');
	};

	// ── Helpers ──────────────────────────────────────────────────────────────────

	OmniBuilder.prototype._findItem = function (id) {
		return this.layout.find(function (i) { return i.id === id; }) || null;
	};

	OmniBuilder.prototype._syncLayoutFromDOM = function () {
		var self = this;
		var order = [];
		this.$canvas.find('.omni-canvas-widget').each(function () {
			order.push($(this).data('widget-id'));
		});
		var indexed = {};
		self.layout.forEach(function (i) { indexed[i.id] = i; });
		self.layout = order.map(function (id) { return indexed[id]; }).filter(Boolean);
	};

	OmniBuilder.prototype._buildWidgetEl = function (item, meta) {
		var self   = this;
		meta       = meta || widgetMeta[item.widget] || { label: item.widget, icon: 'dashicons-layout', group: 'other' };
		var color  = colorMap[item.config.color || 'teal'] || '#00D4AA';
		var width  = item.config.width || '1/2';
		var title  = item.config.title || meta.label;
		var wCls   = 'omni-widget-width-' + width.replace('/', '-');

		var $el = $(
			'<div class="omni-canvas-widget ' + wCls + '" data-widget-id="' + item.id + '">' +
			'  <div class="omni-canvas-widget-drag-handle" title="Drag to reorder">⠿</div>' +
			'  <div class="omni-canvas-widget-icon" style="color:' + color + '"><span class="dashicons ' + meta.icon + '"></span></div>' +
			'  <div class="omni-canvas-widget-title">' + $('<div>').text(title).html() + '</div>' +
			'  <div class="omni-canvas-widget-group omni-muted">' + (meta.group || '') + '</div>' +
			'  <button class="omni-canvas-widget-remove" title="Remove">×</button>' +
			'</div>'
		);
		return $el;
	};

	OmniBuilder.prototype._updateWidgetEl = function (id, item) {
		var meta = widgetMeta[item.widget] || { label: item.widget, icon: 'dashicons-layout', group: 'other' };
		var $el  = this.$canvas.find('[data-widget-id="' + id + '"]');
		var newEl = this._buildWidgetEl(item, meta);
		newEl.addClass('selected');
		$el.replaceWith(newEl);
	};

	OmniBuilder.prototype._showNotice = function (msg, type) {
		var $n = $('#omni-builder-notice-global');
		$n.removeClass('omni-notice-warning omni-notice-success omni-notice-error')
		  .addClass('omni-notice-' + (type || 'success'))
		  .text(msg).show();
		setTimeout(function () { $n.fadeOut(); }, 3000);
	};

	// ── Init ─────────────────────────────────────────────────────────────────────

	$(document).ready(function () {
		if ($('#omni-dnd-builder').length) {
			window.omniBuilderInstance = new OmniBuilder();
		}
	});

})(jQuery);
