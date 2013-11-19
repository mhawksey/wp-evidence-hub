<?php

new Evidence_Hub_Shortcode_Evidence_GeoMap();
class Evidence_Hub_Shortcode_Evidence_GeoMap extends Evidence_Hub_Shortcode {
	var $shortcode = 'evidence_geomap';
	var $defaults = array(
		'post_id' => false,
		'post_ids' => false,
		'title' => false,
		'no_evidence_message' => "There is no evidence map yet to display",
		'link_post' => true,
		'link_sessions' => true,
		'title_tag' => 'h3',
	);

	

	static $post_types_with_evidence = array();
	
	
	function prep_options() {
		// Turn csv into array
		if (!is_array($this->options['post_ids'])) $this->options['post_ids'] = array();
		if (!empty($this->options['post_ids'])) $this->options['post_ids'] = explode(',', $this->options['post_ids']);

		// add post_id to post_ids and get rid of it
		if ($this->options['post_id']) $this->options['post_ids'] = array_merge($this->options['post_ids'], explode(',', $this->options['post_id']));
		unset($this->options['post_id']);
		
		// fallback to current post if nothing specified
		if (empty($this->options['post_ids']) && $GLOBALS['post']->ID) $this->options['post_ids'] = array($GLOBALS['post']->ID);
		
		// unique list
		$this->options['post_ids'] = array_unique($this->options['post_ids']);
	}

	function content() {
		$sub_options = array();
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
		
		$sub_options = array_merge($sub_options, array(
			'type' => array(
				'type' => 'select',
				'save_as' => 'post_meta',
				'label' => "Type",
				'options' => array('evidence' => 'Evidence',
								   'project' => 'Project'),
				),
		));
		$sub_options = array_merge($sub_options, array(
			'hypothesis_id' => array(
				'type' => 'select',
				'save_as' => 'post_meta',
				'label' => "Hypothesis",
				'options' => $hypothesis_options,
				),
		));
		$sub_options = array_merge($sub_options, array(
			'polarity' => array(
				'type' => 'select',
				'save_as' => 'term',
				'label' => "Polarity",
				'options' => get_terms('evidence_hub_polarity', 'hide_empty=0&orderby=id'),
				),
		));
		 $sub_options = array_merge($sub_options, array(
			'sector' => array(
				'type' => 'select',
				'save_as' => 'term',
				'label' => 'Sector',
				'options' => get_terms('evidence_hub_sector', 'hide_empty=0&orderby=id'),
				)
		 ));

		
		ob_start();
		extract($this->options);
		$errors = array();		
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
		
		return ob_get_clean();
	}
}