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
	 * Class constructor.
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

		global $wwpdf_logs;

		// more rigorous check than nonce:
		if ( ! current_user_can( 'manage_shop_settings' ) ) {
			return;
		}

		if ( isset( $_REQUEST['wwpdf-download-debug-log'] ) ) {

			nocache_headers();

			header( 'Content-Type: text/plain' );
			header( 'Content-Disposition: attachment; filename="wwpdf-debug-log.txt"' );

			echo wp_strip_all_tags( $_REQUEST['wwpdf-debug-log-contents'] );
			exit;

		} elseif ( isset( $_REQUEST['wwpdf-clear-debug-log'] ) ) {

			// First a quick security check
			check_ajax_referer( 'wwpdf-logging-nonce', 'wwpdf_logging_nonce' );

			// Clear the debug log.
			$wwpdf_logs->clear_log_file();

			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=waterwoo-pdf&section=log_settings' ) );
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
		$terms = [
			'error', 'file_download', 'api_request',
		];
		return apply_filters( 'wwpdf_log_types', $terms );
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
			'labels'                => [ 'name' => __( 'Logs', 'wp-logging' ) ],
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
		$this->filename = wp_hash( home_url( DIRECTORY_SEPARATOR ) ) . '-wwpdf-debug.log';
		$this->file     = trailingslashit( $upload_dir['basedir'] ) . $this->filename;
		if ( ! is_writeable( $upload_dir['basedir'] ) ) {
			$this->is_writable = false;
		}

	}

	/**
	 * Check if a log type is valid
	 *
	 * Checks to see if the specified type is in the registered list of types
	 *
	 * @param string $type
	 * @access private
	 * @return bool
	 */
	private static function valid_type( $type ) {
		return in_array( $type, self::log_types() );
	}


	/**
	 * Create new log entry
	 *
	 * This is just a simple and fast way to log something. Use self::insert_log()
	 * if you need to store custom meta data
	 *
	 * @param string $title
	 * @param string $message
	 * @param int $parent
	 * @param string $type
	 * @access  private
	 * @return int The ID of the new log entry
	 */
	public function add( $title = '', $message = '', $parent = 0, $type = null ) {

		$log_data = [
			'post_title'    => $title,
			'post_content'  => $message,
			'post_parent'   => $parent,
			'log_type'      => $type
		];
		return $this->insert_log( $log_data );

	}

	/**
	 * Stores a log entry
	 *
	 * @param array $log_data
	 * @param array $log_meta
	 * @access private
	 * @return int The ID of the newly created log item
	 */
	public function insert_log( $log_data = [], $log_meta = [] ) {

		$defaults = array(
			'post_type'     => 'wwpdf_log',
			'post_status'   => 'publish',
			'post_parent'   => 0,
			'post_content'  => '',
			'log_type'      => false
		);

		$args = wp_parse_args( $log_data, $defaults );
		do_action( 'wwpdf_pre_insert_log' );
		// store the log entry
		$log_id = wp_insert_post( $args );
		// set the log type, if any
		if( $log_data['log_type'] && self::valid_type( $log_data['log_type'] ) ) {
			wp_set_object_terms( $log_id, $log_data['log_type'], 'wwpdf_log_type', false );
		}
		// set log meta, if any
		if( $log_id && ! empty( $log_meta ) ) {
			foreach( (array) $log_meta as $key => $meta ) {
				update_post_meta( $log_id, '_wwpdf_log_' . sanitize_key( $key ), $meta );
			}
		}
		do_action( 'wwpdf_post_insert_log', $log_id );
		return $log_id;

	}

	/**
	 * Easily retrieves log items for a particular object ID
	 *
	 * @param int $object_id
	 * @param string $type
	 * @param string $paged
	 * @access private
	 * @return array
	 */
	public function get_logs( $object_id = 0, $type = null, $paged = null ) {
		return $this->get_connected_logs( array( 'post_parent' => $object_id, 'paged' => $paged, 'log_type' => $type ) );

	}

	/**
	 * Retrieve all connected logs
	 *
	 * Used for retrieving logs related to particular items, such as a specific purchase.
	 *
	 * @param array $args
	 * @access  private
	 * @return array|false
	 */
	public static function get_connected_logs( $args = [] ) {

		$defaults = array(
			'post_parent'       => 0,
			'post_type'         => 'wwpdf_log',
			'posts_per_page'    => 10,
			'post_status'       => 'publish',
			'paged'             => get_query_var( 'paged' ),
			'log_type'          => false
		);
		$query_args = wp_parse_args( $args, $defaults );
		if ( $query_args['log_type'] && self::valid_type( $query_args['log_type'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy'  => 'wwpdf_log_type',
					'field'     => 'slug',
					'terms'     => $query_args['log_type']
				)
			);
		}
		$logs = get_posts( $query_args );

		if ( $logs ) {
			return $logs;
		}
		// no logs found
		return false;

	}

	/**
	 * Retrieves number of log entries connected to particular object ID
	 *
	 * @access  private
	 * @uses WP_Query()
	 * @uses self::valid_type()
	 * @return int
	 */
	public static function get_log_count( $object_id = 0, $type = null, $meta_query = null, $date_query = null ) {

		$query_args = array(
			'post_parent'       => $object_id,
			'post_type'         => 'wwpdf_log',
			'posts_per_page'    => -1,
			'post_status'       => 'publish',
			'fields'            => 'ids',
		);
		if ( ! empty( $type ) && self::valid_type( $type ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy'  => 'wwpdf_log_type',
					'field'     => 'slug',
					'terms'     => $type
				)
			);
		}
		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}
		if ( ! empty( $date_query ) ) {
			$query_args['date_query'] = $date_query;
		}
		$logs = new WP_Query( $query_args );
		return (int) $logs->post_count;

	}

	/**
	 * Delete a log
	 *
	 * @uses WWPDF_Logging::valid_type
	 * @param int $object_id (default: 0)
	 * @param string $type Log type (default: null)
	 * @param array $meta_query Log meta query (default: null)
	 * @return void
	 */
	public function delete_logs( $object_id = 0, $type = null, $meta_query = null  ) {

		$query_args = array(
			'post_parent'       => $object_id,
			'post_type'         => 'wwpdf_log',
			'posts_per_page'    => -1,
			'post_status'       => 'publish',
			'fields'            => 'ids',
		);
		if ( ! empty( $type ) && self::valid_type( $type ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy'  => 'wwpdf_log_type',
					'field'     => 'slug',
					'terms'     => $type,
				)
			);
		}
		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}
		$logs = get_posts( $query_args );
		if ( $logs ) {
			foreach ( $logs as $log ) {
				wp_delete_post( $log, true );
			}
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