/* Omni Reports — Report Manager JS */
/* global jQuery, omniReports */

(function ($) {
	'use strict';

	var editingId = null;

	// ── Modal helpers ────────────────────────────────────────────────────────

	function openModal( title, saveBtnText ) {
		$('#omni-modal-title').text( title || 'Create New Report' );
		$('#omni-modal-save').text( saveBtnText || '+ Create Report' );
		$('#omni-report-modal').show();
		$('body').addClass('omni-modal-open');
	}

	function closeModal() {
		$('#omni-report-modal').hide();
		$('body').removeClass('omni-modal-open');
		resetModal();
		editingId = null;
	}

	function resetModal() {
		$('#rpt-name').val('');
		$('#rpt-slug').val('');
		$('#rpt-category').val('sales');
		$('#rpt-version').val('1.0');
		$('#rpt-order').val('0');
		$('#rpt-visible').prop('checked', true);
		$('#rpt-icon').val('dashicons-chart-bar');
		$('#rpt-color').val('teal');
		$('#rpt-description').val('');
		$('#rpt-csv').prop('checked', true);
		$('#rpt-print').prop('checked', true);
		$('.omni-modal-tab').first().trigger('click');
		$('.omni-icon-option').removeClass('selected');
		$('.omni-icon-option[data-icon="dashicons-chart-bar"]').addClass('selected');
		$('.omni-color-option').removeClass('selected');
		$('.omni-color-option[data-color="teal"]').addClass('selected');
	}

	function populateModal( report ) {
		$('#rpt-name').val( report.name || '' );
		$('#rpt-slug').val( report.slug || '' );
		$('#rpt-category').val( report.category || 'sales' );
		$('#rpt-version').val( report.version || '1.0' );
		$('#rpt-order').val( report.menu_order || 0 );
		$('#rpt-visible').prop('checked', !! report.visible );
		$('#rpt-icon').val( report.icon || 'dashicons-chart-bar' );
		$('#rpt-color').val( report.color || 'teal' );
		$('#rpt-description').val( report.description || '' );
		$('#rpt-csv').prop('checked', report.csv_export !== false );
		$('#rpt-print').prop('checked', report.printable !== false );

		$('.omni-icon-option').removeClass('selected');
		$('.omni-icon-option[data-icon="' + ( report.icon || 'dashicons-chart-bar' ) + '"]').addClass('selected');
		$('.omni-color-option').removeClass('selected');
		$('.omni-color-option[data-color="' + ( report.color || 'teal' ) + '"]').addClass('selected');
	}

	// ── Slug auto-generation ─────────────────────────────────────────────────

	function slugify( str ) {
		return str.toLowerCase().trim()
			.replace( /[^a-z0-9\s-]/g, '' )
			.replace( /[\s_]+/g, '-' )
			.replace( /-+/g, '-' );
	}

	// ── Filter tabs ──────────────────────────────────────────────────────────

	function applyFilter( cat ) {
		$('.omni-report-card').each( function () {
			var cardCat = $(this).data('cat');
			if ( cat === 'all' || cardCat === cat ) {
				$(this).show();
			} else {
				$(this).hide();
			}
		});
	}

	// ── Save report via AJAX ─────────────────────────────────────────────────

	function saveReport() {
		var name = $.trim( $('#rpt-name').val() );
		if ( ! name ) {
			$('#rpt-name').focus();
			return;
		}

		var slug = $.trim( $('#rpt-slug').val() ) || slugify( name );

		var report = {
			id:          editingId || ( 'rpt_' + Date.now() ),
			name:        name,
			slug:        slug,
			category:    $('#rpt-category').val(),
			version:     $('#rpt-version').val() || '1.0',
			menu_order:  parseInt( $('#rpt-order').val() ) || 0,
			visible:     $('#rpt-visible').is(':checked'),
			icon:        $('#rpt-icon').val() || 'dashicons-chart-bar',
			color:       $('#rpt-color').val() || 'teal',
			description: $('#rpt-description').val(),
			csv_export:  $('#rpt-csv').is(':checked'),
			printable:   $('#rpt-print').is(':checked'),
			type:        'custom',
			required:    false,
			page_slug:   'omni-reports-builder',
		};

		$('#omni-modal-save').prop('disabled', true).text('Saving…');

		$.post( omniReports.ajaxUrl, {
			action: 'omni_save_report_meta',
			nonce:  omniReports.nonce,
			report: JSON.stringify( report ),
		}, function ( r ) {
			$('#omni-modal-save').prop('disabled', false).text('+ Create Report');
			if ( r.success ) {
				closeModal();
				location.reload();
			}
		});
	}

	// ── Init ─────────────────────────────────────────────────────────────────

	$(function () {

		// Filter tabs.
		$(document).on( 'click', '.omni-filter-tab', function () {
			$('.omni-filter-tab').removeClass('active');
			$(this).addClass('active');
			applyFilter( $(this).data('cat') );
		});

		// Open create modal.
		$('#omni-create-report-btn').on( 'click', function () {
			editingId = null;
			resetModal();
			openModal('Create New Report', '+ Create Report');
		});

		// Open edit modal.
		$(document).on( 'click', '.omni-edit-report', function () {
			var report = $(this).data('report');
			if ( typeof report === 'string' ) {
				try { report = JSON.parse( report ); } catch(e) { return; }
			}
			editingId = report.id;
			populateModal( report );
			openModal('Edit Report', 'Save Changes');
		});

		// Close modal.
		$('#omni-modal-close, #omni-modal-cancel').on('click', closeModal);
		$('#omni-report-modal').on('click', function(e) {
			if ($(e.target).is('#omni-report-modal')) closeModal();
		});

		// Save modal.
		$('#omni-modal-save').on('click', saveReport);

		// Modal tab switching.
		$(document).on('click', '.omni-modal-tab', function () {
			var tab = $(this).data('tab');
			$('.omni-modal-tab').removeClass('active');
			$(this).addClass('active');
			$('.omni-tab-panel').removeClass('active');
			$('#tab-' + tab).addClass('active');
		});

		// Auto-slug from name.
		$('#rpt-name').on('input', function () {
			if ( ! editingId ) {
				$('#rpt-slug').val( slugify( $(this).val() ) );
			}
		});

		// Icon picker.
		$(document).on('click', '.omni-icon-option', function () {
			$('.omni-icon-option').removeClass('selected');
			$(this).addClass('selected');
			$('#rpt-icon').val( $(this).data('icon') );
		});

		// Color picker.
		$(document).on('click', '.omni-color-option', function () {
			$('.omni-color-option').removeClass('selected');
			$(this).addClass('selected');
			$('#rpt-color').val( $(this).data('color') );
		});

		// Delete report.
		$(document).on('click', '.omni-delete-report', function () {
			var id = $(this).data('id');
			if ( ! id ) return;
			if ( ! confirm('Delete this report? This cannot be undone.') ) return;
			var $card = $(this).closest('.omni-report-card');
			$.post( omniReports.ajaxUrl, {
				action: 'omni_delete_report_meta',
				nonce:  omniReports.nonce,
				id:     id,
			}, function (r) {
				if ( r.success ) { $card.fadeOut(200, function(){ $(this).remove(); }); }
			});
		});

		// Move up.
		$(document).on('click', '.omni-order-up', function () {
			var $card = $(this).closest('.omni-report-card');
			var $prev = $card.prev('.omni-report-card');
			if ( $prev.length ) {
				$card.insertBefore($prev);
				syncOrder();
			}
		});

		// Move down.
		$(document).on('click', '.omni-order-down', function () {
			var $card = $(this).closest('.omni-report-card');
			var $next = $card.next('.omni-report-card');
			if ( $next.length ) {
				$card.insertAfter($next);
				syncOrder();
			}
		});

		// Duplicate report.
		$(document).on('click', '.omni-duplicate-report', function () {
			var id   = $(this).data('id');
			var $card = $(this).closest('.omni-report-card');
			// Find report data from edit button on same card.
			var report = $card.find('.omni-edit-report').data('report');
			if ( typeof report === 'string' ) {
				try { report = JSON.parse(report); } catch(e) { return; }
			}
			var copy = Object.assign({}, report, {
				id:   'rpt_' + Date.now(),
				name: report.name + ' (Copy)',
				slug: report.slug + '-copy',
			});
			$.post( omniReports.ajaxUrl, {
				action: 'omni_save_report_meta',
				nonce:  omniReports.nonce,
				report: JSON.stringify(copy),
			}, function(r) {
				if (r.success) location.reload();
			});
		});

		// Install defaults.
		$('#omni-install-defaults').on('click', function () {
			var $btn = $(this).prop('disabled', true).text('Installing…');
			$.post( omniReports.ajaxUrl, {
				action: 'omni_install_defaults',
				nonce:  omniReports.nonce,
			}, function(r) {
				if (r.success) location.reload();
				else $btn.prop('disabled', false).text('Install default reports');
			});
		});

		// Keyboard close.
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape') closeModal();
		});
	});

	function syncOrder() {
		var ids = [];
		$('.omni-report-grid .omni-report-card').each(function() {
			ids.push( $(this).data('id') );
		});
		$.post( omniReports.ajaxUrl, {
			action: 'omni_reorder_reports',
			nonce:  omniReports.nonce,
			order:  JSON.stringify(ids),
		});
	}

}(jQuery));
