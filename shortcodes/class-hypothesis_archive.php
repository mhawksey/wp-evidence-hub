<?php
/**
 * Shortcode to display list of hypotheses
 *
 * Shortcode: [hypothesis_archive]
 * Options: do_cache - boolean to disable cache option default: true
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */
 
new Evidence_Hub_Shortcode_Hypothesis_Archive();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Hypothesis_Archive extends Evidence_Hub_Shortcode {
	var $shortcode = 'hypothesis_archive';
	var $defaults = array();

	static $post_types_with_sessions = NULL;
	
	/**
	* Generate post content.
	* Gets all the hypothesis and renders in a single page 
	*
	* @since 0.1.1
	* @return string.
	*/
	function content() {
		ob_start();
		extract($this->options);
		// build a query
		$args=array(
		  'post_type' => 'hypothesis',
		  'post_status' => 'publish',
		  'orderby' => 'title',
		  'order' => 'ASC',
		  'posts_per_page' => -1
		);
		$my_query = null;
		$my_query = new WP_Query($args);
		// do the_loop
		if( $my_query->have_posts() ) {
		  while ($my_query->have_posts()) : $my_query->the_post();
		  	global $more; $more = 0; 
			?>
			<h1 class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h1>
            <div class="entry-summary">
				<?php the_content(); ?>
			</div><!-- .entry-summary -->
            
			<?php
		  endwhile;
		}
		wp_reset_query(); 
		return ob_get_clean();
	}
}