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
 
new Evidence_Hub_Shortcode_EvidenceRatings();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_EvidenceRatings extends Evidence_Hub_Shortcode {
	var $shortcode = 'evidence_ratings';
	public $defaults = array('post_id' => false,
							 'meta_key' => false,
							 'post_type' => 'evidence',
							 'rating_type' => 'ratings_score',
							 'min_votes' => 0,
							 'limit' => 5,
							 'polarity' => false);
	static $post_types_with_shortcode = array('hypothesis');
	/**
	* Generate post content. 
	*
	* @since 0.1.1
	* @return string.
	*/
	function content() {
		ob_start();
		extract($this->options); 
		$id = ($post_id) ? $post_id : get_the_ID();
		if ($polarity){
			$args = array('tax_query' => array(
											array(
												'taxonomy' => 'evidence_hub_polarity',
												'field' => 'slug',
												'terms' => $polarity
											))
						);
		}
		$this->get_highest_rated_by_hyp($id, $post_type, $rating_type, $min_votes, $limit, $args);
		return ob_get_clean();
	} // end of function content
	
	function get_highest_rated_by_hyp($hyp_id = 0, $post_type = 'evidence', $rating_type = 'ratings_score', $min_votes = 0, $limit = 5, $add_args = false) {
		$output = '';
		$args = array( 'posts_per_page' => $limit, 
					   'meta_key' => $rating_type, 
					   'orderby' => 'meta_value_num', 
					   'order' => 'DESC', 
					   'fields' => 'ids',
					   'post_type' => $post_type,
					   'meta_query' => array(
					   						array('key' => 'evidence_hub_hypothesis_id',
													'value' => $hyp_id,
													'compare' => '='
											)),
						);
		if ($add_args){
			$args = array_merge($args, $add_args);	
		}
		$the_query = new WP_Query($args);
		// The Loop
		if ( $the_query->have_posts() ) {
				echo '<ul class="postrank-list">';
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$rating_block = "";
				if ($rating_type =='post_views_count'){
					$rating_block = ' - <span id="postviews">'.$this->eh_get_post_views(get_the_ID()).'</span>';
				} else {
					$rating_block = '<span id="postvoting">'.the_ratings('div', get_the_ID(), false).'</span>';
				}
				echo '<li><a href="'.get_permalink().'">' . get_the_title() . '</a> '.$rating_block.'</li>';
			}
				echo '</ul>';
		} else {
			echo '<em class="postrank-list">No posts found</em>';
		}
		/* Restore original Post Data */
		wp_reset_postdata();	
	}
	
	function eh_get_post_views($postID){
		$count_key = 'post_views_count';
		$count = get_post_meta($postID, $count_key, true);
		if($count==''){
			delete_post_meta($postID, $count_key);
			add_post_meta($postID, $count_key, '0');
			return "0 View";
		}
		return $count.' Views';
	}

} // end of class