<?php
/**
 * Summary of pos/neg hypothesis evidence shortcode
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package WP Evidence Hub
 * @subpackage Evidence_Hub_Shortcode
 */
new Evidence_Hub_Shortcode_HypBar();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_HypBar extends Evidence_Hub_Shortcode {

	const SHORTCODE = 'hypothesis_bar';

	var $defaults = array();

	static $post_types_with_sessions = NULL;

	/**
	* Generate post content. 
	*
	* @since 0.1.1
	* @return string.
	*/
	protected function content() {
		ob_start();
		extract($this->options);
		$errors = array();
		$output = array();

		// get all the evidence and add terms
		$posts = Evidence_Hub::add_terms(get_posts( array (	'post_type' => 'evidence', // my custom post type
									'posts_per_page' => -1,
									'post_status' => 'publish',
									'fields' => 'ids' ))); // show all posts);
		// get all the hypothesis ids													
		$hypotheses = get_posts( array(	'post_type' => 'hypothesis', // my custom post type
						'posts_per_page' => -1,
						'post_status' => 'publish',
						'orderby' => 'title',
						'order' => 'ASC',
						'fields' => 'ids'));
		// for each hypothesis get pos/neg weighting
		foreach($hypotheses as $hypothesis){
			$hposts = Evidence_Hub::filterOptions($posts, 'hypothesis_id', $hypothesis);		
			$output[] = array(	'name' => get_the_title($hypothesis),
						'url' => get_permalink($hypothesis),
						'barNeg' => count(Evidence_Hub::filterOptions($hposts, 'polarity_slug', 'neg')),
						'barPos' => count(Evidence_Hub::filterOptions($hposts, 'polarity_slug', 'pos')));					
		} 
		// dump html ?>
		<script>
			/*global bar_data: false, hyp_w: false, hyp_h: false */
			var bar_data = <?php $this->print_json_data( $output ) ?>,
			hyp_w = document.getElementById("content").offsetWidth,
			hyp_h = parseInt(hyp_w*9/16);
		</script>
		<script src="<?php echo plugins_url( 'js/hyp_bars.js' , EVIDENCE_HUB_REGISTER_FILE );?>"></script>
		<div id="vis"><?php $this->print_chart_loading_no_support_message() ?></div>
        <?php
		return ob_get_clean();
	}
}
