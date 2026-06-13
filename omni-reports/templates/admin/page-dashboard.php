<?php
defined( 'ABSPATH' ) || exit;
$reports    = Omni_Reports_Registry::get_all();
$total      = count( $reports );
$defaults   = count( Omni_Reports_Registry::defaults() );
$categories = Omni_Reports_Registry::categories();

// Count by category.
$counts = [ 'all' => $total ];
foreach ( $reports as $r ) {
	$cat = $r['category'] ?? 'other';
	$counts[ $cat ] = ( $counts[ $cat ] ?? 0 ) + 1;
}
?>

<!-- Manager Header -->
<div class="omni-manager-header">
	<div class="omni-manager-header-identity">
		<div class="omni-manager-logo">O</div>
		<div>
			<h1 class="omni-manager-title"><?php esc_html_e( 'Omni Report Manager', 'omni-reports' ); ?></h1>
			<p class="omni-manager-subtitle">
				<?php printf( esc_html__( '%d installed &bull; %d default reports', 'omni-reports' ), $total, $defaults ); ?>
			</p>
		</div>
	</div>
	<div class="omni-manager-actions">
		<button class="omni-btn omni-btn-secondary" id="omni-install-defaults">
			<span class="dashicons dashicons-download" style="font-size:14px;width:14px;height:14px;margin-top:1px"></span>
			<?php esc_html_e( 'Install default reports', 'omni-reports' ); ?>
		</button>
		<button class="omni-btn omni-btn-primary" id="omni-create-report-btn">
			<span style="font-size:16px;line-height:1">+</span>
			<?php esc_html_e( 'Create report', 'omni-reports' ); ?>
		</button>
	</div>
</div>

<!-- Filter Tabs -->
<div class="omni-filter-tabs">
	<div class="omni-filter-tab active" data-cat="all">
		<span class="dashicons dashicons-grid-view" style="font-size:14px;width:14px;height:14px;margin-top:2px"></span>
		<?php esc_html_e( 'All reports', 'omni-reports' ); ?>
		<span class="omni-count"><?php echo esc_html( $counts['all'] ); ?></span>
	</div>
	<?php foreach ( $categories as $key => $label ) :
		if ( empty( $counts[ $key ] ) ) continue; ?>
	<div class="omni-filter-tab" data-cat="<?php echo esc_attr( $key ); ?>">
		<?php echo esc_html( $label ); ?>
		<span class="omni-count"><?php echo esc_html( $counts[ $key ] ?? 0 ); ?></span>
	</div>
	<?php endforeach; ?>
</div>

