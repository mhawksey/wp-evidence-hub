<?php
if(!class_exists('Location_Template'))
{
	/**
	 * A PostTypeTemplate class that provides 3 additional meta fields
	 */
	class Location_Template
	{
		public $post_type	= "location";
		public $archive_slug = "location"; // use pluralized string if you want an archive page
		public $singular = "Location";
		$this->plural = "Locations";
		var $options = array();
		
    	/**
    	 * The Constructor
    	 */
    	public function __construct()
    	{
    		// register actions
    		add_action('init', array(&$this, 'init'));
    		add_action('admin_init', array(&$this, 'admin_init'));
			add_action('manage_edit-'.$this->post_type.'_columns', array(&$this, 'columns'));
			add_action('manage_'.$this->post_type.'_posts_custom_column', array(&$this, 'column'),10 ,2);
			
			Evidence_Hub::$post_types[] = $this->post_type;
			
    	} // END public function __construct()

    	/**
    	 * hook into WP's init action hook
    	 */
    	public function init()
    	{
    		// Initialize Post Type
    		$this->create_post_type();
    		add_action('save_post', array(&$this, 'save_post'));
    	} // END public function init()
		
		public function columns($columns) {
			$columns = array(
				'cb' => '<input type="checkbox" />',
				'title' => __( $this->singular ),
				'evidence_hub_country' => __( 'Country' ),
				'author' => __( 'Author' ),
				'date' => __( 'Date' )
			);

			return $columns;
		}
		public function column($column, $post_id) {
			global $post;
			switch (str_replace('evidence_hub_', '', $column)) {
				case 'country':
					$location = wp_get_object_terms( $post_id, $column);
					if ( empty( $location ) )
						echo __( 'Empty' );
					else
						printf( __( '%s' ), $location[0]->name  );
					break;
			default :
				break;
			}
		}
		
		
    	/**
    	 * Create the post type
    	 */
    	public function create_post_type()
    	{
    		register_post_type($this->post_type,
    			array(
    				'labels' => array(
    					'name' => __(sprintf('%ss', ucwords(str_replace("_", " ", $this->post_type)))),
    					'singular_name' => __(ucwords(str_replace("_", " ", $this->post_type)))
    				),
					'labels' => array(
						'name' => __(sprintf('%s', $this->plural)),
						'singular_name' => __(sprintf('%s', $this->singular)),
						'add_new' => __(sprintf('Add New %s', $this->singular)),
						'add_new_item' => __(sprintf('Add New %s', $this->singular)),
						'edit_item' => __(sprintf('Edit %s', $this->singular)),
						'new_item' => __(sprintf('New %s', $this->singular)),
						'view_item' => __(sprintf('View %s', $this->singular)),
						'search_items' => __(sprintf('Search %s', $this->plural)),
						'not_found' => __(sprintf('No %s found', $this->plural)),
						'not_found_in_trash' => __(sprintf('No found in Trash%s', $this->plural)),
					),
    				'public' => true,
    				'description' => __("A location"),
    				'supports' => array(
    					'title', 'editor', 'excerpt', 'author', 
    				),
					'has_archive' => true,
					'rewrite' => array(
						'slug' => $this->archive_slug,
						'with_front' => false,
					),
					'menu_position' => 30,
					'menu_icon' => EVIDENCE_HUB_URL.'/images/icons/location.png',
    			)
    		);
		
			$args = Evidence_Hub::get_taxonomy_args("Country", "Countries");
		
			register_taxonomy( 'evidence_hub_country', array($this->post_type, 'evidence'), $args );
			
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
			
			
    	}
	
    	/**
    	 * Save the metaboxes for this custom post type
    	 */
    	public function save_post($post_id)
    	{
            // verify if this is an auto save routine. 
            // If it is our form has not been submitted, so we dont want to do anything
			if (get_post_type($post_id) != $this->post_type) return;
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
			if (isset($_POST['evidence_hub_nonce']) && !wp_verify_nonce($_POST['evidence_hub_nonce'], plugin_basename(__FILE__))) return;
			if (!current_user_can('edit_post', $post_id)) return;

			foreach($this->options as $name => $option)
			{
				// Update the post's meta field
				$field_name = "evidence_hub_$name";
				if (isset($_POST[$field_name])){
					if ($option['save_as'] == 'term'){
						wp_set_object_terms( $post_id, $_POST[$field_name], $field_name);
					} else {
						update_post_meta($post_id, $field_name, $_POST[$field_name]);
					}
				}
			}
    	} // END public function save_post($post_id)

    	/**
    	 * hook into WP's admin_init action hook
    	 */
    	public function admin_init()
    	{			

			$this->options = array_merge($this->options, array(
				'country' => array(
					'type' => 'select',
					'save_as' => 'term',
					'position' => 'side',
					'label' => "Country",
					'options' => get_terms('evidence_hub_country', 'hide_empty=0'),
					),
				));
				
				

			// Add metaboxes
    		add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
    	} // END public function admin_init()
			

    	/**
    	 * hook into WP's add_meta_boxes action hook
    	 */
    	public function add_meta_boxes()
    	{
// Add this metabox to every selected post
    		add_meta_box( 
    			sprintf('wp_evidence_hub_%s_side_section', $this->post_type),
    			sprintf('%s Information', ucwords(str_replace("_", " ", $this->post_type))),
    			array(&$this, 'add_inner_meta_boxes_side'),
    			$this->post_type,
				'side'
    	    );	
			remove_meta_box('tagsdiv-evidence_hub_country',$this->post_type,'side');
			
    					
    	} // END public function add_meta_boxes()

		 /**
		 * called off of the add meta box
		 */		
		public function add_inner_meta_boxes_side($post)
		{		
			$sub_options = Evidence_Hub::filterOptions($this->options, 'position', 'side');
			include(sprintf("%s/custom_post_metaboxes.php", dirname(__FILE__)));			
		} // END public function add_inner_meta_boxes($post)
		
		/**
		 * called off of the add meta box
		 */		
		public function add_inner_meta_boxes($post)
		{		
			// Render the job order metabox
			$sub_options = Evidence_Hub::filterOptions($this->options, 'position', 'bottom');
			include(sprintf("%s/custom_post_metaboxes.php", dirname(__FILE__)));			
		} // END public function add_inner_meta_boxes($post)
		
		public function set_countries(){
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
		

	} // END class Post_Type_Template
} // END if(!class_exists('Post_Type_Template'))