<?php
/**
 * Construct a sankey shortcode
 *
 * There is an option to use the shortcode with different country slugs 
 * Shortcode: [hypothesis_sankey]
 * Options: slug - string (2 character code to display snakey for particular country e.g [hypothesis_sankey slug="us"] for USA 
 *			post_id - hypothesis id defaluts to current post id
 * 
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */
 
new Evidence_Hub_Shortcode_Hypothesis_Sankey();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Hypothesis_Sankey extends Evidence_Hub_Shortcode {
	var $shortcode = 'hypothesis_sankey';
	var $defaults = array('slug' => 'World');

	static $post_types_with_sessions = NULL;
	
	/**
	* Generate post content.
	* Gets all the hypothesis and renders in a single page.
	*
	* @since 0.1.1
	* @return string.
	*/
	function content() {
		ob_start();
		extract($this->options); 
		$hyp_id = "";
		$id = ($post_id) ? $post_id : get_the_ID();
		if (get_post_type($id) === 'hypothesis'){
			$hyp_id = $id;	
		}
		?>
		<script src="<?php echo plugins_url( 'js/sankey.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
        <script src="<?php echo plugins_url( 'js/sankey-main.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
        <div>Evidence flow - <span id="sankey-display-title">World</span></div>
        <div id="sankey-display"></div>
        <script> 
			function getPath(url) {
				var a = document.createElement('a');
				a.href = url;
				return a.pathname.charAt(0) != '/' ? '/' + a.pathname : a.pathname;
			}
			var MyAjax = {
				pluginurl: getPath('<?php echo EVIDENCE_HUB_URL; ?>'),
				apiurl: '<?php $this->print_api_url() ?>',
				ajaxurl: getPath('<?php echo admin_url();?>admin-ajax.php')
			};
			var graph = {};
			var SANKEY_MARGIN = {top: 1, right: 1, bottom: 1, left: 1},
			SANKEY_WIDTH = document.getElementById("sankey-display").offsetWidth,
			SANKEY_HEIGHT = 400;
			
			var svg = d3.select('#sankey-display').append('svg');
			san = svg
				.attr("height" , SANKEY_HEIGHT)
				.attr("preserveAspectRatio", "xMinYMin meet")
		        .attr("viewBox", "0 0 " + SANKEY_WIDTH + " " + SANKEY_HEIGHT )
				.append('g')
				.attr('id', 'sandisplay');
			
			renderSankey('<?php echo $slug ?>', '<?php echo $hyp_id; ?>');
		</script>
        <?php
		return ob_get_clean();
	}
}