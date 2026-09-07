<?php

use CanyonWebworks\pdfInkLite\lib\CanyonWebworks\cynpdi;

defined( 'ABSPATH' ) || exit;

final class WWPDF_Watermark {

	protected string $origfile;

	protected string $newfile;

	protected array $settings;

	private ?object $pdf;

	private array $size = [];

	public function __construct( $origfile, $newfile, $settings ) {

		$this->origfile = $origfile ?? '';
		$this->newfile  = $newfile ?? '';
		$this->settings = $settings ?? [];
		$this->define_constants();
		$this->includes();
		$this->pdf = new cynpdi();

	}

	/**
	 * Overwrite TCPDF constants as necessary
	 *
	 * @return void
	 */
	private function define_constants() {

		if ( ! defined( 'K_TCPDF_EXTERNAL_CONFIG' ) ) {
			define( 'K_TCPDF_EXTERNAL_CONFIG', true );
		} else if ( ! K_TCPDF_EXTERNAL_CONFIG ) {
			wwpdf_debug_log( 'Another plugin defined the K_TCPDF_EXTERNAL_CONFIG constant as FALSE. PDF Ink would like to set it to TRUE' );
		}

		if ( ! defined( 'K_PATH_IMAGES' ) ) {
			foreach ( [
				// Avoids including dangerous paths
				WWPDF_PATH . 'lib/tecnick/tcpdf/examples/images/',
				WWPDF_PATH . 'lib/tecnick/tcpdf/images/',
				WWPDF_PATH . 'lib/tecnick/tcpdf/',
			] as $tcpdf_images_path ) {
				if ( @file_exists( $tcpdf_images_path ) ) {
					define ( 'K_PATH_IMAGES', $tcpdf_images_path );
					break;
				}
			}
		}

	}

	/**
	 * Include required PHP files (TCPDI/TCPDF)
	 *
	 * @return void
	 */
	private function includes() {

		require_once WWPDF_PATH . 'lib/tcpdf_config.php';
		require_once WWPDF_PATH . 'lib/pauln/tcpdi_parser/tcpdi_parser.php';
		require_once WWPDF_PATH . 'lib/tecnick/tcpdf/tcpdf.php';
		require_once WWPDF_PATH . 'lib/tcpdf_child.php';
		require_once WWPDF_PATH . 'lib/CanyonWebworks/cynpdi.php';

	}

	/**
	 * Run TCPDF commands
	 *
	 * @throws Exception
	 * @return void
	 */
	public function do_watermark() {

		// This free plugin is BASIC, if not CRUDE! 🥴
		// If you want to do a lot more with your PDF files,
		// (like adding multiple marks with your own fonts, using HTML
		// for more styling, and marking chosen pages)
		// please support the work of WordPress developers
		// and buy the full version of this plugin at ** www.pdfink.com! **
		$pagecount = $this->pdf->setSourceFile( $this->origfile );

		if ( ! $pagecount ) {
			throw new Exception( 'Unable to parse PDF into memory, possibly due to a PDF version >= 2.0, or incrementation, or a syntax issue.' );
		}

		if ( version_compare( 1.6, $this->pdf->getPDFVersion(), '<' ) ) {
			wwpdf_debug_log( 'Watermarking may not succeed, possibly having to do with a PDF version > 1.6.' );
		}

		$font = apply_filters_deprecated( 'wwpdf_add_custom_font', [ $this->settings['font_face'] ], '6.3', '', 'The `wwpdf_add_custom_font` filter hook is included in PDF Ink (pdfink.com). Please upgrade to continue using it.' );
		$this->pdf->SetFont( $font, '', $this->settings['font_size'] );
		$this->pdf->SetFontSize( $this->settings['font_size'] );
		$rgb_array = explode( ",", $this->hex2rgb( $this->settings['font_color'] ) );
		$this->pdf->SetTextColor( $rgb_array[0], $rgb_array[1], $rgb_array[2] );

		// Get mark position
		$left_margin = apply_filters_deprecated( 'wwpdf_left_margin', [ $this->settings['margin_lr'] ], '6.0', '', 'The PDF Ink `wwpdf_left_margin` filter hook has no replacement. Margins are now adjustable in the plugin settings.' );
		$this->pdf->SetMargins( $left_margin, apply_filters_deprecated( 'wwpdf_top_margin', [ 0 ], '6.0', '', 'The `wwpdf_top_margin` filter hook is deprecated in the free version of PDF Ink. For easy top margin control, please upgrade.' ) );

		// Optional attribution
		if ( isset( $this->settings['source'] ) && 'edd' === $this->settings['source'] ) {
			$attribution = edd_get_option( 'pdfink_attribution', '' );
		} else {
			$attribution = get_option( 'pdfink_attribution', '' );
		}

		for ( $i = 1; $i <= $pagecount; $i++ ) {

			$this->setup_page( $i ); // $i is page number

			$y_adjustment = $this->settings['y_adjuster'];

			if ( $y_adjustment < 0 ) { // for measuring from bottom of page
				// upper-left corner Y coordinate
				$_y_adjustment = $this->size['h'] - abs( $y_adjustment );
			} else { // set greater than zero
				if ( $y_adjustment >= $this->size['h'] ) {
					$_y_adjustment = $this->settings['font_size'] * -1;
				} else {
					$_y_adjustment = $y_adjustment;
				}
			}
			$this->pdf->SetXY( $left_margin, $_y_adjustment );

			if ( '' !== $this->settings['content'] ) {

				do_action_deprecated( 'wwpdf_before_write', [ $this->pdf, $i ], '6.0', '', 'The `wwpdf_before_write` filter hook is deprecated in the free version of PDF Ink.' );
				$this->pdf->Write( 1, $this->settings['content'], apply_filters( 'wwpdf_write_URL', '' ), false, apply_filters( 'wwpdf_write_align', 'C' ) );
				do_action_deprecated( 'wwpdf_after_write', [ $this->pdf, $i ], '6.0', '', 'The `wwpdf_after_write` filter hook is deprecated in the free version of PDF Ink.' );

				// Please support your local volunteer WordPress developer venmo.com/canyonwebworks or paypal.me/canyonwebworks
				if ( 2 === $i && 'yes' === $attribution || '1' === $attribution || 'on' === $attribution ) {
					$url = 'https://pdfink.com/?source=pdf';
					if ( isset( $this->settings['source'] ) ) {
						if ( 'woo' === $this->settings['source'] ) {
							$url = 'https://pdfink.com/?source=pdf&utm_campaign=woo';
						}
						if ( 'edd' === $this->settings['source'] ) {
							$url = 'https://pdfink.com/?source=pdf&utm_campaign=edd';
						}
						if ( 'dlm' === $this->settings['source'] ) {
							$url = 'https://pdfink.com/?source=pdf&utm_campaign=dlm';
						}
					}
					try {
						$this->pdf->SetXY( 0, 0 );
						$this->pdf->setFontSize( 0.5 );
						$this->pdf->SetTextColor( 255, 255, 255 );
						$this->pdf->SetAlpha( 0 );
						$ctas = [
							"Free PDF passwords and watermarking by PDF Ink - www.pdfink.com",
							"Use PDF Ink Lite to password, watermark, and embed PDF files",
							"PDF written and secured by PDF Ink - www.pdfink.com",
							"PDF customized using the free PDF Ink WordPress plugin",
							"PDF personalized by PDF Ink - www.pdfink.com",
						];
						$this->pdf->Write( 1, $ctas[ rand( 0, 4 ) ], $url, false, 'C' );
					} catch ( Exception $e ) {}
				}
			}

		}

		// ARCFOUR Encryption & password
		$this->protect_pdf();

		do_action_deprecated( 'wwpdf_before_output', [ $this->pdf ], '6.0', '', 'The `wwpdf_before_output` filter hook is deprecated in the free version of PDF Ink.' );

		$this->pdf->Output( $this->newfile, apply_filters_deprecated( 'wwpdf_output_dest', [ 'F' ], '6.3', '', 'The `wwpdf_output_dest` filter hook is deprecated in the free version of PDF Ink (pdfink.com).' ) );

	}

