<?php
/**
 * Dummy Shortcode to intercept hypothesis details page
 *
 * Shortcode: [hypothesis_summary]
 * Options: do_cache - boolean to disable cache option default: true
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */
 
new Evidence_Hub_Shortcode_Hypothesis_Summary();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Hypothesis_Summary extends Evidence_Hub_Shortcode {
	var $shortcode = 'hypothesis_summary';
	public $defaults = array(
		'title' => false,
		'display_sankey' => true,
		'no_evidence_message' => "There is no evidence yet for this hypothesis",
		'title_tag' => 'h3',
	);

	static $post_types_with_shortcode = array('hypothesis');
	
	/**
	* Adds shortcode content to named post post types. 
	*
	* @since 0.1.1 
	*/
	function add_to_page($content) {
		if (in_array(get_post_type(), self::$post_types_with_shortcode)) {
			if (is_single()) {
				$included_page = get_page( 1525 ); 
				$content = $included_page->post_content;
			} 
		}
		return $content;
	}

	function content(){
	}

} // end of class