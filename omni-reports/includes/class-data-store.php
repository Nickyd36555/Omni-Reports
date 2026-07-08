<?php
/**
 * Data Store — all WooCommerce DB queries.
 *
 * Targets WC 4.0+ analytics tables with graceful fallback notes.
 *
 * @package OmniReports
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Omni_Reports_Data_Store
 */
class Omni_Reports_Data_Store {

	/** @var wpdb */
	private $wpdb;

	/**
	 * Non-revenue statuses — wc_order_stats stores without the wc- prefix.
	 * We list both forms so the exclusion works regardless of WC version/storage.
	 */
	private $excluded_statuses = [
		'pending', 'cancelled', 'failed', 'checkout-draft',
		'wc-pending', 'wc-cancelled', 'wc-failed', 'wc-checkout-draft',
	];

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a safe SQL IN list from an array of strings.
	 *
	 * @param string[] $items
	 * @return string  Comma-separated quoted values ready for SQL IN (...).
	 */
	/**
	 * Check whether the WC analytics tables exist (WC 4.0+).
	 */
	public function analytics_tables_exist() {
		$table = $this->wpdb->prefix . 'wc_order_stats';
		return (bool) $this->wpdb->get_var(
			$this->wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
		);
	}

	private function in_list( array $items ) {
		return implode( ',', array_map( function ( $s ) {
			return "'" . esc_sql( $s ) . "'";
		}, $items ) );
	}

	/**
	 * Return the excluded statuses IN clause.
	 */
	private function excluded_in() {
		return $this->in_list( $this->excluded_statuses );
	}

	/**
	 * Ensure dates are valid Y-m-d strings; fall back to sensible defaults.
	 *
	 * @param string $date_from
	 * @param string $date_to
	 * @return array{0:string,1:string}
	 */
	public function sanitize_dates( $date_from, $date_to ) {
		$fmt       = 'Y-m-d';
		$date_from = sanitize_text_field( $date_from );
		$date_to   = sanitize_text_field( $date_to );

		if ( ! $date_from || ! strtotime( $date_from ) ) {
			$date_from = gmdate( $fmt, strtotime( '-30 days' ) );
		}
		if ( ! $date_to || ! strtotime( $date_to ) ) {
			$date_to = gmdate( $fmt );
		}
		// Ensure from <= to
		if ( strtotime( $date_from ) > strtotime( $date_to ) ) {
			[ $date_from, $date_to ] = [ $date_to, $date_from ];
		}
		return [ $date_from, $date_to . ' 23:59:59' ];
	}

	// -------------------------------------------------------------------------
	// Sales Overview
	// -------------------------------------------------------------------------

	/**
	 * Get high-level KPI totals for the date range.
	 *
	 * @param string $date_from Y-m-d
	 * @param string $date_to   Y-m-d
	 * @return object
	 */
	public function get_sales_overview( $date_from, $date_to ) {
		[ $from, $to ] = $this->sanitize_dates( $date_from, $date_to );
		$table         = $this->wpdb->prefix . 'wc_order_stats';

		$sql = $this->wpdb->prepare(
			"SELECT
				SUM(total_sales)        AS revenue,
				SUM(net_total)          AS net_revenue,
				COUNT(*)                AS orders,
				COALESCE(AVG(NULLIF(total_sales,0)),0) AS avg_order_value,
				SUM(total_refunds)      AS refunds,
				SUM(tax_total)          AS tax,
				SUM(shipping_total)     AS shipping
			FROM {$table}
			WHERE status NOT IN ({$this->excluded_in()})
			  AND date_created BETWEEN %s AND %s",
			$from,
			$to
		);

		$row = $this->wpdb->get_row( $sql );

		// Fallback: if analytics table is empty or has zero revenue, query posts/postmeta directly.
		if ( ! $row || floatval( $row->revenue ) == 0 ) {
			$row = $this->get_sales_overview_fallback( $from, $to );
		}

		return $row ?? new stdClass();
	}

	private function get_sales_overview_fallback( $from, $to ) {
		$revenue_statuses = [ 'wc-completed', 'wc-processing', 'wc-on-hold', 'completed', 'processing', 'on-hold' ];
		$in = $this->in_list( $revenue_statuses );
		$sql = $this->wpdb->prepare(
			"SELECT
				SUM(CAST(pm_total.meta_value AS DECIMAL(12,2)))      AS revenue,
				SUM(CAST(pm_total.meta_value AS DECIMAL(12,2)))      AS net_revenue,
				COUNT(DISTINCT p.ID)                                  AS orders,
				AVG(CAST(pm_total.meta_value AS DECIMAL(12,2)))      AS avg_order_value,
				SUM(CAST(pm_refund.meta_value AS DECIMAL(12,2)))     AS refunds,
				SUM(CAST(pm_tax.meta_value AS DECIMAL(12,2)))        AS tax,
				SUM(CAST(pm_ship.meta_value AS DECIMAL(12,2)))       AS shipping
			FROM {$this->wpdb->posts} p
			LEFT JOIN {$this->wpdb->postmeta} pm_total  ON p.ID = pm_total.post_id  AND pm_total.meta_key  = '_order_total'
			LEFT JOIN {$this->wpdb->postmeta} pm_refund ON p.ID = pm_refund.post_id AND pm_refund.meta_key = '_order_total_refunded'
			LEFT JOIN {$this->wpdb->postmeta} pm_tax    ON p.ID = pm_tax.post_id    AND pm_tax.meta_key    = '_order_tax'
			LEFT JOIN {$this->wpdb->postmeta} pm_ship   ON p.ID = pm_ship.post_id   AND pm_ship.meta_key   = '_order_shipping'
			WHERE p.post_type = 'shop_order'
			  AND p.post_status IN ({$in})
			  AND p.post_date BETWEEN %s AND %s",
			$from, $to
		);
		return $this->wpdb->get_row( $sql ) ?? new stdClass();
	}

