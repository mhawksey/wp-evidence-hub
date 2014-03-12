<?php


class Evidence_Hub_Settings_Cache {
	function __construct() {
		register_activation_hook(EVIDENCE_HUB_REGISTER_FILE, array(&$this, 'activate'));
		register_deactivation_hook(EVIDENCE_HUB_REGISTER_FILE, array(&$this, 'deactivate'));
		
		add_action('admin_init', array(&$this, 'save'));
		add_action('admin_menu', array(&$this, 'admin_menu'));
	}
	
	function activate() {
		add_option('evidence_hub_caching', true);
	}
	
	function deactivate() {
		delete_option('evidence_hub_caching');
	}
	
	function admin_menu() {
		add_submenu_page(
			'evidence_hub',
			"Caching/Settings",
			"Caching/Settings",
			'hypothesis_admin',
			'evidence_hub_cache',
			array(&$this, 'page')
		);
	}
	
	function page() {
		if (!current_user_can('hypothesis_admin')) wp_die("You do not have sufficient permissions to access this page.");
		
		$caching = get_option('evidence_hub_caching');
		$cache = Evidence_Hub_Shortcode::get_all_cache();
		
		?>
		<div id="evidence_hub_cache" class="wrap">
			<h2>Evidence Hub Caching</h2>
			<p>Evidence Hub caches the content of any of it's shortcodes you use in your site.</p>

			<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
				<?php wp_nonce_field('nonce_evidence_hub_cache'); ?>
				<p>
					<?php if ($caching) { ?>
						<input type="submit" name="evidence_hub_disable_cache" class="button button-primary" value="Disable Caching" />
					<?php } else { ?>
						<input type="submit" name="evidence_hub_enable_cache" class="button button-primary" value="Enable Caching" />
					<?php } ?>
					<input type="submit" name="evidence_hub_clear_cache" class="button button-primary" value="Clear Cache" />
				</p>
				<input type="hidden" name="evidence_hub_cache_settings" value="save" />
			</form>
			
			<?php if ($caching) { ?>
				<h3>Cached Shortcodes</h3>
				<?php if (empty($cache)) { ?>
					<p>No cached shortcodes.</p>
				<?php } else { ?>
					<table>
						<tr>
							<th>count</th>
							<th>shortcode</th>
						</tr>
						<?php foreach ($cache as $shortcode) { ?>
							<tr>
								<td><?php echo $shortcode->count; ?></td>
								<td><?php echo $shortcode->shortcode; ?></td>
							</tr>
						<?php } ?>
					</table>
				<?php } ?>
			<?php } ?>
		</div>
        <div class="wrap">
            <h2>Evidence Hub Settings</h2>
            <form method="post" action="options.php"> 
                <?php @settings_fields('evidence_hub_settings'); ?>
                <?php @do_settings_fields('evidence_hub_settings'); ?>
        
                <?php do_settings_sections('evidence_hub_template'); ?>
        
                <?php @submit_button(); ?>
            </form>
        </div>
        
		
		<?php
	}
	public function settings_section_evidence_hub_template()
	{
		// Think of this as help text for the section.
		echo 'The pages below can be set for custom templates for evidence and hypothesis data';
	}

	/**
	 * This function provides text inputs for settings fields
	 */
	public function settings_field_input_text($args)
	{
		// Get the field name from the $args array
		$field = $args['name'];
		// Get the value of this setting
		$value = get_option($field);
		// echo a proper input type="text"
		echo sprintf('<input type="text" name="%s" id="%s" value="%s" />', $field, $field, $value);
	} // END public function settings_field_input_text($args)
	
	/**
	* This function provides text inputs for settings fields
	*/
	public function settings_field_input_page_select($args)
	{
		// Get the field name from the $args array
		$field = $args['name'];
		// Get the value of this setting
		$value = get_option($field);
		$args['selected'] = ($value) ? $value : 0;
		$args['show_option_none'] = '-Select page-';
		$args['option_none_value'] = false;
		wp_dropdown_pages($args);
	} // END public function settings_field_input_page_select($args)
	
