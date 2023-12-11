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
$welcome      = utf8_decode( esc_html__( 'Vendor ', 'wk-marketplace' ) ) . $user_name . utf8_decode( esc_html__( ' has requested to publish ', 'wk-marketplace' ) ) . '<strong>' . $product_name . '</strong> ' . utf8_decode( esc_html__( 'product', 'wk-marketplace' ) ) . ' ! ';
$msg          = utf8_decode( esc_html__( 'Please review the request', 'wk-marketplace' ) );
$review_here  = sprintf( admin_url( 'post.php?post=%s&action=edit' ), $product );
$admin        = get_option( 'admin_email' );
$footer_text  = apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );

echo '= ' . wp_kses_post( utf8_decode( $email_heading ) ) . " =\n\n";

echo wp_kses_post( utf8_decode( esc_html__( 'Hi', 'wk-marketplace' ) ) ) . ', ' . wp_kses_post( $admin ) . "\n\n";

echo wp_kses_post( $welcome );

echo wp_kses_post( $msg ) . "\n\n" . '<a href=' . esc_url( $review_here ) . '>' . wp_kses_post( utf8_decode( esc_html__( 'Here', 'wk-marketplace' ) ) ) . '</a>' . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo wp_kses_post( $footer_text );