	/**
	 * Set up each TCPDF page object
	 *
	 * @param int $page_no
	 * @return void
	 */
	private function setup_page( $page_no ) {

		$idx            = $this->pdf->importPage( $page_no );
		$this->size     = $this->pdf->getTemplateSize( $idx );
		$size_array     = [ $this->size['w'], $this->size['h'] ];
		$orientation    = ( $this->size['w'] > $this->size['h'] ) ? 'L' : 'P';

		$this->pdf->SetAutoPageBreak( true );
		$this->pdf->AddPage( $orientation, $size_array );

		$this->pdf->useTemplate( $idx );
		$this->pdf->importAnnotations( $page_no );

	}


	/**
	 * Add encryption and password to PDF
	 *
	 * @return void
	 */
	protected function protect_pdf() {

		// Passwording
		$user_pwd = $this->settings['password'] ?? '';

		// Adding file protections in this list removes them
		$permissions = [];

		// Learn more about options at https://tcpdf.org/examples/example_016/
		if ( 'yes' === $this->settings['disable_print'] || '1' === $this->settings['disable_print'] ) { // Saved in DB as yes/no
			$permissions[] = 'print';
		}
		if ( 'yes' === $this->settings['disable_mods'] || '1' === $this->settings['disable_mods'] ) {
			$permissions[] = 'modify';
		}
		if ( 'yes' === $this->settings['disable_copy'] || '1' === $this->settings['disable_copy'] ) {
			$permissions[] = 'copy';
		}
		if ( 'yes' === $this->settings['disable_annot'] || '1' === $this->settings['disable_annot'] ) {
			$permissions[] = 'annot-forms';
		}
		// Higher encryption allows selective blocking of 'extract', 'fill-forms', 'assemble', and 'print-high'
		// Get these protections with higher encryption by using PDF Ink (pdfink.com)
		if ( ! empty( $user_pwd ) || array_filter( $permissions ) ) {
			$this->pdf->SetProtection( $permissions, $user_pwd );
		}

	}

	/**
	 * Convert hex color to RGB
	 *
	 * @param string $hex
	 * @return string RGB color value
	 */
	protected function hex2rgb( string $hex ): string {

		$hex = str_replace( "#", "", $hex );
		$r = hexdec( substr( $hex,0,2 ) );
		$g = hexdec( substr( $hex,2,2 ) );
		$b = hexdec( substr( $hex,4,2 ) );
		return implode( ",", [ $r, $g, $b ] );

	}

}