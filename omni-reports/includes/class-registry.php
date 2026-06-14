<?php
/**
 * Report Registry — stores metadata for all reports (standard + custom).
 *
 * @package OmniReports
 */

defined( 'ABSPATH' ) || exit;

class Omni_Reports_Registry {

	const OPTION = 'omni_reports_registry';

	/** Default (standard) report definitions */
	public static function defaults() {
		return [
			[
				'id'          => 'sales-overview',
				'name'        => 'Sales Overview',
				'slug'        => 'sales-overview',
				'category'    => 'sales',
				'version'     => '1.0',
				'icon'        => 'dashicons-chart-line',
				'color'       => 'teal',
				'menu_order'  => 10,
				'visible'     => true,
				'type'        => 'standard',
				'required'    => true,
				'description' => 'Total revenue, orders, average order value, refunds, tax, and shipping.',
				'page_slug'   => 'omni-reports-sales',
				'csv_export'  => true,
				'printable'   => true,
			],
			[
				'id'          => 'revenue-trends',
				'name'        => 'Revenue Trends',
				'slug'        => 'revenue-trends',
				'category'    => 'sales',
				'version'     => '1.0',
				'icon'        => 'dashicons-chart-area',
				'color'       => 'blue',
				'menu_order'  => 20,
				'visible'     => true,
				'type'        => 'standard',
				'required'    => false,
				'description' => 'Revenue grouped by day, week, or month with trend charts.',
				'page_slug'   => 'omni-reports-revenue',
				'csv_export'  => true,
				'printable'   => true,
			],
			[
				'id'          => 'products',
				'name'        => 'Products',
				'slug'        => 'products',
				'category'    => 'sales',
				'version'     => '1.0',
				'icon'        => 'dashicons-products',
				'color'       => 'purple',
				'menu_order'  => 30,
				'visible'     => true,
				'type'        => 'standard',
				'required'    => false,
				'description' => 'Revenue, quantity sold, and orders per product.',
				'page_slug'   => 'omni-reports-products',
				'csv_export'  => true,
				'printable'   => true,
			],
			[
				'id'          => 'categories',
				'name'        => 'Categories',
				'slug'        => 'categories',
				'category'    => 'sales',
				'version'     => '1.0',
				'icon'        => 'dashicons-category',
				'color'       => 'green',
				'menu_order'  => 40,
				'visible'     => true,
				'type'        => 'standard',
				'required'    => false,
				'description' => 'Revenue, quantity, and orders by product category.',
				'page_slug'   => 'omni-reports-categories',
				'csv_export'  => true,
				'printable'   => true,
			],
			[
				'id'          => 'customers',
				'name'        => 'Customers',
				'slug'        => 'customers',
				'category'    => 'sales',
				'version'     => '1.0',
				'icon'        => 'dashicons-groups',
				'color'       => 'blue',
				'menu_order'  => 50,
				'visible'     => true,
				'type'        => 'standard',
				'required'    => false,
				'description' => 'New vs returning customers, lifetime value, and geography.',
				'page_slug'   => 'omni-reports-customers',
				'csv_export'  => true,
				'printable'   => true,
			],
			[
				'id'          => 'orders',
				'name'        => 'Orders',
				'slug'        => 'orders',
				'category'    => 'sales',
				'version'     => '1.0',
				'icon'        => 'dashicons-list-view',
				'color'       => 'orange',
				'menu_order'  => 60,
				'visible'     => true,
				'type'        => 'standard',
				'required'    => false,
				'description' => 'Orders by status and over time.',
				'page_slug'   => 'omni-reports-orders',
				'csv_export'  => true,
				'printable'   => true,
			],
			[
				'id'          => 'coupons',
				'name'        => 'Coupons',
				'slug'        => 'coupons',
				'category'    => 'sales',
				'version'     => '1.0',
				'icon'        => 'dashicons-tag',
				'color'       => 'pink',
				'menu_order'  => 70,
				'visible'     => true,
				'type'        => 'standard',
				'required'    => false,
				'description' => 'Coupon usage, discount amounts, and revenue.',
				'page_slug'   => 'omni-reports-coupons',
				'csv_export'  => true,
				'printable'   => true,
			],
			[
				'id'          => 'tax',
				'name'        => 'Tax',
				'slug'        => 'tax',
				'category'    => 'sales',
				'version'     => '1.0',
				'icon'        => 'dashicons-calculator',
				'color'       => 'red',
				'menu_order'  => 80,
				'visible'     => true,
				'type'        => 'standard',
				'required'    => false,
				'description' => 'Tax collected by rate and over time.',
				'page_slug'   => 'omni-reports-tax',
				'csv_export'  => true,
				'printable'   => true,
			],
			[
				'id'          => 'shipping',
				'name'        => 'Shipping',
				'slug'        => 'shipping',
				'category'    => 'sales',
				'version'     => '1.0',
				'icon'        => 'dashicons-car',
				'color'       => 'teal',
				'menu_order'  => 90,
				'visible'     => true,
				'type'        => 'standard',
				'required'    => false,
				'description' => 'Shipping revenue by method and over time.',
				'page_slug'   => 'omni-reports-shipping',
				'csv_export'  => true,
				'printable'   => true,
			],
			[
				'id'          => 'profit',
				'name'        => 'Profit',
				'slug'        => 'profit',
				'category'    => 'finance',
				'version'     => '1.0',
				'icon'        => 'dashicons-chart-area',
				'color'       => 'purple',
				'menu_order'  => 15,
				'visible'     => true,
				'type'        => 'standard',
				'required'    => false,
				'description' => 'Gross profit, margins, and cost breakdown.',
				'page_slug'   => 'omni-reports-profit',
				'csv_export'  => true,
				'printable'   => true,
			],
			[
				'id'          => 'refunds',
				'name'        => 'Refunds',
				'slug'        => 'refunds',
				'category'    => 'sales',
				'version'     => '1.0',
				'icon'        => 'dashicons-undo',
				'color'       => 'red',
				'menu_order'  => 65,
				'visible'     => true,
				'type'        => 'standard',
				'required'    => false,
				'description' => 'Refund totals, rates, and top refunded products.',
				'page_slug'   => 'omni-reports-refunds',
				'csv_export'  => true,
				'printable'   => true,
			],
			[
				'id'          => 'costs',
				'name'        => 'Cost Manager',
				'slug'        => 'costs',
				'category'    => 'finance',
				'version'     => '1.0',
				'icon'        => 'dashicons-calculator',
				'color'       => 'orange',
				'menu_order'  => 100,
				'visible'     => false,
				'type'        => 'standard',
				'required'    => false,
				'description' => 'Manage product costs for profit calculations.',
				'page_slug'   => 'omni-reports-costs',
				'csv_export'  => false,
				'printable'   => false,
			],
			[
				'id'          => 'stock-tracker',
				'name'        => 'Stock Tracker',
				'slug'        => 'stock-tracker',
				'category'    => 'other',
				'version'     => '1.0',
				'icon'        => 'dashicons-archive',
				'color'       => 'blue',
				'menu_order'  => 110,
				'visible'     => true,
				'type'        => 'standard',
				'required'    => false,
				'description' => 'Live inventory levels, stock value, and reorder alerts.',
				'page_slug'   => 'omni-reports-stock',
				'csv_export'  => true,
				'printable'   => true,
			],
		];
	}

