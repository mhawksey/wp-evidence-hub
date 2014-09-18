<?php
/**
 * Fancy Evidence Map with Bars and Sankey Shortcode
 *
 * Shortcode: [evidence_map]
 * Options: title - boolean|string
 *			no_evidence_message - message used on error
 *			title_tag - tag to wrap title in
 *			do_cache - boolean to disable cache option default: true
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */
 
new Evidence_Hub_Shortcode_Evidence_Map();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Evidence_Map extends Evidence_Hub_Shortcode {
	public $shortcode = 'evidence_map';
	public $defaults = array(
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
		
		/**
		* A lot of this is a fudge to get the data in the right shape for Timo Grossenbacher/Global Oil Presentation.
		* The overall aim is to get a year bin (the actual year is currently ignored) with an array containing: country name,
		* slug, id (country id used in world-110m.json TOPOJson), and evidence counts for +ve/-ve extract shown below. Once we 
		* have this Timo's script (with some modification) does the rest.   
		* var data = {
		* 		  "2013": [
		* 			{
		* 			  "name": "Australia",
		* 			  "slug": "au",
		* 			  "id": "36",
		* 			  "positive": 0,
		* 			  "negative": 1
		* 			},
		* 			{
		* 			  "name": "Belgium",
		* 			  "slug": "be",
		* 			  "id": "56",
		* 			  "positive": 0,
		* 			  "negative": 0
		* 			},
		*			...
		* 		  ]
		* 		} 
		*
		*/
		
		$year = array();
		$graph = array();
		$nodes = array();
		$links = array();
		$totals = array();
		
		// Build a country slug => id lookup by reading csv used in visualisation
		$country_ids = $this->get_country_ids();
		
		$world = array("name"=>"World",
			   "id" => 900,
			   "positive" => 0,
			   "negative" => 0);
		
		// build query to fetch all evidence ids
		$args = array('post_type' => 'evidence', // my custom post type
    				   'posts_per_page' => -1,
					   'post_status' => 'publish',
					   'fields' => 'ids'); // show all posts);
		// add terms and custom fields to posts
		$posts = Evidence_Hub::add_terms(get_posts($args));
		
		// fetch country taxonomy
		$countries = get_terms('evidence_hub_country', array('post_types' =>array('evidence')));
		// fetch polarity taxonomy
		$polarities = get_terms('evidence_hub_polarity');

		// for each country get total pos/neg evidence 
		foreach ($countries as $country){
			$cposts = Evidence_Hub::filterOptions($posts, 'country_slug' , $country->slug);
			$totals = array();
			foreach ($polarities as $polarity){
				$pposts = Evidence_Hub::filterOptions($cposts, 'polarity_slug', $polarity->slug);
				$totals[$polarity->slug] = count($pposts);
			}
			$year[] = array("name" => $country->name,
							"slug" => $country->slug,
							"id" => $country_ids[$country->slug],
							"positive" => $totals['pos'],
							"negative" => $totals['neg']);
			// keep running total of pos/neg
			$world['positive'] = $world['positive'] + $totals['pos'];
			$world['negative'] = $world['negative'] + $totals['neg'];		
		}
		$year[] = $world;
		$data = array("2013" => $year);
		// finally echo all the HTML/JS required
		?>
        <script type="application/javascript">
				/* <![CDATA[ */
		var MyAjax = {
			pluginurl: getPath('<?php echo EVIDENCE_HUB_URL; ?>'),
			apiurl: '<?php $this->print_api_url() ?>',
			ajaxurl: getPath('<?php echo admin_url();?>admin-ajax.php')
		};
		function getPath(url) {
			var a = document.createElement('a');
			a.href = url;
			return a.pathname.charAt(0) != '/' ? '/' + a.pathname : a.pathname;
		}
		var data = <?php echo json_encode($data);?>;
		/* ]]> */
		</script>
        <script src="<?php echo plugins_url( 'lib/map/lib/queue.v1.min.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
        <script src="<?php echo plugins_url( 'lib/map/lib/topojson.v1.min.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
        <script src="<?php echo plugins_url( 'lib/map/lib/colorbrewer.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
        <script src="<?php echo plugins_url( 'lib/map/lib/mootools-core-1.4.5.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
        <script src="<?php echo plugins_url( 'lib/map/lib/mootools-more-1.4.0.1.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
		
        <script src="<?php echo plugins_url( 'lib/map/src/control.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
        <script src="<?php echo plugins_url( 'js/sankey.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
        
        <link rel="stylesheet" href="<?php echo plugins_url( 'lib/map/css/skeleton.css', EVIDENCE_HUB_REGISTER_FILE )?>" />
        <link rel="stylesheet" href="<?php echo plugins_url( 'lib/map/css/styles.css', EVIDENCE_HUB_REGISTER_FILE )?>" />
        <!--[if gte IE 7]>
           <style>svg { height: 450px }</style>
        <![endif]-->
        <!-- main script -->
        <script src="<?php echo plugins_url( 'lib/map/src/main.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
        <!--[if lt IE 9]>
        <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
        <!--[if lte IE 10]>
        <style>
        #fullscreen-button { display:none; };
        </style>
        <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->

        <!-- Mobile-spezifische Metatags
        ================================================== -->
        <!--<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">-->
		
          <div id="evidence-map">
                <div id="loading">
                    Loading...
                </div>

            <header>
                <h1>OER Research Hub - Evidence Map</h1>
            </header>
            <div id="impressum">
                <small>
                    <p>Tested in latest versions of Firefox, Chrome, Safari, and Internet Explorer.</p>
                    <strong>Original Author</strong>:
                    <br/>
                    Timo Grossenbacher (BSc in Geography, University of Zurich)
                    <br/>
                    <strong>Modified By Author</strong>:
                    <br/>
                    Martin Hawksey
                    <br/>
                    <strong>Sources</strong>:
                    <br/>
                    Original Code: <a href="http://labs.wnstnsmth.net/worldoil/">Timo Grossenbacher/Global Oil Presentation</a>
                    <br/>
                    Geodata: <a href="https://github.com/mbostock/topojson/blob/master/examples/world-110m.json">mbostock/topojson</a>            
               </small>    
            </div>
		  </div>
        
        <script>
            window.addEvent('domready', function() {
                init();
                //constructControlPanel('Global Oil Production & Consumption');
                
            });
        </script>
        <div id="fullscreen-button"><a href="#" id="evidence-map-fullscreen">Full Screen</a></div>
		<script src="<?php echo plugins_url( 'lib/map/lib/bigscreen.min.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
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
			jQuery('#impressum').show();
			jQuery('#evidence-map').css('height','100%');
			jQuery('#ui').show();
		}
		function onExitEvidenceMap(){
			jQuery('#impressum').hide();
			jQuery('#evidence-map').css('height','');
			jQuery('#ui').hide();
		}
		</script>
		<?php
		return ob_get_clean();
	}
	
	/**
	* Build a country slug => id lookup.
	*
	* @since 0.1.1
	* @return array 
	*/
	private function get_country_ids(){
		$country_ids = array();
		$handle = fopen(EVIDENCE_HUB_PATH.'/lib/map/data/world-country-names.csv', 'r'); 
		if ($handle) { 
			set_time_limit(0); 		
			//loop through one row at a time 
			while (($rows = fgetcsv($handle, 256, ';')) !== FALSE) 
			{ 
				$country_ids[$rows[2]] = $rows[0];
			} 
			fclose($handle); 
		}
		return $country_ids; 	
	}
}