<?php
/**
 *
 */

new Evidence_Hub_Shortcode_Evidence_Summary();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/shortcode.php'.
class Evidence_Hub_Shortcode_Evidence_Summary extends Evidence_Hub_Shortcode {

	const SHORTCODE = 'evidence_summary';

	var $defaults = array(
		'post_id' => false,
		'post_ids' => false,
		'title' => false,
		'sankey' => true,
		'no_evidence_message' => "There is no evidence yet for this hypothesis",
		'link_post' => true,
		'link_sessions' => true,
		'title_tag' => 'h3',
	);

	

	static $post_types_with_evidence = array('hypothesis');
	
	protected function add_to_page($content) {
		if (in_array(get_post_type(), self::$post_types_with_evidence)) {
			if (is_single()) {
				$content = preg_replace('/(<span id=\"more-[0-9]*\"><\/span>)/', '$1'.do_shortcode('[evidence_summary]').'<h3>Hypothesis Details</h3>', $content, 1); 
			} else {
				$content .= do_shortcode('[evidence_summary sankey=false]');
			}
		}
		return $content;
	}
	
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


    /**
     * @return string
     */
	protected function content() {
		ob_start();
		extract($this->options);
		$post_id = implode(",", $this->options['post_ids']);
		$errors = array();
		?>
        <div class="evidence-list">
            <<?php echo $title_tag; ?>>
                <?php if (!$title) { ?>
                    Evidence 
                <?php } else echo $title; ?>
            </<?php echo $title_tag; ?>>
         <div id="sankey-chart"><?php $this->print_chart_loading_no_support_message() ?></div>
		<?php
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
		$evidence = Evidence_Hub::add_terms(get_posts($args));

		if (!empty($evidence) || !empty($no_evidence_message)) :
			$nodes = array();
			$base_link = get_permalink();
			


            echo '<div id="evidence-balance">'; //html

            $links = $this->print_get_nodes_links($evidence, $nodes, $post_id);

            $this->print_sankey_javascript($sankey, $nodes, $links);
        ?>
        <?php else: ?>
                <p><?php echo $no_evidence_message; ?></p>
        <?php endif; // end of if !empty($evidence) ?>
		<?php echo '</div>'; //html end of evidence-balance ?>
<?php return ob_get_clean();
	} // end of function content


    /**
     * @param array [in/out]
     * @return array Get array of links.
     */
    protected function print_get_nodes_links($evidence, &$nodes, $post_id) {
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
					$nodes[] = array(
							"name" => $polarity->name,
							"url" => $base_link."evidence/polarity/".$polarity->slug,
							"id" => $polarity->slug,
							"type" => "polarity",
							"fill" => json_decode($polarity->description)->fill
					);
					$nodeList[$polarity->name] = 1;
				}
				if (count($pposts) > 0){ 
					$links[] = array("source" => $hposts_title, "target" => $polarity->name, "value" => count($pposts));
				}
				foreach($sectors as $sector){	
					$sposts = Evidence_Hub::filterOptions($pposts, 'sector_slug', $sector->slug);
					if (empty($nodeList[$sector->name])){
						$nodes[] = array(
								"name" => $sector->name,
								"url" => $base_link."sector/".$sector->slug,
								"id" => $sector->slug,
								"type" => "sector",
								"fill" => json_decode($sector->description)->fill
						);
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
     * @return NULL
     */
    protected function print_sankey_javascript($sankey, $nodes, $links) {
        $graph = array('nodes' => $nodes, 'links' => $links); ?>
		<?php if ($sankey == 1): // <-- start of sankey if single ?>
            <script>
            var graph = <?php print_r(json_encode($graph)); ?>;
            var margin = {top: 1, right: 1, bottom: 1, left: 1},
                width = document.getElementById("content").offsetWidth - margin.left - margin.right,
                height = 400 - margin.top - margin.bottom;
            </script>
            <link rel="stylesheet" href="<?php echo plugins_url( 'lib/map/css/styles.css' , EVIDENCE_HUB_REGISTER_FILE )?>" />
            <script src="<?php echo plugins_url( 'js/sankey.js' , EVIDENCE_HUB_REGISTER_FILE )?>"></script>
            <script src="<?php echo plugins_url( 'js/sankey-control.js' , EVIDENCE_HUB_REGISTER_FILE )?>"></script>
        <?php endif; // end of sankey if single and no evidence.
    }

} // end of class
