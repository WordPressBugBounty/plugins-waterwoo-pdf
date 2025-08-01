<?php
//============================================================+
// File name    : tcpdi_parser.php
// Version      : 1.2
// Begin        : 2024-10-18
// Last Update  : 2025-02-28
// Author       : Little Package - https://github.com/littlepackage
// License      : GNU-LGPL v3 (https://www.gnu.org/licenses/lgpl-3.0.en.html)
//
// Based on     : tcpdi_parser.php
// Version      : 1.1
// Begin        : 2013-09-25
// Last Update  : 2016-05-03
// Author       : Paul Nicholls - https://github.com/pauln
// License      : GNU-LGPL v3 (http://www.gnu.org/copyleft/lesser.html)
//
// Based on     : tcpdf_parser.php
// Version      : 1.0.003
// Begin        : 2011-05-23
// Last Update  : 2013-03-17
// Author       : Nicola Asuni - Tecnick.com LTD - www.tecnick.com - info@tecnick.com
// License      : GNU-LGPL v3 (http://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2011-2013 Nicola Asuni - Tecnick.com LTD
//
// This file is for use with the TCPDF software library.
//
// tcpdi_parser is free software: you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// tcpdi_parser is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the License
// along with tcpdi_parser. If not, see
// <http://www.tecnick.com/pagefiles/tcpdf/LICENSE.TXT>.
//
// See LICENSE file for more information.
// -------------------------------------------------------------------
//
// Description : This is a PHP class for parsing PDF documents.
//
//============================================================+

namespace LittlePackage\lib\tcpdi\pauln\tcpdi;

require_once __DIR__ . '/../tcpdf/tcpdf/include/tcpdf_filters.php';

use LittlePackage\lib\tcpdf\tecnick\tcpdf\includes\TCPDF_FILTERS as TCPDF_FILTERS;
use Exception;

/**
 * @file
 * This is a PHP class for parsing PDF documents.
 * @author Paul Nicholls
 * @author Nicola Asuni
 * @version 1.1
 */


if ( ! defined( 'PDF_TYPE_NULL' ) ) {
	define ('PDF_TYPE_NULL', 0);
}
if ( ! defined( 'PDF_TYPE_NUMERIC' ) ) {
	define ('PDF_TYPE_NUMERIC', 1);
}
if ( ! defined( 'PDF_TYPE_TOKEN' ) ) {
	define ('PDF_TYPE_TOKEN', 2);
}
if ( ! defined( 'PDF_TYPE_HEX' ) ) {
	define ('PDF_TYPE_HEX', 3);
}
if ( ! defined( 'PDF_TYPE_STRING' ) ) {
	define ('PDF_TYPE_STRING', 4);
}
if ( ! defined( 'PDF_TYPE_DICTIONARY' ) ) {
	define ('PDF_TYPE_DICTIONARY', 5);
}
if ( ! defined( 'PDF_TYPE_ARRAY' ) ) {
	define ('PDF_TYPE_ARRAY', 6);
}
if ( ! defined( 'PDF_TYPE_OBJDEC' ) ) {
	define ('PDF_TYPE_OBJDEC', 7);
}
if ( ! defined( 'PDF_TYPE_OBJREF' ) ) {
	define ('PDF_TYPE_OBJREF', 8);
}
if ( ! defined( 'PDF_TYPE_OBJECT' ) ) {
	define ('PDF_TYPE_OBJECT', 9);
}
if ( ! defined( 'PDF_TYPE_STREAM' ) ) {
	define ('PDF_TYPE_STREAM', 10);
}
if ( ! defined( 'PDF_TYPE_BOOLEAN' ) ) {
	define ('PDF_TYPE_BOOLEAN', 11);
}
if ( ! defined( 'PDF_TYPE_REAL' ) ) {
	define ('PDF_TYPE_REAL', 12);
}

/**
 * @class tcpdi_parser
 * This is a PHP class for parsing PDF documents
 * Based on TCPDF_PARSER, part of the TCPDF project by Nicola Asuni
 * @brief This is a PHP class for parsing PDF documents
 * @version 1.1
 * @author Paul Nicholls - github.com/pauln
 * @author Nicola Asuni - info@tecnick.com
 */
class tcpdi_parser {
	/**
	 * Unique parser ID
	 * @public
	 */
	public $uniqueid = '';

	/**
	 * Raw content of the PDF document
	 * @private
	 */
	private $pdfdata;

	/**
	 * XREF data
	 * @protected
	 */
	protected $xref = [];

	/**
	 * Object streams
	 * @protected
	 */
	protected $objstreams = [];

	/**
	 * Objects in objstreams
	 * @protected
	 */
	protected $objstreamobjs = [];

	/**
	 * List of seen XREF data locations
	 * @protected
	 */
	protected $xref_seen_offsets = [];

	/**
	 * Array of PDF objects
	 * @protected
	 */
	protected $objects = [];

	/**
	 * Array of object offsets
	 * @private
	 */
	private $objoffsets = [];

	/**
	 * Class object for decoding filters
	 * @private
	 */
	private $FilterDecoders;

	/**
	 * Pages
	 *
	 * @private array
	 */
	private $pages;

	/**
	 * Page count
	 * @private integer
	 */
	private $page_count;

	/**
	 * actual page number
	 * @private integer
	 */
	private $pageno;

	/**
	 * PDF version of the loaded document
	 * @private string
	 */
	private $pdfVersion;

	/**
	 * Available BoxTypes
	 *
	 * @public array
	 */
	public $availableBoxes = [ '/MediaBox', '/CropBox', '/BleedBox', '/TrimBox', '/ArtBox' ];

	/**
	 * Array of configuration parameters
	 * @private
	 */
	private $cfg = [
		'die_for_errors' => false,
		'ignore_filter_decoding_errors' => true,
		'ignore_missing_filter_decoders' => true,
	];

// -----------------------------------------------------------------------------

	/**
	 * Parse a PDF document and return an array of objects
	 *
	 * @param $data (string) PDF data to parse
	 *
	 * @public
	 * @throws Exception
	 * @since 1.0.000 (2011-05-24)
	 */
	public function __construct( $data, $uniqueid, $cfg = [] ) {
		if ( empty( $data ) ) {
			$this->Error('PDF data was not read, either because the file doesn\'t exist, is corrupted, or transfer timed out.');
		}

		// find the PDF header starting position
		if ( ( $trimpos = strpos( $data, '%PDF-' ) ) === FALSE ) {
			$this->Error( 'Invalid PDF data: missing %PDF header.' );
		}

		// set configuration parameters
		if (isset($cfg['die_for_errors']) ) {
			$this->cfg['die_for_errors'] = !!$cfg['die_for_errors'];
		}
		if (isset($cfg['ignore_filter_decoding_errors']) ) {
			$this->cfg['ignore_filter_decoding_errors'] = !!$cfg['ignore_filter_decoding_errors'];
		}
		if (isset($cfg['ignore_missing_filter_decoders']) ) {
			$this->cfg['ignore_missing_filter_decoders'] = !!$cfg['ignore_missing_filter_decoders'];
		}
		// get PDF content string
		$this->uniqueid = $uniqueid;

		// get PDF content string
		if ( 0 < $trimpos ) {
		$this->pdfdata = substr( $data, $trimpos );
		} else {
			$this->pdfdata = $data;
		}
		// error_log( '$pdf_data: ' . print_r( str_replace("\x00", "[NULL]", $data ), true ) );

		// initialize class for decoding filters
		$this->FilterDecoders = new TCPDF_FILTERS();
		// get xref and trailer data
		$this->xref = $this->getXrefData();

		$this->findObjectOffsets();

		$this->getPDFVersion();
		// parse all document objects
		$this->objects = [];
		$this->readPages();

	}

