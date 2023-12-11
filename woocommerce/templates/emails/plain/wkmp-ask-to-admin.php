<?php
/**
 * Email templates.
 *
 * @package Multi Vendor Marketplace
 *
 * @version 5.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if access directly.

$username      = utf8_decode( esc_html__( 'Email: ', 'wk-marketplace' ) );
$username_mail = utf8_decode( $data['email'] );

$user_obj          = get_user_by( 'email', $username_mail );
$user_name         = $user_obj->first_name ? $user_obj->first_name . ' ' . $user_obj->last_name : esc_html__( 'Someone', 'wk-marketplace' );
$msg               = utf8_decode( $user_name . ' ' . esc_html__( 'asked a query from following account:', 'wk-marketplace' ) );
$admin             = utf8_decode( esc_html__( 'Message: ', 'wk-marketplace' ) );
$admin_message     = utf8_decode( $data['ask'] );
$footer_text       = apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
$reference         = utf8_decode( esc_html__( 'Subject : ', 'wk-marketplace' ) );
$reference_message = utf8_decode( $data['subject'] );

echo '= ' . wp_kses_post( utf8_decode( $email_heading ) ) . " =\n\n";

echo esc_html__( 'Hi', 'wk-marketplace' ) . ', ' . wp_kses_post( $admin_email ) . "\n\n";

echo wp_kses_post( $msg ) . "\n\n";

echo '<strong>' . wp_kses_post( $username ) . '</strong>' . wp_kses_post( $username_mail ) . "\n\n";
echo '<strong>' . wp_kses_post( $reference ) . '</strong>' . wp_kses_post( $reference_message ) . "\n\n";
echo '<strong>' . wp_kses_post( $admin ) . '</strong>' . wp_kses_post( $admin_message ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo wp_kses_post( $footer_text );
