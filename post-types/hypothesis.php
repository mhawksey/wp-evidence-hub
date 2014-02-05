<?php
if(!class_exists('Hypothesis_Template'))
{
	/**
	 * A PostTypeTemplate class that provides 3 additional meta fields
	 */
	class Hypothesis_Template
	{
		const POST_TYPE	= "hypothesis";
		const ARCHIVE_SLUG = "hypothesis"; // use pluralized string if you want an archive page
		const SINGULAR = "Hypothesis";
		const PLURAL = "Hypotheses";
		var $options = array();
		
    	/**
    	 * The Constructor
    	 */
    	public function __construct()
    	{
    		// register actions
    		add_action('init', array(&$this, 'init'));
    		add_action('admin_init', array(&$this, 'admin_init'));
			//add_action('manage_edit-'.self::POST_TYPE.'_columns', array(&$this, 'columns'));
			//add_action('manage_'.self::POST_TYPE.'_posts_custom_column', array(&$this, 'column'),10 ,2);
			add_filter('post_type_link', array(&$this, 'custom_post_type_link'), 1, 3);
		
			
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
		
		public function custom_post_type_link($post_link, $post = 0, $leavename = false) {	
			
				
			if ($post->post_type == 'hypothesis') {
				return str_replace('%post_id%', $post->ID, $post_link);
			} else {
				return $post_link;
			}
		}
		public function columns($columns) {
			$columns = array(
				'cb' => '<input type="checkbox" />',
				'title' => __( self::SINGULAR ),
				'evidence_hub_rag' => __( 'Status' ),
				'date' => __( 'Date' )
			);

			return $columns;
		}
		public function column($column, $post_id) {
			global $post;
			switch (str_replace('evidence_hub_', '', $column)) {
			case 'rag':
				$rag = wp_get_object_terms( $post_id, $column );
				if ( empty( $rag ) )
				echo __( 'Empty' );
				else
					printf( __( '%s' ),$rag[0]->name );
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
    				'description' => __("A hypothesis"),
    				'supports' => array(
    					'title', 'editor', 'excerpt', 'author',
    				),
					'capabilities' => array(
						'edit_post'          => 'hypothesis_admin',
						'read_post'          => 'hypothesis_admin',
						'delete_post'        => 'hypothesis_admin',
						'edit_posts'         => 'hypothesis_admin',
						'edit_others_posts'  => 'hypothesis_admin',
						'publish_posts'      => 'hypothesis_admin',
						'read_private_posts' => 'hypothesis_admin'
					),
					'has_archive' => self::ARCHIVE_SLUG,
					'rewrite' => array(
						'slug' => self::ARCHIVE_SLUG.'/%post_id%',
						'with_front' => false,
					),
					'menu_position' => 30,
					'menu_icon' => EVIDENCE_HUB_URL.'/images/icons/hyp.png',
    			)
    		);

			
			$args = Evidence_Hub::get_taxonomy_args("RAG Status","RAG Status");
		
			register_taxonomy( 'evidence_hub_rag', self::POST_TYPE, $args );
    	}
	
    	/**
    	 * Save the metaboxes for this custom post type
    	 */
    	public function save_post($post_id)
    	{
            // verify if this is an auto save routine. 
            // If it is our form has not been submitted, so we dont want to do anything
			if (get_post_type($post_id) != self::POST_TYPE) return;
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
				'rag' => array(
					'type' => 'select',
					'save_as' => 'term',
					'position' => 'side',
					'label' => "RAG",
					'options' => get_terms('evidence_hub_rag', 'hide_empty=0&orderby=id'),
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
    			sprintf('wp_evidence_hub_%s_side_section', self::POST_TYPE),
    			sprintf('%s Information', ucwords(str_replace("_", " ", self::POST_TYPE))),
    			array(&$this, 'add_inner_meta_boxes_side'),
    			self::POST_TYPE,
				'side'
    	    );	
			remove_meta_box('tagsdiv-evidence_hub_rag',self::POST_TYPE,'side');				
    	} // END public function add_meta_boxes()

		 /**
		 * called off of the add meta box
		 */		
		public function add_inner_meta_boxes_side($post)
		{		
			wp_nonce_field(plugin_basename(__FILE__), 'evidence_hub_nonce');
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

	} // END class Post_Type_Template
} // END if(!class_exists('Post_Type_Template'))