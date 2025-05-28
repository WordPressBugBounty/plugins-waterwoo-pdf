<?php
/**
 * Plugin Name: PDF Ink Lite
 * Plugin URI: https://wordpress.org/plugins/waterwoo-pdf/
 * Description: Custom watermark your PDF files upon WooCommerce, Download Monitor, and Easy Digital Download customer download. Since 2014. FKA "WaterWoo"
 * Version: 4.0.1
 * Author: Little Package
 * Author URI: https://pdfink.com
 * Donate link: https://paypal.me/littlepackage
 * WC requires at least: 4.0
 * WC tested up to: 9.8
 *
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Text Domain: waterwoo-pdf
 * Domain path: /lang
 *
 * Copyright 2013-2025 Little Package
 *
 *      This file is part of PDF Ink Lite, a plugin for WordPress. If
 *      it benefits you, please support my volunteer work
 *
 *      https://paypal.me/littlepackage  or/and
 *
 *      leave a nice review at:
 *
 *      https://wordpress.org/support/view/plugin-reviews/waterwoo-pdf?filter=5
 *
 *      Thank you. 😊
 *
 *      PDF Ink Lite is free software: You can redistribute it and/or modify
 *      it under the terms of the GNU General Public
 *      License as published by the Free Software Foundation, either
 *      version 3 of the License, or (at your option) any later version.
 *
 *      PDF Ink Lite is distributed in the hope that it will
 *      be useful, but WITHOUT ANY WARRANTY; without even the
 *      implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
 *      PURPOSE. See the GNU General Public License for more details.
 *
 *      You should have received a copy of the GNU General Public License
 *      along with WordPress. If not, see <http://www.gnu.org/licenses/>.
 *
 * @todo maybe remove deprecated filters
 * @todo regenerate lang files - always
 *
 */
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WWPDF_FREE_VERSION' ) ) {
	define( 'WWPDF_FREE_VERSION', '4.0.1' );
}

if ( ! defined( 'WWPDF_FREE_MIN_PHP' ) ) {
	define( 'WWPDF_FREE_MIN_PHP', '7.0' );
}

if ( ! defined( 'WWPDF_FREE_MIN_WP' ) ) {
	define( 'WWPDF_FREE_MIN_WP', '4.9' );
}

if ( ! defined( 'WWPDF_FREE_MIN_WC' ) ) {
	define( 'WWPDF_FREE_MIN_WC', '4.0' );
}