	/**
	 *
	 * @return array
	 * @throws Exception
	 */
	public function _getMetaData() {
		return isset( $this->xref['trailer'][1]['/Info'] ) ? $this->getObjectVal( $this->getObjectVal( $this->xref['trailer'][1]['/Info'] ) ) : [];
	}

	/**
	 * Clean up when done, to free memory etc
	 *
	 * @return void
	 */
	public function cleanUp() {
		unset( $this->pdfdata, $this->objstreams, $this->objects, $this->objstreamobjs, $this->xref, $this->objoffsets, $this->pages );
		$this->pdfdata = '';
		$this->objstreams = [];
		$this->objects = [];
		$this->objstreamobjs = [];
		$this->xref = [];
		$this->objoffsets = [];
		$this->pages = [];
	}

	/**
	 * Return an array of parsed PDF document objects
	 *
	 * @return array Array of parsed PDF document objects
	 * @public
	 * @since 1.0.000 (2011-06-26)
	 */
	public function getParsedData() {
		return [ $this->xref, $this->objects, $this->pages ];
	}

	/**
	 * Get PDF-Version
	 *
	 * And reset the PDF Version used in FPDI if needed
	 * @return mixed
	 * @public
	 */
	public function getPDFVersion() {
		preg_match( '/\d\.\d/', substr( $this->pdfdata, 0, 16 ), $m );
		if ( isset( $m[0] ) ) {
			$this->pdfVersion = $m[0];
		}

		return $this->pdfVersion;
	}

	/**
	 * Read all /Page(s)
	 *
	 * @return void
	 * @throws Exception
	 */
	public function readPages() {

		if ($this->xref['trailer'][1]['/Root'][0] != PDF_TYPE_OBJREF) {
			$this->Error('Root element must be indirect reference type. In other words, this PDF\'s syntax is malformed.');
		}

		$objref = null;
		$params = $this->getObjectVal( $this->xref['trailer'][1]['/Root'] );
		if ($params && !empty($params[1]) && is_array($params[1][1]) ) {
			foreach ($params[1][1] as $k=>$v) {
				if ($k == '/Pages') {
					$objref = $v;
					break;
				}
			}
		} else if ( 3 === count( $params ) && $params[0] === PDF_TYPE_OBJREF ) {
			$objref = $params;
		}
		if ( $objref == null || $objref[0] !== PDF_TYPE_OBJREF ) {
			// Offset not found
			return;
		}
		$dict = $this->getObjectVal( $objref );
		if ( $dict[0] == PDF_TYPE_OBJECT && $dict[1][0] == PDF_TYPE_DICTIONARY ) {
			// Dict wrapped in an object
			$dict = $dict[1];
		}
		if ( $dict[0] !== PDF_TYPE_DICTIONARY ) {
			return;
		}
		$this->pages = [];
		// Get down through /Kids into pages
		if ( isset( $dict[1]['/Kids'] ) ) {
			$v = $dict[1]['/Kids'];
			if ( $v[0] == PDF_TYPE_ARRAY ) {
				foreach ( $v[1] as $ref ) {
					$page = $this->getObjectVal( $ref );
					$this->readPage( $page );
				}
			}
		}

		$this->page_count = count( $this->pages );
	}

	/**
	 * Read a single /Page element, recursing through /Kids if necessary
	 *
	 * @return void
	 * @throws Exception
	 */
	private function readPage( $page ) {
		if ( isset( $page[1][1]['/Kids'] ) ) {
			// Nested pages!
			foreach ( $page[1][1]['/Kids'][1] as $subref ) {
				$subpage = $this->getObjectVal( $subref );
				$this->readPage( $subpage );
			}
		} else {
			$this->pages[] = $page;
		}
	}

	/**
	 * Get pagecount from sourcefile
	 *
	 * @return int
	 */
	function getPageCount() {
		return $this->page_count;
	}

	/**
	 * Get Cross-Reference (xref) table and trailer data from PDF document data
	 *
	 * @param int $offset xref offset (if known)
	 * @param array $xref previous xref array (if any)
	 *
	 * @return array containing xref and trailer data
	 * @protected
	 * @throws Exception
	 * @since 1.0.000 (2011-05-24)
	 */
	protected function getXrefData( $offset = 0, $xref = [] ) {
		if ( $offset == 0 ) {
			// find last startxref
			if (preg_match_all('/[\r\n]startxref[\s]*[\r\n]+([0-9]+)[\s]*[\r\n]+%%EOF/i', $this->pdfdata, $matches, PREG_SET_ORDER, $offset) == 0) { // processes 4x5 times faster than following line
				// if ( preg_match('/.*[\r\n]startxref[\s\r\n]+([0-9]+)[\s\r\n]+%%EOF/is', $this->pdfdata, $matches ) == 0 ) {
				$this->Error( 'Unable to find startxref' );
			}
			$matches = array_pop( $matches );
			$startxref = $matches[1];
		} elseif ( strpos( $this->pdfdata, 'xref', $offset ) == $offset ) {
			// Already pointing at the xref table
			$startxref = $offset;
		} elseif ( preg_match( '/([0-9]+[\s][0-9]+[\s]obj)/i', $this->pdfdata, $matches, PREG_OFFSET_CAPTURE, $offset ) ) {
			// Cross-Reference Stream object
			$startxref = $offset;
		} elseif ( preg_match( '/[\r\n]startxref[\s]*[\r\n]+([0-9]+)[\s]*[\r\n]+%%EOF/i', $this->pdfdata, $matches, PREG_OFFSET_CAPTURE, $offset) ) {
			// startxref found
			$startxref = $matches[1][0];
		} else {
			$this->Error( 'Unable to find startxref' );
		}
		unset( $matches );

		// DOMPDF gets the startxref wrong, giving us the linebreak before the xref starts.
		$startxref += strspn( $this->pdfdata, "\r\n", $startxref );

		// check xref position
		if ( strpos( $this->pdfdata, 'xref', $startxref ) == $startxref ) {
			// Cross-Reference
			$xref = $this->decodeXref( $startxref, $xref );
		} else {
			// Cross-Reference Stream
			$xref = $this->decodeXrefStream( $startxref, $xref );
		}
		if ( empty( $xref ) ) {
			$this->Error( 'Unable to find xref' );
		}

		return $xref;
	}

