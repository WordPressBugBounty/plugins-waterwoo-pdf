<?php
//============================================================+
// File name    : cynpdi.php
// Version      : 1.0
// Begin        : 2026-08-15
// Author       : C. Paquette - Canyon Webworks, LLC - www.canyonwebworks.com / www.pdfink.com
// License      : GNU-LGPL v3 (https://www.gnu.org/licenses/lgpl-3.0.html)
//
// Based on     : tcpdi (based on FPDI)
// Version      : 1.1
// Begin        : 2013-09-25
// Last Update  : 2016-05-03
// Author       : Paul Nicholls - https://github.com/pauln/tcpdi
// License      : GNU-LGPL v3 (http://www.gnu.org/copyleft/lesser.html)
//
// Based on     : FPDI v1.4.4
// Version      : 1.1
// Author       : Jan Slabon - https://github.com/setasign
// License      : Apache-2.0 (https://www.apache.org/licenses/LICENSE-2.0)
//
//============================================================+

namespace CanyonWebworks\pdfInkLite\lib\CanyonWebworks;

use CanyonWebworks\pdfInkLite\lib\TCPDF_Child;
use CanyonWebworks\pdfInkLite\lib\tecnick\tcpdf\includes\TCPDF_STATIC;
use CanyonWebworks\pdfInkLite\lib\pauln\tcpdi_parser\tcpdi_parser;
use Exception;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/templateHandler.php';
require_once __DIR__ . '/annotationHandler.php';

class cynpdi extends TCPDF_Child {

	use annotationHandler;
	use templateHandler;

	public string $filename = '';

	private object|null $parser = null;

	private array $objectStack;

	private array $doneObjectStack;

	private int $currentObjectId;

	private array $importedPages = [];

	private array $sourcePageObjIds = [];

	private array $importedAnnots = [];

	private int $numTocPages = 0;

	private int $tocPageNum = 0;

	protected array $templates = [];

	public int $currentTemplateID = 0;

	private string $tplPrefix = "/TPL";

	protected array $resources = [];

	/**
	 * Cache resolved named destinations to avoid repeated /Names tree traversal
	 *
	 * Keyed by a stable hash of the input /D value.
	 *
	 * @var array<string, array|null>
	 */
	private $namedDestinationCache = [];

	/**
	 * Cached catalog dictionary resolution (from the PDF trailer /Root)
	 *
	 * @var array|null
	 */
	private $catalogDictionaryCache = null;

	/**
	 * Whether catalogDictionaryCache has been computed
	 *
	 * @var bool
	 */
	private $catalogDictionaryCacheInitialized = false;

	/**
	 * Set a source file: the original PDF
	 *
	 * @param string $filename a valid filename
	 * @return int number of available pages
	 * @throws Exception
	 */
	function setSourceFile( $filename ): int {

		$_filename = realpath( $filename );
		if ( false !== $_filename ) {
			$filename = $_filename;
		}

		try {
			$this->filename = $filename;
			$this->parser = $this->_getPdfParser( $filename );
			$this->setPdfVersion( max( $this->getPdfVersion(), $this->parser->getPdfVersion() ) );

		} catch ( Exception $e ) {
			unset( $this->parser );
			throw $e;
		}

		return $this->parser->getPageCount();

	}

	/**
	 * Return a PDF parser object
	 *
	 * @param string $filename
	 *
	 * @return tcpdi_parser
	 * @throws Exception
	 */
	private function _getPdfParser( $filename ) {

		try {
			$data = file_get_contents( $filename );
		} catch ( Exception $e ) {
			$this->Error( 'Unable to get PDF file contents.' );
		}
		return new tcpdi_parser( $data, $filename );

	}

	/**
	 * Get the current PDF version
	 *
	 * @return string
	 */
	public function getPDFVersion() {
		return $this->PDFVersion;
	}

	/**
	 * Set the PDF version
	 *
	 * @param string $version
	 * @return void
	 */
	public function setPDFVersion( $version = '1.3' ): void {
		$this->PDFVersion = $version;
	}

