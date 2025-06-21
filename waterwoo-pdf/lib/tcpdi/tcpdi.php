<?php
//
//  TCPDI - Version 1.1
//  Based on FPDI - Version 1.4.4
//
//    Copyright 2004-2013 Setasign - Jan Slabon
//
//  Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//  You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
//  Unless required by applicable law or agreed to in writing, software
//  distributed under the License is distributed on an "AS IS" BASIS,
//  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//  See the License for the specific language governing permissions and
//  limitations under the License.
//

namespace LittlePackage\lib\tcpdi\pauln\tcpdi;

use LittlePackage\lib\tcpdf\tecnick\tcpdf\includes\TCPDF_STATIC as TCPDF_STATIC;
use Exception;

defined( 'ABSPATH' ) || exit;

require_once('fpdf_tpl.php');

require_once('tcpdi_parser.php');

class TCPDI extends FPDF_TPL {

	/**
	 * Set the default JPEG compression quality (1-100).
	 * @protected
	 * @since 3.0.000 (2008-03-27)
	 */
	protected $jpeg_quality = 100;

	/**
	 * Actual filename
	 * @var string
	 */
	public $current_filename = '';

	/**
	 * Parser-Objects
	 * @var array
	 */
	public $parsers;

	/**
	 * Current parser
	 * @var object
	 */
	public $current_parser;

	/**
	 * object stack
	 * @var array
	 */
	var $_obj_stack;

	/**
	 * done object stack
	 * @var array
	 */
	var $_don_obj_stack;

	/**
	 * Current Object Id.
	 * @var integer
	 */
	var $_current_obj_id;

	/**
	 * The name of the last imported page box
	 * @var string
	 */
	var $lastUsedPageBox;

	/**
	 * Cache for imported pages/template ids
	 * @var array
	 */
	var $_importedPages = [];

	/**
	 * Cache for imported page annotations
	 * @var array
	 */
	var $_importedAnnots = [];

	/**
	 * Number of TOC pages, used for annotation offset
	 * @var integer
	 */
	var $_numTOCpages = 0;

	/**
	 * First TOC page, used for annotation offset
	 * @var integer
	 */
	var $_TOCpagenum = 0;

	private $currentFilename;

	private $currentParser;

	/**
	 * Set a source-file
	 *
	 * @param string $filename a valid filename
	 * @throws Exception
	 * @return int number of available pages
	 */
	function setSourceFile($filename) {

		$_filename = realpath( $filename );
		if ( false !== $_filename ) {
			$filename = $_filename;
		}

		$currentFilename = $this->current_filename;
		$currentParser = $this->current_parser;

		try {
		$this->current_filename = $filename;

		if ( ! isset($this->parsers[$filename] ) ) {
			$this->parsers[$filename] = $this->_getPdfParser( $filename );
			$this->setPdfVersion(
				max( $this->getPdfVersion(), $this->parsers[$filename]->getPdfVersion() )
			);
		}
		$this->current_parser =& $this->parsers[$filename];

		} catch ( Exception $e ) {
			unset( $this->parsers[$filename] );
			$this->currentFilename = $currentFilename;
			$this->currentParser = $currentParser;
			throw $e;
		}

		return $this->parsers[$filename]->getPageCount();

	}

	/**
	 * Returns a PDF parser object
	 *
	 * @param string $filename
	 * @return tcpdi_parser
	 */
	function _getPdfParser($filename) {

		if ( ! class_exists('tcpdi_parser' ) ) {
			require_once( 'tcpdi_parser.php' );
		}

		try {
			$data = file_get_contents($filename);
		} catch ( Exception $e ) {
			$this->Error('Unable to get PDF file contents.');
		}
		return new tcpdi_parser($data, $filename);

	}

	/**
	 * Get the current PDF version
	 *
	 * @return string
	 */
	function getPDFVersion() {
		return $this->PDFVersion;
	}

