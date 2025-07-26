<?php defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WWPDF_Settings_Woo', false ) ) {
	return new WWPDF_Settings_Woo();
}

class WWPDF_Settings_Woo extends WC_Settings_Page {

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->id = 'pdf-ink-lite';
		$this->label = __( 'PDF Ink Lite', 'waterwoo-pdf' );

		add_action( 'admin_enqueue_scripts',                                                [ $this, 'admin_enqueue_scripts' ], 11 );
		parent::__construct();

		add_action( 'woocommerce_admin_field_pdfink_css',                                   [ $this, 'pdfink_css' ], 10, 1 );
		add_action( 'woocommerce_admin_field_pdfink_intro',                                 [ $this, 'pdfink_intro' ], 10, 1 );
		add_action( 'woocommerce_admin_field_pdfink_cta',                                   'pdfink_cta_tb', 10, 1 );

		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_encrypt',             [ $this, 'woocommerce_admin_settings_sanitize_wwpdf_encrypt' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_watermark_pages',     [ $this, 'woocommerce_admin_settings_sanitize_wwpdf_watermark_pages' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_rtl',                 [ $this, 'woocommerce_admin_settings_sanitize_return_zero' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_margin_top_bottom',   [ $this, 'woocommerce_admin_settings_sanitize_return_zero' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_overlay_rotate',      [ $this, 'woocommerce_admin_settings_sanitize_return_zero' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_overlay_finetune_X',  [ $this, 'woocommerce_admin_settings_sanitize_return_zero' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_start_page',          [ $this, 'woocommerce_admin_settings_sanitize_return_one' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_end_page',            [ $this, 'woocommerce_admin_settings_sanitize_return_minus_one' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_failure',             [ $this, 'woocommerce_admin_settings_sanitize_return_no' ], 10, 3 );

		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_disable_ass',         [ $this, 'woocommerce_admin_settings_sanitize_return_zero' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_disable_printing_high',[ $this, 'woocommerce_admin_settings_sanitize_return_zero' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_disable_fill_forms',  [ $this, 'woocommerce_admin_settings_sanitize_return_zero' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_disable_extract',     [ $this, 'woocommerce_admin_settings_sanitize_return_zero' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wwpdf_protect_unlock',      [ $this, 'woocommerce_admin_settings_sanitize_return_zero' ], 10, 3 );

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
		if ( isset( $_GET['tab'] ) && $this->id === $_GET['tab'] ) {
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
			''              => __( 'PDF Options', 'waterwoo-pdf' ),
			'housekeeping'  => __( 'Housekeeping', 'waterwoo-pdf' ),
			'log_settings'  => __( 'Logging', 'waterwoo-pdf' ),
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

		if ( 'housekeeping' === $current_section ) {
			$settings = [
				[
					'id'    => 'pdfink_css',
					'type'  => 'pdfink_css',
				],
				[
					'type' => 'title',
					'id'   => 'housekeeping',
					'name' => __( 'Housekeeping', 'waterwoo-pdf' ),
					'desc' => __( 'New with PDF Ink Lite v4: marked PDF files are stored in the wp-content/uploads/pdf-ink/ folder for easier management.' ),
				],
				[
					'title'   => __( 'Leave No Trace?', 'waterwoo-pdf' ),
					'id'      => 'wwpdf_delete_checkbox',
					'type'    => 'checkbox',
					'desc'    => __( 'If this box is checked and you uninstall PDF Ink Lite, all your settings will be deleted from your WordPress database.', 'waterwoo-pdf' )
								 . '<br>' . sprintf( __( 'Marked PDF files will accumulate in your PDF folder whether using Force downloads or not. To keep your server tidy, manually delete ad lib or </strong><a href="%s" target="_blank" rel="noopener">upgrade this plugin</a></strong> for better file handling and automatic cleaning.', 'waterwoo-pdf' ), 'https://pdfink.com/' ),
					'default' => 'no',
				],
				[
					'id'     => 'pdfink_attribution',
					'type'     => 'checkbox',
					'title'    => __( 'Give Us Attribution', 'waterwoo-pdf' ),
					'desc'     => __( 'We\'d love it if you check this box and allow us to add a tiny, invisible link to the second page of your marked PDF files, giving PDF Ink Lite credit.', 'waterwoo-pdf' ),
					'default'      => 'no',
				],
				[
					'id'   => 'housekeeping',
					'type' => 'sectionend'
				],
			];

		} else if ( 'log_settings' === $current_section ) {
			$settings = [
				[
					'type' => 'title',
					'name' => __( 'PDF Ink Lite Logs', 'waterwoo-pdf' ),
				],
				[
					'id'      => 'wwpdf_debug_mode',
					'type'    => 'checkbox',
					'title'   => __( 'Enable Logs?', 'waterwoo-pdf' ),
					'desc'    => __( 'Check to enable event/error logging. This can help with debugging.', 'waterwoo-pdf' ),
					'default' => 'no',
				],
				[ 'type' => 'sectionend' ],
			];

		} else if ( '' === $current_section ) {

			/* General Settings */
			$settings = [

				[
					'id'    => 'pdfink_intro',
					'type'  => 'pdfink_intro',
				],
				[
					'id'    => 'wwpdf_options',
					'type'  => 'title',
					'title' => __( 'PDF Ink Lite Settings', 'waterwoo-pdf' ),
				],

				[
					'title'   => __( 'Enable Watermarking', 'waterwoo-pdf' ),
					'id'      => 'wwpdf_global',
					'type'    => 'checkbox',
					'desc'    => __( 'Check to watermark PDFs sold via WooCommerce using the settings below.', 'waterwoo-pdf' ),
					'default' => 'no',
				],
				[
					'title'   => __( 'File(s) to watermark', 'waterwoo-pdf' ),
					'id'      => 'wwpdf_files',
					'type'    => 'textarea',
					'desc'    => __( 'List file name(s) of PDF(s), one per line, e.g.,', 'waterwoo-pdf' ) . ' <code>upload.pdf</code> ' . __( 'or', 'waterwoo-pdf' ) . ' <code>my_pdf.pdf</code>. ' . __( ' Case-sensitive.', 'waterwoo-pdf' ),
					'default' => '',
					'css'     => 'min-height: 82px;',
					'desc_at_end'=> true,
				],
				[
					'id'      => 'wwpdf_files_v4',
					'type'    => 'checkbox',
					'title'   => __( 'Enable New Logic?', 'waterwoo-pdf' ),
					'desc'    => __( 'If this box is checked, it changes how the `File(s) to Watermark` field above works.', 'waterwoo-pdf' )
                                . '<br>' . __( 'If checked, and "Enable Watermarking" is also checked, any files listed in the box will not be watermarked.', 'waterwoo-pdf' )
								. '<br>' . __( 'If checked, and "Enable Watermarking" is not checked, any files listed in the box will be watermarked.', 'waterwoo-pdf' )
								. '<br><br>' . sprintf( __( '<a href="%s" target="_blank" rel="noopener">Upgrade</a> for easier file control.', 'waterwoo-pdf' ), 'https://pdfink.com/?source=free_plugin&utm_campaign=woo' ),
					'default' => 'no',
				],
				[
					'id'      => 'pdfink_cta',
					'type'    => 'pdfink_cta',
				],
				[
					'id'        => 'wwpdf_rtl',
					'type'      => 'checkbox',
					'title'     => __( 'Right to Left Watermarking', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Check to switch from default left-to-right (LTR) to right-to-left (RTL), for Arabic, Hebrew, etc.', 'waterwoo-pdf' ),
					'default'   => 'no',
					'class'     => 'disabled',
					'row_class' => 'muted',
					'autoload'  => false,
				],
				[
					'id'        => 'wwpdf_start_page',
					'type'      => 'number',
					'title'     => __( 'Start Page', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Provide a number to indicate the page you wish watermarking to begin on. Defaults to page 1.', 'waterwoo-pdf' ),
					'default'   => 1,
					'class'     => 'disabled',
					'row_class' => 'muted',
					'custom_attributes' => [
						'min'       => 1,
						'max'       => 9999,
						'step'      => 1,
					],
					'autoload'  => false,
				],
				[
					'id'        => 'wwpdf_end_page',
					'type'      => 'number',
					'title'     => __( 'End Page', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Provide a number to indicate the page you wish watermarking to end on. Type \'last\' to indicate last page. Defaults to last page', 'waterwoo-pdf' ),
					'class'     => 'disabled',
					'row_class' => 'muted',
					'default'   => -1,
					'custom_attributes' => [
						'min'       => -1,
						'max'       => 9999,
						'step'      => 1,
					],
					'autoload'  => false,
				],
				[
					'id'        => 'wwpdf_watermark_pages',
					'type'      => 'select',
					'title'     => __( 'Pages to watermark', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Watermark every page, the first page only, the last page only, every odd page, every even page, or custom page range. Defaults to `Every page`', 'waterwoo-pdf' ),
					'class'     => 'disabled',
					'row_class' => 'muted',
					'default'   => 'every',
					'options'   => [
						'every'     => 'Every page',
						'first'     => 'First page only',
						'last'      => 'Last page only',
						'odd'       => 'Odd pages',
						'even'      => 'Even pages',
						'custom'    => 'Custom',
					],
					'autoload'  => false,
				],
				[
					'id'        => 'wwpdf_margin_top_bottom',
					'type'      => 'number',
					'title'     => __( 'Top/bottom margin', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'In millimeters. Yes, metric! Defaults to 0', 'waterwoo-pdf' ),
					'default'   => 10,
					'custom_attributes' => [
						'min'       => 0,
						'max'       => 500,
						'step'      => 1,
					],
					'autoload'  => false,
					'class'     => 'disabled',
					'row_class' => 'muted',
				],
				[
					'id'        => 'wwpdf_margin_left_right',
					'type'      => 'number',
					'title'     => __( 'Left/right margin', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'In millimeters. Yes, metric! Defaults to 0', 'waterwoo-pdf' ),
					'default'   => 10,
					'custom_attributes' => [
						'min'       => 0,
						'max'       => 500,
						'step'      => 1,
					],
					'autoload'  => false,
				],
				[
					'id'   => 'wwpdf_options',
					'type' => 'sectionend'
				],

				[
					'type' => 'title',
					'id'    => 'page_setup',
					'name' => __( 'Page Setup', 'waterwoo-pdf' ),
				],
					[
						'title'   => __( 'Watermark Text', 'waterwoo-pdf' ),
						'id'      => 'wwpdf_footer_input_premium',
						'type'    => 'textarea',
						'desc'    => __( 'Shortcodes available, all caps, in brackets:', 'waterwoo-pdf' )
									. ' <code>[FIRSTNAME]</code> <code>[LASTNAME]</code> <code>[EMAIL]</code> <code>[PHONE]</code> <code>[DATE]</code>'
									. '<br>' . sprintf( __( '<a href="%s" target="_blank" rel="noopener">Upgrade</a> to use HTML and for more than one watermark placement, anywhere, on any page(s).', 'waterwoo-pdf' ), 'https://pdfink.com/?source=free_plugin&utm_campaign=woo' ),
				        'desc_at_end'=> true,
						'default' => __( 'Licensed to [FIRSTNAME] [LASTNAME], [EMAIL]', 'waterwoo-pdf' ),
						'class'   => 'wide-input',
						'css'     => 'min-height: 82px;',
					],
					[
						'title'    => __( 'Font Face', 'waterwoo-pdf' ),
						'id'       => 'wwpdf_font_premium',
						'type'     => 'select',
						'desc'     => __( 'Select a font for watermarks. M Sung will have limited Chinese characters, and Furat will have limited Arabic characters', 'waterwoo-pdf' ),
						'default'  => 'dejavusans',
						'class'    => 'chosen_select',
						'options'  => [
							'dejavusans'            => 'Deja Vu Sans',
							'dejavusanscondensed'   => 'Deja Vu Sans Condensed',
							'dejavuserif'           => 'Deja Vu Serif',
							'msungstdlight'         => 'M Sung',
							'aefurat'               => 'AE Furat',
							'helvetica'             => 'Helvetica',
							'times'                 => 'Times New Roman',
							'courier'               => 'Courier',
							'symbol'                => 'Symbol',
							'zapfdingbats'          => 'Zapf Dingbats',
						],
					],
					[
						'title'             => __( 'Font Size', 'waterwoo-pdf' ),
						'id'                => 'wwpdf_footer_size_premium',
						'type'              => 'number',
						'desc'              => __( 'Provide a number (suggested 10-40) for the font size', 'waterwoo-pdf' ),
						'default'           => '12',
						'custom_attributes' => [
							'min'  => 1,
							'max'  => 200,
							'step' => 1,
						],
					],
					[
						'title'    => __( 'Watermark Color', 'waterwoo-pdf' ),
						'id'       => 'wwpdf_footer_color_premium',
						'type'     => 'color',
						'desc'     => __( 'Color of the watermark, in hex. Defaults to black <code>#000000</code>', 'waterwoo-pdf' ),
						'default'  => '#000000',
					],
				[
					'id'        => 'wwpdf_overlay_rotate',
					'type'      => 'number',
					'title'     => __( 'Rotation', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Rotate the watermark on the page 0-359 degrees. Rotation is counter-clockwise.', 'waterwoo-pdf' ),
					'default'   =>  0,
					'custom_attributes' => [
						'min'       => 0,
						'max'       => 359,
						'step'      => 1,
					],
					'class'     => 'disabled',
					'row_class' => 'muted',
					'autoload'  => false,
				],
				[
					'id'        => 'wwpdf_overlay_finetune_X',
					'type'      => 'number',
					'title'     => __( 'X Fine Tuning', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Move the watermark left and right by adjusting this number. In millimeters. Default 0', 'waterwoo-pdf' ),
					'default'   => 15,
					'custom_attributes' => [
						'min'       => 0,
						'max'       => 250,
						'step'      => 1,
					],
					'class'     => 'disabled',
					'row_class' => 'muted',
					'autoload'  => false,
				],
					[
						'title'             => __( 'Y Fine Tuning', 'waterwoo-pdf' ),
						'id'                => 'wwpdf_footer_finetune_Y_premium',
						'type'              => 'number',
						'desc'              => __( 'Move the content up and down on the page by adjusting this number. In millimeters. Account for the height of your font/text!', 'waterwoo-pdf' ),
						'default'           => -10,
						'custom_attributes' => [
							'max'  => 2000,
							'step' => 1,
						],
					],
				[
					'id'   => 'page_setup',
					'type' => 'sectionend'
				],


				[
					'type' => 'title',
					'id'    => 'security_settings',
					'name' => __( 'Security Settings', 'waterwoo-pdf' ),
				],
					[
						'id'        => 'wwpdf_failure',
						'type'      => 'select',
						'title'     => __( 'Serve PDF if not watermarked?', 'waterwoo-pdf' ),
						'desc'      =>'<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' .  __( 'Should the un-watermarked PDF still be served if watermarking or encryption fails? Default no.', 'waterwoo-pdf' ),
						'default'   => 'no',
						'options'   => [
							'no'        => 'No',
							'yes'        => 'Yes',
						],
						'autoload'  => false,
						'class'     => 'disabled',
						'row_class' => 'muted',
					],
					[
						'id'        => 'wwpdf_encrypt',
						'type'      => 'select',
						'title'     => __( 'Encryption Level', 'waterwoo-pdf' ),
						'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'RC4 encryption is automatically set because it is required for protections & passwording.', 'waterwoo-pdf' ) . __( 'If your server doesn’t support RC4 encryption, watermarking will fail.', 'waterwoo-pdf' ) . __( 'Encryption can slow down and possibly stall your downloads, especially if you are watermarking files with images or embedded fonts.', 'waterwoo-pdf' ) . __( 'The RC4 stream cipher is not bullet-proof.', 'waterwoo-pdf' ) . __( 'Some browsers or PDF viewers may ignore protection settings, and some diligent customers might find ways to remove watermarks and passwords.', 'waterwoo-pdf' ),
						'options'   => [
							'0'     => 'RC4 40 bit',
							'4'     => 'None',
							'1'     => 'RC4 128 bit',
							'2'     => 'AES 128 bit',
							'3'     => 'AES 256 bit',
						],
						'default'   => '0',
						'autoload'  => false,
						'class'     => 'disabled',
						'row_class' => 'muted',
					],
					[
						'id'       => 'wwpdf_disable_printing',
						'type'     => 'checkbox',
						'title'    => __( 'Disable Printing', 'waterwoo-pdf' ),
						'desc'     => __( 'Check this box to make it more difficult for your PDF to be printed by the end consumer.', 'waterwoo-pdf' ),
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
					'id'        => 'wwpdf_disable_ass',
					'type'      => 'checkbox',
					'title'     => __( 'Disable Assembly', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Check this box to prevent insertion, rotation, or deletion of pages and creation of bookmarks or thumbnail images.', 'waterwoo-pdf' ),
					'default'   => 'no',
					'class'     => 'onetwoeightbit disabled',
					'row_class' => 'muted',
					'autoload'  => false,
				],
				[
					'id'        => 'wwpdf_disable_printing_high',
					'type'      => 'checkbox',
					'title'     => __( 'Disable High Res Printing', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Check this box to make it more difficult for your PDF to be printed beautifully by the end consumer.', 'waterwoo-pdf' ),
					'default'   => 'no',
					'class'     => 'onetwoeightbit disabled',
					'row_class' => 'muted',
					'autoload'  => false,
				],
				[
					'id'        => 'wwpdf_disable_fill_forms',
					'type'      => 'checkbox',
					'title'     => __( 'Disable Form Filling', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Check this box to prevent filling in existing interactive form fields (including signature fields).', 'waterwoo-pdf' ),
					'default'   => 'no',
					'class'     => 'onetwoeightbit disabled',
					'row_class' => 'muted',
					'autoload'  => false,
				],
				[
					'id'        => 'wwpdf_disable_extract',
					'type'      => 'checkbox',
					'title'     => __( 'Disable Accessibility', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Check this box to prevent extraction of text and graphics (in support of accessibility to users with disabilities or for other purposes). Some PDF readers already disable this.', 'waterwoo-pdf' ),
					'default'   => 'no',
					'class'     => 'onetwoeightbit disabled',
					'row_class' => 'muted',
					'autoload'  => false,
				],
					[
						'id'       => 'wwpdf_password',
						'type'     => 'text',
						'title'    => __( 'User Password (optional)', 'waterwoo-pdf' ),
						'desc'     => __( 'This is a password your end user will need to enter before viewing the PDF file.', 'waterwoo-pdf' )
									  . '<br>' . __( 'Enter <code>email</code> to set the password automagically as the user\'s checkout email address.', 'waterwoo-pdf' ),
						'autoload' => false,
					],
				[
					'id'        => 'wwpdf_password_owner',
					'type'      => 'text',
					'title'     => __( 'Owner Password (optional)', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'An owner password allows the end user to take control of a PDF. Leave blank and it will be set by MD5 hash automatically (it will be different for every download, and you will not know it).', 'waterwoo-pdf' ),
					'default'   => NULL,
					'desc_tip'  => true,
					'class'     => 'fortybit disabled',
					'row_class' => 'muted',
					'autoload'  => false,
				],
				[
					'id'        => 'wwpdf_protect_unlock',
					'type'      => 'checkbox',
					'title'     => __( 'Unlock with User Password?', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'By default PDF protections can only be removed with an owner password. Check to allow removal with a user password. USE WITH CAUTION.', 'waterwoo-pdf' ),
					'default'   => 'no',
					'class'     => 'fortybit disabled',
					'row_class' => 'muted',
					'autoload'  => false,
				],
				[
					'id'   => 'security_settings',
					'type' => 'sectionend'
				],
			];
		}
		return apply_filters_deprecated( 'wwpdf_settings_tab', [ $settings ], '6.3', '', 'The `wwpdf_settings_tab` filter hook is included in the full (paid) version of PDF Ink. Please upgrade to continue using it.' );

	}

	/**
	 * @param mixed $value
	 * @param array $values
	 *
	 * @return string
	 */
	public function woocommerce_admin_settings_sanitize_wwpdf_encrypt( $value, $values ) {

		return '0';

	}

	/**
	 * @param $value
	 * @param $values
	 *
	 * @return string
	 */
	public function woocommerce_admin_settings_sanitize_wwpdf_watermark_pages( $value, $values ) {

		return 'every';

	}

	/**
	 * @param $value
	 * @param $values
	 *
	 * @return string
	 */
	public function woocommerce_admin_settings_sanitize_return_zero( $value, $values ) {

		return 0;

	}

	/**
	 * @param $value
	 * @param $values
	 *
	 * @return string
	 */
	public function woocommerce_admin_settings_sanitize_return_one( $value, $values ) {

		return 1;

	}

	/**
	 * @param $value
	 * @param $values
	 *
	 * @return string
	 */
	public function woocommerce_admin_settings_sanitize_return_minus_one( $value, $values ) {

		return '-1';

	}

	/**
	 * @param $value
	 * @param $values
	 *
	 * @return string
	 */
	public function woocommerce_admin_settings_sanitize_return_no( $value, $values ) {

		return 'no';

	}

	/**
	 * @param $value
	 *
	 * @return void
	 */
	public function pdfink_css( $value ) { ?>

		<style>button.is-primary{padding:0.5rem 2em;font-size:1.5em;border-radius:8px;background-color:#D15A45;border-color:#D15A45;color:white;}</style>

		<?php
	}


	/**
	 * @param $value
	 *
	 * @return void
	 */
	public function pdfink_intro( $value ) {

		$svg_url = plugins_url('assets/svg/pdfink-lite-sprite.svg#pdf-delivery', dirname( __FILE__ ) );
		?>
		<style>button.is-primary{padding:0.5rem 2em;font-size:1.5em;border-radius:8px;background-color:#D15A45;border-color:#D15A45;color:white;}</style>
		<div style="display:flex;align-items:center;justify-content:space-between;">
			<div style="order:2">
				<a href="https://pdfink.com/?source=free_plugin&utm_campaign=woo" rel="noopener" target="_blank">
					<svg width="300px" height="225px">
						<use href="<?php echo esc_url( $svg_url ); ?>" />
					</svg>
				</a>
			</div>

			<div style="order:1">
				<p style="font-size:1.5em;font-weight:700;">
					<?php _e( 'PDF Ink Lite is rudimentary and may not work on every PDF. Test before going live, and remember, it\'s free!', 'waterwoo-pdf' ); ?>
				</p>
				<p style="font-size:1.4em">
					<?php echo sprintf( __( 'The only watermarking plugin for WooCommerce that works with <strong>any and every</strong> PDF is the <a href="%s" target="_blank" rel="noopener">PDF Ink upgrade combined with the SetaPDF-Stamper add-on</a>.', 'waterwoo-pdf' ), 'https://pdfink.com/documentation/libraries/#recommendation?source=free_plugin&utm_campaign=woo' ); ?>
				</p>
				<p style="font-size:1.3em">
					<?php echo sprintf( __( 'Greyed-out settings below are included in the full (paid) plugin version. <a href="%s" target="_blank" rel="noopener">PDF Ink (the upgrade for this plugin)</a> will provide you with <a href="%s">many more features</a>.', 'waterwoo-pdf' ), 'https://pdfink.com/?source=free_plugin&utm_campaign=woo', admin_url( 'admin.php?page=wc-settings&tab=pdf-ink-lite&section=more_info' ) ); ?>
				</p>
			</div>
		</div>
			<?php

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
			pdfink_log_output();
		} else if ( 'more_info' === $current_section ) {
			$hide_save_button = true;
			pdfink_more_info_screen();
		} else {
			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::output_fields( $settings );
		}

	}

}