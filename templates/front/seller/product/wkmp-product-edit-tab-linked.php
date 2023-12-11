<?php
/**
 * Seller product edit Linked Product tab.
 *
 * @package Multi Vendor Marketplace
 *
 * @version 5.2.0
 */
defined( 'ABSPATH' ) || exit; // Exit if access directly.

?>
<div class="wkmp_container" id="linkedtabwk">
	<?php
	if ( $product->is_type( 'grouped' ) ) {
		include __DIR__ . '/wkmp-product-linked.php';
	}
	?>
	<div class="options_group wkmp_profile_input">
		<p class="form-field">
			<label for="upsell_ids"><?php esc_html_e( 'Upsells', 'wk-marketplace' ); ?></label>
			<select class="wc-product-search" multiple="multiple" style="width: 50%;" id="upsell_ids" name="upsell_ids[]" data-placeholder="<?php esc_attr_e( 'Search&hellip;', 'wk-marketplace' ); ?>">
				<?php
				$product_ids = $product->get_upsell_ids( 'edit' );
				foreach ( $product_array as $key => $value ) {
					$item = wc_get_product( $value->ID );
					if ( is_object( $item ) && intval( $wk_pro_id ) !== intval( $value->ID ) ) {
						?>
						<option value="<?php echo esc_attr( $value->ID ); ?>" <?php echo in_array( intval( $value->ID ), $product_ids, true ) ? 'selected' : ''; ?>> <?php echo wp_kses_post( $item->get_formatted_name() ); ?></option>
					<?php } ?>
				<?php } ?>
			</select>
		</p>
		<?php if ( ! $product->is_type( 'external' ) && ! $product->is_type( 'grouped' ) ) { ?>
			<p class="form-field hide_if_grouped hide_if_external">
				<label for="crosssell_ids"><?php esc_html_e( 'Cross-sells', 'wk-marketplace' ); ?></label>
				<select class="wc-product-search" multiple="multiple" style="width: 50%;" id="crosssell_ids" name="crosssell_ids[]" data-placeholder="<?php esc_attr_e( 'Search&hellip;', 'wk-marketplace' ); ?>">
					<?php
					$product_ids = $product->get_cross_sell_ids( 'edit' );
					foreach ( $product_array as $key => $value ) {
						$item = wc_get_product( $value->ID );
						if ( is_object( $item ) && intval( $wk_pro_id ) !== intval( $value->ID ) ) {
							?>
							<option value="<?php echo esc_attr( $value->ID ); ?>" <?php echo ( in_array( intval( $value->ID ), $product_ids, true ) ) ? 'selected' : ''; ?>><?php echo wp_kses_post( $item->get_formatted_name() ); ?></option>
						<?php } ?>
					<?php } ?>
				</select>
			</p>
		<?php } ?>
	</div>
</div>
