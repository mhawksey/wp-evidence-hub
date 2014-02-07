<?php
/**
 * Construct a evidence custom post type
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 */
 
class Evidence_Template {
	const POST_TYPE	= "evidence";
	const ARCHIVE_SLUG = "evidence"; // use pluralized string if you want an archive page
	const SINGULAR = "Evidence";
	const PLURAL = "Evidence";
	private static $fields;
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
		// register custom columns in wp-admin
		add_action('manage_edit-'.self::POST_TYPE.'_columns', array(&$this, 'columns'));
		add_action('manage_'.self::POST_TYPE.'_posts_custom_column', array(&$this, 'column'),10 ,2);
		// push post types for caching
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
				'description' => __("A piece of evidence to support a hypothesis"),
				'taxonomies' => array('post_tag'),
				'supports' => array(
					'title', 'editor', 'excerpt', 'author' 
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
				'menu_icon' => EVIDENCE_HUB_URL.'/images/icons/evidence.png',
			)
		);
	}
	
	/**
	* Register custom post type fields.
	*
	* @since 0.1.1
	*/
	function set_options(){
		$hypothesis_options = array();
		// get all the hypothesis ids													
		$hypotheses = get_posts( array(	'post_type' => 'hypothesis', // my custom post type
										'posts_per_page' => -1,
										'post_status' => 'publish',
										'orderby' => 'title',
										'order' => 'ASC',
										'fields' => 'ids'));
		foreach($hypotheses as $hypothesis){
			$hypothesis_options[$hypothesis] = get_the_title($hypothesis);
		}
		
		// regester different field options
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
		 Evidence_Hub::$post_type_fields[self::POST_TYPE] = $this->options;
	}

	/**
	* Save the metaboxes for this custom post type.
	*
	* @since 0.1.1
	*/
	public function save_post($post_id)	{
		// verify if this is an auto save routine. 
		// If it is our form has not been submitted, so we dont want to do anything
		if (get_post_type($post_id) != self::POST_TYPE) return;
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if (isset($_POST['evidence_hub_nonce']) && !wp_verify_nonce($_POST['evidence_hub_nonce'], plugin_basename(__FILE__))) return;
		if (!current_user_can('edit_post', $post_id)) return;

		foreach($this->options as $name => $option)	{
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
	* Add action to add metaboxes
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
		// remove standard Tags boxes
		remove_meta_box('tagsdiv-evidence_hub_country',self::POST_TYPE,'side');
		remove_meta_box('tagsdiv-evidence_hub_sector',self::POST_TYPE,'side');
		remove_meta_box('tagsdiv-evidence_hub_polarity',self::POST_TYPE,'side');
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
	
	/**
	* Add hypothesis column to wp-admin.
	*
	* @since 0.1.1
	*/
	public function columns($columns) {
		return array_slice($columns, 0, 3, true) +
				array('evidence_hub_hypothesis_id' => __( 'Hypothesis' )) +
				array_slice($columns, 3, count($columns) - 1, true) ;
	}
	
	/**
	* Sets text and link for custom columns.
	*
	* @since 0.1.1
	*/	
	public function column($column, $post_id) {
		global $post;
		switch (str_replace('evidence_hub_', '', $column)) {
		case 'hypothesis_id':
			$case_id = get_post_meta( $post_id, $column, true );
			$case_title = get_the_title($case_id);
			if ( empty( $case_title ) )
				echo __( 'Empty' );
			else
				printf( __( '<a href="?post_type=evidence&hyp_id=%s">%s</a>' ), $case_id, ucwords($case_title) );
			break;
		default :
			break;
		}
	}  
} // END class Post_Type_Template
new Evidence_Template();