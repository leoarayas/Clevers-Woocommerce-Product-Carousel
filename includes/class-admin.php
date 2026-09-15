<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Clevers_Product_Carousel_Admin {

	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . CLV_SLUG, array( $this, 'save_meta_box' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'add_row_actions' ), 10, 2 );
		add_action( 'admin_action_clv_duplicate_carousel', array( $this, 'handle_duplicate_carousel' ) );
		add_action( 'admin_action_clv_export_carousel_json', array( $this, 'handle_export_carousel_json' ) );
		add_action( 'admin_post_clv_import_carousel_json', array( $this, 'handle_import_carousel_json' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
		add_action( 'wp_ajax_clv_search_products', array( $this, 'ajax_search_products' ) );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'clv_carousel_settings',
			__( 'Carousel Settings', 'clevers-product-carousel' ),
			array( $this, 'render_meta_box' ),
			CLV_SLUG,
			'normal',
			'high'
		);

		add_meta_box(
			'clv_carousel_shortcode',
			__( 'Shortcode & Preview', 'clevers-product-carousel' ),
			array( $this, 'render_shortcode_meta_box' ),
			CLV_SLUG,
			'side',
			'high'
		);

		add_meta_box(
			'clv_carousel_diagnostics',
			__( 'Diagnostics', 'clevers-product-carousel' ),
			array( $this, 'render_diagnostics_meta_box' ),
			CLV_SLUG,
			'side',
			'low'
		);
	}

	public function render_shortcode_meta_box( $post ) {
		$shortcode = sprintf( '[clevers_carousel id="%d"]', (int) $post->ID );
		$meta      = clevers_product_carousel_get_carousel_meta( $post->ID );
		$preset    = max( 1, min( 4, (int) ( $meta['preset'] ?? 1 ) ) );
		?>
		<p>
			<label for="clv-shortcode-copy"><strong><?php esc_html_e( 'Use this shortcode', 'clevers-product-carousel' ); ?></strong></label>
			<input
				type="text"
				id="clv-shortcode-copy"
				class="widefat"
				readonly
				onfocus="this.select();"
				value="<?php echo esc_attr( $shortcode ); ?>"
			/>
		</p>
		<p class="description">
			<?php esc_html_e( 'You can also insert it from the Gutenberg block: Clevers Product Carousel.', 'clevers-product-carousel' ); ?>
		</p>

		<hr />

		<p><strong><?php esc_html_e( 'Tools', 'clevers-product-carousel' ); ?></strong></p>
		<p>
			<a class="button button-secondary" href="<?php echo esc_url( $this->get_duplicate_url( $post->ID ) ); ?>">
				<?php esc_html_e( 'Duplicate Carousel', 'clevers-product-carousel' ); ?>
			</a>
		</p>
		<p>
			<a class="button button-secondary" href="<?php echo esc_url( $this->get_export_url( $post->ID ) ); ?>">
				<?php esc_html_e( 'Export JSON', 'clevers-product-carousel' ); ?>
			</a>
		</p>
		<div style="margin-top:10px;">
			<label for="clv-import-json-<?php echo esc_attr( (int) $post->ID ); ?>">
				<?php esc_html_e( 'Import JSON into this carousel', 'clevers-product-carousel' ); ?>
			</label>
			<textarea
				id="clv-import-json-<?php echo esc_attr( (int) $post->ID ); ?>"
				name="clv_import_json"
				rows="6"
				class="widefat"
				placeholder="<?php echo esc_attr( '{"preset":1,"limit":8}' ); ?>"
			></textarea>
			<p class="description">
				<?php esc_html_e( 'Uses the main editor save flow to avoid nested forms.', 'clevers-product-carousel' ); ?>
			</p>
			<p>
				<button type="submit" name="clv_apply_import_json" value="1" class="button button-primary"><?php esc_html_e( 'Import Settings', 'clevers-product-carousel' ); ?></button>
			</p>
		</div>

		<style>
			.clv-preview-shell { border: 1px solid #dcdcde; border-radius: 8px; padding: 10px; background: #fff; }
			.clv-preview-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 8px; }
			.clv-preview-card { border: 1px solid #e2e4e7; border-radius: 6px; padding: 8px; background: #f6f7f7; min-height: 74px; position: relative; }
			.clv-preview-card::before { content: ""; display: block; height: 24px; border-radius: 4px; background: linear-gradient(90deg, #e6e8eb, #f5f6f7, #e6e8eb); margin-bottom: 7px; }
			.clv-preview-card::after { content: ""; display: block; height: 8px; border-radius: 4px; width: 75%; background: #d6d9dd; }
			.clv-preview-shell[data-preset="2"] .clv-preview-card { border-radius: 12px; box-shadow: 0 1px 8px rgba(0,0,0,.05); }
			.clv-preview-shell[data-preset="3"] .clv-preview-card { background: #1f2937; border-color: #111827; }
			.clv-preview-shell[data-preset="3"] .clv-preview-card::after { background: #9ca3af; }
			.clv-preview-shell[data-preset="4"] .clv-preview-card { background: #ffffff; border-color: #e5e7eb; }
			.clv-preview-label { margin: 0 0 8px; font-size: 12px; color: #50575e; }
		</style>

		<p class="clv-preview-label">
			<?php
			printf(
				/* translators: %d: preset number. */
				esc_html__( 'Quick preview of Preset %d', 'clevers-product-carousel' ),
				$preset
			);
			?>
		</p>
		<div class="clv-preview-shell" id="clv-preset-preview-side" data-preset="<?php echo esc_attr( $preset ); ?>">
			<div class="clv-preview-grid">
				<div class="clv-preview-card"></div>
				<div class="clv-preview-card"></div>
				<div class="clv-preview-card"></div>
				<div class="clv-preview-card"></div>
			</div>
		</div>
		<?php
	}

	public function render_diagnostics_meta_box( $post ) {
		$meta               = clevers_product_carousel_get_carousel_meta( $post->ID );
		$manual_ids         = array_values( array_unique( array_filter( array_map( 'intval', (array) ( $meta['manual_product_ids'] ?? array() ) ) ) ) );
		$valid_manual_ids   = array();
		$invalid_manual_ids = array();

		foreach ( $manual_ids as $manual_id ) {
			$product = function_exists( 'wc_get_product' ) ? wc_get_product( $manual_id ) : false;
			if ( $product ) {
				$valid_manual_ids[] = $manual_id;
			} else {
				$invalid_manual_ids[] = $manual_id;
			}
		}

		$diag = array(
			'date'                     => gmdate( 'c' ),
			'plugin_version'           => '1.2.1',
			'php_version'              => PHP_VERSION,
			'wp_version'               => get_bloginfo( 'version' ),
			'woocommerce_active'       => class_exists( 'WooCommerce' ),
			'jquery_registered'        => wp_script_is( 'jquery', 'registered' ),
			'clv_slick_registered'     => wp_script_is( 'clv-slick', 'registered' ),
			'clv_carousel_registered'  => wp_script_is( 'clv-carousel', 'registered' ),
			'gutenberg_block_registered' => class_exists( 'WP_Block_Type_Registry' ) ? WP_Block_Type_Registry::get_instance()->is_registered( 'clevers-product-carousel/carousel' ) : false,
			'manual_mode_enabled'      => ! empty( $meta['manual_products_enabled'] ),
			'manual_ids_total'         => count( $manual_ids ),
			'manual_ids_valid'         => $valid_manual_ids,
			'manual_ids_invalid'       => $invalid_manual_ids,
			'builder_compat_mode'      => ! empty( $meta['builder_compat_mode'] ),
			'builder_init_delay_ms'    => (int) ( $meta['builder_init_delay_ms'] ?? 0 ),
			'builder_disable_center_mode' => ! empty( $meta['builder_disable_center_mode'] ),
		);
		$queue_metrics = clevers_product_carousel_get_queue_metrics( (int) $post->ID );
		$diag['queue_metrics'] = $queue_metrics;
		?>
		<p class="description"><?php esc_html_e( 'Quick environment and configuration checks for this carousel.', 'clevers-product-carousel' ); ?></p>
		<ul style="margin:0 0 10px 16px; list-style:disc;">
			<li><?php echo esc_html( class_exists( 'WooCommerce' ) ? __( 'WooCommerce active', 'clevers-product-carousel' ) : __( 'WooCommerce missing', 'clevers-product-carousel' ) ); ?></li>
			<li><?php echo esc_html( ! empty( $meta['builder_compat_mode'] ) ? __( 'Builder compatibility mode ON', 'clevers-product-carousel' ) : __( 'Builder compatibility mode OFF', 'clevers-product-carousel' ) ); ?></li>
			<li><?php echo esc_html( sprintf( __( 'Manual IDs: %1$d (%2$d invalid)', 'clevers-product-carousel' ), count( $manual_ids ), count( $invalid_manual_ids ) ) ); ?></li>
		</ul>
		<div style="border:1px solid #dcdcde;border-radius:6px;padding:8px;margin-bottom:10px;background:#fff;">
			<p style="margin:0 0 8px;"><strong><?php esc_html_e( 'Queue observability', 'clevers-product-carousel' ); ?></strong></p>
			<ul style="margin:0 0 6px 16px;list-style:disc;">
				<li><?php echo esc_html( sprintf( __( 'Pending: %d', 'clevers-product-carousel' ), (int) $queue_metrics['pending'] ) ); ?></li>
				<li><?php echo esc_html( sprintf( __( 'Processed: %d', 'clevers-product-carousel' ), (int) $queue_metrics['processed'] ) ); ?></li>
				<li><?php echo esc_html( sprintf( __( 'Failed: %d', 'clevers-product-carousel' ), (int) $queue_metrics['failed'] ) ); ?></li>
				<li><?php echo esc_html( sprintf( __( 'Avg time per product: %sms', 'clevers-product-carousel' ), number_format_i18n( (float) $queue_metrics['avg_time_ms_per_product'], 2 ) ) ); ?></li>
			</ul>
			<?php if ( ! empty( $queue_metrics['last_error'] ) ) : ?>
				<p style="margin:6px 0 0;color:#b32d2e;">
					<strong><?php esc_html_e( 'Last error:', 'clevers-product-carousel' ); ?></strong>
					<?php echo esc_html( $queue_metrics['last_error'] ); ?>
				</p>
			<?php endif; ?>
		</div>
		<textarea id="clv-diagnostic-report" class="widefat" rows="10" readonly><?php echo esc_textarea( wp_json_encode( $diag, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></textarea>
		<p style="margin-top:8px;">
			<button type="button" class="button button-secondary" id="clv-copy-diagnostic-report"><?php esc_html_e( 'Copy report', 'clevers-product-carousel' ); ?></button>
		</p>
		<script>
			(function() {
				var btn = document.getElementById('clv-copy-diagnostic-report');
				var ta = document.getElementById('clv-diagnostic-report');
				if (!btn || !ta) return;
				btn.addEventListener('click', function() {
					ta.focus();
					ta.select();
					try {
						document.execCommand('copy');
						btn.textContent = '<?php echo esc_js( __( 'Copied', 'clevers-product-carousel' ) ); ?>';
						setTimeout(function(){ btn.textContent = '<?php echo esc_js( __( 'Copy report', 'clevers-product-carousel' ) ); ?>'; }, 1200);
					} catch (e) {}
				});
			})();
		</script>
		<?php
	}

	public function render_meta_box( $post ) {
		$meta = clevers_product_carousel_get_carousel_meta( $post->ID );

		wp_nonce_field( 'clv_save_carousel', 'clv_carousel_nonce' );

		$preset       = max( 1, min( 4, (int) ( $meta['preset'] ?? 1 ) ) );
		$limit        = max( 1, (int) ( $meta['limit'] ?? 8 ) );
		$orderby      = isset( $meta['orderby'] ) ? sanitize_text_field( $meta['orderby'] ) : 'date';
		$order        = isset( $meta['order'] ) ? sanitize_text_field( $meta['order'] ) : 'DESC';
		$categories   = array_map( 'sanitize_title', (array) ( $meta['categories'] ?? array() ) );
		$on_sale      = ! empty( $meta['on_sale'] );
		$on_featured  = ! empty( $meta['on_featured'] );
		$instock_only = ! empty( $meta['instock_only'] );
		$slides       = max( 1, (int) ( $meta['slidesToShow'] ?? 4 ) );
		$slides_tablet = max( 1, (int) ( $meta['slidesToShowTablet'] ?? min( 2, $slides ) ) );
		$slides_mobile = max( 1, (int) ( $meta['slidesToShowMobile'] ?? 1 ) );
		$autoplay     = ! empty( $meta['autoplay'] );
		$autoplay_ms  = max( 500, (int) ( $meta['autoplayMs'] ?? 3000 ) );
		$dots         = ! empty( $meta['dots'] );
		$arrows       = ! empty( $meta['arrows'] );
		$manual_ids   = array_map( 'intval', (array) ( $meta['manual_product_ids'] ?? array() ) );
		$use_manual   = ! empty( $meta['manual_products_enabled'] );
		$builder_compat_mode      = ! empty( $meta['builder_compat_mode'] );
		$builder_init_delay_ms    = max( 0, min( 5000, (int) ( $meta['builder_init_delay_ms'] ?? 0 ) ) );
		$builder_disable_center   = ! empty( $meta['builder_disable_center_mode'] );

		$color_primary     = isset( $meta['color_primary'] ) ? esc_attr( $meta['color_primary'] ) : '';
		$color_primary2    = isset( $meta['color_primary2'] ) ? esc_attr( $meta['color_primary2'] ) : '';
		$color_secondary   = isset( $meta['color_secondary'] ) ? esc_attr( $meta['color_secondary'] ) : '';
		$color_accent      = isset( $meta['color_accent'] ) ? esc_attr( $meta['color_accent'] ) : '';
		$color_text        = isset( $meta['color_text'] ) ? esc_attr( $meta['color_text'] ) : '';
		$color_card_bg     = isset( $meta['color_card_bg'] ) ? esc_attr( $meta['color_card_bg'] ) : '';
		$color_border      = isset( $meta['color_border'] ) ? esc_attr( $meta['color_border'] ) : '';
		$bubble_background = isset( $meta['bubble_background'] ) ? esc_attr( $meta['bubble_background'] ) : '';
		$bubble_text       = isset( $meta['bubble_text'] ) ? esc_attr( $meta['bubble_text'] ) : '';
		$button_background = isset( $meta['button_background'] ) ? esc_attr( $meta['button_background'] ) : '';
		$button_text       = isset( $meta['button_text'] ) ? esc_attr( $meta['button_text'] ) : '';

		$button_text_color_value = ( 'transparent' === $button_text ) ? '' : $button_text;
		$terms                   = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		?>
		<style>
			.clv-field { margin: 12px 0; }
			.clv-field label { display: block; font-weight: 600; margin-bottom: 4px; }
			.clv-fieldset { border: 1px solid #dcdcde; border-radius: 8px; padding: 10px 12px; margin: 12px 0; }
			.clv-fieldset legend { font-weight: 600; padding: 0 6px; }
			.clv-inline-check { display: inline-flex; align-items: center; gap: 6px; margin-right: 14px; margin-bottom: 6px; font-weight: 400; }
			.clv-categories-list { max-height: 180px; overflow: auto; border: 1px solid #dcdcde; border-radius: 6px; padding: 8px; background: #fff; }
			.clv-categories-list label { display: flex; align-items: center; gap: 6px; font-weight: 400; margin: 0 0 6px; }
			.clv-product-picker { border: 1px solid #dcdcde; border-radius: 6px; padding: 8px; background: #fff; }
			.clv-product-picker-results { border: 1px solid #dcdcde; border-radius: 4px; margin-top: 6px; max-height: 140px; overflow: auto; background: #fff; }
			.clv-product-picker-results button { display: block; width: 100%; text-align: left; padding: 6px 8px; border: 0; background: #fff; cursor: pointer; }
			.clv-product-picker-results button:hover { background: #f0f6fc; }
			.clv-selected-products { margin-top: 8px; display: flex; flex-wrap: wrap; gap: 6px; }
			.clv-chip { display: inline-flex; align-items: center; gap: 6px; background: #f0f0f1; border: 1px solid #dcdcde; border-radius: 16px; padding: 4px 8px; }
			.clv-chip button { border: 0; background: transparent; cursor: pointer; color: #b32d2e; font-weight: 700; padding: 0; }
			.clv-grid-3 { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 10px; }
			.clv-preview-inline { border: 1px solid #dcdcde; border-radius: 8px; padding: 10px; background: #fff; margin-top: 10px; }
			.clv-preview-inline .clv-preview-grid { display: grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap: 8px; }
			.clv-preview-inline .clv-preview-card { border: 1px solid #e2e4e7; border-radius: 6px; padding: 8px; background: #f6f7f7; min-height: 60px; }
			.clv-preview-inline .clv-preview-card::before { content: ""; display: block; height: 16px; border-radius: 4px; background: #e6e8eb; margin-bottom: 6px; }
			.clv-preview-inline .clv-preview-card::after { content: ""; display: block; height: 6px; border-radius: 4px; width: 70%; background: #d6d9dd; }
			.clv-preview-inline[data-preset="2"] .clv-preview-card { border-radius: 12px; box-shadow: 0 1px 8px rgba(0,0,0,.05); }
			.clv-preview-inline[data-preset="3"] .clv-preview-card { background: #1f2937; border-color: #111827; }
			.clv-preview-inline[data-preset="3"] .clv-preview-card::after { background: #9ca3af; }
			.clv-preview-inline[data-preset="4"] .clv-preview-card { background: #ffffff; border-color: #e5e7eb; }
			@media (max-width: 782px) { .clv-preview-inline .clv-preview-grid { grid-template-columns: repeat(2,minmax(0,1fr)); } .clv-grid-3 { grid-template-columns: 1fr; } }
		</style>

		<div class="clv-field">
			<label for="clv-preset-select"><?php esc_html_e( 'Preset / Design', 'clevers-product-carousel' ); ?></label>
			<select id="clv-preset-select" name="clv[preset]">
				<option value="1" <?php selected( $preset, 1 ); ?>>Preset 1</option>
				<option value="2" <?php selected( $preset, 2 ); ?>>Preset 2</option>
				<option value="3" <?php selected( $preset, 3 ); ?>>Preset 3</option>
				<option value="4" <?php selected( $preset, 4 ); ?>>Preset 4</option>
			</select>
		</div>

		<div class="clv-preview-inline" id="clv-preset-preview-inline" data-preset="<?php echo esc_attr( $preset ); ?>">
			<p class="description" style="margin-top:0;">
				<?php esc_html_e( 'Basic preset preview (layout style approximation).', 'clevers-product-carousel' ); ?>
			</p>
			<div class="clv-preview-grid">
				<div class="clv-preview-card"></div>
				<div class="clv-preview-card"></div>
				<div class="clv-preview-card"></div>
				<div class="clv-preview-card"></div>
			</div>
		</div>

		<div class="clv-field">
			<label for="clv-limit"><?php esc_html_e( 'Limit', 'clevers-product-carousel' ); ?></label>
			<input id="clv-limit" type="number" min="1" max="48" name="clv[limit]" value="<?php echo esc_attr( $limit ); ?>"/>
		</div>

		<div class="clv-fieldset">
			<legend><?php esc_html_e( 'Sorting', 'clevers-product-carousel' ); ?></legend>
			<div class="clv-field">
				<label for="clv-orderby"><?php esc_html_e( 'Order by', 'clevers-product-carousel' ); ?></label>
				<select id="clv-orderby" name="clv[orderby]">
					<?php foreach ( $this->get_orderby_options() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $orderby, $key ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="clv-field">
				<label for="clv-order"><?php esc_html_e( 'Order', 'clevers-product-carousel' ); ?></label>
				<select id="clv-order" name="clv[order]">
					<?php foreach ( $this->get_order_options() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $order, $key ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="clv-field">
			<label><?php esc_html_e( 'Product Categories', 'clevers-product-carousel' ); ?></label>
			<?php if ( is_wp_error( $terms ) || empty( $terms ) ) : ?>
				<p class="description"><?php esc_html_e( 'No product categories found yet.', 'clevers-product-carousel' ); ?></p>
			<?php else : ?>
				<div class="clv-categories-list">
					<?php foreach ( $terms as $term ) : ?>
						<label for="<?php echo esc_attr( 'clv-cat-' . $term->term_id ); ?>">
							<input
								type="checkbox"
								id="<?php echo esc_attr( 'clv-cat-' . $term->term_id ); ?>"
								name="clv[categories][]"
								value="<?php echo esc_attr( $term->slug ); ?>"
								<?php checked( in_array( $term->slug, $categories, true ) ); ?>
							/>
							<span><?php echo esc_html( $term->name ); ?></span>
							<code><?php echo esc_html( $term->slug ); ?></code>
						</label>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<p class="description"><?php esc_html_e( 'Leave empty to include products from all categories.', 'clevers-product-carousel' ); ?></p>
		</div>

		<div class="clv-fieldset">
			<legend><?php esc_html_e( 'Filters', 'clevers-product-carousel' ); ?></legend>
			<div class="clv-field">
				<label class="clv-inline-check">
					<input type="checkbox" name="clv[manual_products_enabled]" <?php checked( $use_manual ); ?> />
					<?php esc_html_e( 'Use manual product selection (IDs)', 'clevers-product-carousel' ); ?>
				</label>
				<textarea
					id="clv-manual-product-ids-csv"
					name="clv[manual_product_ids_csv]"
					rows="3"
					class="widefat"
					style="display:none;"
					placeholder="<?php echo esc_attr__( 'Example: 12, 54, 99', 'clevers-product-carousel' ); ?>"
				><?php echo esc_textarea( implode( ', ', $manual_ids ) ); ?></textarea>
				<div class="clv-product-picker" id="clv-product-picker" data-nonce="<?php echo esc_attr( wp_create_nonce( 'clv_search_products' ) ); ?>">
					<label for="clv-product-search"><?php esc_html_e( 'Search products to add', 'clevers-product-carousel' ); ?></label>
					<input type="search" id="clv-product-search" class="widefat" placeholder="<?php echo esc_attr__( 'Type product name...', 'clevers-product-carousel' ); ?>" />
					<div id="clv-product-search-results" class="clv-product-picker-results" hidden></div>
					<div id="clv-selected-products" class="clv-selected-products"></div>
				</div>
				<p class="description">
					<?php esc_html_e( 'When enabled, the carousel uses these WooCommerce product IDs (in this order) and ignores automatic product filters.', 'clevers-product-carousel' ); ?>
				</p>
			</div>
			<label class="clv-inline-check">
				<input type="checkbox" name="clv[on_sale]" <?php checked( $on_sale ); ?> />
				<?php esc_html_e( 'On Sale', 'clevers-product-carousel' ); ?>
			</label>
			<label class="clv-inline-check">
				<input type="checkbox" name="clv[on_featured]" <?php checked( $on_featured ); ?> />
				<?php esc_html_e( 'Featured', 'clevers-product-carousel' ); ?>
			</label>
			<label class="clv-inline-check">
				<input type="checkbox" name="clv[instock_only]" <?php checked( $instock_only ); ?> />
				<?php esc_html_e( 'In Stock Only', 'clevers-product-carousel' ); ?>
			</label>
			<p class="description" style="margin-bottom:0;">
				<?php esc_html_e( 'If On Sale and Featured are both enabled, products must match both filters by default.', 'clevers-product-carousel' ); ?>
			</p>
		</div>

		<hr/>

		<h3><?php esc_html_e( 'Carousel Options', 'clevers-product-carousel' ); ?></h3>

		<div class="clv-fieldset">
			<legend><?php esc_html_e( 'Responsive Slides', 'clevers-product-carousel' ); ?></legend>
			<div class="clv-grid-3">
				<div class="clv-field">
					<label for="clv-slides-desktop"><?php esc_html_e( 'Desktop (>=1024px)', 'clevers-product-carousel' ); ?></label>
					<input id="clv-slides-desktop" type="number" min="1" max="8" name="clv[slidesToShow]" value="<?php echo esc_attr( $slides ); ?>"/>
				</div>
				<div class="clv-field">
					<label for="clv-slides-tablet"><?php esc_html_e( 'Tablet (<1024px)', 'clevers-product-carousel' ); ?></label>
					<input id="clv-slides-tablet" type="number" min="1" max="8" name="clv[slidesToShowTablet]" value="<?php echo esc_attr( $slides_tablet ); ?>"/>
				</div>
				<div class="clv-field">
					<label for="clv-slides-mobile"><?php esc_html_e( 'Mobile (<768px)', 'clevers-product-carousel' ); ?></label>
					<input id="clv-slides-mobile" type="number" min="1" max="8" name="clv[slidesToShowMobile]" value="<?php echo esc_attr( $slides_mobile ); ?>"/>
				</div>
			</div>
		</div>

		<div class="clv-field">
			<label class="clv-inline-check">
				<input type="checkbox" name="clv[autoplay]" <?php checked( $autoplay ); ?> />
				<?php esc_html_e( 'Autoplay', 'clevers-product-carousel' ); ?>
			</label>
		</div>

		<div class="clv-field">
			<label for="clv-autoplay-ms"><?php esc_html_e( 'Autoplay Speed (ms)', 'clevers-product-carousel' ); ?></label>
			<input id="clv-autoplay-ms" type="number" min="500" step="100" max="60000" name="clv[autoplayMs]" value="<?php echo esc_attr( $autoplay_ms ); ?>"/>
		</div>

		<div class="clv-field">
			<label class="clv-inline-check">
				<input type="checkbox" name="clv[dots]" <?php checked( $dots ); ?> />
				<?php esc_html_e( 'Dots', 'clevers-product-carousel' ); ?>
			</label>
			<label class="clv-inline-check">
				<input type="checkbox" name="clv[arrows]" <?php checked( $arrows ); ?> />
				<?php esc_html_e( 'Arrows', 'clevers-product-carousel' ); ?>
			</label>
		</div>

		<div class="clv-fieldset">
			<legend><?php esc_html_e( 'Builder Compatibility', 'clevers-product-carousel' ); ?></legend>
			<label class="clv-inline-check">
				<input type="checkbox" name="clv[builder_compat_mode]" <?php checked( $builder_compat_mode ); ?> />
				<?php esc_html_e( 'Enable builder compatibility mode (Brizy/Elementor/etc.)', 'clevers-product-carousel' ); ?>
			</label>
			<div class="clv-field">
				<label for="clv-builder-init-delay"><?php esc_html_e( 'Extra init delay (ms)', 'clevers-product-carousel' ); ?></label>
				<input
					id="clv-builder-init-delay"
					type="number"
					min="0"
					max="5000"
					step="50"
					name="clv[builder_init_delay_ms]"
					value="<?php echo esc_attr( $builder_init_delay_ms ); ?>"
				/>
				<p class="description"><?php esc_html_e( 'Useful when the builder renders widgets asynchronously after page load.', 'clevers-product-carousel' ); ?></p>
			</div>
			<label class="clv-inline-check">
				<input type="checkbox" name="clv[builder_disable_center_mode]" <?php checked( $builder_disable_center ); ?> />
				<?php esc_html_e( 'Disable center mode in builders (helps with some editor layouts)', 'clevers-product-carousel' ); ?>
			</label>
		</div>

		<hr/>

		<h3><?php esc_html_e( 'Colors', 'clevers-product-carousel' ); ?></h3>

		<div class="clv-field"><label><?php esc_html_e( 'Primary', 'clevers-product-carousel' ); ?></label>
			<input type="color" name="clv[color_primary]" value="<?php echo esc_attr( $color_primary ); ?>">
		</div>
		<div class="clv-field"><label><?php esc_html_e( 'Primary (Hover)', 'clevers-product-carousel' ); ?></label>
			<input type="color" name="clv[color_primary2]" value="<?php echo esc_attr( $color_primary2 ); ?>">
		</div>
		<div class="clv-field"><label><?php esc_html_e( 'Secondary', 'clevers-product-carousel' ); ?></label>
			<input type="color" name="clv[color_secondary]" value="<?php echo esc_attr( $color_secondary ); ?>">
		</div>
		<div class="clv-field"><label><?php esc_html_e( 'Accent', 'clevers-product-carousel' ); ?></label>
			<input type="color" name="clv[color_accent]" value="<?php echo esc_attr( $color_accent ); ?>">
		</div>
		<div class="clv-field"><label><?php esc_html_e( 'Bubble Background', 'clevers-product-carousel' ); ?></label>
			<input type="color" name="clv[bubble_background]" value="<?php echo esc_attr( $bubble_background ); ?>">
		</div>
		<div class="clv-field"><label><?php esc_html_e( 'Bubble Text', 'clevers-product-carousel' ); ?></label>
			<input type="color" name="clv[bubble_text]" value="<?php echo esc_attr( $bubble_text ); ?>">
		</div>
		<div class="clv-field"><label><?php esc_html_e( 'Button Background', 'clevers-product-carousel' ); ?></label>
			<input type="color" name="clv[button_background]" value="<?php echo esc_attr( $button_background ); ?>">
		</div>
		<div class="clv-field">
			<label><?php esc_html_e( 'Button Text', 'clevers-product-carousel' ); ?></label>
			<input type="color" name="clv[button_text]" value="<?php echo esc_attr( $button_text_color_value ); ?>">
			<label class="clv-inline-check" style="margin-top:6px;">
				<input type="checkbox" name="clv[button_text_transparent]" <?php checked( 'transparent', $button_text ); ?> />
				<?php esc_html_e( 'Transparent', 'clevers-product-carousel' ); ?>
			</label>
		</div>
		<div class="clv-field"><label><?php esc_html_e( 'Text', 'clevers-product-carousel' ); ?></label>
			<input type="color" name="clv[color_text]" value="<?php echo esc_attr( $color_text ); ?>">
		</div>
		<div class="clv-field"><label><?php esc_html_e( 'Card Background', 'clevers-product-carousel' ); ?></label>
			<input type="color" name="clv[color_card_bg]" value="<?php echo esc_attr( $color_card_bg ); ?>">
		</div>
		<div class="clv-field"><label><?php esc_html_e( 'Border', 'clevers-product-carousel' ); ?></label>
			<input type="color" name="clv[color_border]" value="<?php echo esc_attr( $color_border ); ?>">
		</div>

		<script>
			(function() {
				var presetSelect = document.getElementById('clv-preset-select');
				var productPicker = document.getElementById('clv-product-picker');
				if (!presetSelect) {
					return;
				}
				function syncPreview() {
					var preset = String(presetSelect.value || '1');
					var inlinePreview = document.getElementById('clv-preset-preview-inline');
					var sidePreview = document.getElementById('clv-preset-preview-side');
					if (inlinePreview) inlinePreview.setAttribute('data-preset', preset);
					if (sidePreview) sidePreview.setAttribute('data-preset', preset);
				}
				presetSelect.addEventListener('change', syncPreview);
				syncPreview();

				if (!productPicker) {
					return;
				}

				var searchInput = document.getElementById('clv-product-search');
				var resultsBox = document.getElementById('clv-product-search-results');
				var selectedBox = document.getElementById('clv-selected-products');
				var csvField = document.getElementById('clv-manual-product-ids-csv');
				var nonce = productPicker.getAttribute('data-nonce');
				var selected = [];
				var debounceTimer = null;

				function syncCsv() {
					if (csvField) csvField.value = selected.join(', ');
				}

				function renderSelected() {
					if (!selectedBox) return;
					selectedBox.innerHTML = '';
					selected.forEach(function(id) {
						var chip = document.createElement('span');
						chip.className = 'clv-chip';
						chip.innerHTML = '<span>#' + id + '</span>';
						var remove = document.createElement('button');
						remove.type = 'button';
						remove.setAttribute('aria-label', 'Remove #' + id);
						remove.textContent = '×';
						remove.addEventListener('click', function() {
							selected = selected.filter(function(v) { return v !== id; });
							renderSelected();
							syncCsv();
						});
						chip.appendChild(remove);
						selectedBox.appendChild(chip);
					});
				}

				function addSelected(id) {
					id = parseInt(id, 10);
					if (!id || selected.indexOf(id) !== -1) return;
					selected.push(id);
					renderSelected();
					syncCsv();
				}

				if (csvField && csvField.value.trim()) {
					csvField.value.split(',').forEach(function(v) {
						var id = parseInt(v.trim(), 10);
						if (id) addSelected(id);
					});
				}

				function renderResults(items) {
					if (!resultsBox) return;
					resultsBox.innerHTML = '';
					if (!items.length) {
						resultsBox.hidden = true;
						return;
					}
					items.forEach(function(item) {
						var btn = document.createElement('button');
						btn.type = 'button';
						btn.textContent = item.label + ' (#' + item.id + ')';
						btn.addEventListener('click', function() {
							addSelected(item.id);
							searchInput.value = '';
							resultsBox.hidden = true;
							resultsBox.innerHTML = '';
						});
						resultsBox.appendChild(btn);
					});
					resultsBox.hidden = false;
				}

				function searchProducts(term) {
					if (!term || term.length < 2) {
						renderResults([]);
						return;
					}
					var url = ajaxurl + '?action=clv_search_products&_ajax_nonce=' + encodeURIComponent(nonce) + '&q=' + encodeURIComponent(term);
					fetch(url, { credentials: 'same-origin' })
						.then(function(r) { return r.json(); })
						.then(function(json) {
							if (!json || !json.success || !Array.isArray(json.data)) {
								renderResults([]);
								return;
							}
							renderResults(json.data);
						})
						.catch(function() { renderResults([]); });
				}

				if (searchInput) {
					searchInput.addEventListener('input', function() {
						var term = searchInput.value.trim();
						window.clearTimeout(debounceTimer);
						debounceTimer = window.setTimeout(function() {
							searchProducts(term);
						}, 220);
					});
				}
				renderSelected();
			})();
		</script>
		<?php
	}

	public function save_meta_box( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( $post->post_type !== CLV_SLUG ) {
			return;
		}

		$nonce = filter_input( INPUT_POST, 'clv_carousel_nonce', FILTER_DEFAULT );
		if ( empty( $nonce ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $nonce ) );
		if ( ! wp_verify_nonce( $nonce, 'clv_save_carousel' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$import_requested = isset( $_POST['clv_apply_import_json'] );
		if ( $import_requested ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON payload: decoded + structurally validated in extract_import_settings_from_raw() / normalize_imported_settings(). wp_unslash() strips slashes added by WP magic quotes.
			$raw      = isset( $_POST['clv_import_json'] ) ? wp_unslash( $_POST['clv_import_json'] ) : '';
			$settings = $this->extract_import_settings_from_raw( $raw );

			if ( is_array( $settings ) ) {
				$normalized = $this->normalize_imported_settings( $settings );
				update_post_meta( $post_id, '_clv_settings', $normalized );
				$ver = (int) get_post_meta( $post_id, '_clv_cache_version', true );
				update_post_meta( $post_id, '_clv_cache_version', $ver + 1 );
				$this->append_notice_to_redirect( 'imported' );
			} elseif ( '' === trim( (string) $raw ) ) {
				$this->append_notice_to_redirect( 'import_empty' );
			} else {
				$this->append_notice_to_redirect( 'import_invalid' );
			}

			return;
		}

		$in = filter_input( INPUT_POST, 'clv', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! is_array( $in ) ) {
			$in = array();
		}
		$in = wp_unslash( $in );

		$allowed_orderby = array_keys( $this->get_orderby_options() );
		$allowed_order   = array_keys( $this->get_order_options() );

		$out               = array();
		$out['preset']     = max( 1, min( 4, (int) ( $in['preset'] ?? 1 ) ) );
		$out['limit']      = max( 1, min( 48, (int) ( $in['limit'] ?? 8 ) ) );
		$out['orderby']    = in_array( ( $in['orderby'] ?? 'date' ), $allowed_orderby, true ) ? $in['orderby'] : 'date';
		$out['order']      = in_array( ( $in['order'] ?? 'DESC' ), $allowed_order, true ) ? $in['order'] : 'DESC';
		$out['categories'] = $this->sanitize_categories_input( $in );

		$out['on_sale']      = ! empty( $in['on_sale'] );
		$out['on_featured']  = ! empty( $in['on_featured'] );
		$out['instock_only'] = ! empty( $in['instock_only'] );
		$out['manual_products_enabled'] = ! empty( $in['manual_products_enabled'] );
		$out['manual_product_ids']      = $this->sanitize_product_ids_csv( $in['manual_product_ids_csv'] ?? '' );

		$out['slidesToShow'] = max( 1, min( 8, (int) ( $in['slidesToShow'] ?? 4 ) ) );
		$out['slidesToShowTablet'] = max( 1, min( 8, (int) ( $in['slidesToShowTablet'] ?? min( 2, $out['slidesToShow'] ) ) ) );
		$out['slidesToShowMobile'] = max( 1, min( 8, (int) ( $in['slidesToShowMobile'] ?? 1 ) ) );
		$out['autoplay']     = ! empty( $in['autoplay'] );
		$out['autoplayMs']   = max( 500, min( 60000, (int) ( $in['autoplayMs'] ?? 3000 ) ) );
		$out['dots']         = ! empty( $in['dots'] );
		$out['arrows']       = ! empty( $in['arrows'] );
		$out['builder_compat_mode']       = ! empty( $in['builder_compat_mode'] );
		$out['builder_init_delay_ms']     = max( 0, min( 5000, (int) ( $in['builder_init_delay_ms'] ?? 0 ) ) );
		$out['builder_disable_center_mode'] = ! empty( $in['builder_disable_center_mode'] );

		$color_fields = array(
			'color_primary',
			'color_primary2',
			'color_secondary',
			'bubble_background',
			'bubble_text',
			'color_accent',
			'color_text',
			'color_card_bg',
			'color_border',
			'button_background',
			'button_text',
		);

		foreach ( $color_fields as $field ) {
			$out[ $field ] = $this->sanitize_color_value( $in[ $field ] ?? '' );
		}

		if ( ! empty( $in['button_text_transparent'] ) ) {
			$out['button_text'] = 'transparent';
		}

		update_post_meta( $post_id, '_clv_settings', $out );

		$ver = (int) get_post_meta( $post_id, '_clv_cache_version', true );
		update_post_meta( $post_id, '_clv_cache_version', $ver + 1 );
	}

	private function get_orderby_options() {
		return array(
			'date'          => __( 'Date', 'clevers-product-carousel' ),
			'modified'      => __( 'Modified date', 'clevers-product-carousel' ),
			'title'         => __( 'Title', 'clevers-product-carousel' ),
			'menu_order'    => __( 'Menu order', 'clevers-product-carousel' ),
			'rand'          => __( 'Random', 'clevers-product-carousel' ),
			'price'         => __( 'Price', 'clevers-product-carousel' ),
			'popularity'    => __( 'Popularity', 'clevers-product-carousel' ),
			'rating'        => __( 'Rating', 'clevers-product-carousel' ),
			'date_modified' => __( 'Date modified (WC)', 'clevers-product-carousel' ),
		);
	}

	private function get_order_options() {
		return array(
			'DESC' => __( 'Descending', 'clevers-product-carousel' ),
			'ASC'  => __( 'Ascending', 'clevers-product-carousel' ),
		);
	}

	private function sanitize_categories_input( $in ) {
		if ( ! empty( $in['categories'] ) && is_array( $in['categories'] ) ) {
			return array_values(
				array_filter(
					array_map( 'sanitize_title', $in['categories'] )
				)
			);
		}

		return array_values(
			array_filter(
				array_map(
					'sanitize_title',
					array_map(
						'trim',
						explode( ',', (string) ( $in['categories_csv'] ?? '' ) )
					)
				)
			)
		);
	}

	private function sanitize_product_ids_csv( $csv ) {
		$ids = array_map(
			'intval',
			array_filter(
				array_map( 'trim', explode( ',', (string) $csv ) ),
				'strlen'
			)
		);

		$ids = array_values( array_unique( array_filter( $ids ) ) );

		return $ids;
	}

	private function sanitize_color_value( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}

		if ( 'transparent' === strtolower( $value ) ) {
			return 'transparent';
		}

		$hex = sanitize_hex_color( $value );
		return $hex ? $hex : '';
	}

	public function add_row_actions( $actions, $post ) {
		if ( ! ( $post instanceof WP_Post ) || CLV_SLUG !== $post->post_type ) {
			return $actions;
		}

		$actions['clv_duplicate'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $this->get_duplicate_url( $post->ID ) ),
			esc_html__( 'Duplicate', 'clevers-product-carousel' )
		);
		$actions['clv_export'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $this->get_export_url( $post->ID ) ),
			esc_html__( 'Export JSON', 'clevers-product-carousel' )
		);

		return $actions;
	}

	public function handle_duplicate_carousel() {
		$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
		$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! $post_id || ! wp_verify_nonce( $nonce, 'clv_duplicate_carousel_' . $post_id ) ) {
			wp_die( esc_html__( 'Invalid duplicate request.', 'clevers-product-carousel' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to duplicate this carousel.', 'clevers-product-carousel' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || CLV_SLUG !== $post->post_type ) {
			wp_die( esc_html__( 'Carousel not found.', 'clevers-product-carousel' ) );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'   => CLV_SLUG,
				'post_status' => 'draft',
				'post_title'  => sprintf(
					/* translators: %s original title */
					__( '%s (Copy)', 'clevers-product-carousel' ),
					$post->post_title ?: __( 'Untitled Carousel', 'clevers-product-carousel' )
				),
			),
			true
		);

		if ( is_wp_error( $new_id ) ) {
			$this->redirect_with_notice( $post_id, 'duplicate_error' );
		}

		$settings = clevers_product_carousel_get_carousel_meta( $post_id );
		update_post_meta( $new_id, '_clv_settings', $settings );
		update_post_meta( $new_id, '_clv_cache_version', 0 );

		wp_safe_redirect(
			add_query_arg(
				array(
					'post'   => (int) $new_id,
					'action' => 'edit',
					'clv_notice' => 'duplicated',
				),
				admin_url( 'post.php' )
			)
		);
		exit;
	}

	public function handle_export_carousel_json() {
		$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
		$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! $post_id || ! wp_verify_nonce( $nonce, 'clv_export_carousel_json_' . $post_id ) ) {
			wp_die( esc_html__( 'Invalid export request.', 'clevers-product-carousel' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to export this carousel.', 'clevers-product-carousel' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || CLV_SLUG !== $post->post_type ) {
			wp_die( esc_html__( 'Carousel not found.', 'clevers-product-carousel' ) );
		}

		$payload = array(
			'plugin'    => 'clevers-product-carousel',
			'version'   => '1.2.1',
			'exported'  => gmdate( 'c' ),
			'carousel'  => array(
				'title'    => $post->post_title,
				'settings' => clevers_product_carousel_get_carousel_meta( $post_id ),
			),
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		header( 'Content-Disposition: attachment; filename=clevers-carousel-' . (int) $post_id . '.json' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	public function handle_import_carousel_json() {
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;

		if ( ! $post_id ) {
			wp_die( esc_html__( 'Missing carousel ID.', 'clevers-product-carousel' ) );
		}

		check_admin_referer( 'clv_import_carousel_json_' . $post_id );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to import settings into this carousel.', 'clevers-product-carousel' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || CLV_SLUG !== $post->post_type ) {
			wp_die( esc_html__( 'Carousel not found.', 'clevers-product-carousel' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON payload: decoded + structurally validated in extract_import_settings_from_raw() / normalize_imported_settings(). wp_unslash() strips slashes added by WP magic quotes.
		$raw = isset( $_POST['clv_import_json'] ) ? wp_unslash( $_POST['clv_import_json'] ) : '';
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			$this->redirect_with_notice( $post_id, 'import_empty' );
		}

		$settings = $this->extract_import_settings_from_raw( $raw );
		if ( ! is_array( $settings ) ) {
			$this->redirect_with_notice( $post_id, 'import_invalid' );
		}

		$normalized = $this->normalize_imported_settings( $settings );
		update_post_meta( $post_id, '_clv_settings', $normalized );
		$ver = (int) get_post_meta( $post_id, '_clv_cache_version', true );
		update_post_meta( $post_id, '_clv_cache_version', $ver + 1 );

		$this->redirect_with_notice( $post_id, 'imported' );
	}

	public function render_admin_notices() {
		if ( ! is_admin() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || CLV_SLUG !== $screen->post_type ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice from redirect query arg; value is whitelisted against $map before rendering, never persisted or echoed raw. Nonce was verified at the originating handler.
		$notice = isset( $_GET['clv_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['clv_notice'] ) ) : '';
		if ( '' === $notice ) {
			return;
		}

		$map = array(
			'duplicated'       => array( 'success', __( 'Carousel duplicated.', 'clevers-product-carousel' ) ),
			'duplicate_error'  => array( 'error', __( 'Unable to duplicate carousel.', 'clevers-product-carousel' ) ),
			'imported'         => array( 'success', __( 'Carousel settings imported.', 'clevers-product-carousel' ) ),
			'import_empty'     => array( 'warning', __( 'Paste JSON before importing.', 'clevers-product-carousel' ) ),
			'import_invalid'   => array( 'error', __( 'Invalid JSON payload for carousel import.', 'clevers-product-carousel' ) ),
		);

		if ( ! isset( $map[ $notice ] ) ) {
			return;
		}

		list( $type, $message ) = $map[ $notice ];
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $type ),
			esc_html( $message )
		);
	}

	public function ajax_search_products() {
		check_ajax_referer( 'clv_search_products' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error( array( 'message' => 'woocommerce_missing' ), 400 );
		}

		$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$query = trim( $query );
		if ( '' === $query ) {
			wp_send_json_success( array() );
		}

		$posts = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 15,
				's'              => $query,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$results = array();
		foreach ( $posts as $product_post ) {
			$results[] = array(
				'id'    => (int) $product_post->ID,
				'label' => wp_strip_all_tags( get_the_title( $product_post ) ),
			);
		}

		wp_send_json_success( $results );
	}

	private function get_duplicate_url( $post_id ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'  => 'clv_duplicate_carousel',
					'post_id' => (int) $post_id,
				),
				admin_url( 'admin.php' )
			),
			'clv_duplicate_carousel_' . (int) $post_id
		);
	}

	private function get_export_url( $post_id ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'  => 'clv_export_carousel_json',
					'post_id' => (int) $post_id,
				),
				admin_url( 'admin.php' )
			),
			'clv_export_carousel_json_' . (int) $post_id
		);
	}

	private function redirect_with_notice( $post_id, $notice ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'post'       => (int) $post_id,
					'action'     => 'edit',
					'clv_notice' => sanitize_key( $notice ),
				),
				admin_url( 'post.php' )
			)
		);
		exit;
	}

	private function looks_like_settings_array( $data ) {
		$keys = array( 'preset', 'limit', 'slidesToShow', 'autoplay', 'categories', 'color_primary' );
		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $data ) ) {
				return true;
			}
		}

		return false;
	}

	private function extract_import_settings_from_raw( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return null;
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return null;
		}

		if ( isset( $data['carousel']['settings'] ) && is_array( $data['carousel']['settings'] ) ) {
			return $data['carousel']['settings'];
		}

		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			return $data['settings'];
		}

		if ( $this->looks_like_settings_array( $data ) ) {
			return $data;
		}

		return null;
	}

	private function append_notice_to_redirect( $notice ) {
		add_filter(
			'redirect_post_location',
			static function ( $location ) use ( $notice ) {
				return add_query_arg( 'clv_notice', sanitize_key( $notice ), $location );
			}
		);
	}

	private function normalize_imported_settings( array $settings ) {
		$normalized = array();

		$normalized['preset']                  = max( 1, min( 4, (int) ( $settings['preset'] ?? 1 ) ) );
		$normalized['limit']                   = max( 1, min( 48, (int) ( $settings['limit'] ?? 8 ) ) );
		$normalized['orderby']                 = in_array( ( $settings['orderby'] ?? 'date' ), array_keys( $this->get_orderby_options() ), true ) ? $settings['orderby'] : 'date';
		$normalized['order']                   = in_array( ( $settings['order'] ?? 'DESC' ), array_keys( $this->get_order_options() ), true ) ? $settings['order'] : 'DESC';
		$normalized['categories']              = array_values( array_filter( array_map( 'sanitize_title', (array) ( $settings['categories'] ?? array() ) ) ) );
		$normalized['on_sale']                 = ! empty( $settings['on_sale'] );
		$normalized['on_featured']             = ! empty( $settings['on_featured'] );
		$normalized['instock_only']            = ! empty( $settings['instock_only'] );
		$normalized['manual_products_enabled'] = ! empty( $settings['manual_products_enabled'] );
		$normalized['manual_product_ids']      = array_values( array_unique( array_filter( array_map( 'intval', (array) ( $settings['manual_product_ids'] ?? array() ) ) ) ) );
		$normalized['slidesToShow']            = max( 1, min( 8, (int) ( $settings['slidesToShow'] ?? 4 ) ) );
		$normalized['slidesToShowTablet']      = max( 1, min( 8, (int) ( $settings['slidesToShowTablet'] ?? min( 2, $normalized['slidesToShow'] ) ) ) );
		$normalized['slidesToShowMobile']      = max( 1, min( 8, (int) ( $settings['slidesToShowMobile'] ?? 1 ) ) );
		$normalized['autoplay']                = ! empty( $settings['autoplay'] );
		$normalized['autoplayMs']              = max( 500, min( 60000, (int) ( $settings['autoplayMs'] ?? 3000 ) ) );
		$normalized['dots']                    = ! empty( $settings['dots'] );
		$normalized['arrows']                  = ! empty( $settings['arrows'] );
		$normalized['builder_compat_mode']     = ! empty( $settings['builder_compat_mode'] );
		$normalized['builder_init_delay_ms']   = max( 0, min( 5000, (int) ( $settings['builder_init_delay_ms'] ?? 0 ) ) );
		$normalized['builder_disable_center_mode'] = ! empty( $settings['builder_disable_center_mode'] );

		$color_fields = array(
			'color_primary',
			'color_primary2',
			'color_secondary',
			'bubble_background',
			'bubble_text',
			'color_accent',
			'color_text',
			'color_card_bg',
			'color_border',
			'button_background',
			'button_text',
		);

		foreach ( $color_fields as $field ) {
			$normalized[ $field ] = $this->sanitize_color_value( $settings[ $field ] ?? '' );
		}

		return $normalized;
	}
}
