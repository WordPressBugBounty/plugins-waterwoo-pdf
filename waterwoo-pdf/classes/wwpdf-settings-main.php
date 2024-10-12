<?php defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WWPDF_Settings_Main', false ) ) {
	return new WWPDF_Settings_Main();
}

class WWPDF_Settings_Main extends WC_Settings_Page {

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->id = 'waterwoo-pdf';
		$this->label = __( 'Watermark', 'waterwoo-pdf' );

		add_action( 'admin_enqueue_scripts',                                                [ $this, 'admin_enqueue_scripts' ], 11 );
		parent::__construct();

		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_margin_top_bottom',   [ $this, 'woocommerce_admin_settings_sanitize_margin_option' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_margin_left_right',   [ $this, 'woocommerce_admin_settings_sanitize_margin_option' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_footer_finetune_X',   [ $this, 'woocommerce_admin_settings_sanitize_margin_option' ], 10, 3 );

	}

	/**
	 * @param $page
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts( $page ) {

		if ( 'woocommerce_page_wc-settings' !== $page ) {
			return;
		}
		if ( isset( $_GET['tab'] ) && 'waterwoo-pdf' === $_GET['tab'] ) {
			if ( ! isset( $_GET['section'] ) || ( isset( $_GET['section'] ) && 'more_info' !== $_GET['section'] ) ) {
				wp_dequeue_script( 'woo-connect-notice' );
			}
		}

	}

	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function get_sections() {

		$sections = [
			''              => __( 'Options', 'waterwoo-pdf' ),
			'log_settings'  => __( 'Error/event Logging', 'waterwoo-pdf' ),
			'more_info'     => __( 'More Info', 'waterwoo-pdf' ),
		];
		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );

	}

	/**
	 * Get default (general options) settings array
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {

		$settings = [];

		if ( 'log_settings' === $current_section ) {

			$settings = [
				/* Log Settings */
				[
					'type' => 'title',
					'name' => __( 'WaterWoo PDF Logging / Debugging', 'waterwoo-pdf' ),
				],
				[
					'id'      => 'wwpdf_debug_mode',
					'type'    => 'checkbox',
					'title'   => __( 'Enable WaterWoo Logs?', 'waterwoo-pdf' ),
					'desc'    => __( 'Check to enable WaterWoo Logs', 'waterwoo-pdf' ),
					'default' => 'no',
				],
				[ 'type' => 'sectionend' ],

			];

		} else if ( '' === $current_section ) {

			/* General Settings */
			$settings = [

				[
					'id'    => 'wwpdf_options',
					'type'  => 'title',
					'title' => __( 'WaterMark PDF Options', 'waterwoo-pdf' ),
					'desc'  => '<strong>' . __( 'Note:', 'waterwoo-pdf' ) . '</strong> ' . __( 'This free watermarking plugin is rudimentary and may not work on every PDF. Test before going live!', 'waterwoo-pdf' )
							. '<br>The <strong>only</strong> watermarking plugin for WooCommerce that works with <strong>any and every</strong> PDF is <a href="https://www.little-package.com/shop/pdf-stamper-for-woocommerce/" target="_blank" rel="noopener">PDF Stamper for WooCommerce</a>',
				],

				[
					'title'   => __( 'Enable Watermarking', 'waterwoo-pdf' ),
					'id'      => 'wwpdf_global',
					'type'    => 'checkbox',
					'desc'    => __( 'Check to enable PDF watermarking', 'waterwoo-pdf' ),
					'default' => 'no',
				],
				[
					'title'   => __( 'File(s) to watermark', 'waterwoo-pdf' ),
					'id'      => 'wwpdf_files',
					'type'    => 'textarea',
					'desc'    => __( 'List file name(s) of PDF(s) to watermark, one per line, e.g., <code>upload.pdf</code> or <code>my_pdf.pdf</code> .<br>If left blank, Watermark PDF for WooCommerce will watermark all PDFs sold through WooCommerce.', 'waterwoo-pdf' ),
					'default' => '',
					'css'     => 'min-height: 82px;',
				],
				[
					'title'   => __( 'Custom text for footer watermark', 'waterwoo-pdf' ),
					'id'      => 'wwpdf_footer_input_premium',
					'type'    => 'textarea',
					'desc'    => __( 'Shortcodes available, all caps, in brackets: <code>[FIRSTNAME]</code> <code>[LASTNAME]</code> <code>[EMAIL]</code> <code>[PHONE]</code> <code>[DATE]</code>', 'waterwoo-pdf' ),
					'default' => __( 'Licensed to [FIRSTNAME] [LASTNAME], [EMAIL]', 'waterwoo-pdf' ),
					'class'   => 'wide-input',
					'css'     => 'min-height: 82px;',
				],
				[
					'title'    => __( 'Font face', 'waterwoo-pdf' ),
					'id'       => 'wwpdf_font_premium',
					'type'     => 'select',
					'desc'     => __( 'Select a font for watermarks. M Sung will have limited Chinese characters, and Furat will have limited Arabic characters', 'waterwoo-pdf' ),
					'default'  => 'helvetica',
					'class'    => 'chosen_select',
					'options'  => [
						'helvetica'           => 'Helvetica',
						'times'               => 'Times New Roman',
						'courier'             => 'Courier',
						'dejavusanscondensed' => 'Deja Vu Sans Condensed',
						'msungstdlight'       => 'M Sung',
						'aefurat'             => 'AE Furat',
					],
					'desc_tip' => true,
				],
				[
					'title'             => __( 'Font size', 'waterwoo-pdf' ),
					'id'                => 'wwpdf_footer_size_premium',
					'type'              => 'number',
					'desc'              => __( 'Provide a number (suggested 10-20) for the footer watermark font size', 'waterwoo-pdf' ),
					'default'           => '12',
					'custom_attributes' => [
						'min'  => 1,
						'max'  => 200,
						'step' => 1,
					],
					'desc_tip'          => true,
				],
				[
					'title'    => __( 'Watermark color', 'waterwoo-pdf' ),
					'id'       => 'wwpdf_footer_color_premium',
					'type'     => 'color',
					'desc'     => __( 'Color of the footer watermark. Default is black: <code>#000000</code>.', 'waterwoo-pdf' ),
					'default'  => '#000000',
					'desc_tip' => true,
				],
				[
					'title'             => __( 'Y Fine Tuning', 'waterwoo-pdf' ),
					'id'                => 'wwpdf_footer_finetune_Y_premium',
					'type'              => 'number',
					'desc'              => __( 'In millimeters. Move the footer watermark up and down on the page by adjusting this number. If this number is longer/higher than the length/height of your PDF, it will default back to -10 (10 millimeters from the bottom of the page). Account for the height of your font/text!', 'waterwoo-pdf' ),
					'default'           => -10,
					'custom_attributes' => [
						'max'  => 2000,
						'step' => 1,
					],
					'desc_tip'          => true,
				],
				[
					'id'   => 'wwpdf_options',
					'type' => 'sectionend'
				],
				[
					'type' => 'title',
					'id'    => 'wwpdf_security_options',
					'name' => __( 'WaterWoo Security Options', 'waterwoo-pdf' ),
					'desc' => __( 'Warning: some browsers or PDF viewers may ignore protection settings, and some diligent customers might find ways to remove watermarks and passwords.', 'waterwoo-pdf' ) .
								'<br><strong>' . __( 'RC4 encryption is automatically set because it is required for protections & passwording.', 'waterwoo-pdf' ) .
								'</strong> ' . __( 'If your server doesn\'t support RC4 encryption, watermarking will fail.', 'waterwoo-pdf' ) .
								'<br>' . __( 'Always test before going live.', 'waterwoo-pdf' )
				],
				[
					'id'       => 'wwpdf_disable_printing',
					'type'     => 'checkbox',
					'title'    => __( 'Disable Low Res Printing', 'waterwoo-pdf' ),
					'desc'     => __( 'Check this box to make it more difficult for your PDF to be printed at all by the end consumer.', 'waterwoo-pdf' ),
					'default'  => 'no',
					'autoload' => false,
				],
				[
					'id'       => 'wwpdf_disable_copy',
					'type'     => 'checkbox',
					'title'    => __( 'Disable Copying', 'waterwoo-pdf' ),
					'desc'     => __( 'Check this box to prevent your end consumer from copying and pasting content from your PDF.', 'waterwoo-pdf' ),
					'default'  => 'no',
					'autoload' => false,
				],
				[
					'id'       => 'wwpdf_disable_mods',
					'type'     => 'checkbox',
					'title'    => __( 'Disable Editing', 'waterwoo-pdf' ),
					'desc'     => __( 'Check this box to prevent editing/modification of your PDF by the end consumer in Acrobat.', 'waterwoo-pdf' ),
					'default'  => 'no',
					'autoload' => false,
				],
				[
					'id'       => 'wwpdf_disable_annot',
					'type'     => 'checkbox',
					'title'    => __( 'Disable Annotations', 'waterwoo-pdf' ),
					'desc'     => __( 'Check this box to prevent the addition or modification of text annotations/comments, and filling of interactive form fields. If "editing and annotation" are both allowed, customers can create or modify interactive form fields (including signature fields).', 'waterwoo-pdf' ),
					'default'  => 'no',
					'autoload' => false,
				],
				[
					'id'       => 'wwpdf_password',
					'type'     => 'text',
					'title'    => __( 'User Password (optional)', 'waterwoo-pdf' ),
					'desc'     => __( 'This is a password your end user will need to enter *before viewing* the PDF file. Enter <code>email</code> to set the password automagically as the user\'s checkout email address.', 'waterwoo-pdf' ),
					'desc_tip' => true,
					'autoload' => false,
				],
				[
					'id'       => 'wwpdf_security_info',
					'type'     => 'info',
					'title'    => __( 'Improve security', 'waterwoo-pdf' ),
					'text'     => sprintf( __( 'The free version of this plugin allows crude encryption (RC4) and its protections. <strong><a href="%s" target="_blank" rel="noopener">The premium version of this plugin</a></strong> allows for AES encryption allows further protections such as high-resolution print blocking, blocked form-filling, and the removal of PDF extraction and assembly privileges. Furthermore, the Premium version of the plugin allows you to set an owner password for your PDF and includes filters for TCPDF SetProtection() arguments.', 'waterwoo-pdf' ), 'https://www.little-package.com/shop/waterwoo-pdf-premium' ),
				],
				[
					'title'   => __( 'Leave No Trace?', 'waterwoo-pdf' ),
					'id'      => 'wwpdf_delete_checkbox',
					'type'    => 'checkbox',
					'desc'    => __( 'If this box is checked if/when you uninstall WaterMark PDF, all your settings will be deleted from your Wordpress database.', 'waterwoo-pdf' )
								. '<br>' . sprintf( __( 'Marked PDF files will accumulate in your PDF folder whether using Force downloads or not. To keep your server tidy, manually delete ad lib or </strong><a href="%s" target="_blank" rel="noopener">upgrade to Premium</a></strong> for better file handling and automatic cleaning.', 'waterwoo-pdf' ), 'https://www.little-package.com/shop/waterwoo-pdf-premium' ),
					'default' => 'no',
				],
				[
					'id'   => 'wwpdf_security_options',
					'type' => 'sectionend'
				],
			];
		}
		return apply_filters_deprecated( 'wwpdf_settings_tab', [ $settings ], '6.3', '', 'The `wwpdf_settings_tab` filter hook is included in the Premium (paid) version of WaterWoo PDF. Please upgrade to continue using it.' );

	}

	/**
	 * Output the settings
	 *
	 * @return void
	 */
	public function output() {

		global $current_section, $hide_save_button;

		if ( 'log_settings' === $current_section ) {
			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::output_fields( $settings );
			$this->output_log_settings();
		} else if ( 'more_info' === $current_section ) {
			$this->output_more_info_screen();
		} else {
			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::output_fields( $settings );
		}

	//	WC_Admin_Settings::output_fields( $settings );

	}

	/**
	 * @return void
	 */
	public function output_log_settings() { ?>

		<p>
			<?php esc_html_e( 'Watermarking events and errors will be saved to a file in your /wp-content/ folder. You can view the contents below.', 'waterwoo-pdf' ); ?>
			<br>
			<?php esc_html_e( 'Maybe only turn this on for troubleshooting because this file can get large.', 'waterwoo-pdf' ); ?>
		</p>
		<?php if ( 'yes' !== get_option( 'wwpdf_debug_mode', 'no' ) ) {
			return;
		}
		global $wwpdf_logs; ?>

		<div class="wrap">
			<h3><span><?php esc_html_e( 'Logs', 'waterwoo-pdf' ); ?></span></h3>
				<label for="wwpdf-log-textarea"><?php esc_html_e( 'Use this tool to help debug TCPDI/TCPDF and WaterWoo functionality.', 'waterwoo-pdf' ); ?></label>
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
				<?php _e( 'Log file', 'waterwoo-pdf' ); ?>: <code><?php echo esc_html( $wwpdf_logs->get_log_file_path() ); ?></code>
			</p>
		</div>

	<?php }

	/**
	 * Get "more info" section settings array
	 *
	 * @return void
	 */
	public function output_more_info_screen() { ?>

		<div style="margin:3em">
			<p style="font-size: 2em;">
				Hi, I'm Caroline, a WordPress developer based in Utah, USA. I've kept the <strong>PDF Watermarker</strong> plugin in active development since 2014 <em>as an unpaid volunteer</em>. Why? Because I believe IP protection is important.
			<p style="font-size: 1.75em;">
				But also -- and truthfully -- I depend on donations and paid upgrades to make my living. If you find this little plugin useful, and particularly if you benefit from it, consider upgrading to the much more powerful <a href="https://www.little-package.com/shop/waterwoo-pdf-premium" target="_blank" rel="noopener">WaterWoo PDF Premium</a>. Some features included in the upgrade:<br>
			<ul style="list-style:circle;margin-left:30px">
				<li>Full watermark page number and position control
				<li>Another full watermark position, anywhere on the page
				<li>Upload your own TTF <strong>fonts</strong>
				<li>RTL
				<li>Watermark <strong>opacity</strong> control
				<li>Extended magic <strong>shortcodes</strong>, including billing address information, order number, future dates, and copies purchased
				<li>Full PDF <strong>password</strong> protection, encryption & permissions control
				<li>Add <strong>barcodes</strong> and QR codes to PDFs
				<li>Backend <strong>test watermarking</strong> of PDFs on-the-fly
				<li><strong>Per-product</strong> and variable product watermarking settings
				<li>Keep your original file name
				<li>Unzip archives and mark chosen PDFs inside
				<li>Automatic, scheduled file cleanup
				<li>Support for <strong>externally hosted files (like Amazon S3)</strong>
				<li>Compatibility with <strong>Free Downloads WooCommerce</strong> and <strong>WooCommerce Bulk Downloads</strong>
				<li><?php echo sprintf(__( 'Priority email support, <a href="%s" target="_blank" rel="noopener">and more!</a>', 'waterwoo-pdf' ), 'https://www.little-package.com/shop/waterwoo-pdf-premium/' ) ?>

			</ul></p>
			<p style="font-size: 1.5em;">
				If that's not in your budget I understand. Please take a moment to write <a href="https://wordpress.org/support/plugin/waterwoo-pdf/reviews/?filter=5" target="_blank" rel="noopener">an encouraging review</a>, or <a href="https://www.paypal.com/paypalme/littlepackage" target="_blank" rel="noopener noreferrer">donate $3 using PayPal</a> to cover my coffee today. ☕️ 😋️ Your kindness and enthusiasm makes donating my time to this open-source project worthwhile!
			</p>
			<h2 style="font-size:3em">Need help?</h2>
			<p style="font-size: 2em;">
				Please refer to the <a href="https://wordpress.org/plugins/waterwoo-pdf/#faq-header" target=_blank" rel="noopener">FAQ</a> and <a href="https://wordpress.org/support/plugin/waterwoo-pdf/" target="_blank" rel="noopener nofollow">support forum</a> where your question might already be answered. <a href="https://wordpress.org/support/topic/before-you-post-please-read-2/" rel="noopener">Read this before posting</a>. I only provide email support for paying customers (thank you ✌️).</p>
			</p>
		</div>

	<?php }

}