	/**
	* This function provides text inputs for settings fields
	*/
	public function settings_field_input_radio($args)
	{
		// Get the field name from the $args array
		$field = $args['name'];
		$value = get_option($field);
		echo '
		<div id="eh_settings">';

		foreach($args['choices'] as $val => $trans)
		{
			$val = esc_attr($val);

			echo '
			<input id="'.$field.'-'.$val.'" type="radio" name="'.$field.'" value="'.$val.'" '.checked($val, $value, FALSE).' />
			<label for="'.$field.'-'.$val.'">'.esc_html($trans).'</label>';
		}

		echo '
			<p class="description">'.$args['description'].'</p>
		</div>';
		
	} // END public function settings_field_input_radio($args)
	
	function save() {
		register_setting('evidence_hub_settings', 'hypothesis_template_page');
		register_setting('evidence_hub_settings', 'display_cookie_notice');
		register_setting('evidence_hub_settings', 'display_custom_head_foot');
		register_setting('evidence_hub_settings', 'display_postrating');
		register_setting('evidence_hub_settings', 'display_facetious');
		// add your settings section
		add_settings_section(
			'evidence_hub_template-section', 
			'Settings', 
			array(&$this, 'settings_section_evidence_hub_template'), 
			'evidence_hub_template'
		);

		// add your setting's fields
		add_settings_field(
			'evidence_hub_settings-hypothesis_page', 
			'Hypothesis Page Template', 
			array(&$this, 'settings_field_input_page_select'), 
			'evidence_hub_template', 
			'evidence_hub_template-section',
			array(
				'name' => 'hypothesis_template_page'
			)
		);
		
		add_settings_field(
			'evidence_hub_settings-display_cookie_notice', 
			'Cookie Notice', 
			array(&$this, 'settings_field_input_radio'), 
			'evidence_hub_template', 
			'evidence_hub_template-section',
			array(  'name' => 'display_cookie_notice',
					'choices' => array( 'yes' => 'Enable',
										'no' => 'Disable'),
					'description' => 'Enable or Disable cookie notices',
			)
		);
		
		add_settings_field(
			'evidence_hub_settings-display_display_postrating', 
			'Post Ratings', 
			array(&$this, 'settings_field_input_radio'), 
			'evidence_hub_template', 
			'evidence_hub_template-section',
			array(  'name' => 'display_postrating',
					'choices' => array( 'yes' => 'Enable',
										'no' => 'Disable'),
					'description' => 'Enable or Disable evidence/project/policy rating',
			)
		);
		
		add_settings_field(
			'evidence_hub_settings-display_custom_head_foot', 
			'Custom Headers/Footers', 
			array(&$this, 'settings_field_input_radio'), 
			'evidence_hub_template', 
			'evidence_hub_template-section',
			array(  'name' => 'display_custom_head_foot',
					'choices' => array( 'yes' => 'Enable',
										'no' => 'Disable'),
					'description' => 'Enable or Disable custom headers/footers',
			)
		);
		
				
		add_settings_field(
			'evidence_hub_settings-display_facetious', 
			'Enable Facetious Search Widget', 
			array(&$this, 'settings_field_input_radio'), 
			'evidence_hub_template', 
			'evidence_hub_template-section',
			array(  'name' => 'display_facetious',
					'choices' => array( 'yes' => 'Enable',
										'no' => 'Disable'),
					'description' => 'Enable or Disable the Facetious Search Widget',
			)
		);
		
		if (isset($_POST['evidence_hub_cache_settings']) && check_admin_referer('nonce_evidence_hub_cache')) {
			if (isset($_POST['evidence_hub_disable_cache'])) {
				update_option('evidence_hub_caching', false);
				Evidence_Hub::add_admin_notice("Caching disabled.");
			} else if (isset($_POST['evidence_hub_enable_cache'])) {
				update_option('evidence_hub_caching', true);
				Evidence_Hub::add_admin_notice("Caching enabled.");
			} else if (isset($_POST['evidence_hub_clear_cache'])) {
				Evidence_Hub_Shortcode::clear_cache();
				Evidence_Hub::add_admin_notice("Cache cleared.");
			}
			
			header("Location: ".$_SERVER['REQUEST_URI']);
			die;
		}	
		if(isset($_POST['option_page']) && isset($_POST['hypothesis_template_page'])){
			Evidence_Hub::add_admin_notice("Setting saved.");
		}
	}
	
}