	/**
	 * Decode the Cross-Reference section
	 *
	 * @param int $startxref Offset at which the xref section starts
	 * @param array $xref Previous xref array (if any)
	 *
	 * @return array containing xref and trailer data
	 * @protected
	 * @throws Exception
	 * @since 1.0.000 (2011-06-20)
	 */
	protected function decodeXref( $startxref, $xref = [] ) {
		$this->xref_seen_offsets[] = $startxref;
		if ( ! isset( $xref['xref_location'] ) ) {
			$xref['xref_location'] = $startxref;
			$xref['max_object']    = 0;
		}
		// extract xref data (object indexes and offsets)
		$xoffset = $startxref + 5;
		// initialize object number
		$obj_num = 0;
		$offset  = $xoffset;
		// @todo the [.|\W?] syntax seems wrong. (Originally it was [.?|\W?] which is more wrong.)
		while (preg_match('/^[.|\W?]([0-9]+)[\s]([0-9]+)[\s]?([nf]?)/im', $this->pdfdata, $matches, PREG_OFFSET_CAPTURE, $offset) > 0) {
		// while ( preg_match('/^([0-9]+)[\s]([0-9]+)[\s]?([nf]?)/im', $this->pdfdata, $matches, PREG_OFFSET_CAPTURE, $offset) > 0 ) {
			$offset = ( strlen( $matches[0][0] ) + $matches[0][1] );
			if ( $matches[3][0] === 'n' ) {
				// create unique object index: [object number]_[generation number]
				$gen_num = intval( $matches[2][0] );
				// check if object already exist
				if ( ! isset( $xref['xref'][ $obj_num ][ $gen_num ] ) ) {
					// store object offset position
					$xref['xref'][ $obj_num ][ $gen_num ] = intval( $matches[1][0] );
				}
				++$obj_num;
				$offset += 2;
			} elseif ( $matches[3][0] === 'f' ) {
				++$obj_num;
				$offset += 2;
			} else {
				// object number (index)
				$obj_num = intval( $matches[1][0] );
			}
		}
		unset( $matches );
		$xref['max_object'] = max( $xref['max_object'], $obj_num );
		// get trailer data
		if (preg_match('/trailer[\s]*<<(.*)>>[\s]*[\r\n]+(?:[%].*[\r\n]+)*startxref[\s]*[\r\n]+/isU', $this->pdfdata, $matches, PREG_OFFSET_CAPTURE, $xoffset) > 0) {
			$trailer_data = $matches[1][0];
			if (empty($xref['trailer']) ) {
				// get only the last updated version
				$xref['trailer'] = [];
				$xref['trailer'][0] = PDF_TYPE_DICTIONARY;
				$xref['trailer'][1] = [];
				// parse trailer_data
				if ( preg_match( '/Size[\s]+([0-9]+)/i', $trailer_data, $matches ) > 0 ) {
					$xref['trailer'][1]['/Size'] = [ PDF_TYPE_NUMERIC, intval( $matches[1] ) ];
				}
				if ( preg_match( '/Root[\s]+([0-9]+)[\s]+([0-9]+)[\s]+R/i', $trailer_data, $matches ) > 0 ) {
					$xref['trailer'][1]['/Root'] = [
						PDF_TYPE_OBJREF,
						intval( $matches[1] ),
						intval( $matches[2] )
					];
				}
				if ( preg_match( '/Encrypt[\s]+([0-9]+)[\s]+([0-9]+)[\s]+R/i', $trailer_data, $matches ) > 0 ) {
					$xref['trailer'][1]['/Encrypt'] = [
						PDF_TYPE_OBJREF,
						intval( $matches[1] ),
						intval( $matches[2] )
					];
				}
				if ( preg_match( '/Info[\s]+([0-9]+)[\s]+([0-9]+)[\s]+R/i', $trailer_data, $matches ) > 0 ) {
					$xref['trailer'][1]['/Info'] = [
						PDF_TYPE_OBJREF,
						intval( $matches[1] ),
						intval( $matches[2] )
					];
				}
				if ( preg_match( '/ID[\s]*[\[][\s]*[<]([^>]*)[>][\s]*[<]([^>]*)[>]/i', $trailer_data, $matches ) > 0 ) {
					$xref['trailer'][1]['/ID'] = [PDF_TYPE_ARRAY, []];
					$xref['trailer'][1]['/ID'][1][0] = [ PDF_TYPE_HEX, $matches[1] ];
					$xref['trailer'][1]['/ID'][1][1] = [ PDF_TYPE_HEX, $matches[2] ];
				}
			}
			// incremental PDF
			if ( preg_match( '/Prev[\s]+([0-9]+)/i', $trailer_data, $matches ) > 0 ) {
				// get previous xref
				$prevoffset = intval( $matches[1] );
				if ( ! in_array( $prevoffset, $this->xref_seen_offsets ) ) {
					$this->xref_seen_offsets[] = $prevoffset;
					$xref                      = $this->getXrefData( $prevoffset, $xref );
				}
			}
			unset( $matches );
		} else {
			$this->Error( 'Unable to find PDF trailer' );
		}

		return $xref;
	}

