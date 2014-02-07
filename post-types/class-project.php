<?php
/**
 * Construct a project custom post type
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 */

class Project_Template {
	const POST_TYPE	= "project";
	const ARCHIVE_SLUG = "project"; // use pluralized string if you want an archive page
	const SINGULAR = "Project";
	const PLURAL = "Projects";
	public $options = array();
	
	/**
	* The Constructor
	*
	* @since 0.1.1
	*/
	public function __construct() {
		// register actions
		add_action('init', array(&$this, 'init'));
		add_action('init', array(&$this, 'set_options'));
		add_action('admin_init', array(&$this, 'admin_init'));
		
		Evidence_Hub::$post_types[] = self::POST_TYPE;
	} // END public function __construct()

	/**
	* hook into WP's init action hook.
	*
	* @since 0.1.1
	*/
	public function init() {
		// Initialize Post Type
		$this->create_post_type();
		add_action('save_post', array(&$this, 'save_post'));
	} // END public function init()
		
	/**
	* Register custom post type.
	*
	* @since 0.1.1
	*/
	public function create_post_type() {
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
				'description' => __("A location"),
				'supports' => array(
					'title', 'editor', 'excerpt', 'author', 
				),
				'capabilities' => array(
					'edit_post'          => 'evidence_edit_posts',
					'read_post'          => 'evidence_read_post',
					'delete_post'        => 'evidence_delete_posts',
					'edit_posts'         => 'evidence_edit_posts',
					'edit_others_posts'  => 'evidence_admin',
					'publish_posts'      => 'evidence_admin',
					'read_private_posts' => 'evidence_admin'
				),
				'has_archive' => true,
				'rewrite' => array(
					'slug' => self::ARCHIVE_SLUG,
					'with_front' => false,
				),
				'menu_position' => 30,
				'menu_icon' => EVIDENCE_HUB_URL.'/images/icons/location.png',
			)
		);	
	}
	
	/**
	* Register custom post type fields.
	*
	* @since 0.1.1
	*/
	function set_options(){
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
			'resource_link' => array(
				'type' => 'text',
				'save_as' => 'post_meta',
				'position' => 'bottom',
				'label' => 'Link'
				)
		 ));
		 Evidence_Hub::$post_type_fields[self::POST_TYPE] = $this->options;
	}
	
	/**
	* Save the metaboxes for this custom post type.
	*
	* @since 0.1.1
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
	* Add action to add metaboxes and add Google Map location finder.
	*
	* @since 0.1.1
	*/
	public function admin_init() {	
		add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
	} // END public function admin_init()
		

	/**
	* Register custom fields box in wp-admin.
	*
	* @since 0.1.1
	*/
	public function add_meta_boxes() {
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
		
					
	} // END public function add_meta_boxes()

	/**
	* Render side custom fields in wp-admin.
	*
	* @since 0.1.1
	*/			
	public function add_inner_meta_boxes_side($post) {	
		wp_nonce_field(plugin_basename(__FILE__), 'evidence_hub_nonce');	
		$sub_options = Evidence_Hub::filterOptions($this->options, 'position', 'side');
		include(sprintf("%s/custom_post_metaboxes.php", dirname(__FILE__)));			
	} // END public function add_inner_meta_boxes($post)
	
	/**
	* Render bottom custom fields in wp-admin.
	*
	* @since 0.1.1
	*/	
	public function add_inner_meta_boxes($post)	{		
		// Render the job order metabox
		$sub_options = Evidence_Hub::filterOptions($this->options, 'position', 'bottom');
		include(sprintf("%s/custom_post_metaboxes.php", dirname(__FILE__)));			
	} // END public function add_inner_meta_boxes($post)
} // END class Post_Type_Template
new Project_Template();