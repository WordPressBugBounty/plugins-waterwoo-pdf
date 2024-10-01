<?php

namespace LittlePackage\lib\tcpdf;

use LittlePackage\lib\tcpdf\tecnick\tcpdf\TCPDF;
use LittlePackage\lib\tcpdf\tecnick\tcpdf\includes\TCPDF_STATIC as TCPDF_STATIC;
use LittlePackage\lib\tcpdf\tecnick\tcpdf\includes\TCPDF_FONT_DATA as TCPDF_FONT_DATA;

defined( 'ABSPATH' ) || exit;

class TCPDF_Child extends TCPDF {

	/**
	 * Document metadata.
	 * @protected
	 */
	public $metadata = [];

	/**
	 * Document creation date
	 * @protected
	 */
	protected $creationdate = NULL;

	/**
	 * Document producer
	 * @protected
	 */
	protected $producer = NULL;

	/**
	 * Set the default JPEG compression quality (1-100)
	 * @param int $quality JPEG quality, integer between 1 and 100
	 * @public
	 * @since 3.0.000 (2008-03-27)
	 */
	public function setJPEGQuality($quality) {
		if (($quality < 1) || ($quality > 100)) {
			$quality = 75;
		}
		if ( ! isset( $quality ) ) {
			$quality = apply_filters( 'wwpdf_jpeg_quality', 100 );
		}
		$this->jpeg_quality = intval($quality);
	}

	/**
	 * Set a flag to print page header.
	 * @param boolean $val set to true to print the page header (default), false otherwise.
	 * @public
	 */
	public function setPrintHeader($val=true) {
		$this->print_header = false;
	}

	/**
	 * Set a flag to print page footer.
	 * @param boolean $val set to true to print the page footer (default), false otherwise.
	 * @public
	 */
	public function setPrintFooter($val=true) {
		$this->print_footer = false;
	}

	/**
	 * This method is used to render the page header.
	 * It is automatically called by AddPage() and could be overwritten in your own inherited class.
	 * @public
	 */
	public function Header() {}

	/**
	 * This method is used to render the page footer.
	 * It is automatically called by AddPage() and could be overwritten in your own inherited class.
	 * @public
	 */
	public function Footer() {}

} // End of class