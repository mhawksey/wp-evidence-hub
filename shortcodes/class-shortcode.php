<?php
/**
 * Abstract class used to construct shortcodes
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */
 
abstract class Evidence_Hub_Shortcode extends Evidence_Hub_Base {

	const SHORTCODE = 'evidence_hub_shortcode';

	var $defaults = array('do_cache' => true);
	var $options = array();
	
	/**
	* Construct the plugin object.
	*
	* @since 0.1.1
	*/
	public function __construct() {
		add_shortcode( $this->get_shortcode(), array( &$this, 'shortcode' ));
		add_filter('the_content', array(&$this, 'pre_add_to_page'));

		add_action('save_post', array(&$this, 'save_post'));
		add_action('trash_post', array(&$this, 'trash_post'));
		
		//register_activation_hook(EVIDENCE_HUB_REGISTER_FILE, array(&$this, 'activate'));
		//register_deactivation_hook(EVIDENCE_HUB_REGISTER_FILE, array(&$this, 'deactivate'));
		
		global $wpdb;
		$wpdb->evidence_hub_shortcode_cache = $wpdb->prefix.'evidence_hub_shortcode_cache';
	}


	protected function get_shortcode() {
		// Play safe for now! [Bug: #24]
		//return defined( 'static::SHORTCODE' ) ? static::SHORTCODE : $this->shortcode;
		return property_exists( $this, 'shortcode' ) ? $this->shortcode : static::SHORTCODE;
	}

	/**
	* Handles shortcode rendering and caching.
	*
	* @since 0.1.1
	* @param array $options 
	*/	
	public function shortcode($options) {
		$this->options = shortcode_atts($this->defaults, $options);	
		$this->prep_options();
		$this->debug_shortcode( $options );
		if (!$content = $this->get_cache()) {
			$content = $this->content();
			if (!isset($this->options['do_cache'])) {
				$this->cache($content);
			}
		}
		return $content;
	}

	/**
	* WP Hook filter. Intercepts content rendering and adds shortcode as required.
	*
	* @since 0.1.1 
	*/
	public function pre_add_to_page($content) {
		$options = get_option('evidence_hub_options');
		$options['add_to_page'] = 1;
		return $options['add_to_page'] ? $this->add_to_page($content) : $content;
	}
	
	/**
	* Holder for extended classes. 
	*
	* @since 0.1.1 
	*/
	protected function add_to_page($content) {
		return $content;
	}
	
	public function make_meta_bar($post_types_with_shortcode){
		ob_start();
		extract($this->options);
		$errors = array();
		$post_id = get_the_ID();
			$post = Evidence_Hub::add_meta($post_id);
			$post['type'] = get_post_type($post_id);
			if (!$post) {
				$errors[] = "$post_id is not a valid post ID";
			} else if (!in_array($post['type'], $post_types_with_shortcode)) {
				$errors[] = "<a href='".get_permalink($post_id)."'>".get_the_title($post_id)."</a> is not the correct type of post";
			} else if ($location=="header") { 
				$this->meta_bar($post, $header_terms);
			} else if ($location=="footer") { 
	  			$this->meta_bar($post, $footer_terms);
			}
		
		if (count($errors)) return "[Shortcode errors (". $this->get_shortcode() ."): ".implode(', ', $errors)."]";	
		return ob_get_clean();
	}
	
	/**
	* Renders metadata assocated with custom postype. 
	*
	* @since 0.1.1
	* @param object $post single post object which has been through Evidence_Hub::add_meta.
	* @param array $options passed from shortcode parameters.
	*/
	public function meta_bar($post, $options){
		$out = array();
		
		// shorcode uses comma separated list of field ids to include in bar
		foreach (explode(',', $options) as $type) {
			$type = trim($type);
			$slug = $type."_slug";
			// if there is a slug then a taxonomy term
			if (isset($post[$type]) && isset($post[$slug])){
				$out[] = get_the_term_list( $post['ID'], "evidence_hub_".$type, ucwords(str_replace("_", " ",$type)).": ", ", ");
			// else it's a custom field
			} else {
				$out[] = $this->get_custom_field($post, $type);
			}
		}
		// remove NULL
		$out = array_filter($out); 
		if(!empty($out)){ 
			echo '<div id="evidence-meta">'.implode(" | ", $out).'</div>';
       }	
	}
	