	/**
	 * Set the PDF version
	 *
	 * @return void
	 */
	public function setPDFVersion($version = '1.3') {
		$this->PDFVersion = $version;
	}

	/**
	 * Import a page
	 *
	 * @param int $pageno pagenumber
	 * @return int Index of imported page - to use with fpdf_tpl::useTemplate()
	 */
	public function importPage($pageno, $boxName = '/CropBox') {

		if ($this->_intpl) {
			$this->Error('Please import the desired pages before creating a new template.');
		}

		$fn = $this->current_filename;

		// check if page already imported
		$pageKey = $fn . '-' . ((int)$pageno) . $boxName;
		if ( isset( $this->_importedPages[$pageKey] ) ) {
			return $this->_importedPages[$pageKey];
		}

		$parser =& $this->parsers[$fn];
		$parser->setPageno($pageno);

		if ( ! in_array( $boxName, $parser->availableBoxes ) ) {
			$this->Error( sprintf( 'Unknown box: %s', $boxName ) );
		}

		$pageboxes = $parser->getPageBoxes($pageno, $this->k);

		/**
		 * MediaBox
		 * CropBox: Default -> MediaBox
		 * BleedBox: Default -> CropBox
		 * TrimBox: Default -> CropBox
		 * ArtBox: Default -> CropBox
		 */

		if (!isset($pageboxes[$boxName]) && ($boxName == '/BleedBox' || $boxName == '/TrimBox' || $boxName == '/ArtBox')) {
			$boxName = '/CropBox';
		}
		if (!isset($pageboxes[$boxName]) && $boxName == '/CropBox') {
			$boxName = '/MediaBox';
		}

		if (!isset($pageboxes[$boxName])) {
			return false;
		}

		$this->lastUsedPageBox = $boxName;

		$box = $pageboxes[$boxName];

		$this->tpl++;
		$this->tpls[$this->tpl] = [];
		$tpl =& $this->tpls[$this->tpl];
		$tpl['parser'] =& $parser;
		$tpl['resources'] = $parser->getPageResources();
		$tpl['buffer'] = $parser->getContent();
		$tpl['box'] = $box;

		// To build an array that can be used by PDF_TPL::useTemplate()
		$this->tpls[$this->tpl] = array_merge($this->tpls[$this->tpl], $box);

		// An imported page will start at 0,0 everytime. Translation will be set in _putformxobjects()
		$tpl['x'] = 0;
		$tpl['y'] = 0;

		// handle rotated pages
		$rotation = $parser->getPageRotation($pageno);
		$tpl['_rotationAngle'] = 0;
		if (isset($rotation[1]) && ($angle = $rotation[1] % 360) != 0) {
			$steps = $angle / 90;

			$_w = $tpl['w'];
			$_h = $tpl['h'];
			$tpl['w'] = $steps % 2 == 0 ? $_w : $_h;
			$tpl['h'] = $steps % 2 == 0 ? $_h : $_w;
			if ($angle < 0) {
				$angle += 360;
			}
			$tpl['_rotationAngle'] = $angle * -1;
		}

		$this->_importedPages[$pageKey] = $this->tpl;
		return $this->tpl;
	}

