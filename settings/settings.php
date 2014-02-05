<?php

if(!class_exists('Evidence_Hub_Settings'))
{

	class Evidence_Hub_Settings
	{
		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{
			// register actions
            //add_action('admin_init', array(&$this, 'admin_init'));
        	add_action('admin_menu', array(&$this, 'add_menu'));
		} // END public function __construct
		
        
        /**
         * add a menu
         */		
        public function add_menu()
        {
            // Add a page to manage this plugin's settings
			add_menu_page(
				"Evidence Hub",
				"Evidence Hub",
				'hypothesis_admin',
				'evidence_hub',
				array(&$this, 'plugin_settings_page'),
				EVIDENCE_HUB_URL.'/images/icons/hub.png',
				'29'
			);
			add_submenu_page('evidence_hub', 'JSON API', 'JSON API', 'hypothesis_admin', 'json-api', array( '__JSON_API__', 'admin_options' ));
			
			$GLOBALS['menu'][28] = array('', 'read', 'separator-28', '', 'wp-menu-separator');

        } // END public function add_menu()
    
        /**
         * Menu Callback
         */		
        public function plugin_settings_page()
        {
        	if(!current_user_can('manage_options'))
        	{
        		wp_die(__('You do not have sufficient permissions to access this page.'));
        	}
	
        	// Render the settings template
        	include(sprintf("%s/overview.php", dirname(__FILE__)));
        } // END public function plugin_settings_page()
    } // END class Evidence_Hub_Settings
} // END if(!class_exists('Evidence_Hub_Settings'))