	/**
	* Handle custom fields rendering. 
	*
	* @since 0.1.1
	* @param object $post single post object which has been through Evidence_Hub::add_meta.
	* @param string $type field name.
	* @return string.
	*/
	public function get_custom_field($post, $type){
		// if field name not in post throw back null
		if (!isset($post[$type]) || $post[$type] == ""){
			return NULL;
		}
		// handle hypothesis as related value
		if ($type == 'hypothesis_id') {
			return  __(sprintf('<span class="meta_label">Hypothesis</span>: <a href="%s">%s</a>', get_permalink($post[$type]), get_the_title($post[$type])));
		// handle post_type
		} elseif($type == "type" ) {
			return __(sprintf('<span class="meta_label">Type</span>: <a href="%s">%s</a>', get_post_type_archive_link($post[$type]), ucwords($post[$type])));
		// special case for links	
		} elseif(isset($post[$type]) && ('citation' == $type || 'resource_link' == $type || 'url' == $type)) {
			// if valid link wrap in href
			if (filter_var($post[$type], FILTER_VALIDATE_URL) === FALSE) {
				return "<span class='$type'>" .
				__(sprintf('<span class="meta_label">%s</span>: %s', ucwords(str_replace("_", " ",$type)),$post[$type]))
				. '</span>';
			} else {
				return "<span class='$type'>" .
				__(sprintf('<span class="meta_label">%s</span>: <a href="%s">%s</a>', ucwords(str_replace("_", " ",$type)),$post[$type],$post[$type]))
				. '</span>';
			}
		// final case all other custom fields
		} elseif (isset($post[$type])) {
			return __(sprintf('<span class="meta_label">%s</span>: %s', ucwords(str_replace("_", " ",$type)),$post[$type]));
		}
		return NULL;
	}
	
	/**
	* Turns options into booleans. 
	*
	* @since 0.1.1
	*/
	protected function prep_options() {
		foreach ($this->options as $key => $value) {
			if (is_string($value)) {
				if ($value == 'true') $this->options[$key] = true;
				if ($value == 'false') $this->options[$key] = false;
			}
		}
		if (!isset($this->options['post_id']) && isset($GLOBALS['post'])) {
			$this->options['post_id'] = $GLOBALS['post']->ID;
		}
	}

	/**
	* @return string
	*/
	abstract protected function content();


	// Ajax configuration, SVG logo etc. --------------------------------------

	/** Print the `MyAjax` Javascript configuration object.
	*   Include SVG logo path... [Bug: #6]
	*/
	protected function print_myajax_config_javascript() { ?>
		var MyAjax = {
			pluginurl: getPath('<?php echo EVIDENCE_HUB_URL; ?>'),
			apiurl: '<?php echo $this->get_api_url() ?>',
			ajaxurl: getPath('<?php echo admin_url() ?>admin-ajax.php'),
			svg_logo:  <?php $this->json_option( 'wp_evidence_hub_svg_logo', 'images/oer-evidence-hub-logo.svg' )?>,
			svg_scale: <?php echo $this->get_option( 'wp_evidence_hub_svg_logo_scale', '[ 0.7, 0.7 ]' );  #$this->json_option(... array(0.7, 0.7)) ?>
		};
		function getPath(url) {
			var a = document.createElement('a');
			a.href = url;
			return a.pathname.charAt(0) != '/' ? '/' + a.pathname : a.pathname;
		}
<?php
	}

	/** Print custom SVG CSS styles [Bug: #6].
	*/
	protected function print_custom_svg_style() { ?>
		<style id="custom-svg-style">
		<?php echo $this->get_option('wp_evidence_hub_svg_style') ?>
		</style>
<?php
	}

	/** Output the site's API URL to the `MyAjax` Javascript object [Bug: #4].
	 * @param string $method API method, eg. 'hub.get_geojson'.
	 * @return string URL.
	 */
	protected function get_api_url( $method = NULL) {
		$is_permalink = get_option( 'permalink_structure' );
		$url = site_url() .'/'.
			($is_permalink ? get_option( 'json_api_base', 'api' ) .'/%s/?' : '?json=%s&' );
		if ($method) {
			return str_replace( '%s', $method, $url );
		}
		return $url;
	}

