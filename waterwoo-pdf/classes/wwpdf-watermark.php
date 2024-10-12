<?php

use LittlePackage\lib\tcpdi\pauln\tcpdi\TCPDI as TCPDI;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WWPDF_Watermark' ) ) :

	class WWPDF_Watermark {

		private $pdf;

		private $size = null;

		protected $file = '';

		public $newfile = '';

		protected $footer = '';

		protected $email = '';

		public function __construct( $origfile, $newfile, $footer, $email ) {

			$this->file     = $origfile;
			$this->newfile  = $newfile;
			$this->footer   = $footer;
			$this->email    = $email;
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

			// If you want to do a whole lot more with TCPDF, buy the premium version of this plugin (formerly known as WaterWoo)!
			// This plugin is BASIC, if not CRUDE 🥴
			// Please support the work of WordPress developers
			$pagecount = $this->pdf->setSourceFile( $this->file );

			$font = sanitize_text_field( get_option( 'wwpdf_font_premium', 'helvetica' ) );
			$font = apply_filters_deprecated( 'wwpdf_add_custom_font', [ $font ], '6.3', '', 'The `wwpdf_add_custom_font` filter hook is included in the Premium (paid) version of WaterWoo PDF. Please upgrade to continue using it.' );
			$footer_size = absint( sanitize_text_field( get_option( 'wwpdf_footer_size_premium', 12 ) ) );
			$this->pdf->SetFont( $font, '', $footer_size );

			$footer_color = $this->hex2rgb( sanitize_text_field( get_option( 'wwpdf_footer_color_premium', '#000000' ) ) );
			$rgb_array = explode( ",", $footer_color );
			$this->pdf->SetTextColor( $rgb_array[0], $rgb_array[1], $rgb_array[2] );

			// Get mark position
			$footer_y = sanitize_text_field( get_option( 'wwpdf_footer_finetune_Y_premium', -10 ) );

			for ( $i = 1; $i <= $pagecount; $i++ ) {

				$this->setup_page( $i ); // $i is page number

				if ( apply_filters_deprecated( 'wwpdf_dont_watermark_this_page', [false, $i, $pagecount ], '6.3', '', 'The `wwpdf_dont_watermark_this_page` filter hook is included in the Premium (paid) version of WaterWoo PDF. Please upgrade to continue using it.' ) ) {
					continue;
				}

				$this->pdf->SetMargins( apply_filters( 'wwpdf_left_margin', 0 ), apply_filters( 'wwpdf_top_margin', 0 ) );

				if ( $footer_y < 0 ) { // for measuring from bottom of page
					// upper-left corner Y coordinate
					$_footer_y = $this->size['h'] - abs( $footer_y );
				} else { // set greater than zero
					if ( $footer_y >= $this->size['h'] ) {
						$_footer_y = -10;
					} else {
						$_footer_y = $footer_y;
					}
				}

				$this->pdf->SetXY( 0, $_footer_y );

				do_action( 'wwpdf_before_write', $this->pdf, $i );
				$this->pdf->Write( 1, $this->footer, apply_filters( 'wwpdf_write_URL', '' ), FALSE, apply_filters( 'wwpdf_write_align', 'C' ) );
				do_action( 'wwpdf_after_write', $this->pdf, $i );

			}

			// ARCFOUR Encryption & password
			$this->protect_pdf();

			do_action( 'wwpdf_before_output', $this->pdf );

			$this->pdf->Output( $this->newfile, apply_filters_deprecated( 'wwpdf_output_dest', [ 'F' ], '6.3', '', 'The `wwpdf_output_dest` filter hook is included in the Premium (paid) version of WaterWoo PDF. Please upgrade to continue using it.' ) );

		}

		/**
		 * Set up each TCPDF page object
		 *
		 * @return void
		 */
		private function setup_page( $page ) {

			$idx            = $this->pdf->importPage( $page, '/BleedBox' );
			$this->size     = $this->pdf->getTemplateSize( $idx );
			$size_array     = [ $this->size['w'], $this->size['h'] ];
			$orientation    = ( $this->size['w'] > $this->size['h'] ) ? 'L' : 'P';

			$this->pdf->SetAutoPageBreak( true, 0 );
			$this->pdf->AddPage( $orientation, '' );
			$this->pdf->setPageFormatFromTemplatePage( $page, $orientation, $size_array );
			$this->pdf->useTemplate( $idx );
			$this->pdf->importAnnotations( $page );

		}


		/**
		 * Add encryption and password to PDF
		 *
		 * @return void
		 */
		protected function protect_pdf() {

			// Passwording
			$pwd_enabled = false;
			$user_pwd = get_option( 'wwpdf_password', '' );
			if ( ! empty( $user_pwd ) ) {
				$pwd_enabled = true;
				if ( 'email' === $user_pwd ) {
					$user_pwd = $this->email;
				}
			}

			// Adding file protections in this list removes them
			$permissions = [];

			if ( 'yes' === sanitize_text_field( get_option( 'wwpdf_disable_printing', 'no' ) ) ) { // Saved in DB as yes/no
				$permissions[] = 'print';
			}
			if ( 'yes' === sanitize_text_field( get_option( 'wwpdf_disable_mods', 'no' ) ) ) {
				$permissions[] = 'modify';
			}
			if ( 'yes' === sanitize_text_field( get_option( 'wwpdf_disable_copy', 'no' ) ) ) {
				$permissions[] = 'copy';
			}
			if ( 'yes' === sanitize_text_field( get_option( 'wwpdf_disable_annot', 'no' ) ) ) {
				$permissions[] = 'annot-forms';
			}
			// Higher encryption allows blocking 'extract', 'fill-forms', 'assemble', and 'print-high'

			// Learn more about options at https://tcpdf.org/examples/example_016/

			if ( $pwd_enabled || array_filter( $permissions ) ) {
				$pub_key = apply_filters_deprecated( 'wwpdf_public_key', [ null ], '6.3', '', 'The `wwpdf_public_key` filter hook is included in the Premium (paid) version of WaterWoo PDF. Please upgrade to continue using it.' );
				$this->pdf->SetProtection( $permissions, $user_pwd, null, 0, $pub_key );
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

endif;