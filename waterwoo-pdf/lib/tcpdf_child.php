<?php
//============================================================+
// File name    : tcpdf_child.php
// Version      : 1.2.0
// Begin        : 2019-06-24
// Last Update  : 2026-08-17
// Author       : C. Paquette - Canyon Webworks, LLC - www.canyonwebworks.com / www.pdfink.com
// License      : GNU-LGPL v3 (https://www.gnu.org/licenses/lgpl-3.0.html)
//============================================================+

namespace CanyonWebworks\pdfInkLite\lib;

use CanyonWebworks\pdfInkLite\lib\tecnick\tcpdf\TCPDF;

defined( 'ABSPATH' ) || exit;

class TCPDF_Child extends TCPDF {

	/**
	 * Set the default JPEG compression quality (1-100)
	 * @protected
	 * @since 3.0.000 (2008-03-27)
	 */
	protected $jpeg_quality = 100;

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
	 * Keep default JPEG compression quality at 100 to avoid surprises
	 *
	 * @param int $quality JPEG quality, integer between 1 and 100
	 * @public
	 * @since 3.0.000 (2008-03-27)
	 */
	public function setJPEGQuality($quality) {
		if (($quality < 1) || ($quality > 100)) {
			$quality = 100;
		}
		$this->jpeg_quality = intval($quality);
	}

	/**
	 * Set a flag to print page header
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
	 * This method is used to render the page header
	 * It is automatically called by AddPage() and could be overwritten in your own inherited class
	 * @public
	 */
	public function Header() {}

	/**
	 * This method is used to render the page footer
	 * It is automatically called by AddPage() and could be overwritten in your own inherited class
	 * @public
	 */
	public function Footer() {}

}