	/** Safely get json-decoded properties, eg. SVG fill colour [Bug #18].
	* `sector.taxonomy = "evidence_hub_sector"`
	*/
	protected static function json_get( $json, $prop, $default = '', $is_sector_fill = TRUE ) {
		static $error_count = 0;
		$obj = json_decode( $json );
		if (isset($obj->{ $prop })) {
			return $obj->{ $prop };
		}
		elseif ($is_sector_fill && $error_count < 1) {
			static::error("ERROR. Missing SVG fill in 'sector' tax. description!");
		}
		$error_count++;
		return is_string( $obj ) ? $obj . $default : $default;
	}

	/** Print a JSON-encoded config. option */
	protected function json_option( $option, $default = NULL ) {
		echo json_encode($this->get_option( $option, $default ));
	}

	/** Safely output a local URL/file containing JSON [Bug: #13].
	 * @param string $url
	 */
	protected function print_json_file( $url ) {
		$result = print_r(file_get_contents( $url ), $return = TRUE);
		$test = json_decode( $result );
		if (!$test || !$result || preg_match( '/^</', $result )) {
			$result = "'ERROR, not JSON: $url'; window.console && console.log('Error, not JSON', '$url');";
		}
		echo $result;
	}

	/** Output JSON data containing, eg. dashes as '&8211;' [Bug: #23].
	*/
	protected function print_json_data( $obj ) {
		echo str_replace( '&#8211;', '—', html_entity_decode(json_encode( $obj ), ENT_NOQUOTES ));
	}

	/** Output a message for Internet Explorer <= 8. And a "Loading..." message [Bug: #8].
	*/
	protected function print_chart_loading_no_support_message( $is_map = FALSE, $is_partial = FALSE ) {
		if ($is_partial) {
			$message = 'Unfortunately, not all functionality will work in older browsers.';
		} elseif ($is_map) {
			$message = 'Unfortunately, the map won\'t display in older browsers.';
		} else {
			$message = 'Unfortunately, the chart won\'t display in older browsers.';
		}
		?>
<!--[if lte IE 8]>
	<div class="oer-chart-no-js">
		<p><?php echo $message ?> Please
		<a href="http://whatbrowser.org/">try a different browser</a>.</p>
	</div>
<![endif]-->
	<div id="loading" class="oer-chart-loading"> Loading... </div>
<?php
	}

