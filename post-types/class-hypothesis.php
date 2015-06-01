<?php
/**
 * Construct a hypothesis custom post type
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_CustomPostType
 */
new Hypothesis_Template();
class Hypothesis_Template extends Evidence_Hub_CustomPostType {

	const POST_TYPE = 'hypothesis';

	protected $archive_slug = "hypothesis"; // use pluralized string if you want an archive page
	protected $singular = "Hypothesis";
	protected $plural = "Hypotheses";
	protected $options = array();

	public function __construct() {
		parent::__construct();

		if ($this->is_proposition()) {
			$this->archive_slug = "proposition";
			$this->singular = __("Proposition", self::LOC_DOMAIN);
			$this->plural = __("Propositions", self::LOC_DOMAIN);
		}
	}

	/**
	* Register custom post type.
	*
	* @since 0.1.1
	*/
	public function create_post_type(){
		register_post_type($this->get_post_type() ,
			array(
				'labels' => array(
					'name' => __(sprintf('%ss', ucwords(str_replace("_", " ", $this->get_post_type() )))),
					'singular_name' => __(ucwords(str_replace("_", " ", $this->get_post_type() )))
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
'show_in_nav_menus' => true,
				'description' => $this->is_proposition()
					? __('A proposition or hypothesis', self::LOC_DOMAIN)
					: __('A hypothesis', self::LOC_DOMAIN),
				'supports' => array(
					'title', 'editor', 'excerpt', 'author', 'comments'
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
				'has_archive' => $this->archive_slug,
				'rewrite' => array(
					'slug' => $this->archive_slug.'/%post_id%',
					'with_front' => false,
				),
				'menu_position' => 30,
				'menu_icon' => EVIDENCE_HUB_URL.'images/icons/hyp.png',
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
			'rag' => array(
				'type' => 'select',
				'save_as' => 'term',
				'position' => 'side',
				'label' => '<abbr title="'.
					__('Red-Amber-Green', self::LOC_DOMAIN). '">RAG</abbr>',
				'options' => get_terms('evidence_hub_rag', 'hide_empty=0&orderby=id'),
				),
			));
		$this->options = array_merge($this->options, array(
			'key_questions' => array(
				'type' => 'html',
				'save_as' => 'post_meta',
				'position' => 'bottom',
				),
			));
		Evidence_Hub::$post_type_fields[ $this->get_post_type() ] = $this->options;
	}
		
	/**
	* Register custom fields box in wp-admin.
	*
	* @since 0.1.1
	*/
	public function add_meta_boxes() {
		// Add this metabox to every selected post	
		add_meta_box( 
			sprintf('wp_evidence_hub_%s_section', $this->get_post_type() ),
			sprintf('%s Key Questions', $this->singular),
			array(&$this, 'add_inner_meta_boxes'),
			$this->get_post_type() ,
			'normal',          // The part of the page where the edit screen section should be shown.
            'high'
		);
		// Add this metabox to custom post wp-admin
		add_meta_box( 
			sprintf('wp_evidence_hub_%s_side_section', $this->get_post_type() ),
			sprintf('%s Information', $this->singular),
			array(&$this, 'add_inner_meta_boxes_side'),
			$this->get_post_type() ,
			'side'
		);	
		remove_meta_box('tagsdiv-evidence_hub_rag',$this->get_post_type() ,'side');
	} // END public function add_meta_boxes()

	
	/**
	* function to register custom slug hypothesis/%hypothesis_slug%/%post_id%/.
	*
	* @since 0.1.1
	*/
	public function custom_post_type_link($post_link, $post = 0, $leavename = false) {			
		if ($post->post_type == 'hypothesis') {
			return str_replace('%post_id%', $post->ID, $post_link);
		} else {
			return $post_link;
		}
	}
} // END class Post_Type_Template
