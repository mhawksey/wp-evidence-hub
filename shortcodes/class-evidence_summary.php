<?php
/**
 * Evidence Summary Shortcode class used to construct shortcodes
 *
 * Generates single hypothesis summary view of evidence
 * Shortcode: [evidence_summary]
 * Options: title - boolean|string
 * 			display_sankey - boolean true to display sankey 
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

new Evidence_Hub_Shortcode_Evidence_Summary();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Evidence_Summary extends Evidence_Hub_Shortcode {
	var $shortcode = 'evidence_summary';
	var $defaults = array(
		'title' => false,
		'display_sankey' => true,
		'no_evidence_message' => "There is no evidence yet for this hypothesis",
		'title_tag' => 'h3',
	);

	static $post_types_with_shortcode = array('hypothesis');
	
	/**
	* Adds shortcode content to named post post types. 
	*
	* @since 0.1.1 
	*/
	function add_to_page($content) {
		if (in_array(get_post_type(), self::$post_types_with_shortcode)) {
			if (is_single()) {
				$content = preg_replace('/(<span id=\"more-[0-9]*\"><\/span>)/', '$1'.do_shortcode('[evidence_summary]').'<h3>Hypothesis Details</h3>', $content, 1); 
			} else {
				$content .= do_shortcode('[evidence_summary display_sankey=0]');
			}
			if (function_exists('the_ratings')){
				$content .= '<div id="postvoting">'.the_ratings('div', $id, false).'</div>';
			}
		}
		return $content;
	}

	/**
	* Generate post content. 
	*
	* @since 0.1.1
	* @return string.
	*/
	function content() {
		ob_start();
		extract($this->options);
		$post_id = get_the_ID();
		$errors = array();
		
		// set and wrap content title?>
        <div class="evidence-list">
            <<?php echo $title_tag; ?>>
                <?php if (!$title) { ?>
                    Evidence 
                <?php } else echo $title; ?>
            </<?php echo $title_tag; ?>>
		<?php
		if ($display_sankey){
			echo '<div id="sankey-chart"></div>';
		}
		// prep query to fetch all evidence post ids associated to hypothesis 
		$args = array('post_type' => 'evidence', // my custom post type
    				   'posts_per_page' => -1,
					   'post_status' => 'publish',
					   'fields' => 'ids',
					   'meta_query' => array(
									array(
										'key' => 'evidence_hub_hypothesis_id',
										'value' => $post_id,
										'compare' => '='
									)
								)); // show all posts);
		// add custom terms and fields
		$evidence = Evidence_Hub::add_terms(get_posts($args));
		
		echo '<div id="evidence-balance">'; //html start evidence-balance
		// if evidence do something with it 
		if (!empty($evidence) || !empty($no_evidence_message)) :
			$nodes = array();
			$base_link = get_permalink();
            
			 // out evidence balance
            $links = $this->print_get_nodes_links($evidence, $nodes, $post_id);
			$graph = array('nodes' => $nodes, 'links' => $links);
			 // out evidence sankey
            if ($display_sankey) : 
				$this->print_sankey_javascript($graph);
			endif;
        else: 
			echo "<p>$no_evidence_message</p>"; //html
		endif; // end of if !empty($evidence)
		echo '</div>'; //html end of evidence-balance
		return ob_get_clean();
	} // end of function content
    
	/**
	* Output evidence balance table pos/neg and return sankey links.
	*
	* @since 0.1.1
	* @param array $evidence posts.
	* @param array &$nodes used in sankey generation.
	* @param string $post_id of hypothesis
	* @return array Get array of links.
	*/
    function print_get_nodes_links($evidence, &$nodes, $post_id) {
        $base_link = get_permalink();
        $links = array();
        $nodesList = array();
		$hposts_title = get_the_title($post_id);
			
		$nodes[] = array("name" => $hposts_title, "url" => $base_link, "id" => $post_id, "type" => "hypothesis" );

        // get polarity and sector terms
			$polarities = get_terms('evidence_hub_polarity', 'hide_empty=0');
			$sectors = get_terms('evidence_hub_sector', 'hide_empty=0');
			foreach ($polarities as $polarity){
				$pposts = Evidence_Hub::filterOptions($evidence, 'polarity_slug', $polarity->slug);
				echo '<div class="evidence-box '.$polarity->slug.'">'; //html 
				echo '<h4>'.$polarity->name.' Evidence ('.count($pposts).')</h4>'; //html
				echo '<ul>'; //html
				if (empty($nodeList[$polarity->name])){
					$nodes[] = array("name" => $polarity->name, "url" => $base_link."evidence/polarity/".$polarity->slug, "id" => $polarity->slug, "type" => "polarity", "fill" => json_decode($polarity->description)->fill);
					$nodeList[$polarity->name] = 1;
				}
				if (count($pposts) > 0){ 
					$links[] = array("source" => $hposts_title, "target" => $polarity->name, "value" => count($pposts));
				}
				foreach($sectors as $sector){	
					$sposts = Evidence_Hub::filterOptions($pposts, 'sector_slug', $sector->slug);
					if (empty($nodeList[$sector->name])){
						$nodes[] = array("name" => $sector->name, "url" => $base_link."sector/".$sector->slug, "id" => $sector->slug, "type" => "sector", "fill" => json_decode($sector->description)->fill);
						$nodeList[$sector->name] = 1;
					}
					if (count($sposts) > 0) {
						$links[] = array("source" => $polarity->name, "target" => $sector->name, "value" => count($sposts));
						echo '<li>'.$sector->name; //html
						echo '<ul>'; //html 
						foreach($sposts as $epost){
							echo '<li><a href="'.get_permalink($epost['ID']).'">'.get_the_title($epost['ID']).'</a></li>'; //html
						}
						echo '</ul>'; //html
						echo '</li>'; //html
					}
				}
				echo '</ul>'; // html
				echo '</div>'; //html end of div evidence-box
			}
        return $links;
    }


	/**
	* Output sankey script.
	*
	* @since 0.1.1
	* @param array $graph of sankey data.
	* @return NULL.
	*/
    function print_sankey_javascript($graph) { ?>
		<script>
        var graph = <?php print_r(json_encode($graph)); ?>;
        var margin = {top: 1, right: 1, bottom: 1, left: 1},
            width = document.getElementById("content").offsetWidth - margin.left - margin.right,
            height = 400 - margin.top - margin.bottom;
        </script>
        <link rel="stylesheet" type="text/css" href="<?php echo plugins_url( 'lib/map/css/styles.css' , EVIDENCE_HUB_REGISTER_FILE )?>" />
        <script src="<?php echo plugins_url( 'js/sankey.js' , EVIDENCE_HUB_REGISTER_FILE )?>"></script>
        <script src="<?php echo plugins_url( 'js/sankey-control.js' , EVIDENCE_HUB_REGISTER_FILE )?>"></script>
    <?php 
    }

} // end of class