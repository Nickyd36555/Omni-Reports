<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="coupons">
	<h1><?php esc_html_e( 'Coupons Report', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<!-- Search bar -->
	<div class="omni-chart-card" style="padding:16px 20px">
		<div style="display:flex;align-items:center;gap:10px">
			<input type="text" id="omni-coupon-search" placeholder="<?php esc_attr_e( 'Search coupon code…', 'omni-reports' ); ?>"
				style="flex:1;max-width:320px;padding:8px 12px;border:1px solid var(--omni-border,#E2E8F0);border-radius:8px;font-size:13px;outline:none">
			<button class="omni-btn omni-btn-primary omni-btn-sm" id="omni-coupon-search-btn">
				<?php esc_html_e( 'Search', 'omni-reports' ); ?>
			</button>
			<button class="omni-btn omni-btn-ghost omni-btn-sm" id="omni-coupon-clear-btn">
				<?php esc_html_e( 'Clear', 'omni-reports' ); ?>
			</button>
			<span id="omni-coupon-count" style="font-size:12px;color:#718096;margin-left:auto"></span>
		</div>
	</div>

	<!-- KPI summary -->
	<div class="omni-kpi-grid" id="omni-coupon-kpis" style="margin-bottom:20px">
		<div class="omni-kpi-card">
			<span class="omni-kpi-label"><?php esc_html_e( 'Total Coupons Used', 'omni-reports' ); ?></span>
			<span class="omni-kpi-value" id="kpi-coupon-count">—</span>
		</div>
		<div class="omni-kpi-card">
			<span class="omni-kpi-label"><?php esc_html_e( 'Total Discount Given', 'omni-reports' ); ?></span>
			<span class="omni-kpi-value" id="kpi-coupon-discount">—</span>
		</div>
		<div class="omni-kpi-card">
			<span class="omni-kpi-label"><?php esc_html_e( 'Total Revenue (with coupon)', 'omni-reports' ); ?></span>
			<span class="omni-kpi-value" id="kpi-coupon-revenue">—</span>
		</div>
		<div class="omni-kpi-card">
			<span class="omni-kpi-label"><?php esc_html_e( 'Avg Discount per Use', 'omni-reports' ); ?></span>
			<span class="omni-kpi-value" id="kpi-coupon-avg">—</span>
		</div>
	</div>

	<!-- Bar chart -->
	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Top Coupons by Discount Amount', 'omni-reports' ); ?></h2>
		<div id="omni-coupon-chart-wrap">
			<canvas id="omni-coupon-bar" height="80"></canvas>
		</div>
	</div>

	<!-- Table -->
	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Coupon Breakdown', 'omni-reports' ); ?></h2>
		<div id="omni-coupons-table-wrap"><p class="omni-loading"><span class="omni-spinner"></span><?php esc_html_e( 'Loading…', 'omni-reports' ); ?></p></div>
	</div>
</div>

<script>
jQuery(function($){
	var currentSearch = '';
	var fmt = omniReports.formatCurrency;

	function load(from, to){
		$('#omni-coupons-table-wrap').html('<p class="omni-loading"><span class="omni-spinner"></span>Loading…</p>');
		$.post(omniReports.ajaxUrl, {
			action:    'omni_get_coupons_report',
			nonce:     omniReports.nonce,
			date_from: from,
			date_to:   to,
			search:    currentSearch,
		}, function(r){
			if(!r.success) return;
			var rows = r.data || [];

			// KPIs
			var totalUses     = 0, totalDiscount = 0, totalRevenue = 0;
			rows.forEach(function(d){
				totalUses     += parseInt(d.usage_count) || 0;
				totalDiscount += parseFloat(d.discount_amount) || 0;
				totalRevenue  += parseFloat(d.revenue) || 0;
			});
			$('#kpi-coupon-count').text(totalUses.toLocaleString());
			$('#kpi-coupon-discount').text(fmt(totalDiscount));
			$('#kpi-coupon-revenue').text(fmt(totalRevenue));
			$('#kpi-coupon-avg').text(totalUses ? fmt(totalDiscount / totalUses) : fmt(0));
			$('#omni-coupon-count').text(rows.length + ' coupon' + (rows.length !== 1 ? 's' : '') + ' found');

			// Chart — top 15
			if(rows.length){
				var top = rows.slice(0, 15);
				omniReports.renderBarChart('omni-coupon-bar', top, 'coupon_code', 'discount_amount', 'Discount Amount');
			}

			// Table
			if(!rows.length){
				$('#omni-coupons-table-wrap').html('<p class="omni-empty-state">No coupon data found for this period.</p>');
				return;
			}
			var html = '<table class="omni-data-table"><thead><tr>' +
				'<th>Coupon Code</th>' +
				'<th class="num">Times Used</th>' +
				'<th class="num">Total Discount</th>' +
				'<th class="num">Revenue</th>' +
				'<th class="num">Avg Discount</th>' +
				'</tr></thead><tbody>';
			rows.forEach(function(d){
				var uses = parseInt(d.usage_count) || 0;
				var disc = parseFloat(d.discount_amount) || 0;
				html += '<tr>' +
					'<td><strong>' + $('<span>').text(d.coupon_code || '—').html() + '</strong></td>' +
					'<td class="num">' + uses.toLocaleString() + '</td>' +
					'<td class="num">' + fmt(disc) + '</td>' +
					'<td class="num">' + fmt(parseFloat(d.revenue) || 0) + '</td>' +
					'<td class="num">' + fmt(uses ? disc / uses : 0) + '</td>' +
					'</tr>';
			});
			html += '</tbody></table>';
			$('#omni-coupons-table-wrap').html(html);
		});
	}

	// Search
	$('#omni-coupon-search-btn').on('click', function(){
		currentSearch = $.trim($('#omni-coupon-search').val());
		load(omniReports.currentFrom(), omniReports.currentTo());
	});
	$('#omni-coupon-clear-btn').on('click', function(){
		currentSearch = '';
		$('#omni-coupon-search').val('');
		load(omniReports.currentFrom(), omniReports.currentTo());
	});
	$('#omni-coupon-search').on('keypress', function(e){
		if(e.which === 13){
			$('#omni-coupon-search-btn').trigger('click');
		}
	});

	$('#omni-export-csv').on('click', function(){ omniReports.exportCsv('coupons'); });
	omniReports.onDateChange(load);
	load(omniReports.currentFrom(), omniReports.currentTo());
});
</script>
