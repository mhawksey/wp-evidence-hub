<?php
/**
 * Construct a project custom post type
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_CustomPostType
 */
new Project_Template();
class Project_Template extends Evidence_Hub_CustomPostType {
	public $post_type	= "project";
	public $archive_slug = "project"; // use pluralized string if you want an archive page
	public $singular = "Project";
	public $plural = "Projects";
	public $options = array();
		
	/**
	* Register custom post type.
	*
	* @since 0.1.1
	*/
	public function create_post_type() {
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
					'title', 'editor', 'excerpt', 'author', 'comments' 
				),
				'capabilities' => array(
					'edit_post'          => 'edit_evidence',
					'read_post'          => 'read_evidence',
					'delete_post'        => 'delete_evidence',
					'edit_others_posts'  => 'evidence_admin',
					'publish_posts'      => 'evidence_admin',
					'read_private_posts' => 'evidence_admin',
				),
				'has_archive' => true,
				'rewrite' => array(
					'slug' => $this->archive_slug,
					'with_front' => false,
				),
				'menu_position' => 30,
				'menu_icon' => EVIDENCE_HUB_URL.'images/icons/location.png',
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
		 Evidence_Hub::$post_type_fields[$this->post_type] = $this->options;
	}

	/**
	* Register custom fields box in wp-admin.
	*
	* @since 0.1.1
	*/
	public function add_meta_boxes() {
		// Add this metabox to every selected post	
		add_meta_box( 
			sprintf('wp_evidence_hub_%s_section', $this->post_type),
			sprintf('%s Information', ucwords(str_replace("_", " ", $this->post_type))),
			array(&$this, 'add_inner_meta_boxes'),
			$this->post_type,
			'normal',
			'high'
		);
		add_meta_box( 
			sprintf('wp_evidence_hub_%s_side_section', $this->post_type),
			sprintf('%s Information', ucwords(str_replace("_", " ", $this->post_type))),
			array(&$this, 'add_inner_meta_boxes_side'),
			$this->post_type,
			'side',
			'high'
		);	
		remove_meta_box('tagsdiv-evidence_hub_country',$this->post_type,'side');
		Pronamic_Google_Maps_MetaBox::register($this->post_type, 'normal', 'high');
		
					
	} // END public function add_meta_boxes()

} // END class Post_Type_Template