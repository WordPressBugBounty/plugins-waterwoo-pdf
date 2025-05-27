<?php defined( 'ABSPATH' ) || exit;

class WWPDF_Settings_DLM {

	public function __construct() {

		add_filter( 'dlm_settings', [ $this, 'dlm_settings' ], 11, 1 );

		add_action( 'dlm_tab_content_pdf_ink_lite', 'pdfink_cta_tb', 10, 1 );

	}

	/**
	 * Alter the default Download Monitor settings array.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function dlm_settings( array $settings ): array {

		$settings['pdf_ink_lite'] = [

			'title'    => __( 'PDF Ink Lite', 'waterwoo-pdf' ),
			'sections' => [
				'pdfink_general' => [
					'priority' => 80,
					'title'    => __( 'PDF Options', 'waterwoo-pdf' ),
					'fields'   => [
						[
							'name'     => 'pdfink_intro',
							'type'     => 'callback',
							'callback' => 'dlm_pdfink_intro',
						],
						// Stamp setup
						[
							'name'     => 'dlm_stamper_global',
							'type'     => 'checkbox',
							'label'    => __( 'Enable Global PDF Stamping', 'waterwoo-pdf' ),
							'desc'     => __( 'Check to stamp <em>all</em> PDFs sold through your DLM shop using the settings below.', 'waterwoo-pdf' ),
							'std'      => '0',
							'cb_label' => __( 'Enable', 'waterwoo-pdf' ),
							'priority' => 1,
						],
						[
							'name'     => 'dlm_stamper_files',
							'label'    => 'File(s) to Watermark',
							'desc'     => __( 'List FILE NAME(S) of PDF(s), one per line, e.g., <code>upload.pdf</code> or <code>my_pdf.pdf</code>. Case-sensitive.', 'waterwoo-pdf' ) . '<br>'
							              . __( 'If left blank and the Global checkbox above is checked, <strong>all</strong> PDFs sold through DLM will be watermarked.', 'waterwoo-pdf' ) . ' '
							              . __( 'But if the global checkbox is checked and files are listed here, those files listed will <strong>not</strong> be watermarked.', 'waterwoo-pdf' ) . '<br>'
							              . __( 'Want something easier? Upgrade to PDF Ink -- pdfink.com!', 'waterwoo-pdf' ),
							'type'     => 'textarea',
							'priority' => 2,

						],

						[
							'name'     => 'dlm_stamper_pages',
							'type'     => 'callback',
							'callback' => 'dlm_stamper_pages',
							'label'    => __( 'Pages to stamp', 'waterwoo-pdf' ),
							'priority' => 3,
						],
						[
							'name'     => 'dlm_stamper_start_pg',
							'type'     => 'callback',
							'callback' => 'dlm_stamper_start_pg',
							'label'    => __( 'Start Page', 'waterwoo-pdf' ),
							'priority' => 4,
						],
						[
							'name'     => 'dlm_stamper_end_pg',
							'type'     => 'callback',
							'callback' => 'dlm_stamper_end_pg',
							'label'    => __( 'End Page', 'waterwoo-pdf' ),
							'priority' => 5,
						],
						[
							'name'     => 'dlm_stamper_margin_lr',
							'type'     => 'number',
							'label'    => __( 'Left/right "margin"', 'waterwoo-pdf' ),
							'desc'     => __( 'In millimeters. Yes, metric! Defaults to 0', 'waterwoo-pdf' ),
							'std'      => 0,
							'priority' => 6,
						],

						[
							'name'     => 'dlm_stamper_stamp',
							'type'     => 'textarea',
							'label'    => __( 'Watermark Text', 'waterwoo-pdf' ),
							'desc'     => '<small>' . __( 'Shortcodes available, all caps, in brackets:', 'waterwoo-pdf' ) . '<br /><code>[FIRSTNAME]</code> <code>[LASTNAME]</code> <code>[EMAIL]</code> <code>[PHONE]</code> <code>[DATE]</code></small>',
							'std'      => 'Hello World!',
							'priority' => 7,
						],
						[
							'name'     => 'dlm_stamper_font',
							'type'     => 'select',
							'label'    => __( 'Font Face', 'waterwoo-pdf' ),
							'desc'     => __( 'Select a font for watermarks. M Sung will have limited Chinese characters, and Furat will have limited Arabic characters', 'waterwoo-pdf' ),
							'std'      => 'dejavusans',
							'options'  => [
								'dejavusans'          => 'Deja Vu Sans',
								'dejavusanscondensed' => 'Deja Vu Sans Condensed',
								'dejavuserif'         => 'Deja Vu Serif',
								'msungstdlight'       => 'M Sung',
								'aefurat'             => 'AE Furat',
								'helvetica'           => 'Helvetica',
								'times'               => 'Times New Roman',
								'courier'             => 'Courier',
								'symbol'              => 'Symbol',
								'zapfdingbats'        => 'Zapf Dingbats',
							],
							'priority' => 8,
						],
						[
							'name'     => 'dlm_stamper_size',
							'type'     => 'text',
							'label'    => __( 'Font Size', 'waterwoo-pdf' ),
							'desc'     => __( 'Provide a number (suggested 10-40) for the font size', 'waterwoo-pdf' ),
							'std'      => 24,
							'priority' => 9,
						],
						[
							'name'     => 'dlm_stamper_color',
							'type'     => 'text',
							'label'    => __( 'Watermark Color', 'waterwoo-pdf' ),
							'desc'     => __( 'Color of the watermark, in hex. Defaults to black <code>#000000</code>', 'waterwoo-pdf' ),
							'std'      => '#000000',
							'priority' => 10,
						],
						[
							'name'     => 'dlm_stamper_opacity',
							'type'     => 'callback',
							'callback' => 'dlm_stamper_opacity',
							'label'    => __( 'Opacity', 'waterwoo-pdf' ),
							'priority' => 11,
						],
						[
							'name'     => 'dlm_stamper_rotate',
							'type'     => 'callback',
							'callback' => 'dlm_stamper_rotate',
							'label'    => __( 'Rotation', 'waterwoo-pdf' ),
							'priority' => 12,
						],
						[
							'name'     => 'dlm_stamper_finetune_Y',
							'type'     => 'text',
							'label'    => __( 'Y Fine Tuning', 'waterwoo-pdf' ),
							'desc'     => __( 'Move the content up and down on the page by adjusting this number. In millimeters. If this number is longer/higher than the length/height of your PDF, it will default back to -10 (10 millimeters from the bottom of the page). Account for the height of your font/text!', 'waterwoo-pdf' ),
							'std'      => -10,
							'priority' => 13,
						],
						[
							'name'     => 'dlm_stamper_failure',
							'type'     => 'callback',
							'callback' => 'dlm_stamper_failure',
							'label'    => __( 'Serve PDF if not stamped?', 'download-monitor-stamper' ),
							'priority' => 14,
						],
						[
							'name'     => 'dlm_stamper_encryption',
							'type'     => 'callback',
							'callback' => 'dlm_stamper_encryption',
							'label'    => __( 'Encryption Level', 'waterwoo-pdf' ),
							'priority' => 15,
						],
						[
							'name'     => 'dlm_stamper_dis_printing',
							'type'     => 'checkbox',
							'label'    => __( 'Disable Printing', 'waterwoo-pdf' ),
							'desc'     => __( 'Check this box to make it much more difficult for your PDF to be printed by the end consumer.', 'waterwoo-pdf' ),
							'std'      => '0',
							'cb_label' => __( 'Disable', 'waterwoo-pdf' ),
							'priority' => 16,
						],
						[
							'name'     => 'dlm_stamper_dis_copy',
							'type'     => 'checkbox',
							'label'    => __( 'Disable Copying', 'waterwoo-pdf' ),
							'desc'     => __( 'Check this box to prevent your end consumer from copying and pasting content from your PDF.', 'waterwoo-pdf' ),
							'std'      => '0',
							'cb_label' => __( 'Disable', 'waterwoo-pdf' ),
							'priority' => 17,
						],
						[
							'name'     => 'dlm_stamper_dis_mods',
							'type'     => 'checkbox',
							'label'    => __( 'Disable Editing', 'waterwoo-pdf' ),
							'desc'     => __( 'Check this box to prevent editing of your PDF by the end consumer in Acrobat.', 'waterwoo-pdf' ),
							'std'      => '0',
							'cb_label' => __( 'Disable', 'waterwoo-pdf' ),
							'priority' => 18,
						],
						[
							'name'     => 'dlm_stamper_dis_annot',
							'type'     => 'checkbox',
							'label'    => __( 'Disable Annotations', 'waterwoo-pdf' ),
							'desc'     => __( 'Check this box to prevent the addition of annotations and forms to the file.', 'waterwoo-pdf' ),
							'std'      => '0',
							'cb_label' => __( 'Disable', 'waterwoo-pdf' ),
							'priority' => 19,
						],
						[
							'name'     => 'dlm_stamper_disable_fill_forms',
							'type'     => 'callback',
							'callback' => 'dlm_stamper_disable_fill_forms',
							'label'    => __( 'Disable Form Filling', 'waterwoo-pdf' ),
							'priority' => 20,
						],
						[
							'name'     => 'dlm_stamper_disable_extract',
							'type'     => 'callback',
							'callback' => 'dlm_stamper_disable_extract',
							'label'    => __( 'Disable Extraction', 'waterwoo-pdf' ),
							'priority' => 21,
						],
						[
							'name'     => 'dlm_stamper_disable_ass',
							'type'     => 'callback',
							'callback' => 'dlm_stamper_disable_ass',
							'label'    => __( 'Disable Assembly', 'waterwoo-pdf' ),
							'priority' => 22,
						],
						[
							'name'     => 'dlm_stamper_disable_print_high',
							'type'     => 'callback',
							'callback' => 'dlm_stamper_disable_print_high',
							'label'    => __( 'Disable High Res Printing', 'waterwoo-pdf' ),
							'priority' => 23,
						],
						[
							'name'     => 'dlm_stamper_pwd',
							'type'     => 'text',
							'label'    => __( 'PDF User Password', 'waterwoo-pdf' ),
							'desc'     => __( 'This is a password your end user will need to enter before viewing the PDF file.', 'waterwoo-pdf' ),
							'std'      => '',
							'priority' => 24,
						],
						[
							'name'     => 'dlm_stamper_owner_pwd',
							'type'     => 'callback',
							'callback' => 'dlm_stamper_owner_pwd',
							'label'    => __( 'PDF Owner Password', 'waterwoo-pdf' ),
							'desc'     => __( 'An owner password allows the end user to take control of a PDF. Leave blank and it will be set by MD5 hash automatically (it will be different for every download, and you will not know it).', 'waterwoo-pdf' ),
							'priority' => 25,
						],
						[
							'name'     => 'dlm_stamper_protect_unlock',
							'type'     => 'callback',
							'callback' => 'dlm_stamper_protect_unlock',
							'label'    => __( 'Unlock with User Password?', 'waterwoo-pdf' ),
							'desc'     => __( 'By default PDF protections can only be removed with an owner password. Check to allow removal with a user password. USE WITH CAUTION.', 'waterwoo-pdf' ),
							'priority' => 26,
						],
					],

				],
				'housekeeping'   => [
					'title'  => __( 'Housekeeping', 'waterwoo-pdf' ),
					'fields' => [
						[
							'name'     => 'dlm_stamper_lnt',
							'type'     => 'checkbox',
							'label'    => __( 'Leave No Trace?', 'waterwoo-pdf' ),
							'desc'     => __( 'If this box is checked and you uninstall PDF Ink Lite, all your settings will be deleted from your WordPress database.', 'waterwoo-pdf' )
							              . '<br>' . sprintf( __( 'Marked PDF files will accumulate in your PDF folder whether using Force downloads or not. To keep your server tidy, manually delete ad lib or </strong><a href="%s" target="_blank" rel="noopener">upgrade this plugin</a></strong> for better file handling and automatic cleaning.', 'waterwoo-pdf' ), 'https://pdfink.com/' ),
							'std'      => '0',
							'cb_label' => __( 'Enable', 'waterwoo-pdf' ),
						],
						[
							'name'     => 'pdfink_attribution',
							'type'     => 'checkbox',
							'label'    => __( 'Give Us Attribution', 'waterwoo-pdf' ),
							'desc'     => __( 'We\'d love it if you check this box and allow us to add a tiny, invisible link to the second page of your marked PDF files, giving PDF Ink Lite credit.', 'waterwoo-pdf' ),
							'std'      => '0',
							'cb_label' => __( 'Enable', 'waterwoo-pdf' ),
						],
					],
				],
				'logs'           => [
					'title'  => __( 'Logging', 'waterwoo-pdf' ),
					'fields' => [
						[
							'name'     => 'wwpdf_debug_mode',
							'type'     => 'checkbox',
							'label'    => __( 'Enable Logs?', 'waterwoo-pdf' ),
							'desc'     => __( 'Check to enable event/error logging. This can help with debugging.', 'waterwoo-pdf' ),
							'std'      => '0', // @todo this might be 0/1 whereas the Woo one might be on/off or yes/no
							'cb_label' => __( 'Enable', 'waterwoo-pdf' ),
						],
						[
							'name'     => 'wwpdf_logs',
							'type'     => 'callback',
							'callback' => 'pdfink_log_output',
						],
					],
				],
				'more_info'      => [
					'title'  => __( 'More Info', 'waterwoo-pdf' ),
					'fields' => [
						[
							'name'     => 'pdfink_info',
							'type'     => 'callback',
							'callback' => 'pdfink_more_info_screen',
						],
					],
				],
			],
		];
		// This was a pain to set up because DLM has a weeeeeeeird settings API, requiring each field has a priority,
		// otherwise the fields are essentially shuffled after they are delivered back to DLM.
		return $settings;

	}

}

/**
 * @param $args
 *
 * @return void
 */
function dlm_pdfink_intro() {

    $svg_url = plugins_url('assets/svg/pdfink-lite-sprite.svg#pdf-delivery', dirname( __FILE__ ) );
    ?>

    <div style="display:flex;align-items:center;justify-content:space-between;">
        <div style="order:2">
            <a href="https://pdfink.com?source=wordpress&utm_campaign=dlm" rel="noopener" target="_blank">
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
                <?php echo sprintf( __( 'The only watermarking plugin for Download Monitor that works with <strong>any and every</strong> PDF is <a href="%s" target="_blank" rel="noopener">PDF Ink combined with the SetaPDF-Stamper add-on</a>.', 'waterwoo-pdf' ), 'https://pdfink.com/documentation/libraries/#recommendation?source=wordpress&utm_campaign=edd' ); ?>
            </p>
            <p style="font-size:1.3em">
                <?php echo sprintf( __( 'Greyed-out settings below are included in the <a href="%s" target="_blank" rel="noopener">full (paid) PDF Ink version</a>, which provides <a href="%s">many more features</a>.', 'waterwoo-pdf' ), 'https://pdfink.com?source=wordpress&utm_campaign=edd', admin_url( 'admin.php?page=wc-settings&tab=pdf-ink-lite&section=more_info' ) ); ?>
            </p>
        </div>
    </div>
<?php
}

function dlm_stamper_pages() { ?>

	<div class="settings-row-muted">
		<select id="setting-dlm_stamper_pages" class="regular-text disabled" name="dlm_stamper_pages" disabled>
			<option value="all" selected="selected">Every page</option>
			<option value="first">First page only</option>
			<option value="last">Last page only</option>
			<option value="odd">Odd pages</option>
			<option value="even">Even pages</option>
			<option value="custom">Custom</option>
		</select>
		<p>
			<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;">
				<span class="dashicons dashicons-admin-network pdfink-upgrade"></span>
			</a>
			<?php _e( 'Stamp every page, the first page only, the last page only, every odd page, every even page, or custom page range. Defaults to `Every page`', 'waterwoo-pdf' ); ?>

		</p>
	</div>

	<?php
}

function dlm_stamper_start_pg() { ?>

	<div class="settings-row-muted"><input id="setting-dlm_stamper_start_pg" class="regular-text" type="number" name="dlm_stamper_start_pg" value="1" disabled >
		<p>
			<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;">
				<span class="dashicons dashicons-admin-network pdfink-upgrade"></span>
			</a>
			<?php _e( 'Provide a number to indicate the page you wish watermarking to begin on. Defaults to page 1.', 'waterwoo-pdf' ); ?>
		</p>
	</div>
	<?php

}

function dlm_stamper_end_pg() { ?>

	<div class="settings-row-muted">
		<input id="setting-dlm_stamper_end_pg" class="regular-text" type="text" name="dlm_stamper_end_pg" value="last" disabled >
		<p>
			<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;">
				<span class="dashicons dashicons-admin-network pdfink-upgrade"></span>
			</a>
			<?php _e( 'Provide a number to indicate the page you wish watermarking to end on. Type \'last\' to indicate last page. Defaults to last page', 'waterwoo-pdf' ); ?>
		</p>
	</div>
<?php

}

function dlm_stamper_margin_lr() { ?>

	<div class="settings-row-muted"><input id="setting-dlm_stamper_margin_lr" class="regular-text" type="number" name="dlm_stamper_margin_lr" value="0" disabled >
		<p>
			<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;">
				<span class="dashicons dashicons-admin-network pdfink-upgrade"></span>
			</a>
			<?php _e( 'In millimeters. Yes, metric! Defaults to 0', 'waterwoo-pdf' ); ?>
		</p>
	</div>
	<?php
}

function dlm_stamper_opacity() { ?>

	<div class="settings-row-muted">
		<input class="regular-text disabled" type="text" name="dlm_stamper_opacity" value="1" disabled>
		<p>
			<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;">
				<span class="dashicons dashicons-admin-network pdfink-upgrade"></span>
			</a> <?php _e( 'Make your stamp transparent. A value of 0 will be translucent, .5 means 50% opaque, .75 is 3/4 opaque, etc.', 'waterwoo-pdf' ); ?>
		</p>
	</div>
	<?php
}

function dlm_stamper_rotate() { ?>

	<div class="settings-row-muted">
		<input class="regular-text disabled" type="text" name="dlm_stamper_rotate" value="0" disabled>
		<p>
			<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;">
				<span class="dashicons dashicons-admin-network pdfink-upgrade"></span>
			</a> <?php _e( 'Rotate the stamp on the page 0-359 degrees', 'waterwoo-pdf' ); ?>
		</p>
	</div>
	<?php
}

function dlm_stamper_failure() { ?>
	<div class="settings-row-muted">

		<select id="setting-dlm_stamper_failure" class="regular-text disabled" name="dlm_stamper_failure" disabled>
			<option value="yes">Yes</option>
			<option value="no" selected>No</option>
		</select>
		<p>
			<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;">
				<span class="dashicons dashicons-admin-network pdfink-upgrade"></span>
			</a>
			<?php _e( 'Should the un-watermarked PDF still be served if watermarking or encryption fails? Default no.', 'waterwoo-pdf' );
			?>

		</p>
	</div>
<?php
}

function dlm_stamper_encryption() {

	$security_values = [];
	$security_values[] = get_option( 'dlm_stamper_dis_printing', '' );
	$security_values[] = get_option( 'dlm_stamper_dis_copy', '' );
	$security_values[] = get_option( 'dlm_stamper_dis_mods', '' );
	$security_values[] = get_option( 'dlm_stamper_dis_annot', '' );
	?>


	<div class="settings-row-muted">

		<select id="setting-dlm_stamper_encryption" class="regular-text disabled" name="dlm_stamper_encryption" disabled>
			<option value="none"';
			<?php if ( ! in_array( '1', $security_values ) ) { echo
				' selected="selected"';
			} ?>>None</option>
			<option value="RC440"';
			<?php if ( in_array( '1', $security_values ) ) { echo
				' selected="selected"';
			} ?>>RC4 40-bit</option>
			<option value="RC4128">RC4 128-bit</option>
			<option value="AES128">AES 128-bit</option>
			<option value="AES256">AES 256-bit</option>
		</select>
		<p>
			<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;">
				<span class="dashicons dashicons-admin-network pdfink-upgrade"></span>
			</a>
			<?php _e( 'RC4 encryption is automatically set because it is required for protections & passwording.', 'waterwoo-pdf' );
			_e( 'If your server doesn’t support RC4 encryption, watermarking will fail.', 'waterwoo-pdf' );
			_e( 'Encryption can slow down and possibly stall your downloads, especially if you are watermarking files with images or embedded fonts.', 'waterwoo-pdf' );
			_e( 'The RC4 stream cipher is not bullet-proof.', 'waterwoo-pdf' );
			_e( 'Some browsers or PDF viewers may ignore protection settings, and some diligent customers might find ways to remove watermarks and passwords.', 'waterwoo-pdf' );
			?>

		</p>
	</div>
<?php

}

function dlm_stamper_disable_fill_forms() { ?>

	<div class="settings-row-muted">
			<div class="wpchill-toggle">
				<input class="wpchill-toggle__input" id="setting-dlm_stamper_disable_fill_forms" name="dlm_stamper_disable_fill_forms" type="checkbox" value="1" class="disabled" disabled>
				<div class="wpchill-toggle__items">
					<span class="wpchill-toggle__track"></span>
					<span class="wpchill-toggle__thumb"></span>
					<svg class="wpchill-toggle__off" width="6" height="6" aria-hidden="true" role="img" focusable="false" viewBox="0 0 6 6">
						<path d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
					</svg>
					<svg class="wpchill-toggle__on" width="2" height="6" aria-hidden="true" role="img" focusable="false" viewBox="0 0 2 6">
						<path d="M0 0h2v6H0z"></path>
					</svg>
				</div>
			</div>
			<label>Disable</label>
		<p>
			<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a>
			<?php _e( 'Check this box to disable filling in existing interactive form fields (including signature fields).', 'waterwoo-pdf' ); ?>
		</p>
	</div>
<?php

}

function dlm_stamper_disable_extract() { ?>

	<div class="settings-row-muted">
		<div class="wpchill-toggle">
			<input class="wpchill-toggle__input" id="setting-dlm_stamper_disable_extract" name="dlm_stamper_disable_extract" type="checkbox" value="1" class="disabled" disabled>
			<div class="wpchill-toggle__items">
				<span class="wpchill-toggle__track"></span>
				<span class="wpchill-toggle__thumb"></span>
				<svg class="wpchill-toggle__off" width="6" height="6" aria-hidden="true" role="img" focusable="false" viewBox="0 0 6 6">
					<path d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
				</svg>
				<svg class="wpchill-toggle__on" width="2" height="6" aria-hidden="true" role="img" focusable="false" viewBox="0 0 2 6">
					<path d="M0 0h2v6H0z"></path>
				</svg>
			</div>
		</div>
		<label>Disable</label>
		<p>
			<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a>
			<?php _e( 'Check this box to disallow extraction of text and graphics (extraction supports of accessibility to users with disabilities and other purposes).', 'waterwoo-pdf' ); ?>
		</p>
	</div>
<?php

}

function dlm_stamper_disable_ass() { ?>
	<div class="settings-row-muted">
		<div class="wpchill-toggle settings-row-muted">
			<input class="wpchill-toggle__input" id="setting-dlm_stamper_disable_ass" name="dlm_stamper_disable_ass" type="checkbox" value="1" class="disabled" disabled>
			<div class="wpchill-toggle__items">
				<span class="wpchill-toggle__track"></span>
				<span class="wpchill-toggle__thumb"></span>
				<svg class="wpchill-toggle__off" width="6" height="6" aria-hidden="true" role="img" focusable="false" viewBox="0 0 6 6">
					<path d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
				</svg>
				<svg class="wpchill-toggle__on" width="2" height="6" aria-hidden="true" role="img" focusable="false" viewBox="0 0 2 6">
					<path d="M0 0h2v6H0z"></path>
				</svg>
			</div>
		</div>
		<label>Disable</label>
		<p>
			<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a>
			<?php _e( 'Check this box to disable assembly (insertion, rotation, or deletion of pages and creation of bookmarks or thumbnail images).', 'waterwoo-pdf' ); ?>
		</p>
	</div>
<?php

}

function dlm_stamper_disable_print_high() { ?>

	<div class="settings-row-muted">
		<div class="wpchill-toggle">
			<input class="wpchill-toggle__input" id="setting-dlm_stamper_disable_print_high" name="dlm_stamper_disable_print_high" type="checkbox" value="1" class="disabled" disabled>
			<div class="wpchill-toggle__items">
				<span class="wpchill-toggle__track"></span>
				<span class="wpchill-toggle__thumb"></span>
				<svg class="wpchill-toggle__off" width="6" height="6" aria-hidden="true" role="img" focusable="false" viewBox="0 0 6 6">
					<path d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
				</svg>
				<svg class="wpchill-toggle__on" width="2" height="6" aria-hidden="true" role="img" focusable="false" viewBox="0 0 2 6">
					<path d="M0 0h2v6H0z"></path>
				</svg>
			</div>
		</div>
		<label>Disable</label>
		<p>
			<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a>
			<?php _e( 'Check this box to make it more difficult for your PDF to be printed beautifully by the end consumer.', 'waterwoo-pdf' ); ?>
		</p>
	</div>
<?php

}

function dlm_stamper_owner_pwd() { ?>

	<div class="settings-row-muted">
		<input class="regular-text disabled" type="text" name="dlm_stamper_owner_pwd" value="" disabled>
		<p>
			<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a>
			<?php _e( 'An owner password allows the end user to take control of a PDF. Leave blank and it will be set by MD5 hash automatically (it will be different for every download, and you will not know it).', 'waterwoo-pdf' ); ?>
		</p>
	</div>
<?php

}

function dlm_stamper_protect_unlock() { ?>

	<div class="settings-row-muted">
		<div class="wpchill-toggle">
			<input class="wpchill-toggle__input" id="setting-dlm_stamper_protect_unlock" name="dlm_stamper_protect_unlock" type="checkbox" value="" class="disabled" disabled>
			<div class="wpchill-toggle__items">
				<span class="wpchill-toggle__track"></span>
				<span class="wpchill-toggle__thumb"></span>
				<svg class="wpchill-toggle__off" width="6" height="6" aria-hidden="true" role="img" focusable="false" viewBox="0 0 6 6">
					<path d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
				</svg>
				<svg class="wpchill-toggle__on" width="2" height="6" aria-hidden="true" role="img" focusable="false" viewBox="0 0 2 6">
					<path d="M0 0h2v6H0z"></path>
				</svg>
			</div>
		</div>
		<label>Enable</label>
		<p>
			<a href="#TB_inline?&width=640&height=280&inlineId=pdfink-upgrade-tb" class="thickbox" style="text-decoration:none;"><span class="dashicons dashicons-admin-network pdfink-upgrade"></span></a>
			<?php _e( 'By default PDF protections can only be removed with an owner password. Check to allow removal with a user password. USE WITH CAUTION.', 'waterwoo-pdf' ); ?>
		</p>
	</div>
<?php

}
