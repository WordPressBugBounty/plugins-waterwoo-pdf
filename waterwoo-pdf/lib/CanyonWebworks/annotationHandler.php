<?php

namespace CanyonWebworks\pdfInkLite\lib\CanyonWebworks;

use Exception;

trait annotationHandler {

	/**
	 * @param int $pageno
	 * @return void
	 * @author Paul Nicholls - https://github.com/pauln
	 * @license LGPL-3.0-or-later
	 */
	public function importAnnotations( $pageno ): void {

		$this->parser->setPageno( $pageno );
		if ( ! $annots = $this->parser->getPageAnnotations() ) {
			return;
		}
		if ( is_array( $annots ) ) {
			if ( $annots[0] == PDF_TYPE_OBJECT // We got an object (9)
				&& isset( $annots[1] ) && is_array( $annots[1] ) && $annots[1][0] == PDF_TYPE_ARRAY // It's an array (6)
				&& isset( $annots[1][1] ) && is_array( $annots[1][1] )
				&& count( $annots[1][1] ) > 0  // It's not empty - there are annotations for this page
			) {

				$this->importedAnnots[ $pageno ] = [];
				if ( ! isset( $this->objectStack[$this->filename] ) ) {
					$this->objectStack[$this->filename] = [];
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
				if ( ! isset( $this->objectStack[$this->filename] ) ) {
					$this->objectStack[$this->filename] = [];
				}
				$this->importedAnnots[$this->page] = [];
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
	 * @author Paul Nicholls - https://github.com/pauln
	 * @license LGPL-3.0-or-later
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

		if ( ! isset( $this->doneObjectStack[$this->filename][$old_id] ) ) {
			$this->_newobj(false, true);
			$this->objectStack[$this->filename][$old_id] = [$this->n, $value];
			$this->doneObjectStack[$this->filename][$old_id] = [$this->n, $value];
		}
		$objid = $this->doneObjectStack[$this->filename][$old_id][0];
		$this->importedAnnots[$pageno][] = $objid;

	}

	/**
	 * Get references to page annotations
	 * @param int $pageno page number
	 * @return string
	 * @protected
	 * @author Nicola Asuni
	 * @since 5.0.010 (2010-05-17)
	 */
	protected function _getannotsrefs( $pageno ): string {

		if ( ! empty( $this->numTocPages ) && $pageno >= $this->tocPageNum ) {
			// Offset page number to account for TOC being inserted before page containing annotations
			$pageno -= $this->numTocPages;
		}
		if ( !
		( isset($this->importedAnnots[$pageno] )
			|| isset( $this->PageAnnots[$pageno] )
			|| ( $this->sign && isset( $this->signature_data['cert_type'] ) )
		)
		) {
			return '';
		}
		$out = ' /Annots [';
		if ( isset( $this->importedAnnots[ $pageno ] ) && ! empty( $this->importedAnnots[ $pageno ] ) ) {
			foreach ( $this->importedAnnots[ $pageno ] as $val ) {
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
			$out .= ' ' . $this->sig_obj_id.' 0 R';
		}
		if ( ! empty( $this->empty_signature_appearance ) ) {
			foreach ( $this->empty_signature_appearance as $esa ) {
				if ( $esa['page'] == $pageno ) {
					// set reference for empty signature objects
					$out .= ' ' . $esa['objid'] . ' 0 R';
				}
			}
		}
		$out .= ' ]';
		return $out;

	}

	/**
	 * Check for and add /GoTo (URL) destinations to dictionary
	 * in case they were found during parsing
	 *
	 * Modified to use isTypeToken()/isTypeDictionary()/isTypeArray() for type checks
	 *
	 * @param array|mixed $value
	 * @return void
	 * @author Gerhard Potgieter http://gerhardpotgieter.com/
	 * @author Canyon Webworks https://github.com/canyonwebworks
	 * @license GPL-3.0
	 */
	private function getGoToDest( &$value ) {

		// Must be a dictionary-value array with second element as dict
		if ( ! is_array( $value ) || ! isset( $value[1] ) || ! is_array( $value[1] ) ) {
			return;
		}

		$dict = &$value[1];

		// Must be a Link annotation with an /A action
		if ( ! $this->isTypeToken( $dict, '/Subtype', 'Link' )
			|| ! isset( $dict['/A'] ) ) {
			return;
		}

		// Resolve the action object (handle indirect references)
		$action = $this->normalizeObject( $this->parser->getObjectVal( $dict['/A'] ) );
		if ( ! $action || ! $this->isTypeDictionary( $action ) ) {
			return;
		}

		$action_dict = $action[1];

		// Action must be a GoTo with a /D (destination)
		if ( ! $this->isTypeToken( $action_dict, '/S', 'GoTo' )
			 || ! isset( $action_dict['/D'] ) ) {
			return;
		}

		// Skip if somehow already resolved
		if ( $this->isTypeArray( $action_dict['/D'] ) ) {
			return;
		}

		// Resolve and replace the named destination
		$resolved_dest = $this->resolveNamedDestination( $action_dict['/D'] );
		if ( $this->isTypeArray( $resolved_dest ) ) {
			$action_dict['/D'] = $resolved_dest;
			$dict['/A'] = [ PDF_TYPE_DICTIONARY, $action_dict ];
		}

	}

	/**
	 * Resolve a named destination value to a destination array
	 *
	 * Supports:
	 * - Catalog /Dests dictionary (legacy)
	 * - Catalog /Names -> /Dests name tree
	 *
	 * @param array $name_value Destination name value (string/hex/token or reference)
	 *
	 * @return array|null Resolved destination array value or null if unresolved
	 * @author Gerhard Potgieter http://gerhardpotgieter.com/
	 * @license GPL-3.0
	 */
	public function resolveNamedDestination( $name_value ) {

		$cacheKey = $this->getNamedDestinationCacheKey( $name_value );
		if ( array_key_exists( $cacheKey, $this->namedDestinationCache ) ) {
			return $this->namedDestinationCache[ $cacheKey ];
		}

		$catalog = $this->getCatalogDictionary();
		if ( ! $catalog ) {
			$this->namedDestinationCache[ $cacheKey ] = null;
			return null;
		}

		// Legacy /Dests dictionary on catalog
		if ( isset( $catalog['/Dests'] ) ) {
			$dest = $this->resolveDestinationFromDictionary( $catalog['/Dests'], $name_value );
			if ( $dest ) {
				$this->namedDestinationCache[ $cacheKey ] = $dest;
				return $dest;
			}
		}

		// Name tree at /Names /Dests
		if ( isset( $catalog['/Names'] ) ) {
			$names = $this->resolveValue( $catalog['/Names'] );
			if ( is_array( $names ) && $names[0] === PDF_TYPE_DICTIONARY && isset( $names[1]['/Dests'] ) ) {
				$dest = $this->resolveDestinationFromNameTree( $names[1]['/Dests'], $name_value );
				$this->namedDestinationCache[ $cacheKey ] = $dest;
				return $dest;
			}
		}
		$this->namedDestinationCache[ $cacheKey ] = null;
		return null;

	}

	/**
	 * Build stable cache key for a parsed /D destination name value.
	 *
	 * @param array $name_value
	 *
	 * @return string
	 * @author Gerhard Potgieter http://gerhardpotgieter.com/
	 * @license GPL-3.0
	 */
	private function getNamedDestinationCacheKey( $name_value ) {

		if ( ! is_array( $name_value ) || ! isset( $name_value[0] ) ) {
			return 'u:' . md5( serialize( $name_value ) );
		}

		$type = (string) $name_value[0];
		$raw = isset( $name_value[1] ) ? (string) $name_value[1] : '';

		return $type . ':' . md5( $raw );

	}

	/**
	 * Resolve a parsed value, unwrapping object references/containers
	 *
	 * @param array $value Parsed PDF value
	 * @return array
	 * @throws Exception
	 */
	private function resolveValue( $value ) {

		return $this->normalizeObject( $this->parser->getObjectVal( $value ) );

	}

	/**
	 * Handle indirect object references
	 *
	 * @param $obj
	 *
	 * @return mixed
	 */
	public function normalizeObject( $obj ) {

		if ( is_array( $obj ) && isset( $obj[0] ) && $obj[0] === PDF_TYPE_OBJECT ) {
			return $obj[1];
		}
		return $obj;

	}

	/**
	 * Get catalog dictionary from trailer root
	 *
	 * @return array|null
	 * @author Gerhard Potgieter http://gerhardpotgieter.com/
	 * @license GPL-3.0
	 */
	private function getCatalogDictionary() {

		if ( $this->catalogDictionaryCacheInitialized ) {
			return $this->catalogDictionaryCache;
		}

		$this->catalogDictionaryCacheInitialized = true;

		if ( ! isset( $this->parser->xref['trailer'][1]['/Root'] ) ) {
			$this->catalogDictionaryCache = null;
			return null;
		}

		$root = $this->resolveValue( $this->parser->xref['trailer'][1]['/Root'] );
		if ( is_array( $root ) && isset( $root[0] ) && $root[0] === PDF_TYPE_DICTIONARY ) {
			$this->catalogDictionaryCache = $root[1];
			return $root[1];
		}

		$this->catalogDictionaryCache = null;
		return null;

	}

	/**
	 * Resolve named destination from a /Dests dictionary
	 *
	 * @param array $dests_value Parsed dictionary or reference to dictionary
	 * @param array $name_value Destination name value
	 *
	 * @return array|null
	 * @author Gerhard Potgieter http://gerhardpotgieter.com/
	 * @license GPL-3.0
	 */
	private function resolveDestinationFromDictionary( $dests_value, $name_value ) {

		$dests = $this->resolveValue( $dests_value );

		if ( ! is_array( $dests ) || $dests[0] !== PDF_TYPE_DICTIONARY ) {
			return null;
		}

		foreach ( $dests[1] as $key => $dest_value ) {
			$key_value = [ PDF_TYPE_TOKEN, ltrim( $key, '/' ) ];
			if ( $this->destinationNameMatches( $key_value, $name_value ) ) {
				return $this->normalizeDestinationValue( $dest_value );
			}
		}

		return null;

	}

	/**
	 * Resolve named destination by traversing a /Names tree
	 *
	 * @param array $tree_value Parsed names tree dictionary or reference
	 * @param array $name_value Destination name value
	 *
	 * @return array|null
	 * @author Gerhard Potgieter http://gerhardpotgieter.com/
	 * @license GPL-3.0
	 */
	private function resolveDestinationFromNameTree( $tree_value, $name_value ) {

		$tree = $this->resolveValue( $tree_value );

		if ( ! is_array( $tree ) || $tree[0] !== PDF_TYPE_DICTIONARY ) {
			return null;
		}

		$dict = $tree[1];
		if ( isset( $dict['/Names'] ) ) {
			$names = $this->resolveValue( $dict['/Names'] );
			if ( is_array( $names ) && $names[0] === PDF_TYPE_ARRAY ) {
				$items = $names[1];
				$count = count( $items );
				for ( $i = 0; $i + 1 < $count; $i += 2 ) {
					$candidate_name = $items[$i];
					$candidate_dest = $items[$i + 1];
					if ( $this->destinationNameMatches( $candidate_name, $name_value ) ) {
						return $this->normalizeDestinationValue( $candidate_dest );
					}
				}
			}
		}

		if ( isset( $dict['/Kids'] ) ) {
			$kids = $this->resolveValue( $dict['/Kids'] );
			if ( is_array( $kids ) && $kids[0] === PDF_TYPE_ARRAY ) {
				foreach ( $kids[1] as $kid ) {
					$dest = $this->resolveDestinationFromNameTree( $kid, $name_value );
					if ( $dest ) {
						return $dest;
					}
				}
			}
		}
		return null;

	}

	/**
	 * Normalize a destination entry to a destination array.
	 *
	 * @param array $dest_value Parsed destination value.
	 *
	 * @return array|null
	 * @author Gerhard Potgieter http://gerhardpotgieter.com/
	 * @license GPL-3.0
	 */
	private function normalizeDestinationValue( $dest_value ) {

		$dest = $this->resolveValue( $dest_value );

		if ( ! is_array( $dest ) ) {
			return null;
		}

		if ( $dest[0] === PDF_TYPE_ARRAY ) {
			return $dest;
		}

		if ( $dest[0] === PDF_TYPE_DICTIONARY && isset( $dest[1]['/D'] ) ) {
			$nested = $this->resolveValue( $dest[1]['/D'] );
			if ( is_array( $nested ) && $nested[0] === PDF_TYPE_ARRAY ) {
				return $nested;
			}
		}

		return null;
	}

	/**
	 * Compare two destination name values across token/string/hex encodings.
	 *
	 * @param array $a
	 * @param array $b
	 *
	 * @return bool
	 * @author Gerhard Potgieter http://gerhardpotgieter.com/
	 * @license GPL-3.0
	 */
	private function destinationNameMatches( $a, $b ) {
		$vals_a = $this->destinationNameCandidates( $a );
		$vals_b = $this->destinationNameCandidates( $b );

		foreach ( $vals_a as $va ) {
			foreach ( $vals_b as $vb ) {
				if ( $va !== '' && $va === $vb ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Return comparable string candidates for a destination-name value.
	 *
	 * @param array $value
	 *
	 * @return array
	 * @author Gerhard Potgieter http://gerhardpotgieter.com/
	 * @license GPL-3.0
	 */
	private function destinationNameCandidates( $value ) {

		$value = $this->resolveValue( $value );

		if ( ! is_array( $value ) || ! isset( $value[0], $value[1] ) ) {
			return [];
		}

		if ( $value[0] === PDF_TYPE_STRING || $value[0] === PDF_TYPE_TOKEN ) {
			return [ (string) $value[1] ];
		}

		if ( $value[0] === PDF_TYPE_HEX ) {
			$hex = preg_replace( '/[^0-9A-Fa-f]/', '', (string) $value[1] );
			$vals = [ $hex ];
			if ( $hex !== '' && ( strlen( $hex ) % 2 ) === 0 && ctype_xdigit( $hex ) ) {
				$decoded = @pack( 'H*', $hex );
				if ( is_string( $decoded ) ) {
					$vals[] = $decoded;
				}
			}
			return $vals;
		}

		return [];
	}


}