<?php defined( 'ABSPATH' ) || exit;

class WWPDF_Settings_EDD {

	public function __construct() {

		// Add section for EDDiMark settings under the Extensions tab
		add_filter( 'edd_settings_sections_extensions',                 [ $this, 'edd_settings_sections_extensions' ] );

		// Add settings to the EDDiMark section
		add_filter( 'edd_settings_extensions',                          [ $this, 'edd_settings_extensions' ], 11 );

		// Sanitize EDDiMark section settings
		add_filter( 'edd_settings_extensions-pdf_ink_lite_sanitize',    [ $this, 'sanitize_input' ], 10, 1 );

		add_action( 'edd_pdfink_cta_tb',                                'pdfink_cta_tb', 10, 1 );

		add_action( 'edd_pdfink_intro',                                 [ $this, 'pdfink_intro' ], 10, 1 );

	}

	/**
	 * @param $args
	 *
	 * @return void
	 */
	public function pdfink_intro( $args ) {

		$svg_url = plugins_url('assets/svg/pdfink-lite-sprite.svg#pdf-delivery', dirname( __FILE__ ) );
		?>

		<div style="display:flex;align-items:center;justify-content:space-between;">
			<div style="order:2">
				<a href="https://pdfink.com/?source=free_plugin&utm_campaign=edd" rel="noopener" target="_blank">
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
					<?php echo sprintf( __( 'The only watermarking plugin for Easy Digital Downloads that works with <strong>any and every</strong> PDF is the <a href="%s" target="_blank" rel="noopener">PDF Ink upgrade combined with the SetaPDF-Stamper add-on</a>.', 'waterwoo-pdf' ), 'https://pdfink.com/documentation/libraries/#recommendation?source=free_plugin&utm_campaign=edd' ); ?>
				</p>
				<p style="font-size:1.3em">
					<?php echo sprintf( __( 'Greyed-out settings below are included in the <a href="%s" target="_blank" rel="noopener">full (paid) PDF Ink version</a>, which provides <a href="%s">many more features</a>.', 'waterwoo-pdf' ), 'https://pdfink.com/?source=free_plugin&utm_campaign=edd', admin_url( 'admin.php?page=wc-settings&tab=pdf-ink-lite&section=more_info' ) ); ?>
				</p>
			</div>
		</div>
		<?php

	}

	/**
	 * Add settings section to EDD Extensions tab
	 *
	 * @param array $sections
	 *
	 * @return array
	 */
	public function edd_settings_sections_extensions( array $sections ): array {

		$sections['pdf_ink_lite'] = __( 'PDF Ink Lite', 'waterwoo-pdf' );
		return $sections;

	}