	/**
	 * Decode the Cross-Reference Stream section
	 *
	 * @param int $startxref Offset at which the xref section starts
	 * @param array $xref Previous xref array (if any)
	 *
	 * @return array containing xref and trailer data
	 *
	 * @protected
	 * @throws Exception
	 * @since 1.0.003 (2013-03-16)
	 */
	protected function decodeXrefStream( $startxref, $xref=[] ) {
		// try to read Cross-Reference Stream
		list( $xrefobj, $unused ) = $this->getRawObject( $startxref );
		$xrefcrs = $this->getIndirectObject( $xrefobj[1], $startxref );
		if ( ! isset( $xref['xref_location'] ) ) {
			$xref['xref_location'] = $startxref;
			$xref['max_object'] = 0;
		}
		if ( ! isset( $xref['xref'] ) ) {
			$xref['xref'] = [];
		}
		if ( empty( $xref['trailer'] ) ) {
			// get only the last updated version
			$xref['trailer'] = [];
			$xref['trailer'][0] = PDF_TYPE_DICTIONARY;
			$xref['trailer'][1] = [];
			$filltrailer        = true;
		} else {
			$filltrailer = false;
		}
		$valid_crs = false;
		$sarr      = $xrefcrs[0][1];
		$keys      = array_keys( $sarr );
		$columns   = 1; // Default as per PDF 32000-1:2008.
		$predictor = 1; // Default as per PDF 32000-1:2008.
		foreach ( $keys as $key ) {
			$v = $sarr[ $key ];
			if ( ( $key == '/Type' ) && ( $v[0] == PDF_TYPE_TOKEN && ( $v[1] == 'XRef' ) ) ) {
				$valid_crs = true;
				// } elseif ( ( $key == '/Index' ) && ( $v[0] == PDF_TYPE_ARRAY && count( $v[1] >= 2 ) ) ) {
			} elseif ( ( $key == '/Index' ) && ( $v[0] == PDF_TYPE_ARRAY && count( $v[1] ) >= 2 ) ) {
				// first object number in the subsection
				$index_first = intval( $v[1][0][1] );
				// number of entries in the subsection
				// $index_entries = intval( $v[1][1][1] );
			} elseif ( ( $key == '/Prev' ) && ( $v[0] == PDF_TYPE_NUMERIC ) ) {
				// get previous xref offset
				$prevxref = intval( $v[1] );
			} elseif ( ( $key == '/W' ) && ( $v[0] == PDF_TYPE_ARRAY ) ) {
				// number of bytes (in the decoded stream) of the corresponding field
				$wb = [];
				$wb[0] = intval( $v[1][0][1] );
				$wb[1] = intval( $v[1][1][1] );
				$wb[2] = intval( $v[1][2][1] );
			} elseif ( ( $key == '/DecodeParms' ) && ( $v[0] == PDF_TYPE_DICTIONARY ) ) {
				$decpar = $v[1];
				foreach ( $decpar as $kdc => $vdc ) {
					if ( ( $kdc == '/Columns' ) && ( $vdc[0] == PDF_TYPE_NUMERIC ) ) {
						$columns = intval( $vdc[1] );
					} elseif ( ( $kdc == '/Predictor' ) && ( $vdc[0] == PDF_TYPE_NUMERIC ) ) {
						$predictor = intval( $vdc[1] );
					}
				}
			} elseif ( $filltrailer ) {
				switch ( $key ) {
					case '/Size':
					case '/Root':
					case '/Info':
					case '/ID':
						$xref['trailer'][1][ $key ] = $v;
						break;
					default:
						break;
				}
			}
		}
		// decode data
		$obj_num = 0;
		if ( $valid_crs && isset( $xrefcrs[1][3][0] ) ) {
			// number of bytes in a row
			// $rowlen = ($columns + 1);
			$rowlen = ($columns + (isset($predictor) ? 1 : 0) );
			// convert the stream into an array of integers
			$sdata = unpack( 'C*', $xrefcrs[1][3][0] );
			// split the rows
			$sdata = array_chunk( $sdata, $rowlen );
			// initialize decoded array
			$ddata = [];
			// initialize first row with zeros
			$prev_row = array_fill( 0, $rowlen, 0 );
			// for each row apply PNG unpredictor
			foreach ( $sdata as $k => $row ) {
				// initialize new row
				$ddata[$k] = [];
				// get PNG predictor value
				if ( empty( $predictor ) ) {
					$predictor = ( 10 + $row[0] );
				}
				// for each byte on the row
				for ( $i = 1; $i <= $columns; ++$i ) {
					if ( ! isset( $row[ $i ] ) ) {
						// No more data in this row - we're done here
						break;
					}
					// new index
					$j = ( $i - 1 );
					$row_up = $prev_row[ $j ];
					if ( $i == 1 ) {
						$row_left   = 0;
						$row_upleft = 0;
					} else {
						$row_left   = $row[ ( $i - 1 ) ];
						$row_upleft = $prev_row[ ( $j - 1 ) ];
					}
					switch ( $predictor ) {
						case 1: // No prediction (equivalent to PNG None)
						case 10:
							// PNG prediction (on encoding, PNG None on all rows)
							$ddata[ $k ][ $j ] = $row[ $i ];
							break;
						case 11:
							// PNG prediction (on encoding, PNG Sub on all rows)
							$ddata[ $k ][ $j ] = ( ( $row[ $i ] + $row_left ) & 0xff );
							break;
						case 12:
							// PNG prediction (on encoding, PNG Up on all rows)
							$ddata[ $k ][ $j ] = ( ( $row[ $i ] + $row_up ) & 0xff );
							break;
						case 13:
							// PNG prediction (on encoding, PNG Average on all rows)
							$ddata[ $k ][ $j ] = ( ( $row[ $i ] + ( ( $row_left + $row_up ) / 2 ) ) & 0xff );
							break;
						case 14:
							// PNG prediction (on encoding, PNG Paeth on all rows)
							// initial estimate
							$p = ( $row_left + $row_up - $row_upleft );
							// distances
							$pa   = abs( $p - $row_left );
							$pb   = abs( $p - $row_up );
							$pc   = abs( $p - $row_upleft );
							$pmin = min( $pa, $pb, $pc );
							// return minumum distance
							switch ( $pmin ) {
								case $pa:
									$ddata[ $k ][ $j ] = ( ( $row[ $i ] + $row_left ) & 0xff );
									break;
								case $pb:
									$ddata[ $k ][ $j ] = ( ( $row[ $i ] + $row_up ) & 0xff );
									break;
								case $pc:
									$ddata[ $k ][ $j ] = ( ( $row[ $i ] + $row_upleft ) & 0xff );
									break;
							}
							break;
						default:
							// PNG prediction (on encoding, PNG optimum)
							$this->Error( "Unknown PNG predictor $predictor" );
					}
				}
				$prev_row = $ddata[ $k ];
			} // end for each row
			// complete decoding
			unset( $sdata );
			$sdata = [];
			// for every row
			foreach ( $ddata as $k => $row ) {
				// initialize new row
				$sdata[ $k ] = [0, 0, 0];
				if ( isset($wb[0]) && $wb[0] == 0 ) {
					// default type field
					$sdata[ $k ][0] = 1;
				}
				$i = 0; // count bytes on the row
				// for every column
				for ( $c = 0; $c < 3; ++$c ) {
					// for every byte on the column
					for ( $b = 0; $b < $wb[ $c ]; ++$b ) {
						if ( isset( $row[ $i ] ) ) {
							$sdata[ $k ][ $c ] += ( $row[ $i ] << ( ( $wb[ $c ] - 1 - $b ) * 8 ) );
						}
						++$i;
					}
				}
			}
			unset( $ddata );
			// fill xref
			if ( isset( $index_first ) ) {
				$obj_num = $index_first;
			}
			foreach ( $sdata as $row ) {
				switch ( $row[0] ) {
					case 0:
						// (f) linked list of free objects
						++$obj_num;
						break;
					case 1:
						// (n) objects that are in use but are not compressed
						// check if object already exist
						if ( ! isset( $xref['xref'][ $obj_num ][ $row[2] ] ) ) {
							// store object offset position
							$xref['xref'][ $obj_num ][ $row[2] ] = $row[1];
						}
						++$obj_num;
						break;
					case 2:
						// compressed objects
						// $row[1] = object number of the object stream in which this object is stored
						// $row[2] = index of this object within the object stream
						/*$index = $row[1].'_0_'.$row[2];
						$xref['xref'][$row[1]][0][$row[2]] = -1;*/
						break;
					default:
						// null objects
						break;
				}
			}
		} // end decoding data
		$xref['max_object'] = max( $xref['max_object'], $obj_num );
		if ( isset( $prevxref ) ) {
			// get previous xref
			$xref = $this->getXrefData( $prevxref, $xref );
		}

		return $xref;
	}

	/**
	 * Get raw stream data
	 *
	 * @param int $offset Stream offset
	 * @param int $length Stream length
	 *
	 * @return array Steam content
	 * @protected
	 */
	protected function getRawStream( $offset, $length ) {
		$offset += strspn( $this->pdfdata, "\x00\x09\x0a\x0c\x0d\x20", $offset );
		$offset += 6; // "stream"
		$offset += strspn( $this->pdfdata, "\x20", $offset );
		$offset += strspn( $this->pdfdata, "\r\n", $offset );

		$obj = [];
		$obj[] = PDF_TYPE_STREAM;
		$obj[] = substr( $this->pdfdata, $offset, $length );

		return [ $obj, $offset + $length ];
	}