	/** Output the markup and Javascript for a fullscreen button [Bug: #8].
	*/
	protected function print_fullscreen_button_html_javascript() { ?>
	<!--[if lte IE 10]>
		<style> #fullscreen-button { display:none; } </style>
	<![endif]-->
		<div id="fullscreen-button" class="map-controls">
		  <!--[Bug: #40]-->
		  <a href="#" id="map-reset-button" onclick="document.location.reload();return false"><i class="el-icon-refresh x-el-icon-zoom-out"></i>Refresh map</a> <i class=sep ></i>
		  <a href="#" id="evidence-map-fullscreen"><i class=el-icon-fullscreen ></i>Full Screen</a>
		</div>
		<script src="<?php echo plugins_url( 'lib/map/lib/bigscreen.min.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
		<script>
		var element = document.getElementById('evidence-map');
		jQuery('#evidence-map-fullscreen').on('click', function () {
			if (BigScreen.enabled) {
				BigScreen.request(element, onEnterEvidenceMap, onExitEvidenceMap);
				// You could also use .toggle(element, onEnter, onExit, onError)
			}
			else {
				// fallback for browsers that don't support full screen
			}
		});

		// called when the first element enters full screen

		function onEnterEvidenceMap(){
			jQuery('#evidence-map').css('height', '100%');
			jQuery('#map').css('height', jQuery('#evidence-map').height());
			map.invalidateSize();
		}
		function onExitEvidenceMap(){
			jQuery('#evidence-map').css('height', '');
			jQuery('#map').css('height', parseInt(jQuery('#evidence-map').width() * 9/16));
			map.invalidateSize();
		}
		</script>
<?php
	}

    /** Output Javascript with configuration options [Bug: #49]
    *
    * @param string $js_key Based on eg. shortcode.
    * @param mixed  $js_value
    */
    protected function print_js_config( $js_key, $js_value, $with_el = false ) {
        // Sanitize data - ensure $js_key is a string ...
        $js_value = json_encode( $js_value );

        if($with_el): ?><script>
<?php endif; ?>
var OERRH = OERRH || {};
OERRH.<?php echo $js_key ?> = <?php echo $js_value ?>;
<?php if($with_el): ?></script><?php endif;
    }

	/** Output [geomap] Javascript configuration [Bug: #47]
	*/
    protected function print_leaflet_geomap_options_javascript() {
        $map_center = $this->decode_option( 'evidence_geomap_center', '[25, 0]' );
        $this->print_js_config( 'geomap', array(
		    'center' => $map_center,
		    'filter_position' => $this->get_option(
		        'evidence_geomap_filter_position', 'topright' ), # Filter by "Type"...
		    'summary_position' => $this->get_option(
		        'evidence_geomap_summary_position', 'bottomleft' ), # AKA "search"
		    'attribution' => ' '. $this->get_option(
		        'evidence_geomap_attribution' ),
		    'no_location_latlng' => $this->decode_option(
		        'evidence_geomap_no_location_latlng' ),  // [Bug: #50]
		));
    }


	/** Put Evidence Hub shortcodes used on a page in the Javascript console [Bug: #9].
	*/
	protected function debug_shortcode( $options = NULL ) {
		$shortcode = $this->get_shortcode();
		$js_options = json_encode( $options ? $options : '[no options]' );

		if (headers_sent()): ?>
		<script>
		window.console && console.log('X-WP-Shortcode: "<?php echo $shortcode .'"\', '. $js_options ?>);
		</script>
		<?php
		else:
			header( 'X-Evidence-Hub-Shortcode: '. $shortcode .'; input='. $js_options );
		endif;
	}

	/* See: `Evidence_Hub_Base` class
	*
	protected function debug( $obj .. ) { .. } */



	// Caching ----------------------------------------------------------------

	// TODO: doesn't $wpdb need to be globalized in this function?
	/**
	* Create table for cached shortcodes. 
	*
	* @since 0.1.1
	*/
	public function activate() {
		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		dbDelta("CREATE TABLE $wpdb->evidence_hub_shortcode_cache (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			shortcode text NOT NULL,
			options text NOT NULL,
			content mediumtext NOT NULL,
			UNIQUE KEY id(id)
		);");
	}
	
	/**
	* Drop table for cached shortcodes. 
	*
	* @since 0.1.1
	*/
	public function deactivate() {
		global $wpdb;
		$wpdb->query("drop table $wpdb->evidence_hub_shortcode_cache");
	}
	
	/**
	* Hooks WP save process. Entire cache cleared on custom post type save. 
	*
	* @since 0.1.1
	* @param string $post_id
	*/
	public function save_post($post_id) {
		if (!in_array(get_post_type($post_id), Evidence_Hub::$post_types)) return;
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		self::clear_cache();
	}

	/**
	* Hooks WP trash process. Entire cache cleared on custom post type save. 
	*
	* @since 0.1.1
	* @param string $post_id
	*/	
	public function trash_post($post_id) {
		if (!in_array(get_post_type($post_id), Evidence_Hub::$post_types)) return;
		self::clear_cache();
	}
	
	/**
	* If caching is enabled fetch cached value. 
	*
	* @since 0.1.1
	*/		
	protected function get_cache() {
		if (!get_option('evidence_hub_caching')) return false;
		
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare(
			"SELECT content
			from $wpdb->evidence_hub_shortcode_cache
			where shortcode = %s
			and options = %s",
			$this->get_shortcode(),
			serialize($this->options)
		));
	}
	
	/**
	* If caching is enabled save content to cache. 
	*
	* @since 0.1.1
	* @param string $content
	*/	
	protected function cache($content) {
		if (!get_option('evidence_hub_caching')) return false;
		
		global $wpdb;
		$wpdb->insert($wpdb->evidence_hub_shortcode_cache, array(
			'created' => current_time('mysql'),
			'shortcode' => $this->get_shortcode(),
			'options' => serialize($this->options),
			'content' => $content,
		));
	}
	
	/**
	* Get count of cached shortcode items for object. 
	*
	* @since 0.1.1
	* @return string cache count
	*/
	public static function get_all_cache() {
		global $wpdb;
		return $wpdb->get_results("SELECT shortcode, count(id) AS count FROM $wpdb->evidence_hub_shortcode_cache GROUP BY shortcode", OBJECT);
	}
	
	/**
	* Clear cache. 
	*
	* @since 0.1.1
	*/
	public static function clear_cache() {
		global $wpdb;
		$wpdb->query("TRUNCATE $wpdb->evidence_hub_shortcode_cache");
	}
}