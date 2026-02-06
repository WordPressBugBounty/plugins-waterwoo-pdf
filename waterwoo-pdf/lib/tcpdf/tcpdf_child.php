<?php

namespace LittlePackage\lib\tcpdf;

use LittlePackage\lib\tcpdf\tecnick\tcpdf\TCPDF;
use LittlePackage\lib\tcpdf\tecnick\tcpdf\includes\TCPDF_STATIC;
use LittlePackage\lib\tcpdf\tecnick\tcpdf\includes\TCPDF_FONTS;

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


	/**
	 * Output pages (and replace page number aliases).
	 * @protected
	 */
	protected function _putpages() {
		$filter = ($this->compress) ? '/Filter /FlateDecode ' : '';
		// get internal aliases for page numbers
		$pnalias = $this->getAllInternalPageNumberAliases();
		$num_pages = $this->numpages;
		$ptpa = TCPDF_STATIC::formatPageNumber(($this->starting_page_number + $num_pages - 1));
		$ptpu = TCPDF_FONTS::UTF8ToUTF16BE($ptpa, false, $this->isunicode, $this->CurrentFont);
		$ptp_num_chars = $this->GetNumChars($ptpa);
		$pagegroupnum = 0;
		$groupnum = 0;
		$ptgu = 1;
		$ptga = 1;
		$ptg_num_chars = 1;
		$did_warning = 0;
		for ($n = 1; $n <= $num_pages; ++$n) {
			// get current page
			$temppage = $this->getPageBuffer($n);
			$pagelen = strlen($temppage);
			// set replacements for total pages number
			$pnpa = TCPDF_STATIC::formatPageNumber(($this->starting_page_number + $n - 1));
			$pnpu = TCPDF_FONTS::UTF8ToUTF16BE($pnpa, false, $this->isunicode, $this->CurrentFont);
			$pnp_num_chars = $this->GetNumChars($pnpa);
			$pdiff = 0; // difference used for right shift alignment of page numbers
			$gdiff = 0; // difference used for right shift alignment of page group numbers
			if (!empty($this->pagegroups)) {
				if (isset($this->newpagegroup[$n])) {
					$pagegroupnum = 0;
					++$groupnum;
					$ptga = TCPDF_STATIC::formatPageNumber($this->pagegroups[$groupnum]);
					$ptgu = TCPDF_FONTS::UTF8ToUTF16BE($ptga, false, $this->isunicode, $this->CurrentFont);
					$ptg_num_chars = $this->GetNumChars($ptga);
				}
				++$pagegroupnum;
				$pnga = TCPDF_STATIC::formatPageNumber($pagegroupnum);
				$pngu = TCPDF_FONTS::UTF8ToUTF16BE($pnga, false, $this->isunicode, $this->CurrentFont);
				$png_num_chars = $this->GetNumChars($pnga);
				// replace page numbers
				$replace = array();
				$replace[] = array($ptgu, $ptg_num_chars, 9, $pnalias[2]['u']);
				$replace[] = array($ptga, $ptg_num_chars, 7, $pnalias[2]['a']);
				$replace[] = array($pngu, $png_num_chars, 9, $pnalias[3]['u']);
				$replace[] = array($pnga, $png_num_chars, 7, $pnalias[3]['a']);
				list($temppage, $gdiff) = TCPDF_STATIC::replacePageNumAliases($temppage, $replace, $gdiff);
			}
			// replace page numbers
			$replace = array();
			$replace[] = array($ptpu, $ptp_num_chars, 9, $pnalias[0]['u']);
			$replace[] = array($ptpa, $ptp_num_chars, 7, $pnalias[0]['a']);
			$replace[] = array($pnpu, $pnp_num_chars, 9, $pnalias[1]['u']);
			$replace[] = array($pnpa, $pnp_num_chars, 7, $pnalias[1]['a']);
			list($temppage, $pdiff) = TCPDF_STATIC::replacePageNumAliases($temppage, $replace, $pdiff);
			// replace right shift alias
			$temppage = $this->replaceRightShiftPageNumAliases($temppage, $pnalias[4], max($pdiff, $gdiff));
			// replace EPS marker
			$temppage = str_replace($this->epsmarker, '', $temppage);
			//Page
			$this->page_obj_id[$n] = $this->_newobj();
			$out = '<<';
			$out .= ' /Type /Page';
			$out .= ' /Parent 1 0 R';
			if (empty($this->signature_data['approval']) OR ($this->signature_data['approval'] != 'A')) {
				$out .= ' /LastModified '.$this->_datestring(0, $this->doc_modification_timestamp);
			}
			$out .= ' /Resources 2 0 R';
			foreach ($this->page_boxes as $box) {
				if ( $did_warning < 1 && ( 0 !== (int) $this->pagedim[$n][$box]['llx'] || 0 !== $this->pagedim[$n][$box]['lly'] ) ) {
					wwpdf_debug_log( 'Unexpected white space on your PDF? The open-source parser (TCPDI) included with PDF Ink Lite did not understand your PDF page boundaries. Upgrade to PDF Ink for better PDF handling.', 'warning');
					++$did_warning;
				}
				$out .= ' /'.$box;
				$out .= sprintf(' [%F %F %F %F]', $this->pagedim[$n][$box]['llx'], $this->pagedim[$n][$box]['lly'], $this->pagedim[$n][$box]['urx'], $this->pagedim[$n][$box]['ury']);
			}
			if (isset($this->pagedim[$n]['BoxColorInfo']) AND !empty($this->pagedim[$n]['BoxColorInfo'])) {
				$out .= ' /BoxColorInfo <<';
				foreach ($this->page_boxes as $box) {
					if (isset($this->pagedim[$n]['BoxColorInfo'][$box])) {
						$out .= ' /'.$box.' <<';
						if (isset($this->pagedim[$n]['BoxColorInfo'][$box]['C'])) {
							$color = $this->pagedim[$n]['BoxColorInfo'][$box]['C'];
							$out .= ' /C [';
							$out .= sprintf(' %F %F %F', ($color[0] / 255), ($color[1] / 255), ($color[2] / 255));
							$out .= ' ]';
						}
						if (isset($this->pagedim[$n]['BoxColorInfo'][$box]['W'])) {
							$out .= ' /W '.($this->pagedim[$n]['BoxColorInfo'][$box]['W'] * $this->k);
						}
						if (isset($this->pagedim[$n]['BoxColorInfo'][$box]['S'])) {
							$out .= ' /S /'.$this->pagedim[$n]['BoxColorInfo'][$box]['S'];
						}
						if (isset($this->pagedim[$n]['BoxColorInfo'][$box]['D'])) {
							$dashes = $this->pagedim[$n]['BoxColorInfo'][$box]['D'];
							$out .= ' /D [';
							foreach ($dashes as $dash) {
								$out .= sprintf(' %F', ($dash * $this->k));
							}
							$out .= ' ]';
						}
						$out .= ' >>';
					}
				}
				$out .= ' >>';
			}
			$out .= ' /Contents '.($this->n + 1).' 0 R';
			$out .= ' /Rotate '.$this->pagedim[$n]['Rotate'];
			if (!$this->pdfa_mode || $this->pdfa_version >= 2) {
				$out .= ' /Group << /Type /Group /S /Transparency /CS /DeviceRGB >>';
			}
			if (isset($this->pagedim[$n]['trans']) AND !empty($this->pagedim[$n]['trans'])) {
				// page transitions
				if (isset($this->pagedim[$n]['trans']['Dur'])) {
					$out .= ' /Dur '.$this->pagedim[$n]['trans']['Dur'];
				}
				$out .= ' /Trans <<';
				$out .= ' /Type /Trans';
				if (isset($this->pagedim[$n]['trans']['S'])) {
					$out .= ' /S /'.$this->pagedim[$n]['trans']['S'];
				}
				if (isset($this->pagedim[$n]['trans']['D'])) {
					$out .= ' /D '.$this->pagedim[$n]['trans']['D'];
				}
				if (isset($this->pagedim[$n]['trans']['Dm'])) {
					$out .= ' /Dm /'.$this->pagedim[$n]['trans']['Dm'];
				}
				if (isset($this->pagedim[$n]['trans']['M'])) {
					$out .= ' /M /'.$this->pagedim[$n]['trans']['M'];
				}
				if (isset($this->pagedim[$n]['trans']['Di'])) {
					$out .= ' /Di '.$this->pagedim[$n]['trans']['Di'];
				}
				if (isset($this->pagedim[$n]['trans']['SS'])) {
					$out .= ' /SS '.$this->pagedim[$n]['trans']['SS'];
				}
				if (isset($this->pagedim[$n]['trans']['B'])) {
					$out .= ' /B '.$this->pagedim[$n]['trans']['B'];
				}
				$out .= ' >>';
			}
			$out .= $this->_getannotsrefs($n);
			$out .= ' /PZ '.$this->pagedim[$n]['PZ'];
			$out .= ' >>';
			$out .= "\n".'endobj';
			$this->_out($out);
			//Page content
			$p = ($this->compress) ? gzcompress($temppage) : $temppage;
			$this->_newobj();
			$p = $this->_getrawstream($p);
			$this->_out('<<'.$filter.'/Length '.strlen($p).'>> stream'."\n".$p."\n".'endstream'."\n".'endobj');
		}
		//Pages root
		$out = $this->_getobj(1)."\n";
		$out .= '<< /Type /Pages /Kids [';
		foreach($this->page_obj_id as $page_obj) {
			$out .= ' '.$page_obj.' 0 R';
		}
		$out .= ' ] /Count '.$num_pages.' >>';
		$out .= "\n".'endobj';
		$this->_out($out);
	}

} // End of class