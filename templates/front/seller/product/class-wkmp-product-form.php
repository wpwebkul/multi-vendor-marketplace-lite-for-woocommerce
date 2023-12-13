<?php
/**
 * Seller product at front.
 *
 * @package Multi Vendor Marketplace
 * @version 5.0.0
 */

namespace WkMarketplace\Templates\Front\Seller\Product;

use WK_Caching;

defined( 'ABSPATH' ) || exit; // Exit if access directly.

if ( ! class_exists( 'WKMP_Product_Form' ) ) {
	/**
	 * Seller Add / Edit Product class.
	 *
	 * Class WKMP_Product_Form
	 *
	 * @package WkMarketplace\Templates\Front\Seller\Product
	 */
	class WKMP_Product_Form {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Product id.
		 *
		 * @var int $product_id Product id.
		 */
		protected $product_id;

		/**
		 * Seller id.
		 *
		 * @var int $seller_id Seller id.
		 */
		protected $seller_id;

		/**
		 * WPDB Object.
		 *
		 * @var \QM_DB|\wpdb
		 */
		protected $wpdb;

		/**
		 * Marketplace class object.
		 *
		 * @var $wkmarketplace WKMarketplace.
		 */
		protected $wkmarketplace;

		/**
		 * Constructor of the class.
		 *
		 * WKMP_Product_Form constructor.
		 *
		 * @param int $seller_id Seller id.
		 */
		public function __construct( $seller_id = 0 ) {
			global $wkmarketplace, $wpdb;

			$this->wkmarketplace = $wkmarketplace;
			$this->wpdb          = $wpdb;

			$this->seller_id = intval( $seller_id );
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
		 * Show add product form.
		 *
		 * @param int $seller_id Seller id.
		 *
		 * @return void
		 */
		public function wkmp_add_product_form( $seller_id ) {
			$this->seller_id     = empty( $this->seller_id ) ? $seller_id : $this->seller_id;
			$categories          = array();
			$allowed_cat         = get_user_meta( $seller_id, 'wkmp_seller_allowed_categories', true );
			$dynamic_sku_enabled = get_user_meta( $seller_id, '_wkmp_enable_seller_dynamic_sku', true );
			$dynamic_sku_prefix  = get_user_meta( $seller_id, '_wkmp_dynamic_sku_prefix', true );

			if ( ! $allowed_cat ) {
				$allowed_cat = get_option( '_wkmp_seller_allowed_categories', array() );
			}

			$product_categories = wp_dropdown_categories(
				array(
					'show_option_none' => '',
					'hierarchical'     => 1,
					'hide_empty'       => 0,
					'name'             => 'product_cate[]',
					'id'               => 'mp_seller_product_categories',
					'taxonomy'         => 'product_cat',
					'title_li'         => '',
					'orderby'          => 'name',
					'order'            => 'ASC',
					'class'            => '',
					'exclude'          => '',
					'selected'         => $categories,
					'echo'             => 0,
					'value_field'      => 'slug',
					'walker'           => new WKMP_Category_Filter( $allowed_cat ),
				)
			);

			?>
			<div class="form wkmp_container add-product-form">
				<?php
				$nonce_first           = \WK_Caching::wk_get_request_data( 'wkmp_select_type_cat_nonce_name', array( 'method' => 'post' ) );
				$mp_product_type       = wc_get_product_types();
				$allowed_product_types = get_option( '_wkmp_seller_allowed_product_types', array() );
				$allowed_product_types = empty( $allowed_product_types ) ? array() : $allowed_product_types;

				if ( ! empty( $nonce_first ) && wp_verify_nonce( $nonce_first, 'wkmp_select_type_cat_nonce_action' ) ) {
					$product_cats = \WK_Caching::wk_get_request_data(
						'product_cate',
						array(
							'method' => 'post',
							'flag'   => 'array',
						)
					);

					$product_type = \WK_Caching::wk_get_request_data( 'product_type', array( 'method' => 'post' ) );
					$next_clicked = \WK_Caching::wk_get_request_data( 'wkmp_add_product_next_step', array( 'method' => 'post' ) );

					if ( ! empty( $product_type ) && $next_clicked && ! empty( $product_cats ) ) {
						require_once __DIR__ . '/wkmp-add-product.php';
					} else {
						wc_print_notice( esc_html__( 'Sorry, Firstly select product category(s) and type.', 'wk-marketplace' ), 'error' );
						require_once __DIR__ . '/wkmp-add-product-first-step.php';
					}
				} else {
					$nonce_submit = \WK_Caching::wk_get_request_data( 'wkmp_add_product_submit_nonce_name', array( 'method' => 'post' ) );
					$next_clicked = \WK_Caching::wk_get_request_data( 'wkmp_add_product_next_step', array( 'method' => 'post' ) );

					if ( ! empty( $nonce_first ) && ! wp_verify_nonce( $nonce_first, 'wkmp_select_type_cat_nonce_action' ) || ( ! empty( $nonce_submit ) && ! wp_verify_nonce( $nonce_submit, 'wkmp_add_product_submit_nonce_action' ) ) ) {
						wc_print_notice( esc_html__( 'Security nonce not validated!!', 'wk-marketplace' ), 'error' );
					}

					require_once __DIR__ . '/wkmp-add-product-first-step.php';
				}
				?>
			</div>
			<?php
		}

		/**
		 * Edit product form.
		 *
		 * @param int $seller_id Seller id.
		 *
		 * @return void
		 */
		public function wkmp_edit_product_form( $seller_id = 0 ) {
			global $wp;

			$nonce_add    = \WK_Caching::wk_get_request_data( 'wkmp_add_product_submit_nonce_name', array( 'method' => 'post' ) );
			$nonce_update = \WK_Caching::wk_get_request_data( 'wkmp_edit_product_nonce_field', array( 'method' => 'post' ) );

			$nonce_failed   = false;
			$form_submitted = ! ( empty( $nonce_add ) && empty( $nonce_update ) );

			if ( ! empty( $nonce_add ) && ! wp_verify_nonce( $nonce_add, 'wkmp_add_product_submit_nonce_action' ) ) {
				$nonce_failed = true;
			}

			if ( ! empty( $nonce_update ) && ! wp_verify_nonce( $nonce_update, 'wkmp_edit_product_nonce_action' ) ) {
				$nonce_failed = true;
			}

			$this->seller_id = empty( $this->seller_id ) ? $seller_id : $this->seller_id;

			$posted_data = array(
				'dynamic_sku_enabled' => get_user_meta( $this->seller_id, '_wkmp_enable_seller_dynamic_sku', true ),
				'dynamic_sku_prefix'  => get_user_meta( $this->seller_id, '_wkmp_dynamic_sku_prefix', true ),
			);

			if ( $form_submitted && ! $nonce_failed ) {
				$args                       = array( 'method' => 'post' );
				$posted_data['product_sku'] = \WK_Caching::wk_get_request_data( 'product_sku', $args );

				$args['filter']            = 'float';
				$posted_data['regu_price'] = \WK_Caching::wk_get_request_data( 'regu_price', $args );
				$posted_data['sale_price'] = \WK_Caching::wk_get_request_data( 'sale_price', $args );

				$args['filter']                 = 'int';
				$posted_data['seller_id']       = \WK_Caching::wk_get_request_data( 'seller_id', $args );
				$posted_data['sell_pr_id']      = \WK_Caching::wk_get_request_data( 'sell_pr_id', $args );
				$posted_data['wk-mp-stock-qty'] = \WK_Caching::wk_get_request_data( 'wk-mp-stock-qty', $args );

				$posted_data['product_desc'] = empty( $_POST['product_desc'] ) ? '' : wp_kses_post( $_POST['product_desc'] );
				$posted_data['short_desc']   = empty( $_POST['short_desc'] ) ? '' : wp_kses_post( $_POST['short_desc'] );

				$this->wkmp_product_add_update( $posted_data );
			} elseif ( $nonce_failed ) {
				wc_print_notice( esc_html__( 'Sorry!! security check failed. Please try again!!', 'wk-marketplace' ), 'error' );
			}

			$wpdb_obj = $this->wpdb;

			$query_vars       = $wp->query_vars;
			$edit_product     = get_option( '_wkmp_edit_product_endpoint', 'seller-edit-product' );
			$this->product_id = empty( $query_vars[ $edit_product ] ) ? 0 : intval( $query_vars[ $edit_product ] );

			if ( $this->product_id > 0 ) {
				$wk_pro_id = $this->product_id;

				$allowed_cat = get_user_meta( $this->seller_id, 'wkmp_seller_allowed_categories', true );

				if ( ! $allowed_cat ) {
					$allowed_cat = get_option( '_wkmp_seller_allowed_categories', array() );
				}

				$categories         = wp_get_post_terms( $wk_pro_id, 'product_cat', array( 'fields' => 'slugs' ) );
				$product_categories = wp_dropdown_categories(
					array(
						'show_option_none' => '',
						'hierarchical'     => 1,
						'hide_empty'       => 0,
						'name'             => 'product_cate[]',
						'id'               => 'mp_seller_product_categories',
						'taxonomy'         => 'product_cat',
						'title_li'         => '',
						'orderby'          => 'name',
						'order'            => 'ASC',
						'class'            => '',
						'exclude'          => '',
						'selected'         => $categories,
						'echo'             => 0,
						'value_field'      => 'slug',
						'walker'           => new WKMP_Category_Filter( $allowed_cat ),
					)
				);

				$product_auth  = get_post_field( 'post_author', $this->product_id );
				$post_row_data = $wpdb_obj->get_results( $wpdb_obj->prepare( "SELECT * FROM {$wpdb_obj->prefix}posts WHERE ID = %s", $this->product_id ) );
				$product_array = $wpdb_obj->get_results( $wpdb_obj->prepare( "SELECT * FROM {$wpdb_obj->prefix}posts WHERE post_type = 'product' AND post_status = 'publish' AND post_author = %d ORDER BY ID DESC", $this->seller_id ) );

				require_once __DIR__ . '/wkmp-edit-product.php';
				unset( $_POST );
			}
		}

		/**
		 * Add/Update product into database.
		 *
		 * @param array $posted_data Posted data.
		 *
		 * @return void
		 */
		public function wkmp_product_add_update( $posted_data ) {
			global $current_user;

			$errors = $this->wkmp_product_validation( $posted_data );

			if ( ! empty( $errors ) ) {
				foreach ( $errors as $value ) {
					wc_print_notice( $value, 'error' );
				}
			} else {
				$posted_data['seller_id'] = empty( $posted_data['seller_id'] ) ? ( empty( $this->seller_id ) ? get_current_user_id() : $this->seller_id ) : $posted_data['seller_id'];

				$manage_stock_status = false;
				$sell_pr_id          = isset( $posted_data['sell_pr_id'] ) ? intval( $posted_data['sell_pr_id'] ) : 0;
				$sell_pr_id          = empty( $sell_pr_id ) ? $this->product_id : $sell_pr_id;

				$args = array(
					'method' => 'post',
					'flag'   => 'array',
				);

				$variation_att_ids = \WK_Caching::wk_get_request_data( 'mp_attribute_variation_name', $args );
				$att_val           = \WK_Caching::wk_get_request_data( 'pro_att', $args );

				if ( isset( $posted_data['sale_price'] ) && '' === $posted_data['sale_price'] ) {
					unset( $posted_data['sale_price'] );
				}

				if ( ! empty( $variation_att_ids ) && ! empty( $att_val ) ) {
					$this->wkmp_update_product_variation_data( $posted_data, $variation_att_ids );
				}

				$att = array();

				if ( ! empty( $att_val ) ) {
					foreach ( $att_val as $attribute ) {

						if ( empty( $attribute['name'] ) || empty( $attribute['value'] ) ) {
							continue;
						}

						$rep_str            = $attribute['value'];
						$rep_str            = preg_replace( '/\s+/', ' ', $rep_str );
						$attribute['name']  = str_replace( ' ', '-', $attribute['name'] );
						$attribute['value'] = str_replace( '|', '|', $rep_str );

						if ( isset( $attribute['is_visible'] ) ) {
							$attribute['is_visible'] = (int) $attribute['is_visible'];
						} else {
							$attribute['is_visible'] = 0;
						}

						if ( isset( $attribute['is_variation'] ) ) {
							$attribute['is_variation'] = (int) $attribute['is_variation'];
						} else {
							$attribute['is_variation'] = 0;
						}

						$attribute['is_taxonomy']                           = (int) $attribute['is_taxonomy'];
						$att[ str_replace( ' ', '-', $attribute['name'] ) ] = $attribute;
					}
				}

				$product_auth = ( $sell_pr_id > 0 ) ? get_post_field( 'post_author', $sell_pr_id ) : 0;

				$args['flag'] = '';
				$product_name = \WK_Caching::wk_get_request_data( 'product_name', $args );

				if ( ! empty( $product_name ) ) {
					$product_name = wp_strip_all_tags( $product_name );
					$product_dsc  = empty( $posted_data['product_desc'] ) ? '' : $posted_data['product_desc'];

					$downloadable           = \WK_Caching::wk_get_request_data( '_downloadable', $args );
					$posted_sku             = \WK_Caching::wk_get_request_data( 'product_sku', $args );
					$max_qty_limit          = \WK_Caching::wk_get_request_data( '_wkmp_max_product_qty_limit', $args );
					$product_status         = \WK_Caching::wk_get_request_data( 'mp_product_status', $args );
					$product_gallery_images = \WK_Caching::wk_get_request_data( 'product_image_Galary_ids', $args );

					$args['default'] = 'simple';
					$product_type    = \WK_Caching::wk_get_request_data( 'product_type', $args );

					$args['default'] = 'no';
					$virtual         = \WK_Caching::wk_get_request_data( '_virtual', $args );
					$back_order      = \WK_Caching::wk_get_request_data( '_backorders', $args );
					$sold_individual = \WK_Caching::wk_get_request_data( 'wk_sold_individual', $args );
					$manage_stock    = \WK_Caching::wk_get_request_data( 'wk_stock_management', $args );

					$args['default'] = 0;
					$threshold       = \WK_Caching::wk_get_request_data( 'wk-mp-stock-threshold', $args );

					$args['default'] = '-1';
					$limit           = \WK_Caching::wk_get_request_data( '_download_limit', $args );
					$expiry          = \WK_Caching::wk_get_request_data( '_download_expiry', $args );

					$args['default'] = '';

					$simple    = ( 'simple' === $product_type ) ? 'yes' : 'no';
					$stock_qty = ( 'yes' === $manage_stock ) ? $posted_data['wk-mp-stock-qty'] : '';

					if ( empty( $posted_sku ) && $sell_pr_id > 0 ) {
						$posted_sku = get_post_meta( $sell_pr_id, '_sku', true );
					}

					$price       = empty( $posted_data['regu_price'] ) ? '' : $posted_data['regu_price'];
					$sales_price = empty( $posted_data['sale_price'] ) ? '' : $posted_data['sale_price'];

					$product_short_desc = empty( $posted_data['short_desc'] ) ? '' : $posted_data['short_desc'];

					$product_data = array(
						'post_author'           => $this->seller_id,
						'post_content'          => $product_dsc,
						'post_content_filtered' => $product_short_desc,
						'post_title'            => htmlspecialchars( $product_name ),
						'post_excerpt'          => $product_short_desc,
						'post_status'           => $product_status,
						'post_type'             => 'product',
						'comment_status'        => 'open',
						'ping_status'           => 'open',
						'post_password'         => '',
						'post_name'             => wp_strip_all_tags( $product_name ),
						'to_ping'               => '',
						'pinged'                => '',
						'post_parent'           => '',
						'menu_order'            => '',
						'guid'                  => '',
					);

					$nonce_update = \WK_Caching::wk_get_request_data( 'wkmp_edit_product_nonce_field', array( 'method' => 'post' ) );

					if ( $sell_pr_id > 0 && intval( $product_auth ) === $this->seller_id && ! empty( $nonce_update ) ) {

						// Add mp shipping per product addon data.
						$product_shipping_class = '';

						if ( 'external' !== $product_type ) {
							$product_shipping_class = \WK_Caching::wk_get_request_data( '$product_shipping_class', $args );
						}

						wp_set_object_terms( $sell_pr_id, $product_shipping_class, 'product_shipping_class' );

						$product_data['ID'] = $sell_pr_id;

						if ( wp_update_post( $product_data ) ) {
							wc_print_notice( __( 'Product Updated Successfully.', 'wk-marketplace' ) );

							if ( ! empty( $posted_sku ) ) {
								update_post_meta( $sell_pr_id, '_sku', wp_strip_all_tags( $posted_sku ) );
							}

							$visibility = ( 'publish' === $product_status && in_array( 'wk_marketplace_seller', $current_user->roles, true ) ) ? 'visible' : '';

							update_post_meta( $sell_pr_id, '_visibility', $visibility );

							if ( is_numeric( $price ) || empty( $price ) ) {
								update_post_meta( $sell_pr_id, '_regular_price', $price );
							}

							if ( 'variable' !== $product_type ) {
								if ( ! empty( $sales_price ) && is_numeric( $sales_price ) && $sales_price < $price ) {
									update_post_meta( $sell_pr_id, '_sale_price', $sales_price );
									update_post_meta( $sell_pr_id, '_price', $sales_price );
								} else {
									update_post_meta( $sell_pr_id, '_sale_price', '' );
									if ( is_numeric( $price ) || empty( $price ) ) {
										update_post_meta( $sell_pr_id, '_price', $price );
									}
								}
							} else {
								delete_post_meta( $sell_pr_id, '_price' );
							}

							$args['default'] = 'instock';
							$stock           = \WK_Caching::wk_get_request_data( '_stock_status', $args );

							if ( ! empty( $variation_att_ids ) ) {
								$stock = ( $manage_stock_status ) ? 'instock' : 'outofstock';
							} else {
								if ( 'yes' === $manage_stock ) {
									$stock = ( $stock_qty ) ? 'instock' : 'outofstock';
								}
							}

							$args['default'] = '';

							update_post_meta( $sell_pr_id, '_sold_individually', $sold_individual );
							update_post_meta( $sell_pr_id, '_low_stock_amount', $threshold );
							update_post_meta( $sell_pr_id, '_backorders', $back_order );
							update_post_meta( $sell_pr_id, '_stock_status', $stock );
							update_post_meta( $sell_pr_id, '_manage_stock', $manage_stock );
							update_post_meta( $sell_pr_id, '_virtual', $virtual );
							update_post_meta( $sell_pr_id, '_simple', $simple );
							update_post_meta( $sell_pr_id, '_wkmp_max_product_qty_limit', $max_qty_limit );

							if ( 'yes' === $virtual ) {
								update_post_meta( $sell_pr_id, '_weight', '' );
								update_post_meta( $sell_pr_id, '_length', '' );
								update_post_meta( $sell_pr_id, '_width', '' );
								update_post_meta( $sell_pr_id, '_height', '' );
							} else {
								$weight = \WK_Caching::wk_get_request_data( '_weight', $args );
								$length = \WK_Caching::wk_get_request_data( '_length', $args );
								$width  = \WK_Caching::wk_get_request_data( '_width', $args );
								$height = \WK_Caching::wk_get_request_data( '_height', $args );

								$weight = empty( $weight ) ? '' : wc_format_decimal( $weight );
								$length = empty( $length ) ? '' : wc_format_decimal( $length );
								$width  = empty( $width ) ? '' : wc_format_decimal( $width );
								$height = empty( $height ) ? '' : wc_format_decimal( $height );

								update_post_meta( $sell_pr_id, '_weight', $weight );
								update_post_meta( $sell_pr_id, '_length', $length );
								update_post_meta( $sell_pr_id, '_width', $width );
								update_post_meta( $sell_pr_id, '_height', $height );
							}

							if ( 'external' === $product_type ) {
								$pro_url = \WK_Caching::wk_get_request_data( 'product_url', $args );
								$btn_txt = \WK_Caching::wk_get_request_data( 'button_txt', $args );

								if ( ! empty( $pro_url ) && ! empty( $btn_txt ) ) {
									update_post_meta( $sell_pr_id, '_product_url', esc_url_raw( $pro_url ) );
									update_post_meta( $sell_pr_id, '_button_text', $btn_txt );
								}
							}

							// Save upsells && Cross sells data.

							$args['flag']   = 'array';
							$args['filter'] = 'int';

							$upsell_ids    = \WK_Caching::wk_get_request_data( 'upsell_ids', $args );
							$crosssell_ids = \WK_Caching::wk_get_request_data( '_crosssell_ids', $args );

							update_post_meta( $sell_pr_id, '_upsell_ids', $upsell_ids );
							update_post_meta( $sell_pr_id, '_crosssell_ids', $crosssell_ids );

							if ( 'grouped' === $product_type ) {
								$group_product_ids = \WK_Caching::wk_get_request_data( 'mp_grouped_products', $args );
								update_post_meta( $sell_pr_id, '_children', $group_product_ids );
							}

							if ( 'yes' === $downloadable ) {
								$upload_file_url = array();
								$args['filter']  = '';

								$download_urls  = \WK_Caching::wk_get_request_data( '_mp_dwnld_file_urls', $args );
								$download_names = \WK_Caching::wk_get_request_data( '_mp_dwnld_file_names', $args );
								$file_hashes    = \WK_Caching::wk_get_request_data( '_mp_dwnld_file_hashes', $args );

								update_post_meta( $sell_pr_id, '_downloadable', $downloadable );
								update_post_meta( $sell_pr_id, '_virtual', 'yes' );

								foreach ( $download_urls as $key => $value ) {
									$dw_file_name = ( ! empty( $download_names[ $key ] ) ) ? $download_names[ $key ] : '';

									$upload_file_url[ md5( $value ) ] = array(
										'id'            => md5( $value ),
										'name'          => $dw_file_name,
										'file'          => $value,
										'previous_hash' => $file_hashes[ $key ],
									);
								}

								$data_store = \WC_Data_Store::load( 'customer-download' );

								if ( $upload_file_url ) {
									foreach ( $upload_file_url as $download ) {
										$new_hash = md5( $download['file'] );

										if ( $download['previous_hash'] && $download['previous_hash'] !== $new_hash ) {
											// Update permissions.
											$data_store->update_download_id( $sell_pr_id, $download['previous_hash'], $new_hash );
										}
									}
								}
								update_post_meta( $sell_pr_id, '_downloadable_files', $upload_file_url );
							} else {
								update_post_meta( $sell_pr_id, '_downloadable', 'no' );
							}

							$att = empty( $att ) ? array() : $att;

							update_post_meta( $sell_pr_id, '_product_attributes', $att );

							if ( '' !== $stock_qty ) {
								update_post_meta( $sell_pr_id, '_stock', $stock_qty );
							} else {
								delete_post_meta( $sell_pr_id, '_stock' );
							}

							update_post_meta( $sell_pr_id, '_download_limit', $limit );
							update_post_meta( $sell_pr_id, '_download_expiry', $expiry );
							update_post_meta( $sell_pr_id, '_product_image_gallery', $product_gallery_images );

							$args['flag']   = '';
							$args['filter'] = '';

							$thumbnail_id = \WK_Caching::wk_get_request_data( '_mp_dwnld_file_urls', $args );

							if ( ! empty( $thumbnail_id ) ) {
								update_post_meta( $sell_pr_id, '_thumbnail_id', $thumbnail_id );
							}
						}

						$args['flag']   = 'array';
						$args['filter'] = '';

						$product_cats = \WK_Caching::wk_get_request_data( 'product_cate', $args );

						$this->wkmp_update_pro_category( $product_cats, $sell_pr_id );
						wp_set_object_terms( $sell_pr_id, $product_type, 'product_type', false );
					}

					do_action( 'marketplace_process_product_meta', $sell_pr_id );

					if ( ! get_option( '_wkmp_allow_seller_to_publish', true ) ) {
						if ( ! get_post_meta( $sell_pr_id, 'mp_added_noti' ) ) {
							delete_post_meta( $sell_pr_id, 'mp_admin_view' );
							update_option( 'wkmp_approved_product_count', (int) ( get_option( 'wkmp_approved_product_count', 0 ) + 1 ) );
							update_post_meta( $sell_pr_id, 'mp_added_noti', true );
						}

						do_action( 'wkmp_seller_published_product', $this->seller_id, $sell_pr_id );
					}

					$nonce_add = \WK_Caching::wk_get_request_data( 'wkmp_add_product_submit_nonce_name', array( 'method' => 'post' ) );

					if ( $sell_pr_id > 0 && intval( $product_auth ) === intval( $this->seller_id ) && ! empty( $nonce_add ) ) {

						if ( 'simple' === $product_type ) {
							$obj_product = new \WC_Product_Simple( $sell_pr_id );
							$obj_product->save();
						} elseif ( 'variable' === $product_type ) {
							$obj_product = new \WC_Product_Variable( $sell_pr_id );
							$obj_product->save();

							foreach ( $variation_att_ids as $variation_id ) {
								$variation = new \WC_Product_Variation( $variation_id );
								$variation->save();
							}
						}
					}
					do_action( 'wkmp_after_seller_created_product', $this->seller_id, $sell_pr_id );
				}
			}
		}

		/**
		 * Adding variation attribute of product.
		 *
		 * @param array $posted_data Posted data.
		 * @param array $var_attr_ids Variation attr ids.
		 *
		 * @return void
		 */
		public function wkmp_update_product_variation_data( $posted_data, $var_attr_ids ) {
			$wpdb_obj               = $this->wpdb;
			$variation_data         = array();
			$variation_data['_sku'] = array();
			$temp_var_sku           = array();
			$var_regu_price         = array();
			$var_sale_price         = array();

			$args = array(
				'method' => 'post',
				'flag'   => 'array',
			);

			$mp_attr_names       = WK_Caching::wk_get_request_data( 'mp_attribute_name', $args );
			$is_downloadables    = WK_Caching::wk_get_request_data( 'wkmp_variable_is_downloadable', $args );
			$vars_is_virtual     = WK_Caching::wk_get_request_data( 'wkmp_variable_is_virtual', $args );
			$sales_from          = WK_Caching::wk_get_request_data( 'wkmp_variable_sale_price_dates_from', $args );
			$sales_to            = WK_Caching::wk_get_request_data( 'wkmp_variable_sale_price_dates_to', $args );
			$backorders          = WK_Caching::wk_get_request_data( 'wkmp_variable_backorders', $args );
			$manage_stocks       = WK_Caching::wk_get_request_data( 'wkmp_variable_manage_stock', $args );
			$stocks_status       = WK_Caching::wk_get_request_data( 'wkmp_variable_stock_status', $args );
			$variable_skus       = WK_Caching::wk_get_request_data( 'wkmp_variable_sku', $args );
			$download_file_urls  = WK_Caching::wk_get_request_data( '_mp_variation_downloads_files_url', $args );
			$download_file_names = WK_Caching::wk_get_request_data( '_mp_variation_downloads_files_name', $args );

			$args['filter']   = 'float';
			$regular_prices   = WK_Caching::wk_get_request_data( 'wkmp_variable_regular_price', $args );
			$sale_prices      = WK_Caching::wk_get_request_data( 'wkmp_variable_sale_price', $args );
			$variable_widths  = WK_Caching::wk_get_request_data( 'wkmp_variable_width', $args );
			$variable_heights = WK_Caching::wk_get_request_data( 'wkmp_variable_height', $args );
			$variable_lengths = WK_Caching::wk_get_request_data( 'wkmp_variable_length', $args );
			$variable_weights = WK_Caching::wk_get_request_data( 'wkmp_variable_weight', $args );

			$args['filter']   = 'int';
			$downloads_expiry = WK_Caching::wk_get_request_data( 'wkmp_variable_download_expiry', $args );
			$downloads_limit  = WK_Caching::wk_get_request_data( 'wkmp_variable_download_limit', $args );
			$variable_stocks  = WK_Caching::wk_get_request_data( 'wkmp_variable_stock', $args );
			$variable_img_ids = WK_Caching::wk_get_request_data( 'upload_var_img', $args );
			$var_menu_orders  = WK_Caching::wk_get_request_data( 'wkmp_variation_menu_order', $args );

			foreach ( $var_attr_ids as $var_id ) {
				$var_regu_price[ $var_id ] = is_numeric( $regular_prices[ $var_id ] ) ? $regular_prices[ $var_id ] : '';

				if ( isset( $sale_prices[ $var_id ] ) && is_numeric( $sale_prices[ $var_id ] ) && $sale_prices[ $var_id ] < $var_regu_price[ $var_id ] ) {
					$var_sale_price[ $var_id ] = $sale_prices[ $var_id ];
				} else {
					$var_sale_price[ $var_id ] = '';
				}

				foreach ( $mp_attr_names[ $var_id ] as $variation_type ) {
					$args['filter'] = '';
					$attr_names     = WK_Caching::wk_get_request_data( 'attribute_' . $variation_type, $args );
					$variation_data[ 'attribute_' . sanitize_title( $variation_type ) ][] = trim( $attr_names[ $var_id ] );
				}
				$downloadable_variable = 'no';
				if ( isset( $is_downloadables[ $var_id ] ) ) {
					$downloadable_variable = ( 'yes' === $is_downloadables[ $var_id ] ) ? 'yes' : $downloadable_variable;
				}

				$virtual_variable = 'no';
				if ( isset( $vars_is_virtual[ $var_id ] ) ) {
					$virtual_variable = ( 'yes' === $vars_is_virtual[ $var_id ] ) ? 'yes' : $virtual_variable;
				}

				if ( 'yes' === $downloadable_variable ) {
					if ( isset( $downloads_expiry[ $var_id ] ) && is_numeric( $downloads_expiry[ $var_id ] ) ) {
						$downloadable_variable = $downloads_expiry[ $var_id ];
					}
					if ( isset( $downloads_limit[ $var_id ] ) && is_numeric( $downloads_limit[ $var_id ] ) ) {
						$downloadable_variable = $downloads_limit[ $var_id ];
					}
				}

				if ( isset( $var_sale_price[ $var_id ] ) && is_numeric( $var_sale_price[ $var_id ] ) && $var_sale_price[ $var_id ] < $var_regu_price[ $var_id ] ) {
					$variation_data['_sale_price'][] = $var_sale_price[ $var_id ];
				} else {
					$variation_data['_sale_price'][] = '';
				}

				if ( '' === $var_sale_price[ $var_id ] ) {
					$variation_data['_price'][] = is_numeric( $var_regu_price[ $var_id ] ) ? $var_regu_price[ $var_id ] : '';
				} else {
					$variation_data['_price'][] = is_numeric( $var_sale_price[ $var_id ] ) ? $var_sale_price[ $var_id ] : '';
				}

				$variation_data['_regular_price'][] = is_numeric( $var_regu_price[ $var_id ] ) ? $var_regu_price[ $var_id ] : '';

				if ( ! empty( $sales_to ) ) {
					$variation_data['_sale_price_dates_to'][] = $sales_to[ $var_id ];
				}

				if ( isset( $sales_from ) ) {
					$variation_data['_sale_price_dates_from'][] = $sales_from[ $var_id ];
				}

				$variation_data['_backorders'][] = $backorders[ $var_id ];

				$manage_stock = 'no';
				if ( isset( $manage_stocks ) && isset( $manage_stocks[ $var_id ] ) ) {
					$manage_stock = ( 'yes' === $manage_stocks[ $var_id ] ) ? 'yes' : $manage_stock;
				}

				$variation_data['_manage_stock'][] = $manage_stock;

				if ( 'yes' === $manage_stock ) {
					$variation_data['_stock'][] = $variable_stocks[ $var_id ];
				} else {
					$variation_data['_stock'][]        = '';
					$variation_data['_stock_status'][] = $stocks_status[ $var_id ];
				}

				$var_sku_check = wp_strip_all_tags( $variable_skus[ $var_id ] );

				if ( isset( $variable_skus[ $var_id ] ) && ! empty( $variable_skus[ $var_id ] ) ) {
					$var_data = $wpdb_obj->get_results( $wpdb_obj->prepare( "SELECT meta_id FROM {$wpdb_obj->prefix}postmeta WHERE meta_key='_sku' AND meta_value=%s AND post_id != %d", $var_sku_check, $var_id ) );

					if ( empty( $var_data ) && ! in_array( $variable_skus[ $var_id ], $temp_var_sku, true ) ) {
						$variation_data['_sku'][] = $var_sku_check;
						$temp_var_sku[]           = $var_sku_check;
					} else {
						$variation_data['_sku'][] = '';
						wc_add_notice( esc_html__( 'Invalid or Duplicate SKU.', 'wk-marketplace' ), 'error' );
					}
				} else {
					$variation_data['_sku'][] = '';
				}

				$variation_data['_width'][]        = is_numeric( $variable_widths[ $var_id ] ) ? $variable_widths[ $var_id ] : '';
				$variation_data['_height'][]       = is_numeric( $variable_heights[ $var_id ] ) ? $variable_heights[ $var_id ] : '';
				$variation_data['_length'][]       = is_numeric( $variable_lengths[ $var_id ] ) ? $variable_lengths[ $var_id ] : '';
				$variation_data['_virtual'][]      = $virtual_variable;
				$variation_data['_downloadable'][] = $downloadable_variable;
				$thumbnail_id                      = $variable_img_ids[ $var_id ];

				if ( ! empty( $thumbnail_id ) ) {
					$variation_data['_thumbnail_id'][] = $thumbnail_id;
				} else {
					$variation_data['_thumbnail_id'][] = 0;
				}

				$variation_data['_weight'][]     = is_numeric( $variable_weights[ $var_id ] ) ? $variable_weights[ $var_id ] : '';
				$variation_data['_menu_order'][] = is_numeric( $var_menu_orders[ $var_id ] ) ? $var_menu_orders[ $var_id ] : '';

				/* variation for download able product */
				if ( 'yes' === $downloadable_variable ) {
					$variation_files = $download_file_urls[ $var_id ];
					$variation_names = $download_file_names[ $var_id ];

					if ( isset( $download_file_urls[ $var_id ] ) && count( $download_file_urls[ $var_id ] ) > 0 ) {
						$files = array();

						if ( ! empty( $variation_files ) ) {
							$variation_count = count( $variation_files );
							for ( $i = 0; $i < $variation_count; ++ $i ) {
								$file_url = wp_unslash( trim( $variation_files[ $i ] ) );
								if ( '' !== $file_url ) {
									$files[ md5( $file_url ) ] = array(
										'name' => wc_clean( $variation_names[ $i ] ),
										'file' => $file_url,
									);
								}
							}
						}
						update_post_meta( $var_id, '_downloadable_files', $files );
					}
				}
			}

			$variation_data_key     = array_keys( $variation_data );
			$variations_values      = array_values( $variation_data );
			$variation_data_count   = count( $variation_data );
			$variation_att_id_count = count( $var_attr_ids );

			for ( $i = 0; $i < $variation_data_count; ++ $i ) {
				for ( $x = 0; $x < $variation_att_id_count; ++ $x ) {
					update_post_meta( $var_attr_ids[ $x ], $variation_data_key[ $i ], $variations_values[ $i ][ $x ] );
					if ( '_sale_price' === $variation_data_key[ $i ] && '' === $variations_values[ $i ][ $x ] ) {
						delete_post_meta( $var_attr_ids[ $x ], '_sale_price' );
					}
				}
			}
		}

		/**
		 * Validate product fields before adding or updating the product.
		 *
		 * @param array $data Data.
		 *
		 * @return array
		 */
		public function wkmp_product_validation( $data ) {
			$errors   = array();
			$wpdb_obj = $this->wpdb;

			if ( ! is_numeric( $data['regu_price'] ) && ! empty( $data['regu_price'] ) ) {
				$errors[] = esc_html__( 'Regular Price is not a number.', 'wk-marketplace' );
			}

			if ( ! is_numeric( $data['sale_price'] ) && ! empty( $data['sale_price'] ) ) {
				$errors[] = esc_html__( 'Sale Price is not a number.', 'wk-marketplace' );
			}

			if ( ! is_numeric( $data['wk-mp-stock-qty'] ) && ! empty( $data['wk-mp-stock-qty'] ) ) {
				$errors[] = esc_html__( 'Stock Quantity is not a number.', 'wk-marketplace' );
			}

			$posted_sku = empty( $data['product_sku'] ) ? '' : $data['product_sku'];
			$sell_pr_id = empty( $data['sell_pr_id'] ) ? 0 : intval( $data['sell_pr_id'] );

			if ( ! empty( $posted_sku ) ) {
				$dynamic_sku_prefix = empty( $data['dynamic_sku_prefix'] ) ? '' : $data['dynamic_sku_prefix'];

				$sku_post_id = $wpdb_obj->get_var( $wpdb_obj->prepare( "SELECT post_id FROM {$wpdb_obj->prefix}postmeta WHERE meta_key='_sku' AND meta_value=%s", $posted_sku ) );

				if ( intval( $sku_post_id ) > 0 && $data['dynamic_sku_enabled'] && ! empty( $dynamic_sku_prefix ) ) {
					$post_sku_prefix = get_post_meta( $sku_post_id, '_sku_prefix', true );
					$sku_post_id     = ( $post_sku_prefix === $dynamic_sku_prefix );
				}

				$prod_sku = ( $sell_pr_id > 0 ) ? get_post_meta( $sell_pr_id, '_sku', true ) : '';

				if ( $prod_sku !== $posted_sku && ! empty( $sku_post_id ) ) {
					$errors[] = esc_html__( 'Invalid or Duplicate SKUs.', 'wk-marketplace' );
				}
			}

			return apply_filters( 'wkmp_product_validation_errors', $errors, $data );
		}

		/**
		 * Update product category.
		 *
		 * @param int $cat_id Category id.
		 * @param int $post_id Post id.
		 *
		 * @return void
		 */
		public function wkmp_update_pro_category( $cat_id, $post_id ) {
			if ( is_array( $cat_id ) && array_key_exists( '1', $cat_id ) ) {
				wp_set_object_terms( $post_id, $cat_id, 'product_cat' );
			} elseif ( is_array( $cat_id ) ) {
				$term = get_term_by( 'slug', $cat_id[0], 'product_cat' );
				wp_set_object_terms( $post_id, $term->term_id, 'product_cat' );
			}
		}

		/**
		 * Get product image.
		 *
		 * @param int    $pro_id int prod id.
		 * @param string $meta_value meta value.
		 *
		 * @return string $product_image
		 */
		public function wkmp_get_product_image( $pro_id, $meta_value ) {
			$p = get_post_meta( $pro_id, $meta_value, true );
			if ( is_null( $p ) ) {
				return '';
			}

			return get_post_meta( $p, '_wp_attached_file', true );
		}

		/**
		 * Marketplace media fix.
		 *
		 * @param string $post_id Post Id.
		 */
		public function wkmp_marketplace_media_fix( $post_id = '' ) {
			global $frontier_post_id;
			$frontier_post_id = $post_id;
			add_filter( 'media_view_settings', array( $this, 'wkmp_marketplace_media_fix_filter' ), 10, 2 );
		}

		/**
		 * Fix insert media editor button filter.
		 *
		 * @param array $settings setting array.
		 * @param int   $post post.
		 */
		public function wkmp_marketplace_media_fix_filter( $settings, $post ) {
			global $frontier_post_id;
			$settings['post']['id'] = $frontier_post_id;

			return $settings;
		}

		/**
		 * Display attribute variations
		 *
		 * @param int $var_id variable id.
		 *
		 * @return void
		 */
		public function wkmp_attributes_variation( $var_id ) {
			$wk_pro_id = $var_id;
			$args      = array(
				'post_parent'    => $wk_pro_id,
				'post_type'      => 'product_variation',
				'posts_per_page' => - 1,
				'post_status'    => 'publish',
			);

			$children_array = get_children( $args );

			$i = 0;

			foreach ( $children_array as $var_att ) {
				$this->wkmp_attribute_variation_data( $var_att->ID, $wk_pro_id );
				$i ++;
			}
		}

		/**
		 * Include variations HTML
		 *
		 * @param int $var_id variable id.
		 * @param int $wk_pro_id variable id.
		 *
		 * @return void
		 */
		public function wkmp_attribute_variation_data( $var_id, $wk_pro_id ) {
			require __DIR__ . '/wkmp-variations.php';
		}

		/**
		 * WordPress text input
		 *
		 * @param int $field Field.
		 * @param int $wk_pro_id Product id.
		 *
		 * @return void
		 */
		public function wkmp_wp_text_input( $field, $wk_pro_id ) {
			global $post;

			$the_post_id            = empty( $wk_pro_id ) ? $post->ID : $wk_pro_id;
			$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
			$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short';
			$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
			$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
			$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $the_post_id, $field['id'], true );
			$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
			$field['type']          = isset( $field['type'] ) ? $field['type'] : 'text';
			$data_type              = empty( $field['data_type'] ) ? '' : $field['data_type'];

			switch ( $data_type ) {
				case 'price':
					$field['class'] .= ' wc_input_price';

					$field['value'] = wc_format_localized_price( $field['value'] );
					break;
				case 'decimal':
					$field['class'] .= ' wc_input_decimal';

					$field['value'] = wc_format_localized_decimal( $field['value'] );
					break;
				case 'stock':
					$field['class'] .= ' wc_input_stock';

					$field['value'] = wc_stock_amount( $field['value'] );
					break;
				case 'url':
					$field['class'] .= ' wc_input_url';

					$field['value'] = esc_url( $field['value'] );
					break;
				default:
					break;
			}

			// Custom attribute handling.
			$custom_attributes = array();

			if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
				foreach ( $field['custom_attributes'] as $attribute => $value ) {
					$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
				}
			}

			$custom_attributes = implode( ' ', $custom_attributes );

			echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label><input type="' . esc_attr( $field['type'] ) . '" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['value'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" ' . esc_attr( $custom_attributes ) . ' /> ';

			if ( ! empty( $field['description'] ) ) {
				if ( isset( $field['desc_tip'] ) && false !== $field['desc_tip'] ) {
					echo wp_kses(
						wc_help_tip( $field['description'] ),
						array(
							'span' => array(
								'tabindex'   => array(),
								'aria-label' => array(),
								'data-tip'   => array(),
								'class'      => array(),
							),
						)
					);
				} else {
					echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
				}
			}
			echo '</p>';
		}
	}
}
