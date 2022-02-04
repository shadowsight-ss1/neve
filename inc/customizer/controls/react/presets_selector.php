<?php
/**
 * Presets selector control. Handles data passing from args to JS.
 *
 * @package Neve\Customizer\Controls\React
 */

namespace Neve\Customizer\Controls\React;

/**
 * Class Spacing
 *
 * @package Neve\Customizer\Controls\React
 */
class Presets_Selector extends \WP_Customize_Control {

	/**
	 * Control type.
	 *
	 * @var string
	 */
	public $type = 'neve_presets_selector';

	/**
	 * Presets for the control.
	 *
	 * @var array
	 */
	public $presets = [];

	/**
	 * Builder Setting Slug.
	 *
	 * @var string | null
	 */
	public $builder = null;

	/**
	 * Send to JS.
	 */
	public function json() {
		$json            = parent::json();
		$json['presets'] = $this->presets;
		$json['builder'] = $this->builder;
		return $json;
	}
}