	/**
	 * @param $pageno
	 * @param $orientation
	 * @param $size_array
	 *
	 * @return void
	 */
	public function setPageFormatFromTemplatePage($pageno, $orientation, $size_array) {
		$fn = $this->current_filename;
		$parser =& $this->parsers[$fn];
		$parser->setPageno($pageno);
		$boxes = $parser->getPageBoxes($pageno, $this->k);

		// added WWPDF 2.7 to cover scrappy pages with outrageous box sizes
		if ( array_key_exists( '/CropBox', $boxes ) &&
			array_key_exists( '/MediaBox', $boxes ) &&
			( $boxes['/MediaBox']['x'] == '0' && $boxes['/MediaBox']['y'] == '0' ) &&
			( $boxes['/CropBox']['x'] != '0' || $boxes['/CropBox']['y'] != '0' )
		) {

			foreach ( $boxes as $name => &$box ) {
				if ( array_key_exists( $name, $boxes ) ) {
					if ( $box['x'] != '0' || $box['y'] != '0' ) {
						$box['x'] = $box['y'] = $box['llx'] = $box['lly'] = 0;
						$box['urx'] = $box['w'];
						$box['ury'] = $box['h'];
					}
				}
				if ( $box['w'] > $size_array[0] ) {
					$box['w'] = $box['urx'] = $size_array[0];
				}
				if ( $box['h'] > $size_array[1] ) {
					$box['h'] = $box['ury'] = $size_array[1];
				}
			}
			unset( $box );
		}

		foreach ($boxes as $name => $box) {
			if ( $name[0] == '/' ) {
				$boxes[substr($name, 1)] = $box;
				unset($boxes[$name]);
			}
		}
		$this->setPageFormat($boxes, $orientation);

	}

	/**
	 * Wrapper for AddPage() which tracks TOC pages to offset annotations later
	 *
	 * @param string $orientation
	 * @param string $format
	 * @param bool $keepmargins
	 * @param bool $tocpage
	 */
	public function AddPage( $orientation='', $format='', $keepmargins=false, $tocpage=false ) {
		if ( $this->inxobj ) {
			// we are inside an XObject template
			return;
		}
		parent::AddPage( $orientation, $format, $keepmargins, $tocpage );
		if ( $this->tocpage ) {
			$this->_numTOCpages++;
		}

	}

	/**
	 * Wrapper for AddTOC() which tracks TOC position to offset annotations later
	 *
	 * @param string $page
	 * @param string $numbersfont
	 * @param string $filler
	 * @param string $toc_name
	 * @param string $style
	 * @param int[] $color
	 * @return void
	 */
	public function AddTOC( $page='', $numbersfont='', $filler='.', $toc_name='TOC', $style='', $color=[0,0,0] ) {
		if ( ! TCPDF_STATIC::empty_string( $page ) ) {
			$this->_TOCpagenum = $page;
		} else {
			$this->_TOCpagenum = $this->page;
		}

		parent::AddTOC( $page, $numbersfont, $filler, $toc_name, $style, $color );

	}

	/**
	 * @param int $pageno
	 *
	 * @return void
	 */
	public function importAnnotations( $pageno ) {

		$fn = $this->current_filename;
		$parser =& $this->parsers[$fn];
		$parser->setPageno( $pageno );
		if ( ! $annots = $parser->getPageAnnotations() ) {
			return;
		}
		if ( is_array( $annots ) ) {
			if ( $annots[0] == PDF_TYPE_OBJECT // We got an object (9)
				&& isset( $annots[1] ) && is_array( $annots[1] ) && $annots[1][0] == PDF_TYPE_ARRAY // It's an array (6)
				&& isset( $annots[1][1] ) && is_array( $annots[1][1] )
				&& count( $annots[1][1] ) > 0  // It's not empty - there are annotations for this page
			) {

				$this->_importedAnnots[ $pageno ] = [];
				if (!isset($this->_obj_stack[$fn])) {
					$this->_obj_stack[$fn] = [];
				}
				$objs = [];
				foreach ( $annots[1][1] as $annot ) {
					if ( PDF_TYPE_DICTIONARY === $annots[1][1][0][0] ) {
						if ( ! in_array( $annots['obj'], $objs ) ) {
							$this->importAnnotation( $annots['obj'], $pageno );
						}
						$objs[] = $annots['obj'];
					} else {
						/**
						 * Send a page number to importAnnotation because previously it
						 * was mixing up pages and putting links on the wrong page
						 */
						$this->importAnnotation( $annot, $pageno );
					}
				}
				unset( $annot );

			} else if (
				$annots[0] == PDF_TYPE_ARRAY // It's an array (6)
				&& is_array( $annots[1] ) && count( $annots[1] ) > 0  // It's not empty - there are annotations for this page
			) {
				if ( ! isset( $this->_obj_stack[$fn] ) ) {
					$this->_obj_stack[$fn] = [];
				}
				$this->_importedAnnots[$this->page] = [];
				foreach ($annots[1] as $annot) {
					$this->importAnnotation( $annot, $pageno );
				}
				unset( $annot );
			}

		}

	}

