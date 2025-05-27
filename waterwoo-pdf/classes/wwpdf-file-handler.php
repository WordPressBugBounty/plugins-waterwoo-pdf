<?php

use WPChill\DownloadMonitor\Shop\Services\Services as Services;

defined( 'ABSPATH' ) || exit;

final class WWPDF_Free_File_Handler {

	/**
	 * @var string
	 */
	private $watermarked_file;

	/**
	 * @var string
	 */
	protected $email;

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->watermarked_file = '';

		// Filter the file download path - WooCommerce
		add_filter( 'woocommerce_download_product_filepath',    [ $this, 'dispatch_woo' ], 50, 5 );

		// Download Monitor
		add_filter( 'dlm_file_path',                            [ $this, 'dispatch_dlm' ], 10, 3 );

		// Filter for core Easy Digital Downloads (EDD) and EDD All Access
		if ( isset( $_GET['eddfile'] ) || isset( $_GET['edd-all-access-download'] ) ) {
			add_filter( 'edd_requested_file',                   [ $this, 'dispatch_edd' ], 15, 4 );
		}

		if ( apply_filters( 'wwpdf_do_cleanup', true ) ) {
			$this->do_cleanup();
		}

	}

	/**
	 *
	 * @throws Exception if watermarking fails in WWPDF_Watermark
	 * @param string $file_path - has already perhaps been filtered by 'woocommerce_product_file_download_path'
	 * @param string $email
	 * @param object $order
	 * @param object $product
	 * @param object $download
	 *
	 * @return void|string $file_path
	 */
	public function dispatch_woo( $file_path, $email, $order, $product, $download ) {

		if ( apply_filters_deprecated( 'wwpdf_skip_watermarking', [ false, $file_path, $email, $order, $product, $download ], '6.7', 'wwpdf_abort_watermarking' ) ) {
			return $file_path;
		}
		if ( apply_filters( 'wwpdf_abort_watermarking', false, $file_path, $email, $order, $product, $download ) ) {
			return $file_path;
		}

		$requested_filename = $this->get_requested_filename( $file_path );

		$global_on = get_option( 'wwpdf_global', 'no' );
		$file_list = sanitize_textarea_field( get_option( 'wwpdf_files', '' ) );

		$file_array = apply_filters( 'wwpdf_filter_file_list', array_filter( array_map( 'trim', explode( PHP_EOL, $file_list ) ) ), $email, $order );
		$file_listed = in_array( $requested_filename, $file_array );

		$v4_method = get_option( 'wwpdf_files_v4', 'no' );
		if ( 'yes' === $v4_method ) {
			if ( ( 'yes' === $global_on && $file_listed ) || ( 'no' === $global_on && ! $file_listed ) ) {
				wwpdf_debug_log( $requested_filename . 'PDF not set to be watermarked', 'warning' );
				return $file_path;
			}
		} else {
			if ( 'yes' !== $global_on ) {
				return $file_path;
			}
		}

		$order_id = $order->get_id();
		$product_id = $product->get_id();
		$this->email = $email;


		$file = $this->dispatch( 'woo', $file_path, $order_id, $product_id );
		return apply_filters_deprecated( 'wwpdf_filter_watermarked_file', [ $file, $email, $order, $product, $download ], '6.0', '', '' );

	}

	/**
	 *
	 * @param string $file_path
	 * @param boolean $remote_file
	 * @param object $download
	 *
	 * @return string
	 */
	public function dispatch_dlm( $file_path, $remote_file, $download ) {

		// Sorry, the free version of PDF Ink (pdfink.com) doesn't handle remote PDF files
		// Upgrade at www.pdfink.com to handle files not hosted on your server.
		if ( $remote_file ) {
			wwpdf_debug_log( '(PDF Ink Lite) The free version of PDF Ink (pdfink.com) doesn\'t handle remotely-hosted PDF files.', 'warning' );
			return $file_path;
		}

		// Try to get $file_path if missing -- unlikely
		if ( empty( $file_path ) ) {
			$_file_path = $download->get_version()->get_url();
			if ( empty( $_file_path ) ) {
				wwpdf_debug_log( '(PDF Ink Lite) File path empty inside `dlm_file_path` hook. PDF manipulation aborted.', 'error' );
				// Pass this problem back to DLM
				return $file_path;
			} else {
				$file_path = $_file_path;
			}
		}

		$global_on = get_option( 'dlm_stamper_global' ); // '1'
		$file_list = sanitize_textarea_field( get_option( 'dlm_stamper_files', '' ) );

		if ( false == $global_on && empty( $file_list ) ) { // Quick check to abort early
			return $file_path;
		}

		$requested_filename = $this->get_requested_filename( $file_path );
		$order_id = $_GET['order_id'] ?? false;
		$file_array = apply_filters( 'wwpdf_filter_file_list', array_filter( array_map( 'trim', explode( PHP_EOL, $file_list ) ) ), $_GET, $order_id );
		$file_listed = in_array( $requested_filename, $file_array );

		if ( ( $global_on == true && $file_listed ) || ( $global_on == false && ! $file_listed ) ) {
			wwpdf_debug_log( '(PDF Ink Lite) ' . $requested_filename . ' not set to be watermarked', 'warning' );
			return $file_path;
		}

		$order_id = $_GET['order_id'] ?? false;
		$download_id = $download->get_version()->get_id();

		// Download Monitor
		return $this->dispatch( 'dlm', $file_path, $order_id, $download_id );

	}

	/**
	 * @param string $file_path
	 * @param $download_files
	 * @param $file_key
	 * @param array $args
	 *
	 * @return string
	 */
	public function dispatch_edd( $file_path, $download_files, $file_key, $args ) {

		if ( empty( $file_path ) ) {
			edd_debug_log( '(PDF Ink Lite) File path empty inside `edd_requested_file` hook. PDF manipulation aborted.' );
			// Pass this problem back to EDD
			return $file_path;
		}

		$global_on = edd_get_option( 'eddimark_global' );
		$file_list = edd_get_option( 'eddimark_files', '' );

		if ( false == $global_on && empty( $file_list ) ) { // quick check
			edd_debug_log( '(PDF Ink Lite) PDF not watermarked. Watermarking not turned on for this file.' );
			return $file_path;
		}

		$requested_filename = $this->get_requested_filename( $file_path );
		$file_array = apply_filters( 'wwpdf_filter_file_list', array_filter( array_map( 'trim', explode( PHP_EOL, $file_list ) ) ), $args );
		$file_listed = in_array( $requested_filename, $file_array );

		if ( ( $global_on == true && $file_listed ) || ( $global_on == false && ! $file_listed ) ) {
			edd_debug_log( '(PDF Ink Lite) PDF not set to be watermarked' );
			return $file_path;
		}

		// Easy Digital Downloads
		return $this->dispatch( 'edd', $file_path, $args['payment'], $args['download']  );

	}

	/**
	 * @param string $source
	 * @param string $file_path
	 * @param int|string $order_id
	 * @param int $product_id
	 *
	 * @return mixed|void
	 */
	protected function dispatch( $source, $file_path, $order_id, $product_id ) {

		// Check if it's a PDF
		if ( 'edd' === $source && function_exists( 'edd_get_file_extension' ) ) {
			if ( 'pdf' !== strtolower( edd_get_file_extension( $file_path ) ) ) {
				edd_debug_log( '(PDF Ink Lite) ' . $file_path . ' does not seem to be a PDF file.' );
				return $file_path;
			}
		} else {
			$file_extension = preg_replace( '/\?.*/', '', substr( strrchr( $file_path, '.' ), 1 ) );
			if ( 'pdf' !== strtolower( $file_extension ) ) {
				wwpdf_debug_log( $file_path . ' does not seem to be a PDF file.', 'warning' );
				return $file_path;
			}
		}

		try {

			$content                = $this->get_content( $source, $order_id, $product_id );
			$settings               = $this->get_settings( $source );
			$settings['content']    = $content;
			$settings['email']      = $this->email;
			$settings['source']     = $source;

			return $this->maybe_apply_watermark( $source, $file_path, $settings, $order_id );

		} catch ( \Exception $e ) {

			$error_message = $e->getMessage();
			if ( 'edd' === $source ) {
				edd_debug_log( '(PDF Ink Lite) Caught exception: ' . print_r( $error_message, true ) );
			} else {
				wwpdf_debug_log( 'Caught exception: ' . $error_message, 'warning' );
			}
			if ( apply_filters( 'wwpdf_serve_unwatermarked_file', false, $file_path ) ) {
				return $file_path;
			} else {
				wp_die( apply_filters( 'wwpdf_filter_exception_message', __( 'Sorry, we were unable to prepare this file for download! Please notify site administrator. An error has been logged on their end.', 'waterwoo-pdf' ), $error_message, $file_path ), '', [ 'back_link' => true ] );
			}

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
	 * @return void
	 * @deprecated in PDF Watermark v4.0
	 */
	public function pdf_filepath( $file_path, $email, $order, $product, $download ) {
		// Sorry guys
	}

	/**
	 * @param string $file_path
	 *
	 * @return string
	 */
	protected function get_requested_filename( $file_path ) {

		$name = basename( $file_path );
		if ( $strpos = strpos( $name, '?' ) ) {
			$name = substr( $name, 0, $strpos );
		}
		return $name;

	}

	/**
	 * Parses watermark content and replaces shortcodes if necessary
	 *
	 * @param int $order_id
	 * @param int $product_id
	 * @return boolean|string $content
	 */
	public function get_content( $source, $order_id, $product_id ) {

		$email = '';
		$paid_date = current_time( 'timestamp' );
		if ( 'woo' === $source ) {
			$content = sanitize_text_field( get_option( 'wwpdf_footer_input_premium', 'Licensed to [FIRSTNAME] [LASTNAME], [EMAIL]' ) );
			if ( empty( $content ) ) {
				return '';
			}
			if ( function_exists( 'wc_get_order' ) && $order = wc_get_order( $order_id ) ) {
				$order_data = $order->get_data();
				$first_name = $order_data['billing']['first_name'] ?? '';
				$last_name  = $order_data['billing']['last_name'] ?? '';
				$phone      = $order_data['billing']['phone'] ?? '';
				$paid_date  = $order_data['date_created']->date( 'Y-m-d H:i:s' ) ?? '';
			}

		} else if ( 'dlm' === $source ) {
			$content = sanitize_text_field( get_option( 'dlm_stamper_stamp', '' ) );
			if ( empty( $content ) ) {
				return '';
			}
			if ( $order_id ) {
				$order = $this->get_dlm_order( $order_id );
				if ( $order ) {
					$email      = $order->get_customer()->get_email() ?? '';
					$first_name = $order->get_customer()->get_first_name() ?? '';
					$last_name  = $order->get_customer()->get_last_name() ?? '';
					$phone      = $order->get_customer()->get_phone() ?? '';
					$paid_date  = $order->get_date_created()->format( 'Y-m-d H:i:s' ) ?? '';
				}
			}
			$this->email = $email;

		} else if ( 'edd' === $source ) {

			$content = sanitize_text_field( edd_get_option( 'eddimark_f_input', '' ) );
			if ( empty( $content ) ) {
				return '';
			}
			if ( ! empty( $order_id ) ) {
				$user_info = edd_get_payment_meta_user_info( $order_id );
				$paid_date = edd_get_payment_completed_date( $order_id );
			}
			if ( ! empty( $user_info ) ) { // $user_info comes with a Payment ID and gives more accurate info
				$first_name = $user_info['first_name'] ?? '';
				$last_name  = $user_info['last_name'] ?? '';
				$email      = $user_info['email'] ?? '';
				$phone      = $user_info['phone'] ?? '';
			}
			$this->email = $email;

		}

		// if current user is logged in we can get some details about them...
		if ( is_user_logged_in() && $user = wp_get_current_user() ) {
			if ( 'woo' !== $source && empty( $email ) ) {
				$email = $user->user_email ?? '';
				$this->email = $email;
			}
			if ( empty( $first_name ) ) {
				$first_name = $user->user_firstname ?? '';
			}
			if ( empty( $last_name ) ) {
				$last_name = $user->user_lastname ?? '';
			}
			if ( empty( $phone ) ) {
				$phone = get_user_meta( $user->ID, 'billing_phone', true ) ?? '';
			}

		}

		$date_format    = get_option( 'date_format' );
		$paid_date      = date_i18n( $date_format, strtotime( $paid_date ) );
		$timestamp      = date_i18n( $date_format, current_time( 'timestamp' ) );

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
			'pdfink_filter_woo_magic_tags',
			'The `pdfink_filter_woo_magic_tags` filter hook is included in the upgrade of this plugin at pdfink.com. `wwpdf_filter_shortcodes` will be removed soon from the Lite plugin version.'
		);

		foreach ( $shortcodes as $shortcode => $value ) {
			if ( ! empty( $value ) ) {
				$content = str_replace( $shortcode, $value, $content );
			} else {
				$content = str_replace( $shortcode, '', $content );
			}
		}

		$content = apply_filters_deprecated( 'wwpdf_filter_footer', [ $content, $order_id, $product_id ], '6.3', 'pdfink_filter_placement_content', 'The `pdfink_filter_placement_content` filter hook is included in the upgrade of this plugin at pdfink.com. `wwpdf_filter_footer` will be removed soon from the Lite plugin version.' );

		// Text encode before returning
		return html_entity_decode( $content, ENT_QUOTES | ENT_XML1, 'UTF-8' );

	}

	/**
	 * @param $source
	 *
	 * @return array
	 */
	protected function get_settings( $source ) {

		$settings = [];
		if ( 'woo' === $source ) {

			$settings['margin_lr']      = absint( sanitize_text_field( get_option( 'wwpdf_margin_left_right', 0 ) ) );
			$settings['font_face']      = sanitize_text_field( get_option( 'wwpdf_font_premium', 'dejavusans' ) );
			$settings['font_size']      = absint( sanitize_text_field( get_option( 'wwpdf_footer_size_premium', 12 ) ) );
			$settings['font_color']     = sanitize_text_field( get_option( 'wwpdf_footer_color_premium', '#000000' ) );
			$settings['y_adjuster']     = sanitize_text_field( get_option( 'wwpdf_footer_finetune_Y_premium' ) );
			$settings['password']       = get_option( 'wwpdf_password', '' ); // @todo sanitize password?
			$settings['disable_print']  = sanitize_text_field( get_option( 'wwpdf_disable_printing', 'no' ) ); // @todo  is this checkbox "no" yes? or? Verify.
			$settings['disable_mods']   = sanitize_text_field( get_option( 'wwpdf_disable_mods', 'no' ) );
			$settings['disable_copy']   = sanitize_text_field( get_option( 'wwpdf_disable_copy', 'no' ) );
			$settings['disable_annot']  = sanitize_text_field( get_option( 'wwpdf_disable_annot', 'no' ) );

		} else if ( 'dlm' === $source ) {

			$settings['margin_lr']      = absint( sanitize_text_field( get_option( 'dlm_stamper_margin_lr', 0 ) ) );
			$settings['font_face']      = sanitize_text_field( get_option( 'dlm_stamper_font', 'dejavusans' ) );
			$settings['font_size']      = absint( sanitize_text_field( get_option( 'dlm_stamper_size', 12 ) ) );
			$settings['font_color']     = sanitize_text_field( get_option( 'dlm_stamper_color', '#000000' ) );
			$settings['y_adjuster']     = sanitize_text_field( get_option( 'dlm_stamper_finetune_Y' ) );
			$settings['password']       = get_option( 'dlm_stamper_pwd', '' ); // @todo sanitize password?
			$settings['disable_print']  = sanitize_text_field( get_option( 'dlm_stamper_dis_printing', 'no' ) ); // @todo  is this checkbox "no" yes? or? Verify.
			$settings['disable_mods']   = sanitize_text_field( get_option( 'dlm_stamper_dis_mods', 'no' ) );
			$settings['disable_copy']   = sanitize_text_field( get_option( 'dlm_stamper_dis_copy', 'no' ) );
			$settings['disable_annot']  = sanitize_text_field( get_option( 'dlm_stamper_dis_annot', 'no' ) );

		} else if ( 'edd' === $source ) {

			$settings['margin_lr']      = absint( sanitize_text_field( get_option( 'eddimark_margin_left_right', 0 ) ) );
			$settings['font_face']      = sanitize_text_field( edd_get_option( 'eddimark_font', 'dejavusans' ) );
			$settings['font_size']      = absint( sanitize_text_field( edd_get_option( 'eddimark_f_size', 12 ) ) );
			$settings['font_color']     = sanitize_text_field( edd_get_option( 'eddimark_f_color', '#000000' ) );
			$settings['y_adjuster']     = sanitize_text_field( edd_get_option( 'eddimark_f_finetune_Y' ) );
			$settings['password']       = edd_get_option( 'eddimark_pw', '' ); // @todo sanitize password?
			$settings['disable_print']  = sanitize_text_field( edd_get_option( 'eddimark_disable_print', 'no' ) ); // @todo  is this checkbox "no" yes? or? Verify.
			$settings['disable_mods']   = sanitize_text_field( edd_get_option( 'eddimark_disable_mods', 'no' ) );
			$settings['disable_copy']   = sanitize_text_field( edd_get_option( 'eddimark_disable_copy', 'no' ) );
			$settings['disable_annot']  = sanitize_text_field( edd_get_option( 'eddimark_disable_annot', 'no' ) );

		}
		return $settings;

	}

	/**
	 *
	 * @param string $source
	 * @param string $file_path
	 * @param $order_id
	 *
	 * @return mixed|void
	 * @throws Exception
	 */
	public function maybe_apply_watermark( $source, $file_path, $settings, $order_id ) {

		// Determine if file is local or remote, and get path
		$parsed_file_path = $this->parse_file_path( $file_path );

		if ( $parsed_file_path['remote_file'] ) {
			if ( 'edd' === $source ) {
				edd_debug_log( '(PDF Ink Lite) The free version of PDF Ink (pdfink.com) doesn\'t handle remotely-hosted PDF files.' );
			} else {
				wwpdf_debug_log( 'The free version of PDF Ink (pdfink.com) doesn\'t handle remotely-hosted PDF files.', 'error' );
			}
			return $file_path;
		}

		if ( ! empty( $order_id ) ) {
			$watermarked_path = PDFINK_LITE_UPLOADS_PATH . $source . DIRECTORY_SEPARATOR . $order_id;
		} else {
			$watermarked_path = PDFINK_LITE_UPLOADS_PATH . $source . DIRECTORY_SEPARATOR . date( 'Y' ) . DIRECTORY_SEPARATOR . date( 'm' ) . DIRECTORY_SEPARATOR . date( 'd' ) . $order_id;
		}

		if ( ! wp_mkdir_p( $watermarked_path ) || ! is_writable( $watermarked_path ) ) {
			throw new Exception( __( 'The PDF destination folder, ' . $watermarked_path .' is not writable.', 'waterwoo-pdf' ) );
		}

		$_file_path = $parsed_file_path['file_path'];
		if ( function_exists( 'wp_normalize_path' ) ) {
			$_file_path = wp_normalize_path( $_file_path );
		}

		// Attempt to watermark using the open source TCPDI/TCPDF libraries
		// There are other better libraries available which you can use easily if you upgrade to PDF Ink (www.pdfink.com)
		$watermarker = new WWPDF_Watermark( $_file_path, $watermarked_path . DIRECTORY_SEPARATOR . $this->get_requested_filename( $_file_path ), $settings );
		$watermarker->do_watermark();

		$watermarked_file = str_replace( ABSPATH, '', $watermarker->newfile );

		if ( ! file_exists( $watermarked_file ) ) {
			// Revert to original returned file
			$watermarked_file = $watermarker->newfile;
		}
		// @todo cleanup using $this->watermarked file
		$this->watermarked_file = $watermarked_file;

		// Send watermarked file back to WooCommerce
		return $watermarked_file;

	}

	/**
	 * Get Download Monitor order object from order ID
	 *
	 * @param string $order_id
	 *
	 * @return false|\WPChill\DownloadMonitor\Shop\Order\Order
	 */
	protected function get_dlm_order( $order_id ) {

		if ( $order_id && 0 !== strpos( $order_id, 'dlm_' ) ) {
			try {
				$order = Services::get()->service( 'order_repository' )->retrieve_single( $order_id );
				if ( is_a( $order, 'Order' ) ) {
					return $order;
				} else {
					throw new Exception( 'Retrieved order is not a DLM Order object.' );
				}
			} catch ( Exception $e ) {
				wwpdf_debug_log( '(PDF Ink Lite) Unable to get DLM order from order ID: ' . $e->getMessage(), 'error' );
			}
		}
		return false;

	}

	/**
	 * Parse file path and see if it's remote or local
	 *
	 * Borrowed liberally from WooCommerce
	 *
	 * @param  string $file_path
	 * @return array
	 */
	private function parse_file_path( $file_path ) {

		$wp_uploads     = wp_upload_dir();
		$wp_uploads_dir = $wp_uploads['basedir'];
		$wp_uploads_url = $wp_uploads['baseurl'];

		/**
		 * Replace uploads dir, site url etc with absolute counterparts if we can.
		 * Note the str_replace on site_url is on purpose, so if https is forced
		 * via filters we can still do the string replacement on a HTTP file.
		 */
		$replacements = [
			$wp_uploads_url => $wp_uploads_dir,
			network_site_url( '/', 'https' ) => ABSPATH,
			str_replace( 'https:', 'http:', network_site_url( '/', 'http' ) ) => ABSPATH,
			site_url( '/', 'https' ) => ABSPATH,
			str_replace( 'https:', 'http:', site_url( '/', 'http' ) ) => ABSPATH,
		];

		$file_path = str_replace( array_keys( $replacements ), array_values( $replacements ), $file_path );

		$parsed_file_path = wp_parse_url( $file_path );
		$remote_file      = true;

		// Shortcode (e.g. Dropbox, AWS, etc.)
		// @todo use str_starts_with() & str_ends_with() when PHP 8.0+ reached
		if ( '[' === substr( $file_path, 0, 1 ) && ']' === substr( $file_path, -1 ) ) {
			return [
				'remote_file' => true,
				'file_path'   => $file_path,
				'query'       => $parsed_file_path['query'],
				'shortcode'   => true,
			];
		}

		// Paths that begin with '//' are always remote URLs.
		if ( '//' === substr( $file_path, 0, 2 ) ) {
			return [
				'remote_file' => true,
				'file_path'   => is_ssl() ? 'https:' . $file_path : 'http:' . $file_path,
				'query'       => $parsed_file_path['query'],
				'shortcode'   => false,
			];
		}

		// See if path needs an abspath prepended to work.
		// Using $parsed_file_path['path'] removes the query, if there is one
		if ( isset( $parsed_file_path['path'] ) && file_exists( ABSPATH . $parsed_file_path['path'] ) ) {
			$remote_file = false;
			$file_path = ABSPATH . $parsed_file_path['path'];

		} else if ( file_exists( ABSPATH . $file_path ) ) { // same as above but for if the parsing somehow lost the file path
			$remote_file = false;
			$file_path = ABSPATH . $file_path;

		} else if ( isset( $parsed_file_path['path'] ) && '/wp-content' === substr( $parsed_file_path['path'], 0, 11 ) ) {
			$remote_file = false;
			$file_path = realpath( WP_CONTENT_DIR . substr( $parsed_file_path['path'], 11 ) );

		} else if ( '/wp-content' === substr( $file_path, 0, 11 ) ) {
			$remote_file = false;
			$file_path = realpath( WP_CONTENT_DIR . substr( $file_path, 11 ) );

		} elseif ( ( ! isset( $parsed_file_path['scheme'] ) || ! in_array( $parsed_file_path['scheme'], [ 'http', 'https', 'ftp' ], true ) )
		           && isset( $parsed_file_path['path'] )
		) { // We have an absolute path
			$remote_file = false;
			$file_path   = $parsed_file_path['path'];

		} else if ( 0 === strpos( str_replace( ABSPATH, '', $parsed_file_path['path'] ), 'wp-content', 0 ) ) {
			$remote_file = false; // Some other absolute path that includes wp-content... might as well try it?
			$file_path   = $parsed_file_path['path'];

		}

		return [
			'remote_file'   => $remote_file,
			'file_path'     => $file_path,
			'query'         => ! empty( $parsed_file_path['query'] ) ? $parsed_file_path['query'] : '',
			'shortcode'     => false,
		];

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

		// We can only *try* to cleanup if we have a forced download.
		if ( 'force' === get_option( 'woocommerce_file_download_method' ) ) {
			add_action( 'shutdown', [ $this, 'cleanup_file' ] ); // this will not work every time because we cannot know download is complete before PHP shutdown
		}

		// Recommend setting up a cron job to remove watermarked files periodically,
		// but adding a hook here just in case you have other plans. The upgraded version of this plugin
		// includes automatic file cleanup, on a chosen schedule.
		do_action( 'wwpdf_file_cleanup', $this->watermarked_file );

	}

}