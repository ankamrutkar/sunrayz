<?php
/**
 * The Social customize control extends the WP_Customize_Control class.
 *
 * @package customizer-controls
 */

if ( ! class_exists( 'WP_Customize_Control' ) ) {
	return;
}


/**
 * Class cryptozfree_Control_Social
 *
 * @access public
 */
class cryptozfree_Control_Social extends WP_Customize_Control {
	/**
	 * Control type
	 *
	 * @var string
	 */
	public $type = 'cryptozfree_social_control';

	/**
	 * Additional arguments passed to JS.
	 *
	 * @var array
	 */
	public $default = array();

	/**
	 * Additional arguments passed to JS.
	 *
	 * @var array
	 */
	public $input_attrs = array();

	/**
	 * Send to JS.
	 */
	public function to_json() {
		parent::to_json();
		$this->json['input_attrs'] = $this->input_attrs;
		$this->json['default']     = $this->default;
	}
	/**
	 * Empty Render Function to prevent errors.
	 */
	public function render_content() {
	}
}
$wp_customize->register_control_type( 'cryptozfree_Control_Social' );
