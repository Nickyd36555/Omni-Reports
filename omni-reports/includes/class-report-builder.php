<?php
/**
 * Custom Report Builder — dynamic query engine.
 *
 * @package OmniReports
 */

defined( 'ABSPATH' ) || exit;

class Omni_Reports_Report_Builder {

	/** @var wpdb */
	private $wpdb;

	private $excluded_statuses = [ 'wc-pending', 'wc-cancelled', 'wc-failed', 'wc-checkout-draft' ];

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Available metrics definitions.
	 */
	public static function available_metrics() {
		return [
			'revenue'          => __( 'Gross Revenue', 'omni-reports' ),
			'net_revenue'      => __( 'Net Revenue', 'omni-reports' ),
			'orders'           => __( 'Orders', 'omni-reports' ),
			'avg_order_value'  => __( 'Avg Order Value', 'omni-reports' ),
			'qty_sold'         => __( 'Qty Sold', 'omni-reports' ),
			'customers'        => __( 'Customers', 'omni-reports' ),
			'refunds'          => __( 'Refunds', 'omni-reports' ),
			'tax'              => __( 'Tax', 'omni-reports' ),
			'shipping'         => __( 'Shipping', 'omni-reports' ),
			'discount_amount'  => __( 'Discount Amount', 'omni-reports' ),
		];
	}

	/**
	 * Available dimensions definitions.
	 */
	public static function available_dimensions() {
		return [
			'date_day'        => __( 'Date (Day)', 'omni-reports' ),
			'date_week'       => __( 'Date (Week)', 'omni-reports' ),
			'date_month'      => __( 'Date (Month)', 'omni-reports' ),
			'product'         => __( 'Product', 'omni-reports' ),
			'category'        => __( 'Category', 'omni-reports' ),
			'order_status'    => __( 'Order Status', 'omni-reports' ),
			'country'         => __( 'Country', 'omni-reports' ),
			'payment_method'  => __( 'Payment Method', 'omni-reports' ),
		];
	}

