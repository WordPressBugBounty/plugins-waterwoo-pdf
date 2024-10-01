<?php
//
//  FPDF_TPL - Version 1.2.3
//
//  Copyright 2004-2013 Setasign - Jan Slabon
//
//  Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//  You may obtain a copy of the License at
//
//  http://www.apache.org/licenses/LICENSE-2.0
//
//  Unless required by applicable law or agreed to in writing, software
//  distributed under the License is distributed on an "AS IS" BASIS,
//  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//  See the License for the specific language governing permissions and
//  limitations under the License.
//
namespace LittlePackage\lib\tcpdi\pauln\tcpdi;

class FPDF_TPL extends \LittlePackage\lib\tcpdf\TCPDF_Child {

	/**
	 * Array of template data
	 *
	 * @var array
	 */
	protected $tpls = array();

	/**
	 * Current Template-ID
	 *
	 * @var int
	 */
	public $tpl = 0;

	/**
	 * "In Template"-Flag
	 *
	 * @var boolean
	 */
	protected $_intpl = false;

	/**
	 * Name prefix of templates used in Resources dictionary
	 * @var string A String defining the Prefix used as Template-Object-Names. Have to begin with an /
	 */
	public $tplprefix = "/TPL";

	/**
	 * Resources used By Templates and Pages
	 *
	 * @var array
	 */
	protected $_res = array();

	/**
	 * Last used Template data
	 *
	 * @var array
	 */
	public $lastUsedTemplateData = array();

	 /**
	 * Use a template in current page or other template.
	 *
	 * You can use a template in a page or in another template.
	 * You can give the used template a new size.
	 * All parameters are optional. The width or height is calculated automatically
	 * if one is given. If no parameter is given the origin size as defined in
	 * {@link beginTemplate()} method is used.
	 *
	 * The calculated or used width and height are returned as an array.
	 *
	 * @param int $tplIdx A valid template-id
	 * @param int $_x The x-position
	 * @param int $_y The y-position
	 * @param int $_w The new width of the template
	 * @param int $_h The new height of the template
	 * @return array The height and width of the template (array('w' => ..., 'h' => ...))
	 * @throws LogicException|InvalidArgumentException
	 */
	public function useTemplate($tplidx, $_x = null, $_y = null, $_w = 0, $_h = 0) {
		if ($this->page <= 0) {
			$this->Error('You have to add a page first!');
		}

		if (!isset($this->tpls[$tplidx])) {
			$this->Error('Template does not exist!');
		}

		if ($this->_intpl) {
			$this->_res['tpl'][$this->tpl]['tpls'][$tplidx] =& $this->tpls[$tplidx];
		}

		$tpl = $this->tpls[$tplidx];
		$w = $tpl['w'];
		$h = $tpl['h'];

		if ($_x == null) {
			$_x = 0;
		}

		if ($_y == null) {
			$_y = 0;
		}

		$_x += $tpl['x'];
		$_y += $tpl['y'];

		$wh = $this->getTemplateSize($tplidx, $_w, $_h);
		$_w = $wh['w'];
		$_h = $wh['h'];

		$tplData = array(
			'x' => $this->x,
			'y' => $this->y,
			'w' => $_w,
			'h' => $_h,
			'scaleX' => ($_w / $w),
			'scaleY' => ($_h / $h),
			'tx' => $_x,
			'ty' =>  ($this->h - $_y - $_h),
			'lty' => ($this->h - $_y - $_h) - ($this->h - $h) * ($_h / $h)
		);

		$this->_out(sprintf('q %.4F 0 0 %.4F %.4F %.4F cm',
			$tplData['scaleX'], $tplData['scaleY'], $tplData['tx'] * $this->k, $tplData['ty'] * $this->k)
		); // Translate
		$this->_out(sprintf('%s%d Do Q', $this->tplprefix, $tplidx));

		$this->lastUsedTemplateData = $tplData;

		return array('w' => $_w, 'h' => $_h);
	}