	/**
	 * Get object type, raw value and offset to next object
	 *
	 * @param $offset (int) Object offset
	 *
	 * @return array containing object type, raw value and offset to next object
	 * @protected
	 * @since 1.0.000 (2011-06-20)
	 */
	protected function getRawObject( $offset = 0, $data = null ) {
		if ( $data == null ) {
			$data =& $this->pdfdata;
		}
		$objtype = ''; // object type to be returned
		$objval  = ''; // object value to be returned

		// skip initial white space (control) chars: \x00 null (NUL), \x09 horizontal tab (HT), \x0A line feed (LF), \x0C form feed (FF), \x0D carriage return (CR), \x20 space (SP)
		$offset += strspn($data, "\x00\x09\x0a\x0c\x0d\x20", $offset);

		// tcpdi_parser can really get stuck here if char is null or otherwise; bail
		if ( ! isset( $data[ $offset ] ) ) {
			return;
		}
		// get first char
		$char = $data[ $offset ];
		// get object type
		switch ( $char ) {
			case '%':
				// \x25 PERCENT SIGN
				// skip comment and search for next token
				$next = strcspn( $data, "\r\n", $offset );
				if ( $next > 0 ) {
					$offset += $next;
					return $this->getRawObject($offset, $data);
				}
				break;
			case '/':
				// \x2F SOLIDUS
				// name object
				$objtype = PDF_TYPE_TOKEN;
				++$offset;
				$length = strcspn( $data, "\x00\x09\x0a\x0c\x0d\x20\x28\x29\x3c\x3e\x5b\x5d\x7b\x7d\x2f\x25", $offset );
				$objval = substr( $data, $offset, $length );
				$offset += $length;
				break;
			case '(':   // \x28 LEFT PARENTHESIS
			case ')':
				// \x29 RIGHT PARENTHESIS
				// literal string object
				$objtype = PDF_TYPE_STRING;
				++$offset;
				$strpos = $offset;
				if ( $char == '(' ) {
					$open_bracket = 1;
					while ( $open_bracket > 0 ) {
						if ( ! isset( $data[ $strpos ] ) ) {
							break;
						}
						$ch = $data[ $strpos ];
						switch ( $ch ) {
							case '\\':
								// REVERSE SOLIDUS (5Ch) (Backslash)
								// skip next character
								++$strpos;
								break;
							case '(':
								// LEFT PARENHESIS (28h)
								++$open_bracket;
								break;
							case ')':
								// RIGHT PARENTHESIS (29h)
								--$open_bracket;
								break;
						}
						++$strpos;
					}
					$objval = substr( $data, $offset, ( $strpos - $offset - 1 ) );
					$offset = $strpos;
				}
				break;
			case '[':   // \x5B LEFT SQUARE BRACKET
			case ']':
				// \x5D RIGHT SQUARE BRACKET
				// array object
				$objtype = PDF_TYPE_ARRAY;
				++$offset;
				if ( '[' === $char ) {
					if ( $data[ $offset + 1 ] === '<' && $data[ $offset + 2 ] === '<' ) { // we have an array of dictionaries
						// get array content
						$objval = [];
						++$offset;
						list( $element, $offset ) = $this->getDictValue( $offset, $data );
						$objval[] = $element;
						// return [[], $offset];
					} else {
						// get array content
						$objval = [];
						do {
							// get element
							list( $element, $offset ) = $this->getRawObject( $offset, $data );
							$objval[] = $element;
						} while ( $element[0] !== ']' );
					}
					// remove closing delimiter
					array_pop( $objval );
				} else {
					$objtype = ']';
				}
				break;
			case '<':   // \x3C LESS-THAN SIGN
			case '>':
				// \x3E GREATER-THAN SIGN
				if ( isset( $data[($offset + 1)]) && ($data[($offset + 1)] === $char ) ) { // >> or <<
					// Dictionary object
					$objtype = PDF_TYPE_DICTIONARY;
					if ('<' === $char) {
						list ( $objval, $offset ) = $this->getDictValue( $offset, $data );
					} else {
						$objtype = '>>';
						$offset  += 2;
					}
				} else if ( '<' === $char && isset($data[$offset + 1]) && '>' === $data[$offset + 1] ) { // <>
					$offset += 2;
				} else {
					// hexadecimal string object
					$objtype = PDF_TYPE_HEX;
					++$offset;
					// The "Panose" entry in the FontDescriptor Style dict seems to have hex bytes separated by spaces.
					if ( ('<' === $char) && (preg_match('/^([0-9A-Fa-f ]+)[>]/iU', substr($data, $offset), $matches) == 1) ) {
						// remove white space characters
						$objval = $matches[1];
						$offset += strlen( $matches[0] );
						unset( $matches );
					} else if ( ( $char == '<' ) && ( $endpos = strpos( $this->pdfdata, '>', $offset ) ) !== FALSE ) {
						$objval = substr( $data, $offset, $endpos - $offset );
						$offset = $endpos + 1;
					}
				}
				break;
			default:
				$frag = @$data[$offset];
				if ( isset($frag) &&
					 isset($data[$offset+1]) &&
					 isset($data[$offset+2]) &&
					 isset($data[$offset+3])
				) {
					$frag = $data[$offset] . $data[$offset+1] . $data[$offset+2] . $data[$offset+3];
				}
				switch ( $frag ) {
					case 'endo':
						// indirect object
						$objtype = 'endobj';
						$offset  += 6;
						break;
					case 'stre':
						// Streams should always be indirect objects, and thus processed by getRawStream().
						// If we get here, treat it as a null object as something has gone wrong.
					case 'null':
						// null object
						$objtype = PDF_TYPE_NULL;
						$offset  += 4;
						$objval  = 'null';
						break;
					case 'true':
						// boolean true object
						$objtype = PDF_TYPE_BOOLEAN;
						$offset  += 4;
						$objval  = true;
						break;
					case 'fals':
						// boolean false object
						$objtype = PDF_TYPE_BOOLEAN;
						$offset  += 5;
						$objval  = false;
						break;
					case 'ends':
					case 'ndst':
						// end stream object
						$objtype = 'endstream';
						$offset  += 9;
						break;
					default:
						if ( preg_match( '/^([0-9]+)[\s]+([0-9]+)[\s]+([Robj]{1,3})/i', substr( $data, $offset, 33 ), $matches ) == 1 ) {
							if ('R' === $matches[3]) {
								// indirect object reference
								$objtype = PDF_TYPE_OBJREF;
								$offset  += strlen( $matches[0] );
								$objval  = [ intval( $matches[1] ), intval( $matches[2] ) ];
							} elseif ( 'obj' === $matches[3] ) {
								// object start
								$objtype = PDF_TYPE_OBJECT;
								$objval  = intval( $matches[1] ) . '_' . intval( $matches[2] );
								$offset  += strlen( $matches[0] );
							}
						} elseif ( ( $numlen = strspn( $data, '+-.0123456789', $offset ) ) > 0 ) {
							// numeric object
							$objval  = substr( $data, $offset, $numlen );
							$objtype = ( intval( $objval ) != $objval ) ? PDF_TYPE_REAL : PDF_TYPE_NUMERIC;
							$offset  += $numlen;
						}
						unset( $matches );
						break;
				}
				break;
		}
		$obj = [];
		$obj[] = $objtype;
		if ( $objtype == PDF_TYPE_OBJREF && is_array( $objval ) ) {
			foreach ( $objval as $val ) {
				$obj[] = $val;
			}
		} else {
			$obj[] = $objval;
		}
		return [$obj, $offset];
	}

	/**
	 * @param $offset
	 * @param $data
	 *
	 * @return array
	 */
	private function getDictValue( $offset, $data ) {
		$objval = [];

		// Extract dict from data
		$i = 2;
		$dict   = '';
		// given $offset is at beginning of << or >>, move forward to goods
		$offset += 2;
		do {
			// >> is end of Dictionary
			if ('>' === $data[$offset] && '>' === $data[$offset+1] ) {
				$i -= 2; // exits do(), esp if dict (<<) ends (>>) before it begins
				$dict   .= '>>';
				$offset += 2;
			} else if ('<' === $data[$offset] && '<' === $data[$offset+1]) {
				$i += 2;
				$dict   .= '<<';
				$offset += 2;
			} else {
				// 2.2.2
				if ('<' === $data[$offset]) {
					$i++;
				} else if ('>' === $data[$offset]) {
					$i--;
				}
				$dict .= $data[ $offset ];
				$offset++;
			}
		} while ( $i > 0 );

		// Now that we have just the dict, parse it.
		$dictoffset = 0;
		do {
			// Get dict element
			list( $key, $eloffset ) = $this->getRawObject( $dictoffset, $dict );
			if ('>>' === $key[0]) {
				break;
			}
			list( $element, $dictoffset ) = $this->getRawObject( $eloffset, $dict );
			$objval[ '/' . $key[1] ] = $element;
			unset( $key, $element );
		} while ( true );

		return [$objval, $offset];
	}

