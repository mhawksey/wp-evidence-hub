<?php
/**
 * Project Meta Bars Shortcode class used to construct shortcodes
 * 
 * Generates metadata bars for projects
 * Shortcode: [project_meta]
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
new Evidence_Hub_Shortcode_Project_Meta();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Project_Meta extends Evidence_Hub_Shortcode {
	var $shortcode = 'project_meta';
	var $defaults = array(
		'title' => false,
		'location' => false,
		'header_terms' => 'type,country',
		'footer_terms' => 'resource_link',
		'no_evidence_message' => "There is no meta data for this project",
		'title_tag' => 'h4',
	);

	static $post_types_with_shortcode = array('project');
	
	/**
	* Adds shortcode content to named post post types. 
	*
	* @since 0.1.1 
	*/	
	function add_to_page($content) {
		if (in_array(get_post_type(), self::$post_types_with_shortcode)) {
			$content = (($this->defaults['header_terms']) ? do_shortcode('[project_meta location="header"]') : '').$content.(($this->defaults['footer_terms']) ? do_shortcode('[project_meta location="footer"]') : '');
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