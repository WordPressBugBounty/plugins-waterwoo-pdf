<?php

namespace LittlePackage\lib\tcpdf;

use LittlePackage\lib\tcpdf\tecnick\tcpdf\TCPDF;

defined( 'ABSPATH' ) || exit;

class TCPDF_Child extends TCPDF {

	/**
	 * Document metadata
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
	 * If true print TCPDF meta link
	 * @protected
	 * @since 5.9.152 (2012-03-23)
	 */
	protected $tcpdflink;

	public function __construct() {

		parent::__construct();
		$this->tcpdflink = false;

	}

	/**
	 * Set the default JPEG compression quality (1-100)
	 * @param int $quality JPEG quality, integer between 1 and 100
	 * @public
	 * @since 3.0.000 (2008-03-27)
	 */
	public function setJPEGQuality($quality) {
		if (($quality < 1) || ($quality > 100)) {
			$quality = 100;
		}
		$quality = apply_filters( 'wwpdf_jpeg_quality', $quality );

		$this->jpeg_quality = intval($quality);
	}

	/**
	 * Set a flag to print page header.
	 * @param boolean $val set to true to print the page header (default), false otherwise
	 * @public
	 */
	public function setPrintHeader($val=true) {
		$this->print_header = false;
	}

	/**
	 * Set a flag to print page footer
	 * @param boolean $val set to true to print the page footer (default), false otherwise
	 * @public
	 */
	public function setPrintFooter($val=true) {
		$this->print_footer = false;
	}

	/**
	 * This method is used to render the page header.
	 * It is automatically called by AddPage() and could be overwritten in your own inherited class
	 * @public
	 */
	public function Header() {}

	/**
	 * This method is used to render the page footer.
	 * It is automatically called by AddPage() and could be overwritten in your own inherited class
	 * @public
	 */
	public function Footer() {}

} // End of class