<?php
/*
Plugin Name: WP Evidence Hub
Plugin URI: https://github.com/fyaconiello/wp_plugin_template
Description: A simple wordpress plugin template
Version: 0.1
Author: Martin Hawksey
Author URI: http://www.yaconiello.com
License: GPL2

Based on Name: WP Plugin Template
Based on Plugin URI: https://github.com/fyaconiello/wp_plugin_template
Based on Version: 1.0
Based on Author: Francis Yaconiello
Based on Author URI: http://www.yaconiello.com
*/
/*
Copyright 2012  Francis Yaconiello  (email : francis@yaconiello.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('EVIDENCE_HUB_VERSION', '0.3');
define('EVIDENCE_HUB_PATH', dirname(__FILE__));
define('EVIDENCE_HUB_URL', plugin_dir_url(__FILE__));
define('EVIDENCE_HUB_REGISTER_FILE', __FILE__);



if(!class_exists('Evidence_Hub'))
{
	class Evidence_Hub
	{
		static $post_types = array(); 
		
		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{
			add_action('init', array(&$this, 'init'));
			
			// Initialize Settings
            require_once(sprintf("%s/settings/settings.php", EVIDENCE_HUB_PATH));
            $Evidence_Hub_Settings = new Evidence_Hub_Settings();
			require_once(sprintf("%s/settings/cache.php", EVIDENCE_HUB_PATH));
			$Evidence_Hub_Settings_Cache = new Evidence_Hub_Settings_Cache();
			
			require_once(sprintf("%s/shortcodes/shortcode.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/evidence_summary.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/evidence_meta.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/evidence_map.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/hypothesis_bars.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/hypothesis_archive.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/hypothesis_sankey.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/evidence_geomap.php", EVIDENCE_HUB_PATH));
			
			// Register custom post types - hypothesis
			require_once(sprintf("%s/post-types/hypothesis.php", EVIDENCE_HUB_PATH));
			$Hypothesis_Template = new Hypothesis_Template();
			
			// Register custom post types - evidence
			require_once(sprintf("%s/post-types/evidence.php", EVIDENCE_HUB_PATH));
			$Evidence_Template = new Evidence_Template();
			
			/*
			// Register custom post types - location
			require_once(sprintf("%s/post-types/location.php", EVIDENCE_HUB_PATH));
			$Location_Template = new Location_Template();
			*/
			
			// Register custom post types - project
			require_once(sprintf("%s/post-types/project.php", EVIDENCE_HUB_PATH));
			$Project_Template = new Project_Template();
			
			// Initialize Pronamics Google Maps distro
			if (!class_exists('Pronamic_Google_Maps_Maps')){
			   require_once(sprintf("%s/lib/pronamic-google-maps/pronamic-google-maps.php", EVIDENCE_HUB_PATH));
			}
			// Initialize JSON API Distro
			if (!class_exists('JSON_API')){
			   require_once(sprintf("%s/lib/json-api/json-api.php", EVIDENCE_HUB_PATH));
			}
			if (!class_exists('Facetious')){
				require_once(sprintf("%s/lib/facetious/facetious.php", EVIDENCE_HUB_PATH));
			}
			

			
			add_filter('json_api_controllers', array(&$this,'add_hub_controller'));
			add_filter('json_api_hub_controller_path', array(&$this,'set_hub_controller_path'));
			add_action('admin_notices', array(&$this, 'admin_notices'));
		   
		   	add_action('admin_enqueue_scripts', array(&$this, 'enqueue_autocomplete_scripts'));
			add_action( 'wp_enqueue_scripts', array(&$this, 'enqueue_front_scripts') );
		   
			add_filter('query_vars', array(&$this, 'evidence_hub_queryvars') );
			add_action('pre_get_posts', array(&$this, 'evidence_hub_query'), 1);
			
			add_action( 'admin_menu', array(&$this,'my_remove_named_menus'),999 );
			
			add_action('wp_ajax_get_sankey_data', array(&$this, 'get_sankey_data'));
			add_action('wp_ajax_nopriv_get_sankey_data', array(&$this, 'get_sankey_data'));

		   //$this->include_files();
		} // END public function __construct
		
		    	/**
    	 * hook into WP's init action hook
    	 */
    	public function init()
    	{	
			
			add_rewrite_rule("^country/([^/]+)/evidece/polarity/([^/]+)/sector/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]&polarity=$matches[2]&sector=$matches[3]&paged=$matches[4]','top');
			add_rewrite_rule("^country/([^/]+)/evidence/polarity/([^/]+)/sector/([^/]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]&polarity=$matches[2]&sector=$matches[3]','top');
			
			add_rewrite_rule("^country/([^/]+)/hypothesis/([0-9]+)/[^/]+/polarity/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]&hyp_id=$matches[2]&polarity=$matches[3]&paged=$matches[4]','top');
			add_rewrite_rule("^country/([^/]+)/hypothesis/([0-9]+)/[^/]+/polarity/([^/]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]&hyp_id=$matches[2]&polarity=$matches[3]','top');			
			add_rewrite_rule("^country/([^/]+)/hypothesis/([0-9]+)/.*?",'index.php?post_type=hypothesis&p=$matches[2]','top');
			
			add_rewrite_rule("^country/([^/]+)/evidence/(polarity|sector)/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]&$matches[2]=$matches[3]&paged=$matches[4]','top');
			add_rewrite_rule("^country/([^/]+)/evidence/(polarity|sector)/([^/]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]&$matches[2]=$matches[3]','top');
			
			add_rewrite_rule("^country/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]&paged=$matches[3]','top');
			add_rewrite_rule("^country/([^/]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]','top');
					
					
			add_rewrite_rule("^evidence/polarity/([^/]+)/sector/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&polarity=$matches[1]&sector=$matches[2]&paged=$matches[3]','top');
			add_rewrite_rule("^evidence/polarity/([^/]+)/sector/([^/]+)?",'index.php?post_type=evidence&polarity=$matches[1]&sector=$matches[2]','top');
			
			add_rewrite_rule("^evidence/(polarity|sector)/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&$matches[1]=$matches[2]&paged=$matches[3]','top');
			add_rewrite_rule("^evidence/(polarity|sector)/([^/]+)?",'index.php?post_type=evidence&$matches[1]=$matches[2]','top');
	
			
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/evidence/polarity/([^/]+)/sector/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&hyp_id=$matches[1]&polarity=$matches[3]&sector=$matches[4]&paged=$matches[5]','top');
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/evidence/polarity/([^/]+)/sector/([^/]+)?",'index.php?post_type=evidence&hyp_id=$matches[1]&polarity=$matches[3]&sector=$matches[4]','top');
			
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/evidence/(polarity|sector)/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&hyp_id=$matches[1]&$matches[3]=$matches[4]&paged=$matches[5]','top');
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/evidence/(polarity|sector)/([^/]+)?",'index.php?post_type=evidence&hyp_id=$matches[1]&$matches[3]=$matches[4]','top');
			
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/evidence/page/([0-9]+)?",'index.php?post_type=evidence&hyp_id=$matches[1]&paged=$matches[2]','top');  
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/evidence/?",'index.php?post_type=evidence&hyp_id=$matches[1]','top');
			  
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/page/([0-9]+)?",'index.php?post_type=hypothesis&p=$matches[1]&paged=$matches[2]','top');
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/?",'index.php?post_type=hypothesis&p=$matches[1]','top');
		}
		
		public function evidence_hub_queryvars( $qvars )
		{
		  $qvars[] = 'hyp_id';
		  $qvars[] = 'polarity';
		  $qvars[] = 'sector';
		  return $qvars;
		}
		
		public function evidence_hub_query($query){
			
			if ( is_admin() || is_search() || isset($query->query_vars['facetious_post_type']))
				return;
			
			if ( is_post_type_archive( 'hypothesis' ) && !isset( $query->query_vars['hyp_id']) ) {
				$query->set( 'orderby', 'title' );
				$query->set( 'order', 'ASC' );
				$query->set( 'posts_per_page', -1 );
				return;
			} 
			
			if( isset( $query->query_vars['hyp_id'] ) || (is_post_type_archive( 'evidence' ) && $query->is_main_query())) {
				$meta_query = array();
				$tax_query = array('relation' => 'AND');
				$querystr = $query->query_vars;
				
				$query->init();
				if (isset( $querystr['hyp_id'] )){
					$meta_query[] = array(
									'key' => 'evidence_hub_hypothesis_id',
									'value' => $querystr['hyp_id'],
									'compare' => '='
									);
									
				}
				if (isset( $querystr['polarity'] ))
					$tax_query[] = array(
									'taxonomy' => 'evidence_hub_polarity',
									'field' => 'slug',
									'terms' => $querystr['polarity'],
									);
				if (isset( $querystr['sector'] ))
					$tax_query[] = array(
									'taxonomy' => 'evidence_hub_sector',
									'field' => 'slug',
									'terms' => $querystr['sector'],
									);
									
				if (isset( $querystr['evidence_hub_country'] ))
					$tax_query[] = array(
									'taxonomy' => 'evidence_hub_country',
									'field' => 'slug',
									'terms' => $querystr['evidence_hub_country'],
									);									
				
				$query->set( 'post_type', $querystr['post_type']);	
				$query->set( 'meta_query' ,$meta_query);
				$query->set( 'tax_query' ,$tax_query);
				$query->set( 'post_status', 'publish');
				$query->parse_query();	
				return;
			}	
		}
		
		function add_hub_controller($controllers) {
		  $controllers[] = 'hub';
		  return $controllers;
		}
		
		
		function set_hub_controller_path() {
		  return sprintf("%s/json/hub.php", EVIDENCE_HUB_PATH);
		}
		
		public function my_remove_named_menus(){
			global $menu;
			foreach ( $menu as $i => $item ) {
				if ( 'pronamic_google_maps' == $item[2] ) {
						unset( $menu[$i] );
						return $item;
				}
	        }
	        return false;
		}
		
		public static function admin_notices() {
			$messages = get_option('evidence_hub_messages', array());
			if (count($messages)) {
				foreach ($messages as $message) { ?>
					<div class="updated">
						<p><?php echo $message; ?></p>
					</div>
				<?php }
				delete_option('evidence_hub_messages');
			}
		}
		
		public static function add_admin_notice($message) {
			$messages = get_option('evidence_hub_messages', array());
			$messages[] = $message;
			update_option('evidence_hub_messages', $messages);
		}
		
		public static function filterOptions($arr, $key, $val){
			$newArr = array();
			foreach($arr as $name => $option) {
				if (array_key_exists($key, $option) && $option[$key]==$val){
					$newArr[$name] = $arr[$name];
				}
			}
			return $newArr;
		}
		
		public function enqueue_autocomplete_scripts() {
			global $typenow;
			global $wp_styles;
			$scripts = array( 'jquery', 'post', 'jquery-ui-autocomplete');
  			if ($typenow=='evidence') {
	  			//wp_enqueue_script( 'pronamic_google_maps_site');
				wp_enqueue_style( 'leafletcss', 'http://cdn.leafletjs.com/leaflet-0.6.4/leaflet.css' );
				wp_enqueue_style( 'leafletcss-ie8', "http://cdn.leafletjs.com/leaflet-0.6.4/leaflet.ie.css", array( 'leafletcss' )  );
    			$wp_styles->add_data( 'leafletcss-ie8', 'conditional', 'IE 8' );
				wp_enqueue_script( 'leafletjs', 'http://cdn.leafletjs.com/leaflet-0.6.4/leaflet.js' );
			} 
			if ($typenow=='location') {
				$scripts[] = 'pronamic_google_maps_admin';
			}
			wp_enqueue_style( 'evidence-hub-autocomplete', plugins_url( 'css/admin.css' , EVIDENCE_HUB_REGISTER_FILE ) );
			wp_enqueue_script( 'evidence-hub-autocomplete', plugins_url( 'js/admin.js' , EVIDENCE_HUB_REGISTER_FILE ), $scripts, '', true );

		}
		
		public function enqueue_front_scripts() {
			wp_enqueue_script( 'd3js', plugins_url( 'lib/map/lib/d3.v3.min.js' , EVIDENCE_HUB_REGISTER_FILE ) );
			
			wp_enqueue_style( 'evidence_hub_style', plugins_url( 'css/style.css' , EVIDENCE_HUB_REGISTER_FILE ) );
		}
		
		public static function get_taxonomy_args($tax_single, $tax_plural){
			$labels = array(
				'name'                => sprintf( _x( '%s', 'taxonomy general name', 'evidence_hub' ), $tax_plural ),
			    'singular_name'       => sprintf( _x( '%s', 'taxonomy singular name', 'evidence_hub' ), $tax_single ),
			    'search_items'        => sprintf( __( 'Search %s', 'evidence_hub' ), $tax_plural ),
			    'all_items'           => sprintf( __( 'All %s', 'evidence_hub' ), $tax_plural ),
			    'parent_item'         => sprintf( __( 'Parent %s', 'evidence_hub' ), $tax_single ),
			    'parent_item_colon'   => sprintf( __( 'Parent %s:', 'evidence_hub' ), $tax_single ),
			    'edit_item'           => sprintf( __( 'Edit %s', 'evidence_hub' ), $tax_single ),
			    'update_item'         => sprintf( __( 'Update %s', 'evidence_hub' ), $tax_single ),
			    'add_new_item'        => sprintf( __( 'Add New %s', 'evidence_hub' ), $tax_single ),
			    'new_item_name'       => sprintf( __( 'New %s Name', 'evidence_hub' ), $tax_single ),
			    'menu_name'           => sprintf( __( '%s', 'evidence_hub' ), $tax_plural )
				
			);
		
			return array(
				'hierarchical'          => false,
				'labels'                => $labels,
				'show_ui'               => true,
				'show_admin_column'     => false,
				'query_var'             => true,
				'rewrite'               => array( 'slug' => strtolower($tax_single)),
			);		
		}
		
		public static function add_meta($post) {
			foreach (get_post_custom($post->ID) as $key => $value) {
				if (strpos($key, 'evidence_hub') !== 0) continue;
				$key = substr($key, 13);
				$post->$key = @unserialize($value[0]) ? @unserialize($value[0]) : $value[0];
			}
			$taxonomies = get_object_taxonomies($post->post_type, 'objects');

			foreach ($taxonomies as $taxonomy_id => $taxonomy) {
				
				if (strpos($taxonomy_id, 'evidence_hub') !== 0) continue;
				$value = wp_get_object_terms($post->ID, $taxonomy_id);
				$taxonomy_id = substr($taxonomy_id, 13);
				$taxonomy_slug = $taxonomy_id."_slug";
				$post->$taxonomy_id = $value[0]->name;
				$post->$taxonomy_slug = $value[0]->slug;
			}
			return $post;
		}
		
		public static function add_terms($posts) {
			$posts_termed = array();
			foreach ($posts as $post_id){
				$post = array();
				$post['ID'] = $post_id;
				foreach (get_post_custom($post_id) as $key => $value) {
					if (strpos($key, 'evidence_hub') !== 0) continue;
					$key = substr($key, 13);
					$post[$key] = @unserialize($value[0]) ? @unserialize($value[0]) : $value[0];
				}
				$taxonomies = get_object_taxonomies(get_post_type($post_id), 'objects');
	
				foreach ($taxonomies as $taxonomy_id => $taxonomy) {
					
					if (strpos($taxonomy_id, 'evidence_hub') !== 0) continue;
					$value = wp_get_object_terms($post_id, $taxonomy_id);
					$taxonomy_id = substr($taxonomy_id, 13);
					$taxonomy_slug = $taxonomy_id."_slug";
					$post[$taxonomy_id] = $value[0]->name;
					$post[$taxonomy_slug] = $value[0]->slug;
				}
				$posts_termed[] = $post;
			}
			return $posts_termed;
		}
		
		public function get_sankey_data(){
			$country_slug = $_POST[ 'country_slug' ];
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
			
			$polarities = get_terms('evidence_hub_polarity');
			$hypotheses = get_posts(array('post_type' => 'hypothesis', // my custom post type
										   'posts_per_page' => -1,
										   'post_status' => 'publish',
										   'orderby' => 'title',
										   'order' => 'ASC',
										   'fields' => 'ids'));
			$sectors = get_terms('evidence_hub_sector');
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
				$hposts = Evidence_Hub::filterOptions($posts, 'hypothesis_id', $hypothesis);
				$hposts_title = get_the_title($hypothesis);
				$base_link = ($country_slug != 'World') ? (site_url().'/country/'.$country_slug) : site_url();
				$nodes[] = array("name" => $hposts_title, "url" => $base_link.'/hypothesis/'.$hypothesis.'/'.basename(get_permalink($hypothesis)), "id" => $hypothesis, "type" => "hypothesis" );
				foreach ($polarities as $polarity){
					$pposts = Evidence_Hub::filterOptions($hposts, 'polarity_slug', $polarity->slug);
					if (empty($nodeList[$polarity->name])){
						$nodes[] = array("name" => $polarity->name, "url" => $base_link."/evidence/polarity/".$polarity->slug, "id" => $polarity->slug, "type" => "polarity", "fill" => json_decode($polarity->description)->fill);
						$nodeList[$polarity->name] = 1;
					}
					if (count($pposts) > 0) 
						$links[] = array("source" => $hposts_title, "target" => $polarity->name, "value" => count($pposts));
					foreach($sectors as $sector){
						$sposts = Evidence_Hub::filterOptions($pposts, 'sector_slug', $sector->slug);
						if (empty($nodeList[$sector->name])){
							$nodes[] = array("name" => $sector->name, "url" => $base_link."/sector/".$sector->slug, "id" => $sector->slug, "type" => "sector", "fill" => json_decode($sector->description)->fill);
							$nodeList[$sector->name] = 1;
						}
						if (count($sposts) > 0) 
							$links[] = array("source" => $polarity->name, "target" => $sector->name, "value" => count($sposts));		
					}
				}
			}	
			$graph = array('nodes' => $nodes, 'links' => $links, 'title' => $title, 'markers' => $markers);
			print_r(json_encode($graph));
			die();
		}
		
	
		public function generate_excerpt($post_id = false) {
			if ($post_id) $post = is_numeric($post_id) ? get_post($post_id) : $post_id;
			else $post = $GLOBALS['post'];
	
			if (!$post) return '';
			if (isset($post->post_excerpt) && !empty($post->post_excerpt)) return $post->post_excerpt;
			if (!isset($post->post_content)) return '';
		
			$content = $raw_content = $post->post_content;
		
			if (!empty($content)) {
				$content = strip_shortcodes($content);
				$content = apply_filters('the_content', $content);
				$content = str_replace(']]>', ']]&gt;', $content);
				$content = strip_tags($content);
	
				$excerpt_length = apply_filters('excerpt_length', 55);
				$words = preg_split("/[\n\r\t ]+/", $content, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
				if (count($words) > $excerpt_length) {
					array_pop($words);
					$content = implode(' ', $words);
					$content .= "...";
				} else $content = implode(' ', $words);
			}
		
			return apply_filters('wp_trim_excerpt', $content, $raw_content);
		}
	
		
		public static function get_select_quick_edit($options, $column_name){
			?><fieldset class="inline-edit-col-right">
                  <div class="inline-edit-group">
                     <label>
                     <select
							name="<?php echo $column_name; ?>"
							id="<?php echo $column_name; ?>"
						>
							<option value=""></option>
                        <span class="title"><?php echo $option['label'];?></span>
                        <?php foreach ($option['options'] as $select) { 
							echo "<option value='" . $select->slug . "'>" . $select->name . "</option>\n";
						}
						?>
                     </label>
                  </div>
               </fieldset><?php
		}
		

		
		
		/**
		 * Activate the plugin
		 */
		public static function activate()
		{
			flush_rewrite_rules();
			update_option( 'Pronamic_Google_maps', array( 'active' => array( 'location' => true, 'evidence' => true, 'project' => true  ) ) );
			// Do nothing
		} // END public static function activate
	
		/**
		 * Deactivate the plugin
		 */		
		public static function deactivate()
		{
			// Do nothing
		} // END public static function deactivate
	} // END class Evidence_Hub
} // END if(!class_exists('Evidence_Hub'))

if(class_exists('Evidence_Hub'))
{
	// Installation and uninstallation hooks
	register_activation_hook(EVIDENCE_HUB_REGISTER_FILE, array('Evidence_Hub', 'activate'));
	register_deactivation_hook(EVIDENCE_HUB_REGISTER_FILE, array('Evidence_Hub', 'deactivate'));

	// instantiate the plugin class
	$wp_plugin_template = new Evidence_Hub();
	
    
}