	/**
	 * Run a custom report.
	 *
	 * @param array $config {
	 *   metrics:   string[]  e.g. ['revenue','orders']
	 *   dimension: string    e.g. 'date_day'
	 *   date_from: string
	 *   date_to:   string
	 *   filters:   array     e.g. ['order_status'=>['wc-completed']]
	 *   limit:     int
	 * }
	 * @return array
	 */
	public function run( array $config ) {
		$metrics   = isset( $config['metrics'] ) ? (array) $config['metrics'] : [ 'revenue', 'orders' ];
		$dimension = isset( $config['dimension'] ) ? sanitize_key( $config['dimension'] ) : 'date_day';
		$date_from = isset( $config['date_from'] ) ? sanitize_text_field( $config['date_from'] ) : gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$date_to   = isset( $config['date_to'] ) ? sanitize_text_field( $config['date_to'] ) : gmdate( 'Y-m-d' );
		$filters   = isset( $config['filters'] ) ? (array) $config['filters'] : [];
		$limit     = isset( $config['limit'] ) ? absint( $config['limit'] ) : 100;

		// Validate metrics.
		$allowed_metrics = array_keys( self::available_metrics() );
		$metrics = array_filter( $metrics, fn( $m ) => in_array( $m, $allowed_metrics, true ) );
		if ( empty( $metrics ) ) {
			$metrics = [ 'revenue', 'orders' ];
		}

		// Validate dimension.
		if ( ! array_key_exists( $dimension, self::available_dimensions() ) ) {
			$dimension = 'date_day';
		}

		$os  = $this->wpdb->prefix . 'wc_order_stats';
		$pl  = $this->wpdb->prefix . 'wc_order_product_lookup';
		$cl  = $this->wpdb->prefix . 'wc_customer_lookup';
		$cpl = $this->wpdb->prefix . 'wc_order_coupon_lookup';
		$tr  = $this->wpdb->term_relationships;
		$tt  = $this->wpdb->term_taxonomy;
		$t   = $this->wpdb->terms;
		$p   = $this->wpdb->posts;

		// Build SELECT columns.
		$select_parts = [];
		$join_parts   = [];
		$group_parts  = [];
		$needs_product  = false;
		$needs_category = false;
		$needs_customer = false;
		$needs_coupon   = false;

		// Dimension SELECT + GROUP BY.
		switch ( $dimension ) {
			case 'date_day':
				$select_parts[] = "DATE(os.date_created) AS dimension";
				$group_parts[]  = "DATE(os.date_created)";
				break;
			case 'date_week':
				$select_parts[] = "DATE(os.date_created - INTERVAL WEEKDAY(os.date_created) DAY) AS dimension";
				$group_parts[]  = "DATE(os.date_created - INTERVAL WEEKDAY(os.date_created) DAY)";
				break;
			case 'date_month':
				$select_parts[] = "DATE_FORMAT(os.date_created,'%Y-%m-01') AS dimension";
				$group_parts[]  = "DATE_FORMAT(os.date_created,'%Y-%m-01')";
				break;
			case 'product':
				$needs_product  = true;
				$select_parts[] = "p.post_title AS dimension";
				$group_parts[]  = "pl.product_id";
				break;
			case 'category':
				$needs_category = true;
				$select_parts[] = "t.name AS dimension";
				$group_parts[]  = "t.term_id";
				break;
			case 'order_status':
				$select_parts[] = "os.status AS dimension";
				$group_parts[]  = "os.status";
				break;
			case 'country':
				$needs_customer = true;
				$select_parts[] = "cl.country AS dimension";
				$group_parts[]  = "cl.country";
				break;
			case 'payment_method':
				$select_parts[] = "os.payment_method AS dimension";
				$group_parts[]  = "os.payment_method";
				break;
		}

		// Metric SELECT.
		$metric_map = [
			'revenue'         => 'SUM(os.total_sales)',
			'net_revenue'     => 'SUM(os.net_total)',
			'orders'          => 'COUNT(DISTINCT os.order_id)',
			'avg_order_value' => 'AVG(os.total_sales)',
			'qty_sold'        => $needs_product ? 'SUM(pl.product_qty)' : 'SUM(os.num_items_sold)',
			'customers'       => 'COUNT(DISTINCT os.customer_id)',
			'refunds'         => 'SUM(os.total_refunds)',
			'tax'             => 'SUM(os.tax_total)',
			'shipping'        => 'SUM(os.shipping_total)',
			'discount_amount' => $needs_coupon ? 'SUM(cpl.discount_amount)' : '0',
		];

		foreach ( $metrics as $m ) {
			$expr           = $metric_map[ $m ] ?? 'NULL';
			$select_parts[] = "{$expr} AS {$m}";
		}

		// Build JOINs.
		$from_clause = "{$os} os";

		if ( $needs_product ) {
			$join_parts[] = "INNER JOIN {$pl} pl ON os.order_id = pl.order_id";
			$join_parts[] = "LEFT JOIN {$p} p ON pl.product_id = p.ID";
		}
		if ( $needs_category ) {
			if ( ! $needs_product ) {
				$join_parts[] = "INNER JOIN {$pl} pl ON os.order_id = pl.order_id";
			}
			$join_parts[] = "INNER JOIN {$tr} tr ON pl.product_id = tr.object_id";
			$join_parts[] = "INNER JOIN {$tt} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'product_cat'";
			$join_parts[] = "INNER JOIN {$t} t ON tt.term_id = t.term_id";
		}
		if ( $needs_customer ) {
			$join_parts[] = "LEFT JOIN {$cl} cl ON os.customer_id = cl.customer_id";
		}
		if ( in_array( 'discount_amount', $metrics, true ) ) {
			$join_parts[] = "LEFT JOIN {$cpl} cpl ON os.order_id = cpl.order_id";
		}

		// Build WHERE.
		$excluded_in   = implode( ',', array_map( fn( $s ) => "'" . esc_sql( $s ) . "'", $this->excluded_statuses ) );
		$where_parts   = [
			"os.status NOT IN ({$excluded_in})",
		];
		$prepare_args  = [];

		// Date range.
		$date_to_full = $date_to . ' 23:59:59';
		$where_parts[] = "os.date_created BETWEEN %s AND %s";
		$prepare_args[] = $date_from;
		$prepare_args[] = $date_to_full;

		// Filters.
		if ( ! empty( $filters['order_status'] ) && is_array( $filters['order_status'] ) ) {
			$statuses      = array_map( 'sanitize_key', $filters['order_status'] );
			$status_in     = implode( ',', array_map( fn( $s ) => "'" . esc_sql( $s ) . "'", $statuses ) );
			$where_parts[] = "os.status IN ({$status_in})";
			// Remove the general exclusion since user specified.
			$where_parts   = array_filter( $where_parts, fn( $w ) => strpos( $w, 'NOT IN' ) === false );
		}

		if ( ! empty( $filters['min_order_value'] ) ) {
			$where_parts[]  = "os.total_sales >= %f";
			$prepare_args[] = floatval( $filters['min_order_value'] );
		}

		if ( ! empty( $filters['max_order_value'] ) ) {
			$where_parts[]  = "os.total_sales <= %f";
			$prepare_args[] = floatval( $filters['max_order_value'] );
		}

		// Assemble query.
		$select  = implode( ', ', $select_parts );
		$joins   = implode( ' ', $join_parts );
		$where   = implode( ' AND ', $where_parts );
		$group   = implode( ', ', $group_parts );
		$group   = $group ? "GROUP BY {$group}" : '';
		$limit   = absint( $limit ) ?: 100;

		$sql = "SELECT {$select} FROM {$from_clause} {$joins} WHERE {$where} {$group} ORDER BY 1 DESC LIMIT {$limit}";

		if ( ! empty( $prepare_args ) ) {
			$sql = $this->wpdb->prepare( $sql, ...$prepare_args );
		}

		return $this->wpdb->get_results( $sql ) ?: [];
	}

	// -------------------------------------------------------------------------
	// Saved Reports
	// -------------------------------------------------------------------------

	private function saved_reports_option() {
		return 'omni_reports_saved_reports';
	}

	public function get_saved_reports() {
		return get_option( $this->saved_reports_option(), [] );
	}

	public function save_report( $name, array $config ) {
		$name    = sanitize_text_field( $name );
		$reports = $this->get_saved_reports();
		$id      = 'rpt_' . uniqid();
		$reports[ $id ] = [
			'id'      => $id,
			'name'    => $name,
			'config'  => $config,
			'created' => current_time( 'mysql' ),
		];
		update_option( $this->saved_reports_option(), $reports );
		return $id;
	}

	public function delete_report( $id ) {
		$id      = sanitize_key( $id );
		$reports = $this->get_saved_reports();
		unset( $reports[ $id ] );
		update_option( $this->saved_reports_option(), $reports );
	}
}