	/**
	 * @param $annot
	 * @param $pageno
	 *
	 * @return void
	 */
	public function importAnnotation( $annot, $pageno ) {
		if ( is_numeric( $annot ) ) {
			$old_id = $annot;
		} else {
			$old_id = $annot[1] ?? '';
		}
		if ( ! is_numeric( $old_id ) ) {
			return;
		}
		$value = [ PDF_TYPE_OBJREF, $old_id, 0 ]; // 8

		$fn = $this->current_filename;
		if ( ! isset($this->_don_obj_stack[$fn][$old_id] ) ) {
			$this->_newobj(false, true);
			$this->_obj_stack[$fn][$old_id] = [$this->n, $value];
			$this->_don_obj_stack[$fn][$old_id] = [$this->n, $value];
		}
		$objid = $this->_don_obj_stack[$fn][$old_id][0];
		$this->_importedAnnots[$pageno][] = $objid;

	}


	/**
	 * Get references to page annotations
	 * @param $pageno (int) page number
	 * @return string
	 * @protected
	 * @author Nicola Asuni
	 * @since 5.0.010 (2010-05-17)
	 */
	protected function _getannotsrefs( $pageno ) {

		if ( ! empty( $this->_numTOCpages ) && $pageno >= $this->_TOCpagenum ) {
			// Offset page number to account for TOC being inserted before page containing annotations.
			$pageno -= $this->_numTOCpages;
		}
		if ( !
			( isset($this->_importedAnnots[$pageno] )
				|| isset( $this->PageAnnots[$pageno] )
				|| ( $this->sign && isset( $this->signature_data['cert_type'] ) )
			)
		) {
			return '';
		}
		$out = ' /Annots [';
		if ( isset( $this->_importedAnnots[ $pageno ] ) && ! empty( $this->_importedAnnots[ $pageno ] ) ) {
			foreach ( $this->_importedAnnots[ $pageno ] as $val ) {
				$out .= ' '.$val.' 0 R';
			}
		}
		if ( isset( $this->PageAnnots[$pageno] ) ) {
			foreach ( $this->PageAnnots[ $pageno ] as $val ) {
				if ( ! in_array( $val['n'], $this->radio_groups ) ) {
					$out .= ' '.$val['n'].' 0 R';
				}
			}
			// add radiobutton groups
			if ( isset( $this->radiobutton_groups[$pageno] ) ) {
				foreach ( $this->radiobutton_groups[$pageno] as $data ) {
					if ( isset($data['n'] ) ) {
						$out .= ' '.$data['n'].' 0 R';
					}
				}
			}
		}
		if ( $this->sign && ( $pageno == $this->signature_appearance['page'] ) && isset( $this->signature_data['cert_type'] ) ) {
			// set reference for signature object
			$out .= ' '.$this->sig_obj_id.' 0 R';
		}
		if ( ! empty( $this->empty_signature_appearance ) ) {
			foreach ( $this->empty_signature_appearance as $esa ) {
				if ( $esa['page'] == $pageno ) {
					// set reference for empty signature objects
					$out .= ' '.$esa['objid'].' 0 R';
				}
			}
		}
		$out .= ' ]';
		return $out;
	}


	/**
	 * Returns the last used page box
	 *
	 * @return string
	 */
	public function getLastUsedPageBox() {
		return $this->lastUsedPageBox;
	}

