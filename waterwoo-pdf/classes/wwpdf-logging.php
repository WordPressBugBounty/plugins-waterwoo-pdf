<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class for logging events and errors
 *
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * https://github.com/pippinsplugins/WP-Logging
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
class WWPDF_Logging {

	public $is_writable = true;
	private $filename   = '';
	private $file       = '';

	/**
	 * Class constructor
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {

		add_action( 'init',                     [ $this, 'setup_log_file' ], 0 );

		// Create the log post type
		add_action( 'init',                     [ $this, 'register_post_type' ], 1 );

		// Create types taxonomy and default types
		add_action( 'init',                     [ $this, 'register_taxonomy' ], 1 );

		add_action( 'init',                     [ $this, 'wwpdf_get_actions' ] );
		add_action( 'wwpdf_submit_debug_log',   [ $this, 'wwpdf_submit_debug_log' ] );


	}

	/**
	 * Hooks WWPDF actions, when present in the $_GET superglobal. Every wwpdf_action
	 * present in $_GET is called using WordPress's do_action function. These
	 * functions are called on init.
	 * used for wwpdf_submit_debug_log()
	 * @return void
	 */
	public function wwpdf_get_actions() {

		$key = ! empty( $_POST['wwpdf_action'] ) ? sanitize_key( $_POST['wwpdf_action'] ) : false;

		if ( ! empty( $key ) ) {
			do_action( "wwpdf_{$key}" , $_POST );
		}

	}


	/**
	 * Handles submit actions for the debug log.
	 */
	function wwpdf_submit_debug_log() {

		// more rigorous check than nonce:
		if ( ! current_user_can( 'manage_shop_settings' ) ) {
			return;
		}

		if ( isset( $_REQUEST['wwpdf-download-debug-log'] ) ) {

			nocache_headers();
			header( 'Content-Type: text/plain' );
			header( 'Content-Disposition: attachment; filename="pdfink-debug-log.txt"' );

			echo wp_strip_all_tags( $_REQUEST['wwpdf-debug-log-contents'] );
			exit;

		} elseif ( isset( $_REQUEST['wwpdf-clear-debug-log'] ) ) {

			global $wwpdf_logs;

			// First a quick security check
			check_ajax_referer( 'wwpdf-logging-nonce', 'wwpdf_logging_nonce' );

			// Clear the debug log
			$wwpdf_logs->clear_log_file();

			// Redirect to either Woo or DLM log settings page where request originated
			wp_safe_redirect( site_url() . $_REQUEST['_wp_http_referer'] );
			exit;

		}

	}

	/**
	 * Log types
	 *
	 * Sets up the default log types and allows for new ones to be created
	 *
	 * @access private
	 * @return array
	 * @filter wwpdf_log_types Gives users chance to add log types
	 */
	private static function log_types() {
		return [
			'error', 'warning', 'file_download', 'api_request',
		];
	}

	/**
	 * Registers the wwpdf_log Post Type
	 *
	 * @access	  public
	 *
	 * @return	 void
	 */
	public function register_post_type() {

		/* logs post type */
		$log_args = [
			'labels'                => [ 'name' => __( 'Logs', 'waterwoo-pdf' ) ],
			'public'                => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'show_ui'               => false,
			'query_var'             => false,
			'rewrite'               => false,
			'capability_type'       => 'post',
			'supports'              => [ 'title', 'editor' ],
			'can_export'            => false
		];
		register_post_type( 'wwpdf_log', $log_args );

	}

	/**
	 * Registers the Type Taxonomy
	 *
	 * The Type taxonomy is used to determine the type of log entry
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function register_taxonomy() {

		register_taxonomy( 'wwpdf_log_type', 'wwpdf_log', array( 'public' => false ) );
		$types = self::log_types();
		foreach ( $types as $type ) {
			if ( ! term_exists( $type, 'wwpdf_log_type' ) ) {
				wp_insert_term( $type, 'wwpdf_log_type' );
			}
		}

	}

	/**
	 * Sets up the log file if it is writable
	 *
	 * @return void
	 */
	public function setup_log_file() {

		$upload_dir     = wp_upload_dir();
		$this->filename = wp_hash( home_url( DIRECTORY_SEPARATOR ) ) . '-pdfink-debug.log';
		$this->file     = trailingslashit( $upload_dir['basedir'] ) . $this->filename;
		if ( ! is_writeable( $upload_dir['basedir'] ) ) {
			$this->is_writable = false;
		}

	}

	/**
	 * Retrieve the log data
	 *
	 * @return string
	 */
	public function get_file_contents() {
		return $this->get_file();
	}

	/**
	 * Log message to file
	 *
	 * @param string $message
	 * @return void
	 */
	public function log_to_file( $message = '' ) {

		$message = date( 'Y-n-d H:i:s' ) . ' - ' . $message . "\r\n";
		$this->write_to_log( $message );

	}

	/**
	 * Retrieve the file data is written to
	 *
	 * @return string
	 */
	protected function get_file() {

		$file = '';
		if ( @file_exists( $this->file ) ) {
			if ( ! is_writeable( $this->file ) ) {
				$this->is_writable = false;
			}
			$file = @file_get_contents( $this->file );
		} else {
			@file_put_contents( $this->file, '' );
			@chmod( $this->file, 0664 );
		}
		return $file;

	}

	/**
	 * Write the log message
	 *
	 * @param string $message
	 * @return void
	 */
	protected function write_to_log( $message = '' ) {

		$file = $this->get_file();
		$file .= $message;
		@file_put_contents( $this->file, $file );

	}

	/**
	 * Delete the log file or removes all contents in the log file if we cannot delete it
	 *
	 * @return bool
	 */
	public function clear_log_file() {

		@unlink( $this->file );

		if ( file_exists( $this->file ) ) {
			// it's still there, so maybe server doesn't have delete rights
			chmod( $this->file, 0664 ); // Try to give the server delete rights
			@unlink( $this->file );
			// See if it's still there
			if ( @file_exists( $this->file ) ) {
				// Remove all contents of the log file if we cannot delete it
				if ( is_writeable( $this->file ) ) {
					file_put_contents( $this->file, '' );
				} else {
					return false;
				}
			}
		}
		$this->file = '';
		return true;

	}

	/**
	 * Return the location of the log file that WWPDF_Logging will use.
	 *
	 * Note: Do not use this file to write to the logs, please use the `wwpdf_debug_log` function to do so.
	 *
	 * @return string
	 */
	public function get_log_file_path() {
		return $this->file;
	}

} // End class WWPDF_Logging