	/**
	 * Get The calculated Size of a Template
	 *
	 * If one size is given, this method calculates the other one.
	 *
	 * @param int $tplidx A valid template-Id
	 * @param int $_w The width of the template
	 * @param int $_h The height of the template
	 * @return array The height and width of the template (array('w' => ..., 'h' => ...))
	 */
	public function getTemplateSize($tplidx, $_w = 0, $_h = 0) {
		if (!isset($this->tpls[$tplidx]))
			return false;

		$tpl = $this->tpls[$tplidx];
		$w = $tpl['w'];
		$h = $tpl['h'];

		if ($_w == 0 and $_h == 0) {
			$_w = $w;
			$_h = $h;
		}

		if($_w == 0)
			$_w = $_h * $w / $h;
		if($_h == 0)
			$_h = $_w * $h / $w;

		return array("w" => $_w, "h" => $_h);
	}

	/**
	 * Private Method that writes the form xobjects
	 */
	protected function _putformxobjects() {
		$filter=($this->compress) ? '/Filter /FlateDecode ' : '';
		reset($this->tpls);

		foreach($this->tpls AS $tplidx => $tpl) {
			$this->_newobj();
			$this->tpls[$tplidx]['n'] = $this->n;
			$this->_out('<<'.$filter.'/Type /XObject');
			$this->_out('/Subtype /Form');
			$this->_out('/FormType 1');
			$this->_out(sprintf('/BBox [%.2F %.2F %.2F %.2F]',
				// llx
				$tpl['x'] * $this->k,
				// lly
				-$tpl['y'] * $this->k,
				// urx
				($tpl['w'] + $tpl['x']) * $this->k,
				// ury
				($tpl['h'] - $tpl['y']) * $this->k
			));

			if ($tpl['x'] != 0 || $tpl['y'] != 0) {
				$this->_out(sprintf('/Matrix [1 0 0 1 %.5F %.5F]',
					 -$tpl['x'] * $this->k * 2, $tpl['y'] * $this->k * 2
				));
			}

			$this->_out('/Resources ');
			$this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');

			if (isset($this->_res['tpl'][$tplidx])) {
				$res = $this->_res['tpl'][$tplidx];
				if (isset($res['fonts']) && count($res['fonts'])) {
					$this->_out('/Font <<');

					foreach($res['fonts'] as $font) {
						$this->_out('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
					}

					$this->_out('>>');
				}

				if(isset($res['images']) || isset($res['tpls'])) {
					$this->_out('/XObject <<');

					if (isset($res['images'])) {
						foreach($res['images'] as $image)
							$this->_out('/I' . $image['i'] . ' ' . $image['n'] . ' 0 R');
					}

					if (isset($res['tpls'])) {
						foreach($res['tpls'] as $i => $_tpl)
							$this->_out($this->tplprefix . $i . ' ' . $_tpl['n'] . ' 0 R');
					}

					$this->_out('>>');
				}
			}

			$this->_out('>>');

			$buffer = ($this->compress) ? gzcompress($tpl['buffer']) : $tpl['buffer'];
			$this->_out('/Length ' . strlen($buffer) . ' >>');
			$this->_putstream($buffer);
			$this->_out('endobj');
		}
	}

	/**
	 * Output images.
	 *
	 * Overwritten to add {@link _putformxobjects()} after _putimages().
	 */
	public function _putimages() {
		parent::_putimages();
		$this->_putformxobjects();
	}

	/**
	 * Writes the references of XObject resources to the document.
	 *
	 * Overwritten to add the the templates to the XObject resource dictionary.
	 */
	public function _putxobjectdict() {
		parent::_putxobjectdict();

		foreach($this->tpls as $tplidx => $tpl) {
			$this->_out(sprintf('%s%d %d 0 R', $this->tplprefix, $tplidx, $tpl['n']));
		}
	}

	/**
	 * Writes bytes to the resulting document.
	 *
	 * Overwritten to delegate the data to the template buffer.
	 *
	 * @param string $s
	 */
	public function _out($s) {
		if ($this->state == 2 && $this->_intpl) {
			$this->tpls[$this->tpl]['buffer'] .= $s . "\n";
		} else {
			parent::_out($s);
		}
	}
}