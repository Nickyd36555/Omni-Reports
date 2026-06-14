<?php
/**
 * Stock Tracker report page.
 *
 * @package OmniReports
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="omni-page-inner">

	<!-- Reorder alert banner -->
	<div id="omni-reorder-banner" class="omni-notice omni-notice-warning" style="display:none;">
		<span class="dashicons dashicons-warning"></span>
		<span id="omni-reorder-banner-text"></span>
	</div>

	<!-- KPI Cards -->
	<div class="omni-kpi-grid" id="omni-stock-kpis">
		<?php
		$cards = [
			[ 'id' => 'total',     'label' => 'Total Products',   'icon' => 'dashicons-products' ],
			[ 'id' => 'instock',   'label' => 'In Stock',         'icon' => 'dashicons-yes-alt' ],
			[ 'id' => 'lowstock',  'label' => 'Low Stock (≤ reorder pt)', 'icon' => 'dashicons-warning' ],
			[ 'id' => 'outstock',  'label' => 'Out of Stock',     'icon' => 'dashicons-no-alt' ],
			[ 'id' => 'value',     'label' => 'Total Stock Value', 'icon' => 'dashicons-money-alt' ],
		];
		foreach ( $cards as $c ) :
		?>
		<div class="omni-kpi-card" id="omni-stock-kpi-<?php echo esc_attr( $c['id'] ); ?>">
			<div class="omni-kpi-icon"><span class="dashicons <?php echo esc_attr( $c['icon'] ); ?>"></span></div>
			<div class="omni-kpi-body">
				<div class="omni-kpi-label"><?php echo esc_html( $c['label'] ); ?></div>
				<div class="omni-kpi-value omni-skeleton">—</div>
			</div>
		</div>
		<?php endforeach; ?>
	</div>

	<!-- Filters -->
	<div class="omni-stock-filters">
		<div class="omni-filter-tabs" id="omni-stock-filter-tabs">
			<button class="omni-filter-tab active" data-filter="all">All</button>
			<button class="omni-filter-tab" data-filter="instock">In Stock</button>
			<button class="omni-filter-tab" data-filter="lowstock">Low Stock</button>
			<button class="omni-filter-tab" data-filter="outofstock">Out of Stock</button>
		</div>
		<div class="omni-stock-search-wrap">
			<input type="text" id="omni-stock-search" class="omni-input" placeholder="Search products…" />
		</div>
		<button class="omni-btn omni-btn-secondary" id="omni-stock-export-csv">
			<span class="dashicons dashicons-download"></span> Export CSV
		</button>
	</div>

	<!-- Table -->
	<div class="omni-card">
		<div id="omni-stock-table-wrap">
			<div class="omni-loading"><span class="omni-spinner"></span> Loading stock data…</div>
		</div>
	</div>

</div>

<script>
(function($) {
	var allProducts = [];
	var activeFilter = 'all';

	function stockStatusClass(status, qty, reorder) {
		if (status === 'outofstock') return 'omni-badge-red';
		if (parseFloat(qty) <= parseFloat(reorder)) return 'omni-badge-yellow';
		return 'omni-badge-green';
	}

	function stockStatusLabel(status, qty, reorder) {
		if (status === 'outofstock') return 'Out of Stock';
		if (parseFloat(qty) <= parseFloat(reorder)) return 'Low Stock';
		return 'In Stock';
	}

	function renderTable(products) {
		var $wrap = $('#omni-stock-table-wrap').empty();
		if (!products || !products.length) {
			$wrap.html('<div class="omni-empty-state">No products found.</div>');
			return;
		}

		var html = '<table class="omni-data-table omni-stock-table">' +
			'<thead><tr>' +
			'<th>Product</th>' +
			'<th>SKU</th>' +
			'<th>Stock Qty</th>' +
			'<th>Status</th>' +
			'<th>Stock Value</th>' +
			'<th>Reorder Point</th>' +
			'<th>Last Updated</th>' +
			'</tr></thead><tbody>';

		products.forEach(function(p) {
			var statusCls = stockStatusClass(p.stock_status, p.stock_qty, p.reorder_point);
			var statusLbl = stockStatusLabel(p.stock_status, p.stock_qty, p.reorder_point);
			var nameEsc   = $('<div>').text(p.name || '').html();
			var skuEsc    = $('<div>').text(p.sku  || '—').html();

			html += '<tr data-status="' + (p.stock_status || '') + '" data-id="' + parseInt(p.ID) + '">' +
				'<td class="omni-stock-name">' + nameEsc + '</td>' +
				'<td>' + skuEsc + '</td>' +
				'<td>' + (parseFloat(p.stock_qty) || 0).toLocaleString() + '</td>' +
				'<td><span class="omni-status-badge ' + statusCls + '">' + statusLbl + '</span></td>' +
				'<td>' + omniReports.formatCurrency(p.stock_value) + '</td>' +
				'<td><input type="number" class="omni-reorder-input" data-product-id="' + parseInt(p.ID) + '" value="' + parseInt(p.reorder_point) + '" min="0" style="width:60px;padding:4px 6px;border:1px solid #E2E8F0;border-radius:6px;font-size:12px;" /></td>' +
				'<td class="omni-muted" style="font-size:11px;">—</td>' +
				'</tr>';
		});

		html += '</tbody></table>';
		$wrap.html(html);

		// Inline reorder save
		$wrap.on('change', '.omni-reorder-input', function() {
			var $input     = $(this);
			var productId  = $input.data('product-id');
			var newPoint   = parseInt($input.val()) || 0;
			$input.css('border-color', '#F6AD55');
			$.post(omniReports.ajaxUrl, {
				action:        'omni_save_reorder_point',
				nonce:         omniReports.nonce,
				product_id:    productId,
				reorder_point: newPoint,
			}, function(res) {
				if (res.success) {
					$input.css('border-color', '#00D4AA');
					setTimeout(function(){ $input.css('border-color', '#E2E8F0'); }, 1500);
					// Update local data
					var p = allProducts.find(function(x){ return parseInt(x.ID) === parseInt(productId); });
					if (p) p.reorder_point = newPoint;
				} else {
					$input.css('border-color', '#FC8181');
				}
			});
		});
	}

	function applyFilter() {
		var search = ($('#omni-stock-search').val() || '').toLowerCase();
		var filtered = allProducts.filter(function(p) {
			var nameMatch = !search || (p.name || '').toLowerCase().indexOf(search) !== -1;
			var filterMatch = true;
			if (activeFilter === 'instock')   filterMatch = p.stock_status === 'instock' && parseFloat(p.stock_qty) > parseFloat(p.reorder_point);
			if (activeFilter === 'lowstock')  filterMatch = p.stock_status !== 'outofstock' && parseFloat(p.stock_qty) <= parseFloat(p.reorder_point);
			if (activeFilter === 'outofstock')filterMatch = p.stock_status === 'outofstock';
			return nameMatch && filterMatch;
		});
		renderTable(filtered);
	}

	function renderKpis(products) {
		var total    = products.length;
		var instock  = products.filter(function(p){ return p.stock_status === 'instock' && parseFloat(p.stock_qty) > parseFloat(p.reorder_point); }).length;
		var lowstock = products.filter(function(p){ return p.stock_status !== 'outofstock' && parseFloat(p.stock_qty) <= parseFloat(p.reorder_point); }).length;
		var outstock = products.filter(function(p){ return p.stock_status === 'outofstock'; }).length;
		var totalVal = products.reduce(function(sum, p){ return sum + parseFloat(p.stock_value||0); }, 0);
		var reorderNeeded = products.filter(function(p){ return p.needs_reorder; }).length;

		$('#omni-stock-kpi-total .omni-kpi-value').removeClass('omni-skeleton').text(total.toLocaleString());
		$('#omni-stock-kpi-instock .omni-kpi-value').removeClass('omni-skeleton').text(instock.toLocaleString());
		$('#omni-stock-kpi-lowstock .omni-kpi-value').removeClass('omni-skeleton').text(lowstock.toLocaleString());
		$('#omni-stock-kpi-outstock .omni-kpi-value').removeClass('omni-skeleton').text(outstock.toLocaleString());
		$('#omni-stock-kpi-value .omni-kpi-value').removeClass('omni-skeleton').text(omniReports.formatCurrency(totalVal));

		if (reorderNeeded > 0) {
			$('#omni-reorder-banner-text').text(
				reorderNeeded + ' ' + (reorderNeeded === 1 ? 'product needs' : 'products need') + ' reordering.'
			);
			$('#omni-reorder-banner').show();
		}
	}

	function loadStock() {
		$('#omni-stock-table-wrap').html('<div class="omni-loading"><span class="omni-spinner"></span> Loading stock data…</div>');
		$.post(omniReports.ajaxUrl, {
			action: 'omni_get_stock_report',
			nonce:  omniReports.nonce,
		}, function(res) {
			if (!res.success) {
				$('#omni-stock-table-wrap').html('<div class="omni-empty-state">Error loading data.</div>');
				return;
			}
			allProducts = res.data || [];
			renderKpis(allProducts);
			applyFilter();
		});
	}

	$(document).ready(function() {
		loadStock();

		// Filter tabs
		$(document).on('click', '#omni-stock-filter-tabs .omni-filter-tab', function() {
			$('#omni-stock-filter-tabs .omni-filter-tab').removeClass('active');
			$(this).addClass('active');
			activeFilter = $(this).data('filter');
			applyFilter();
		});

		// Search
		var searchTimer;
		$('#omni-stock-search').on('input', function() {
			clearTimeout(searchTimer);
			searchTimer = setTimeout(applyFilter, 300);
		});

		// Export CSV
		$('#omni-stock-export-csv').on('click', function() {
			var form = $('<form>', { method: 'POST', action: omniReports.ajaxUrl }).hide();
			form.append($('<input>', { name: 'action', value: 'omni_export_stock_csv' }));
			form.append($('<input>', { name: 'nonce',  value: omniReports.nonce }));
			$('body').append(form);
			form.submit();
			setTimeout(function(){ form.remove(); }, 2000);
		});
	});
})(jQuery);
</script>
