<?php

namespace CanyonWebworks\pdfInkLite\lib\CanyonWebworks;

use Exception;
trait templateHandler {

	/**
	 * @param $tplidx
	 * @param $_x
	 * @param $_y
	 * @param $_w
	 * @param $_h
	 * @param boolean $adjustPageSize
	 *
	 * @return array
	 * @throws Exception
	 * @author Paul Nicholls - https://github.com/pauln/tcpdi
	 * @license LGPL-3.0-or-later
	 */
	public function useTemplate( $tplidx, $_x = null, $_y = null, $_w = 0, $_h = 0, $adjustPageSize = false ): array {

		if ( $adjustPageSize == true && is_null( $_x ) && is_null( $_y ) ) {
			$size = $this->getTemplateSize( $tplidx, $_w, $_h );
			$orientation = $size['w'] > $size['h'] ? 'L' : 'P';
			$size = [ $size['w'], $size['h'] ];
			$this->setPageFormat( $size, $orientation );
		}

		$this->_out( 'q 0 J 1 w 0 j 0 G 0 g' ); // reset standard values
		$size = $this->_useTemplate( $tplidx, $_x, $_y, $_w, $_h );
		$this->_out( 'Q' );
		return $size;

	}

	/**
	 * Use a template in current page or other template
	 *
	 * You can use a template in a page or in another template.
	 * You can give the used template a new size.
	 * All parameters are optional. The width or height is calculated automatically
	 * if one is given. If no parameter is given the origin size as defined in
	 * {@link beginTemplate()} method is used.
	 *
	 * The calculated or used width and height are returned as an array.
	 *
	 * @param int $tplidx A valid template-id
	 * @param int $_x The x-position
	 * @param int $_y The y-position
	 * @param int $_w The new width of the template
	 * @param int $_h The new height of the template
	 * @return array The height and width of the template (array('w' => ..., 'h' => ...))
	 * @throws Exception
	 * @author Jan Slabon - https://github.com/setasign
	 * @license Apache-2.0
	 */
	private function _useTemplate( $tplidx, $_x = null, $_y = null, $_w = 0, $_h = 0 ): array {
		if ( $this->page <= 0 ) {
			$this->Error( 'You have to add a page first!' );
		}

		if ( ! isset( $this->templates[$tplidx] ) ) {
			$this->Error( 'Template does not exist!' );
		}

		$tpl = $this->templates[$tplidx];
		$w = $tpl['w'];
		$h = $tpl['h'];

		if ( $_x == null ) {
			$_x = 0;
		}

		if ( $_y == null ) {
			$_y = 0;
		}

		$_x += $tpl['x'];
		$_y += $tpl['y'];

		$wh = $this->getTemplateSize( $tplidx, $_w, $_h );
		$_w = $wh['w'];
		$_h = $wh['h'];

		$tplData = [
			'x' => $this->x,
			'y' => $this->y,
			'w' => $_w,
			'h' => $_h,
			'scaleX' => ($_w / $w),
			'scaleY' => ($_h / $h),
			'tx' => $_x,
			'ty' =>  ( $this->h - $_y - $_h ),
			'lty' => ( $this->h - $_y - $_h ) - ( $this->h - $h ) * ( $_h / $h )
		];

		$this->_out( sprintf( 'q %.4F 0 0 %.4F %.4F %.4F cm',
			$tplData['scaleX'],
			$tplData['scaleY'],
			$tplData['tx'] * $this->k,
			$tplData['ty'] * $this->k) ); // Translate
		$this->_out( sprintf( '%s%d Do Q', $this->tplPrefix, $tplidx ) );

		return [ 'w' => $_w, 'h' => $_h ];

	}

	/**
	 * Get The calculated Size of a Template
	 *
	 * If one size is given, this method calculates the other one
	 *
	 * @param int $tplidx A valid template-Id
	 * @param int $_w The width of the template
	 * @param int $_h The height of the template
	 * @return boolean|array The height and width of the template (array('w' => ..., 'h' => ...))
	 * @author Jan Slabon - https://github.com/setasign
	 * @license Apache-2.0
	 */
	public function getTemplateSize( $tplidx, $_w = 0, $_h = 0 ) {

		if ( ! isset( $this->templates[ $tplidx ] ) ) {
			return false;
		}

		$tpl = $this->templates[$tplidx];
		$w = $tpl['w'];
		$h = $tpl['h'];

		if ( $_w == 0 and $_h == 0 ) {
			$_w = $w;
			$_h = $h;
		}

		if ( $_w == 0 ) {
			$_w = $_h * $w / $h;
		}
		if ( $_h == 0 ) {
			$_h = $_w * $h / $w;
		}

		return [ "w" => $_w, "h" => $_h ];

	}