	/**
	 * Add watermarking settings to the EDD Extensions tab
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	function edd_settings_extensions( array $settings ): array {

		return array_merge( $settings, [

			'pdf_ink_lite' => [

				'pdfink_cta_tb' => [
					'type'      => 'hook',
					'id'        => 'pdfink_cta_tb',
					'name'      => '',
				],
				'pdfink_intro' => [
					'type'      => 'hook',
					'id'        => 'pdfink_intro',
					'name'      => '',
				],
				'general_settings' => [
					'id'        => 'general_settings',
					'name'      => '<h3>' . __( 'PDF Ink Lite Settings', 'waterwoo-pdf' ) . '</h3>',
					'desc'      => '',
					'type'      => 'header',
					'size'      => 'large',
				],
				'eddimark_global' => [
					'id'        => 'eddimark_global',
					'name'      => __( 'Enable Watermarking', 'waterwoo-pdf' ),
					'desc'      => __( 'Check to watermark PDFs sold via Easy Digital Downloads using the settings below.', 'waterwoo-pdf' ),
					'type'      => 'checkbox',
				],
				'eddimark_files' => [
					'id'        => 'eddimark_files',
					'name'      => 'File(s) to Watermark',
					'desc'      => '<br>' . __( 'List file name(s) of PDF(s), one per line, e.g.,', 'waterwoo-pdf' ) . ' <code>upload.pdf</code> ' . __( 'or', 'waterwoo-pdf' ) . ' <code>my_pdf.pdf</code>. ' . __( 'Case-sensitive.', 'waterwoo-pdf' )
                                   . '<br>' . __( 'If left blank and the Global checkbox above is checked, <strong>all</strong> PDFs sold through EDD will be watermarked.', 'waterwoo-pdf' )
								   . '<br>' . __( 'But if the global checkbox is checked and files are listed here, those files listed will <strong>not</strong> be watermarked.', 'waterwoo-pdf' )
                                   . '<br><br>' . sprintf( __( '<a href="%s" target="_blank" rel="noopener">Upgrade</a> for easier file control.', 'waterwoo-pdf' ), 'https://pdfink.com/?source=free_plugin&utm_campaign=woo' ),
					'type'      => 'textarea',
					'size'      => 'regular',
				],
			'eddimark_rtl' => [
				'id'        => 'eddimark_rtl',
				'name'      => __( 'Right to Left Watermarking', 'waterwoo-pdf' ),
				'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Check to switch from default left-to-right (LTR) to right-to-left (RTL), for Arabic, Hebrew, etc.', 'waterwoo-pdf' ),
				'type'      => 'checkbox',
				'class'     => 'settings-row-muted',
				'field_class'=> 'disabled',
			],
			'eddimark_start_pg' => [
				'id'        => 'eddimark_start_pg',
				'name'      => __( 'Start Page', 'waterwoo-pdf' ),
				'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Provide a number to indicate the page you wish watermarking to begin on. Defaults to page 1.', 'waterwoo-pdf' ),
				'type'      => 'number',
				'size'      => 'small',
				'std'       => '1',
				'min'       => '1',
				'step'      => '1',
				'class'     => 'settings-row-muted',
				'field_class'=> 'disabled',
			],
			'eddimark_end_pg' => [
				'id'        => 'eddimark_end_pg',
				'type'      => 'text',
				'name'      => __( 'End Page', 'waterwoo-pdf' ),
				'std'       => 'last',
				'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Provide a number to indicate the page you wish watermarking to end on. Type \'last\' to indicate last page. Defaults to last page', 'waterwoo-pdf' ),
				'class'     => 'settings-row-muted',
				'field_class'=> 'disabled',
			],
			'eddimark_wmk_pgs' => [
				'id'        => 'eddimark_wmk_pgs',
				'name'      => __( 'Pages to watermark', 'waterwoo-pdf' ),
				'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Watermark every page, the first page only, the last page only, every odd page, every even page, or custom page range. Defaults to `Every page`', 'waterwoo-pdf' ),
				'type'      => 'select',
				'std'       => 'every',
				'options'   => [
					'every' => 'Every page',
					'first' => 'First page only',
					'last'  => 'Last page only',
					'odd'   => 'Odd pages',
					'even'  => 'Even pages',
					'custom'  => 'Custom',
				],
				'class'     => 'settings-row-muted',
				'field_class'=> 'disabled',
			],

				'eddimark_margin_top_bottom' => [
					'id'        => 'eddimark_margin_top_bottom',
					'name'      => __( 'Top/bottom margin', 'waterwoo-pdf' ),
					'type'      => 'number',
					'std'       => '0',
					'size'      => 'small',
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'In millimeters. Yes, metric! Defaults to 0', 'waterwoo-pdf' ),
					'class'     => 'settings-row-muted',
					'field_class'=> 'disabled',
				],
				'eddimark_margin_left_right' => [
					'id'        => 'eddimark_margin_left_right',
					'name'      => __( 'Left/right margin', 'waterwoo-pdf' ),
					'type'      => 'number',
					'std'       => '0',
					'size'      => 'small',
					'desc'      => __( 'In millimeters. Yes, metric! Defaults to 0', 'waterwoo-pdf' ),
				],

				'page_setup' => [
					'id'        => 'page_setup',
					'name'      => '<h3>' . __( 'Page Setup', 'waterwoo-pdf' ) . '</h3>',
					'desc'      => '',
					'type'      => 'header',
					'size'      => 'large',
				],
				'eddimark_f_input' => [
					'id'        => 'eddimark_f_input',
					'name'      => __( 'Watermark Text', 'waterwoo-pdf' ),
					'desc'      => __( 'Shortcodes available, all caps, in brackets:', 'waterwoo-pdf' )
								   . ' <code>[FIRSTNAME]</code> <code>[LASTNAME]</code> <code>[EMAIL]</code> <code>[PHONE]</code> <code>[DATE]</code>'
					               . '<br>' . sprintf( __( '<a href="%s" target="_blank" rel="noopener">Upgrade</a> to use HTML and for more than one watermark placement, anywhere, on any page(s).', 'waterwoo-pdf' ), 'https://pdfink.com/?source=free_plugin&utm_campaign=edd' ),
					'type'      => 'textarea',
					'std'       => '',
					'autoload'  => false,
				],
				'eddimark_font' => [
					'id'        => 'eddimark_font',
					'name'      => __( 'Font Face', 'waterwoo-pdf' ),
					'type'      => 'select',
					'options'   => [
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
					'std'       => 'dejavusans',
					'desc'      => __( 'Select a font for watermarks. M Sung will have limited Chinese characters, and Furat will have limited Arabic characters', 'waterwoo-pdf' ),
				],
				'eddimark_f_size' => [
					'id'        => 'eddimark_f_size',
					'name'      => __( 'Font Size', 'waterwoo-pdf' ),
					'desc'      => __( 'Provide a number (suggested 10-40) for the font size', 'waterwoo-pdf' ),
					'type'      => 'number',
					'std'       => '36',
					'min'       => 1,
					'max'       => 500,
					'step'      => 1,
					'size'      => 'small',
				],
				'eddimark_f_color' => [
					'id'        => 'eddimark_f_color',
					'name'      => __( 'Watermark Color', 'waterwoo-pdf' ),
					'desc'      => '<br>' . __( 'Color of the watermark, in hex. Defaults to black <code>#000000</code>', 'waterwoo-pdf' ),
					'type'      => 'color',
					'std'       => '#CCCCCC',
				],
				'eddimark_f_rotate' => [
					'id'        => 'eddimark_f_rotate',
					'name'      => __( 'Rotation', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Rotate the watermark on the page 0-359 degrees. Rotation is counter-clockwise.', 'waterwoo-pdf' ),
					'type'      => 'number',
					'std'       => 0,
					'min'       => 0,
					'max'       => 359,
					'step'      => 1,
					'size'      => 'small',
					'class'     => 'settings-row-muted',
					'field_class'=> 'disabled',
				],
				'eddimark_f_finetune_X' => [
					'id'        => 'eddimark_f_finetune_X',
					'name'      => __( 'X Fine Tuning', 'waterwoo-pdf' ),
					'type'      => 'number',
					'size'      => 'small',
					'std'       => '0',
					'min'       => '0',
					'step'      => '1',
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Move the watermark left and right by adjusting this number. In millimeters. Default 0', 'waterwoo-pdf' ),
					'class'     => 'settings-row-muted',
					'field_class'=> 'disabled',
				],
				'eddimark_f_finetune_Y' => [
					'id'        => 'eddimark_f_finetune_Y',
					'name'      => __( 'Y Fine Tuning', 'waterwoo-pdf' ),
					'type'      => 'number',
					'std'       => -10,
					'size'      => 'small',
					'desc'      => __( 'Move the content up and down on the page by adjusting this number. In millimeters.Account for the height of your font/text!', 'waterwoo-pdf' ),
				],

				// File protections
				'file_protections' => [
					'id'        => 'security_settings',
					'name'      => '<h3>' . __( 'Security Settings', 'waterwoo-pdf' ) . '</h3>',
					'type'      => 'header',
				],
				'eddimark_failure' => [
					'id'        => 'eddimark_failure',
					'name'      => __( 'Serve PDF if not watermarked?', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Should the un-watermarked PDF still be served if watermarking or encryption fails? Default no.', 'waterwoo-pdf' ) . '<br>' . __( 'If no, the customer will receive an error message saying if file preparation has failed.', 'waterwoo-pdf' ),
					'type'      => 'select',
					'options'   => [
						'yes'   => 'Yes',
						'no'    => 'No',
					],
					'std'       => 'no',
					'class'     => 'settings-row-muted',
					'field_class'=> 'disabled',
				],
				'eddimark_encrypt' => [
					'id'        => 'eddimark_encrypt',
					'name'      => __( 'Encryption level', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'RC4 encryption is automatically set because it is required for protections & passwording.', 'waterwoo-pdf' ) . __( 'If your server doesn’t support RC4 encryption, watermarking will fail.', 'waterwoo-pdf' ) . __( 'Encryption can slow down and possibly stall your downloads, especially if you are watermarking files with images or embedded fonts.', 'waterwoo-pdf' ) . __( 'The RC4 stream cipher is not bullet-proof.', 'waterwoo-pdf' ) . __( 'Some browsers or PDF viewers may ignore protection settings, and some diligent customers might find ways to remove watermarks and passwords.', 'waterwoo-pdf' ),
					'type'      => 'select',
					'options'   => [
						'4'     => 'None',
						'0'     => 'RSA 40 bit',
						'1'     => 'RSA 128 bit',
						'2'     => 'AES 128 bit',
						'3'     => 'AES 256 bit',
					],
					'std'       => '0',
					'class'     => 'settings-row-muted eddimark_encrypt_select',
					'field_class'=> 'disabled',
				],
				'eddimark_disable_print' => [
					'id'        => 'eddimark_disable_print',
					'name'      => __( 'Disable Printing', 'waterwoo-pdf' ),
					'desc'      => __( 'Check this box to make it more difficult for your PDF to be printed by the end consumer.', 'waterwoo-pdf' ),
					'type'      => 'checkbox',
				],
				'eddimark_disable_copy' => [
					'id'        => 'eddimark_disable_copy',
					'name'      => __( 'Disable Copying', 'waterwoo-pdf' ),
					'desc'      => __( 'Check this box to prevent your end consumer from copying and pasting content from your PDF.', 'waterwoo-pdf' ),
					'type'      => 'checkbox',
				],
				'eddimark_disable_mods' => [
					'id'        => 'eddimark_disable_mods',
					'name'      => __( 'Disable Editing', 'waterwoo-pdf' ),
					'desc'      => __( 'Check this box to prevent editing/modification of your PDF by the end consumer in Acrobat.', 'waterwoo-pdf' ),
					'type'      => 'checkbox',
				],
				'eddimark_disable_annot' => [
					'id'        => 'eddimark_disable_annot',
					'name'      => __( 'Disable Annotations', 'waterwoo-pdf' ),
					'desc'      => __( 'Check this box to prevent the addition or modification of text annotations/comments, and filling of interactive form fields. If "editing and annotation" are both allowed, customers can create or modify interactive form fields (including signature fields).', 'waterwoo-pdf' ),
					'type'      => 'checkbox',
				],

			'eddimark_disable_ass' => [
				'id'        => 'eddimark_disable_ass',
				'name'      => __( 'Disable Assembly', 'waterwoo-pdf' ),
				'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Check this box to prevent insertion, rotation, or deletion of pages and creation of bookmarks or thumbnail images.', 'waterwoo-pdf' ),
				'type'      => 'checkbox',
				'class'     => 'settings-row-muted',
				'field_class'=> 'disabled',
			],
			'eddimark_disable_print_high' => [
				'id'        => 'eddimark_disable_print_high',
				'name'      => __( 'Disable High Res Printing', 'waterwoo-pdf' ),
				'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Check this box to make it more difficult for your PDF to be printed beautifully by the end consumer.', 'waterwoo-pdf' ),
				'type'      => 'checkbox',
				'class'     => 'settings-row-muted',
				'field_class'=> 'disabled',
			],
			'eddimark_disable_fill_forms' => [
				'id'        => 'eddimark_disable_fill_forms',
				'name'      => __( 'Disable Form Filling', 'waterwoo-pdf' ),
				'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Check this box to prevent filling in existing interactive form fields (including signature fields).', 'waterwoo-pdf' ),
				'type'      => 'checkbox',
				'class'     => 'settings-row-muted',
				'field_class'=> 'disabled',
			],
			'eddimark_disable_extract' => [
				'id'        => 'eddimark_disable_extract',
				'name'      => __( 'Disable Accessibility', 'waterwoo-pdf' ),
				'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'Check this box to prevent extraction of text and graphics (in support of accessibility to users with disabilities or for other purposes). Some PDF readers already disable this.', 'waterwoo-pdf' ),
				'type'      => 'checkbox',
				'class'     => 'settings-row-muted',
				'field_class'=> 'disabled',
			],
				'eddimark_pw' => [
					'id'        => 'eddimark_pw',
					'name'      => __( 'User Password (optional)', 'waterwoo-pdf' ),
					'desc'      => '<br>' . __( 'This is a password your end user will need to enter before viewing the PDF file.', 'waterwoo-pdf' ),
                    'type'      => 'text',
					'std'       => '',
				],
				'eddimark_pw_owner' => [
					'id'        => 'eddimark_pw_owner',
					'name'      => __( 'Owner Password (optional)', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a>'
								   . '<br>' . __( 'An owner password allows the end user to take control of a PDF. Leave blank and it will be set by MD5 hash automatically (it will be different for every download, and you will not know it).', 'waterwoo-pdf' ),
					'type'      => 'text',
					'std'       => null,
					'class'     => 'settings-row-muted',
					'field_class'=> 'disabled',
				],
				'eddimark_protect_unlock' => [
					'id'        => 'eddimark_protect_unlock',
					'name'      => __( 'Unlock with User Password?', 'waterwoo-pdf' ),
					'desc'      => '<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a> ' . __( 'By default PDF protections can only be removed with an owner password. Check to allow removal with a user password. USE WITH CAUTION.', 'waterwoo-pdf' ),
					'type'      => 'checkbox',
					'class'     => 'settings-row-muted',
					'field_class'=> 'disabled',
				],

				'housekeeping' => [
					'id'        => 'housekeeping',
					'name'      => '<h3>' . __( 'Housekeeping', 'waterwoo-pdf' ) . '</h3>',
					'type'      => 'header',
					'desc'      => __( 'Easy Digital Downloads debug logs can be found at Downloads > Tools > Debug Log', 'waterwoo-pdf' ),
				],
				'eddimark_lnt' => [
					'id'        => 'eddimark_lnt',
					'name'     => __( 'Leave No Trace', 'waterwoo-pdf' ),
					'desc'      => __( 'If this box is checked and you uninstall PDF Ink Lite, all your settings will be deleted from your Wordpress database.', 'waterwoo-pdf' ),
					'type'      => 'checkbox',
				],
				'pdfink_attribution' => [
					'id'        => 'pdfink_attribution',
					'name'     => __( 'Give Us Attribution', 'waterwoo-pdf' ),
					'desc'      => __( 'We\'d love it if you check this box and allow us to add a tiny, invisible link to the second page of your marked PDF files, giving PDF Ink Lite credit.', 'waterwoo-pdf' ),
					'type'      => 'checkbox',
				],

			]

		] );

	}

	/**
	 * Sanitize our EDD settings when saved by user
	 * We aren't going to save some of them at all
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_input( $input ) {

		if ( isset( $input['eddimark_rtl'] ) ) {
			unset( $input['eddimark_rtl'] );
		}
		if ( isset( $input['eddimark_start_pg'] ) ) {
			$input['eddimark_start_pg'] = 1;
		}
		if ( isset( $input['eddimark_end_pg'] ) ) {
			unset( $input['eddimark_end_pg'] );
		}
		if ( isset( $input['eddimark_wmk_pgs'] ) ) {
			unset( $input['eddimark_wmk_pgs'] );
		}
		if ( isset( $input['eddimark_margin_top_bottom'] ) ) {
			$input['eddimark_margin_top_bottom'] = 0;
		}
		if ( isset( $input['eddimark_f_rotate'] ) ) {
			$input['eddimark_f_rotate'] = 0;
		}
		if ( isset( $input['eddimark_f_finetune_X'] ) ) {
			$input['eddimark_f_finetune_X'] = 0;
		}
		if ( isset( $input['eddimark_failure'] ) ) {
			unset( $input['eddimark_failure'] );
		}
		if ( isset( $input['eddimark_encrypt'] ) ) {
			unset( $input['eddimark_encrypt'] );
		}
		if ( isset( $input['eddimark_disable_ass'] ) ) {
			unset( $input['eddimark_disable_ass'] );
		}
		if ( isset( $input['eddimark_disable_print_high'] ) ) {
			unset( $input['eddimark_disable_print_high'] );
		}
		if ( isset( $input['eddimark_disable_fill_forms'] ) ) {
			unset( $input['eddimark_disable_fill_forms'] );
		}
		if ( isset( $input['eddimark_disable_extract'] ) ) {
			unset( $input['eddimark_disable_extract'] );
		}
		if ( isset( $input['eddimark_pw_owner'] ) ) {
			unset( $input['eddimark_pw_owner'] );
		}
		if ( isset( $input['eddimark_protect_unlock'] ) ) {
			unset( $input['eddimark_protect_unlock'] );
		}
		return $input;

	}

}