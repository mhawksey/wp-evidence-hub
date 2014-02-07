<?php
/**
 * Construct a detailed map of evidence using LeafletJS
 * 
 * Shortcode: [evidence_geomap]
 * Options: do_cache - boolean to disable cache option default: true
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */
new Evidence_Hub_Shortcode_Evidence_GeoMap();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Evidence_GeoMap extends Evidence_Hub_Shortcode {
	var $shortcode = 'evidence_geomap';
	var $defaults = array(
		'title' => false,
		'no_evidence_message' => "There is no evidence map yet to display",
		'title_tag' => 'h3',
	);

	static $post_types_with_shortcode = array();
	
	/**
	* Generate post content.
	*
	* @since 0.1.1
	* @return string.
	*/
	function content() {
		ob_start();
		extract($this->options);
		$errors = array();	
		$sub_options = array();
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
		
		// build dropdown filters for map 
		// type
		$sub_options = array_merge($sub_options, array(
			'type' => array(
				'type' => 'select',
				'save_as' => 'post_meta',
				'label' => "Type",
				'options' => array('evidence' => 'Evidence',
								   'project' => 'Project'),
				),
		));
		// hypothesis
		$sub_options = array_merge($sub_options, array(
			'hypothesis_id' => array(
				'type' => 'select',
				'save_as' => 'post_meta',
				'label' => "Hypothesis",
				'options' => $hypothesis_options,
				),
		));
		// polarity
		$sub_options = array_merge($sub_options, array(
			'polarity' => array(
				'type' => 'select',
				'save_as' => 'term',
				'label' => "Polarity",
				'options' => get_terms('evidence_hub_polarity', 'hide_empty=0&orderby=id'),
				),
		));
		// sector
		$sub_options = array_merge($sub_options, array(
			'sector' => array(
				'type' => 'select',
				'save_as' => 'term',
				'label' => 'Sector',
				'options' => get_terms('evidence_hub_sector', 'hide_empty=0&orderby=id'),
				)
		 ));
		//html dump>>
		?>
        <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.6.4/leaflet.css" />
         <!--[if lte IE 8]>
             <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.6.4/leaflet.ie.css" />
         <![endif]-->
         <!--[if lte IE 10]>
        <style>
        #fullscreen-button { display:none; };
        </style>
        <![endif]-->
         <script src="http://cdn.leafletjs.com/leaflet-0.6.4/leaflet.js"></script>
         <div id="evidence-map">
         	<?php $post = NULL; include(sprintf("%s/post-types/custom_post_metaboxes.php", EVIDENCE_HUB_PATH));?>
         	<div id="map"></div>
            
         </div>
         <div id="fullscreen-button"><a href="#" id="evidence-map-fullscreen">Full Screen</a></div>
         <script type="application/javascript">
		 /* <![CDATA[ */	
		 	var json = <?php print_r(file_get_contents(site_url().'/'.get_option('json_api_base', 'api').'/hub/get_geojson/?count=-1&type=evidence,project')); ?>;	
			var hubPoints = json['geoJSON'] || null;
			var pluginurl = '<?php echo EVIDENCE_HUB_URL; ?>';
			jQuery('#map').css('height', parseInt(jQuery('#evidence-map').width()*9/16));		
		/* ]]> */
		</script>
        <script src="<?php echo plugins_url( 'js/oms.min.js' , EVIDENCE_HUB_REGISTER_FILE )?>" type="text/javascript" charset="utf-8"></script>
		<script src="<?php echo plugins_url( 'js/leaflet-map.js' , EVIDENCE_HUB_REGISTER_FILE )?>" type="text/javascript" charset="utf-8"></script>
		<script src="<?php echo plugins_url( 'lib/map/lib/bigscreen.min.js' , EVIDENCE_HUB_REGISTER_FILE )?>" type="text/javascript" charset="utf-8"></script>
		<script>
		var element = document.getElementById('evidence-map');
		document.getElementById('evidence-map-fullscreen').addEventListener('click', function() {
			if (BigScreen.enabled) {
				BigScreen.request(element, onEnterEvidenceMap, onExitEvidenceMap);
				// You could also use .toggle(element, onEnter, onExit, onError)
			}
			else {
				// fallback for browsers that don't support full screen
			}
		}, false);
		
			// called when the first element enters full screen
		
		function onEnterEvidenceMap(){
			jQuery('#evidence-map').css('height','100%');
			jQuery('#map').css('height', jQuery('#evidence-map').height());
			map.invalidateSize();
		}
		function onExitEvidenceMap(){
			jQuery('#evidence-map').css('height','');
			jQuery('#map').css('height', parseInt(jQuery('#evidence-map').width()*9/16));
			map.invalidateSize();
		}
		</script>
		<?php
		// <<html dump	
		return ob_get_clean();
	}
}