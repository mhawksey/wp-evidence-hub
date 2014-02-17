<?php
/**
 * Evidence Meta Bars Shortcode class used to construct shortcodes
 *
 * Generates metadata bars for evidence
 * Shortcode: [evidence_meta]
 * Options: title - boolean|string
 *			location - header|footer|false
 *			header_terms - comma seperated list of fields to display
 *			footer_terms -  comma seperated list of fields to display
 *			no_evidence_message - message used on error
 *			title_tag - tag to wrap title in
 *			do_cache - boolean to disable cache option default: true
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */
 
new Evidence_Hub_Shortcode_Evidence_Meta();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Evidence_Meta extends Evidence_Hub_Shortcode {
	var $shortcode = 'evidence_meta';
	var $defaults = array(
		'title' => false,
		'location' => false,
		'header_terms' => 'type,hypothesis_id,polarity,sector,country',
		'footer_terms' => 'citation',
		'no_evidence_message' => "There is no meta data for this evidence",
		'title_tag' => 'h4',
	);
	
	static $post_types_with_shortcode = array('evidence');
	
	/**
	* Adds shortcode content to named post post types. 
	*
	* @since 0.1.1 
	*/	
	function add_to_page($content) {
		if (in_array(get_post_type(), self::$post_types_with_shortcode)) {
			$content = do_shortcode('[evidence_meta location="header"]').$content.do_shortcode('[evidence_meta location="footer"]');
			if (function_exists('the_ratings')){
				$content .= '<div id="postvoting">'.the_ratings('div', get_the_ID(), false).'</div>';
			}
		}
		return $content;
	}

	/**
	* Generate post content. 
	*
	* @since 0.1.1
	* @return string.
	*/
	function content() {
		return $this->make_meta_bar(self::$post_types_with_shortcode);
	}
}