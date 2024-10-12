<?php defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WWPDF_File_Handler' ) ) :

	class WWPDF_File_Handler {

		private $watermarked_file;

		/**
		 * Constructor
		 */
		public function __construct() {

			$this->watermarked_file = '';

			// Filter the file download path
			add_filter( 'woocommerce_download_product_filepath', [ $this, 'pdf_filepath' ], 50, 5 );

			if ( apply_filters( 'wwpdf_do_cleanup', true ) ) {
				$this->do_cleanup();
			}

		}

		/**
		 * For WC > 4.0, filters file path to add watermark via TCPDI/TCPDF
		 *
		 * @since 2.7.3
		 * @throws Exception if watermarking fails in WWPDF_Watermark
		 * @param string $file_path - has already perhaps been filtered by 'woocommerce_product_file_download_path'
		 * @param string $email
		 * @param object $order
		 * @param object $product
		 * @param object $download
		 * @return void|string $file_path
		 */
		public function pdf_filepath( $file_path, $email, $order, $product, $download ) {

			// Is the plugin enabled?
			if ( "no" === get_option( 'wwpdf_global', 'no' ) ) {
				return $file_path;
			}

			if ( apply_filters( 'wwpdf_skip_watermarking', false, $file_path, $email, $order, $product, $download ) ) {
				return $file_path;
			}

			// PDF - watermarking - start by checking if it's even a PDF
			$file_extension = preg_replace( '/\?.*/', '', substr( strrchr( $file_path, '.' ), 1 ) );
			if ( 'pdf' !== strtolower( $file_extension ) ) {
				return $file_path;
			}

			$order_id = $order->get_id();

			// Get requested PDF and compare to admin designated PDFs
			$requested_file     = basename( $file_path );
			if ( FALSE !== strpos( $requested_file, '?' ) ) {
				$requested_file = substr( $requested_file, 0, strpos( $requested_file, '?' ) );
			}
			$wwpdf_files        = sanitize_textarea_field( get_option( 'wwpdf_files', '' ) );
			$wwpdf_file_list    = array_filter( array_map( 'trim', explode( PHP_EOL, $wwpdf_files ) ) );
			$wwpdf_file_list    = apply_filters( 'wwpdf_filter_file_list', $wwpdf_file_list, $email, $order );

			// Watermark desired files only
			if ( in_array( $requested_file, $wwpdf_file_list ) || $wwpdf_files == '' ) {

				try {
					// Set up watermark content according to admin settings
					$product_id = $product->get_id();
					$content = $this->wwpdf_setup_watermark( $email, $product_id, $order_id );

					$parsed_file_path = WC_Download_Handler::parse_file_path( $file_path );
					$path = $parsed_file_path['file_path'];
					if ( $parsed_file_path['remote_file'] === true ) {
						wwpdf_debug_log( '(error) Remote PDF file path detected: ' . print_r( $path, true ), 'error' );
						throw new Exception( __( 'The free version of WaterWoo PDF cannot serve PDFs from remote servers.', 'waterwoo-pdf' ) . __( 'Try uploading your PDF product to this domain using the native WooCommerce file uploader.', 'waterwoo-pdf' ) );
					}

					if ( function_exists( 'wp_normalize_path' ) ) {
						$path = wp_normalize_path( $path );
					}

					// Create a unique file name for the new watermarked file
					$time = time();
					$watermarked_path = str_replace( '.pdf', '', $path ) . '_' . $order_id . '_' . $time . '.pdf';
					$watermarked_path = apply_filters_deprecated( 'wwpdf_filter_file_path', [ $watermarked_path, $email, $order, $product, $download, $time ], '6.3', '', 'The `wwpdf_filter_file_path` filter hook is deprecated. Please upgrade to continue manipulating the final file path for watermarked PDFs.' );

					if ( ! is_writable( dirname( $watermarked_path ) ) ) {
						wwpdf_debug_log( '(error) The PDF destination folder is not writable: ' . print_r( $watermarked_path, true ), 'error' );
						throw new Exception( __( 'The PDF destination folder is not writable.', 'waterwoo-pdf' ) );
					}
					// Attempt to watermark using TCPDI/TCPDF
					$watermarker = new WWPDF_Watermark( $path, $watermarked_path, $content, $email );
					$watermarker->do_watermark();

				} catch ( Exception $e ) {
					$error_message = $e->getMessage();
					wwpdf_debug_log( '(error) There was an error: ' . print_r( $error_message, true ), 'error' );
					if ( apply_filters( 'wwpdf_serve_unwatermarked_file', false, $file_path ) ) {
						return $file_path;
					} else {
						wp_die( apply_filters( 'wwpdf_filter_exception_message', __( 'Sorry, we were unable to prepare this file for download! Please notify site administrator. An error has been logged on their end.', 'waterwoo-pdf' ), $error_message, $file_path ), '', [ 'back_link' => true ] );
					}
				}

				$watermarked_file = str_replace( ABSPATH, '', $watermarker->newfile );

				if ( ! file_exists( $watermarked_file ) ) {
					$watermarked_file = $watermarker->newfile;
				}
				$this->watermarked_file = $watermarked_file;

				// Send watermarked file back to WooCommerce
				return apply_filters( 'wwpdf_filter_watermarked_file', $watermarked_file, $email, $order, $product, $download );

			}
			return $file_path;

		}

		/**
		 * Parses watermark content and replaces shortcodes if necessary
		 *
		 * @param string $email
		 * @param int $product_id
		 * @param int $order_id
		 * @return string $content - watermark content
		 */
		public static function wwpdf_setup_watermark( $email, $product_id, $order_id ) {

			$order = wc_get_order( $order_id );
			$order_data = $order->get_data();

			$first_name     = $order_data['billing']['first_name'] ?? '';
			$last_name      = $order_data['billing']['last_name'] ?? '';
			$phone          = $order_data['billing']['phone'] ?? '';
			$paid_date      = $order_data['date_created']->date('Y-m-d H:i:s') ?? '';
			$date_format    = get_option( 'date_format' );
			$paid_date      = date_i18n( $date_format, strtotime( $paid_date ) );
			$timestamp      = date_i18n( $date_format, current_time('timestamp') );

			$content        = sanitize_text_field( get_option( 'wwpdf_footer_input_premium', 'Licensed to [FIRSTNAME] [LASTNAME], [EMAIL]' ) );

			$shortcodes = apply_filters_deprecated(
				'wwpdf_filter_shortcodes',
				[
					[
						'[FIRSTNAME]' => $first_name,
						'[LASTNAME]' => $last_name,
						'[EMAIL]' => $email,
						'[PHONE]' => $phone,
						'[DATE]' => $paid_date,
						'[TIMESTAMP]' => $timestamp,
					],
				$email, $product_id, $order_id ],
				'6.3',
				'',
				'The `wwpdf_filter_shortcodes` filter hook is included in the Premium (paid) version of WaterWoo PDF. Please upgrade to continue using it.'
			);

			foreach ( $shortcodes as $shortcode => $value ) {
				if ( ! empty( $value ) ) {
					$content = str_replace( $shortcode, $value, $content );
				} else {
					$content = str_replace( $shortcode, '', $content );
				}
			}

			$content = apply_filters_deprecated( 'wwpdf_filter_footer', [ $content, $order_id, $product_id ], '6.3', '', 'The `wwpdf_filter_footer` filter hook is included in the Premium (paid) version of WaterWoo PDF. Please upgrade to continue using it.' );

			// Text encoding
			if ( has_filter( 'wwpdf_font_decode' ) ) {
				$content = apply_filters_deprecated( 'wwpdf_font_decode', [ $content ], '6.3', '', 'The `wwpdf_font_decode` filter hook is included in the Premium (paid) version of WaterWoo PDF. Please upgrade to continue using it.' );
			} else {
				$out_charset = 'UTF-8';
				$out_charset = apply_filters_deprecated( 'wwpdf_out_charset', [ $out_charset ], '6.3', '', 'The `wwpdf_out_charset` filter hook is included in the Premium (paid) version of WaterWoo PDF. Please upgrade to continue using it.' );
				$content = html_entity_decode( $content, ENT_QUOTES | ENT_XML1, $out_charset );
			}

			return $content;

		}

		/**
		 * Check if there is a stamped file and maybe delete it
		 *
		 * @return void
		 */
		public function cleanup_file() {

			// This only happens if download type is set to FORCE
			if ( isset( $this->watermarked_file ) && ! empty( $this->watermarked_file->newfile ) ) {
				unlink( $this->watermarked_file->newfile );
				$this->watermarked_file = '';
			}

		}

		/**
		 * Try to clean up files stored locally, if forced download (not guaranteed)
		 * Or set up your own CRON for deletion
		 *
		 * @return void
		 */
		private function do_cleanup() {

			// Force
			if ( 'force' === get_option( 'woocommerce_file_download_method' ) ) {
				add_action( 'shutdown', [ $this, 'cleanup_file' ] ); // this will not work every time because we cannot know download is complete before PHP shutdown
			}
			// recommend setting up a cron job to remove watermarked files periodically,
			// but adding a hook here just in case you have other plans
			do_action( 'wwpdf_file_cleanup', $this->watermarked_file );

		}

	}

endif;