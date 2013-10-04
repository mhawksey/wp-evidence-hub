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

if(!class_exists('WP_Evidence_Hub'))
{
	class WP_Evidence_Hub
	{
		//static $post_types = array('hypothesis'); // constructed in custom post type constuctor
		
		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{
        	// Initialize Settings
            require_once(sprintf("%s/settings.php", EVIDENCE_HUB_PATH));
            $WP_Evidence_Hub_Settings = new WP_Evidence_Hub_Settings();
        	
        	// Register custom post types
           require_once(sprintf("%s/post-types/hypothesis.php", EVIDENCE_HUB_PATH));
           $Hypothesis_Template = new Hypothesis_Template();
		   
		   // Register custom post types
           require_once(sprintf("%s/post-types/evidence.php", EVIDENCE_HUB_PATH));
           $Evidence_Template = new Evidence_Template();
		   
		   // Register custom post types
           require_once(sprintf("%s/post-types/location.php", EVIDENCE_HUB_PATH));
           $Location_Template = new Location_Template();
		   
		   // Initialize Settings
		   if (!class_exists('Pronamic_Google_Maps_Maps')){
			   require_once(sprintf("%s/lib/pronamic-google-maps/pronamic-google-maps.php", EVIDENCE_HUB_PATH));
			   add_action( 'admin_init', array(&$this,'my_remove_named_menus') );
		   }
		   //$this->include_files();
		} // END public function __construct

		
		public function my_remove_named_menus(){
			remove_menu_page('pronamic_google_maps');
			//WP_Evidence_Hub::remove_menu_by_name('pronamic_google_maps');
		}
		
		public static function get_pronamic_google_map($post_id){
			$latLong = array();
			$latLong[] = get_post_meta($post_id, '_pronamic_google_maps_latitude', true );
			$latLong[] = get_post_meta($post_id, '_pronamic_google_maps_longitude', true );
			$latLong = array_filter($latLong);
			if (count($latLong) != 2){
				return false;	
			} else {
				ob_start();
				pronamic_google_maps_mashup(array(
												'post_type'   => 'location'
											),
											array(
												'width'       => 260,
												'height'      => 260,
												'latitude'    => $latLong[0],
												'longitude'   => $latLong[1],
												'zoom'        => 14,
												'fit_bounds'  => false,
												'markers' => $latLong,			
											)
										);
				$mapcode = ob_get_contents();
				ob_end_clean();
				return $mapcode;
			}
		}
		
		public static function filterOptions($arr, $loc){
			$newArr = array();
			foreach($arr as $name => $option) {
				if (array_key_exists('position', $option) && $option['position']==$loc){
					$newArr[$name] = $arr[$name];
				}
			}
			return $newArr;
		}
		
		/**
		 * Activate the plugin
		 */
		public static function activate()
		{
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
			update_option( 'Pronamic_Google_maps', array( 'active' => array( 'location' => true  ) ) );
			// Do nothing
		} // END public static function activate
	
		/**
		 * Deactivate the plugin
		 */		
		public static function deactivate()
		{
			// Do nothing
		} // END public static function deactivate
	} // END class WP_Evidence_Hub
} // END if(!class_exists('WP_Evidence_Hub'))

if(class_exists('WP_Evidence_Hub'))
{
	// Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('WP_Evidence_Hub', 'activate'));
	register_deactivation_hook(__FILE__, array('WP_Evidence_Hub', 'deactivate'));

	// instantiate the plugin class
	$wp_plugin_template = new WP_Evidence_Hub();
	
    
}