if ( ! defined( 'WWPDF_PATH' ) ) {
	define( 'WWPDF_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'PDFINK_LITE_UPLOADS_PATH' ) ) {
	define( 'PDFINK_LITE_UPLOADS_PATH', WP_CONTENT_DIR . '/uploads/pdf-ink/' );
	if ( ! wp_mkdir_p( PDFINK_LITE_UPLOADS_PATH ) ) {
		add_action( 'admin_notices', 'pdfink_lite_need_wp_content_dir_access_notice' );
	}
}

class WaterWooPDF {

	private static $instance = false;

	/**
	 * @return bool|WaterWooPDF
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->update_db();
		$this->includes();

		if ( is_admin() ) {
			// Backend settings
			new WWPDF_Settings();

			// Check if WooCommerce is enabled
			if ( class_exists( 'WooCommerce' ) ) {
				// Check WooCommerce version
				if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, WWPDF_FREE_MIN_WC, '<' ) ) {
					add_action( 'admin_notices', 'wwpdf_old_woo_notice' );
				} else {
					// Add a tab to the WooCommerce settings page
					add_filter( 'woocommerce_get_settings_pages', function( $s ) {
						$s[] = include WWPDF_PATH . 'classes/wwpdf-settings-woo.php';
						return $s;
					}, 10, 1 );
				}

			}

			if ( class_exists( 'Easy_Digital_Downloads' ) ) {
				new WWPDF_Settings_EDD();
			}

			if ( class_exists( 'WP_DLM' ) ) {
				new WWPDF_Settings_DLM();
			}

		}

		// Download/error logging
		$GLOBALS['wwpdf_logs'] = new WWPDF_Logging();

		// Only run when downloading
		if ( ! is_admin() ) {
			new WWPDF_Free_File_Handler();
		}

	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'woocommerce' ), WWPDF_FREE_VERSION );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'woocommerce' ), WWPDF_FREE_VERSION );
	}

	/**
	 * Import and convert PDF Ink settings to match paid plugin setting names
	 *
	 * @return void
	 */
	private function update_db() {

		delete_option( 'wwpdf_donate_dismiss_08-28' );

		if ( 'dejavu' === get_option( 'wwpdf_font_premium' ) ) {
			update_option( 'wwpdf_font_premium', 'dejavusanscondensed' );
		}
		if ( $global = get_option( 'wwpdf_enable' ) ) {
			update_option( 'wwpdf_global', $global );
			delete_option( 'wwpdf_enable' );
		}
		if ( $footer_input = get_option( 'wwpdf_footer_input' ) ) {
			update_option( 'wwpdf_footer_input_premium', $footer_input );
			delete_option( 'wwpdf_footer_input' );
		}
		if ( $font = get_option( 'wwpdf_font' ) ) {
			update_option( 'wwpdf_font_premium', $font );
			delete_option( 'wwpdf_font' );
		}
		if ( $footer_size = get_option( 'wwpdf_footer_size' ) ) {
			update_option( 'wwpdf_footer_size_premium', $footer_size );
			delete_option( 'wwpdf_footer_size' );
		}
		if ( $footer_color = get_option( 'wwpdf_footer_color' ) ) {
			update_option( 'wwpdf_footer_color_premium', $footer_color );
			delete_option( 'wwpdf_footer_color' );
		}
		if ( $footer_y = get_option( 'wwpdf_footer_y' ) ) {
			update_option( 'wwpdf_footer_finetune_Y_premium', $footer_y );
			delete_option( 'wwpdf_footer_y' );
		}

	}

	/**
	 * @return void
	 */
	public function includes() {

		include_once WWPDF_PATH . 'classes/wwpdf-logging.php';
		include_once WWPDF_PATH . 'classes/wwpdf-settings.php';
		include_once WWPDF_PATH . 'classes/wwpdf-settings-dlm.php';
		include_once WWPDF_PATH . 'classes/wwpdf-settings-edd.php';
		include_once WWPDF_PATH . 'classes/wwpdf-file-handler.php';
		include_once WWPDF_PATH . 'classes/wwpdf-watermark.php';

	}

}

if ( function_exists('is_plugin_active' ) && is_plugin_active( 'waterwoo-pdf-premium/waterwoo-pdf-premium.php' ) ) {
	wp_die( 'Before activating PDF Ink Lite, please deactivate WaterWoo PDF Premium version. You can use one or the other, but not both.', 'ERROR', [ 'back_link' => true ] );
}

function WWPDF_Free() {
	return WaterWooPDF::get_instance();
}

function wwpdf_old_php_notice() {
	echo '<div class="error"><p>' . sprintf( __( '<strong>PDF Ink Lite</strong> supports PHP %s or later. Please update PHP on your server for better overall results.', 'waterwoo-pdf' ), WWPDF_FREE_MIN_PHP ) . '</p></div>';
}

function wwpdf_old_wp_notice() {
	echo '<div class="error"><p>' . sprintf( __( '<strong>PDF Ink Lite</strong> supports WordPress version %s or later. Please update WordPress to use this plugin.', 'waterwoo-pdf' ), WWPDF_FREE_MIN_WP ) . '</p></div>';
}

function wwpdf_no_woo_notice() {
	echo '<div class="error"><p>' . sprintf( __( 'The <strong>PDF Ink Lite</strong> plugin requires WooCommerce be activated. You can <a href="%s" target="_blank" rel="noopener">download WooCommerce here</a>.', 'waterwoo-pdf' ), 'https://wordpress.org/plugins/woocommerce/' ) . '</p></div>';
}

function wwpdf_old_woo_notice() {
	echo '<div class="error"><p>' . sprintf( __( 'Sorry, <strong>PDF Ink Lite</strong> supports WooCommerce version %s or newer, for security reasons.', 'waterwoo-pdf' ), WWPDF_FREE_MIN_WC ) . '</p></div>';
}

function pdfink_lite_need_wp_content_dir_access_notice() {
	echo '<div class="error"><p>' . __( 'PDF Ink Lite requires that the directory defined by <code>PDFINK_LITE_UPLOADS_PATH</code> (usually `<strong>wp-content/uploads/pdf-ink/</strong>`) is writable.', 'pdf-ink' ) . '</p></div>';
}

function pdfink_cta_tb( $value ) {

	add_thickbox();
	$svg_url = plugins_url('assets/svg/pdfink-lite-sprite.svg#pdf-lover', __FILE__ );
	echo '<style>.wc-settings-row-muted,.settings-row-muted{opacity:50%;}</style>
		<div id="pdfink-upgrade-tb" style="display:none;">
			<div style="display:flex;align-items:center;justify-content:space-between;padding:1.5em;gap:20px;"> 
				<div>
					<a href="https://pdfink.com?source=wordpress" rel="noopener" target="_blank"><svg width="300px" height="225px"><use href="' . esc_url( $svg_url ) . '" /></svg></a>
				</div>
				<div style="text-align:center">
					<h2>Unlock the full potential of PDF Ink</h2>
					<p style="font-size:1.25em;">
						Settings marked with a key &nbsp;<span class="dashicons dashicons-admin-network pdfink-upgrade" style="vertical-align:middle;"></span> are available in the full (paid) version of PDF Ink.
					</p>
					<p style="font-size:1.5em;font-weight:700;">
						Visit <a href="https://pdfink.com?source=wordpress" rel="noopener" target="_blank">pdfink.com</a> to upgrade!
					</p>
				</div>
			</div>
		</div>';

}

/**
 * Logs a message to the debug log file
 *
 * @since 2.8.7
 * @since 2.9.4 Added the 'force' option.
 *
 * @param string $message
 * @param string $type
 * @param boolean $force
 * @global $wwpdf_logs WWPDF_Logging Object
 * @return void
 */
function wwpdf_debug_log( $message = '', $type = '', $force = false ) {

	if ( 'no' === get_option( 'wwpdf_debug_mode', 'no' ) ) {
		return;
	}

	global $wwpdf_logs;
	if ( function_exists( 'mb_convert_encoding' ) ) {
		$message = mb_convert_encoding( $message, 'UTF-8' );
	}
	$wwpdf_logs->log_to_file( $message );

}

function pdfink_log_output() { ?>

	<p>
		<?php esc_html_e( 'Watermarking events and errors will be saved to a file in your /wp-content/ folder. You can view the contents below.', 'waterwoo-pdf' ); ?>
		<br>
		<?php esc_html_e( 'Maybe only turn this on for troubleshooting because this file can get large.', 'waterwoo-pdf' ); ?>
	</p>
	<?php $debug_on = get_option( 'wwpdf_debug_mode', 'no' );
	if ( '1' !== $debug_on && 'yes' !== $debug_on ) {
		return;
	}
	global $wwpdf_logs; ?>

	<div class="wrap">
		<h3><span><?php esc_html_e( 'Logs', 'waterwoo-pdf' ); ?></span></h3>
		<label for="wwpdf-log-textarea"><?php esc_html_e( 'Use this tool to help debug TCPDI/TCPDF and PDF Ink functionality.', 'waterwoo-pdf' ); ?></label>
		<textarea readonly="readonly" id="wwpdf-log-textarea" class="large-text" rows="16" name="wwpdf-debug-log-contents"><?php echo esc_textarea( $wwpdf_logs->get_file_contents() ); ?></textarea>
		<input type="hidden" name="wwpdf_action" value="submit_debug_log">
		<?php wp_nonce_field( 'wwpdf-logging-nonce', 'wwpdf_logging_nonce' ); ?>
		<p class="submit">
			<?php
			submit_button( __( 'Download Debug Log File', 'waterwoo-pdf' ), 'primary', 'wwpdf-download-debug-log', false ); ?> &nbsp; <?php
			submit_button( __( 'Clear Log', 'waterwoo-pdf' ), 'secondary', 'wwpdf-clear-debug-log', false ); ?> &nbsp; <?php
			submit_button( __( 'Copy Entire Log', 'waterwoo-pdf' ), 'secondary', 'wwpdf-copy-debug-log', false, [ 'onclick' => "this.form['wwpdf-debug-log-contents'].focus();this.form['wwpdf-debug-log-contents'].select();document.execCommand('copy');return false;" ] );
			?>
		</p>
		<?php // wp_nonce_field( 'wwpdf-debug-log-action' ); ?>
		<p>
			<?php _e( 'Log file', 'waterwoo-pdf' ); ?>: <code><?php esc_html_e( $wwpdf_logs->get_log_file_path() ); ?></code>
		</p>
	</div>

	<?php

}

/**
 * Get "more info" section settings array
 *
 * @return void
 */
function pdfink_more_info_screen() {
	$svg_url = plugins_url('assets/svg/pdfink-lite-sprite.svg#pdf-download', __FILE__ ); ?>

	<div style="margin:3em">
		<style>.pdf_ink_lite .dlm-content-tab{width:100%}</style>
		<p style="font-size: 2em;">
			<?php _e( 'Hi, I\'m Caroline.', 'waterwoo-pdf' ); ?> 🖖🏼
        </p>
        <p style="font-size: 1.75em;">
            <?php _e( 'I\'ve kept the <strong>PDF Ink Lite</strong> plugin in active development since 2014 as an unpaid volunteer.', 'waterwoo-pdf' ); ?>
            <br>
			<?php echo sprintf( __( 'If you enjoy the free version, think about <a href="%s" target="_blank" rel="noopener">upgrading to the full version</a> for even more great features!', 'waterwoo-pdf' ), 'https://pdfink.com?source=wordpress' ); ?>
        </p>
        <h2 style="font-size:3em;margin-bottom:0"><?php _e('Upgrade Features:', 'waterwoo-pdf' ); ?></h2>
		<div style="display:flex;align-items:center;justify-content:space-between;padding:1.5em;gap:20px;">
            <div>
            <ul style="list-style:circle;margin-left:30px;margin-top:0;font-size: 1.33em;">
                <li>Works with <strong>any</strong> PDF (may require 3rd party library purchase for complex PDFs)
                <li>Full watermark page and position control
                <li>More watermark positions, anywhere on the page
                <li>Upload your own TTF <strong>fonts</strong>
                <li>RTL
                <li>Watermark <strong>opacity</strong> control
                <li>Extended magic <strong>shortcodes</strong> for customized marks, including billing address information, order number, product name, future dates, and copies purchased
                <li>Full PDF <strong>password</strong> protection, encryption & permissions control
                <li>Add <strong>barcodes</strong> and QR codes to PDFs
                <li>Backend <strong>test watermarking</strong> of PDFs on-the-fly
                <li><strong>Per-product</strong> and variable product watermarking settings
                <li>Embed customized/encrypted PDF files on the page
                <li>Unzip archives and mark chosen PDFs inside
                <li>Automatic, scheduled file cleanup
                <li>Support for <strong>externally hosted files (like Amazon S3)</strong>
                <li>Compatibility with <strong>Free Downloads WooCommerce</strong>, <strong>WooCommerce Bulk Downloads</strong>, and <strong>EDD Free Downloads</strong>
                <li>PDF Ink works even without WordPress, allowing you to easily integrate <strong>SetaPDF-Stamper</strong> or <strong>FPDI PDF-Parser</strong> and FPDF/TCPDF into any PHP-based website!
                <li><?php echo sprintf(__( 'Priority email support, <a href="%s" target="_blank" rel="noopener">and more!</a>', 'waterwoo-pdf' ), 'https://pdfink.com/#features' ) ?>
            </ul>
            </div>
            <div>
                <a href="https://pdfink.com?source=wordpress" rel="noopener" target="_blank">
                    <svg width="300px" height="210px"><use href="<?php echo esc_url( $svg_url ); ?>" /></svg>
                </a>
            </div>
        </div>
        <h2 style="font-size:3em;margin-bottom:0"><?php esc_html_e( 'Can\'t Upgrade? Support My Work Another Way!', 'waterwoo-pdf' ); ?></h2>

        <p style="font-size: 1.5em;">
			<?php echo sprintf( __( 'If PDF Ink is not in your budget, please take a moment to write <a href="%s" target="_blank" rel="noopener">an encouraging review</a>, or <a href="%s" target="_blank" rel="noopener noreferrer">donate a couple dollars using PayPal</a> to cover my coffee today.', 'waterwoo-pdf' ), 'https://wordpress.org/support/plugin/waterwoo-pdf/reviews/?filter=5', 'https://www.paypal.com/paypalme/littlepackage' ); ?> ☕️ 😋️ <?php esc_html_e( 'Your kindness and enthusiasm makes donating my time to this open-source project worthwhile!', 'waterwoo-pdf' ); ?>
		</p>
		<h2 style="font-size:3em;margin-bottom:0"><?php esc_html_e( 'Need help?', 'waterwoo-pdf' ); ?></h2>
		<p style="font-size: 2em;">
			<?php echo sprintf( __( 'Please refer to the <a href="%s" target="_blank" rel="noopener">FAQ</a> and <a href="%s" target="_blank" rel="noopener nofollow">support forum</a> where your question might already be answered. <a href="%s" rel="noopener">Read this before posting</a>.', 'waterwoo-pdf' ), 'https://wordpress.org/plugins/waterwoo-pdf/#faq-header', 'https://wordpress.org/support/plugin/waterwoo-pdf/', 'https://wordpress.org/support/topic/before-you-post-please-read-2/' ); ?> <?php esc_html_e( 'I only provide email support for paying customers. Thank you!', 'waterwoo-pdf' ); ?> ✌️</p>
		</p>
	</div>

<?php }


/**
 * Checks for compatibility and maybe fires up the plugin
 *
 * @return void
 */
function wwpdf_plugins_loaded() {

	// Check PHP version
	if ( version_compare( PHP_VERSION, WWPDF_FREE_MIN_PHP, '<' ) ) {
		add_action( 'admin_notices', 'wwpdf_old_php_notice' );
		return;
	}
	// Check WordPress version
	if ( version_compare( get_bloginfo( 'version' ), WWPDF_FREE_MIN_WP, '<' ) ) {
		add_action( 'admin_notices', 'wwpdf_old_wp_notice' );
		return;
	}

	/**
	 * Declare compatibility with HPOS
	 * @return void
	 */
	add_action( 'before_woocommerce_init', function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	} );

	load_plugin_textdomain( 'waterwoo-pdf', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

	WWPDF_Free();

}
add_action( 'plugins_loaded', 'wwpdf_plugins_loaded', 1 );