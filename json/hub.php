<?php
/*
Controller name: Evidence Hub
Controller description: Evidence Hub APIs
*/

class JSON_API_Hub_Controller {
	public function hello_world() {
		return array(
		  "message" => "Hello, world"
		);
	}
	
	public function get_positive(){
		$posts = get_posts(array('post_type' => 'evidence', // my custom post type
    				   'posts_per_page' => -1));
		return $posts;
	}

	
	public function get_reingold_tilford() {
		$args = array('post_type' => 'hypothesis', // my custom post type
    				   'posts_per_page' => -1); // show all posts);
		
		$tree = $this->get_all_post_by_query(array('post_type' => 'hypothesis',
												   'orderby' => 'title', 
												   'order' => 'ASC'
												   ), 'Hypotheses', array('Status' => 'evidence_hub_rag'));
		
		
		foreach ($tree['children'] as $key => $val){
			 $tree['children'][$key]['children'][] = $this->get_all_post_by_query(array('post_type' => 'evidence', 
																	'meta_query' => array(
																		array(
																			'key' => 'evidence_hub_hypothesis_id',
																			'value' => $tree['children'][$key]['id'],
																			'compare' => '='
																		),
																		array(
																			'key' => 'evidence_hub_polarity',
																			'value' => '1',
																			'compare' => '='
																		)
																	)), 'Positive +ve');
																	
			 $tree['children'][$key]['children'][] = $this->get_all_post_by_query(array('post_type' => 'evidence', 
														'meta_query' => array(
															array(
																'key' => 'evidence_hub_hypothesis_id',
																'value' => $tree['children'][$key]['id'],
																'compare' => '='
															),
															array(
																'key' => 'evidence_hub_polarity',
																'value' => '-1',
																'compare' => '='
															)
														)), 'Negative -ve', array('Polarity' => 'evidence_hub_polarity'));
		}

		return $tree;
	}
	
	public function get_all_post_by_query($args,  $name, $postmeta){
		$args = array_merge($args, array('posts_per_page' => -1)); // show all posts);
		$the_query = new WP_Query($args);
		$children = array();
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				
				$meta = array('name' => get_the_title(),
							  'id' => get_the_ID(),
							  'link' => get_permalink(),
							  'excerpt' => get_the_excerpt(),
							  'size' => strlen(get_the_content()) );

				foreach($postmeta as $key => $val){
					$meta[$key] = get_post_meta( get_the_ID(), $val, true  );
				}			
				$children[] = $meta;
			}
		}
		
		if ($name){
			return array(	
					"name" => $name,
					"size" => count($children),
					"children" => $children
				  );
		} else {
			return $children;
		}
	}
}
?>