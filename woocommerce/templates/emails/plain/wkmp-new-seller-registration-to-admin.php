<?php
/**
 * Email templates.
 *
 * @package Multi Vendor Marketplace
 *
 * @version 5.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if access directly.

$msg          = wp_sprintf( /* translators: %s: Blog name. */ esc_html__( 'New Seller registration on %s:', 'wk-marketplace' ), get_option( 'blogname' ) ) . "\n\n\r\n\r\n\n\n";
$username     = utf8_decode( esc_html__( 'Username :- ', 'wk-marketplace' ) ) . $data['user_name'];
$seller_email = utf8_decode( esc_html__( 'User email :- ', 'wk-marketplace' ) ) . $data['user_email'];
$shop_url     = utf8_decode( esc_html__( 'Seller Shop URL :- ', 'wk-marketplace' ) ) . $data['shop_url'];

$footer_text = apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );

echo '= ' . wp_kses_post( utf8_decode( $email_heading ) ) . " =\n\n";
echo wp_sprintf( /* translators: %s: User Name. */ esc_html__( 'Hi %s,', 'wk-marketplace' ), wp_kses_post( utf8_decode( $data['user_name'] ) ) ) . "\n\n";

echo wp_kses_post( $msg ) . "\n";
echo wp_kses_post( $username ) . "\n";
echo wp_kses_post( $seller_email ) . "\n";
echo wp_kses_post( $shop_url ) . "\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo wp_kses_post( $footer_text );
