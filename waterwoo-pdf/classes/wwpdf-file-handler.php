<?php

use Automattic\WooCommerce\Internal\Utilities\FilesystemUtil;
use WPChill\DownloadMonitor\Shop\Services\Services as Services;

defined( 'ABSPATH' ) || exit;

final class WWPDF_Free_File_Handler {

	protected string $email = '';

	protected string $filename = '';

	protected string $temp_folder = '';

	/**
	 * Constructor
	 */
	public function __construct() {

		// Filter the file download path - WooCommerce
		add_filter( 'woocommerce_download_product_filepath',    [ $this, 'dispatch_woo' ], 50, 5 );

		// Download Monitor
		add_filter( 'dlm_file_path',                            [ $this, 'dispatch_dlm' ], 10, 3 );

		// Filter for core Easy Digital Downloads (EDD) and EDD All Access
		if ( isset( $_GET['eddfile'] ) || isset( $_GET['edd-all-access-download'] ) ) {
			add_filter( 'edd_requested_file',                   [ $this, 'dispatch_edd' ], 15, 4 );
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
	public function dispatch_woo( string $file_path, string $email, $order, $product, $download ) {

		if ( empty( $file_path ) ) {
			// Maybe just in case someone else using this hook has nuked the $file_path?
			// Pass this problem back to Woo
			return $file_path;
		}

		if ( apply_filters_deprecated( 'wwpdf_abort_watermarking', [ false, $file_path, $email, $order, $product, $download ], '6.0', '', 'The `wwpdf_abort_watermarking` filter hook will stop working in 2026. The full version of PDF Ink includes this hook.' ) ) {
			return $file_path;
		}

		wwpdf_debug_log( '- - - PDF Ink triggered. Starting. - - -' );

		$this->filename = $this->get_filename( $file_path );

		$global_on = get_option( 'wwpdf_global', 'no' );
		$file_list = sanitize_textarea_field( get_option( 'wwpdf_files', '' ) );

		$file_listed = false;
		if ( ! empty( $file_list ) ) {
			$file_array  = apply_filters_deprecated( 'wwpdf_filter_file_list', [ array_filter( array_map( 'trim', explode( PHP_EOL, $file_list ) ) ), $email, $order ], '6.0', '', 'The `wwpdf_filter_file_list` filter hook will stop working in 2026. The full version of PDF Ink includes this hook.' );
			$file_listed = in_array( $this->filename, $file_array );
		}

		$v4_method = get_option( 'wwpdf_files_v4', 'no' );
		if ( 'yes' === $v4_method ) {
			if ( ( 'yes' === $global_on && $file_listed ) || ( 'no' === $global_on && ! $file_listed ) ) {
				wwpdf_debug_log( 'Manipulation is turned off for file: ' . $this->filename );
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

		/**
		 * Sorry folks, `wwpdf_filter_watermarked_file` hook was removed after
		 * being deprecated (with notice) for over a year.
		 * Developer to developer: Please upgrade to PDF Ink to continue forking
		 */
		return $this->dispatch( 'woo', $file_path, $order_id, $product_id );

	}

	/**
	 *
	 * @param string $file_path
	 * @param boolean $remote_file
	 * @param object $download
	 *
	 * @return string
	 */
	public function dispatch_dlm( string $file_path, bool $remote_file, object $download ) {

		// Sorry, the free version of PDF Ink (pdfink.com) doesn't handle remote PDF files
		// Upgrade at www.pdfink.com to handle files not hosted on your server.
		if ( $remote_file ) {
			wwpdf_debug_log( '(PDF Ink Lite) The free version of PDF Ink (pdfink.com) doesn\'t handle remotely-hosted PDF files.' );
			return $file_path;
		}

		// Try to get $file_path if missing -- unlikely
		if ( empty( $file_path ) ) {
			$_file_path = $download->get_version()->get_url();
			if ( empty( $_file_path ) ) {
				wwpdf_debug_log( '(PDF Ink Lite) File path empty inside `dlm_file_path` hook. PDF manipulation aborted.' );
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

		$this->filename = $this->get_filename( $file_path );
		$order_id       = $_GET['order_id'] ?? false;
		$file_array     = apply_filters( 'wwpdf_filter_file_list', array_filter( array_map( 'trim', explode( PHP_EOL, $file_list ) ) ), $_GET, $order_id );
		$file_listed    = in_array( $this->filename, $file_array );

		if ( ( $global_on == true && $file_listed ) || ( $global_on == false && ! $file_listed ) ) {
			wwpdf_debug_log( '(PDF Ink Lite) ' . $this->filename . ' not set to be watermarked' );
			return $file_path;
		}

		$order_id = $_GET['order_id'] ?? false;
		$download_id = $download->get_version()->get_id();

		// Download Monitor
		return $this->dispatch( 'dlm', $file_path, $order_id, $download_id );

	}

	/**
	 *  Handle Easy Digital Downloads dispatching
	 *
	 * @param string $file_path
	 * @param array $download_files
	 * @param int $file_key
	 * @param array $args
	 *
	 * @return string
	 */
	public function dispatch_edd( string $file_path, $download_files, $file_key, $args ) {

		if ( empty( $file_path ) ) {
			edd_debug_log( '(PDF Ink Lite) File path empty inside `edd_requested_file` hook. PDF manipulation aborted.' );
			// Pass this issue back to EDD
			return $file_path;
		}

		$global_on = edd_get_option( 'eddimark_global' );
		$file_list = edd_get_option( 'eddimark_files', '' );

		if ( false == $global_on && empty( $file_list ) ) { // quick check
			edd_debug_log( '(PDF Ink Lite) PDF not watermarked. Watermarking not turned on for this file.' );
			return $file_path;
		}

		$this->filename = $this->get_filename( $file_path );
		$file_array  = apply_filters( 'wwpdf_filter_file_list', array_filter( array_map( 'trim', explode( PHP_EOL, $file_list ) ) ), $args );
		$file_listed = in_array( $this->filename, $file_array );
		$this->email = $args['email'] ?? '';

		if ( ( $global_on == true && $file_listed ) || ( $global_on == false && ! $file_listed ) ) {
			edd_debug_log( '(PDF Ink Lite) PDF not set to be watermarked' );
			return $file_path;
		}

		// Easy Digital Downloads
		return $this->dispatch( 'edd', $file_path, $args['payment'], $args['download'] );

	}

	/**
	 *
	 * @param string $source
	 * @param string $file_path
	 * @param int|string $order_id
	 * @param int $product_id
	 *
	 * @return mixed|void
	 */
	protected function dispatch( string $source, string $file_path, $order_id, $product_id ) {

		// Remove query and/or fragment from file_path
		$stripped_path = preg_replace( '/[?#].*$/', '', $file_path );
		if ( $stripped_path !== $file_path ) {
			wwpdf_debug_log( 'Query string (?) or fragment (#) removed from file path.' );
		}
		$file_path = $stripped_path;

		// Check for and abort if not PDF (by extension)
		$file_extension = $this->get_file_extension( $file_path, $source );
		if ( 'pdf' !== $file_extension ) {
			$message = 'File does not seem to be a PDF file.';
			if ( 'edd' === $source ) {
				edd_debug_log( '(PDF Ink Lite) ' . $message );
			} else {
				wwpdf_debug_log( $message );
			}
			return $file_path;
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
			}
			wwpdf_debug_log( 'Caught exception: ' . $error_message );

			if ( apply_filters( 'wwpdf_serve_unwatermarked_file', false, $file_path ) ) {
				return $file_path;
			} else {
				wp_die( apply_filters( 'wwpdf_filter_exception_message', __( 'Sorry, we were unable to prepare this file for download! Please notify site administrator. An error has been logged on their end.', 'waterwoo-pdf' ), $error_message, $file_path ), '', [ 'back_link' => true ] );
			}

		}

	}

	/**
	 * Find filename based on path
	 * Queries and fragment already removed in dispatch()
	 *
	 * @param string $file_path
	 * @return string
	 */
	protected function get_filename( string $file_path ) {

		$filename = wp_basename( $file_path );
		if ( empty( $filename ) || '.' === $filename || '..' === $filename ) {
			wwpdf_debug_log( 'File name came up empty in method get_filename(). Renamed `untitled.pdf`.' );
			$filename = 'untitled.pdf';
		}
		wwpdf_debug_log( 'Filename is now: ' . $filename );
		return $filename;

	}

	/**
	 * Get the file extension, using native Woo/EDD/DLM
	 * extension-finding methods where possible
	 *
	 * @param string $file_path
	 * @param string $source
	 *
	 * @return string
	 */
	private function get_file_extension( string $file_path, $source ) {

		// Use native extension-finding methods where possible
		if ( 'edd' === $source && function_exists( 'edd_get_file_extension' ) ) {
			$file_extension = edd_get_file_extension( $file_path );
		} elseif ( 'dlm' === $source && class_exists( 'DLM_File_Manager' ) ) {
			$fm = new DLM_File_Manager;
			$info = $fm->mb_pathinfo( $file_path );
			$file_extension = isset( $info['extension'] ) ? $info['extension'] : '';
		} else { // WooCommerce or fallback
			$file_extension = pathinfo( $file_path, PATHINFO_EXTENSION );
		}
		return strtolower( (string) $file_extension );

	}

	/**
	 * Parses watermark content and replaces shortcodes if necessary
	 *
	 * @param int $order_id
	 * @param int $product_id
	 * @return string $content
	 */
	public function get_content( string $source, $order_id, $product_id ) {

		$email = '';
		$paid_date = current_time( 'timestamp' );
		$content   = '';
		$first_name = '';
		$last_name  = '';
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
			$email = $this->email;

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
			'The `pdfink_filter_woo_magic_tags` filter hook is included in the plugin upgrade at pdfink.com. `wwpdf_filter_shortcodes` will stop working in 2026.'
		);

		foreach ( $shortcodes as $shortcode => $value ) {
			if ( ! empty( $value ) ) {
				$content = str_replace( $shortcode, $value, $content );
			} else {
				$content = str_replace( $shortcode, '', $content );
			}
		}
		if ( has_filter( 'wwpdf_filter_footer' ) ) {
			// PLEASE SUPPORT OPEN SOURCE
			wwpdf_debug_log( 'The `wwpdf_filter_footer` hook was deprecated over a year with notice before it was removed. Please use the `pdfink_filter_placement_content` hook available in the PDF Ink upgrade at pdfink.com' );
		}

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
			$settings['password']       = get_option( 'wwpdf_password', '' );
			$settings['disable_print']  = sanitize_text_field( get_option( 'wwpdf_disable_printing', 'no' ) );
			$settings['disable_mods']   = sanitize_text_field( get_option( 'wwpdf_disable_mods', 'no' ) );
			$settings['disable_copy']   = sanitize_text_field( get_option( 'wwpdf_disable_copy', 'no' ) );
			$settings['disable_annot']  = sanitize_text_field( get_option( 'wwpdf_disable_annot', 'no' ) );

		} else if ( 'dlm' === $source ) {

			$settings['margin_lr']      = absint( sanitize_text_field( get_option( 'dlm_stamper_margin_lr', 0 ) ) );
			$settings['font_face']      = sanitize_text_field( get_option( 'dlm_stamper_font', 'dejavusans' ) );
			$settings['font_size']      = absint( sanitize_text_field( get_option( 'dlm_stamper_size', 12 ) ) );
			$settings['font_color']     = sanitize_text_field( get_option( 'dlm_stamper_color', '#000000' ) );
			$settings['y_adjuster']     = sanitize_text_field( get_option( 'dlm_stamper_finetune_Y' ) );
			$settings['password']       = get_option( 'dlm_stamper_pwd', '' );
			$settings['disable_print']  = sanitize_text_field( get_option( 'dlm_stamper_dis_printing', 'no' ) );
			$settings['disable_mods']   = sanitize_text_field( get_option( 'dlm_stamper_dis_mods', 'no' ) );
			$settings['disable_copy']   = sanitize_text_field( get_option( 'dlm_stamper_dis_copy', 'no' ) );
			$settings['disable_annot']  = sanitize_text_field( get_option( 'dlm_stamper_dis_annot', 'no' ) );

		} else if ( 'edd' === $source ) {

			$settings['margin_lr']      = absint( sanitize_text_field( get_option( 'eddimark_margin_left_right', 0 ) ) );
			$settings['font_face']      = sanitize_text_field( edd_get_option( 'eddimark_font', 'dejavusans' ) );
			$settings['font_size']      = absint( sanitize_text_field( edd_get_option( 'eddimark_f_size', 12 ) ) );
			$settings['font_color']     = sanitize_text_field( edd_get_option( 'eddimark_f_color', '#000000' ) );
			$settings['y_adjuster']     = sanitize_text_field( edd_get_option( 'eddimark_f_finetune_Y' ) );
			$settings['password']       = edd_get_option( 'eddimark_pw', '' );
			$settings['disable_print']  = sanitize_text_field( edd_get_option( 'eddimark_disable_print', 'no' ) );
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
		if ( 'woo' === $source ) {
			$parsed_file_path = WC_Download_Handler::parse_file_path( $file_path );
		} else {
			$parsed_file_path = $this->parse_file_path( $file_path );
		}

		if ( $parsed_file_path['remote_file'] ) {
			if ( 'edd' === $source ) {
				edd_debug_log( '(PDF Ink Lite) The free version of PDF Ink (pdfink.com) doesn\'t handle remotely-hosted PDF files.' );
			}
			wwpdf_debug_log( 'The free version of PDF Ink (pdfink.com) doesn\'t handle remotely-hosted PDF files.' );
			return $file_path;
		}

		if ( ! empty( $order_id ) ) {
			$this->temp_folder = PDFINK_LITE_UPLOADS_PATH . $source . DIRECTORY_SEPARATOR . $order_id;
		} else {
			$this->temp_folder = PDFINK_LITE_UPLOADS_PATH . $source . DIRECTORY_SEPARATOR . date( 'Y' ) . DIRECTORY_SEPARATOR . date( 'm' ) . DIRECTORY_SEPARATOR . date( 'd' );
		}

		if ( ! wp_mkdir_p( $this->temp_folder ) || ! is_writable( $this->temp_folder ) ) {
			wwpdf_debug_log( "The PDF destination folder, $this->temp_folder is not writable." );
			throw new Exception( __( "The PDF destination folder, $this->temp_folder is not writable.", 'waterwoo-pdf' ) );
		}

		$_file_path = $parsed_file_path['file_path'];
		if ( function_exists( 'wp_normalize_path' ) ) {
			$_file_path = wp_normalize_path( $_file_path );
		}

		wwpdf_debug_log( 'Attempting to parse then write file. If errors, enable WP_DEBUG_LOG and review the debug.log in wp‑content/' );

		// Attempt to watermark using the open source TCPDI/TCPDF libraries
		// There are other better libraries available which you can use easily if you upgrade to PDF Ink (www.pdfink.com)
		$watermarked_file = $this->temp_folder . DIRECTORY_SEPARATOR . $this->filename;
		try {
			$watermarker = new WWPDF_Watermark( $_file_path, $watermarked_file, $settings );
			$watermarker->do_watermark();
			if ( ! file_exists( $watermarked_file ) ) {
				throw new Error( 'Watermarked file not found at ' . $watermarked_file );
			}
			unset( $watermarker );

		} catch ( Exception $e ) {
			wwpdf_debug_log( '(PDF Ink Lite) Watermarking failed. More info: ' . $e->getMessage() );
		}

		$this->do_cleanup();

		// Prevent this function running on hook again
		remove_filter( 'woocommerce_download_product_filepath', __FUNCTION__ );

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
				wwpdf_debug_log( '(PDF Ink Lite) Unable to get DLM order from order ID: ' . $e->getMessage() );
			}
		}
		return false;

	}

	/**
	 * Parse file path and see if it's remote or local
	 * Used for EDD and DLM
	 *
	 * @param  string $file_path
	 * @return array
	 * @author WooCommerce
	 * @author Canyon Webworks
	 */
	private function parse_file_path( $file_path ) {

		// Abort immediately if remote-type file detected; PDF Ink Lite does not handle these
		if ( '//' === substr( $file_path, 0, 2 ) // Paths that begin with '//' are always remote URLs (EDD & DLM don't handle them natively anyway)
			|| ( '[' === substr( $file_path, 0, 1 ) && ']' === substr( $file_path, - 1 ) ) // Shortcode (e.g. Dropbox, AWS, etc.)
		) {
			return [
				'remote_file' => true,
			];
		}

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

		$count = 0;
		$file_path = str_replace( array_keys( $replacements ), array_values( $replacements ), $file_path, $count );

		if ( extension_loaded( 'mbstring' ) && ! mb_check_encoding( $file_path, 'ASCII' ) ) {
			wwpdf_debug_log( 'Heads up! File path/name contains non-ASCII characters. This can cause trouble in wp_parse_url().' );
		}

		$parsed_file_path = wp_parse_url( $file_path );
		$remote_file      = null === $count || 0 === $count; // Remote file only if there were no replacements.

		// For compatibility with Bedrock, etc
		if ( '' !== ABSPATH && 0 === strpos( WP_CONTENT_DIR, ABSPATH ) ) {
			$wp_content_dirname =  '/' . substr( WP_CONTENT_DIR, strlen( ABSPATH ) );
		} else {
		    $content_url_path = wp_parse_url( content_url(), PHP_URL_PATH );
			$wp_content_dirname = is_string( $content_url_path ) ? $content_url_path : '/wp-content';
		}

		// See if path needs an abspath prepended to work
		if ( file_exists( ABSPATH . $file_path ) ) {
			$remote_file = false;
			$file_path   = ABSPATH . $file_path;

		} elseif ( 0 === strpos( $file_path, $wp_content_dirname ) ) {
			$remote_file = false;
			$file_path   = realpath( WP_CONTENT_DIR . substr( $file_path, strlen( $wp_content_dirname ) ) );

		} else if ( ( ! isset( $parsed_file_path['scheme'] )
			|| ! in_array( $parsed_file_path['scheme'], [ 'http', 'https', 'ftp' ], true ) )
			&& isset( $parsed_file_path['path'] )
		) {
			$remote_file = false;
			$file_path   = $parsed_file_path['path'];
			// (optionally: check existence here and leave remote if missing)
		}

		return [
			'remote_file'       => $remote_file,
			'file_path'         => $file_path,
		];

	}

	/**
	 * Check if there is a stamped file and maybe delete it
	 * This only happens if download type is set to FORCE.
	 *
	 *
	 * @return void
	 */
	public function cleanup_file() {

		$file_path = $this->temp_folder . DIRECTORY_SEPARATOR . $this->filename;
		if ( empty( $file_path ) || ! is_file( $file_path ) ) {
			wwpdf_debug_log( 'Could not delete watermarked PDF: does not exist or is not a regular file.' );
			return;
		}

		$temp_folder = wp_normalize_path( $this->temp_folder );

		if ( ! is_file( $file_path ) ) {
			wwpdf_debug_log( 'Could not delete watermarked PDF: does not exist or is not a regular file.' );
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			wwpdf_debug_log( 'Unable to get WP_Filesystem on board to delete watermarked PDF.' );
			return;
		}

		$creds = request_filesystem_credentials( wp_nonce_url( site_url() ) );

		// If credentials cannot be obtained (rare on uninstall), abort
		if ( ! $creds ) {
			wwpdf_debug_log( 'Credentials to manipulate WP Filesystem not obtained, so cannot delete watermarked PDF.' );
			return;
		}

		// Initialise the global $wp_filesystem object
		if ( ! WP_Filesystem( $creds ) ) {
			// Initialization failed (e.g., host disallows direct access).
			// You could fall back to PHP's native functions, but for safety we stop.
			return;
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem->delete( $file_path )  ) {
			wwpdf_debug_log( 'Unable to delete watermarked PDF (filesystem error).' );
		}

		// Attempt to delete the parent directory – if empty
		$parent_dir = dirname( $file_path );
		// Safety check to make sure directories match
		if ( $parent_dir === $this->temp_folder && is_dir( $parent_dir ) ) {
			// is_dir() + is_readable() + is_writable() checks are implicit in $wp_filesystem->rmdir()
			$wp_filesystem->rmdir( $parent_dir, false ); // false = non‑recursive (only if empty)
		} else {
			wwpdf_debug_log( 'PDF Ink temp dir not removed, maybe because it is not empty.' );
		}

	}

	/**
	 * Try to clean up files stored locally, if forced download (not guaranteed)
	 * Or set up your own CRON for deletion
	 *
	 * @return void
	 */
	private function do_cleanup() {

		// We can only *try* to clean up if we have a forced download, using PHP shutdown
		if ( ( 'force' === get_option( 'woocommerce_file_download_method' ) && doing_filter( 'woocommerce_download_product_filepath' ) )
			|| ( function_exists( 'edd_get_file_download_method' ) && 'direct' === edd_get_file_download_method() && doing_filter( 'edd_requested_file' ) )
		) {
			add_action( 'shutdown', [ $this, 'cleanup_file' ] ); // this will not work every time because we cannot know download is complete before PHP shutdown
		}

		// Recommend setting up a cron job to remove watermarked files periodically,
		// but adding a hook here just in case you have other plans. The upgraded version of this plugin
		// includes automatic file cleanup, on a chosen schedule.
		do_action( 'wwpdf_file_cleanup', $this->temp_folder . DIRECTORY_SEPARATOR . $this->filename, $this->temp_folder );

	}

}