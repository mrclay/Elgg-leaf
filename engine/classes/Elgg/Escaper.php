<?php
namespace Elgg;

/**
 * WARNING: API IN FLUX. DO NOT USE DIRECTLY.
 *
 * Use the elgg_* versions instead.
 *
 * @access private
 *
 * @package    Elgg.Core
 * @since      1.10.0
 */
class Escaper {

	/**
	 * @var \Zend\Escaper\Escaper
	 */
	protected $escaper;

	public function __construct(\Zend\Escaper\Escaper $zend_escaper) {
		$this->escaper = $zend_escaper;
		if ($zend_escaper->getEncoding() !== 'utf-8') {
			throw new \InvalidArgumentException('$zend_escaper must have encoding "utf-8"');
		}
	}

	public function escapeHtml($str) {
		return $this->escaper->escapeHtml($str);
	}

	public function escapeCss($str) {
		return $this->escaper->escapeCss($str);
	}

	public function escapeHtmlAttr($str) {
		return $this->escaper->escapeHtmlAttr($str);
	}

	public function escapeJs($str) {
		return $this->escaper->escapeJs($str);
	}
}