	/**
	 * Writes the form xobjects
	 *
	 * @return void
	 * @author Jan Slabon - https://github.com/setasign
	 * @license Apache-2.0
	 */
	public function putFormXObjects(): void {

		$filter = ( $this->compress ) ? '/Filter /FlateDecode ' : '';
		reset( $this->templates );
		foreach( $this->templates as $tplidx => $tpl ) {
			$p=( $this->compress ) ? gzcompress( $tpl['buffer'] ) : $tpl['buffer'];
			$this->_newobj();
			$cN = $this->n; // TCPDF/Protection: rem current "n"

			$this->templates[$tplidx]['n'] = $this->n;
			$this->_out( '<<' . $filter . '/Type /XObject' );
			$this->_out( '/Subtype /Form' );
			$this->_out( '/FormType 1' );

			$this->_out( sprintf( '/BBox [%.2F %.2F %.2F %.2F]',
				( $tpl['box']['llx'] ?? $tpl['x'] ) * $this->k,
				( $tpl['box']['lly'] ?? - $tpl['y'] ) * $this->k,
				( $tpl['box']['urx'] ?? $tpl['w'] + $tpl['x'] ) * $this->k,
				( $tpl['box']['ury'] ?? $tpl['h'] - $tpl['y'] ) * $this->k
			));

			$c = 1;
			$s = 0;
			$tx = 0;
			$ty = 0;

			if ( isset( $tpl['box'] ) ) {
				$tx = -$tpl['box']['llx'];
				$ty = -$tpl['box']['lly'];

				if ( $tpl['_rotationAngle'] <> 0 ) {
					$angle = $tpl['_rotationAngle'] * M_PI/180;
					$c=cos( $angle );
					$s=sin( $angle );

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
			} elseif ( $tpl['x'] != 0 || $tpl['y'] != 0 ) {
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

			$this->_out( '/Resources ' );

			if ( isset($tpl['resources'] ) ) {
				$this->parser =& $tpl['parser'];
				$this->writeValue( $tpl['resources'] ); // "n" will be changed
			} else {
				$this->_out( '<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]' );
				if ( isset( $this->resources['tpl'][$tplidx]['fonts']) && count( $this->resources['tpl'][$tplidx]['fonts'] ) ) {
					$this->_out( '/Font <<' );
					foreach( $this->resources['tpl'][$tplidx]['fonts'] as $font ) {
						$this->_out( '/F' . $font['i'] . ' ' . $font['n'] . ' 0 R' );
					}
					$this->_out( '>>' );
				}
				if ( isset( $this->resources['tpl'][$tplidx]['images']) && count($this->resources['tpl'][$tplidx]['images'] ) ||
				    isset( $this->resources['tpl'][$tplidx]['tpls']) && count($this->resources['tpl'][$tplidx]['tpls'] )
				) {
					$this->_out('/XObject <<');
					if ( isset( $this->resources['tpl'][$tplidx]['images'] ) && count( $this->resources['tpl'][$tplidx]['images'] ) ) {
						foreach( $this->resources['tpl'][$tplidx]['images'] as $image ) {
							$this->_out( '/I' . $image['i'] . ' ' . $image['n'] . ' 0 R' );
						}
					}
					if ( isset( $this->resources['tpl'][$tplidx]['tpls']) && count( $this->resources['tpl'][$tplidx]['tpls'] ) ) {
						foreach( $this->resources['tpl'][$tplidx]['tpls'] as $i => $_tpl ) {
							$this->_out( $this->tplPrefix . $i . ' ' . $_tpl['n'] . ' 0 R' );
						}
					}
					$this->_out( '>>' );
				}
				$this->_out( '>>' );
			}

			$this->_out( '/Group <</Type/Group/S/Transparency>>' );

			$nN = $this->n; // TCPDF: rem new "n"
			$this->n = $cN; // TCPDF: reset to current "n"

			$p = $this->_getrawstream( $p );
			$this->_out( '/Length ' . strlen( $p ) . ' >>' );
			$this->_out( "stream\n" . $p . "\nendstream" );
			$this->_out( 'endobj' );
			$this->n = $nN; // TCPDF: reset to new "n"

		}

		$this->putImportedObjects();

	}

	/**
	 * Extended to add {@link putFormxObjects()} after _putimages()
	 *
	 * @author Jan Slabon - https://github.com/setasign
	 * @license Apache-2.0
	 */
	public function _putimages() {

		parent::_putimages();
		$this->putFormXObjects();

	}

	/**
	 * Rebuilds all needed objects of source files
	 *
	 * @return void
	 * @author Paul Nicholls - https://github.com/pauln/tcpdi
	 * @license LGPL-3.0-or-later
	 */
	private function putImportedObjects(): void {

		if ( isset( $this->objectStack[$this->filename] ) && is_array( $this->objectStack[$this->filename] ) ) {
			while( ( $n = key( $this->objectStack[$this->filename] ) ) !== null ) {
				$nObj = $this->parser->getObjectVal( $this->objectStack[$this->filename][$n][1] );
				$this->_newobj( $this->objectStack[$this->filename][$n][0] );
				if ( $nObj[0] == PDF_TYPE_STREAM ) {
					$this->writeValue( $nObj );
				} else {
					$this->writeValue( $nObj[1] );
				}
				$this->_out( 'endobj' );
				$this->objectStack[$this->filename][$n] = null; // free memory
				unset( $this->objectStack[$this->filename][$n] );
				reset( $this->objectStack[$this->filename] );
			}
		}

	}

}