<?php
/*
Controller name: Evidence Hub
Controller description: Evidence Hub APIs
*/

class JSON_API_Hub_Controller {
	public function hello_world() {
		return array(
		  "message" => "JSON API for OER Evidence Hub"
		);
	}
	public function get_hypothesis() {
		global $json_api;
		$include_evidence = $json_api->query->include_evidence;
		$hypothesis_id = $json_api->query->hypothesis_id;
		return $this->get_all_type(array('type' => 'hypothesis', 'include_evidence' => $include_evidence, 'hypothesis_id' => $hypothesis_id));
	}
	
	public function get_evidence() {
		return $this->get_all_type(array('type' => 'evidence'));
	}
	
	public function get_projects() {
		return $this->get_all_type(array('type' => 'project'));
	}
	
	public function get_all_type($args = array()){
		global $json_api;
		$type = (isset($args['type'])) ? $args['type'] : $json_api->query->type;
		$output = array();
		$evidence = array();
		$uri = (!isset($args['ignore_uri'])) ? '' : $_SERVER['REQUEST_URI'];
		$url = parse_url($uri);
		$defaults = array(
					  'ignore_sticky_posts' => true,
					  'fields' => 'ids'
					);
		$query = wp_parse_args($url['query']);
		$query['post_type'] = explode(",",$type);
		if ($json_api->query->count) {
		  $query['posts_per_page'] = $json_api->query->count;
		}
		if (isset($args['hypothesis_id']))
			$query['p'] = $args['hypothesis_id']; 
		unset($query['json']);
		unset($query['post_status']);
		$query = array_merge($defaults, $query, $args);
		$the_query = new WP_Query($query);
		
		$posts = Evidence_Hub::add_terms($the_query->posts);
		foreach ($posts as $post){
				$p = get_post($post['ID']);
				$geo = array();
				if ($p->post_type === 'evidence' || $p->post_type === 'project'){
					if ($p->post_type === 'evidence'){
						if (!$lat = get_post_meta($post['ID'], '_pronamic_google_maps_latitude', true ))
							$lat = get_post_meta($post['project_id'], '_pronamic_google_maps_latitude', true );
							
						if (!$long = get_post_meta($post['ID'], '_pronamic_google_maps_longitude', true ))
							$long = get_post_meta($post['project_id'], '_pronamic_google_maps_longitude', true );
					} elseif ($p->post_type === 'project'){
						$long = get_post_meta($post['ID'], '_pronamic_google_maps_longitude', true );
						$lat = get_post_meta($post['ID'], '_pronamic_google_maps_latitude', true );	
					}
					$geo = array('geometry' => array("type" => "Point", "coordinates" => array((float)$long, (float)$lat)));	
				}
				if (isset($args['include_evidence'])){
					$evidence = $this->get_all_type(array('type'=> 'evidence', 'posts_per_page' => -1, 'meta_query' => array(array('key' => 'evidence_hub_hypothesis_id', 'value' => $post['ID']+"", 'compare' => '=')),  'exclude_post_result' => 1));
				}
				$output[$p->post_type][] = array_merge($post,
							array('title' => $p->post_title,
								  'description' => apply_filters('the_content',$p->post_content),
								  'url' => get_permalink($post['ID']),
								  ), $geo, $evidence);
		}
		wp_reset_query();
		if (isset($args['exclude_post_result'])){
			return array($type => $output);
		}
		return array_merge($this->posts_result($the_query), $output);
	}
	
	public function get_geojson(){
		$posts = $this->get_all_type(array('type' => 'evidence,project', 'posts_per_page' => '-1'));
		$geoJSON = array();
		if (!empty($posts)){
			
			foreach ($posts['project'] as $post){
				$property = array("type" => "project",
								  "name" => $post['title'],
								  "desc" => Evidence_Hub::generate_excerpt($post['ID']),
								  "url" => $post['url'],
								  // Defensive programming - use isset().
								  "sector" => isset($post['sector_slug']) ? $post['sector_slug'] : NULL,
								  );
								  
				$geoJSON[] = array("type" => "Feature",
								   "properties" => $property,
								   "geometry" => $post['geometry']);
									
			}
			foreach ($posts['evidence'] as $post){
				$property = array("type" => "evidence",
								  "name" => $post['title'],
								  "desc" => Evidence_Hub::generate_excerpt($post['ID']),
								  "url" => $post['url'],
								  // Defensive programming - use isset().
								  "sector" => isset($post['sector_slug']) ? $post['sector_slug'] : NULL,
								  "polarity" => $post['polarity_slug'],
								  "project" => (($post['project_id'] > 0) ? get_the_title($post['project_id']) : "N/A"),
								  "hypothesis_id" => $post['hypothesis_id'],
								  "hypothesis" => (($post['hypothesis_id'] > 0) ? get_the_title($post['hypothesis_id']) : "Unassigned"));
								  
				$geoJSON[] = array("type" => "Feature",
								   "properties" => $property,
								   "geometry" => $post['geometry']);						
			}
		}	

		return array('geoJSON' => $geoJSON);
	}
	
	protected function posts_result($query) {
		return array(
		  'count' => (int) $query->post_count,
		  'count_total' => (int) $query->found_posts,
		  'pages' => $query->max_num_pages
		);
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
																		)),
																	 'tax_query' => array(
																		array(
																			'taxonomy' => 'evidence_hub_polarity',
																			'field' => 'slug',
																			'terms' => 'pos'
																		)
																	)), 'Positive +ve');
																	
			 $tree['children'][$key]['children'][] = $this->get_all_post_by_query(array('post_type' => 'evidence', 
														'meta_query' => array(
															array(
																'key' => 'evidence_hub_hypothesis_id',
																'value' => $tree['children'][$key]['id'],
																'compare' => '='
															)),
														'tax_query' => array(
																		array(
																			'taxonomy' => 'evidence_hub_polarity',
																			'field' => 'slug',
																			'terms' => 'neg'
																		)
														)), 'Negative -ve', array('Polarity' => 'evidence_hub_polarity'));
		}

		return $tree;
	}
	
	protected function get_all_post_by_query($args,  $name, $postmeta){
		$args = array_merge($args, array('posts_per_page' => -1)); // show all posts);
		$the_query = new WP_Query($args);
		$children = array();
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				
				$meta = array('name' => get_the_title(),
							  'id' => get_the_ID(),
							  'link' => get_permalink(),
							  'excerpt' => get_the_excerpt());
							  //'size' => strlen(get_the_content()) );

				foreach($postmeta as $key => $val){
					$meta[$key] = get_post_meta( get_the_ID(), $val, true  );
				}			
				$children[] = $meta;
			}
		}
		
		//if ($name){
			return array(	
					"name" => $name,
					"size" => count($children),
					"children" => $children
				  );
		/*} else {
			return $children;
		}*/
	}
}
?>