	/**
	 * Get daily sales data for the chart.
	 *
	 * @param string $date_from
	 * @param string $date_to
	 * @param string $group  day|week|month
	 * @return array
	 */
	public function get_sales_over_time( $date_from, $date_to, $group = 'day' ) {
		[ $from, $to ] = $this->sanitize_dates( $date_from, $date_to );
		$table         = $this->wpdb->prefix . 'wc_order_stats';

		switch ( $group ) {
			case 'week':
				$date_expr = 'DATE(date_created - INTERVAL (WEEKDAY(date_created)) DAY)';
				break;
			case 'month':
				$date_expr = "DATE_FORMAT(date_created,'%Y-%m-01')";
				break;
			default:
				$date_expr = 'DATE(date_created)';
		}

		$sql = $this->wpdb->prepare(
			"SELECT
				{$date_expr}            AS report_date,
				SUM(total_sales)        AS revenue,
				SUM(net_total)          AS net_revenue,
				COUNT(*)                AS orders,
				SUM(total_refunds)      AS refunds,
				SUM(tax_total)          AS tax,
				SUM(shipping_total)     AS shipping,
				SUM(num_items_sold)     AS qty_sold
			FROM {$table}
			WHERE status NOT IN ({$this->excluded_in()})
			  AND date_created BETWEEN %s AND %s
			GROUP BY report_date
			ORDER BY report_date ASC",
			$from,
			$to
		);

		$rows = $this->wpdb->get_results( $sql );

		// Fallback: if analytics table returned empty or zero-revenue data, use posts/postmeta.
		$total_rev = array_sum( array_map( fn( $r ) => floatval( $r->revenue ), $rows ) );
		if ( empty( $rows ) || $total_rev == 0 ) {
			return $this->get_sales_over_time_fallback( $from, $to, $group );
		}

		return $rows;
	}

