<?php
/*
Plugin Name: WP Evidence Hub
Plugin URI: https://github.com/mhawksey/wp-evidence-hub
Description: Plugin to capture and visualise evidence around a set of hypotheses.
Version: 0.1.1
Author: Martin Hawksey
Author URI: http://mashe.hawksey.info
License: GPL2

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

define('EVIDENCE_HUB_VERSION', '0.1.1');
define('EVIDENCE_HUB_PATH', dirname(__FILE__));
// Handle symbolic links - code portability.
define('EVIDENCE_HUB_URL', plugin_dir_url(preg_replace('@\/var\/www\/[^\/]+@', '', __FILE__)));
define('EVIDENCE_HUB_REGISTER_FILE', preg_replace('@\/var\/www\/[^\/]+@', '', __FILE__));

if(!class_exists('Evidence_Hub'))
{
	class Evidence_Hub {
		static $post_types = array(); // used in shortcode caching
		static $post_type_fields = array(); // used to collect field types for frontend data entry 
		/**
		* Construct the plugin object.
		*
		* @since 0.1.1
		*/
		public function __construct() {
			if (!class_exists('Symbolic_Press')){
				require_once(sprintf("%s/lib/class-symbolic-press.php", EVIDENCE_HUB_PATH));
				new Symbolic_Press(__FILE__);
			}
			
			add_action('init', array(&$this, 'init'));
			// Register custom post types - hypothesis
			require_once(sprintf("%s/post-types/class-custom_post_type.php", EVIDENCE_HUB_PATH));
			// Register custom post types - hypothesis
			require_once(sprintf("%s/post-types/class-hypothesis.php", EVIDENCE_HUB_PATH));
			// Register custom post types - evidence
			require_once(sprintf("%s/post-types/class-evidence.php", EVIDENCE_HUB_PATH));		
			// Register custom post types - project
			require_once(sprintf("%s/post-types/class-project.php", EVIDENCE_HUB_PATH));
			// Register custom post types - policy
			require_once(sprintf("%s/post-types/class-policy.php", EVIDENCE_HUB_PATH));
			
			// include shortcodes
			require_once(sprintf("%s/shortcodes/class-shortcode.php", EVIDENCE_HUB_PATH));
			
			require_once(sprintf("%s/shortcodes/class-evidence_entry.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-evidence_geomap.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-evidence_map.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-evidence_meta.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-evidence_summary.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-hypothesis_archive.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-hypothesis_bars.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-hypothesis_sankey.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-policy_geomap.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-policy_meta.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-project_meta.php", EVIDENCE_HUB_PATH));
			
			// Initialize Pronamics Google Maps library
			if (!class_exists('Pronamic_Google_Maps_Maps')){
			   require_once(sprintf("%s/lib/pronamic-google-maps/pronamic-google-maps.php", EVIDENCE_HUB_PATH));
			}
			// Initialize JSON API library
			if (!class_exists('JSON_API')){
			   require_once(sprintf("%s/lib/json-api/json-api.php", EVIDENCE_HUB_PATH));
			}
			// add custom JSON API controllers
			add_filter('json_api_controllers', array(&$this,'add_hub_controller'));
			add_filter('json_api_hub_controller_path', array(&$this,'set_hub_controller_path'));
			
			// Initialize Facetious library
			if (!class_exists('Facetious')){
				require_once(sprintf("%s/lib/facetious/facetious.php", EVIDENCE_HUB_PATH));
			}
			
			// Initialize Settings pages in wp-admin
            require_once(sprintf("%s/settings/settings.php", EVIDENCE_HUB_PATH)); //TODO Tidy
            $Evidence_Hub_Settings = new Evidence_Hub_Settings();
			require_once(sprintf("%s/settings/cache.php", EVIDENCE_HUB_PATH)); //TODO Tidy
			$Evidence_Hub_Settings_Cache = new Evidence_Hub_Settings_Cache();
			
			// register custom query handling
			add_filter('query_vars', array(&$this, 'evidence_hub_queryvars') );
			add_action('pre_get_posts', array(&$this, 'evidence_hub_query'), 1);
			
			add_action('admin_notices', array(&$this, 'admin_notices'));
		   	add_action('admin_enqueue_scripts', array(&$this, 'enqueue_autocomplete_scripts'));
			add_action('wp_enqueue_scripts', array(&$this, 'enqueue_front_scripts') );

			// removed library plugin menus
			add_action( 'admin_menu', array(&$this,'my_remove_named_menus'),999 );
			
			// open ajax for project autom complete
			add_action('wp_ajax_evidence_hub_project_callback', array(&$this, 'ajax_evidence_hub_project_callback') );
			add_action('wp_ajax_evidence_hub_if_project_exists_by_value', array(&$this, 'ajax_evidence_hub_if_project_exists_by_value') );
			
			// debug function
			add_action( 'wp_head', array(&$this, 'show_current_query') );

		} // END public function __construct
		
		/**
    	* Debug function to check wp_query. Add ?q to url to use.
		*
		* @since 0.1.1
    	*/
		public function show_current_query() {
			global $wp_query;
			if ( !isset( $_GET['q'] ) )
				return;
			echo '<textarea cols="50" rows="10">';
			print_r( $wp_query );
			echo '</textarea>';
		}
		
		/**
    	* Hook into WP's init action hook.
		*
		* @since 0.1.1
    	*/
    	public function init() {	
			$caps = array('evidence_read_post', 'evidence_delete_posts', 'evidence_edit_posts');
			
			// define new role of Evidence Contributor to create but not publish/edit their evidence
			global $wp_roles;
			if ( ! isset( $wp_roles ) ){
				$wp_roles = new WP_Roles();
			}
			$adm = $wp_roles->get_role('subscriber');
			$wp_roles->add_role('evidence_contributor', 'Evidence Contributor', $adm->capabilities);
			$contributor = get_role('evidence_contributor');
			$this->add_evidence_capability($contributor, $caps);
			
			// add capability to authors to create publish their evidence
			$this->add_evidence_capability(get_role('author'), $caps);
			
			// add capability to editors to create publish evidence
			$caps[] = 'evidence_admin';
			$this->add_evidence_capability(get_role('editor'), $caps);
			
			// add capability to admin account to let only them them modify/add hypotheses
			$caps[] = 'hypothesis_admin';
			$this->add_evidence_capability(get_role('administrator'), $caps);
			
			// add permalink rewrites
			$this->do_rewrites();
			
			// install contry codes/terms
			$countries = get_terms( 'evidence_hub_country', array( 'hide_empty' => false ) );
			// if no terms then lets add our terms
			if( empty( $countries ) ){
				$countries = $this->set_countries();
				foreach( $countries as $country_code => $country_name ){
					if( !term_exists( $country_name, 'evidence_hub_country' ) ){
						wp_insert_term( $country_name, 'evidence_hub_country', array( 'slug' => $country_code ) );
					}
				}
			}
			
			// register custom post type taxonomies
			// register post type taxonomies for hypothesis
			$args = $this->get_taxonomy_args("RAG Status","RAG Status");
			register_taxonomy( 'evidence_hub_rag', 'hypothesis', $args );
			
			// register post type taxonomies for sector
			$args = $this->get_taxonomy_args("Sector","Sectors");
			register_taxonomy( 'evidence_hub_sector', array('evidence','policy'), $args );
			
			// register post type taxonomies for polarity
			$args = $this->get_taxonomy_args("Polarity","Polarity");
			register_taxonomy( 'evidence_hub_polarity', 'evidence', $args );
			
			// register post type taxonomies for country
			$args = Evidence_Hub::get_taxonomy_args("Country", "Countries");
			register_taxonomy( 'evidence_hub_country', array('evidence', 'project', 'policy'), $args );
			
			// register post type taxonomies for locale
			$args = Evidence_Hub::get_taxonomy_args("Locale","Locales");
			register_taxonomy( 'evidence_hub_locale', 'policy', $args );
			
			Pronamic_Google_Maps_Site::bootstrap();		
		}
		
		/**
    	* Add extra capabilities to WP roles.
		*
		* @since 0.1.1
		* @param object $role WP Role object.
		* @param array $caps string array of capabilitiy identifiers.
    	*/
		public function add_evidence_capability($role, $caps) {
			foreach($caps as $cap){
				$role->add_cap($cap);
			}
		}
		
		/**
    	* Register custom querystring variables.
		*
		* @since 0.1.1
		* @param array $qvars WP qvars.
		* @return array $qvars.
    	*/
		public function evidence_hub_queryvars( $qvars ) {
		  $qvars[] = 'hyp_id';
		  return $qvars;
		}
		
		/**
    	* Handle custom querystring for hyp_id.
		*
		* @since 0.1.1
		* @param array $query WP_query.
		* @return array $query.
    	*/
		public function evidence_hub_query($query) {	
			if (isset( $query->query_vars['hyp_id']) ) {
				$meta_query = array();
				$meta_query[] = array(
									'key' => 'evidence_hub_hypothesis_id',
									'value' => $query->query_vars['hyp_id'],
									'compare' => '='
									);
				$query->set( 'meta_query' ,$meta_query);
				return;
			} 
		}
		
		/**
    	* Register controllers for custom JSON_API end points.
		*
		* @since 0.1.1
		* @param object $controllers JSON_API.
		* @return object $controllers.
    	*/
		public function add_hub_controller($controllers) {
		  $controllers[] = 'hub';
		  return $controllers;
		}
		
		/**
    	* Register controllers define path custom JSON_API end points.
		*
		* @since 0.1.1
    	*/
		public function set_hub_controller_path() {
		  return sprintf("%s/json/hub.php", EVIDENCE_HUB_PATH);
		}
		
		/**
    	* Remove Pronamic Google Map Library wp-admin menu option.
		*
		* @since 0.1.1
    	*/
		public function my_remove_named_menus() {
			global $menu;
			foreach ( $menu as $i => $item ) {
				if ( 'pronamic_google_maps' == $item[2] ) {
						unset( $menu[$i] );
						return $item;
				}
	        }
	        return false;
		}
		
		/**
    	* Handle custom admin notices.
		*
		* @since 0.1.1
    	*/
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
		
		/**
    	* Handle custom admin notices - push message for display.
		*
		* @since 0.1.1
		* @param string $message.
    	*/
		public static function add_admin_notice($message) {
			$messages = get_option('evidence_hub_messages', array());
			$messages[] = $message;
			update_option('evidence_hub_messages', $messages);
		}
		
		/**
    	* function to filter options array used in custom post types.
		*
		* @since 0.1.1
		* @param array $arr options array.
		* @param string $key.
		* @param string $val.
		* @return array $newArray
    	*/
		public static function filterOptions($arr, $key, $val) {
			$newArr = array();
			foreach($arr as $name => $option) {
				if (array_key_exists($key, $option) && $option[$key]===$val){
					$newArr[$name] = $arr[$name];
				}
			}
			return $newArr;
		}
		
		/**
    	* Load additional CSS/JS to wp_head in wp-admin.
		*
		* @since 0.1.1
    	*/
		public function enqueue_autocomplete_scripts() {
			global $typenow;
			global $wp_styles;
			$scripts = array( 'jquery', 'jquery-ui-autocomplete', 'jquery-ui-datepicker');
  			if ($typenow=='evidence') {
				wp_enqueue_style( 'leafletcss', 'http://cdn.leafletjs.com/leaflet-0.6.4/leaflet.css' );
				wp_enqueue_style( 'leafletcss-ie8', "http://cdn.leafletjs.com/leaflet-0.6.4/leaflet.ie.css", array( 'leafletcss' )  );
    			$wp_styles->add_data( 'leafletcss-ie8', 'conditional', 'IE 8' );
				wp_enqueue_script( 'leafletjs', 'http://cdn.leafletjs.com/leaflet-0.6.4/leaflet.js' );
			} 
			if ($typenow=='location') {
				$scripts[] = 'pronamic_google_maps_admin';
			}
			wp_enqueue_style( 'evidence-hub-autocomplete', plugins_url( 'css/style.css' , EVIDENCE_HUB_REGISTER_FILE ) );
			wp_enqueue_script( 'evidence-hub-autocomplete', plugins_url( 'js/script.js' , EVIDENCE_HUB_REGISTER_FILE ), $scripts, '', true );
			wp_register_script( 'd3js', plugins_url( 'lib/map/lib/d3.v3.min.js' , EVIDENCE_HUB_REGISTER_FILE), array( 'jquery' )  );
			wp_enqueue_script( 'd3js' );
			
			// dequeue Pronomic Google Maps Library scripts and requeue with modified location
			wp_dequeue_script('pronamic_google_maps_admin');
			wp_dequeue_style('pronamic_google_maps_admin');
			wp_register_script('pronamic_google_maps_admin', plugins_url( 'lib/pronamic-google-maps/js/admin.js', EVIDENCE_HUB_REGISTER_FILE ),	array( 'jquery', 'google-jsapi' ));
			wp_register_style('pronamic_google_maps_admin',	plugins_url( 'lib/pronamic-google-maps/css/admin.css', EVIDENCE_HUB_REGISTER_FILE )	);
			wp_enqueue_script('pronamic_google_maps_admin');
			wp_enqueue_style('pronamic_google_maps_admin');
		}
		
		/**
    	* Load additional CSS/JS to wp_head in frontend.
		*
		* @since 0.1.1
    	*/
		public function enqueue_front_scripts() {
			$scripts = array( 'jquery', 'jquery-ui-autocomplete', 'jquery-ui-core', 'jquery-ui-tabs');
			wp_register_script( 'd3js', plugins_url( 'lib/map/lib/d3.v3.min.js' , EVIDENCE_HUB_REGISTER_FILE), array( 'jquery' )  );
			wp_enqueue_script( 'd3js' );
			wp_register_script( 'evidence_hub_script', plugins_url( 'js/script.js' , EVIDENCE_HUB_REGISTER_FILE), $scripts  );
			wp_enqueue_script( 'evidence_hub_script' );
			wp_register_style( 'evidence_hub_style', plugins_url( 'css/style.css' , EVIDENCE_HUB_REGISTER_FILE ) );
			wp_enqueue_style( 'evidence_hub_style');
		}
		
		/**
    	* Creates an array for custom post type taxonomies.
		*
		* @since 0.1.1
		* @param string $tax_single.
		* @param string $tax_plural.
		* @return array 
    	*/
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
				'show_admin_column'     => true,
				'query_var'             => true,
				'rewrite'               => array( 'slug' => strtolower($tax_single)),
			);		
		}

		/**
    	* Adds evidence_hub prefixed taxonomy terms and custom fields to a post_id.
		*
		* @since 0.1.1
		* @param string $post_id.
		* @param string $tax_plural.
		* @return array $post
    	*/
		public static function add_meta($post_id) {
			$post = array();
			$post['ID'] = $post_id;
			// get and push post custom fields prefixed evidence_hub
			foreach (get_post_custom($post_id) as $key => $value) {
				if (strpos($key, 'evidence_hub') !== 0) continue;
				$key = substr($key, 13);
				$post[$key] = @unserialize($value[0]) ? @unserialize($value[0]) : $value[0];
			}
			$taxonomies = get_object_taxonomies(get_post_type($post_id), 'objects');

			// get and push post custom taxonomy prefixed evidence_hub
			foreach ($taxonomies as $taxonomy_id => $taxonomy) {
				if (strpos($taxonomy_id, 'evidence_hub') !== 0) continue;
				$value = wp_get_object_terms($post_id, $taxonomy_id);
				
				$taxonomy_id = substr($taxonomy_id, 13);
				$taxonomy_slug = $taxonomy_id."_slug";
				$name = array();
				$slug = array();
				foreach ($value as $v){
					$name[] = $v->name;	
					$slug[] = $v->slug;
				}
				$post[$taxonomy_id] = (count($name)<=1) ? implode("",$name) : $name;
				$post[$taxonomy_slug] = (count($slug)<=1) ? implode("",$slug) : $slug;
			}
			return $post;
		}
		
		/**
    	* Adds evidence_hub prefixed taxonomy terms and custom fields array of post ids.
		*
		* @since 0.1.1
		* @param array $posts passed in using WP get_posts($args = array('fields' => 'ids')).
		* @return array $posts_termed 
    	*/
		public static function add_terms($posts) {
			$posts_termed = array();
			foreach ($posts as $post_id){
				$posts_termed[] = Evidence_Hub::add_meta($post_id);
			}
			return $posts_termed;
		}
		
		/**
    	* Adds ajaxable project name auto-complete.
		*
		* @since 0.1.1
		* @return object 
    	*/
		public function ajax_evidence_hub_project_callback() {
			global $wpdb;
			
			// if search term exists
			if ( $search_term = ( isset( $_POST[ 'evidence_hub_project_search_term' ] ) && ! empty( $_POST[ 'evidence_hub_project_search_term' ] ) ) ? $_POST[ 'evidence_hub_project_search_term' ] : NULL ) {
				if ( ( $projects = $wpdb->get_results( "SELECT posts.ID, posts.post_title, postmeta.meta_value  FROM $wpdb->posts posts INNER JOIN $wpdb->postmeta postmeta ON postmeta.post_id = posts.ID AND postmeta.meta_key ='_pronamic_google_maps_address' WHERE ( (posts.post_title LIKE '%$search_term%' OR postmeta.meta_value LIKE '%$search_term%') AND posts.post_type = 'project' AND post_status = 'publish' ) ORDER BY posts.post_title" ) )
				&& is_array( $projects ) ) {
					$results = array();
					// loop through each user to make sure they are allowed
					foreach ( $projects  as $project ) {								
							$results[] = array(
								'project_id'	=> $project->ID,
								'label'			=> $project->post_title,
								'address'		=> $project->meta_value, 
								);
					}
					// "return" the results
					//wp_reset_postmeta();
					echo json_encode( $results );
				}
			}
			die();
		}
		
		/**
    	* Adds ajaxable project details.
		*
		* @since 0.1.1
		* @return object 
    	*/
		public function ajax_evidence_hub_if_project_exists_by_value() {
			if ( $project_id = ( isset( $_POST[ 'autocomplete_eh_project_id' ] ) && ! empty( $_POST[ 'autocomplete_eh_project_id' ] ) ) ? $_POST[ 'autocomplete_eh_project_id' ] : NULL ) {
				$project_name = $_POST[ 'autocomplete_eh_project_value' ];
			
				$actual_project_name = get_the_title($project_id);
				
				if($project_name !== $actual_project_name){
					echo json_encode( (object)array( 'notamatch' => 1 ) );
					die();
				} else {	
					echo json_encode( (object)array( 'valid' => 1,
													 //'map' => $mapcode,
													 'country' => ($loc = wp_get_object_terms($project_id, 'evidence_hub_country')) ? $loc[0]->slug : NULL,
													 'lat' => get_post_meta($project_id, '_pronamic_google_maps_latitude', true ),
													 'lng' => get_post_meta($project_id, '_pronamic_google_maps_longitude', true ),
													 'zoom' => get_post_meta($project_id, '_pronamic_google_maps_zoom', true )));
					die();
				}
			} 
			echo json_encode( (object)array( 'noid' => 1 ) );
			die();
		}

		/**
    	* Generates a post excerpt (used in json/hub.php).
		*
		* @since 0.1.1
		* @param int $post_id.
		* @return string filtered post content 
    	*/
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
		
		/**
    	* Set country terms.
		*
		* @since 0.1.1
    	*/
		public function set_countries() {
			$jsonIterator = new RecursiveIteratorIterator(
					 new RecursiveArrayIterator(json_decode(file_get_contents(EVIDENCE_HUB_PATH."/lib/countries.json"), TRUE)),
					 RecursiveIteratorIterator::SELF_FIRST);
			$countries = array();
			foreach ($jsonIterator as $key => $val) {
				if(!is_array($val)) {
					$countries[$key] = $val;
				} 
			}
			return $countries;
		}

		/**
    	* Does WP permalink rewrites.
		*
		* @since 0.1.1
    	*/
		private function do_rewrites(){
			add_rewrite_rule("^country/([^/]+)/policy/sector/([^/]+)/page/([0-9]+)?",'index.php?post_type=policy&evidence_hub_country=$matches[1]&evidence_hub_sector=$matches[2]&paged=$matches[3]','top');
			add_rewrite_rule("^country/([^/]+)/policy/sector/([^/]+)?",'index.php?post_type=policy&evidence_hub_country=$matches[1]&evidence_hub_sector=$matches[2]','top');
			
			add_rewrite_rule("^country/([^/]+)/policy/page/([0-9]+)?",'index.php?post_type=policy&evidence_hub_country=$matches[1]&paged=$matches[2]','top');
			add_rewrite_rule("^country/([^/]+)/policy([^/]+)?",'index.php?post_type=policy&evidence_hub_country=$matches[1]','top');
			
			add_rewrite_rule("^country/([^/]+)/evidece/polarity/([^/]+)/sector/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]&evidence_hub_polarity=$matches[2]&evidence_hub_sector=$matches[3]&paged=$matches[4]','top');
			add_rewrite_rule("^country/([^/]+)/evidence/polarity/([^/]+)/sector/([^/]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]&evidence_hub_polarity=$matches[2]&evidence_hub_sector=$matches[3]','top');
			
			add_rewrite_rule("^country/([^/]+)/hypothesis/([0-9]+)/[^/]+/polarity/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]&hyp_id=$matches[2]&evidence_hub_polarity=$matches[3]&paged=$matches[4]','top');
			add_rewrite_rule("^country/([^/]+)/hypothesis/([0-9]+)/[^/]+/polarity/([^/]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]&hyp_id=$matches[2]&evidence_hub_polarity=$matches[3]','top');			
			add_rewrite_rule("^country/([^/]+)/hypothesis/([0-9]+)/.*?",'index.php?post_type=hypothesis&p=$matches[2]','top');
			
			add_rewrite_rule("^country/([^/]+)/evidence/(polarity|sector)/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]&evidence_hub_$matches[2]=$matches[3]&paged=$matches[4]','top');
			add_rewrite_rule("^country/([^/]+)/evidence/(polarity|sector)/([^/]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]&evidence_hub_$matches[2]=$matches[3]','top');
			
			add_rewrite_rule("^country/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]&paged=$matches[3]','top');
			add_rewrite_rule("^country/([^/]+)?",'index.php?post_type=evidence&evidence_hub_country=$matches[1]','top');
					
			add_rewrite_rule("^evidence/polarity/([^/]+)/sector/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&evidence_hub_polarity=$matches[1]&evidence_hub_sector=$matches[2]&paged=$matches[3]','top');
			add_rewrite_rule("^evidence/polarity/([^/]+)/sector/([^/]+)?",'index.php?post_type=evidence&evidence_hub_polarity=$matches[1]&evidence_hub_sector=$matches[2]','top');
			
			add_rewrite_rule("^evidence/(polarity|sector)/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&evidence_hub_$matches[1]=$matches[2]&paged=$matches[3]','top');
			add_rewrite_rule("^evidence/(polarity|sector)/([^/]+)?",'index.php?post_type=evidence&evidence_hub_$matches[1]=$matches[2]','top');
			
			add_rewrite_rule("^policy/sector/([^/]+)/page/([0-9]+)?",'index.php?post_type=policy&evidence_hub_sector=$matches[1]&paged=$matches[2]','top');
			add_rewrite_rule("^policy/sector/([^/]+)?",'index.php?post_type=policy&evidence_hub_sector=$matches[1]','top');
	
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/evidence/polarity/([^/]+)/sector/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&hyp_id=$matches[1]&evidence_hub_polarity=$matches[3]&evidence_hub_sector=$matches[4]&paged=$matches[5]','top');
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/evidence/polarity/([^/]+)/sector/([^/]+)?",'index.php?post_type=evidence&hyp_id=$matches[1]&evidence_hub_polarity=$matches[3]&evidence_hub_sector=$matches[4]','top');
			
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/evidence/(polarity|sector)/([^/]+)/page/([0-9]+)?",'index.php?post_type=evidence&hyp_id=$matches[1]&evidence_hub_$matches[3]=$matches[4]&paged=$matches[5]','top');
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/evidence/(polarity|sector)/([^/]+)?",'index.php?post_type=evidence&hyp_id=$matches[1]&evidence_hub_$matches[3]=$matches[4]','top');
			
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/evidence/page/([0-9]+)?",'index.php?post_type=evidence&hyp_id=$matches[1]&paged=$matches[2]','top');  
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/evidence/?",'index.php?post_type=evidence&hyp_id=$matches[1]','top');
			  
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/page/([0-9]+)?",'index.php?post_type=hypothesis&p=$matches[1]&paged=$matches[2]','top');
			add_rewrite_rule("^hypothesis/([0-9]+)/([^/]+)/?",'index.php?post_type=hypothesis&p=$matches[1]','top');
		}
		
		/**
		* Activate the plugin
		*
		* @since 0.1.1
		*/
		public static function activate(){
			flush_rewrite_rules();
			update_option( 'Pronamic_Google_maps', array( 'active' => array( 'location' => true, 'evidence' => true, 'project' => true, 'policy' => true  ) ) );
			// Do nothing
		} // END public static function activate
	
		/**
		* Deactivate the plugin
		*
		* @since 0.1.1
		*/		
		public static function deactivate(){
			// Do nothing
		} // END public static function deactivate
	} // END class Evidence_Hub
} // END if(!class_exists('Evidence_Hub'))

if(class_exists('Evidence_Hub')){
	// Installation and uninstallation hooks
	register_activation_hook(EVIDENCE_HUB_REGISTER_FILE, array('Evidence_Hub', 'activate'));
	register_deactivation_hook(EVIDENCE_HUB_REGISTER_FILE, array('Evidence_Hub', 'deactivate'));

	// instantiate the plugin class
	$wp_plugin_template = new Evidence_Hub();	
}