	/**
	 * @param $tplidx
	 * @param $_x
	 * @param $_y
	 * @param $_w
	 * @param $_h
	 * @param $adjustPageSize
	 *
	 * @return array
	 */
	public function useTemplate($tplidx, $_x = null, $_y = null, $_w = 0, $_h = 0, $adjustPageSize = false) {

		if ( $adjustPageSize == true && is_null( $_x ) && is_null( $_y ) ) {
			$size = $this->getTemplateSize( $tplidx, $_w, $_h );
			$orientation = $size['w'] > $size['h'] ? 'L' : 'P';
			$size = [$size['w'], $size['h']];

			$this->setPageFormat( $size, $orientation );
		}

		$this->_out('q 0 J 1 w 0 j 0 G 0 g'); // reset standard values
		$size = parent::useTemplate( $tplidx, $_x, $_y, $_w, $_h );
		$this->_out( 'Q' );
		return $size;
	}

	/**
	 * Rebuilds all needed objects of source files
	 *
	 * @return void
	 */
	public function _putimportedobjects() {
		if ( is_array( $this->parsers ) && count( $this->parsers ) > 0 ) {
			foreach( $this->parsers AS $filename => $p ) {
				$this->current_parser =& $this->parsers[$filename];
				if (isset( $this->_obj_stack[$filename] ) && is_array( $this->_obj_stack[$filename] ) ) {
					while( ( $n = key($this->_obj_stack[$filename] ) ) !== null) {
						$nObj = $this->current_parser->getObjectVal( $this->_obj_stack[$filename][$n][1] );

						$this->_newobj( $this->_obj_stack[$filename][$n][0] );

						if ( $nObj[0] == PDF_TYPE_STREAM ) {
							$this->pdf_write_value( $nObj );
						} else {
							$this->pdf_write_value( $nObj[1] );
						}

						$this->_out( 'endobj' );
						$this->_obj_stack[$filename][$n] = null; // free memory
						unset( $this->_obj_stack[$filename][$n] );
						reset( $this->_obj_stack[$filename] );
					}
				}

				// Clean up this parser to free a bit of RAM
				$this->current_parser->cleanUp();
				unset( $this->parsers[$filename] );
			}
		}
	}

