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
Copyright 2014  Martin Hawksey  (email : m.hawksey@gmail.com)

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
		static $options = array();
		/**
		* Construct the plugin object.
		*
		* @since 0.1.1
		*/
		public function __construct() {			
			add_action('init', array(&$this, 'init'));
			Evidence_Hub::$options['cookies'] = get_option('display_cookie_notice');
			Evidence_Hub::$options['custom_head_foot'] = get_option('display_custom_head_foot');
			Evidence_Hub::$options['postrating'] = get_option('display_postrating');
			Evidence_Hub::$options['facetious'] = get_option('display_facetious');
			// Register custom post types
			require_once(sprintf("%s/post-types/class-custom_post_type.php", EVIDENCE_HUB_PATH));
			// Register custom post types - hypothesis
			require_once(sprintf("%s/post-types/class-hypothesis.php", EVIDENCE_HUB_PATH));
			// Register custom post types - evidence
			require_once(sprintf("%s/post-types/class-evidence.php", EVIDENCE_HUB_PATH));		
			// Register custom post types - project
			require_once(sprintf("%s/post-types/class-project.php", EVIDENCE_HUB_PATH));
			// Register custom post types - policy
			require_once(sprintf("%s/post-types/class-policy.php", EVIDENCE_HUB_PATH));
			// Register custom post types - suggestion
			require_once(sprintf("%s/post-types/class-suggestion.php", EVIDENCE_HUB_PATH));
			
			// include shortcodes
			require_once(sprintf("%s/shortcodes/class-shortcode.php", EVIDENCE_HUB_PATH));
			
			require_once(sprintf("%s/shortcodes/class-bookmarklet.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-evidence_entry.php", EVIDENCE_HUB_PATH));
			
			require_once(sprintf("%s/shortcodes/class-general_getpostmeta.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-general_getpoststagged.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-general_geomap.php", EVIDENCE_HUB_PATH));
			
			require_once(sprintf("%s/shortcodes/class-survey_explorer.php", EVIDENCE_HUB_PATH));
			
			require_once(sprintf("%s/shortcodes/class-evidence_map.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-evidence_meta.php", EVIDENCE_HUB_PATH));
			
			require_once(sprintf("%s/shortcodes/class-hypothesis_summary.php", EVIDENCE_HUB_PATH));			 
			require_once(sprintf("%s/shortcodes/class-hypothesis_archive.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-hypothesis_balance.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-hypothesis_breakdown.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-hypothesis_geosummary.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-hypothesis_bars.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-hypothesis_sankey.php", EVIDENCE_HUB_PATH));
			require_once(sprintf("%s/shortcodes/class-hypothesis_ratings.php", EVIDENCE_HUB_PATH));
			
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
			
			if (Evidence_Hub::$options['postrating'] === 'yes'){
				// ratings
				require_once(sprintf("%s/lib/wp-postratings/wp-postratings.php", EVIDENCE_HUB_PATH));
			}

			if (Evidence_Hub::$options['facetious'] === 'yes'){
				// Initialize Facetious library
				if (!class_exists('Facetious')){
					require_once(sprintf("%s/lib/facetious/facetious.php", EVIDENCE_HUB_PATH));
				}
			}
			
			if (Evidence_Hub::$options['cookies'] === 'yes'){
				// Initialize Cookie Notice library
				if (!class_exists('Cookie_Notice')){
					require_once(sprintf("%s/lib/cookie-notice/cookie-notice.php", EVIDENCE_HUB_PATH));
				}
			}
			if (Evidence_Hub::$options['custom_head_foot'] === 'yes'){
				// Initialize Custom Headers and Footers
				if ( !class_exists( 'CustomHeadersAndFooters' ) ) {
					require_once(sprintf("%s/lib/custom-headers-and-footers/custom-headers-and-footers.php", EVIDENCE_HUB_PATH));
				}
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
		   	add_action('admin_enqueue_scripts', array(&$this, 'enqueue_autocomplete_scripts'),999);
			add_action('wp_enqueue_scripts', array(&$this, 'enqueue_front_scripts') );

			// removed library plugin menus
			add_action( 'admin_menu', array(&$this,'my_remove_named_menus'),999 );
			add_action( 'admin_bar_menu', array(&$this,'remove_wp_nodes'), 999 );
			
			// open ajax for project autom complete
			add_action('wp_ajax_evidence_hub_project_callback', array(&$this, 'ajax_evidence_hub_project_callback') );
			add_action('wp_ajax_evidence_hub_if_project_exists_by_value', array(&$this, 'ajax_evidence_hub_if_project_exists_by_value') );
			
			// open ajax for match lookup
			add_action('wp_ajax_evidence_match_lookup', array(&$this, 'ajax_evidence_match_lookup') );
			
			// post count functions
			add_action( 'wp_head', array(&$this, 'eh_track_post_views') );
			remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
			
			// prevent evidence contributors from seeing other image ulpoads
			add_filter( 'ajax_query_attachments_args', array(&$this, 'user_restrict_media_library') );
			
			// debug function
			add_action( 'wp_head', array(&$this, 'show_current_query') );
			add_filter( 'tiny_mce_before_init', array(&$this, 'idle_function_to_tinymce') );
			if (get_option('hypothesis_template_page')){ 
				add_filter( 'single_template', array(&$this, 'get_custom_post_type_template') );
			}

		} // END public function __construct
		
		/**
		* Custom page tempalte 
		
		* @since 0.1.1
		*
		* @param object Existing template.
		* @return object New template.
		*/
		function get_custom_post_type_template($single_template) {
			global $post;
			
			if ($post->post_type == 'hypothesis' && get_option('hypothesis_template_page') !="") {
				global $content_width;
				$content_width = 960;
				$single_template = get_stylesheet_directory().'/'.get_post_meta( get_option('hypothesis_template_page'), '_wp_page_template', true );
				add_filter( 'body_class', array(&$this, 'evidence_hub_body_class') );
			}
			return $single_template;
		}
		
		/**
		* Add full width to hypothesis. 
		*
		* @since 0.1.1
		*
		* @param array Existing class values.
		* @return array Filtered class values.
		*/
		function evidence_hub_body_class( $classes ) {
			$classes[] = 'full-width';
			return $classes;
		}
		
		
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
			$caps = array('read_evidence', 
						  'delete_evidence', 
						  'edit_evidence');
			
			// define new role of Evidence Contributor to create but not publish/edit their evidence
			global $wp_roles;
			if ( ! isset( $wp_roles ) ){
				$wp_roles = new WP_Roles();
			}
			$adm = $wp_roles->get_role('subscriber');
			$wp_roles->add_role('evidence_contributor', 'Evidence Contributor', $adm->capabilities);
			$contributor = get_role('evidence_contributor');
			$contributor->add_cap('upload_files');
			
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
			
			Pronamic_Google_Maps_Site::bootstrap();		
		}
		
		/**
		 * Checks if a particular user has a role. 
		 * Returns true if a match was found.
		 *
		 * @param string $role Role name.
		 * @param int $user_id (Optional) The ID of a user. Defaults to the current user.
		 * @return bool
		 */
		public function appthemes_check_user_role( $role, $user_id = null ) {
		 
			if ( is_numeric( $user_id ) )
			$user = get_userdata( $user_id );
			else
				$user = wp_get_current_user();
		 
			if ( empty( $user ) )
			return false;
		 
			return in_array( $role, (array) $user->roles );
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
    	* Prevents evidence contributors from see other image uploads.
		* From http://stackoverflow.com/a/21710919/1027723
		*
		* @since 0.1.1
		* @param array $query WP Query.
		* @return array $query WP Query.
    	*/
		public function user_restrict_media_library(  $query ) {
			global $current_user;
			if (!current_user_can('evidence_admin')){
				$query['author'] = $current_user->ID ;	
			}
			return $query;
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
		  $qvars[] = 'bookmarklet';
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
		  return sprintf("%s/api/hub.php", EVIDENCE_HUB_PATH);
		}
		
		/**
    	* Remove Pronamic Google Map Library wp-admin menu option.
		* Remove Dashboard, posts and comments for Evidence Contributors
		*
		* @since 0.1.1
    	*/
		public function my_remove_named_menus() {
			global $menu;
			foreach ( $menu as $i => $item ) {
				if ( 'pronamic_google_maps' == $item[2] ) {
						unset( $menu[$i] );
						
				}
				if (!current_user_can( 'evidence_admin' )){
					if ('index.php' === $item[2] || 'edit.php' === $item[2] || 'edit-comments.php' === $item[2] || 'tools.php' === $item[2]){
						unset( $menu[$i] );
					}
				}
	        }
			return $item;
		}
		
		/**
    	* Remove New Post from admin bar for Evidence Contributors
		*
		* @since 0.1.1
    	*/
		public function remove_wp_nodes() {
			if (!current_user_can( 'evidence_admin' )){
				global $wp_admin_bar;   
				$wp_admin_bar->remove_node( 'new-post' );
				$wp_admin_bar->remove_node( 'comments' );
				$wp_admin_bar->remove_node( 'my-sites' );
			}
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
			global $wp_version;
			
			$scripts = array( 'jquery', 'jquery-ui-autocomplete', 'jquery-ui-datepicker','jquery-ui-tabs');
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
			wp_register_script('pronamic_google_maps_admin_eh', EVIDENCE_HUB_URL.'/lib/pronamic-google-maps/js/admin.js',	array( 'jquery', 'google-jsapi' ));
			wp_register_style('pronamic_google_maps_admin_eh', EVIDENCE_HUB_URL.'/lib/pronamic-google-maps/css/admin.css'	);
			// Add the localization for giving the settings.
			wp_localize_script( 'pronamic_google_maps_admin_eh', 'pronamic_google_maps_settings', array(
								'visualRefresh' => get_option( 'pronamic_google_maps_visual_refresh' )
								) );
			wp_enqueue_script('pronamic_google_maps_admin_eh');
			wp_enqueue_style('pronamic_google_maps_admin_eh');
			
			wp_dequeue_style('cookie-notice-admin');
			wp_dequeue_style('cookie-notice-wplike');
			
			wp_enqueue_style('cookie-notice-admin_eh', EVIDENCE_HUB_URL.'/lib/cookie-notice/css/admin.css');
			wp_enqueue_style('cookie-notice-wplike_eh', EVIDENCE_HUB_URL.'/lib/cookie-notice/css/wp-like-ui-theme.css');
			
			if (version_compare( $wp_version, '3.8', '<' )){
				wp_register_style('dashicons', EVIDENCE_HUB_URL.'/css/dashicons.css'	);
				wp_enqueue_style('dashicons');
			}
		}
		
		/**
    	* Load additional CSS/JS to wp_head in frontend.
		*
		* @since 0.1.1
    	*/
		public function enqueue_front_scripts() {
			global $wp_styles;
			global $wp_version;
			
			$scripts = array( 'jquery', 'jquery-ui-autocomplete', 'jquery-ui-core', 'jquery-ui-tabs', 'jquery-ui-accordion',  'jquery-ui-datepicker', 'suggest');
			wp_register_script('pronamic_google_maps_admin_eh', EVIDENCE_HUB_URL.'/lib/pronamic-google-maps/js/admin.js',	array( 'jquery', 'google-jsapi' ));
			wp_register_style('pronamic_google_maps_admin_eh', EVIDENCE_HUB_URL.'/lib/pronamic-google-maps/css/admin.css'	);
			wp_enqueue_script('pronamic_google_maps_admin_eh');
			wp_enqueue_style('pronamic_google_maps_admin_eh');
			wp_localize_script( 'pronamic_google_maps_admin_eh', 'pronamic_google_maps_settings', array(
					'visualRefresh' => get_option( 'pronamic_google_maps_visual_refresh' )
					) );
			wp_register_script( 'd3js', plugins_url( 'lib/map/lib/d3.v3.min.js' , EVIDENCE_HUB_REGISTER_FILE), array( 'jquery' )  );
			wp_enqueue_script( 'd3js' );
			
			wp_enqueue_style( 'leafletcss', 'http://cdn.leafletjs.com/leaflet-0.6.4/leaflet.css' );
			wp_enqueue_style( 'leafletcss-ie8', "http://cdn.leafletjs.com/leaflet-0.6.4/leaflet.ie.css", array( 'leafletcss' )  );
			$wp_styles->add_data( 'leafletcss-ie8', 'conditional', 'IE 8' );
			wp_enqueue_script( 'leafletjs', 'http://cdn.leafletjs.com/leaflet-0.6.4/leaflet.js' );
			
			wp_register_script( 'evidence_hub_script', plugins_url( 'js/script.js' , EVIDENCE_HUB_REGISTER_FILE), $scripts  );
			wp_enqueue_script( 'evidence_hub_script' );
			wp_register_script( 'evidence_hub_frontonlyscript', plugins_url( 'js/frontonlyscript.js' , EVIDENCE_HUB_REGISTER_FILE)  );
			wp_enqueue_script( 'evidence_hub_frontonlyscript' );
			wp_register_style( 'evidence_hub_style', plugins_url( 'css/style.css' , EVIDENCE_HUB_REGISTER_FILE ) );
			wp_enqueue_style( 'evidence_hub_style');
			wp_enqueue_style( 'facetious_widget', EVIDENCE_HUB_URL.'/lib/facetious/facetious.css' );
			
			wp_register_script( 'selectreplace_script', plugins_url( 'js/selectreplace/jquery.minimalect.min.js' , EVIDENCE_HUB_REGISTER_FILE), $scripts  );
			wp_enqueue_script( 'selectreplace_script' );
			wp_enqueue_style( 'selectreplace_style', plugins_url( 'js/selectreplace/jquery.minimalect.min.css' , EVIDENCE_HUB_REGISTER_FILE ) );
			
			// handle cookie-notice enqueue (required because of symbolic links)
			$this->cookie = array(
					'name' => 'cookie_notice_accepted',
					'value' => 'TRUE');

			if(!(isset($_COOKIE[$this->cookie['name']]) && $_COOKIE[$this->cookie['name']] === $this->cookie['value'])){
				wp_dequeue_script('cookie-notice-front');
				wp_dequeue_style('cookie-notice-front');
				
				wp_enqueue_script('cookie-notice-front_eh', EVIDENCE_HUB_URL.'/lib/cookie-notice/js/front.js' ,array('jquery'));
				$this->cookieoptions = get_option('cookie_notice_options');
				$this->times = array(
					'day' => array(__('1 day', 'cookie-notice'), 86400),
					'week' => array(__('1 week', 'cookie-notice'), 604800),
					'month' => array(__('1 month', 'cookie-notice'), 2592000),
					'3months' => array(__('3 months', 'cookie-notice'), 7862400),
					'6months' => array(__('6 months', 'cookie-notice'), 15811200),
					'year' => array(__('1 year', 'cookie-notice'), 31536000),
					'infinity' => array(__('infinity', 'cookie-notice'), 31337313373)
				);

				wp_localize_script(
					'cookie-notice-front_eh',
					'cnArgs',
					array(
						'ajaxurl' => admin_url('admin-ajax.php'),
						'hideEffect' => $this->cookieoptions['hide_effect'],
						'cookieName' => $this->cookie['name'],
						'cookieValue' => $this->cookie['value'],
						'cookieTime' => $this->times[$this->cookieoptions['time']][1],
						'cookiePath' => (defined('COOKIEPATH') ? COOKIEPATH : ''),
						'cookieDomain' => (defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '')
					)
				);
	
				wp_enqueue_style('cookie-notice-front_eh', EVIDENCE_HUB_URL.'/lib/cookie-notice/css/front.css');
			}
			
			if (version_compare( $wp_version, '3.8', '<' )){
				wp_register_style('dashicons', EVIDENCE_HUB_URL.'/css/dashicons.css'	);
				wp_enqueue_style('dashicons');
			}
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
    	* Adds ajaxable evidence match lookup.
		*
		* @since 0.1.1
		* @return object 
    	*/
		public function ajax_evidence_match_lookup() {
			if (isset($_REQUEST['q']) && !empty($_REQUEST['q']) && isset($_REQUEST['lookup_field']) && !empty($_REQUEST['lookup_field'])){
				$toSkip = (isset($_REQUEST['post_id'])) ? $_REQUEST['post_id'] : false;
				$args = array(
							   'orderby' => 'meta_value',
							   'order' => 'ASC',
							   'post_status' => array( 'pending', 'draft', 'future', 'publish' ),
							   'post_type' => Evidence_Hub::$post_types,
							   'meta_query' => array(
								   array(
									   'key' => $_REQUEST['lookup_field'],
									   'value' => $_REQUEST['q'],
									   'compare' => 'LIKE',
								   )
							   )
							 );
				$the_query = new WP_Query($args);
				// The Loop
				if ( $the_query->have_posts() ) {
					$results = array();
						//echo '<ul>';
					while ( $the_query->have_posts() ) {
						$the_query->the_post();
						if ($toSkip && $toSkip != get_the_ID()){
							$results[] = array('title' => get_the_title(), 'url' => get_permalink() );
						} elseif (!$toSkip) {
							$results[] = array('title' => get_the_title(), 'url' => get_permalink());
						}
					}
						echo json_encode($results);
				} else {
					echo json_encode($args);	
				}
				
				/* Restore original Post Data */
				wp_reset_postdata();
				die();
			
			} 
			
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
    	* Generates a post excerpt (used in api/hub.php).
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
		
		function idle_function_to_tinymce( $initArray ) {
			if ( !is_admin() ) {					
				$initArray['init_instance_callback'] = 'myCustomInitInstance'; 
				$initArray['file_browser_callback'] = 'myCustomInitInstance'; // seems you need to call this if media_button true 
				//print_r($initArray);
			}
			return $initArray;
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
    	* formats required labels on data entry.
		*
		* @since 0.1.1
		* @params array $options values
    	*/
		public static function format_label($option){
			$lab = (isset($option['label'])) ? $option['label'].'%s:' : '';
			if (is_admin()){
				$resp = (isset($option['required']) && $option['required']) ? '(*)' : '';
			} else {
				$resp = (isset($option['required']) && $option['required']) ? '(<span class="required">*</span>)' : '';
			}
			echo sprintf($lab, $resp);		
		}
		
		/**
		* record page view counts
		* taken from http://www.wpbeginner.com/wp-tutorials/how-to-track-popular-posts-by-views-in-wordpress-without-a-plugin/
		*
		* @since 0.1.1
		* @params string $postID
		*/
		public function eh_set_post_views($postID) {
			$count_key = 'post_views_count';
			$count = get_post_meta($postID, $count_key, true);
			if($count==''){
				$count = 0;
				delete_post_meta($postID, $count_key);
				add_post_meta($postID, $count_key, '0');
			}else{
				$count++;
				update_post_meta($postID, $count_key, $count);
			}
		}
		
		
		function eh_track_post_views($post_id) {
			if ( !is_single() ) return;
			if ( empty ( $post_id) ) {
				global $post;
				$post_id = $post->ID;    
			}
			$this->eh_set_post_views($post_id);
		}
		
		// http://wordpress.org/support/topic/plugin-pronamic-google-maps-display-pronamic-meta-box-in-a-front-end-page#post-3124660
		////////   PRONAMIC GOOGLE MAPS IN FRONT END   //////////
		/**
		 * Add google maps options to the add custom post area<br />
		 *
		 * @uses wpuf_add_post_form_description action hook
		 *
		 * @param object|null $post the post object
		 */
		public static function wpufe_gmaps($post = null) {
			
			// Pronamic custom function to get custom fields
			//$pgm = ( $post != null ) ? pronamic_get_google_maps_meta() : '' ;
			//print_r(pronamic_get_google_maps_meta());
			
			$pgm_map_type = ( $post != null ) ? get_post_meta( $post->ID, '_pronamic_google_maps_map_type', true ) : '';
			$pgm_zoom = ( $post != null ) ? get_post_meta( $post->ID, '_pronamic_google_maps_zoom', true ) : '';
			$pgm_address = ( $post != null ) ? get_post_meta( $post->ID, '_pronamic_google_maps_address', true ) : '';
			$pgm_latitude = ( $post != null ) ? get_post_meta( $post->ID, '_pronamic_google_maps_latitude', true ) : '';
			$pgm_longitude = ( $post != null ) ? get_post_meta( $post->ID, '_pronamic_google_maps_longitude', true ) : '';
		
		?>
            <div id="pronamic-google-maps-meta-box" >
            
            <li>
                    <input id="pgm-map-type-field" name="<?php echo Pronamic_Google_Maps_Post::META_KEY_MAP_TYPE; ?>" value="<?php echo esc_attr( $pgm_map_type ); ?>" type="hidden" />
                <input id="pgm-zoom-field" name="<?php echo Pronamic_Google_Maps_Post::META_KEY_ZOOM; ?>" value="<?php echo esc_attr( $pgm_zoom ); ?>" type="hidden" />
                    <input id="pgm-active-field" name="<?php echo Pronamic_Google_Maps_Post::META_KEY_ACTIVE; ?>" value="true" type="hidden" />
            
                <label for="pgm-address-field">Address</label>
                <textarea id="pgm-address-field" name="<?php echo Pronamic_Google_Maps_Post::META_KEY_ADDRESS; ?>" rows="2" cols="40"><?php echo esc_attr( $pgm_address ); ?></textarea>
                <p class="description">Please type the address and click on "Geocode ↓" to find the location.</p>
            
            </li>
            <li>
                <input id="pgm-geocode-button" type="button" value="<?php _e('Geocode ↓', 'pronamic_google_maps'); ?>" class="button" name="pgm_geocode" />
            
                <input id="pgm-reverse-geocode-button" type="button" value="<?php echo _e('Reverse Geocode ↑', 'pronamic_google_maps'); ?>" class="button" name="pgm_reverse_geocode" />
            
            </li>
            <li>
                    <label for="pgm-lat-field">Latitude</label>
                    <input id="pgm-lat-field" name="<?php echo Pronamic_Google_Maps_Post::META_KEY_LATITUDE; ?>" value="<?php echo esc_attr($pgm_latitude); ?>" type="text" style="width: 200px;" />
                °
                </li>
            <li>
                    <label for="pgm-lng-field">Longitude</label>
                <input id="pgm-lng-field" name="<?php echo Pronamic_Google_Maps_Post::META_KEY_LONGITUDE; ?>" value="<?php echo esc_attr($pgm_longitude); ?>" type="text" style="width: 200px;" />
                °
                </li>
            <li>
                    <label for="pgm-canvas">Location result</label>
            
                <div id="pgm-canvas" style="width: 380px!important; height: 330px; border: 1px solid white; margin:auto"></div>
            
                    <p class="description">Tip: Change the zoom level and map type to your own wishes.</p>
            
            </li>
            </div>
			<?php
		}
		
		/**
		* Activate the plugin
		*
		* @since 0.1.1
		*/
		public static function activate(){
			flush_rewrite_rules();
			update_option( 'Pronamic_Google_maps', array( 'active' => false ) );
			Evidence_Hub_Shortcode::activate();
			if (function_exists('create_ratinglogs_table')){
				create_ratinglogs_table();
			}
			// Do nothing
		} // END public static function activate
	
		/**
		* Deactivate the plugin
		*
		* @since 0.1.1
		*/		
		public static function deactivate(){
			Evidence_Hub_Shortcode::deactivate();
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