<?php
/**
 * API Example
 * 
 * Generates 'try me' form elements for api endpoints
 * Shortcode: [api_demo]
 * Options: api - string of api endpoint
 *			parameters - additional parameters
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */
new Evidence_Hub_Shortcode_API_Example();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_API_Example extends Evidence_Hub_Shortcode {
	var $shortcode = 'api_demo';
	var $defaults = array(
		'api' => false,
		'parameters' => '',
	);
		
	/**
	* Generate post content. 
	*
	* @since 0.1.1
	* @return string.
	*/
	function content() {
		ob_start();
		extract($this->options);
		if (!($api)): 
			$out = 'To use this shortcode specify an API endpoint e.g. [api_demo api="get_evidence"]';
			return $out;
		else :
		?>
        <div class="api_try">Try it:</div>
        <form class="api_try_form" onsubmit="window.location.href = '<?php echo $this->get_api_url( 'hub/' .$api ); ?>' + document.getElementById('param_<?php echo $api;?>').value; return false;"><?php echo ($this->get_api_url( 'hub/' .$api )); ?>
            <input type="text" id="param_<?php echo $api;?>" value="<?php echo $parameters; ?>">
            <input type="submit">
        </form>
		<?php
		endif;
		return ob_get_clean();
	}
}