<?php
/**
 * Shortcode to display post meta
 *
 * Shortcode: [get_post_meta]
 * Options: post_id - hypothesis id (defults to current post)
 *			meta_key - string of meta keey value to get
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */

new Evidence_Hub_Shortcode_GetPostMeta();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_GetPostMeta extends Evidence_Hub_Shortcode {

	const SHORTCODE = 'get_post_meta';

	protected $defaults = array('meta_key' => false,
							 'single' => true);
	protected static $post_types_with_shortcode = array('hypothesis');

	/**
	* Generate post content. 
	*
	* @since 0.1.1
	* @return string.
	*/
	protected function content() {
		ob_start();
		extract($this->options); 
		$output;
		if ($meta_key){
			$id = ($post_id) ? $post_id : get_the_ID();
			$output = get_post_meta( $id, 'evidence_hub_'.$meta_key, $single );
			if ($output){
				echo $output;
			} else {
				echo 'No key questions have been identified yet';	
			}
		} else {
			printf( '<p>No key provided in %s</p>', self::SHORTCODE );	
		}
		return ob_get_clean();
	} // end of function content

} // end of class