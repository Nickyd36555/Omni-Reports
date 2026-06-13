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

	/** Non-revenue statuses */
	private $excluded_statuses = [ 'wc-pending', 'wc-cancelled', 'wc-failed', 'wc-checkout-draft' ];

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
				COALESCE(AVG(total_sales),0) AS avg_order_value,
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
		return $row ?? new stdClass();
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

		return $this->wpdb->get_results( $sql );
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
	public function get_coupons_report( $date_from, $date_to ) {
		[ $from, $to ] = $this->sanitize_dates( $date_from, $date_to );
		$cl            = $this->wpdb->prefix . 'wc_order_coupon_lookup';
		$os            = $this->wpdb->prefix . 'wc_order_stats';

		$sql = $this->wpdb->prepare(
			"SELECT
				cl.coupon_id,
				cl.coupon_code,
				COUNT(cl.order_id)          AS usage_count,
				SUM(cl.discount_amount)     AS discount_amount,
				SUM(os.total_sales)         AS revenue
			FROM {$cl} cl
			INNER JOIN {$os} os ON cl.order_id = os.order_id
			WHERE os.status NOT IN ({$this->excluded_in()})
			  AND os.date_created BETWEEN %s AND %s
			GROUP BY cl.coupon_id
			ORDER BY usage_count DESC",
			$from,
			$to
		);

		return $this->wpdb->get_results( $sql );
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
}
