<?php

new Evidence_Hub_Shortcode_Hypothesis_Archive();
class Evidence_Hub_Shortcode_Hypothesis_Archive extends Evidence_Hub_Shortcode {
	var $shortcode = 'hypothesis_archive';
	var $defaults = array();

	static $post_types_with_sessions = NULL;

	function content() {
		ob_start();
		extract($this->options);
		$args=array(
		  'post_type' => 'hypothesis',
		  'post_status' => 'publish',
		  'orderby' => 'title',
		  'order' => 'ASC',
		  'posts_per_page' => -1
		);
		$my_query = null;
		$my_query = new WP_Query($args);
		if( $my_query->have_posts() ) {
		  while ($my_query->have_posts()) : $my_query->the_post();
		  	global $more; $more = 0; 
			get_template_part( 'content');
		  endwhile;
		}
		wp_reset_query(); 
		return ob_get_clean();
	}
}