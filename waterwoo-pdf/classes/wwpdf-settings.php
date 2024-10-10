<?php defined( 'ABSPATH' ) || exit;

class WWPDF_Settings {

	public function __construct() {

		// Add a tab to the WooCommerce settings page
		add_filter( 'woocommerce_get_settings_pages',                       [ $this, 'get_settings_pages' ], 10, 1 );

		add_filter( 'plugin_row_meta',                                      [ $this, 'add_support_links' ], 10, 2 );
		add_action( 'current_screen',                                       [ $this, 'load_screen_hooks' ] );
		add_filter( 'plugin_action_links_waterwoo-pdf/waterwoo-pdf.php',    [ $this, 'plugin_action_links' ] );

	}

	/**
	 * Get the settings tab (and sections) going
	 *
	 * @param  array $settings
	 * @return array
	 */
	public function get_settings_pages( $settings ) {

		$settings[] = include 'wwpdf-settings-main.php';
		return $settings;

	}

	/**
	 * Add various support links to plugin page
	 *
	 * @param array $links
	 * @param string $file
	 *
	 * @return array $links
	 */
	public function add_support_links( $links, $file ) {

		if ( ! current_user_can( 'install_plugins' ) ) {
			return $links;
		}
		if ( 'waterwoo-pdf/waterwoo-pdf.php' === $file ) {
			$links[] = '<a href="https://wordpress.org/extend/plugins/waterwoo-pdf/faq/" target="_blank" title="' . __( 'FAQ', 'waterwoo-pdf' ) . '" rel="noopener">' . __( 'FAQ', 'waterwoo-pdf' ) . '</a>';
			$links[] = '<a href="https://wordpress.org/support/plugin/waterwoo-pdf" target="_blank" title="' . __( 'Support', 'waterwoo-pdf' ) . '" rel="noopener">' . __( 'Support', 'waterwoo-pdf' ) . '</a>';
			$links[] = '<a href="https://www.little-package.com/shop/waterwoo-pdf-premium" target="_blank" title="' . __( 'Upgrade your plugin', 'waterwoo-pdf' ) . '" rel="noopener">' . __( 'Upgrade this plugin', 'waterwoo-pdf' ) . '</a>';
		}

		return $links;

	}

	/**
	 * Add CTA link on plugins page
	 *
	 * @param array $links
	 *
	 * @return array $links
	 */
	public function plugin_action_links( $links ) {

		$settings = sprintf( '<a href="%s" title="%s">%s</a>', admin_url( 'admin.php?page=wc-settings&tab=waterwoo-pdf' ), __( 'Go to the settings page', 'waterwoo-pdf' ), __( 'Settings', 'waterwoo-pdf' ) );
		array_unshift( $links, $settings );

		return $links;

	}

	/**
	 * Load screen hooks
	 */
	public function load_screen_hooks() {

		if ( isset( $_GET['tab'] ) && 'waterwoo-pdf' === $_GET['tab'] ) {
			$screen = get_current_screen();
			add_action( 'load-' . $screen->id, [ $this, 'add_help_tabs' ] );
		}

	}

	/**
	 * Add the help tabs
	 */
	public function add_help_tabs() {

		// Check current admin screen
		$screen = get_current_screen();

		// Remove all existing tabs
		$screen->remove_help_tabs();

		// Create arrays with help tab titles
		$screen->add_help_tab( [
			'id'      => 'waterwoo-pdf-usage',
			'title'   => __( 'About the Plugin', 'waterwoo-pdf' ),
			'content' =>
				'<h3>' . __( 'About Watermark PDF for WooCommerce', 'waterwoo-pdf' ) . '</h3>' .
				'<p>' . __( 'Protect your intellectual property! Watermark PDF for WooCommerce allows WooCommerce site administrators to apply custom watermarks to PDFs upon sale.' ) . '</p>' .
				'<p>' . __( 'Watermark PDF for WooCommerce is a plugin that adds a watermark to every page of your PDF file(s). The watermark is customizable with font face, font color, font size, placement, and text. Not only that, but since the watermark is added when the download button is clicked (either on the customer\'s order confirmation page or email), the watermark can include customer-specifc data such as the customer\'s first name, last name, and email. Your watermark is highly customizable and manipulatable.', 'waterwoo-pdf' ) . '</p>' .
				'<p>' . sprintf( __( '<a href="%s" target="_blank" rel="noopener">Consider upgrading to the Premium version</a> if you need more functionality.', 'waterwoo-pdf' ), 'https://www.little-package.com/shop/waterwoo-pdf-premium/' ) . '</p>'

		] );

		// Create help sidebar
		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'waterwoo-pdf' ) . '</strong></p>' .
			'<p><a href="https://wordpress.org/plugins/waterwoo-pdf/#faq" target="_blank" rel="noopener">' . __( 'Frequently Asked Questions', 'waterwoo-pdf' ) . '</a></p>' .
			'<p><a href="https://wordpress.org/plugins/waterwoo-pdf/" target="_blank" rel="noopener">' . __( 'Project on WordPress.org', 'waterwoo-pdf' ) . '</a></p>' .
			'<p><a href="https://www.little-package.com/shop/waterwoo-pdf-premium/" target="_blank" rel="noopener">' . __( 'Upgrade', 'waterwoo-pdf' ) . '</a></p>'
		);

	}

}