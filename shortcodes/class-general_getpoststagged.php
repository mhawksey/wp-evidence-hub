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
 
new Evidence_Hub_Shortcode_GetEvidenceTagged();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_GetEvidenceTagged extends Evidence_Hub_Shortcode {

	const SHORTCODE = 'get_evidence_tagged';

	public $defaults = array('tag' => 'featured');
	static $post_types_with_shortcode = array('hypothesis');

	/**
	* Generate post content. 
	*
	* @since 0.1.1
	* @return string.
	*/
	protected function content() {
		ob_start();
		extract($this->options); 
		$id = ($post_id) ? $post_id : get_the_ID();
		$args = array(  'numberposts' => 3,
						'post_type' => 'evidence',
						'tag' => $tag,
						'meta_query' => array(
						   array(
							   'key' => 'evidence_hub_hypothesis_id',
							   'value' => $id, 
							   'compare' => '=',
						   ))
						);
		$the_query = new WP_Query( $args );

		// The Loop
		if ( $the_query->have_posts() ) {
				echo '<ul class="postrank-list">';
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				echo '<li><a href="'.get_permalink().'">' . get_the_title() . '</a></li>';
			}
				echo '</ul>';
		} else {
			echo 'No featured evidence yet';
		}
		/* Restore original Post Data */
		wp_reset_postdata();
		return ob_get_clean();
	} // end of function content

} // end of class