	public static function get_all() {
		$reports = get_option( self::OPTION, null );
		if ( $reports === null ) {
			self::install_defaults();
			$reports = get_option( self::OPTION, [] );
		}
		return is_array( $reports ) ? $reports : [];
	}

	public static function install_defaults() {
		$existing = get_option( self::OPTION, [] );
		$existing_ids = array_column( $existing, 'id' );
		$defaults = self::defaults();
		foreach ( $defaults as $r ) {
			if ( ! in_array( $r['id'], $existing_ids, true ) ) {
				$existing[] = $r;
			}
		}
		usort( $existing, fn( $a, $b ) => ( $a['menu_order'] ?? 99 ) <=> ( $b['menu_order'] ?? 99 ) );
		update_option( self::OPTION, $existing );
	}

	public static function save( array $report ) {
		$reports = self::get_all();
		$idx = null;
		foreach ( $reports as $i => $r ) {
			if ( $r['id'] === $report['id'] ) { $idx = $i; break; }
		}
		if ( $idx !== null ) {
			$reports[ $idx ] = array_merge( $reports[ $idx ], $report );
		} else {
			$reports[] = $report;
		}
		usort( $reports, fn( $a, $b ) => ( $a['menu_order'] ?? 99 ) <=> ( $b['menu_order'] ?? 99 ) );
		update_option( self::OPTION, $reports );
	}

	public static function delete( $id ) {
		$reports = self::get_all();
		$reports = array_values( array_filter( $reports, fn( $r ) => $r['id'] !== $id ) );
		update_option( self::OPTION, $reports );
	}

	public static function get_visible() {
		return array_filter( self::get_all(), fn( $r ) => ! empty( $r['visible'] ) );
	}

	public static function categories() {
		return [
			'sales'   => __( 'Sales Reports', 'omni-reports' ),
			'traffic' => __( 'Website Traffic', 'omni-reports' ),
			'ads'     => __( 'Advertising', 'omni-reports' ),
			'finance' => __( 'Finance', 'omni-reports' ),
			'other'   => __( 'Other', 'omni-reports' ),
		];
	}
}
