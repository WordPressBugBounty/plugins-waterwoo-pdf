<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class for logging events and errors
 *
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * https://github.com/pippinsplugins/WP-Logging
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
class WWPDF_Logging {

	private $file = '';

	/**
	 * Class constructor
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {

		add_action( 'init',                             [ $this, 'setup_log_file' ], 0 );

		// Create the log post type
		add_action( 'init',                             [ $this, 'register_post_type' ], 1 );

		// Create types taxonomy and default types
		add_action( 'init',                             [ $this, 'register_taxonomy' ], 1 );

		add_action( 'wp_ajax_wwpdf_debug_log_action',   [ $this, 'wwpdf_ajax_debug_log' ] );

	}

	/**
	 * Sets up the log file if it is writable
	 *
	 * @return void
	 */
	public function setup_log_file() {

		$upload_dir = wp_upload_dir();
		$filename   = wp_hash( home_url( DIRECTORY_SEPARATOR ) ) . '-pdfink-debug.log';
		$this->file = trailingslashit( $upload_dir['basedir'] ) . $filename;

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
	 * @return void
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
	 * AJAX handling of log requests
	 *
	 * @return void
	 */
	public function wwpdf_ajax_debug_log() {

		check_ajax_referer( 'wwpdf-logging-nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Not permitted.', 'waterwoo-pdf' ) ], 403 );
		}

		$task = isset( $_POST['task'] ) ? sanitize_key( wp_unslash( $_POST['task'] ) ) : '';
		global $wwpdf_logs;

		switch ( $task ) {
			case 'clear':
				$cleared = $wwpdf_logs->clear_log_file();
				if ( $cleared ) {
					wp_send_json_success( [ 'message' => __( 'Log cleared.', 'waterwoo-pdf' ), 'contents' => '', ] );
				} else {
					wp_send_json_error( [ 'message' => __( 'Log not cleared.', 'waterwoo-pdf' ) ] );
				}

			case 'download':
				wp_send_json_success( [
					'contents' => $wwpdf_logs->get_file_contents(),
				] );

			default:
				wp_send_json_error( [ 'message' => __( 'Unknown task.', 'waterwoo-pdf' ) ], 400 );
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

}