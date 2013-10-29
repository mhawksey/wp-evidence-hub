<?php

new Evidence_Hub_Shortcode_Evidence_Map();
class Evidence_Hub_Shortcode_Evidence_Map extends Evidence_Hub_Shortcode {
	var $shortcode = 'evidence_map';
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
		ob_start();
		extract($this->options);
		$errors = array();	
		?>
        <script type="application/javascript">
				/* <![CDATA[ */
		var MyAjax = {
			pluginurl: "<?php echo EVIDENCE_HUB_URL; ?>",
			ajaxurl: "<?php echo admin_url();?>admin-ajax.php"
		};
		/* ]]> */
		<?php 
		$handle = fopen(EVIDENCE_HUB_PATH.'/lib/map/data/world-country-names.csv', 'r'); 
		$country_ids = array();
		if ($handle) 
		{ 
			set_time_limit(0); 		
			//loop through one row at a time 
			while (($rows = fgetcsv($handle, 256, ';')) !== FALSE) 
			{ 
				$country_ids[$rows[2]] = $rows[0];
			} 
			fclose($handle); 
		} 
	
		$args = array('post_type' => 'evidence', // my custom post type
    				   'posts_per_page' => -1,
					   'post_status' => 'publish',
					   'fields' => 'ids'); // show all posts);
		
		$year = array();
		
		$posts = Evidence_Hub::add_terms(get_posts($args));
		$countries = get_terms('evidence_hub_country');
		$polarities = get_terms('evidence_hub_polarity');
		$args['post_type'] = 'hypothesis';
		$hypotheses = get_posts($args);
		$sectors = get_terms('evidence_hub_sector');
		
		$graph = array();
		$nodes = array();
		$links = array();
		$totals = array();
		/*
		foreach ($countries as $country){
			$cposts = Evidence_Hub::filterOptions($posts, 'country_slug' , $country->slug);
			$totals[$country->slug] = array('type'=>'country', 'count' => count($cposts));
			foreach($hypotheses as $hypothesis){
				$hposts = Evidence_Hub::filterOptions($cposts, 'hypothesis_id', $hypothesis);
				$hposts_title = get_the_title($hypothesis);
				$totals[$country->slug][$hposts_title] = array('type'=>'hypothesis', 'count' => count($hposts));
				foreach ($polarities as $polarity){
					$pposts = Evidence_Hub::filterOptions($hposts, 'polarity_slug', $polarity->slug);
					$totals[$country->slug][$hposts_title][$polarity->slug] = array('type'=>'polarity', 'count' => count($pposts));
					foreach($sectors as $sector){
						$sposts = Evidence_Hub::filterOptions($pposts, 'sector_slug', $sector->slug);
						$totals[$country->slug][$hposts_title][$polarity->slug][$sector->slug] = array('type'=>'sector', 'count' => count($sposts));		
					}
				}
			}			
		}
		*/
		$world = array("name"=>"World",
					   "id" => 900,
					   "positive" => 0,
					   "negative" => 0);
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
			$world['positive'] = $world['positive'] + $totals['pos'];
			$world['negative'] = $world['negative'] + $totals['neg'];		
		}
		$year[] = $world;
		$data = array("2013" => $year);
		print_r("var data = ".json_encode($data).";")
		
		?>
		</script>
        
        <!-- <iframe id="evidence-map" src="<?php echo plugins_url( 'lib/map/index.php' , EVIDENCE_HUB_REGISTER_FILE )?>" style="width:100%; height:300px" webkitallowfullscreen mozallowfullscreen allowfullscreen scrolling="no"></iframe> -->
        <script src="<?php echo plugins_url( 'lib/map/lib/queue.v1.min.js' , EVIDENCE_HUB_REGISTER_FILE )?>" type="text/javascript" charset="utf-8"></script>
        <script src="<?php echo plugins_url( 'lib/map/lib/topojson.v1.min.js' , EVIDENCE_HUB_REGISTER_FILE )?>" type="text/javascript" charset="utf-8"></script>
        <script src="<?php echo plugins_url( 'lib/map/lib/colorbrewer.js' , EVIDENCE_HUB_REGISTER_FILE )?>" type="text/javascript" charset="utf-8"></script>
        <script src="<?php echo plugins_url( 'lib/map/lib/mootools-core-1.4.5.js' , EVIDENCE_HUB_REGISTER_FILE )?>" type="text/javascript" charset="utf-8"></script>
        <script src="<?php echo plugins_url( 'lib/map/lib/mootools-more-1.4.0.1.js' , EVIDENCE_HUB_REGISTER_FILE )?>" type="text/javascript" charset="utf-8"></script>
		
        <script src="<?php echo plugins_url( 'lib/map/src/control.js' , EVIDENCE_HUB_REGISTER_FILE )?>" type="text/javascript" charset="utf-8"></script>
        <script src="<?php echo plugins_url( 'js/sankey.js' , EVIDENCE_HUB_REGISTER_FILE )?>" type="text/javascript" charset="utf-8"></script>

        <!-- main script -->
        <script src="<?php echo plugins_url( 'lib/map/src/main.js' , EVIDENCE_HUB_REGISTER_FILE )?>" type="text/javascript" charset="utf-8"></script>

        <link rel="stylesheet" type="text/css" href="<?php echo plugins_url( 'lib/map/css/skeleton.css' , EVIDENCE_HUB_REGISTER_FILE )?>" />
        <link rel="stylesheet" type="text/css" href="<?php echo plugins_url( 'lib/map/css/styles.css' , EVIDENCE_HUB_REGISTER_FILE )?>" />
        <!--[if lt IE 9]>
        <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->

        <!-- Mobile-spezifische Metatags
        ================================================== -->
        <!--<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">-->
		<style>
		 
		.node rect {
		  /*cursor: move;*/
		  fill-opacity: .8;
		  shape-rendering: crispEdges;
		}
		 
		.node text {
		  pointer-events: none;
		  text-shadow: 0 1px 0 #fff;
		  font-size:11px;
		  font-family: "Open Sans", Helvetica, Arial, "Nimbus Sans L", sans-serif;
		}
		.node text.hide {
		  display:none;
		}
		 
		.link {
		  fill: none;
		  stroke: #000;
		  stroke-opacity: .2;
		}
		 
		.link:hover {
		  stroke-opacity: .5;
		}

		 
		</style>
		
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
        
        <script type="text/javascript">
            window.addEvent('domready', function() {
                init();
                //constructControlPanel('Global Oil Production & Consumption');
                
            });
        </script>
        <div id="fullscreen-button"><a href="#" id="evidence-map-fullscreen">Full Screen</a></div>
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
		<?	
		return ob_get_clean();
	}
}