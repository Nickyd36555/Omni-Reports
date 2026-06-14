<?php
/**
 * Column/metric definitions per report type.
 * Used to populate the Modify tab in the Edit Report modal.
 *
 * @package OmniReports
 */

defined( 'ABSPATH' ) || exit;

class Omni_Reports_Columns {

	/**
	 * Returns all available columns per report slug.
	 * Each column: [ key, label, default_on ]
	 */
	public static function definitions() {
		return [
			'sales-overview' => [
				[ 'key' => 'gross_revenue',    'label' => 'Gross Revenue',      'on' => true ],
				[ 'key' => 'net_revenue',       'label' => 'Net Revenue',         'on' => true ],
				[ 'key' => 'orders',            'label' => 'Orders',              'on' => true ],
				[ 'key' => 'avg_order_value',   'label' => 'Avg Order Value',     'on' => true ],
				[ 'key' => 'refunds',           'label' => 'Refunds',             'on' => true ],
				[ 'key' => 'tax',               'label' => 'Tax Collected',       'on' => true ],
				[ 'key' => 'shipping',          'label' => 'Shipping Revenue',    'on' => true ],
				[ 'key' => 'coupons_used',      'label' => 'Coupons Used',        'on' => false ],
				[ 'key' => 'num_items_sold',    'label' => 'Items Sold',          'on' => false ],
			],
			'revenue-trends' => [
				[ 'key' => 'date',              'label' => 'Date',                'on' => true ],
				[ 'key' => 'revenue',           'label' => 'Gross Revenue',       'on' => true ],
				[ 'key' => 'net_revenue',       'label' => 'Net Revenue',         'on' => true ],
				[ 'key' => 'orders',            'label' => 'Orders',              'on' => true ],
				[ 'key' => 'avg_order_value',   'label' => 'Avg Order Value',     'on' => false ],
				[ 'key' => 'refunds',           'label' => 'Refunds',             'on' => false ],
				[ 'key' => 'tax',               'label' => 'Tax',                 'on' => false ],
				[ 'key' => 'shipping',          'label' => 'Shipping',            'on' => false ],
			],
			'products' => [
				[ 'key' => 'product_name',      'label' => 'Product Name',        'on' => true ],
				[ 'key' => 'sku',               'label' => 'SKU',                 'on' => false ],
				[ 'key' => 'revenue',           'label' => 'Revenue',             'on' => true ],
				[ 'key' => 'qty_sold',          'label' => 'Quantity Sold',       'on' => true ],
				[ 'key' => 'orders',            'label' => 'Orders',              'on' => true ],
				[ 'key' => 'avg_price',         'label' => 'Avg Price',           'on' => false ],
				[ 'key' => 'net_revenue',       'label' => 'Net Revenue',         'on' => false ],
				[ 'key' => 'refunds',           'label' => 'Refunded Qty',        'on' => false ],
			],
			'categories' => [
				[ 'key' => 'category',          'label' => 'Category',            'on' => true ],
				[ 'key' => 'revenue',           'label' => 'Revenue',             'on' => true ],
				[ 'key' => 'net_revenue',       'label' => 'Net Revenue',         'on' => false ],
				[ 'key' => 'qty_sold',          'label' => 'Qty Sold',            'on' => true ],
				[ 'key' => 'orders',            'label' => 'Orders',              'on' => true ],
				[ 'key' => 'avg_order_value',   'label' => 'Avg Order Value',     'on' => false ],
			],
			'customers' => [
				[ 'key' => 'customer_name',     'label' => 'Customer Name',       'on' => true ],
				[ 'key' => 'email',             'label' => 'Email',               'on' => true ],
				[ 'key' => 'total_spend',       'label' => 'Total Spend (LTV)',   'on' => true ],
				[ 'key' => 'order_count',       'label' => 'Order Count',         'on' => true ],
				[ 'key' => 'avg_order',         'label' => 'Avg Order Value',     'on' => true ],
				[ 'key' => 'country',           'label' => 'Country',             'on' => false ],
				[ 'key' => 'first_order',       'label' => 'First Order Date',    'on' => false ],
				[ 'key' => 'last_order',        'label' => 'Last Order Date',     'on' => false ],
				[ 'key' => 'new_returning',     'label' => 'New / Returning',     'on' => false ],
			],
			'orders' => [
				[ 'key' => 'status',            'label' => 'Status',              'on' => true ],
				[ 'key' => 'order_count',       'label' => 'Order Count',         'on' => true ],
				[ 'key' => 'revenue',           'label' => 'Revenue',             'on' => true ],
				[ 'key' => 'percentage',        'label' => '% of Total',          'on' => true ],
				[ 'key' => 'avg_order_value',   'label' => 'Avg Order Value',     'on' => false ],
				[ 'key' => 'tax',               'label' => 'Tax',                 'on' => false ],
				[ 'key' => 'shipping',          'label' => 'Shipping',            'on' => false ],
				[ 'key' => 'refunds',           'label' => 'Refunds',             'on' => false ],
			],
			'coupons' => [
				[ 'key' => 'coupon_code',       'label' => 'Coupon Code',         'on' => true ],
				[ 'key' => 'usage_count',       'label' => 'Times Used',          'on' => true ],
				[ 'key' => 'discount_amount',   'label' => 'Total Discount',      'on' => true ],
				[ 'key' => 'revenue',           'label' => 'Revenue',             'on' => true ],
				[ 'key' => 'avg_discount',      'label' => 'Avg Discount',        'on' => true ],
				[ 'key' => 'first_used',        'label' => 'First Used',          'on' => false ],
				[ 'key' => 'last_used',         'label' => 'Last Used',           'on' => false ],
			],
			'tax' => [
				[ 'key' => 'tax_class',         'label' => 'Tax Class / Rate',    'on' => true ],
				[ 'key' => 'tax_amount',        'label' => 'Tax Collected',       'on' => true ],
				[ 'key' => 'order_count',       'label' => 'Orders',              'on' => true ],
				[ 'key' => 'shipping_tax',      'label' => 'Shipping Tax',        'on' => false ],
				[ 'key' => 'rate_percent',      'label' => 'Rate %',              'on' => false ],
			],
			'shipping' => [
				[ 'key' => 'method',            'label' => 'Shipping Method',     'on' => true ],
				[ 'key' => 'revenue',           'label' => 'Shipping Revenue',    'on' => true ],
				[ 'key' => 'order_count',       'label' => 'Orders',              'on' => true ],
				[ 'key' => 'avg_shipping',      'label' => 'Avg Shipping Cost',   'on' => true ],
				[ 'key' => 'percentage',        'label' => '% of Orders',         'on' => false ],
			],
		];
	}

	/**
	 * Get enabled column keys for a report slug, falling back to defaults.
	 */
	public static function get_enabled( $slug, $saved = [] ) {
		$defs = self::definitions()[ $slug ] ?? [];
		if ( empty( $saved ) ) {
			return array_column( array_filter( $defs, fn($c) => $c['on'] ), 'key' );
		}
		return array_values( array_intersect( array_column( $defs, 'key' ), $saved ) );
	}
}