	/**
	 * Get content of indirect object
	 *
	 * @param string $obj_ref Object number and generation number separated by underscore character
	 * @param int $offset Object offset
	 * @param boolean $decoding If true decode
	 * streams
	 *
	 * @return array containing object data
	 * @protected
	 * @throws Exception
	 * @since 1.0.000 (2011-05-24)
	 */
	protected function getIndirectObject( $obj_ref, $offset = 0, $decoding = true ) {
		$obj = explode( '_', $obj_ref );
		if ( empty( $obj ) || count( $obj ) != 2 ) {
			$this->Error( 'Invalid object reference: ' . print_r( $obj, true ) );
		}

		if ( strpos( $this->pdfdata, $obj[0] . ' ' . $obj[1] . ' obj', $offset ) === $offset
			// Some PDFs have line breaks in the object references - yikes! Adjustment for 4.0:
			|| ( strpos( $this->pdfdata, $obj[0] . "\n" . $obj[1] . "\n" . 'obj', $offset ) === $offset )
		) {
			$objref = $obj[0] . ' ' . $obj[1] . ' obj';
		} else {
			return [ 'null', 'null', $offset ];
		}
		// starting position of object content
		$offset += strlen( $objref );
		// get array of object content
		$objdata = [];
		$i = 0; // object main index
		do {
			$oldoffset = $offset;
			if ( $i > 0
				&& isset( $objdata[$i - 1][0] )
				&& $objdata[$i - 1][0] === PDF_TYPE_DICTIONARY
				&& array_key_exists( '/Length', $objdata[ ( $i - 1 ) ][1] )
			) {
				// Stream - get using /Length in stream's dict
				$lengthobj = $objdata[$i - 1][1]['/Length'];
				if ( $lengthobj[0] === PDF_TYPE_OBJREF ) {
					$lengthobj = $this->getObjectVal( $lengthobj );
					if ( $lengthobj[0] === PDF_TYPE_OBJECT ) {
						$lengthobj = $lengthobj[1];
					}
				}
				$streamlength = $lengthobj[1];
				list( $element, $offset ) = $this->getRawStream( $offset, $streamlength );
			} else {
				// get element
				list( $element, $offset ) = $this->getRawObject( $offset );
			}
			// decode stream using stream's dictionary information
			if ( $decoding && ( $element[0] == PDF_TYPE_STREAM ) && ( isset( $objdata[$i - 1][0] ) ) && ( $objdata[$i - 1][0] == PDF_TYPE_DICTIONARY ) ) {
				$element[3] = $this->decodeStream( $objdata[$i - 1][1], $element[1] );
			}
			$objdata[ $i ] = $element;
			++$i;
		} while ($element[0] != 'endobj' && ( $offset != $oldoffset ) );

		$moved = [];
		if ( $objdata[0][0] === PDF_TYPE_ARRAY && empty( $objdata[0][1] ) ) {
			// Maybe only move this all inside the array if there is more than one dictionary object
			if ( $objdata[1][0] && PDF_TYPE_DICTIONARY === $objdata[1][0]
				&& $objdata[2][0] && PDF_TYPE_DICTIONARY === $objdata[2][0] ) {
				for ( $i = 1; $i <= count( $objdata ); ++$i ) {
					if ( ! empty( $objdata[ $i ] ) && PDF_TYPE_DICTIONARY === $objdata[ $i ][0] && ! empty( $objdata[ $i ][1] ) ) {
						$objdata[0][1][] = $objdata[ $i ];
						$moved[] = $i;
					}
					if ( $objdata[ $i ][0] === ']' || $objdata[ $i ][0] === 'endobj' ) {
						unset( $objdata[ $i ] );
					}
				}
				if ( ! empty( $moved ) ) {
					foreach ( $moved as $move ) {
						unset( $objdata[ $move ] );
					}
				}
			} else {
				// remove opening and closing array wrapper
				array_shift( $objdata );
				if ( isset( $objdata[1] ) && $objdata[1][0] === ']' ) {
					unset( $objdata[1] );
				}
				// remove closing /endobj delimiter
				array_pop( $objdata );
			}
		} else {
			// remove closing /endobj delimiter
		array_pop( $objdata );
		}
		// return raw object content
		return $objdata;
	}

	/**
	 * Get the content of object, resolving indirect object reference if necessary
	 *
	 * @param $obj (string) Object value
	 *
	 * @return array containing object data
	 * @public
	 * @throws Exception
	 * @since 1.0.000 (2011-06-26)
	 */
	public function getObjectVal( $obj ) {
		if ( $obj[0] == PDF_TYPE_OBJREF ) { // 8
			if ( strpos( $obj[1], '_' ) !== false ) {
				$key = explode( '_', $obj[1] );
			} else {
				$key = [ $obj[1], $obj[2] ];
			}

			$ret = [ 0 => PDF_TYPE_OBJECT, 'obj' => $key[0], 'gen' => $key[1] ];

			// reference to indirect object
			$object = null;
			if ( isset( $this->objects[ $key[0] ][ $key[1] ] ) ) {
				// this object has been already parsed
				$object = $this->objects[ $key[0] ][ $key[1] ];
			} elseif ( ( $offset = $this->findObjectOffset( $key ) ) !== false ) {
				// parse new object
				$this->objects[ $key[0] ][ $key[1] ] = $this->getIndirectObject( $key[0] . '_' . $key[1], $offset, false );
				$object                              = $this->objects[ $key[0] ][ $key[1] ];
			} elseif ( ( $key[1] == 0 ) && isset( $this->objstreamobjs[ $key[0] ] ) ) {
				// Object is in an object stream
				$streaminfo = $this->objstreamobjs[ $key[0] ];
				$objs       = $streaminfo[0];
				if ( ! isset( $this->objstreams[ $objs[0] ][ $objs[1] ] ) ) {
					// Fetch and decode object stream
					$objstream = $this->getObjectVal( [
						PDF_TYPE_OBJREF,
						$objs[0],
						$objs[1]
					] );
					$decoded = $this->decodeStream( $objstream[1][1], $objstream[2][1] );
					$this->objstreams[ $objs[0] ][ $objs[1] ] = $decoded[0]; // Store just the data, in case we need more from this objstream
					// Free memory
					unset( $objstream, $decoded );
				}
				$this->objects[ $key[0] ][ $key[1] ] = $this->getRawObject( $streaminfo[1], $this->objstreams[ $objs[0] ][ $objs[1] ] );
				$object                              = $this->objects[ $key[0] ][ $key[1] ];
			}
			if ( ! is_null( $object ) && ! empty( $object ) ) {
				$ret[1] = $object[0];
				if ( isset( $object[1][0] ) && $object[1][0] == PDF_TYPE_STREAM ) { // 10
					$ret[0] = PDF_TYPE_STREAM;
					$ret[2] = $object[1];
				}

				return $ret;
			}
		}

		return $obj;
	}

	/**
	 * Extract object stream to find out what it contains
	 *
	 * @param array $key
	 *
	 * @return void
	 * @throws Exception
	 */
	function extractObjectStream( $key ) {
		$objref = [ PDF_TYPE_OBJREF, $key[0], $key[1] ];
		$obj    = $this->getObjectVal( $objref );
		if ( $obj[0] !== PDF_TYPE_STREAM || ! isset( $obj[1][1]['/First'][1] ) ) {
			// Not a valid object stream dictionary - skip it.
			return;
		}
		$stream = $this->decodeStream( $obj[1][1], $obj[2][1] );// Decode object stream, as we need the first bit
		$first  = intval( $obj[1][1]['/First'][1] );
		$ints   = preg_split( '/\s/', substr( $stream[0], 0, $first ) ); // Get list of object / offset pairs
		for ( $j = 1; $j < count( $ints ); $j++ ) {
			if ( ( $j % 2 ) == 1 ) {
				// $ints[$j] doesn't always read as integer unless intval() used
				$this->objstreamobjs[$ints[$j - 1]] = [ $key, intval($ints[$j]) + $first ];
			}
		}

		// Free memory - we may not need this at all.
		unset( $obj, $stream );
	}