<!-- Report Grid -->
<div class="omni-report-grid" id="omni-report-grid">
	<?php foreach ( $reports as $r ) :
		$color    = sanitize_html_class( $r['color'] ?? 'teal' );
		$icon     = esc_attr( $r['icon'] ?? 'dashicons-chart-bar' );
		$page_slug = $r['page_slug'] ?? ( 'omni-reports-' . $r['slug'] );
		$is_standard = ( $r['type'] ?? '' ) === 'standard';
		?>
	<div class="omni-report-card" data-id="<?php echo esc_attr( $r['id'] ); ?>" data-cat="<?php echo esc_attr( $r['category'] ?? 'other' ); ?>">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug ) ); ?>" class="omni-icon-tile omni-color-<?php echo esc_attr( $color ); ?>" style="text-decoration:none">
			<span class="dashicons <?php echo esc_attr( $icon ); ?>" style="color:#fff"></span>
		</a>
		<div class="omni-report-info">
			<div class="omni-report-name">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug ) ); ?>" style="color:inherit;text-decoration:none">
					<?php echo esc_html( $r['name'] ); ?>
				</a>
			</div>
			<div class="omni-report-meta">
				<span class="omni-report-category"><?php echo esc_html( $categories[ $r['category'] ?? 'other' ] ?? $r['category'] ); ?></span>
				<span><?php printf( esc_html__( 'Menu order: %d', 'omni-reports' ), intval( $r['menu_order'] ?? 0 ) ); ?></span>
				<?php if ( ! empty( $r['required'] ) ) : ?>
				<span class="omni-badge omni-badge-required"><?php esc_html_e( 'Required', 'omni-reports' ); ?></span>
				<?php endif; ?>
				<?php if ( empty( $r['visible'] ) ) : ?>
				<span style="color:#A0AEC0;font-size:11px"><?php esc_html_e( '· Not visible in menu', 'omni-reports' ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<div class="omni-report-actions">
			<button class="omni-action-btn omni-edit-report" title="<?php esc_attr_e( 'Edit', 'omni-reports' ); ?>"
				data-report="<?php echo esc_attr( wp_json_encode( $r ) ); ?>">
				<span class="dashicons dashicons-admin-generic"></span>
			</button>
			<?php if ( ! $is_standard ) : ?>
			<button class="omni-action-btn omni-duplicate-report" title="<?php esc_attr_e( 'Duplicate', 'omni-reports' ); ?>"
				data-id="<?php echo esc_attr( $r['id'] ); ?>">
				<span class="dashicons dashicons-admin-page"></span>
			</button>
			<?php endif; ?>
			<button class="omni-action-btn omni-order-up" title="<?php esc_attr_e( 'Move up', 'omni-reports' ); ?>"
				data-id="<?php echo esc_attr( $r['id'] ); ?>">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
			</button>
			<button class="omni-action-btn omni-order-down" title="<?php esc_attr_e( 'Move down', 'omni-reports' ); ?>"
				data-id="<?php echo esc_attr( $r['id'] ); ?>">
				<span class="dashicons dashicons-arrow-down-alt2"></span>
			</button>
			<?php if ( ! $is_standard ) : ?>
			<button class="omni-action-btn danger omni-delete-report" title="<?php esc_attr_e( 'Remove', 'omni-reports' ); ?>"
				data-id="<?php echo esc_attr( $r['id'] ); ?>">
				<span class="dashicons dashicons-minus"></span>
			</button>
			<?php endif; ?>
		</div>
	</div>
	<?php endforeach; ?>
</div>

<!-- Create / Edit Report Modal -->
<div class="omni-modal-overlay" id="omni-report-modal" style="display:none">
	<div class="omni-modal">
		<div class="omni-modal-header">
			<div class="omni-modal-header-identity">
				<div class="omni-modal-logo">O</div>
				<div>
					<div class="omni-modal-title" id="omni-modal-title"><?php esc_html_e( 'Create New Report', 'omni-reports' ); ?></div>
					<div class="omni-modal-subtitle"><?php esc_html_e( 'Report Configuration', 'omni-reports' ); ?></div>
				</div>
			</div>
			<button class="omni-modal-close" id="omni-modal-close">&times;</button>
		</div>

		<div class="omni-modal-tabs">
			<div class="omni-modal-tab active" data-tab="data">
				<span class="omni-modal-tab-icon"><span class="dashicons dashicons-database" style="font-size:12px;width:12px;height:12px"></span></span>
				<?php esc_html_e( 'Data', 'omni-reports' ); ?>
			</div>
			<div class="omni-modal-tab" data-tab="design">
				<span class="omni-modal-tab-icon"><span class="dashicons dashicons-art" style="font-size:12px;width:12px;height:12px"></span></span>
				<?php esc_html_e( 'Design', 'omni-reports' ); ?>
			</div>
			<div class="omni-modal-tab" data-tab="advanced">
				<span class="omni-modal-tab-icon"><span class="dashicons dashicons-admin-settings" style="font-size:12px;width:12px;height:12px"></span></span>
				<?php esc_html_e( 'Advanced', 'omni-reports' ); ?>
			</div>
		</div>

		<div class="omni-modal-body">
			<!-- Tab: Data -->
			<div class="omni-tab-panel active" id="tab-data">
				<div class="omni-field">
					<label><?php esc_html_e( 'Report Name', 'omni-reports' ); ?> <span class="req">*</span></label>
					<input type="text" id="rpt-name" placeholder="<?php esc_attr_e( 'Enter report name', 'omni-reports' ); ?>">
				</div>
				<div class="omni-field">
					<label><?php esc_html_e( 'Report Slug', 'omni-reports' ); ?> <span class="req">*</span></label>
					<input type="text" id="rpt-slug" placeholder="report-slug">
					<div class="omni-field-hint"><?php esc_html_e( 'Used in the URL. Auto-generated from name.', 'omni-reports' ); ?></div>
				</div>
				<div class="omni-field">
					<label><?php esc_html_e( 'Report Category', 'omni-reports' ); ?></label>
					<select id="rpt-category">
						<?php foreach ( $categories as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<div class="omni-field-hint"><?php esc_html_e( 'Choose the category this report belongs to.', 'omni-reports' ); ?></div>
				</div>
				<div class="omni-field-row">
					<div class="omni-field">
						<label><?php esc_html_e( 'Version Number', 'omni-reports' ); ?> <span class="req">*</span></label>
						<input type="text" id="rpt-version" value="1.0" placeholder="1.0">
						<div class="omni-field-hint"><?php esc_html_e( 'Use major.minor format (e.g. 1.0, 1.2.1)', 'omni-reports' ); ?></div>
					</div>
					<div class="omni-field">
						<label><?php esc_html_e( 'Menu Order', 'omni-reports' ); ?></label>
						<input type="number" id="rpt-order" value="0" min="0">
						<div class="omni-field-hint"><?php esc_html_e( 'Lower = first. 0 = first.', 'omni-reports' ); ?></div>
					</div>
				</div>
				<div class="omni-toggle-row">
					<div>
						<div class="omni-toggle-label"><?php esc_html_e( 'Appear In Menu', 'omni-reports' ); ?></div>
						<div class="omni-toggle-desc"><?php esc_html_e( 'Show this report in the navigation menu.', 'omni-reports' ); ?></div>
					</div>
					<label class="omni-toggle">
						<input type="checkbox" id="rpt-visible" checked>
						<span class="omni-toggle-track"></span>
					</label>
				</div>
			</div>

			<!-- Tab: Design -->
			<div class="omni-tab-panel" id="tab-design">
				<div class="omni-field">
					<label><?php esc_html_e( 'Icon', 'omni-reports' ); ?></label>
					<div class="omni-icon-picker" id="omni-icon-picker">
						<?php
						$icons = [
							'dashicons-chart-bar'     => 'Bar',
							'dashicons-chart-line'    => 'Line',
							'dashicons-chart-pie'     => 'Pie',
							'dashicons-chart-area'    => 'Area',
							'dashicons-groups'        => 'Users',
							'dashicons-cart'          => 'Cart',
							'dashicons-tag'           => 'Tag',
							'dashicons-money-alt'     => 'Money',
							'dashicons-car'           => 'Ship',
							'dashicons-calculator'    => 'Tax',
							'dashicons-products'      => 'Products',
							'dashicons-list-view'     => 'Orders',
						];
						foreach ( $icons as $cls => $label ) : ?>
						<div class="omni-icon-option" data-icon="<?php echo esc_attr( $cls ); ?>">
							<span class="dashicons <?php echo esc_attr( $cls ); ?>" style="font-size:20px;width:20px;height:20px"></span>
							<span><?php echo esc_html( $label ); ?></span>
						</div>
						<?php endforeach; ?>
					</div>
					<input type="hidden" id="rpt-icon" value="dashicons-chart-bar">
				</div>
				<div class="omni-field">
					<label><?php esc_html_e( 'Color Theme', 'omni-reports' ); ?></label>
					<div class="omni-color-picker" id="omni-color-picker">
						<?php
						$colors = [
							'teal'   => [ '#00D4AA', '#0099FF', 'Teal' ],
							'blue'   => [ '#0099FF', '#6B46C1', 'Blue' ],
							'purple' => [ '#9F7AEA', '#6B46C1', 'Purple' ],
							'orange' => [ '#F6AD55', '#E53E3E', 'Orange' ],
							'red'    => [ '#FC8181', '#C53030', 'Red' ],
							'green'  => [ '#68D391', '#276749', 'Green' ],
							'pink'   => [ '#FBB6CE', '#B83280', 'Pink' ],
						];
						foreach ( $colors as $key => [ $c1, $c2, $label ] ) : ?>
						<div class="omni-color-option" data-color="<?php echo esc_attr( $key ); ?>">
							<div class="omni-color-dot" style="background:linear-gradient(135deg,<?php echo esc_attr( $c1 ); ?>,<?php echo esc_attr( $c2 ); ?>)"></div>
							<span><?php echo esc_html( $label ); ?></span>
						</div>
						<?php endforeach; ?>
					</div>
					<input type="hidden" id="rpt-color" value="teal">
				</div>
			</div>

			<!-- Tab: Advanced -->
			<div class="omni-tab-panel" id="tab-advanced">
				<div class="omni-field">
					<label><?php esc_html_e( 'Description', 'omni-reports' ); ?></label>
					<textarea id="rpt-description" rows="3" placeholder="<?php esc_attr_e( 'Optional description of this report...', 'omni-reports' ); ?>"></textarea>
				</div>
				<div class="omni-toggle-row">
					<div>
						<div class="omni-toggle-label"><?php esc_html_e( 'Enable CSV Export', 'omni-reports' ); ?></div>
					</div>
					<label class="omni-toggle">
						<input type="checkbox" id="rpt-csv" checked>
						<span class="omni-toggle-track"></span>
					</label>
				</div>
				<div class="omni-toggle-row">
					<div>
						<div class="omni-toggle-label"><?php esc_html_e( 'Enable Print', 'omni-reports' ); ?></div>
					</div>
					<label class="omni-toggle">
						<input type="checkbox" id="rpt-print" checked>
						<span class="omni-toggle-track"></span>
					</label>
				</div>
			</div>
		</div><!-- /.omni-modal-body -->

		<div class="omni-modal-footer">
			<button class="omni-btn omni-btn-ghost" id="omni-modal-cancel">&times; <?php esc_html_e( 'Cancel', 'omni-reports' ); ?></button>
			<button class="omni-btn omni-btn-primary" id="omni-modal-save">+ <?php esc_html_e( 'Create Report', 'omni-reports' ); ?></button>
		</div>
	</div>
</div>
