<?php
/**
 * Front ajax functions.
 *
 * @package Multi Vendor Marketplace
 * @version 5.0.0
 */

namespace WkMarketplace\Includes\Front;

defined( 'ABSPATH' ) || exit; // Exit if access directly.

if ( ! class_exists( 'WKMP_Front_Ajax_Functions' ) ) {
	/**
	 * Front ajax functions.
	 */
	class WKMP_Front_Ajax_Functions {
		/**
		 * WPDB Object.
		 *
		 * @var \QM_DB|\wpdb
		 */
		private $wpdb;

		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Constructor of the class.
		 *
		 * WKMP_Front_Ajax_Functions constructor.
		 */
		public function __construct() {
			global $wpdb;
			$this->wpdb = $wpdb;
		}

		/**
		 * Ensures only one instance of this class is loaded or can be loaded.
		 *
		 * @return object
		 */
		public static function get_instance() {
			if ( ! static::$instance ) {
				static::$instance = new self();
			}
			return static::$instance;
		}

		/**
		 * Check availability of shop url requested.
		 *
		 * @return void
		 */
		public function wkmp_check_for_shop_url() {
			global $wkmarketplace;
			$response = array();

			if ( check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) ) {
				$slug = \WK_Caching::wk_get_request_data( 'shop_slug', array( 'method' => 'post' ) );

				if ( ! empty( $slug ) ) {
					$user = $wkmarketplace->wkmp_get_seller_id_by_shop_address( $slug );

					if ( preg_match( '/[\'^£$%&*()}{@#~?><>,|=_+¬]/', $slug ) ) {
						$response = array(
							'error'   => true,
							'message' => esc_html__( 'You can not use special characters in shop url except HYPHEN(-).', 'wk-marketplace' ),
						);
					} elseif ( ctype_space( $slug ) ) {
						$response = array(
							'error'   => true,
							'message' => esc_html__( 'White space(s) aren\'t allowed in shop url.', 'wk-marketplace' ),
						);
					} elseif ( $user ) {
						$response = array(
							'error'   => true,
							'message' => esc_html__( 'This shop URl already EXISTS, please try different shop url.', 'wk-marketplace' ),
						);
					} else {
						$response = array(
							'error'   => false,
							'message' => esc_html__( 'This shop URl is available, kindly proceed.', 'wk-marketplace' ),
						);
					}
				} else {
					$response = array(
						'error'   => true,
						'message' => esc_html__( 'Shop url not found!', 'wk-marketplace' ),
					);
				}
			} else {
				$response = array(
					'error'   => true,
					'message' => esc_html__( 'Security check failed!', 'wk-marketplace' ),
				);
			}

			wp_send_json( $response );
		}

		/**
		 * Add\update favourite seller.
		 */
		public function wkmp_update_favourite_seller() {
			$json = array();
			if ( ! check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) || ! current_user_can( 'read' ) ) {
				$json['error']   = true;
				$json['message'] = esc_html__( 'Security check failed!', 'wk-marketplace' );
				wp_send_json( $json );
			}

			$args = array(
				'method' => 'post',
				'filter' => 'int',
			);

			$seller_id   = \WK_Caching::wk_get_request_data( 'seller_id', $args );
			$customer_id = \WK_Caching::wk_get_request_data( 'customer_id', $args );

			if ( $seller_id > 0 && $customer_id > 0 ) {
				$sellers = get_user_meta( $customer_id, 'favourite_seller', true );
				$sellers = $sellers ? explode( ',', $sellers ) : array();

				$key = array_search( $seller_id, $sellers, true );
				if ( false !== $key ) {
					unset( $sellers[ $key ] );
					$json['success'] = 'removed';
					$json['message'] = esc_html__( 'Seller removed from your favourite seller list.', 'wk-marketplace' );
				} else {
					$sellers[]       = $seller_id;
					$json['success'] = 'added';
					$json['message'] = esc_html__( 'Seller added to your favourite seller list.', 'wk-marketplace' );
				}

				delete_user_meta( $customer_id, 'favourite_seller' );
				add_user_meta( $customer_id, 'favourite_seller', implode( ',', $sellers ) );
			}
			wp_send_json( $json );
		}

		/**
		 * State by country code.
		 */
		public function wkmp_get_state_by_country_code() {
			$json = array();
			if ( ! check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) && ! check_ajax_referer( 'wkmp-admin-nonce', 'wkmp_nonce', false ) && ! current_user_can( 'manage_options' ) ) {
				$json['error']   = true;
				$json['message'] = esc_html__( 'Security check failed!', 'wk-marketplace' );
				wp_send_json( $json );
			}

			$country_code = \WK_Caching::wk_get_request_data( 'country_code', array( 'method' => 'post' ) );

			if ( ! empty( $country_code ) ) {
				$states = WC()->countries->get_states( $country_code );
				$html   = '';
				if ( $states ) {
					$html .= '<select name="billing_state" id="billing-state" class="form-control">';
					$html .= '<option value="">' . esc_html__( 'Select state', 'wk-marketplace' ) . '</option>';
					foreach ( $states as $key => $state ) {
						$html .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $state ) . '</option>';
					}
					$html .= '</select>';
				} else {
					$html .= '<input id="billing-state" type="text" placeholder="' . esc_attr__( 'State', 'wk-marketplace' ) . '" name="billing_state" class="form-control" />';
				}
				$json['success'] = true;
				$json['html']    = $html;
			}

			wp_send_json( $json );
		}

		/**
		 * Add shipping Cost to zone.
		 */
		public function wkmp_save_shipping_cost() {
			if ( check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) && current_user_can( 'manage_options' ) ) {
				$ship_cost  = \WK_Caching::wk_get_request_data( 'ship_cost', array( 'method' => 'post' ) );
				$final_data = array();
				parse_str( $ship_cost, $final_data );
				$instance_id     = absint( $final_data['instance_id'] );
				$shipping_method = \WC_Shipping_Zones::get_shipping_method( $instance_id );
				$shipping_method->set_post_data( $final_data );
				$shipping_method->process_admin_options();
				die;
			}
		}

		/**
		 * Delete shipping Class.
		 */
		public function wkmp_delete_shipping_class() {
			if ( check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) && current_user_can( 'manage_options' ) ) {
				$term_id = \WK_Caching::wk_get_request_data( 'get-term', array( 'method' => 'post' ) );
				$resp    = array( 'success' => true );

				if ( ! empty( $term_id ) ) {
					$user_id         = get_current_user_id();
					$term_id         = intval( $term_id );
					$res             = wp_delete_term( $term_id, 'product_shipping_class' );
					$resp['success'] = $res;

					$notice_data = array(
						'wkmp_ship_action' => 'deleted',
					);
					update_user_meta( $user_id, '_wkmp_shipping_notice_data', $notice_data );
					$resp['redirect'] = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) . get_option( '_wkmp_shipping_endpoint', 'seller-shippings' ) . '/add';

				}
				wp_send_json( $resp );
			}
		}

		/**
		 * Add shipping Class.
		 */
		public function wkmp_add_shipping_class() {
			if ( check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) && current_user_can( 'manage_options' ) ) {

				$data = \WK_Caching::wk_get_request_data(
					'data',
					array(
						'method' => 'post',
						'flag'   => 'array',
					)
				);

				$final_data = array();
				$arr        = array();
				$new_arr    = array();

				$json = array(
					'redirect' => '',
					'success'  => false,
				);

				parse_str( $data, $final_data );

				$final_data = empty( $final_data ) ? array() : $final_data;

				$updated = 'error';

				foreach ( $final_data as $s_key => $s_value ) {
					$i = 0;
					$j = 0;
					foreach ( $s_value as $main_key => $main_value ) {
						if ( is_int( $main_key ) ) {
							$arr[ $i ][ $s_key ] = $main_value;
							$i ++;
						} else {
							$new_arr[ $j ][ $s_key ] = $main_value;
							$j ++;
						}
					}
				}

				foreach ( $arr as $arr_value ) {
					if ( array_key_exists( 'term_id', $arr_value ) ) {
						$updated = 'updated';
						wp_update_term( $arr_value['term_id'], 'product_shipping_class', $arr_value );
					}
				}

				$user_id = get_current_user_id();

				foreach ( $new_arr as $new_arr_value ) {
					if ( empty( $new_arr_value['name'] ) ) {
						$updated = 'empty-name';
						continue;
					}
					$term          = wp_insert_term( $new_arr_value['name'], 'product_shipping_class', $new_arr_value );
					$seller_sclass = get_user_meta( $user_id, 'shipping-classes', true );

					if ( ! empty( $seller_sclass ) ) {
						$seller_sclass = maybe_unserialize( $seller_sclass );
						array_push( $seller_sclass, $term['term_id'] );
						$seller_sclass_update = maybe_serialize( $seller_sclass );
						update_user_meta( $user_id, 'shipping-classes', $seller_sclass_update );
					} else {
						$term_arr   = array();
						$term_arr[] = $term['term_id'];
						$term_arr   = maybe_serialize( $term_arr );
						add_user_meta( $user_id, 'shipping-classes', $term_arr );
					}
					$updated = 'added';
				}

				$notice_data = array(
					'wkmp_ship_action' => $updated,
				);

				update_user_meta( $user_id, '_wkmp_shipping_notice_data', $notice_data );

				$json['redirect'] = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) . get_option( '_wkmp_shipping_endpoint', 'seller-shippings' ) . '/add';
				wp_send_json( $json );
			}
		}

		/**
		 * Add shipping method to zone
		 */
		public function wkmp_add_shipping_method() {

			if ( check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) && current_user_can( 'manage_options' ) ) {
				$zone_id     = \WK_Caching::wk_get_request_data( 'zone-id', array( 'method' => 'post' ) );
				$zone_status = $this->wkmp_is_valid_seller_zone_id( $zone_id );
				$confirm     = 'unauthorized';

				if ( $zone_status ) {
					$ship_method  = \WK_Caching::wk_get_request_data( 'ship-method', array( 'method' => 'post' ) );
					$current_zone = new \WC_Shipping_Zone( $zone_id );
					$confirm      = $current_zone->add_shipping_method( $ship_method );
				}

				$result = array( 'success' => $confirm );
				wp_send_json( $result );
			}
		}

		/**
		 * Get seller id base on zone.
		 *
		 * @param int $zon_id zone id.
		 *
		 * @return bool
		 */
		public function wkmp_is_valid_seller_zone_id( $zon_id ) {
			$wpdb_obj    = $this->wpdb;
			$get_data    = $wpdb_obj->get_row( $wpdb_obj->prepare( "Select * from {$wpdb_obj->prefix}mpseller_meta where zone_id = %d ", $zon_id ) );
			$zon_user_id = empty( $get_data->seller_id ) ? 0 : intval( $get_data->seller_id );
			$seller_id   = get_current_user_id();

			if ( $seller_id === $zon_user_id ) {
				return true;
			}

			return false;
		}

		/**
		 * Delete Zone details list ajax.
		 */
		public function wkmp_del_zone() {
			if ( check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) && current_user_can( 'manage_options' ) ) {
				$wpdb_obj          = $this->wpdb;
				$current_seller_id = get_current_user_id();
				$zone_id           = \WK_Caching::wk_get_request_data( 'zone-id', array( 'method' => 'post' ) );

				$json    = array();
				$deleted = false;

				if ( ! empty( $zone_id ) ) {
					$zone_seller_id = $wpdb_obj->get_var( $wpdb_obj->prepare( "SELECT `seller_id` FROM {$wpdb_obj->prefix}mpseller_meta WHERE zone_id = %d", $zone_id ) );
					if ( intval( $zone_seller_id ) === $current_seller_id ) {
						// Using where formatting.
						$zone      = \WC_Shipping_Zones::get_zone( $zone_id );
						$zone_name = $zone->get_data()['zone_name'];

						$wpdb_obj->delete( $wpdb_obj->prefix . 'mpseller_meta', array( 'zone_id' => $zone_id ), array( '%d' ) );
						\WC_Shipping_Zones::delete_zone( $zone_id );

						$notice_data = array(
							'action'    => 'Deleted',
							'zone_name' => $zone_name,
						);
						update_user_meta( $current_seller_id, '_wkmp_shipping_notice_data', $notice_data );
						$deleted = true;
					}
				}

				if ( $deleted ) {
					$json['message']  = esc_html__( 'Shipping zone has been deleted.', 'wk-marketplace' );
					$json['redirect'] = wc_get_endpoint_url( get_option( '_wkmp_shipping_endpoint', 'seller-shippings' ), '', wc_get_page_permalink( 'myaccount' ) );
				} else {
					$msg             = esc_html__( 'You are not allowed to delete this shipping zone.', 'wk-marketplace' );
					$json['message'] = $msg;
					wc_add_notice( $msg, 'error' );
				}

				wp_send_json( $json );
			}
		}

		/**
		 * Delete Shipping Method.
		 */
		public function wkmp_delete_shipping_method() {
			if ( check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) && current_user_can( 'manage_options' ) ) {
				$result     = array( 'success' => false );
				$wpdb_obj   = $this->wpdb;
				$table_name = $wpdb_obj->prefix . 'woocommerce_shipping_zone_methods';
				$zone_id    = \WK_Caching::wk_get_request_data( 'zone-id', array( 'method' => 'post' ) );

				$zone_status = $this->wkmp_is_valid_seller_zone_id( $zone_id );

				if ( $zone_status ) {
					$instance_id = \WK_Caching::wk_get_request_data( 'instance-id', array( 'method' => 'post' ) );
					$res         = $wpdb_obj->get_row( $wpdb_obj->prepare( "SELECT method_id FROM {$wpdb_obj->prefix}woocommerce_shipping_zone_methods WHERE zone_id = %d AND instance_id = %d", $zone_id, $instance_id ) );
					$response    = $wpdb_obj->delete(
						$table_name,
						array(
							'zone_id'     => $zone_id,
							'instance_id' => $instance_id,
						),
						array( '%d' )
					);

					if ( $response ) {
						delete_option( 'woocommerce_' . $res->method_id . '_' . $instance_id . '_settings' );
						$result['success'] = true;
					}
				} else {
					$result['success'] = false;
				}

				wp_send_json( $result );
			}

		}

		/**
		 * Marketplace variation function
		 *
		 * @param int $var_id Variable id.
		 */
		public function wkmp_marketplace_attributes_variation( $var_id ) {
			if ( check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) && current_user_can( 'manage_options' ) ) {

				$wk_pro_id = \WK_Caching::wk_get_request_data(
					'product',
					array(
						'method' => 'post',
						'filter' => 'int',
					)
				);

				if ( ! empty( $wk_pro_id ) ) {
					$post_title   = sprintf( /* translators: %d Product id. */ esc_html__( 'Variation # %d of Product', 'wk-marketplace' ), $wk_pro_id );
					$post_name    = 'product-' . $wk_pro_id . '-variation';
					$product_data = array(
						'post_author'           => get_current_user_id(),
						'post_date'             => '',
						'post_date_gmt'         => '',
						'post_content'          => '',
						'post_content_filtered' => '',
						'post_title'            => $post_title,
						'post_excerpt'          => '',
						'post_status'           => 'publish',
						'post_type'             => 'product_variation',
						'comment_status'        => 'open',
						'ping_status'           => 'open',
						'post_password'         => '',
						'post_name'             => $post_name,
						'to_ping'               => '',
						'pinged'                => '',
						'post_modified'         => '',
						'post_modified_gmt'     => '',
						'post_parent'           => $wk_pro_id,
						'menu_order'            => '',
						'guid'                  => '',
					);

					wp_set_object_terms( $wk_pro_id, 'variable', 'product_type' );
					$var_id = wp_insert_post( $product_data );
					\WC_Product_Variable::sync( $wk_pro_id );

					require_once WKMP_LITE_PLUGIN_FILE . 'templates/front/seller/product/wkmp-variations.php';
					die;
				} else {
					$wk_pro_id = $var_id;

					$args = array(
						'post_parent'    => $wk_pro_id,
						'post_type'      => 'product_variation',
						'posts_per_page' => - 1,
						'post_status'    => 'publish',
					);

					$children_array = get_children( $args );
					$i              = 0;

					foreach ( $children_array as $var_att ) {
						$this->wkmp_attribute_variation_data( $var_att->ID, $wk_pro_id );
						$i ++;
					}
				}
				if ( $wk_pro_id ) {
					wp_die();
				}
			}
		}

		/**
		 * Attribute variation data.
		 *
		 * @param int $var_id Variable id.
		 * @param int $wk_pro_id Product id.
		 */
		public function wkmp_attribute_variation_data( $var_id, $wk_pro_id ) {
			require_once WKMP_LITE_PLUGIN_FILE . 'templates/front/seller/product/wkmp-variations.php';
		}

		/**
		 * Remove variation attribute.
		 */
		public function wkmp_attributes_variation_remove() {
			$result = array(
				'success' => false,
				'msg'     => esc_html__( 'Some error in removing, kindly reload the page and try again!!', 'wk-marketplace' ),
			);

			if ( check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) && current_user_can( 'manage_options' ) ) {
				$var_id = \WK_Caching::wk_get_request_data( 'var_id', array( 'method' => 'post' ) );

				if ( $var_id > 0 ) {
					wp_delete_post( $var_id );
					$result['success'] = true;
					$result['msg']     = esc_html__( 'The variation has been removed successfully.', 'wk-marketplace' );
				}
			}
			wp_send_json( $result );
		}

		/**
		 * Product sku validation.
		 */
		public function wkmp_validate_seller_product_sku() {
			if ( check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) && current_user_can( 'wk_marketplace_seller' ) ) {
				$wpdb_obj = $this->wpdb;
				$chk_sku  = \WK_Caching::wk_get_request_data( 'psku', array( 'method' => 'post' ) );

				$response = array(
					'success' => false,
					'message' => esc_html__( 'Please enter a valid alphanumeric SKU', 'wk-marketplace' ),
				);

				if ( ! empty( $chk_sku ) ) {
					$seller_id           = get_current_user_id();
					$dynamic_sku_enabled = get_user_meta( $seller_id, '_wkmp_enable_seller_dynamic_sku', true );
					$dynamic_sku_prefix  = get_user_meta( $seller_id, '_wkmp_dynamic_sku_prefix', true );

					$sku_post_id = $wpdb_obj->get_var( $wpdb_obj->prepare( "SELECT post_id FROM {$wpdb_obj->prefix}postmeta WHERE meta_key='_sku' AND meta_value=%s", $chk_sku ) );

					if ( intval( $sku_post_id ) > 0 && $dynamic_sku_enabled && ! empty( $dynamic_sku_prefix ) ) {
						$post_sku_prefix = get_post_meta( $sku_post_id, '_sku_prefix', true );
						$sku_post_id     = ( $post_sku_prefix === $dynamic_sku_prefix );
					}

					if ( ! empty( $sku_post_id ) ) {
						$response = array(
							'success' => false,
							'message' => esc_html__( 'SKU already exist please select another SKU', 'wk-marketplace' ),
						);
					} else {
						$response['success'] = true;
						$response['message'] = esc_html__( 'SKU is OK', 'wk-marketplace' );
					}
				}
				wp_send_json( $response );
			}
		}

		/**
		 * Gallery image delete.
		 */
		public function wkmp_productgallary_image_delete() {
			if ( check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) ) {
				$img_id     = \WK_Caching::wk_get_request_data( 'img_id', array( 'method' => 'post' ) );
				$ip         = explode( 'i_', $img_id );
				$img_id     = get_post_meta( $ip[0], '_product_image_gallery', true );
				$arr        = array_diff( explode( ',', $img_id ), array( $ip[1] ) );
				$remain_ids = implode( ',', $arr );
				update_post_meta( $ip[0], '_product_image_gallery', $remain_ids );
				wp_send_json( $remain_ids );
			}
		}

		/**
		 * Downloadable file adding.
		 */
		public function wkmp_downloadable_file_add() {
			if ( check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) ) {
				$y = \WK_Caching::wk_get_request_data( 'var_id', array( 'method' => 'post' ) );
				$i = \WK_Caching::wk_get_request_data( 'eleme_no', array( 'method' => 'post' ) );
				?>
				<div class="tr_div">
					<div>
						<label for="downloadable_upload_file_name_<?php echo esc_attr( $y ) . '_' . esc_attr( $i ); ?>"><?php esc_html_e( 'File Name', 'wk-marketplace' ); ?></label>
						<input type="text" class="input_text" placeholder="File Name" id="downloadable_upload_file_name_<?php echo esc_attr( $y ) . '_' . esc_attr( $i ); ?>" name="_mp_variation_downloads_files_name[<?php echo esc_attr( $y ); ?>][<?php echo esc_attr( $i ); ?>]" value="">
					</div>
					<div class="file_url">
						<label for="downloadable_upload_file_url_<?php echo esc_attr( $y ) . '_' . esc_attr( $i ); ?>"><?php esc_html_e( 'File Url', 'wk-marketplace' ); ?></label>
						<input type="text" class="input_text" placeholder="http://" id="downloadable_upload_file_url_<?php echo esc_attr( $y ) . '_' . esc_attr( $i ); ?>" name="_mp_variation_downloads_files_url[<?php echo esc_attr( $y ); ?>][<?php echo esc_attr( $i ); ?>]" value="">
						<a href="javascript:void(0);" class="button wkmp_downloadable_upload_file" id="<?php echo esc_attr( $y ) . '_' . esc_attr( $i ); ?>"><?php esc_html_e( 'Choose&nbsp;file', 'wk-marketplace' ); ?></a>
						<a href="javascript:void(0);" class="delete mp_var_del" id="mp_var_del_<?php echo esc_attr( $y ) . '_' . esc_attr( $i ); ?>"><?php esc_html_e( 'Delete', 'wk-marketplace' ); ?></a>
					</div>
					<div class="file_url_choose">

					</div>
				</div>
				<?php
				die;
			}
		}

		/**
		 * Change seller dashboard settings.
		 */
		public function wkmp_change_dashboard_to_backend_seller() {
			if ( check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) && current_user_can( 'wk_marketplace_seller' ) ) {
				global $wkmarketplace;
				$data      = array();
				$change_to = \WK_Caching::wk_get_request_data( 'change_to', array( 'method' => 'post' ) );

				if ( ! empty( $change_to ) ) {
					$c_user_id    = get_current_user_id();
					$current_dash = get_user_meta( $c_user_id, 'wkmp_seller_backend_dashboard', true );

					if ( 'front_dashboard' === $change_to ) {
						if ( $current_dash ) {
							update_user_meta( $c_user_id, 'wkmp_seller_backend_dashboard', null );
							$data['redirect'] = esc_url( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) . '/' . get_option( '_wkmp_dashboard_endpoint', 'seller-dashboard' ) );
						}
					} elseif ( 'backend_dashboard' === $change_to ) {
						update_user_meta( $c_user_id, 'wkmp_seller_backend_dashboard', true );
						$wkmarketplace->wkmp_add_role_cap( $c_user_id );
						$data['redirect'] = esc_url( admin_url( 'admin.php?page=seller' ) );
					}
				}

				wp_send_json( $data );
			}
		}

		/**
		 * Delete seller product.
		 *
		 * @return void
		 */
		public function wkmp_delete_seller_product() {
			if ( check_ajax_referer( 'wkmp-front-nonce', 'wkmp_nonce', false ) ) {
				$product_id = \WK_Caching::wk_get_request_data( 'product_id', array( 'method' => 'post' ) );

				$resp = array(
					'success' => false,
					'message' => esc_html__( 'Unable to delete the product. Please try again later!!', 'wk-marketplace' ),
				);

				if ( $product_id > 0 ) {
					$product_author = get_post_field( 'post_author', $product_id );
					$seller_id      = get_current_user_id();

					if ( $seller_id > 0 && intval( $seller_id ) === intval( $product_author ) && wp_delete_post( $product_id ) ) {
						$resp['message'] = esc_html__( 'Product(s) deleted successfully.', 'wk-marketplace' );
						$resp['success'] = true;
					}
				}

				wp_send_json( $resp );
			}
		}
	}
}
