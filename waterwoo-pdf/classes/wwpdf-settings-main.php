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

		parent::__construct();

		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_margin_top_bottom',   [ $this, 'woocommerce_admin_settings_sanitize_margin_option' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_margin_left_right',   [ $this, 'woocommerce_admin_settings_sanitize_margin_option' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_footer_finetune_X',   [ $this, 'woocommerce_admin_settings_sanitize_margin_option' ], 10, 3 );

	}

	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function get_own_sections() {

		return [
			''          => __( 'Options', 'waterwoo-pdf' ),
			'more_info' => __( 'More Info', 'waterwoo-pdf' ),
		];

	}

	/**
	 * Get default (general options) settings array
	 *
	 * @return array
	 */
	public function get_settings_for_default_section() {

		return apply_filters_deprecated( 'wwpdf_settings_tab', [

			[

				[
					'id'     => 'wwpdf_options',
					'type'   => 'title',
					'title'  => __( 'WaterMark PDF Options', 'waterwoo-pdf' ),
					'desc'   => '<strong>' . __( 'Note:', 'waterwoo-pdf' ) . '</strong> ' . __( 'This free watermarking plugin is rudimentary and may not work on every PDF. Test before going live!', 'waterwoo-pdf' )
								. '<br>The <strong>only</strong> watermarking plugin for WooCommerce that works with <strong>any and every</strong> PDF is <a href="https://www.little-package.com/shop/pdf-stamper-for-woocommerce/" target="_blank" rel="noopener">PDF Stamper for WooCommerce</a>',
				],

				[
					'title'     => __( 'Enable Watermarking', 'waterwoo-pdf' ),
					'id'        => 'wwpdf_global',
					'type'      => 'checkbox',
					'desc'      => __( 'Check to enable PDF watermarking', 'waterwoo-pdf' ),
					'default'   => 'no',
				],
				[
					'title'     => __( 'File(s) to watermark', 'waterwoo-pdf' ),
					'id'        => 'wwpdf_files',
					'type'      => 'textarea',
					'desc'      => __( 'List file name(s) of PDF(s) to watermark, one per line, e.g., <code>upload.pdf</code> or <code>my_pdf.pdf</code> .<br>If left blank, Watermark PDF for WooCommerce will watermark all PDFs sold through WooCommerce.', 'waterwoo-pdf' ),
					'default'   => '',
					'css'       => 'min-height: 82px;',
				],
				[
					'title'     => __( 'Custom text for footer watermark', 'waterwoo-pdf' ),
					'id'        => 'wwpdf_footer_input_premium',
					'type'      => 'textarea',
					'desc'      => __( 'Shortcodes available, all caps, in brackets: <code>[FIRSTNAME]</code> <code>[LASTNAME]</code> <code>[EMAIL]</code> <code>[PHONE]</code> <code>[DATE]</code>', 'waterwoo-pdf' ),
					'default'   => __( 'Licensed to [FIRSTNAME] [LASTNAME], [EMAIL]', 'waterwoo-pdf' ),
					'class'     => 'wide-input',
					'css'       => 'min-height: 82px;',
				],
				[
					'title'     => __( 'Font face', 'waterwoo-pdf' ),
					'id'        => 'wwpdf_font_premium',
					'type'      => 'select',
					'desc'      => __( 'Select a font for watermarks. M Sung will have limited Chinese characters, and Furat will have limited Arabic characters', 'waterwoo-pdf' ),
					'default'   => 'helvetica',
					'class'     => 'chosen_select',
					'options'   => [
						'helvetica'           => 'Helvetica',
						'times'               => 'Times New Roman',
						'courier'             => 'Courier',
						'dejavusanscondensed' => 'Deja Vu Sans Condensed',
						'msungstdlight'       => 'M Sung',
						'aefurat'             => 'AE Furat',
					],
					'desc_tip'  => true,
				],
				[
					'title'             => __( 'Font size', 'waterwoo-pdf' ),
					'id'                => 'wwpdf_footer_size_premium',
					'type'              => 'number',
					'desc'              => __( 'Provide a number (suggested 10-20) for the footer watermark font size', 'waterwoo-pdf' ),
					'default'           => '12',
					'custom_attributes' => [
						'min'   => 1,
						'max'   => 200,
						'step'  => 1,
					],
					'desc_tip'          => true,
				],
				[
					'title'             => __( 'Watermark color', 'waterwoo-pdf' ),
					'id'                => 'wwpdf_footer_color_premium',
					'type'              => 'color',
					'desc'              => __( 'Color of the footer watermark. Default is black: <code>#000000</code>.', 'waterwoo-pdf' ),
					'default'           => '#000000',
					'desc_tip'          => true,
				],
				[
					'title'             => __( 'Y Fine Tuning', 'waterwoo-pdf' ),
					'id'                => 'wwpdf_footer_finetune_Y_premium',
					'type'              => 'number',
					'desc'              => __( 'In millimeters. Move the footer watermark up and down on the page by adjusting this number. If this number is longer/higher than the length/height of your PDF, it will default back to -10 (10 millimeters from the bottom of the page). Account for the height of your font/text!', 'waterwoo-pdf' ),
					'default'           => -10,
					'custom_attributes' => [
						'max'   => 2000,
						'step'  => 1,
					],
					'desc_tip'          => true,
				],
				[
					'title'     => __( 'Leave No Trace?', 'waterwoo-pdf' ),
					'id'        => 'wwpdf_delete_checkbox',
					'type'      => 'checkbox',
					'desc'      => __( 'If this box is checked if/when you uninstall WaterMark PDF, all your settings will be deleted from your Wordpress database.', 'waterwoo-pdf' )
                            . '<br>' . sprintf( __( 'Marked PDF files will accumulate in your PDF folder whether using Force downloads or not. To keep your server tidy, manually delete ad lib or <a href="%s" target="_blank" rel="noopener">upgrade</a> for better file handling and automatic cleaning.', 'waterwoo-pdf' ), 'https://www.little-package.com/shop/waterwoo-pdf-premium' ),
					'default'   => 'no',
				],
				[
					'id'        => 'wwpdf_options',
					'type'      => 'sectionend'
				],
			]
		], '6.3', '', 'The `wwpdf_settings_tab` filter hook is included in the Premium (paid) version of WaterWoo PDF. Please upgrade to continue using it.' );

	}

	/**
	 * Output the settings.
	 *
	 * @return void
	 */
	public function output() {

		global $current_section;

		if ( 'more_info' === $current_section ) {
			$this->output_more_info_screen();
		}
		$settings = $this->get_settings_for_section( $current_section );

		WC_Admin_Settings::output_fields( $settings );

	}

	/**
	 * Get "more info" section settings array
	 *
	 * @return array
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
				<li>Extended magic <strong>shortcodes</strong>, including order number, future dates, and copies purchased
				<li>PDF <strong>password</strong> protection, encryption & permissions control
				<li>Add <strong>barcodes</strong> and QR codes to PDFs
				<li>Backend <strong>test watermarking</strong> of PDFs on-the-fly
				<li><strong>Per-product</strong> and variable product watermarking settings
				<li>Keep your original file name
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