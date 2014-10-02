<?php
/**
 * Construct a detailed map using LeafletJS
 * 
 * Shortcode: [geomap]
 * Options: type - string comma list of types to map
 *          do_cache - boolean to disable cache option default: true
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */
new Evidence_Hub_Shortcode_GeoMap();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_GeoMap extends Evidence_Hub_Shortcode {

	const SHORTCODE = 'geomap';

	var $defaults = array(
		'title' => false,
		'no_evidence_message' => "There is no map yet to display",
		'title_tag' => 'h3',
		'type' => 'evidence',
		'table' => true
	);

	static $post_types_with_shortcode = array();
	
	/**
	* Generate post content.
	*
	* @since 0.1.1
	* @return string.
	*/
	protected function content() {
		ob_start();
		extract($this->options);
		$errors = array();	
		$sub_options = array();
		$hypothesis_options = array();
		$types = explode(',', $this->options['type']);
		
		$types_array = array();
		foreach ($types as $i => $value) {
			$types_array[strtolower($value)] = ucwords($value);
		}

		
		// build dropdown filters for map 
		// type
		$sub_options = array_merge($sub_options, array(
			'type' => array(
				'type' => 'select',
				'save_as' => 'post_meta',
				'label' => "Type",
				'options' => $types_array,
				),
		));
		
		if (in_array('evidence', $types)){
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
		}
		
		if (in_array('policy', $types)){
			$sub_options = array_merge($sub_options, array(
				'locale' => array(
					'type' => 'select',
					'save_as' => 'term',
					'label' => 'Locale',
					'options' => get_terms('evidence_hub_locale', 'hide_empty=0&orderby=id'),
					)
			 ));
		}
	
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
		<?php /* Ensure only one version of Leaflet.JS is included [Bug: #25]
        <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7/leaflet.css" />
         <script src="http://cdn.leafletjs.com/leaflet-0.7/leaflet.js"></script>
		*/ ?>
         <div id="evidence-map">
            <div id="map"><?php $this->print_chart_loading_no_support_message( $is_map = TRUE ) ?></div>
            <?php $post = NULL; include(sprintf("%s/post-types/custom_post_metaboxes.php", EVIDENCE_HUB_PATH));?>
         </div>
         <script>
		 /* <![CDATA[ */
			var json = <?php $this->print_json_file($this->get_api_url( 'hub.get_geojson' ) .'count=-1&type='. strtolower($type)) ?>;	
			var hubPoints = json['geoJSON'] || null;
			var pluginurl = '<?php echo EVIDENCE_HUB_URL; ?>';
			var h = (jQuery('#evidence-map').width() > 820) ? parseInt(jQuery('#evidence-map').width()*9/16) : 560;
			jQuery('#map').css('height', h);	
		/* ]]> */
		</script>
        <link rel="stylesheet" href="<?php echo plugins_url( 'js/markercluster/MarkerCluster.css' , EVIDENCE_HUB_REGISTER_FILE )?>" />
        <link rel="stylesheet" href="<?php echo plugins_url( 'js/markercluster/MarkerCluster.Default.css' , EVIDENCE_HUB_REGISTER_FILE )?>" />
        <script src="<?php echo plugins_url( 'js/markercluster/leaflet.markercluster-src.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
		<script src="<?php echo plugins_url( 'js/leaflet-map.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>

		<?php $this->print_fullscreen_button_html_javascript() ?>
		<script>
		jQuery("#eh-form").appendTo(".my-custom-control");
		jQuery('#evidence-map fieldset').show();
		</script>
		<?php
		if($table){
			$this->renderGoogleTable();	
		}
		// <<html dump	
		return ob_get_clean();
	}

	function renderGoogleTable() { ?>
        <script>
          google.load('visualization', '1.1', { packages: [ 'controls' ] });
        </script>
        <script>
		var data, table;
		var pickers = {};;
		var c = [];
		var summaryControl = L.Control.extend({
			options: {
				position: 'bottomleft'
			},

			onAdd: function (map) {
				// create the control container with a particular class name
				var controlDiv = L.DomUtil.create('div', 'summary-table-block');
				controlDiv.innerHTML = "<div id='tbl-holder'><div class='tbl-header'>Results (<span id='result-count'></span>) <div class='expander'>▼</div></div><div id='summary-table'><div id='control1'></div><div id='table1'></div></div></div>";	
				L.DomEvent.disableClickPropagation(controlDiv);
				return controlDiv;
			}
		});
		map.addControl(new summaryControl());

		function drawVisualization() {
			// Prepare the data.
			
			data = google.visualization.arrayToDataTable(tableArray, false);
			for (i=0; i<data.getNumberOfColumns(); i++){
				c[data.getColumnLabel(i)] = i;
			} 

			var formatter = new google.visualization.PatternFormat('<div>{1} - <span style="text-transform: capitalize;">{2}</span><br/><a href="{0}">Read more..</a></div></div>');
			formatter.format(data, [c['url'],c['name'], c['type']], c['desc']);
	   
			// Define a StringFilter control for the 'Name' column
			var stringFilter = new google.visualization.ControlWrapper({
			  'controlType': 'StringFilter',
			  'containerId': 'control1',
			  'options': {
				'filterColumnIndex': c['name'],
				'ui': {'label': 'Search',}
			  }
			});
			jQuery('#evidence-map select').each(function(i,v) {
				var name = v.id.substring(13)
				pickers[name] = picker(name);
				v.addEventListener(
							 'change',
							 function() {
								pickers[name].setState({value: this.value});
								pickers[name].draw();
								document.getElementById('result-count').innerHTML = table.getDataTable().getNumberOfRows();
							 },
							 false
						  );
			});
		  	var cssClassNames = {headerRow: 'tbl-head', 
								 headerCell: 'tbl-head',
								 tableRow: 'tbl-row',
								 oddTableRow: 'tbl-row'};
			// Define a table visualization
			table = new google.visualization.ChartWrapper({
			  'chartType': 'Table',
			  'containerId': 'table1',
			  'options': {'height': '300px', 
						  'width': '22em',
						  //'page': 'enable',
						  //'pageSize': 5,
						  'allowHtml': true,
						  'pagingSymbols': {prev: 'prev', next: 'next'},
						  'pagingButtonsConfiguration': 'auto',
						  'cssClassNames': cssClassNames},
			  'view': {'columns': [c['desc']]}
			});

			google.visualization.events.addListener(table, 'ready', onReady);
			google.visualization.events.addListener(stringFilter, 'statechange', function () {
				var state = stringFilter.getState();
				document.getElementById('result-count').innerHTML = table.getDataTable().getNumberOfRows();
			});
		  
			// Create the dashboard.
			var dashboard = new google.visualization.Dashboard(document.getElementById('summary-table')).
			  // Configure the string filter to affect the table contents
			  bind(stringFilter, table);
			for (pick in pickers){
				dashboard.bind(pickers[pick], table);
			}
			  //bind(pickers['type'], table).
			  // Draw the dashboard
			  dashboard.draw(data);
			  
			  document.getElementById('tbl-holder').style.display = 'block';
		  }
		  function onReady(){
			  google.visualization.events.addListener(table.getChart() , 'select', function(){
				var sel = table.getChart().getSelection();
				map.setZoom(3);
				var curID = table.getDataTable().getValue(sel[0].row, c['id']);
				var currentMarker = markerMap[curID];
				setTimeout(function() {  markers.zoomToShowLayer(currentMarker, function(){
					currentMarker.openPopup();
				});}, 1000);
				table.getChart().setSelection(null);
				event.preventDefault();
				return false;
			});
			document.getElementById('result-count').innerHTML = table.getDataTable().getNumberOfRows();
		  }

		  function picker(type){
			var newdiv = document.createElement('div');
			newdiv.setAttribute('id','control-'+type);
			newdiv.setAttribute('style', 'display:none');
			document.getElementById('summary-table').appendChild(newdiv);
			
			return new google.visualization.ControlWrapper({
				'controlType': 'StringFilter',
				'containerId': 'control-'+type,
				'options': {
				  'filterColumnIndex': c[type],
				  'ui': {
					'allowTyping': false,
				  }
				},
			});   
		  }
		  google.setOnLoadCallback(drawVisualization);
	  </script>
 <?php }
}