	/**
	 * Find all object offsets.  Saves having to scour the file multiple times.
	 * @private
	 * @return void
	 * @throws Exception
	 */
	private function findObjectOffsets() {
		$this->objoffsets = [];
		// match any # of whitespace, then "# # obj"
		if ( preg_match_all( '/(*ANYCRLF)^[\s]*([0-9]+)[\s]+([0-9]+)[\s]+obj/im', $this->pdfdata, $matches, PREG_OFFSET_CAPTURE ) >= 1 ) {
			$i             = 0;
			$laststreamend = 0;
			foreach ( $matches[0] as $match ) {
				/* Formatting correction added to tcpdi-parser, for obj values printing on separate lines
				For example:
				321
				0
				obj
				--- instead of ---
				321 0 obj
				-- have tried: --
				no good: $match[0] = trim( $match[0] );
				no good: $match[0] = preg_replace('/[\n|\r|\r\n]+/', ' ', trim( $match[0] ) );
				no good: $match[0] = preg_replace('/[\n|\r]+/', ' ', $match[0] );
				-- hint --
				It can start or end with spaces and line breaks, but should not have line breaks inside it.
				This issue seems to be growing as AI-generated PDFs arrive on scene?
				credit: https://pdfink.com/
				*/
				$match[0] = preg_replace('/(?<!^)[\n|\r]+/', ' ', $match[0] );

				$offset = $match[1] + strspn( $match[0], "\x00\x09\x0a\x0c\x0d\x20" );
				if ( $offset < $laststreamend ) {
					// Contained within another stream, skip it.
					continue;
				}
				$this->objoffsets[ trim( $match[0] ) ] = $offset;
				$dictoffset                            = $match[1] + strlen( $match[0] );
				$dictfrag                              = substr( $this->pdfdata, $dictoffset, 256 );
				if ( preg_match( '|^\s+<<[^>]+/Length\s+(\d+)|', $dictfrag, $lengthmatch, PREG_OFFSET_CAPTURE ) == 1 ) {
					$laststreamend += intval( $lengthmatch[1][0] );
				}
				if ( preg_match( '|^\s+<<[^>]+/ObjStm|', $dictfrag ) == 1 ) {
					$this->extractObjectStream( [ $matches[1][ $i ][0], $matches[2][ $i ][0] ] );
				}
				$i++;
			}
		}
		unset( $lengthmatch, $dictfrag, $matches );
	}

	/**
	 * Get offset of an object.  Checks xref first, then offsets found by scouring the file.
	 *
	 * @param $key (array) Object key to find (obj, gen).
	 *
	 * @return int Offset of the object in $this->pdfdata.
	 * @private
	 */
	private function findObjectOffset( $key ) {
		$objref = $key[0] . ' ' . $key[1] . ' obj';
		if ( isset( $this->xref['xref'][ $key[0] ][ $key[1] ] ) ) {
			$offset = $this->xref['xref'][ $key[0] ][ $key[1] ];
			if ( strpos( $this->pdfdata, $objref, $offset ) === $offset ) {
				// Offset is in xref table and matches actual position in file
				//echo "Offset in XREF is correct, returning<br>";
				return $this->xref['xref'][ $key[0] ][ $key[1] ];
			}
		}
		if ( array_key_exists( $objref, $this->objoffsets ) ) {
			//echo "Offset found in internal reftable<br>";
			return $this->objoffsets[ $objref ];
		}

		return false;
	}

	/**
	 * Decode the specified stream
	 *
	 * @param $sdic (array) Stream's dictionary array
	 * @param $stream (string) Stream to decode
	 *
	 * @return array containing decoded stream data and remaining filters
	 * @protected
	 * @since 1.0.000 (2011-06-22)
	 * @throws Exception
	 */
	protected function decodeStream( $sdic, $stream ) {
		// get stream length and filters
		$slength = strlen( $stream );
		if ( $slength <= 0 ) {
			return ['', []];
		}
		$filters = [];
		foreach ( $sdic as $k => $v ) {
			if ( $v[0] == PDF_TYPE_NUMERIC && '/Length'===$k ) {
				// get declared stream length
				$declength = intval( $v[1] );
				if ( $declength < $slength ) {
					$stream  = substr( $stream, 0, $declength );
					$slength = $declength;
				}
			} elseif ( $v[0] == PDF_TYPE_TOKEN ) {
				if ('/Filter'===$k) {
					// single filter
					$filters[] = $v[1];
				}
			} elseif ( $v[0] == PDF_TYPE_ARRAY ) {
				// array of filters
				foreach ( $v[1] as $flt ) {
					if ( $flt[0] == PDF_TYPE_TOKEN ) {
						$filters[] = $flt[1];
					}
				}
			}
		}
		// decode the stream
		$remaining_filters = [];
		foreach ( $filters as $filter ) {
			if ( in_array( $filter, $this->FilterDecoders->getAvailableFilters() ) ) {
				try {
					$stream = $this->FilterDecoders->decodeFilter( $filter, $stream );
				} catch (Exception $e) {
					$emsg = $e->getMessage();
					if ( ( ( $emsg[0] == '~' ) && ! $this->cfg['ignore_missing_filter_decoders'] )
						 || ( ( $emsg[0] != '~' ) && ! $this->cfg['ignore_filter_decoding_errors'] ) ) {
						$this->Error( $e->getMessage() );
					}
				}
			} else {
				// add missing filter to array
				$remaining_filters[] = $filter;
			}
		}

		return [ $stream, $remaining_filters ];
	}


	/**
	 * Set page number
	 *
	 * @param int $pageno Pagenumber to use
	 *
	 * @throws Exception
	 */
	public function setPageno( $pageno ) {
		$pageno = ( (int) $pageno ) - 1;

		if ( $pageno < 0 || $pageno >= $this->getPageCount() ) {
			$this->error( "Pagenumber seems wrong. (Requested $pageno, max " . $this->getPageCount() . ")" );
		}

		$this->pageno = $pageno;
	}

	/**
	 * Get page-resources from current page
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getPageResources() {
		return $this->_getPageResources( $this->pages[ $this->pageno ] );
	}

	/**
	 * Get page-resources from /Page
	 *
	 * @param array $obj Array of pdf-data
	 *
	 * @throws Exception
	 */
	private function _getPageResources( $obj ) { // $obj = /Page
		$obj = $this->getObjectVal( $obj );

		// If the current object has a resources dictionary
		// associated with it, we use it. Otherwise, we move
		// back to its parent object.
		if ( isset ( $obj[1][1]['/Resources'] ) ) {
			$res = $obj[1][1]['/Resources'];
			if ( $res[0] == PDF_TYPE_OBJECT ) {
				return $res[1];
			}
			return $res;
		} else {
			if ( ! isset ( $obj[1][1]['/Parent'] ) ) {
				return false;
			} else {
				$res = $this->_getPageResources( $obj[1][1]['/Parent'] );
				if ( $res[0] == PDF_TYPE_OBJECT ) {
					return $res[1];
				}
				return $res;
			}
		}
	}

