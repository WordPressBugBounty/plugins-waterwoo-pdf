<?php

use LittlePackage\lib\tcpdi\pauln\tcpdi\TCPDI as TCPDI;

defined( 'ABSPATH' ) || exit;

final class WWPDF_Watermark {

	private $pdf;

	private $size = null;

	protected $origfile = '';

	public $newfile = '';

	protected $settings = [];

	public function __construct( $origfile, $newfile, $settings ) {

		$this->origfile = $origfile;
		$this->newfile  = $newfile;
		$this->settings = $settings;
		$this->includes();
		$this->pdf = new TCPDI();

	}

	/**
	 * Include required PHP files
	 *
	 * @return void
	 */
	private function includes() {

		require_once WWPDF_PATH . 'lib/tcpdf/tcpdf/tcpdf.php';
		require_once WWPDF_PATH . 'lib/tcpdf/tcpdf_child.php';
		require_once WWPDF_PATH . 'lib/tcpdi/tcpdi.php';

	}

	/**
	 * Run TCPDF commands
	 *
	 * @return void
	 */
	public function do_watermark() {

		// This free plugin is BASIC, if not CRUDE! 🥴
		// If you want to do a whole lot more with your PDF files,
		// (like adding multiple marks with your own fonts, using HTML
		// for more styling, and marking chosen pages)
		// please support the work of WordPress developers
		// and buy the full version of this plugin at www.pdfink.com!
		$pagecount = $this->pdf->setSourceFile( $this->origfile );

		if ( ! $pagecount ) {
			throw new Exception( 'Unable to parse PDF into memory, possibly due to a PDF version >= 2.0' );
		}

		if ( version_compare( 1.6, $this->pdf->getPDFVersion(), '<' ) ) {
			wwpdf_debug_log( 'Watermarking may not succeed, possibly having to do with a PDF version > 1.6.', 'warning' );
		}

		$font = apply_filters_deprecated( 'wwpdf_add_custom_font', [ $this->settings['font_face'] ], '6.3', '', 'The `wwpdf_add_custom_font` filter hook is included in PDF Ink (pdfink.com). Please upgrade to continue using it.' );
		$this->pdf->SetFont( $font, '', $this->settings['font_size'] );
		$this->pdf->SetFontSize( $this->settings['font_size'] );
		$rgb_array = explode( ",", $this->hex2rgb( $this->settings['font_color'] ) );
		$this->pdf->SetTextColor( $rgb_array[0], $rgb_array[1], $rgb_array[2] );

		// Get mark position
		$left_margin = apply_filters_deprecated( 'wwpdf_left_margin', [ $this->settings['margin_lr'] ], '6.0', '', 'The PDF Ink `wwpdf_left_margin` filter hook has no replacement. Margins are now adjustable in the plugin settings.' );
		$this->pdf->SetMargins( $left_margin, apply_filters( 'wwpdf_top_margin', 0 ) );

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

				do_action( 'wwpdf_before_write', $this->pdf, $i );
				$this->pdf->Write( 1, $this->settings['content'], apply_filters( 'wwpdf_write_URL', '' ), false, apply_filters( 'wwpdf_write_align', 'C' ) );
				do_action( 'wwpdf_after_write', $this->pdf, $i );

				// Yep, after ten years of writing/maintaining/supporting a free plugin on the WP repository,
				// and taking in fewer than $50 donations during that time, I've decided ask for attribution.
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

		do_action( 'wwpdf_before_output', $this->pdf );

		$this->pdf->Output( $this->newfile, apply_filters_deprecated( 'wwpdf_output_dest', [ 'F' ], '6.3', '', 'The `wwpdf_output_dest` filter hook is deprecated in the free version of PDF Ink (pdfink.com).' ) );

	}

	/**
	 * Set up each TCPDF page object
	 *
	 * @return void
	 */
	private function setup_page( $page ) {

		$idx            = $this->pdf->importPage( $page, '/BleedBox' );
		$this->pdf->importAnnotations( $page );
		$this->size     = $this->pdf->getTemplateSize( $idx );

		$size_array     = [ $this->size['w'], $this->size['h'] ];
		$orientation    = ( $this->size['w'] > $this->size['h'] ) ? 'L' : 'P';

		$this->pdf->SetAutoPageBreak( true, 0 );
		$this->pdf->AddPage( $orientation, '' );
		$this->pdf->setPageFormatFromTemplatePage( $page, $orientation, $size_array );

		$this->pdf->useTemplate( $idx );

	}


	/**
	 * Add encryption and password to PDF
	 *
	 * @return void
	 */
	protected function protect_pdf() {

		// Passwording
		$pwd_enabled = false;
		$user_pwd = $this->settings['password'] ?? '';
		if ( ! empty( $user_pwd ) ) {
			if ( 'email' === $user_pwd ) {
				$user_pwd = sanitize_email( $this->email );
			}
			if ( ! empty( $user_pwd ) ) { // if still valid after sanitization
				$pwd_enabled = true;
			}
		}

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
		// Higher encryption allows selective blocking blocking of 'extract', 'fill-forms', 'assemble', and 'print-high'
		// Get these protections with higher encryption by using PDF Ink (pdfink.com)
		if ( $pwd_enabled || array_filter( $permissions ) ) {
			$this->pdf->SetProtection(
				$permissions,
				$user_pwd,
				null,
				0,
				null
			);
		}
	}

	/**
	 * Convert hex color to RGB
	 *
	 * @param string $hex
	 * @return string
	 */
	protected function hex2rgb( $hex ) {

		$hex = str_replace( "#", "", $hex );
		$r = hexdec( substr( $hex,0,2 ) );
		$g = hexdec( substr( $hex,2,2 ) );
		$b = hexdec( substr( $hex,4,2 ) );
		return implode( ",", [ $r, $g, $b ] );

	}

}