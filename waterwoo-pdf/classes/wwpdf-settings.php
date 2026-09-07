<?php defined( 'ABSPATH' ) || exit;

class WWPDF_Settings {

	public function __construct() {

		add_action( 'admin_enqueue_scripts',                                [ $this, 'admin_enqueue_scripts' ], 11 );

		add_action( 'admin_notices',                                        [ $this, 'admin_notices' ] );

		add_filter( 'plugin_row_meta',                                      [ $this, 'add_support_links' ], 10, 2 );

		add_filter( 'plugin_action_links_waterwoo-pdf/waterwoo-pdf.php',    [ $this, 'plugin_action_links' ] );

		add_action( 'wp_ajax_pdfink_lite_dismiss_notice',                   [ $this, 'ajax_dismiss_notice' ] );

	}

	/**
	 * @param string $page
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts( string $page ) {

		if ( 'woocommerce_page_wc-settings' !== $page && 'download_page_edd-settings' !== $page && 'plugins.php' !== $page ) {
			return;
		}

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_style( 'pdfink-admin', plugins_url( 'assets/css/pdfink-admin' . $suffix . '.css', WWPDF_FILE ), [], WWPDF_FREE_VERSION, 'screen' );

		if ( 'woocommerce_page_wc-settings' === $page ) {
			if ( isset( $_GET['tab'] ) && 'pdf-ink-lite' === $_GET['tab'] ) {
				if ( ! isset( $_GET['section'] ) || ( isset( $_GET['section'] ) && 'more_info' !== $_GET['section'] ) ) {
					wp_dequeue_script( 'woo-connect-notice' );
				}
			}
		}

		wp_enqueue_script( 'pdfink-admin', plugins_url( 'assets/js/pdfink-admin' . $suffix . '.js', WWPDF_FILE ), [], WWPDF_FREE_VERSION );
		$data = [
			'ajax_url'  => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'notice-nonce' ),
		];
		$data_script = 'const pdfInkLiteAjax = ' . wp_json_encode( $data ) . ';';
		wp_add_inline_script( 'pdfink-admin', $data_script, 'before' );

	}

	/**
	 * Politely add our API-fed notice to the Plugins page
	 *
	 * @return void
	 */
	public function admin_notices() {

		global $pagenow;
		if ( $pagenow !== 'plugins.php' ) {
			return;
		}

		if ( ! is_plugin_active( 'waterwoo-pdf/waterwoo-pdf.php' ) ) {
			return;
		}

		if ( defined( 'DISABLE_NAG_NOTICES' ) && DISABLE_NAG_NOTICES === true ) {
			return;
		}

		self::render_remote_banner();

	}


	/**
	 * Maybe show a banner at the top of settings screens with PDF Ink news
	 *
	 * @return void
	 */
	public static function render_remote_banner() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( defined( 'DISABLE_NAG_NOTICES' ) && DISABLE_NAG_NOTICES === TRUE ) {
			return;
		}

		// Check if user has dismissed banner
		if ( get_transient( 'pdfink_lite_notice_' . get_current_user_id() ) ) {
			return;
		}

		// Fetch content (cached for 12 hours)
		$banner_data = self::fetch_remote_content();

		if ( $banner_data && ! empty( $banner_data['html'] ) ) { ?>
			<div id="pdfink-notice" class="notice edd-notice">
				<?php echo wp_kses_post( $banner_data['html'] ); ?> <a href="<?php echo wp_kses_post( $banner_data['cta_link'] ); ?>" target="_blank" rel="noopener"><?php echo wp_kses_post( $banner_data['cta_text'] ); ?></a>
				<button type="button" class="notice-dismiss" data-dismiss="<?php echo esc_attr( $banner_data['dismiss_days'] ) ?? 7; ?>"><span class="screen-reader-text">Dismiss this notice.</span></button>
			</div>
		<?php }

	}

	/**
	 * Call PDF Ink API for news update
	 *
	 * @return mixed|null
	 */
	private static function fetch_remote_content() {

		$cache_key = 'pdfink_lite_remote_notice';
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return json_decode( $cached, true );
		}

		$response = wp_remote_get(
			add_query_arg(
				[
					'v'       => WWPDF_FREE_VERSION,
					'edition' => 'lite',
				],
				'https://pdfink.com/wp-json/pdf-ink/v1/plugin-notice/' ),
			[
				'timeout' => 8,
			]
		);

		$code = 0;
		if ( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$code = wp_remote_retrieve_response_code( $response );
		}

		if ( $code === 200 && ! empty( $body['data']) ) {
			// Cache for up to 12 hours
			set_transient( $cache_key, wp_json_encode( $body['data'] ), HOUR_IN_SECONDS * 12 );
			return $body['data'];
		}
		return null;

	}

	/**
	 * Add various support links to plugin page
	 *
	 * @param array $links
	 * @param string $file
	 *
	 * @return array $links
	 */
	public function add_support_links( array $links, string $file ): array {

		if ( ! current_user_can( 'install_plugins' ) ) {
			return $links;
		}
		if ( 'waterwoo-pdf/waterwoo-pdf.php' === $file ) {
			$links[] = '<a href="https://wordpress.org/extend/plugins/waterwoo-pdf/faq/" target="_blank" title="' . __( 'FAQ', 'waterwoo-pdf' ) . '" rel="noopener">' . __( 'FAQ', 'waterwoo-pdf' ) . '</a>';
			$links[] = '<a href="https://wordpress.org/support/plugin/waterwoo-pdf" target="_blank" title="' . __( 'Support', 'waterwoo-pdf' ) . '" rel="noopener">' . __( 'Support', 'waterwoo-pdf' ) . '</a>';
			$links[] = '<a href="https://pdfink.com/" target="_blank" title="' . __( 'Upgrade your plugin', 'waterwoo-pdf' ) . '" rel="noopener">' . __( 'Upgrade this plugin', 'waterwoo-pdf' ) . '</a>';
		}

		return $links;

	}

	/**
	 * Add link to settings page on plugins page
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public function plugin_action_links( array $links ): array {

		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$links[] = sprintf( '<a href="%s" title="%s">%s</a>', admin_url( 'admin.php?page=wc-settings&tab=pdf-ink-lite' ), __( 'Go to the settings page', 'waterwoo-pdf' ), __( 'Settings for Woo', 'waterwoo-pdf' ) );
		}
		if ( is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) ) {
			$links[] = sprintf( '<a href="%s" title="%s">%s</a>', admin_url( 'edit.php?post_type=download&page=edd-settings&tab=extensions&section=pdf_ink_lite' ), __( 'Go to the settings page', 'waterwoo-pdf' ), __( 'Settings for EDD', 'waterwoo-pdf' ) );
		}
		if ( is_plugin_active( 'download-monitor/download-monitor.php' ) ) {
			$links[] = sprintf( '<a href="%s" title="%s">%s</a>', admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings&tab=pdf_ink_lite' ), __( 'Go to the settings page', 'waterwoo-pdf' ), __( 'Settings for DLM', 'waterwoo-pdf' ) );
		}

		return $links;

	}

	/**
	 * Allow users to clean up their WP admin of nag messages
	 *
	 * @return void
	 */
	public function ajax_dismiss_notice() {

		check_ajax_referer( 'notice-nonce', 'nonce' );
		$dismiss = (int) $_POST[ 'dismiss_days' ] ?? 7;
		set_transient( 'pdfink_lite_notice_' . get_current_user_id(), true, DAY_IN_SECONDS * $dismiss );
		wp_send_json_success();

	}

}