	/**
	 * Get annotations from current page
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getPageAnnotations() {
		return $this->_getPageAnnotations( $this->pages[ $this->pageno ] );
	}

	/**
	 * Get annotations from /Page
	 *
	 * @param array $obj Array of pdf-data
	 *
	 * @throws Exception
	 */
	private function _getPageAnnotations( $obj ) { // $obj = /Page
		$obj = $this->getObjectVal( $obj );

		if ( ! $obj ) {
			return false;
		}
		$obj = $this->getObjectVal( $obj ); // @todo is this redundant?

		/**
		 * If the current object has an annotations dictionary associated with it,
		 * we use it. Otherwise, we move back to its parent object.
		 */
		if ( isset ( $obj[1][1]['/Annots'] ) ) {
			$annots = $obj[1][1]['/Annots'];
		} else {
			if ( ! isset ( $obj[1][1]['/Parent'] ) ) {
				return false;
			} else {
				$annots = $this->_getPageAnnotations( $obj[1][1]['/Parent'] );
			}
		}
		if ( isset( $annots[0] ) ) {
			if ( $annots[0] === PDF_TYPE_OBJREF ) { // 8
			return $this->getObjectVal( $annots );
			} else if ( $annots[0] === PDF_TYPE_ARRAY && $annots[1][0] === PDF_TYPE_OBJREF ) {
				// Maybe try drilling down
				return $this->getObjectVal( $annots[1][0] );
			}
		}

		return $annots;
	}


	/**
	 * Get content of current page
	 *
	 * If more /Contents is an array, the streams are concated
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getContent() {
		$buffer = '';

		if ( isset( $this->pages[ $this->pageno ][1][1]['/Contents'] ) ) {
			$contents = $this->_getPageContent( $this->pages[ $this->pageno ][1][1]['/Contents'] );
			foreach ( $contents as $tmp_content ) {
				$buffer .= $this->_rebuildContentStream( $tmp_content ) . ' ';
			}
		}

		return $buffer;
	}


	/**
	 * Resolve all content-objects
	 *
	 * @param array $content_ref
	 *
	 * @return array
	 * @throws Exception
	 */
	private function _getPageContent( $content_ref ) {
		$contents = [];

		if ( $content_ref[0] == PDF_TYPE_OBJREF ) {
			$content = $this->getObjectVal( $content_ref );
			if ( $content[1][0] == PDF_TYPE_ARRAY ) {
				$contents = $this->_getPageContent( $content[1] );
			} else {
				$contents[] = $content;
			}
		} elseif ( $content_ref[0] == PDF_TYPE_ARRAY ) {
			foreach ( $content_ref[1] as $tmp_content_ref ) {
				$tmp_contents = $this->_getPageContent($tmp_content_ref);
				$contents = array_merge($contents,$tmp_contents);
			}
		}

		return $contents;
	}


	/**
	 * Rebuild content-streams
	 *
	 * @param array $obj
	 *
	 * @return string
	 * @throws Exception
	 */
	private function _rebuildContentStream( $obj ) {
		$filters = [];

		if ( isset( $obj[1][1]['/Filter'] ) ) {
			$_filter = $obj[1][1]['/Filter'];

			if ( $_filter[0] == PDF_TYPE_OBJREF ) {
				$tmpFilter = $this->getObjectVal( $_filter );
				$_filter   = $tmpFilter[1];
			}

			if ( $_filter[0] == PDF_TYPE_TOKEN ) {
				$filters[] = $_filter;
			} elseif ( $_filter[0] == PDF_TYPE_ARRAY ) {
				$filters = $_filter[1];
			}
		}

		$stream = $obj[2][1];

		foreach ( $filters as $_filter ) {
			$stream = $this->FilterDecoders->decodeFilter( $_filter[1], $stream );
		}

		return $stream;
	}


	/**
	 * Get a Box from a page
	 * Array format is same as used by fpdf_tpl
	 *
	 * @param array $page a /Page
	 * @param string $box_index Type of Box @see $availableBoxes
	 * @param float Scale factor from user space units to points
	 *
	 * @return boolean|array
	 * @throws Exception
	 */
	public function getPageBox( $page, $box_index, $k ) {
		$page = $this->getObjectVal( $page );
		$box  = null;
		if ( isset( $page[1][1][ $box_index ] ) ) {
			$box =& $page[1][1][ $box_index ];
		}

		if ( ! is_null( $box ) && $box[0] == PDF_TYPE_OBJREF ) {
			$tmp_box = $this->getObjectVal( $box );
			$box     = $tmp_box[1];
		}

		if ( ! is_null( $box ) && $box[0] == PDF_TYPE_ARRAY ) {
			$b =& $box[1];

			return [
				'x'   => $b[0][1] / $k,
				'y'   => $b[1][1] / $k,
				'w'   => abs( $b[0][1] - $b[2][1] ) / $k,
				'h'   => abs( $b[1][1] - $b[3][1] ) / $k,
				'llx' => min( $b[0][1], $b[2][1] ) / $k,
				'lly' => min( $b[1][1], $b[3][1] ) / $k,
				'urx' => max( $b[0][1], $b[2][1] ) / $k,
				'ury' => max( $b[1][1], $b[3][1] ) / $k,
			];
		} elseif ( ! isset ( $page[1][1]['/Parent'] ) ) {
			return false;
		} else {
			return $this->getPageBox( $this->getObjectVal( $page[1][1]['/Parent'] ), $box_index, $k );
		}
	}

	/**
	 * Get all page boxes by page no
	 *
	 * @param int The page number
	 * @param float Scale factor from user space units to points
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getPageBoxes( $pageno, $k ) {
		return $this->_getPageBoxes( $this->pages[ $pageno - 1 ], $k );
	}

	/**
	 * Get all boxes from /Page
	 *
	 * @param array a /Page
	 *
	 * @return array
	 * @throws Exception
	 */
	private function _getPageBoxes( $page, $k ) {
		$boxes = [];

		// @todo if all boxes are the same, should we give them all or just stick to CropBox or BleedBox only?
		foreach ( $this->availableBoxes as $box ) {
			if ( $_box = $this->getPageBox( $page, $box, $k ) ) {
				$boxes[ $box ] = $_box;
			}
		}

		return $boxes;
	}

	/**
	 * Get the page rotation by pageno
	 *
	 * @param integer $pageno
	 *
	 * @return array
	 */
	public function getPageRotation( $pageno ) {
		return $this->_getPageRotation( $this->pages[ $pageno - 1 ] );
	}

	private function _getPageRotation( $obj ) { // $obj = /Page
		$obj = $this->getObjectVal( $obj );
		if ( isset ( $obj[1][1]['/Rotate'] ) ) {
			$res = $this->getObjectVal( $obj[1][1]['/Rotate'] );
			if ( $res[0] == PDF_TYPE_OBJECT ) {
				return $res[1];
			}

			return $res;
		} else {
			if ( ! isset ( $obj[1][1]['/Parent'] ) ) {
				return false;
			} else {
				$res = (array) $this->_getPageRotation( $obj[1][1]['/Parent'] );
				if ( $res[0] == PDF_TYPE_OBJECT ) {
					return $res[1];
				}

				return $res;
			}
		}
	}

	/**
	 * This method is automatically called in case of fatal error; it simply outputs the message and halts the execution.
	 *
	 * @param $msg (string) The error message
	 *
	 * @public
	 * @throws Exception
	 * @since 1.0.000 (2011-05-23)
	 */
	public function Error($msg) {
		wwpdf_debug_log( 'TCPDI_PARSER ERROR: ' . $msg, 'error' );
		throw new Exception('TCPDF_PARSER ERROR: '.$msg);
	}

} // END OF tcpdi_parser CLASS