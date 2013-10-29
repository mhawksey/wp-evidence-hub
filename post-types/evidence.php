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
			add_action('wp_ajax_evidence_hub_project_callback', array(&$this, 'ajax_evidence_hub_project_callback') );
			add_action('wp_ajax_evidence_hub_if_project_exists_by_value', array(&$this, 'ajax_evidence_hub_if_project_exists_by_value') );
			
			
			Evidence_Hub::$post_types[] = self::POST_TYPE;
			
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
				'evidence_hub_hypothesis_id' => __( 'Hypothesis' ),
				'evidence_hub_country' => __( 'Country' ),
				'evidence_hub_sector' => __( 'Sector' ),
				'author' => __( 'Author' ),
				'date' => __( 'Date' )
			);

			return $columns;
		}
		
		public function add_to_bulk_quick_edit_custom_box( $column_name, $post_type ) {
			print_r($column_name);
			$type = str_replace('evidence_hub_', '', $column_name);
			switch ($type) {
				case 'sector':
					Evidence_Hub::get_select_quick_edit($this->options->$type, $column_name);
					break;
				default:
					break;
			}			
		}
		
		public function column($column, $post_id) {
			global $post;
			switch (str_replace('evidence_hub_', '', $column)) {
			case 'polarity':
				$polarity = wp_get_object_terms( $post_id, $column);;
				if ( empty( $polarity ) )
					echo __( 'Empty' );
				else 
					echo __( '<span class="eh_pol">'.$polarity[0]->name.'</span>' );
				break;
			case 'hypothesis_id':
				$hypothesis = get_the_title(get_post_meta( $post_id, $column, true ));
				if ( empty( $hypothesis ) )
					echo __( 'Empty' );
				else
					printf( __( '%s' ), ucwords($hypothesis) );
				break;
			case 'country':
				$location = wp_get_object_terms( $post_id, $column);
				if ( empty( $location ) )
					echo __( 'Empty' );
				else
					printf( __( '%s' ), $location[0]->name  );
				break;
			case 'sector':
				$sector = wp_get_object_terms( $post_id, $column);
				if ( empty( $sector ) )
					echo __( 'Empty' );
				else
					printf( __( '%s' ), $sector[0]->name );
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
					'taxonomies' => array('post_tag'),
    				'supports' => array(
    					'title', 'editor', 'excerpt', 'author' 
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
			
			$args = Evidence_Hub::get_taxonomy_args("Sector","Sectors");
			register_taxonomy( 'evidence_hub_sector', self::POST_TYPE, $args );
			$args = Evidence_Hub::get_taxonomy_args("Polarity","Polarity");
			register_taxonomy( 'evidence_hub_polarity', self::POST_TYPE, $args );
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
					if ($option['save_as'] == 'term'){
						wp_set_object_terms( $post_id, $_POST[$field_name], $field_name);
					} else {
    					update_post_meta($post_id, $field_name, $_POST[$field_name]);
					}
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
					'save_as' => 'post_meta',
					'position' => 'side',
					'label' => "Hypothesis",
					'options' => $hypothesis_options,
					),
			));
			$this->options = array_merge($this->options, array(
				'polarity' => array(
					'type' => 'select',
					'save_as' => 'term',
					'position' => 'side',
					'label' => "Polarity",
					'options' => get_terms('evidence_hub_polarity', 'hide_empty=0&orderby=id'),
					),
			));
			$this->options = array_merge($this->options, array(
				'date' => array(
					'type' => 'date',
					'save_as' => 'post_meta',
					'position' => 'side',
					'label' => 'Date'
					)
			 ));
			 $this->options = array_merge($this->options, array(
				'sector' => array(
					'type' => 'select',
					'save_as' => 'term',
					'position' => 'side',
					'quick_edit' => true,
					'label' => 'Sector',
					'options' => get_terms('evidence_hub_sector', 'hide_empty=0&orderby=id'),
					)
			 ));
			 $this->options = array_merge($this->options, array(
				'country' => array(
					'type' => 'select',
					'save_as' => 'term',
					'position' => 'side',
					'label' => "Country",
					'options' => get_terms('evidence_hub_country', 'hide_empty=0'),
					),
			 ));
			 $this->options = array_merge($this->options, array(
				'project_id' => array(
					'type' => 'project',
					'save_as' => 'post_meta',
					'position' => 'side',
					'label' => 'Project/Org',
					'descr' => 'Optional field to associate evidence to a project',
					)
			 ));
			 $this->options = array_merge($this->options, array(
				'citation' => array(
					'type' => 'text',
					'save_as' => 'post_meta',
					'position' => 'bottom',
					'label' => 'Citation'
					)
			 ));

			
			// Add metaboxes
    		add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
			//add_action( 'bulk_edit_custom_box', array(&$this,'add_to_bulk_quick_edit_custom_box'), 10, 2 );
			//add_action( 'quick_edit_custom_box', array(&$this,'add_to_bulk_quick_edit_custom_box'), 10, 2 );
    	} // END public function admin_init()

			
    	/**
    	 * hook into WP's add_meta_boxes action hook
    	 */
    	public function add_meta_boxes()
    	{
    		// Add this metabox to every selected post	
    		add_meta_box( 
    			sprintf('wp_evidence_hub_%s_section', self::POST_TYPE),
    			sprintf('%s Information', ucwords(str_replace("_", " ", self::POST_TYPE))),
    			array(&$this, 'add_inner_meta_boxes'),
    			self::POST_TYPE,
				'normal',
				'high'
    	    );
			add_meta_box( 
    			sprintf('wp_evidence_hub_%s_side_section', self::POST_TYPE),
    			sprintf('%s Information', ucwords(str_replace("_", " ", self::POST_TYPE))),
    			array(&$this, 'add_inner_meta_boxes_side'),
    			self::POST_TYPE,
				'side'
    	    );
			remove_meta_box('tagsdiv-evidence_hub_country',self::POST_TYPE,'side');
			remove_meta_box('tagsdiv-evidence_hub_sector',self::POST_TYPE,'side');
			remove_meta_box('tagsdiv-evidence_hub_polarity',self::POST_TYPE,'side');
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
		
		
		public function ajax_evidence_hub_if_project_exists_by_value() {
			if ( $project_id = ( isset( $_POST[ 'autocomplete_eh_project_id' ] ) && ! empty( $_POST[ 'autocomplete_eh_project_id' ] ) ) ? $_POST[ 'autocomplete_eh_project_id' ] : NULL ) {
				$project_name = $_POST[ 'autocomplete_eh_project_value' ];
			
				$actual_project_name = get_the_title($project_id);
				
				if($project_name !== $actual_project_name){
					echo json_encode( (object)array( 'notamatch' => 1 ) );
					die();
				} else {
					//$mapcode = Evidence_Hub::get_pronamic_google_map($project_id);	
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
		  
	} // END class Post_Type_Template
} // END if(!class_exists('Post_Type_Template'))