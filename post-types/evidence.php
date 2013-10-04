<?php
if(!class_exists('Evidence_Template'))
{
	/**
	 * A PostTypeTemplate class that provides 3 additional meta fields
	 */
	class Evidence_Template
	{
		const POST_TYPE	= "evidence";
		const ARCHIVE_SLUG = "evidence"; // use pluralized string if you want an archive page
		const SINGULAR = "Evidence";
		const PLURAL = "Evidence";
		var $options = array();

    	/**
    	 * The Constructor
    	 */
    	public function __construct()
    	{
    		// register actions
    		add_action('init', array(&$this, 'init'));
    		add_action('admin_init', array(&$this, 'admin_init'));
			add_action('manage_edit-'.self::POST_TYPE.'_columns', array(&$this, 'columns'));
			add_action('manage_'.self::POST_TYPE.'_posts_custom_column', array(&$this, 'column'),10 ,2);
			add_action('admin_enqueue_scripts', array(&$this, 'enqueue_autocomplete_scripts'));
			add_action('wp_ajax_evidence_hub_location_callback', array(&$this, 'ajax_evidence_hub_location_callback') );
			add_action('wp_ajax_evidence_hub_if_location_exists_by_value', array(&$this, 'ajax_evidence_hub_if_location_exists_by_value') );
			
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
				'title' => __( self::SINGULAR ),
				'evidence_hub_polarity' => __( 'Polarity' ),
				'date' => __( 'Date' )
			);

			return $columns;
		}
		
		public function column($column, $post_id) {
			global $post;
			switch (str_replace('evidence_hub_', '', $column)) {
			case 'polarity':
				$polarity = get_post_meta( $post_id, $column, true );
				if ( empty( $polarity ) )
				echo __( 'Empty' );
				else
					printf( __( '%s' ), ucwords($polarity) );
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
    		register_post_type(self::POST_TYPE,
    			array(
    				'labels' => array(
    					'name' => __(sprintf('%ss', ucwords(str_replace("_", " ", self::POST_TYPE)))),
    					'singular_name' => __(ucwords(str_replace("_", " ", self::POST_TYPE)))
    				),
					'labels' => array(
						'name' => __(sprintf('%s', self::PLURAL)),
						'singular_name' => __(sprintf('%s', self::SINGULAR)),
						'add_new' => __(sprintf('Add New %s', self::SINGULAR)),
						'add_new_item' => __(sprintf('Add New %s', self::SINGULAR)),
						'edit_item' => __(sprintf('Edit %s', self::SINGULAR)),
						'new_item' => __(sprintf('New %s', self::SINGULAR)),
						'view_item' => __(sprintf('View %s', self::SINGULAR)),
						'search_items' => __(sprintf('Search %s', self::PLURAL)),
						'not_found' => __(sprintf('No %s found', self::PLURAL)),
						'not_found_in_trash' => __(sprintf('No found in Trash%s', self::PLURAL)),
					),
    				'public' => true,
    				'description' => __("A piece of evidence to support a hypothesis"),
    				'supports' => array(
    					'title', 'editor', 'excerpt', 
    				),
					'has_archive' => true,
					'rewrite' => array(
						'slug' => self::ARCHIVE_SLUG,
						'with_front' => false,
					),
					'menu_position' => 30,
					'menu_icon' => EVIDENCE_HUB_URL.'/images/icons/evidence.png',
    			)
    		);
    	}
	
    	/**
    	 * Save the metaboxes for this custom post type
    	 */
    	public function save_post($post_id)
    	{
            // verify if this is an auto save routine. 
            // If it is our form has not been submitted, so we dont want to do anything
            if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            {
                return;
            }
            
    		if($_POST['post_type'] == self::POST_TYPE && current_user_can('edit_post', $post_id))
    		{
    			foreach($this->options as $name => $option)
    			{
    				// Update the post's meta field
					$field_name = "evidence_hub_$name";
    				update_post_meta($post_id, $field_name, $_POST[$field_name]);
    			}
    		}
    		else
    		{
    			return;
    		} // if($_POST['post_type'] == self::POST_TYPE && current_user_can('edit_post', $post_id))
    	} // END public function save_post($post_id)

    	/**
    	 * hook into WP's admin_init action hook
    	 */
    	public function admin_init()
    	{			
    		Pronamic_Google_Maps_Site::bootstrap();
			$hypothesis_options = array();

			$hypothesis_query = new WP_Query(array(
				'post_type' => 'hypothesis',
				'posts_per_page' => -1, // show all
				'orderby' => 'title',
				'order' => 'ASC',
			));
			
			foreach ($hypothesis_query->posts as $hypothesis) {
				$hypothesis_options[$hypothesis->ID] = get_the_title($hypothesis->ID);
			}
			
			
			$this->options = array_merge($this->options, array(
				'hypothesis_id' => array(
					'type' => 'select',
					'position' => 'side',
					'label' => "Hypothesis",
					'options' => $hypothesis_options,
					),
			));
			$this->options = array_merge($this->options, array(
				'polarity' => array(
					'type' => 'select',
					'position' => 'side',
					'label' => "Polarity",
					'options' => array (
								'1' => '+',
								'-1' => '–',),
					),
			));
			$this->options = array_merge($this->options, array(
				'date' => array(
					'type' => 'date',
					'position' => 'side',
					'label' => 'Date'
					)
			 ));
			 $this->options = array_merge($this->options, array(
				'sector' => array(
					'type' => 'select',
					'position' => 'side',
					'label' => 'Sector',
					'options' => array (
								'School-K12' => 'School-K12',
								'College' => 'College',
								'Higher Education' => 'Higher Education',
								'Informal' => 'Informal'),
					)
			 ));
			 $this->options = array_merge($this->options, array(
				'location_id' => array(
					'type' => 'location',
					'position' => 'side',
					'label' => 'Location'
					)
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
    			sprintf('wp_evidence_hub_%s_side_section', self::POST_TYPE),
    			sprintf('%s Information', ucwords(str_replace("_", " ", self::POST_TYPE))),
    			array(&$this, 'add_inner_meta_boxes_side'),
    			self::POST_TYPE,
				'side'
    	    );	
    		add_meta_box( 
    			sprintf('wp_evidence_hub_%s_section', self::POST_TYPE),
    			sprintf('%s Citations', ucwords(str_replace("_", " ", self::POST_TYPE))),
    			array(&$this, 'add_inner_meta_boxes'),
    			self::POST_TYPE,
				'normal'
    	    );
    	} // END public function add_meta_boxes()
		

		 /**
		 * called off of the add meta box
		 */		
		public function add_inner_meta_boxes_side($post)
		{		
			$sub_options = WP_Evidence_Hub::filterOptions($this->options, 'side');
			include(sprintf("%s/custom_post_metaboxes.php", dirname(__FILE__)));			
		} // END public function add_inner_meta_boxes($post)
		
		/**
		 * called off of the add meta box
		 */		
		public function add_inner_meta_boxes($post)
		{		
			// Render the job order metabox
			$sub_options = WP_Evidence_Hub::filterOptions($this->options, 'bottom');
			include(sprintf("%s/custom_post_metaboxes.php", dirname(__FILE__)));			
		} // END public function add_inner_meta_boxes($post)
		
		public function enqueue_autocomplete_scripts() {
			wp_enqueue_style( 'evidence-hub-autocomplete', plugins_url( '../css/admin.css' , __FILE__ ) );
			wp_enqueue_script( 'evidence-hub-autocomplete', plugins_url( '../js/admin.js' , __FILE__ ), array( 'jquery', 'post', 'jquery-ui-autocomplete' ), '', true );
			global $typenow;
  			if ($typenow=='evidence') {
	  			wp_enqueue_script( 'pronamic_google_maps_site' );
			} 
			 // we could just do wp_enqueue_script('post'); instead to load all the scripts related to add new post page.
		}
		
		public function ajax_evidence_hub_location_callback() {
			global $wpdb;
			// if search term exists
			if ( $search_term = ( isset( $_POST[ 'evidence_hub_location_search_term' ] ) && ! empty( $_POST[ 'evidence_hub_location_search_term' ] ) ) ? $_POST[ 'evidence_hub_location_search_term' ] : NULL ) {
				if ( ( $locations = $wpdb->get_results( "SELECT posts.ID, posts.post_title, postmeta.meta_value  FROM $wpdb->posts posts INNER JOIN $wpdb->postmeta postmeta ON postmeta.post_id = posts.ID AND postmeta.meta_key ='_pronamic_google_maps_address' WHERE ( posts.post_title LIKE '%$search_term%' OR postmeta.meta_value LIKE '%$search_term%' AND posts.post_type = 'location' AND post_status = 'publish' ) ORDER BY posts.post_title" ) )
				&& is_array( $locations ) ) {
					$results = array();
					// loop through each user to make sure they are allowed
					foreach ( $locations  as $location ) {								
							$results[] = array(
								'location_id'	=> $location->ID,
								'label'			=> $location->post_title,
								'address'		=> $location->meta_value, 
								);
					}
					// "return" the results
					echo json_encode( $results );
				}
			}
			die();
		}
		
		
		public function ajax_evidence_hub_if_location_exists_by_value() {
			if ( $location_id = ( isset( $_POST[ 'autocomplete_eh_location_id' ] ) && ! empty( $_POST[ 'autocomplete_eh_location_id' ] ) ) ? $_POST[ 'autocomplete_eh_location_id' ] : NULL ) {
				$location_name = $_POST[ 'autocomplete_eh_location_value' ];
			
				$actual_location_name = get_the_title($location_id);
				
				if($location_name !== $actual_location_name){
					echo json_encode( (object)array( 'notamatch' => 1 ) );
					die();
				} else {
					$mapcode = WP_Evidence_Hub::get_pronamic_google_map($location_id);		
					echo json_encode( (object)array( 'valid' => 1,
													 'map' => $mapcode));
					die();
				}
			} 
			echo json_encode( (object)array( 'noid' => 1 ) );
			die();
		}

		  
	} // END class Post_Type_Template
} // END if(!class_exists('Post_Type_Template'))