<?php
/**
 * Email templates.
 *
 * @package Multi Vendor Marketplace
 *
 * @version 5.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if access directly.

$_product     = wc_get_product( $product );
$product_name = utf8_decode( $_product->get_name() );
$user_name    = utf8_decode( get_user_meta( $user, 'first_name', true ) );
$msg          = '';
$review_here  = '';
$welcome      = utf8_decode( esc_html__( 'Unfortunately! Your product ( ', 'wk-marketplace' ) ) . '<strong>' . esc_html( $product_name ) . '</strong> ' . utf8_decode( esc_html__( ' ) has been rejected by Admin!', 'wk-marketplace' ) );
$footer_text  = apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );

if ( $status ) {
	$welcome     = utf8_decode( esc_html__( 'Congrats! Your product ( ', 'wk-marketplace' ) ) . '<strong>' . esc_html( $product_name ) . '</strong> ' . utf8_decode( esc_html__( ' ) has been published!', 'wk-marketplace' ) );
	$msg         = utf8_decode( esc_html__( 'Click here to view it ', 'wk-marketplace' ) );
	$review_here = get_the_permalink( $product );
	$review_here = ' <a href=' . $review_here . '>' . utf8_decode( esc_html__( 'Here', 'wk-marketplace' ) ) . '</a>';
}

echo '= ' . wp_kses_post( utf8_decode( $email_heading ) ) . " =\n\n";

echo wp_kses_post( utf8_decode( esc_html__( 'Hi', 'wk-marketplace' ) ) ) . ', ' . wp_kses_post( $user_name ) . "\n\n";

echo wp_kses_post( $welcome );

echo wp_kses_post( $msg ) . "\n\n" . wp_kses_post( $review_here ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo wp_kses_post( $footer_text );