	private function get_sales_over_time_fallback( $from, $to, $group = 'day' ) {
		$revenue_statuses = [ 'wc-completed', 'wc-processing', 'wc-on-hold', 'completed', 'processing', 'on-hold' ];
		$in = $this->in_list( $revenue_statuses );
		switch ( $group ) {
			case 'week':
				$date_expr = 'DATE(p.post_date - INTERVAL WEEKDAY(p.post_date) DAY)';
				break;
			case 'month':
				$date_expr = "DATE_FORMAT(p.post_date,'%Y-%m-01')";
				break;
			default:
				$date_expr = 'DATE(p.post_date)';
		}
		$sql = $this->wpdb->prepare(
			"SELECT
				{$date_expr} AS report_date,
				SUM(CAST(pm.meta_value AS DECIMAL(12,2))) AS revenue,
				SUM(CAST(pm.meta_value AS DECIMAL(12,2))) AS net_revenue,
				COUNT(DISTINCT p.ID) AS orders,
				0 AS refunds, 0 AS tax, 0 AS shipping, 0 AS qty_sold
			FROM {$this->wpdb->posts} p
			LEFT JOIN {$this->wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_order_total'
			WHERE p.post_type = 'shop_order'
			  AND p.post_status IN ({$in})
			  AND p.post_date BETWEEN %s AND %s
			GROUP BY report_date
			ORDER BY report_date ASC",
			$from, $to
		);
		return $this->wpdb->get_results( $sql ) ?: [];
	}

	// -------------------------------------------------------------------------
	// Revenue Trends
	// -------------------------------------------------------------------------

	/**
	 * Revenue grouped by period, with optional comparison.
	 *
	 * @param string $date_from
	 * @param string $date_to
	 * @param string $group day|week|month
	 * @return array
	 */
	public function get_revenue_trends( $date_from, $date_to, $group = 'day' ) {
		return $this->get_sales_over_time( $date_from, $date_to, $group );
	}

	// -------------------------------------------------------------------------
	// Products
	// -------------------------------------------------------------------------

	/**
	 * Product revenue / qty sold.
	 *
	 * @param string $date_from
	 * @param string $date_to
	 * @param int    $limit
	 * @return array
	 */
	public function get_products_report( $date_from, $date_to, $limit = 50 ) {
		[ $from, $to ] = $this->sanitize_dates( $date_from, $date_to );
		$pl            = $this->wpdb->prefix . 'wc_order_product_lookup';
		$os            = $this->wpdb->prefix . 'wc_order_stats';
		$posts         = $this->wpdb->posts;
		$limit         = absint( $limit ) ?: 50;

		$sql = $this->wpdb->prepare(
			"SELECT
				pl.product_id,
				p.post_title                    AS product_name,
				SUM(pl.product_gross_revenue)   AS revenue,
				SUM(pl.product_net_revenue)     AS net_revenue,
				SUM(pl.product_qty)             AS qty_sold,
				COUNT(DISTINCT pl.order_id)     AS orders
			FROM {$pl} pl
			INNER JOIN {$os} os ON pl.order_id = os.order_id
			LEFT JOIN {$posts} p ON pl.product_id = p.ID
			WHERE os.status NOT IN ({$this->excluded_in()})
			  AND os.date_created BETWEEN %s AND %s
			GROUP BY pl.product_id
			ORDER BY revenue DESC
			LIMIT %d",
			$from,
			$to,
			$limit
		);

		return $this->wpdb->get_results( $sql );
	}

	// -------------------------------------------------------------------------
	// Categories
	// -------------------------------------------------------------------------

	/**
	 * Revenue by product category.
	 *
	 * @param string $date_from
	 * @param string $date_to
	 * @return array
	 */
	public function get_categories_report( $date_from, $date_to ) {
		[ $from, $to ] = $this->sanitize_dates( $date_from, $date_to );
		$pl            = $this->wpdb->prefix . 'wc_order_product_lookup';
		$os            = $this->wpdb->prefix . 'wc_order_stats';
		$tr            = $this->wpdb->term_relationships;
		$tt            = $this->wpdb->term_taxonomy;
		$t             = $this->wpdb->terms;

		$sql = $this->wpdb->prepare(
			"SELECT
				t.name                              AS category_name,
				SUM(pl.product_gross_revenue)       AS revenue,
				SUM(pl.product_qty)                 AS qty_sold,
				COUNT(DISTINCT pl.order_id)         AS orders
			FROM {$pl} pl
			INNER JOIN {$os} os   ON pl.order_id   = os.order_id
			INNER JOIN {$tr} tr   ON pl.product_id = tr.object_id
			INNER JOIN {$tt} tt   ON tr.term_taxonomy_id = tt.term_taxonomy_id
			                      AND tt.taxonomy = 'product_cat'
			INNER JOIN {$t} t     ON tt.term_id    = t.term_id
			WHERE os.status NOT IN ({$this->excluded_in()})
			  AND os.date_created BETWEEN %s AND %s
			GROUP BY t.term_id
			ORDER BY revenue DESC",
			$from,
			$to
		);

		return $this->wpdb->get_results( $sql );
	}

	// -------------------------------------------------------------------------
	// Customers
	// -------------------------------------------------------------------------

	/**
	 * Customer stats: new vs returning, top by LTV.
	 *
	 * @param string $date_from
	 * @param string $date_to
	 * @return array{summary:object, top_customers:array}
	 */
	public function get_customers_report( $date_from, $date_to ) {
		[ $from, $to ] = $this->sanitize_dates( $date_from, $date_to );
		$os            = $this->wpdb->prefix . 'wc_order_stats';
		$cl            = $this->wpdb->prefix . 'wc_customer_lookup';

		// New vs Returning summary.
		$summary_sql = $this->wpdb->prepare(
			"SELECT
				SUM(CASE WHEN os.returning_customer = 0 THEN 1 ELSE 0 END) AS new_customers,
				SUM(CASE WHEN os.returning_customer = 1 THEN 1 ELSE 0 END) AS returning_customers,
				COUNT(DISTINCT os.customer_id)                              AS unique_customers
			FROM {$os} os
			WHERE os.status NOT IN ({$this->excluded_in()})
			  AND os.date_created BETWEEN %s AND %s",
			$from,
			$to
		);
		$summary = $this->wpdb->get_row( $summary_sql );

		// Top customers by LTV within period.
		$top_sql = $this->wpdb->prepare(
			"SELECT
				cl.customer_id,
				cl.first_name,
				cl.last_name,
				cl.email,
				cl.country,
				SUM(os.total_sales)     AS ltv,
				COUNT(os.order_id)      AS order_count,
				AVG(os.total_sales)     AS avg_order_value
			FROM {$os} os
			INNER JOIN {$cl} cl ON os.customer_id = cl.customer_id
			WHERE os.status NOT IN ({$this->excluded_in()})
			  AND os.date_created BETWEEN %s AND %s
			GROUP BY cl.customer_id
			ORDER BY ltv DESC
			LIMIT 50",
			$from,
			$to
		);
		$top_customers = $this->wpdb->get_results( $top_sql );

		// Geography.
		$geo_sql = $this->wpdb->prepare(
			"SELECT
				cl.country,
				COUNT(DISTINCT cl.customer_id) AS customers,
				SUM(os.total_sales)            AS revenue
			FROM {$os} os
			INNER JOIN {$cl} cl ON os.customer_id = cl.customer_id
			WHERE os.status NOT IN ({$this->excluded_in()})
			  AND os.date_created BETWEEN %s AND %s
			  AND cl.country IS NOT NULL AND cl.country != ''
			GROUP BY cl.country
			ORDER BY revenue DESC
			LIMIT 30",
			$from,
			$to
		);
		$geography = $this->wpdb->get_results( $geo_sql );

		return [
			'summary'       => $summary,
			'top_customers' => $top_customers,
			'geography'     => $geography,
		];
	}

	// -------------------------------------------------------------------------
	// Orders
	// -------------------------------------------------------------------------

	/**
	 * Orders by status and over time.
	 *
	 * @param string $date_from
	 * @param string $date_to
	 * @return array
	 */
	public function get_orders_report( $date_from, $date_to ) {
		[ $from, $to ] = $this->sanitize_dates( $date_from, $date_to );
		$os            = $this->wpdb->prefix . 'wc_order_stats';

		$by_status_sql = $this->wpdb->prepare(
			"SELECT
				status,
				COUNT(*) AS order_count,
				SUM(total_sales) AS revenue
			FROM {$os}
			WHERE date_created BETWEEN %s AND %s
			GROUP BY status
			ORDER BY order_count DESC",
			$from,
			$to
		);
		$by_status = $this->wpdb->get_results( $by_status_sql );

		$over_time_sql = $this->wpdb->prepare(
			"SELECT
				DATE(date_created) AS report_date,
				COUNT(*) AS order_count,
				SUM(total_sales) AS revenue
			FROM {$os}
			WHERE status NOT IN ({$this->excluded_in()})
			  AND date_created BETWEEN %s AND %s
			GROUP BY report_date
			ORDER BY report_date ASC",
			$from,
			$to
		);
		$over_time = $this->wpdb->get_results( $over_time_sql );

		return [
			'by_status' => $by_status,
			'over_time' => $over_time,
		];
	}

	// -------------------------------------------------------------------------
	// Coupons
	// -------------------------------------------------------------------------

	/**
	 * Coupon usage report.
	 *
	 * @param string $date_from
	 * @param string $date_to
	 * @return array
	 */
	public function get_coupons_report( $date_from, $date_to, $search = '' ) {
		[ $from, $to ] = $this->sanitize_dates( $date_from, $date_to );
		$cl = $this->wpdb->prefix . 'wc_order_coupon_lookup';
		$os = $this->wpdb->prefix . 'wc_order_stats';

		// Try analytics tables first
		if ( $this->analytics_tables_exist() ) {
			$search_clause = '';
			$args = [ $from, $to ];
			if ( $search ) {
				$like = '%' . $this->wpdb->esc_like( strtolower( $search ) ) . '%';
				$search_clause = ' AND LOWER(cl.coupon_code) LIKE %s';
				$args[] = $like;
			}

			$sql = $this->wpdb->prepare(
				"SELECT
					cl.coupon_id,
					cl.coupon_code,
					COUNT(DISTINCT cl.order_id)     AS usage_count,
					SUM(cl.discount_amount)          AS discount_amount,
					SUM(os.total_sales)              AS revenue
				FROM {$cl} cl
				INNER JOIN {$os} os ON cl.order_id = os.order_id
				WHERE os.status NOT IN ({$this->excluded_in()})
				  AND os.date_created BETWEEN %s AND %s
				  {$search_clause}
				GROUP BY cl.coupon_id, cl.coupon_code
				ORDER BY discount_amount DESC
				LIMIT 200",
				...$args
			);

			$rows = $this->wpdb->get_results( $sql );
			if ( ! empty( $rows ) ) {
				return $rows;
			}
		}

		// Fallback: order items query
		$revenue_statuses = [ 'wc-completed', 'wc-processing', 'wc-on-hold', 'completed', 'processing', 'on-hold' ];
		$status_in = $this->in_list( $revenue_statuses );

		$search_sql = $search ? $this->wpdb->prepare( ' AND LOWER(oi.order_item_name) LIKE LOWER(%s)', '%' . $this->wpdb->esc_like( $search ) . '%' ) : '';

		$sql = $this->wpdb->prepare(
			"SELECT
				oi.order_item_name                               AS coupon_code,
				COUNT(DISTINCT oi.order_id)                      AS usage_count,
				SUM(CAST(oim.meta_value AS DECIMAL(12,4)))       AS discount_amount,
				SUM(CAST(pm_total.meta_value AS DECIMAL(12,2)))  AS revenue
			FROM {$this->wpdb->prefix}woocommerce_order_items oi
			INNER JOIN {$this->wpdb->prefix}woocommerce_order_itemmeta oim
				ON oi.order_item_id = oim.order_item_id AND oim.meta_key = 'discount_amount'
			INNER JOIN {$this->wpdb->posts} p ON oi.order_id = p.ID
			LEFT JOIN {$this->wpdb->postmeta} pm_total
				ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
			WHERE oi.order_item_type = 'coupon'
			  AND p.post_status IN ({$status_in})
			  AND p.post_date BETWEEN %s AND %s
			  {$search_sql}
			GROUP BY oi.order_item_name
			ORDER BY discount_amount DESC
			LIMIT 200",
			$from, $to
		);

		return $this->wpdb->get_results( $sql ) ?: [];
	}

	// -------------------------------------------------------------------------
	// Tax
	// -------------------------------------------------------------------------

	/**
	 * Tax collected report.
	 *
	 * @param string $date_from
	 * @param string $date_to
	 * @return array
	 */
	public function get_tax_report( $date_from, $date_to ) {
		[ $from, $to ] = $this->sanitize_dates( $date_from, $date_to );
		$os            = $this->wpdb->prefix . 'wc_order_stats';
		$otl           = $this->wpdb->prefix . 'woocommerce_order_items';
		$oim           = $this->wpdb->prefix . 'woocommerce_order_itemmeta';
		$orders        = $this->wpdb->posts;

		// Simple aggregate from order_stats (by rate name via order items).
		// Fallback: just total tax per day.
		$sql = $this->wpdb->prepare(
			"SELECT
				DATE(os.date_created)   AS report_date,
				SUM(os.tax_total)       AS tax_total,
				COUNT(*)                AS orders
			FROM {$os} os
			WHERE os.status NOT IN ({$this->excluded_in()})
			  AND os.date_created BETWEEN %s AND %s
			GROUP BY report_date
			ORDER BY report_date ASC",
			$from,
			$to
		);
		$by_day = $this->wpdb->get_results( $sql );

		// Tax by rate name from order items.
		$rate_sql = $this->wpdb->prepare(
			"SELECT
				im2.meta_value                  AS rate_label,
				SUM(CAST(im.meta_value AS DECIMAL(10,4))) AS tax_amount,
				COUNT(DISTINCT oi.order_id)     AS orders
			FROM {$otl} oi
			INNER JOIN {$oim} im  ON oi.order_item_id = im.order_item_id AND im.meta_key = 'tax_amount'
			INNER JOIN {$oim} im2 ON oi.order_item_id = im2.order_item_id AND im2.meta_key = 'label'
			INNER JOIN {$os} os   ON oi.order_id = os.order_id
			WHERE oi.order_item_type = 'tax'
			  AND os.status NOT IN ({$this->excluded_in()})
			  AND os.date_created BETWEEN %s AND %s
			GROUP BY im2.meta_value
			ORDER BY tax_amount DESC",
			$from,
			$to
		);
		$by_rate = $this->wpdb->get_results( $rate_sql );

		return [
			'by_day'  => $by_day,
			'by_rate' => $by_rate,
		];
	}

	// -------------------------------------------------------------------------
	// Shipping
	// -------------------------------------------------------------------------

	/**
	 * Shipping revenue by method.
	 *
	 * @param string $date_from
	 * @param string $date_to
	 * @return array
	 */
	public function get_shipping_report( $date_from, $date_to ) {
		[ $from, $to ] = $this->sanitize_dates( $date_from, $date_to );
		$otl           = $this->wpdb->prefix . 'woocommerce_order_items';
		$oim           = $this->wpdb->prefix . 'woocommerce_order_itemmeta';
		$os            = $this->wpdb->prefix . 'wc_order_stats';

		$by_method_sql = $this->wpdb->prepare(
			"SELECT
				oi.order_item_name                          AS shipping_method,
				SUM(CAST(im.meta_value AS DECIMAL(10,4)))   AS shipping_total,
				COUNT(DISTINCT oi.order_id)                 AS orders
			FROM {$otl} oi
			INNER JOIN {$oim} im ON oi.order_item_id = im.order_item_id AND im.meta_key = 'cost'
			INNER JOIN {$os} os  ON oi.order_id = os.order_id
			WHERE oi.order_item_type = 'shipping'
			  AND os.status NOT IN ({$this->excluded_in()})
			  AND os.date_created BETWEEN %s AND %s
			GROUP BY oi.order_item_name
			ORDER BY shipping_total DESC",
			$from,
			$to
		);
		$by_method = $this->wpdb->get_results( $by_method_sql );

		$over_time_sql = $this->wpdb->prepare(
			"SELECT
				DATE(os.date_created)   AS report_date,
				SUM(os.shipping_total)  AS shipping_total
			FROM {$os} os
			WHERE os.status NOT IN ({$this->excluded_in()})
			  AND os.date_created BETWEEN %s AND %s
			GROUP BY report_date
			ORDER BY report_date ASC",
			$from,
			$to
		);
		$over_time = $this->wpdb->get_results( $over_time_sql );

		return [
			'by_method' => $by_method,
			'over_time' => $over_time,
		];
	}

	// -------------------------------------------------------------------------
	// Profit
	// -------------------------------------------------------------------------

	public function get_profit_report( $date_from, $date_to, $group = 'day' ) {
		[ $from, $to ] = $this->sanitize_dates( $date_from, $date_to );
		$table         = $this->wpdb->prefix . 'wc_order_stats';

		switch ( $group ) {
			case 'week':
				$date_expr = 'DATE(date_created - INTERVAL (WEEKDAY(date_created)) DAY)';
				break;
			case 'month':
				$date_expr = "DATE_FORMAT(date_created,'%Y-%m-01')";
				break;
			case 'year':
				$date_expr = "DATE_FORMAT(date_created,'%Y-01-01')";
				break;
			default:
				$date_expr = 'DATE(date_created)';
		}

		$sql = $this->wpdb->prepare(
			"SELECT
				{$date_expr}             AS report_date,
				SUM(total_sales)         AS gross_sales,
				SUM(shipping_total)      AS shipping_total,
				SUM(tax_total)           AS tax_total,
				SUM(net_total)           AS net_total,
				COUNT(*)                 AS orders
			FROM {$table}
			WHERE status NOT IN ({$this->excluded_in()})
			  AND date_created BETWEEN %s AND %s
			GROUP BY report_date
			ORDER BY report_date ASC",
			$from,
			$to
		);
		$rows = $this->wpdb->get_results( $sql );

		$product_costs = get_option( 'omni_product_costs', [] );

		// Get product costs per order for the period.
		$pl  = $this->wpdb->prefix . 'wc_order_product_lookup';
		$os2 = $this->wpdb->prefix . 'wc_order_stats';
		$cost_sql = $this->wpdb->prepare(
			"SELECT
				{$date_expr}              AS report_date,
				pl.product_id,
				SUM(pl.product_qty)       AS qty
			FROM {$pl} pl
			INNER JOIN {$os2} os ON pl.order_id = os.order_id
			WHERE os.status NOT IN ({$this->excluded_in()})
			  AND os.date_created BETWEEN %s AND %s
			GROUP BY report_date, pl.product_id",
			$from,
			$to
		);
		$cost_rows = $this->wpdb->get_results( $cost_sql );

		// Build cost per date.
		$costs_by_date = [];
		foreach ( $cost_rows as $cr ) {
			$cost_per_unit = isset( $product_costs[ $cr->product_id ] ) ? floatval( $product_costs[ $cr->product_id ] ) : 0;
			$date          = $cr->report_date;
			if ( ! isset( $costs_by_date[ $date ] ) ) {
				$costs_by_date[ $date ] = 0;
			}
			$costs_by_date[ $date ] += $cost_per_unit * floatval( $cr->qty );
		}

		// Enrich rows.
		$totals = [
			'gross_sales'    => 0,
			'product_cost'   => 0,
			'shipping_total' => 0,
			'tax_total'      => 0,
			'gross_profit'   => 0,
			'orders'         => 0,
		];
		foreach ( $rows as &$row ) {
			$product_cost          = $costs_by_date[ $row->report_date ] ?? 0;
			$row->product_cost     = round( $product_cost, 2 );
			$row->gross_cost       = round( $product_cost + floatval( $row->shipping_total ) + floatval( $row->tax_total ), 2 );
			$row->gross_profit     = round( floatval( $row->gross_sales ) - $row->gross_cost, 2 );
			$row->profit_margin    = floatval( $row->gross_sales ) > 0
				? round( ( $row->gross_profit / floatval( $row->gross_sales ) ) * 100, 2 )
				: 0;
			$totals['gross_sales']    += floatval( $row->gross_sales );
			$totals['product_cost']   += $product_cost;
			$totals['shipping_total'] += floatval( $row->shipping_total );
			$totals['tax_total']      += floatval( $row->tax_total );
			$totals['gross_profit']   += $row->gross_profit;
			$totals['orders']         += intval( $row->orders );
		}
		unset( $row );

		$totals['gross_cost']    = round( $totals['product_cost'] + $totals['shipping_total'] + $totals['tax_total'], 2 );
		$totals['gross_profit']  = round( $totals['gross_profit'], 2 );
		$totals['profit_margin'] = $totals['gross_sales'] > 0
			? round( ( $totals['gross_profit'] / $totals['gross_sales'] ) * 100, 2 )
			: 0;
		$totals['avg_profit']    = $totals['orders'] > 0
			? round( $totals['gross_profit'] / $totals['orders'], 2 )
			: 0;

		return [
			'rows'   => $rows,
			'totals' => $totals,
		];
	}

	// -------------------------------------------------------------------------
	// Refunds
	// -------------------------------------------------------------------------

	public function get_refunds_report( $date_from, $date_to ) {
		[ $from, $to ] = $this->sanitize_dates( $date_from, $date_to );
		$os            = $this->wpdb->prefix . 'wc_order_stats';

		// Summary.
		$summary_sql = $this->wpdb->prepare(
			"SELECT
				SUM(total_refunds)          AS total_refunded,
				SUM(CASE WHEN total_refunds > 0 THEN 1 ELSE 0 END) AS refund_count,
				COUNT(*)                    AS total_orders,
				COALESCE(AVG(CASE WHEN total_refunds > 0 THEN total_refunds END), 0) AS avg_refund
			FROM {$os}
			WHERE status NOT IN ({$this->excluded_in()})
			  AND date_created BETWEEN %s AND %s",
			$from,
			$to
		);
		$summary = $this->wpdb->get_row( $summary_sql );
		if ( $summary ) {
			$summary->refund_rate = $summary->total_orders > 0
				? round( ( $summary->refund_count / $summary->total_orders ) * 100, 2 )
				: 0;
		}

		// Over time.
		$over_time_sql = $this->wpdb->prepare(
			"SELECT
				DATE(date_created)          AS report_date,
				SUM(total_refunds)          AS refunded,
				SUM(CASE WHEN total_refunds > 0 THEN 1 ELSE 0 END) AS refund_count
			FROM {$os}
			WHERE status NOT IN ({$this->excluded_in()})
			  AND date_created BETWEEN %s AND %s
			GROUP BY report_date
			ORDER BY report_date ASC",
			$from,
			$to
		);
		$over_time = $this->wpdb->get_results( $over_time_sql );

		// Top refunded products.
		$pl = $this->wpdb->prefix . 'wc_order_product_lookup';
		$posts = $this->wpdb->posts;
		$top_sql = $this->wpdb->prepare(
			"SELECT
				pl.product_id,
				p.post_title                    AS product_name,
				COUNT(DISTINCT pl.order_id)     AS refund_count,
				SUM(pl.product_gross_revenue)   AS refund_amount
			FROM {$pl} pl
			INNER JOIN {$os} os ON pl.order_id = os.order_id
			LEFT JOIN {$posts} p ON pl.product_id = p.ID
			WHERE os.status NOT IN ({$this->excluded_in()})
			  AND os.date_created BETWEEN %s AND %s
			  AND os.total_refunds > 0
			GROUP BY pl.product_id
			ORDER BY refund_amount DESC
			LIMIT 20",
			$from,
			$to
		);
		$top_products = $this->wpdb->get_results( $top_sql );

		return [
			'summary'      => $summary,
			'over_time'    => $over_time,
			'top_products' => $top_products,
		];
	}

	// -------------------------------------------------------------------------
	// Dashboard KPIs
	// -------------------------------------------------------------------------

	/**
	 * Dashboard summary: sales KPIs + order counts.
	 *
	 * @param string $date_from
	 * @param string $date_to
	 * @return array
	 */
	public function get_dashboard_summary( $date_from, $date_to ) {
		$kpis      = $this->get_sales_overview( $date_from, $date_to );
		$over_time = $this->get_sales_over_time( $date_from, $date_to, 'day' );
		return [
			'kpis'      => $kpis,
			'over_time' => $over_time,
		];
	}

	// -------------------------------------------------------------------------
	// Dashboard Home (full dashboard with comparison)
	// -------------------------------------------------------------------------

	/**
	 * Full dashboard home data: summary KPIs, comparison, charts, top lists.
	 *
	 * @param string $date_from
	 * @param string $date_to
	 * @return array
	 */
	public function get_dashboard_home( $date_from, $date_to ) {
		[ $from, $to ] = $this->sanitize_dates( $date_from, $date_to );

		// Calculate comparison period (same length, immediately prior).
		$from_ts = strtotime( $from );
		$to_ts   = strtotime( $to );
		$diff    = $to_ts - $from_ts;
		$comp_to   = gmdate( 'Y-m-d 23:59:59', $from_ts - 1 );
		$comp_from = gmdate( 'Y-m-d', $from_ts - $diff - 86400 );

		$summary      = $this->get_sales_overview( $from, $to );
		$comp_summary = $this->get_sales_overview( $comp_from, $comp_to );

		// New customers for current and prior periods.
		$os = $this->wpdb->prefix . 'wc_order_stats';
		$new_cust_sql = $this->wpdb->prepare(
			"SELECT SUM(CASE WHEN returning_customer = 0 THEN 1 ELSE 0 END) AS new_customers
			FROM {$os}
			WHERE status NOT IN ({$this->excluded_in()})
			  AND date_created BETWEEN %s AND %s",
			$from, $to
		);
		$new_cust_row = $this->wpdb->get_row( $new_cust_sql );
		if ( $summary ) {
			$summary->new_customers = $new_cust_row ? (int) $new_cust_row->new_customers : 0;
		}

		$comp_cust_sql = $this->wpdb->prepare(
			"SELECT SUM(CASE WHEN returning_customer = 0 THEN 1 ELSE 0 END) AS new_customers
			FROM {$os}
			WHERE status NOT IN ({$this->excluded_in()})
			  AND date_created BETWEEN %s AND %s",
			$comp_from, $comp_to
		);
		$comp_cust_row = $this->wpdb->get_row( $comp_cust_sql );
		if ( $comp_summary ) {
			$comp_summary->new_customers = $comp_cust_row ? (int) $comp_cust_row->new_customers : 0;
		}

		// Refund rate for current period.
		$refund_sql = $this->wpdb->prepare(
			"SELECT COUNT(*) AS total, SUM(CASE WHEN total_refunds > 0 THEN 1 ELSE 0 END) AS refunded
			FROM {$os}
			WHERE status NOT IN ({$this->excluded_in()})
			  AND date_created BETWEEN %s AND %s",
			$from, $to
		);
		$refund_row = $this->wpdb->get_row( $refund_sql );
		if ( $summary ) {
			$total_orders           = $refund_row ? (int) $refund_row->total : 0;
			$summary->refund_rate   = $total_orders > 0
				? round( ( (int) $refund_row->refunded / $total_orders ) * 100, 2 )
				: 0;
		}

		$comp_refund_sql = $this->wpdb->prepare(
			"SELECT COUNT(*) AS total, SUM(CASE WHEN total_refunds > 0 THEN 1 ELSE 0 END) AS refunded
			FROM {$os}
			WHERE status NOT IN ({$this->excluded_in()})
			  AND date_created BETWEEN %s AND %s",
			$comp_from, $comp_to
		);
		$comp_refund_row = $this->wpdb->get_row( $comp_refund_sql );
		if ( $comp_summary ) {
			$comp_total                 = $comp_refund_row ? (int) $comp_refund_row->total : 0;
			$comp_summary->refund_rate  = $comp_total > 0
				? round( ( (int) $comp_refund_row->refunded / $comp_total ) * 100, 2 )
				: 0;
		}

		// Items sold for current period.
		$items_sql = $this->wpdb->prepare(
			"SELECT SUM(num_items_sold) AS items_sold FROM {$this->wpdb->prefix}wc_order_stats
			 WHERE status NOT IN ({$this->excluded_in()}) AND date_created BETWEEN %s AND %s",
			$from, $to
		);
		$items_row = $this->wpdb->get_row( $items_sql );
		if ( $summary ) {
			$summary->items_sold = $items_row ? (int) $items_row->items_sold : 0;
		}

		// Items sold for comparison period.
		$comp_items_sql = $this->wpdb->prepare(
			"SELECT SUM(num_items_sold) AS items_sold FROM {$this->wpdb->prefix}wc_order_stats
			 WHERE status NOT IN ({$this->excluded_in()}) AND date_created BETWEEN %s AND %s",
			$comp_from, $comp_to
		);
		$comp_items_row = $this->wpdb->get_row( $comp_items_sql );
		if ( $comp_summary ) {
			$comp_summary->items_sold = $comp_items_row ? (int) $comp_items_row->items_sold : 0;
		}

		// Weekly aggregates (last 7 days from current period end).
		$week_from = gmdate( 'Y-m-d', strtotime( $to ) - 6 * 86400 );
		$week_sql  = $this->wpdb->prepare(
			"SELECT SUM(net_total) AS weekly_net, SUM(total_sales) AS weekly_gross,
			        COUNT(*) AS weekly_orders, SUM(num_items_sold) AS weekly_items
			 FROM {$this->wpdb->prefix}wc_order_stats
			 WHERE status NOT IN ({$this->excluded_in()}) AND date_created BETWEEN %s AND %s",
			$week_from . ' 00:00:00', $to
		);
		$week_row = $this->wpdb->get_row( $week_sql );

		$prior_week_to   = gmdate( 'Y-m-d', strtotime( $week_from ) - 86400 );
		$prior_week_from = gmdate( 'Y-m-d', strtotime( $prior_week_to ) - 6 * 86400 );
		$prior_week_sql  = $this->wpdb->prepare(
			"SELECT SUM(net_total) AS weekly_net, SUM(total_sales) AS weekly_gross,
			        COUNT(*) AS weekly_orders, SUM(num_items_sold) AS weekly_items
			 FROM {$this->wpdb->prefix}wc_order_stats
			 WHERE status NOT IN ({$this->excluded_in()}) AND date_created BETWEEN %s AND %s",
			$prior_week_from . ' 00:00:00', $prior_week_to . ' 23:59:59'
		);
		$prior_week_row = $this->wpdb->get_row( $prior_week_sql );

		$weekly_summary = [
			'weekly_net'    => $week_row ? round( (float) $week_row->weekly_net, 2 )    : 0,
			'weekly_gross'  => $week_row ? round( (float) $week_row->weekly_gross, 2 )  : 0,
			'weekly_orders' => $week_row ? (int) $week_row->weekly_orders               : 0,
			'weekly_items'  => $week_row ? (int) $week_row->weekly_items                : 0,
		];
		$comp_weekly_summary = [
			'weekly_net'    => $prior_week_row ? round( (float) $prior_week_row->weekly_net, 2 )    : 0,
			'weekly_gross'  => $prior_week_row ? round( (float) $prior_week_row->weekly_gross, 2 )  : 0,
			'weekly_orders' => $prior_week_row ? (int) $prior_week_row->weekly_orders               : 0,
			'weekly_items'  => $prior_week_row ? (int) $prior_week_row->weekly_items                : 0,
		];

		// Gross profit for summary (revenue minus product costs).
		$product_costs = get_option( 'omni_product_costs', [] );
		$pl = $this->wpdb->prefix . 'wc_order_product_lookup';
		$cost_sql = $this->wpdb->prepare(
			"SELECT pl.product_id, SUM(pl.product_qty) AS qty
			FROM {$pl} pl
			INNER JOIN {$os} os ON pl.order_id = os.order_id
			WHERE os.status NOT IN ({$this->excluded_in()})
			  AND os.date_created BETWEEN %s AND %s
			GROUP BY pl.product_id",
			$from, $to
		);
		$cost_rows  = $this->wpdb->get_results( $cost_sql );
		$total_cost = 0;
		foreach ( $cost_rows as $cr ) {
			$cpu         = isset( $product_costs[ $cr->product_id ] ) ? floatval( $product_costs[ $cr->product_id ] ) : 0;
			$total_cost += $cpu * floatval( $cr->qty );
		}
		if ( $summary ) {
			$summary->gross_profit = round( floatval( $summary->revenue ?? 0 ) - $total_cost, 2 );
		}

		// Comparison gross profit.
		$comp_cost_sql = $this->wpdb->prepare(
			"SELECT pl.product_id, SUM(pl.product_qty) AS qty
			FROM {$pl} pl
			INNER JOIN {$os} os ON pl.order_id = os.order_id
			WHERE os.status NOT IN ({$this->excluded_in()})
			  AND os.date_created BETWEEN %s AND %s
			GROUP BY pl.product_id",
			$comp_from, $comp_to
		);
		$comp_cost_rows  = $this->wpdb->get_results( $comp_cost_sql );
		$comp_total_cost = 0;
		foreach ( $comp_cost_rows as $cr ) {
			$cpu              = isset( $product_costs[ $cr->product_id ] ) ? floatval( $product_costs[ $cr->product_id ] ) : 0;
			$comp_total_cost += $cpu * floatval( $cr->qty );
		}
		if ( $comp_summary ) {
			$comp_summary->gross_profit = round( floatval( $comp_summary->revenue ?? 0 ) - $comp_total_cost, 2 );
		}

		// Revenue chart (daily, current + prior).
		$current_rows = $this->get_sales_over_time( $from, $to, 'day' );
		$prior_rows   = $this->get_sales_over_time( $comp_from, $comp_to, 'day' );

		$current_labels  = array_column( $current_rows, 'report_date' );
		$current_revenue = array_map( fn( $r ) => (float) $r->revenue, $current_rows );
		$current_profit  = array_map( fn( $r ) => round( (float) $r->net_revenue, 2 ), $current_rows );
		$prior_revenue   = array_map( fn( $r ) => (float) $r->revenue, $prior_rows );

		$revenue_chart = [
			'labels'   => $current_labels,
			'datasets' => [
				[
					'label' => 'Revenue',
					'data'  => $current_revenue,
					'type'  => 'bar',
				],
				[
					'label' => 'Net Profit',
					'data'  => $current_profit,
					'type'  => 'line',
				],
				[
					'label'  => 'Prior Revenue',
					'data'   => $prior_revenue,
					'type'   => 'line',
					'dashed' => true,
				],
			],
		];

		// Weekly (last 7 days).
		$week_from = gmdate( 'Y-m-d', strtotime( '-6 days' ) );
		$week_to   = gmdate( 'Y-m-d' );
		$weekly_rows = $this->get_sales_over_time( $week_from, $week_to, 'day' );
		$weekly = array_map( fn( $r ) => [
			'date'    => $r->report_date,
			'revenue' => (float) $r->revenue,
			'orders'  => (int) $r->orders,
		], $weekly_rows );

		// Top products.
		$top_products = $this->get_products_report( $from, $to, 10 );

		// Top customers.
		$cust_data     = $this->get_customers_report( $from, $to );
		$top_customers = array_slice( $cust_data['top_customers'], 0, 10 );

		// Order status breakdown.
		$orders_data = $this->get_orders_report( $from, $to );
		$order_status_breakdown = $orders_data['by_status'];

		// Top category.
		$cats      = $this->get_categories_report( $from, $to );
		$top_cat   = ! empty( $cats ) ? $cats[0]->category_name : '';
		$top_prod  = ! empty( $top_products ) ? $top_products[0]->product_name : '';

		return [
			'summary'                => $summary,
			'comp_summary'           => $comp_summary,
			'revenue_chart'          => $revenue_chart,
			'weekly'                 => $weekly,
			'weekly_summary'         => $weekly_summary,
			'comp_weekly_summary'    => $comp_weekly_summary,
			'top_products'           => $top_products,
			'top_customers'          => $top_customers,
			'order_status_breakdown' => $order_status_breakdown,
			'quick_stats'            => [
				'top_category' => $top_cat,
				'top_product'  => $top_prod,
			],
			'analytics_tables_exist' => $this->analytics_tables_exist(),
		];
	}

	// -------------------------------------------------------------------------
	// Stock Tracker
	// -------------------------------------------------------------------------

	/**
	 * Get stock report data for all published products and variations.
	 *
	 * @return array
	 */
	public static function get_stock_report() {
		global $wpdb;
		$products = $wpdb->get_results( "
			SELECT p.ID, p.post_title as name,
			       MAX(CASE WHEN pm.meta_key='_sku' THEN pm.meta_value END) as sku,
			       MAX(CASE WHEN pm.meta_key='_stock' THEN CAST(pm.meta_value AS DECIMAL(10,2)) END) as stock_qty,
			       MAX(CASE WHEN pm.meta_key='_stock_status' THEN pm.meta_value END) as stock_status,
			       MAX(CASE WHEN pm.meta_key='_manage_stock' THEN pm.meta_value END) as manage_stock,
			       MAX(CASE WHEN pm.meta_key='_regular_price' THEN CAST(pm.meta_value AS DECIMAL(10,2)) END) as price,
			       MAX(CASE WHEN pm.meta_key='_sale_price' THEN CAST(pm.meta_value AS DECIMAL(10,2)) END) as sale_price
			FROM {$wpdb->posts} p
			JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE p.post_type IN ('product','product_variation')
			  AND p.post_status = 'publish'
			  AND pm.meta_key IN ('_sku','_stock','_stock_status','_manage_stock','_regular_price','_sale_price')
			GROUP BY p.ID
			ORDER BY p.post_title
		" );
		$reorder_points = get_option( 'omni_reorder_points', [] );
		foreach ( $products as &$p ) {
			$p->reorder_point  = isset( $reorder_points[ $p->ID ] ) ? (int) $reorder_points[ $p->ID ] : 5;
			$p->needs_reorder  = ( $p->stock_status === 'outofstock' || (float) $p->stock_qty <= $p->reorder_point );
			$effective_price   = ! empty( $p->sale_price ) ? $p->sale_price : $p->price;
			$p->stock_value    = round( (float) $p->stock_qty * (float) $effective_price, 2 );
		}
		unset( $p );
		return $products;
	}

	// -------------------------------------------------------------------------
	// Payment Methods
	// -------------------------------------------------------------------------

	public function get_payment_methods_report( $date_from, $date_to ) {
		[ $from, $to ] = $this->sanitize_dates( $date_from, $date_to );

		$revenue_statuses = [ 'wc-completed', 'wc-processing', 'wc-on-hold', 'completed', 'processing', 'on-hold' ];
		$in = $this->in_list( $revenue_statuses );

		$sql = $this->wpdb->prepare(
			"SELECT
				COALESCE(pm.meta_value, 'Unknown')   AS payment_method,
				COALESCE(pm2.meta_value, pm.meta_value, 'Unknown') AS payment_method_title,
				COUNT(DISTINCT p.ID)                 AS order_count,
				SUM(CAST(pt.meta_value AS DECIMAL(12,2))) AS total_sales
			FROM {$this->wpdb->posts} p
			LEFT JOIN {$this->wpdb->postmeta} pm  ON p.ID = pm.post_id  AND pm.meta_key  = '_payment_method'
			LEFT JOIN {$this->wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_payment_method_title'
			LEFT JOIN {$this->wpdb->postmeta} pt  ON p.ID = pt.post_id  AND pt.meta_key  = '_order_total'
			WHERE p.post_type = 'shop_order'
			  AND p.post_status IN ({$in})
			  AND p.post_date BETWEEN %s AND %s
			GROUP BY pm.meta_value
			ORDER BY total_sales DESC",
			$from, $to
		);

		return $this->wpdb->get_results( $sql ) ?: [];
	}
}