	/**
	 * Import a page
	 *
	 * @param int $pageno pagenumber
	 * @return int Index of imported page - to use with fpdf_tpl::useTemplate()
	 *
	 * @author Jan Slabon - https://github.com/setasign
	 * @author Paul Nicholls - https://github.com/pauln/tcpdi
	 * @author Gerhard Potgieter http://gerhardpotgieter.com/
	 * @author Canyon Webworks https://github.com/canyonwebworks
	 */
	public function importPage( $pageno, $boxName = '/CropBox' ) {

		$fn = $this->filename;

		// Build the source-page-object-ID → output-page-number map needed to
		// remap internal link destinations when annotation objects are written.
		// getPageObjectId() does not depend on setPageno(), so it is safe to
		// call here, before the cache check, to ensure the entry is recorded
		// even when the template is returned from cache on repeat calls.
		// Hat tip: @author: Gerhard Potgieter http://gerhardpotgieter.com/
		$srcObjectId = $this->parser->getPageObjectId( (int) $pageno );
		if ( $srcObjectId !== null ) {
			if ( ! isset( $this->sourcePageObjIds[ $fn ] ) ) {
				$this->sourcePageObjIds[ $fn ] = [];
			}
			/**
			 * Map of source PDF page object IDs to output page numbers.
			 * Keyed by [source_filename][source_obj_id] => output_page_number.
			 * Used in writeValue() to remap page references inside copied
			 * annotation objects so that internal navigation links survive watermarking.
			 */
			$this->sourcePageObjIds[ $fn ][ $srcObjectId ] = (int) $pageno;
		} /* end hat tip 🎩 */

		// check if page already imported
		$pageKey = $fn . '-' . ( (int) $pageno ) . $boxName;
		if ( isset( $this->importedPages[$pageKey] ) ) {
			return $this->importedPages[$pageKey];
		}

		$this->parser->setPageno( $pageno );

		if ( ! in_array( $boxName, $this->parser->availableBoxes ) ) {
			$this->Error( sprintf( 'Unknown box: %s', $boxName ) );
		}

		$pageboxes = $this->parser->getPageBoxes( $pageno, $this->k );

		/**
		 * MediaBox
		 * CropBox: Default -> MediaBox
		 * BleedBox: Default -> CropBox
		 * TrimBox: Default -> CropBox
		 * ArtBox: Default -> CropBox
		 */

		if ( ! isset( $pageboxes[$boxName] ) && in_array( $boxName, [ '/BleedBox', '/TrimBox', '/ArtBox' ] ) ) {
			$boxName = '/CropBox';
		}
		if ( ! isset( $pageboxes[$boxName] ) && '/CropBox' === $boxName ) {
			$boxName = '/MediaBox';
		}

		if ( ! isset( $pageboxes[$boxName] ) ) {
			return false;
		}

		$box = $pageboxes[$boxName];

		++$this->currentTemplateID;
		$this->templates[$this->currentTemplateID] = [];
		$tpl =& $this->templates[$this->currentTemplateID];
		$tpl['parser'] =& $this->parser;
		$tpl['resources']   = $this->parser->getPageResources();
		$tpl['buffer'] = $this->parser->getContent();
		$tpl['box'] = $box;

		// To build an array that can be used by PDF_TPL::useTemplate()
		$this->templates[$this->currentTemplateID] = array_merge( $this->templates[$this->currentTemplateID], $box );

		// An imported page will start at 0,0 everytime. Translation will be set in putFormXObjects()
		$tpl['x'] = 0;
		$tpl['y'] = 0;

		// handle rotated pages
		$rotation = $this->parser->getPageRotation( $pageno );
		$tpl['_rotationAngle'] = 0;
		if ( isset( $rotation[1] ) && ( $angle = $rotation[1] % 360 ) != 0 ) {
			$steps = $angle / 90;

			$_w = $tpl['w'];
			$_h = $tpl['h'];
			$tpl['w'] = $steps % 2 == 0 ? $_w : $_h;
			$tpl['h'] = $steps % 2 == 0 ? $_h : $_w;
			if ( $angle < 0 ) {
				$angle += 360;
			}
			$tpl['_rotationAngle'] = $angle * -1;
		}

		$this->importedPages[$pageKey] = $this->currentTemplateID;
		return $this->currentTemplateID;

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
			++$this->numTocPages;
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
	 *
	 * @return void
	 * @author Paul Nicholls - https://github.com/pauln
	 */
	public function AddTOC( $page='', $numbersfont='', $filler='.', $toc_name='TOC', $style='', $color=[0,0,0] ) {
		if ( ! TCPDF_STATIC::empty_string( $page ) ) {
			$this->tocPageNum = $page;
		} else {
			$this->tocPageNum = $this->page;
		}
		parent::AddTOC( $page, $numbersfont, $filler, $toc_name, $style, $color );

	}

	/**
	 * Rewritten to handle existing own defined objects
	 *
	 * @param bool $obj_id
	 * @param bool $onlynewobj
	 * @return int $obj_id
	 */
	public function _newobj( $obj_id = false, $onlynewobj = false ) {

		if ( ! $obj_id ) {
			$obj_id = ++$this->n;
		}

		// Begin a new object
		if ( ! $onlynewobj ) {
			$this->offsets[$obj_id] = $this->bufferlen;
			$this->_out( $obj_id . ' 0 obj' );
			$this->currentObjectId = $obj_id; // for later use with encryption
		}
		return $obj_id;

	}

	/**
	 * Writes a value
	 * Needed to rebuild the source document
	 *
	 * @param mixed $value A PDF-Value. Structure of values see cases in this method
	 */
	public function writeValue( &$value ): void {

		if ( is_int( $value ) || ! $value ) {
			return;
		}
		switch ( $value[0] ) {
			case PDF_TYPE_STRING:
				if ( $this->encrypted ) {
					$value[1] = $this->_unescape($value[1]);
					$value[1] = $this->_encrypt_data($this->currentObjectId, $value[1]);
					$value[1] = TCPDF_STATIC::_escape($value[1]);
				}
				break;

			case PDF_TYPE_STREAM:
				if ( $this->encrypted ) {
					$value[2][1] = $this->_encrypt_data( $this->currentObjectId, $value[2][1] );
					$value[1][1]['/Length'] = [
						PDF_TYPE_NUMERIC,
						strlen( $value[2][1] )
					];
				}
				break;

			case PDF_TYPE_HEX:
				if ( $this->encrypted ) {
					$value[1] = pack( 'H*', str_replace( ["\r", "\n", ' '], '', $value[1] ) );
					$value[1] = $this->_encrypt_data( $this->currentObjectId, $value[1] );
					// remake hexstring of encrypted string
					$value[1] = current( unpack( 'H*', $value[1] ) );
				}
				break;
		}

		switch ( $value[0] ) {

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
				for ( $i = 0; $i < count($value[1]); $i++ ) {
					$this->writeValue( $value[1][$i] );
				}
				$this->_straightOut( ']' );
				break;

			case PDF_TYPE_DICTIONARY:

				/* Add the /GoTo action to the dictionary value ✨ */
				$this->getGoToDest( $value );

				$this->_straightOut( '<<' );
				reset( $value[1] );
				foreach( $value[1] as $k => $v ) {
					// /Annots need to be in brackets [] even if not an array of annots
					if ( '/Annots' === $k && $v[0] !== PDF_TYPE_ARRAY ) {
						$this->_straightOut( $k . ' [' );
					} else {
						$this->_straightOut( $k );
					}
					$this->writeValue( $v );
					// Again, /Annots need to be in brackets [] even if not an array of annots
					if ( '/Annots' === $k && $v[0] !== PDF_TYPE_ARRAY ) {
						$this->_straightOut( ']' );
					}
				}
				$this->_straightOut( '>>' );
				break;

			case PDF_TYPE_OBJREF:
				// An indirect object reference
				// Fill the object stack if needed
				$cpfn =& $this->parser->uniqueid;

				// Remap source page object references to the corresponding output
				// page objects so that internal navigation links (whose /Dest arrays
				// contain direct page-object references) survive the watermark process.
				// page_obj_id[$n] is populated by _putpages(), which always runs before
				// _putimportedobjects(), so the mapping is available here.
				if ( isset( $this->sourcePageObjIds[ $cpfn ][ (int) $value[1] ] ) ) {
					$outPageNum = $this->sourcePageObjIds[ $cpfn ][ (int) $value[1] ];
					if ( isset( $this->page_obj_id[ $outPageNum ] ) ) {
						$this->_out( $this->page_obj_id[ $outPageNum ] . ' 0 R' );
						break;
					}
				}

				if ( ! isset( $this->doneObjectStack[$cpfn][$value[1]] ) ) {
					$this->_newobj( false, true );
					$this->objectStack[$cpfn][$value[1]] = [$this->n, $value];
					$this->doneObjectStack[$cpfn][$value[1]] = [$this->n, $value]; // Value is maybe obsolete!
				}
				$objid = $this->doneObjectStack[$cpfn][$value[1]][0];
				$this->_out( $objid . ' 0 R' );
				break;

			case PDF_TYPE_STRING:
				// A string
				$this->_straightOut( '(' . $value[1] . ')' );
				break;

			case PDF_TYPE_STREAM:
				// A stream. First, output the stream dictionary,
				// then the stream data itself
				$this->writeValue( $value[1] );
				$this->_out( 'stream' );
				$this->_out( $value[2][1] );
				$this->_out( 'endstream' );
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
	 * _straightOut() method added (vs. _out() ) so not each call will add a newline to the output
	 * Use a space instead
	 *
	 * @param string $s
	 */
	public function _straightOut( $s ) {

		if ( $this->state == 2 ) {
			if ( $this->inxobj ) {
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
				$this->setPageBuffer( $this->page, $s . " ", true );
			}
		} elseif ( $this->state > 0 ) {
			// set general data
			$this->setBuffer( $s . " " );
		}

	}

	/**
	 * Inherited to maybe clean up
	 *
	 */
	public function _enddoc() {

		parent::_enddoc();
		$this->parser = null;
		$this->importedAnnots = [];

	 }

	 /**
	 * Crucial for rewriting the objects we have parsed
	 *
	 * @return string
	 */
	protected function _getxobjectdict() {

		$out = parent::_getxobjectdict();
		if ( count( $this->templates ) ) {
			foreach( $this->templates as $tplidx => $tpl ) {
				$out .= sprintf( '%s%d %d 0 R', $this->tplPrefix, $tplidx, $tpl['n'] );
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
		for ( $count = 0, $n = strlen($s); $count < $n; $count++ ) {
			if ( $s[$count] != '\\' || $count == $n-1 ) {
				$out .= $s[$count];
			} else {
				switch ( $s[++$count] ) {
					case ')':
					case '(':
					case '\\':
						$out .= $s[$count];
						break;
					case 'f':
						$out .= chr( 0x0C );
						break;
					case 'b':
						$out .= chr( 0x08 );
						break;
					case 't':
						$out .= chr( 0x09 );
						break;
					case 'r':
						$out .= chr( 0x0D );
						break;
					case 'n':
						$out .= chr( 0x0A );
						break;
					case "\r":
						if ( $count != $n-1 && $s[$count+1] === "\n" ) {
							++ $count;
						}
						break;
					case "\n":
						break;
					default:
						// Octal-Values
						if (ord( $s[$count] ) >= ord( '0' ) &&
							ord( $s[$count] ) <= ord( '9' ) ) {
							$oct = ''. $s[$count];

							if ( ord( $s[$count+1] ) >= ord( '0' ) &&
								ord( $s[$count+1] ) <= ord( '9' ) ) {
								$oct .= $s[++$count];

								if ( ord( $s[$count+1] ) >= ord( '0' ) &&
									ord( $s[$count+1] ) <= ord( '9' ) ) {
									$oct .= $s[++$count];
								}
							}
							$out .= chr( octdec( $oct ) );
						} else {
							$out .= $s[$count];
						}
				}
			}
		}
		return $out;

	}

	/**
	 * @param $obj
	 * @param $key
	 * @param $expected_value
	 *
	 * @return bool
	 */
	private function isTypeToken( $obj, $key, $expected_value ) {

		return isset( $obj[$key] )
			&& is_array( $obj[$key] )
			&& $obj[$key][0] === PDF_TYPE_TOKEN
			&& $obj[$key][1] === $expected_value;

	}

	/**
	 * @param $obj
	 *
	 * @return bool
	 */
	private function isTypeArray( $obj ) {
		return is_array( $obj ) && isset( $obj[0] ) && $obj[0] === PDF_TYPE_ARRAY;
	}

	/**
	 * @param $obj
	 *
	 * @return bool
	 */
	private function isTypeDictionary( $obj ) {
		return is_array( $obj ) && isset( $obj[0], $obj[1] ) && $obj[0] === PDF_TYPE_DICTIONARY;
	}

}
