<?php
/**
 * Email templates.
 *
 * @package Multi Vendor Marketplace
 *
 * @version 5.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if access directly.

$loginurl    = $data['user_login'];
$welcome     = sprintf( utf8_decode( esc_html__( 'Welcome to ', 'wk-marketplace' ) ) . get_option( 'blogname' ) . '!' ) . "\r\n\n";
$msg         = utf8_decode( esc_html__( 'Your account has been created awaiting for admin approval.', 'wk-marketplace' ) ) . "\n\n\r\n\r\n\n\n";
$username    = utf8_decode( esc_html__( 'User :- ', 'wk-marketplace' ) ) . $data['user_email'];
$password    = utf8_decode( esc_html__( 'User Password :- ', 'wk-marketplace' ) ) . $data['user_pass'];
$admin       = get_option( 'admin_email' );
$reference   = utf8_decode( esc_html__( 'If you have any problems, please contact me at', 'wk-marketplace' ) ) . "\r\n\r\n";
$footer_text = apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );

echo '= ' . wp_kses_post( utf8_decode( $email_heading ) ) . " =\n\n";
echo sprintf( /* translators: %s: Login URL. */ esc_html__( 'Hi %s,', 'wk-marketplace' ), esc_html( utf8_decode( $loginurl ) ) ) . "\n\n";

echo wp_kses_post( $welcome ) . "\n";
echo wp_kses_post( $msg ) . "\n";
echo wp_kses_post( $username ) . "\n";
echo wp_kses_post( $password ) . "\n";
echo wp_kses_post( $reference ) . "\n";
echo '<a href="mailto:' . wp_kses_post( $admin ) . '">' . wp_kses_post( $admin ) . '</a>';

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo wp_kses_post( $footer_text );