	/**
	 * Method that writes the form xobjects
	 *
	 * @return void
	 */
	public function _putformxobjects() {
		$filter=($this->compress) ? '/Filter /FlateDecode ' : '';
		reset($this->tpls);
		foreach($this->tpls AS $tplidx => $tpl) {
			$p=($this->compress) ? gzcompress($tpl['buffer']) : $tpl['buffer'];
			$this->_newobj();
			$cN = $this->n; // TCPDF/Protection: rem current "n"

			$this->tpls[$tplidx]['n'] = $this->n;
			$this->_out('<<' . $filter . '/Type /XObject');
			$this->_out('/Subtype /Form');
			$this->_out('/FormType 1');

			$this->_out(sprintf('/BBox [%.2F %.2F %.2F %.2F]',
				(isset($tpl['box']['llx']) ? $tpl['box']['llx'] : $tpl['x']) * $this->k,
				(isset($tpl['box']['lly']) ? $tpl['box']['lly'] : -$tpl['y']) * $this->k,
				(isset($tpl['box']['urx']) ? $tpl['box']['urx'] : $tpl['w'] + $tpl['x']) * $this->k,
				(isset($tpl['box']['ury']) ? $tpl['box']['ury'] : $tpl['h'] - $tpl['y']) * $this->k
			));

			$c = 1;
			$s = 0;
			$tx = 0;
			$ty = 0;

			if (isset($tpl['box'])) {
				$tx = -$tpl['box']['llx'];
				$ty = -$tpl['box']['lly'];

				if ($tpl['_rotationAngle'] <> 0) {
					$angle = $tpl['_rotationAngle'] * M_PI/180;
					$c=cos($angle);
					$s=sin($angle);

					switch($tpl['_rotationAngle']) {
						case -90:
						   $tx = -$tpl['box']['lly'];
						   $ty = $tpl['box']['urx'];
						   break;
						case -180:
							$tx = $tpl['box']['urx'];
							$ty = $tpl['box']['ury'];
							break;
						case -270:
							$tx = $tpl['box']['ury'];
							$ty = -$tpl['box']['llx'];
							break;
					}
				}
			} elseif ($tpl['x'] != 0 || $tpl['y'] != 0) {
				$tx = -$tpl['x'] * 2;
				$ty = $tpl['y'] * 2;
			}

			$tx *= $this->k;
			$ty *= $this->k;

			if ($c != 1 || $s != 0 || $tx != 0 || $ty != 0) {
				$this->_out(sprintf('/Matrix [%.5F %.5F %.5F %.5F %.5F %.5F]',
					$c, $s, -$s, $c, $tx, $ty
				));
			}

			$this->_out('/Resources ');

			if (isset($tpl['resources'])) {
				$this->current_parser =& $tpl['parser'];
				$this->pdf_write_value($tpl['resources']); // "n" will be changed
			} else {
				$this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
				if (isset($this->_res['tpl'][$tplidx]['fonts']) && count($this->_res['tpl'][$tplidx]['fonts'])) {
					$this->_out('/Font <<');
					foreach($this->_res['tpl'][$tplidx]['fonts'] as $font)
						$this->_out('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
					$this->_out('>>');
				}
				if(isset($this->_res['tpl'][$tplidx]['images']) && count($this->_res['tpl'][$tplidx]['images']) ||
				   isset($this->_res['tpl'][$tplidx]['tpls']) && count($this->_res['tpl'][$tplidx]['tpls']))
				{
					$this->_out('/XObject <<');
					if (isset($this->_res['tpl'][$tplidx]['images']) && count($this->_res['tpl'][$tplidx]['images'])) {
						foreach($this->_res['tpl'][$tplidx]['images'] as $image)
							$this->_out('/I' . $image['i'] . ' ' . $image['n'] . ' 0 R');
					}
					if (isset($this->_res['tpl'][$tplidx]['tpls']) && count($this->_res['tpl'][$tplidx]['tpls'])) {
						foreach($this->_res['tpl'][$tplidx]['tpls'] as $i => $_tpl) {
							$this->_out($this->tplprefix . $i . ' ' . $_tpl['n'] . ' 0 R');
					}
					}
					$this->_out('>>');
				}
				$this->_out('>>');
			}

			$this->_out('/Group <</Type/Group/S/Transparency>>');

			$nN = $this->n; // TCPDF: rem new "n"
			$this->n = $cN; // TCPDF: reset to current "n"

			$p = $this->_getrawstream($p);
			$this->_out('/Length ' . strlen($p) . ' >>');
			$this->_out("stream\n" . $p . "\nendstream");

			$this->_out('endobj');
			$this->n = $nN; // TCPDF: reset to new "n"
		}

		$this->_putimportedobjects();
	}

	/**
	 * Rewritten to handle existing own defined objects
	 */
	public function _newobj($obj_id = false, $onlynewobj = false) {
		if (!$obj_id) {
			$obj_id = ++$this->n;
		}

		//Begin a new object
		if (!$onlynewobj) {
			$this->offsets[$obj_id] = $this->bufferlen;
			$this->_out($obj_id . ' 0 obj');
			// $this->_straightOut( $obj_id . ' 0 obj' );
			$this->_current_obj_id = $obj_id; // for later use with encryption
		}

		return $obj_id;
	}

	/**
	 * Writes a value
	 * Needed to rebuild the source document
	 *
	 * @param mixed $value A PDF-Value. Structure of values see cases in this method
	 */
	public function pdf_write_value(&$value) {
		if ( is_int( $value ) || ! $value ) {
			return;
		}
		switch ($value[0]) {
			case PDF_TYPE_STRING:
				if ($this->encrypted) {
					$value[1] = $this->_unescape($value[1]);
					$value[1] = $this->_encrypt_data($this->_current_obj_id, $value[1]);
					$value[1] = TCPDF_STATIC::_escape($value[1]);
				}
				break;

			case PDF_TYPE_STREAM:
				if ($this->encrypted) {
					$value[2][1] = $this->_encrypt_data($this->_current_obj_id, $value[2][1]);
					$value[1][1]['/Length'] = [
						PDF_TYPE_NUMERIC,
						strlen($value[2][1])
					];
				}
				break;

			case PDF_TYPE_HEX:
				if ($this->encrypted) {
					$value[1] = $this->hex2str($value[1]);
					$value[1] = $this->_encrypt_data($this->_current_obj_id, $value[1]);

					// remake hexstring of encrypted string
					$value[1] = $this->str2hex($value[1]);
				}
				break;
		}

		switch ($value[0]) {

			case PDF_TYPE_TOKEN:
				$this->_straightOut( '/' . $value[1] );
				break;
			case PDF_TYPE_NUMERIC:
			case PDF_TYPE_REAL:
				if ( 0 != $value[1] && is_float( $value[1] ) ) {
					$this->_straightOut( rtrim( rtrim( sprintf('%F', $value[1] ), '0' ), '.' ) );
				} else {
					$this->_straightOut( $value[1] );
				}
				break;

			case PDF_TYPE_ARRAY:

				// An array. Output the proper structure and move on
				$this->_straightOut('[');
				for ($i = 0; $i < count($value[1]); $i++) {
					$this->pdf_write_value($value[1][$i]);
				}

				$this->_straightOut( ']' );
				break;

			case PDF_TYPE_DICTIONARY:

				// A dictionary
				$this->_straightOut('<<');

				reset ($value[1]);
				foreach( $value[1] as $k => $v ) {
					// /Annots need to be in brackets [] even if not an array of annots
					if ( '/Annots' === $k && $v[0] !== PDF_TYPE_ARRAY ) {
						$this->_straightOut($k . ' [');
					} else {
						$this->_straightOut( $k );
					}
					$this->pdf_write_value($v);
					// Again, /Annots need to be in brackets [] even if not an array of annots
					if ( '/Annots' === $k && $v[0] !== PDF_TYPE_ARRAY ) {
						$this->_straightOut( ']' );
				}
				}
				$this->_straightOut('>>');
				break;

			case PDF_TYPE_OBJREF:

				// An indirect object reference
				// Fill the object stack if needed
				$cpfn =& $this->current_parser->uniqueid;

				if (!isset($this->_don_obj_stack[$cpfn][$value[1]])) {
					$this->_newobj(false, true);
					$this->_obj_stack[$cpfn][$value[1]] = [$this->n, $value];
					$this->_don_obj_stack[$cpfn][$value[1]] = [$this->n, $value]; // Value is maybe obsolete!
				}
				$objid = $this->_don_obj_stack[$cpfn][$value[1]][0];
			//	$this->_out( $objid . ' 0 R' ); // original
				$this->_straightOut( $objid . ' 0 R' );
				break;

			case PDF_TYPE_STRING:

				// A string
				$this->_straightOut('(' . $value[1] . ')');

				break;

			case PDF_TYPE_STREAM:

				// A stream. First, output the stream dictionary,
				// then the stream data itself
				$this->pdf_write_value($value[1]);
				$this->_out('stream');
				$this->_out($value[2][1]);
				$this->_out('endstream');
				break;

			case PDF_TYPE_HEX:
				$this->_straightOut('<' . $value[1] . '>');
				break;

			case PDF_TYPE_BOOLEAN:
				$this->_straightOut($value[1] ? 'true ' : 'false ');
				break;

			case PDF_TYPE_NULL:
				// The null object
				$this->_straightOut('null ');
				break;
		}
	}

	/**
	 * Modified so not each call will add a newline to the output
	 * Use a space instead
	 *
	 * @param string $s
	 */
	public function _straightOut($s) {
		if ($this->state == 2) {
			if ($this->inxobj) {
				// we are inside an XObject template
				$this->xobjects[$this->xobjid]['outdata'] .= $s . " ";
			} else if ( ! $this->InFooter
				&& isset( $this->footerlen[$this->page] )
				&& $this->footerlen[$this->page] > 0
			) {
				// puts data before page footer
				$page_buffer = $this->getPageBuffer( $this->page );
				$page = substr( $page_buffer, 0, -$this->footerlen[$this->page] );
				$footer = substr( $page_buffer, -$this->footerlen[$this->page] );
				$this->setPageBuffer( $this->page, $page . $s . " " . $footer );
				// update footer position
				$this->footerpos[$this->page] += strlen( $s . " " );
			} else {
				// set page data
				$this->setPageBuffer($this->page, $s . " ", true);
			}
		} elseif ($this->state > 0) {
			// set general data
			$this->setBuffer( $s . " " );
		}
	}

	/**
	 * rewritten to close opened parsers
	 *
	 */
	public function _enddoc() {
		parent::_enddoc();
		$this->_closeParsers();
	}

	/**
	 * close all files opened by parsers
	 */
	public function _closeParsers() {
		if ($this->state > 2 && count($this->parsers) > 0) {
			$this->cleanUp();
			return true;
		}
		return false;
	}

	/**
	 * Removes cycled references and closes the file handles of the parser objects
	 */
	public function cleanUp() {
		foreach ($this->parsers as $k => $_){
			$this->parsers[$k]->cleanUp();
			$this->parsers[$k] = null;
			unset($this->parsers[$k]);
		}
	}

	// Functions from here on are taken from FPDI's fpdi2tcpdf_bridge.php to remove dependence on it

	/**
	 * @param $s
	 * @param int $n
	 *
	 */
	public function _putstream($s, $n=0) {
		$this->_out($this->_getstream($s, $n));
	}

	/**
	 * @return string
	 */
	public function _getxobjectdict() {
		$out = parent::_getxobjectdict();
		if (count($this->tpls)) {
			foreach($this->tpls as $tplidx => $tpl) {
				$out .= sprintf('%s%d %d 0 R', $this->tplprefix, $tplidx, $tpl['n']);
			}
		}

		return $out;
	}

	/**
	 * Unescapes a PDF string
	 *
	 * @param string $s
	 * @return string
	 */
	public function _unescape($s) {
		$out = '';
		for ($count = 0, $n = strlen($s); $count < $n; $count++) {
			if ($s[$count] != '\\' || $count == $n-1) {
				$out .= $s[$count];
			} else {
				switch ($s[++$count]) {
					case ')':
					case '(':
					case '\\':
						$out .= $s[$count];
						break;
					case 'f':
						$out .= chr(0x0C);
						break;
					case 'b':
						$out .= chr(0x08);
						break;
					case 't':
						$out .= chr(0x09);
						break;
					case 'r':
						$out .= chr(0x0D);
						break;
					case 'n':
						$out .= chr(0x0A);
						break;
					case "\r":
						if ($count != $n-1 && $s[$count+1] == "\n")
							$count++;
						break;
					case "\n":
						break;
					default:
						// Octal-Values
						if (ord($s[$count]) >= ord('0') &&
							ord($s[$count]) <= ord('9')) {
							$oct = ''. $s[$count];

							if (ord($s[$count+1]) >= ord('0') &&
								ord($s[$count+1]) <= ord('9')) {
								$oct .= $s[++$count];

								if (ord($s[$count+1]) >= ord('0') &&
									ord($s[$count+1]) <= ord('9')) {
									$oct .= $s[++$count];
								}
							}

							$out .= chr(octdec($oct));
						} else {
							$out .= $s[$count];
						}
				}
			}
		}
		return $out;
	}

	/**
	 * Hexadecimal to string
	 *
	 * @param string $hex
	 * @return string
	 */
	public function hex2str($hex) {
		return pack('H*', str_replace(["\r", "\n", ' '], '', $hex));
	}

	/**
	 * String to hexadecimal
	 *
	 * @param string $str
	 * @return string
	 */
	public function str2hex($str) {
		return current(unpack('H*', $str));
	}
}