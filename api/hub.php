<?php
/*
Controller name: Evidence Hub
Controller description: Evidence Hub APIs
*/

class JSON_API_Hub_Controller {
	public function hello_world() {
		return array(
		  "message" => "JSON API for the Evidence Hub"
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
		if ($json_api->query->paged) {
			$query['paged'] = $json_api->query->paged;
		}
		if (isset($args['hypothesis_id']))
			$query['p'] = $args['hypothesis_id']; 
		unset($query['json']);
		unset($query['post_status']);
		$query = array_merge($defaults, $query, $args);
		$the_query = new WP_Query($query);
		foreach ($the_query->posts as $post_id){
				$p = Evidence_Hub::add_meta($post_id);
				$geo = array();
				if ($p->post_type != 'hypothesis'){
					if ($p->post_type === 'evidence'){
						if (!$lat = get_post_meta($post_id, '_pronamic_google_maps_latitude', true ))
							$lat = get_post_meta($p['project_id'], '_pronamic_google_maps_latitude', true );
							
						if (!$long = get_post_meta($post_id, '_pronamic_google_maps_longitude', true ))
							$long = get_post_meta($p['project_id'], '_pronamic_google_maps_longitude', true );
					} else {
						$long = get_post_meta($post_id, '_pronamic_google_maps_longitude', true );
						$lat = get_post_meta($post_id, '_pronamic_google_maps_latitude', true );	
					}
					$geo = array('geometry' => array("type" => "Point", "coordinates" => array((float)$long, (float)$lat)));	
				}
				if (isset($args['include_evidence'])){
					$evidence = $this->get_all_type(array('type'=> 'evidence', 'posts_per_page' => -1, 'meta_query' => array(array('key' => 'evidence_hub_hypothesis_id', 'value' => $post_id+"", 'compare' => '=')),  'exclude_post_result' => 1));
				}
				$output[get_post_type($post_id)][] = array_merge($p,
							array('title' => get_the_title($post_id),
								  'description' => apply_filters('the_content',get_post_field('post_content', $post_id)),
								  'url' => get_permalink($post_id),
								  ), $geo, $evidence);
		}
		wp_reset_query();
		if (isset($args['exclude_post_result'])){
			return array($type => $output);
		}
		return array_merge($this->posts_result($the_query), $output);
	}
	
	public function get_sankey_data(){
		global $json_api;
		$country_slug = isset($json_api->query->country_slug) ? $json_api->query->country_slug : "World";
		$hyp_id = isset($json_api->query->hyp_id) ? $json_api->query->hyp_id : NULL;
		$title = "World";
		$nodes = array();
		$links = array();
		$markers = array();
		$nodesList = array();
		
		$args = array('post_type' => 'evidence', // my custom post type
		   'posts_per_page' => -1,
		   'post_status' => 'publish',
		   'fields' => 'ids'
		   ); // show all posts);
		   
		if ($country_slug != "World"){
			$args = array_merge($args, array('tax_query' => array(array('taxonomy' => 'evidence_hub_country',
										'field' => 'slug',
										'terms' => $country_slug,))));
			$term = get_term_by('slug', $country_slug, 'evidence_hub_country'); 
			$title = $term->name;
		}
		
		$posts = Evidence_Hub::add_terms(get_posts($args));
		
		$args = array( 'post_type' => 'hypothesis', // my custom post type
					   'posts_per_page' => -1,
					   'post_status' => 'publish',
					   'orderby' => 'title',
					   'order' => 'ASC',
					   'fields' => 'ids');
		if ($hyp_id) {
			$args['p'] = $hyp_id;
		}
		
		$polarities = get_terms('evidence_hub_polarity', 'hide_empty=0');
		$hypotheses = get_posts($args);
		$sectors = get_terms('evidence_hub_sector', 'hide_empty=0');
		if ($country_slug != "World"){
			foreach ($posts as $post){
				$markers[] = array("id" => $post['ID'],
								   "name" => get_the_title($post['ID']),
								   "url" => get_permalink($post['ID']),
								   "lat" => get_post_meta($post['ID'], '_pronamic_google_maps_latitude', true ),
								   "lng" => get_post_meta($post['ID'], '_pronamic_google_maps_longitude', true ),
								   "sector" => $post['sector_slug'],
								   "polarity" =>  $post['polarity_slug']);
			}
		}
		
		foreach($hypotheses as $hypothesis){
			$test = $hypothesis;
			$hposts = Evidence_Hub::filterOptions($posts, 'hypothesis_id', $hypothesis);
			$hposts_title = get_the_title($hypothesis);
			$base_link = ($country_slug != 'World') ? (site_url().'/country/'.$country_slug) : site_url();
			$hyp_link = $base_link . '/hypothesis/'.$hypothesis.'/'.basename(get_permalink($hypothesis));
			$nodes[] = array("name" => $hposts_title, "url" => $hyp_link, "id" => $hypothesis, "type" => "hypothesis" );
			foreach ($polarities as $polarity){
				$pposts = Evidence_Hub::filterOptions($hposts, 'polarity_slug', $polarity->slug);
				if (empty($nodeList[$polarity->name])){
					$nodes[] = array("name" => $polarity->name, "url" => $base_link."/evidence/polarity/".$polarity->slug, "id" => $polarity->slug, "type" => "polarity", "fill" => json_decode($polarity->description)->fill);
					$nodeList[$polarity->name] = 1;
				}
				if (count($pposts) > 0) {
					$links[] = array("source" => $hposts_title, "target" => $polarity->name, "value" => count($pposts));
				}
				foreach($sectors as $sector){
					$sposts = Evidence_Hub::filterOptions($pposts, 'sector_slug', $sector->slug);
					if (empty($nodeList[$sector->name])){
						$nodes[] = array("name" => $sector->name, "url" => $base_link."/evidence/sector/".$sector->slug, "id" => $sector->slug, "type" => "sector", "fill" => json_decode($sector->description)->fill);
						$nodeList[$sector->name] = 1;
					}
					if (count($sposts) > 0) {
						$links[] = array("source" => $polarity->name, "target" => $sector->name, "value" => count($sposts));	
					}
				}
			}
		}	
		return array('nodes' => $nodes, 'links' => $links, 'title' => $title, 'markers' => $markers);
	}
	
	public function get_geojson($args=array()){
		global $json_api;
		if ($json_api->query->count) {
		  $args['posts_per_page'] = $json_api->query->count;
		}
		if ($json_api->query->type) {
			$args['type'] = $json_api->query->type;
		}
		if ($json_api->query->paged) {
			$args['paged'] = $json_api->query->paged;
		}
		$posts = $this->get_all_type($args);
		$geoJSON = array();
		if (!empty($posts) && !empty($args['type'])){
			foreach (explode(",", $args['type']) as $type){ 
				foreach ($posts[$type] as $post){
					$property = array("id" => $post['ID'],
									  "type" => $type,
									  "name" => $post['title'],
									  "desc" => Evidence_Hub::generate_excerpt($post['ID']),
									  "url" => $post['url'],
									  // Defensive programming - use isset().
									  "sector" => isset($post['sector_slug']) ? $post['sector_slug'] : NULL,
									  );
					if ($type=='evidence'){
						$property = array_merge($property, array("polarity" => isset($post['polarity_slug']) ? $post['polarity_slug'] : "N/A",
									  "project" => (($post['project_id'] > 0) ? get_the_title($post['project_id']) : "N/A"),
									  "hypothesis_id" => $post['hypothesis_id'],
									  "hypothesis" => (($post['hypothesis_id'] > 0) ? get_the_title($post['hypothesis_id']) : "Unassigned")));
					} elseif ($type=='policy'){
						$property = array_merge($property, array("locale" => isset($post['locale_slug']) ? $post['locale_slug'] : NULL));	
					}
									  
					$geoJSON[] = array("type" => "Feature",
									   "properties" => $property,
									   "geometry" => isset($post['geometry']) ? $post['geometry'] : NULL);						
				}
			}
		}	

		return array('count' => $posts['count'],
					 'count_total' => $posts['count_total'],
					 'pages' => $posts['pages'],
					 'geoJSON' => $geoJSON);
	}
	
	protected function posts_result($query) {
		return array(
		  'count' => (int) $query->post_count,
		  'count_total' => (int) $query->found_posts,
		  'pages' => $query->max_num_pages
		);
	}
	
	public function create_evidence() {
		global $json_api;
		if (!current_user_can('edit_posts')) {
		  $json_api->error("You need to login with a user that has 'edit_posts' capacity.");
		}
		if (!$json_api->query->nonce) {
		  $json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
		}
		$nonce_id = $json_api->get_nonce_id('hub', 'create_evidence');
		if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
		  $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}
		nocache_headers();
		$didwhat = array("type" => $_REQUEST['type']);

		
		$post = new JSON_API_Post();
		$id = $post->create($_REQUEST);

		update_post_meta( $id, '_pronamic_google_maps_map_type', $_REQUEST['_pronamic_google_maps_map_type'] );
		update_post_meta( $id, '_pronamic_google_maps_zoom', $_REQUEST['_pronamic_google_maps_zoom'] );
		update_post_meta( $id, '_pronamic_google_maps_active', $_REQUEST['_pronamic_google_maps_active'] );
		update_post_meta( $id, '_pronamic_google_maps_address', $_REQUEST['_pronamic_google_maps_address'] );
		update_post_meta( $id, '_pronamic_google_maps_latitude', $_REQUEST['_pronamic_google_maps_latitude'] );
		update_post_meta( $id, '_pronamic_google_maps_longitude', $_REQUEST['_pronamic_google_maps_longitude'] );
		
		if (empty($id)) {
		  $json_api->error("Could not create post.");
		}
		
		return array(
		  'post_id' => $id,
		  'edit_link_url' => get_edit_post_link($id, ""),
		  'edit_link_html' => '<a href="'.get_edit_post_link($id, "").'">Click here to edit your submission</a>',
		);
	}
	
	private function create($values = null) {
		unset($values['id']);
		if (empty($values) || empty($values['title'])) {
		  $values = array(
			'title' => 'Untitled',
			'content' => ''
		  );
		}
		return $this->save($values);
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
	
	protected function get_all_post_by_query($args, $name, $postmeta){
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