<?php
/**
 * Email templates.
 *
 * @package Multi Vendor Marketplace
 *
 * @version 5.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if access directly.

$order_id     = empty( $data['order_id'] ) ? 0 : intval( $data['order_id'] );
$seller_order = wc_get_order( $order_id );

$product_details  = empty( $data['product_details'] ) ? array() : $data['product_details'];
$common_functions = empty( $data['common_functions'] ) ? '' : $data['common_functions'];

$total_payment   = 0;
$shipping_method = $seller_order->get_shipping_method();
$payment_method  = $seller_order->get_payment_method_title();
$fees            = $seller_order->get_fees();
$total_discount  = $seller_order->get_total_discount();
$date_string     = empty( $data['date_string'] ) ? gmdate( 'Y-m-d H:i:s' ) : $data['date_string'];

echo '= ' . wp_kses_post( utf8_decode( $email_heading ) ) . " =\n\n";

$result = utf8_decode( /* translators: %1$d: Order number, %2$s: Billing first name. */ sprintf( esc_html__( 'Alas. Just to let you know — order #%1$d belonging to %2$s has been cancelled:', 'wk-marketplace' ), esc_html( $seller_order->get_order_number() ), esc_html( $seller_order->get_formatted_billing_full_name() ) ) ) . "\n\n" . 'Order #' . $seller_order->get_ID() . ' (' . $date_string . ') ' . "\n\n";

foreach ( $product_details as $product_id => $details ) {
	$product  = new WC_Product( $product_id );
	$detail_c = 0;
	if ( count( $details ) > 0 ) {
		$detail_c = count( $details );
	}
	for ( $i = 0; $i < $detail_c; ++ $i ) {
		$total_payment = floatval( $total_payment ) + floatval( $details[ $i ]['product_total_price'] ) + floatval( $seller_order->get_total_shipping() );
		if ( 0 === intval( $details[ $i ]['variable_id'] ) ) {
			$result .= utf8_decode( $details[ $i ]['product_name'] ) . utf8_decode( esc_html__( 'SKU: ', 'wk-marketplace' ) ) . $common_functions->wkmp_get_sku( $product ) . ' X ' . $details[ $i ]['qty'] . ' = ' . $seller_order->get_currency() . ' ' . $details[ $i ]['product_total_price'] . "\n\n";
		} else {
			$attribute = $product->get_attributes();

			$attribute_name = '';
			foreach ( $attribute as $key => $value ) {
				$attribute_name = $value['name'];
			}
			$result .= utf8_decode( $details[ $i ]['product_name'] ) . ' (' . utf8_decode( esc_html__( 'SKU: ', 'wk-marketplace' ) ) . $common_functions->wkmp_get_sku( $product ) . ' )';
			if ( ! empty( $details[ $i ]['meta_data'] ) ) {
				foreach ( $details[ $i ]['meta_data'] as $m_data ) {
					$result .= '(' . wc_attribute_label( $m_data['key'] ) . ' : ' . strtoupper( $m_data['value'] ) . ')';
				}
			}

			$result .= ' X ' . $details[ $i ]['qty'] . ' = ' . $seller_order->get_currency() . ' ' . $details[ $i ]['product_total_price'] . "\n\n";
		}
	}
}

if ( ! empty( $total_discount ) ) {
	$total_payment -= $total_discount;
	$result        .= utf8_decode( esc_html__( 'Discount', 'wk-marketplace' ) ) . ' : -' . wc_price( $total_discount, array( 'currency' => $seller_order->get_currency() ) ) . "\n\n";
}

if ( ! empty( $shipping_method ) ) :
	$result .= utf8_decode( esc_html__( 'Shipping', 'wk-marketplace' ) ) . ' : ' . wc_price( ( $seller_order->get_total_shipping() ? $seller_order->get_total_shipping() : 0 ), array( 'currency' => $seller_order->get_currency() ) ) . "\n\n";
endif;

$total_fee_amount = 0;

if ( ! empty( $fees ) ) {
	foreach ( $fees as $key => $fee ) {
		$fee_name   = $fee->get_data()['name'];
		$fee_amount = floatval( $fee->get_data()['total'] );

		$total_fee_amount += $fee_amount;

		$result .= utf8_decode( $fee_name ) . ' : ' . wc_price( $fee_amount, array( 'currency' => $seller_order->get_currency() ) ) . "\n\n";
	}
}

$total_payment += $total_fee_amount;

if ( ! empty( $payment_method ) ) :
	$result .= utf8_decode( esc_html__( 'Payment Method', 'wk-marketplace' ) ) . ' : ' . $payment_method . "\n\n";
endif;

$result .= utf8_decode( esc_html__( 'Total', 'wk-marketplace' ) ) . ' : ' . wc_price( $total_payment, array( 'currency' => $seller_order->get_currency() ) ) . "\n\n";

$text_align = is_rtl() ? 'right' : 'left';

$result .= utf8_decode( esc_html__( 'Billing address', 'wk-marketplace' ) ) . ' : ' . "\n\n";

foreach ( $seller_order->get_address( 'billing' ) as $add ) {
	if ( $add ) {
		$result .= utf8_decode( $add ) . "\n";
	}
}
if ( ! wc_ship_to_billing_address_only() && $seller_order->needs_shipping_address() ) :
	$shipping = '';
	if ( $seller_order->get_formatted_shipping_address() ) :
		$shipping = utf8_decode( $seller_order->get_formatted_shipping_address() );
	endif;

	if ( ! empty( $shiping ) ) {
		$result .= utf8_decode( esc_html__( 'Shipping address', 'wk-marketplace' ) ) . ' : ' . "\n\n";
		foreach ( $seller_order->get_address( 'billing' ) as $add ) {
			if ( $add ) {
				$result .= utf8_decode( $add ) . "\n";
			}
		}
	}
endif;

echo wp_kses_post( $result );

do_action( 'woocommerce_email_footer', $email );
