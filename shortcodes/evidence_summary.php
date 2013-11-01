<?php

new Evidence_Hub_Shortcode_Evidence_Summary();
class Evidence_Hub_Shortcode_Evidence_Summary extends Evidence_Hub_Shortcode {
	var $shortcode = 'evidence_summary';
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
	
	function add_to_page($content) {
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

	function content() {
		ob_start();
		extract($this->options);
		
		$errors = array();
		?>
		<div class="evidence-list">
				<<?php echo $title_tag; ?>>
					<?php if (!$title) { ?>
						Evidence 
					<?php } else echo $title; ?>
				</<?php echo $title_tag; ?>>
 		<div id="sankey-chart"></div>
		<?
		if (empty($post_ids)) $errors[] = "No posts ID provided";
		foreach ($post_ids as $post_id) {
			$post = get_post($post_id);
			
			if (!$post) {
				$errors[] = "$post_id is not a valid post ID";
			} else if (!in_array(get_post_type($post), self::$post_types_with_evidence)) {
				$errors[] = "<a href='".get_permalink($post->ID)."'>$post->post_title</a> is not the correct type of post";
			}
		}
		
		if (count($errors)) return "[Shortcode errors (".$this->shortcode."): ".implode(', ', $errors)."]";
		
		$evidence = Evidence_Hub::get_evidence($post_ids);
						
		if (!empty($evidence) || !empty($no_evidence_message)) { 

				$graph = array();
				$nodes = array();
				$links = array();
				$base_link = get_permalink();
				$totals = array();
				
				$nodes[] = array("name" => get_the_title(), "url" => $base_link, "id" => get_the_ID(), "type" => "hypothesis" );
				$pol_terms = get_terms('evidence_hub_polarity', 'hide_empty=0&orderby=id');
				$sec_terms = get_terms('evidence_hub_sector', 'hide_empty=0&orderby=id');
				foreach ( $pol_terms as $term ) {
					$nodes[] = array("name" => $term->name, "url" => $base_link."evidence/polarity/".$term->slug, "id" => $term->slug, "type" => "polarity", "fill" => json_decode($term->description)->fill);
					$totals[$term->name] = array("total" => 0, "slug" => $term->slug, "sector" => array());
					foreach ($evidence as $ev) {
						if ($ev->polarity == $term->name)
							$totals[$term->name]['total'] = $totals[$term->name]['total'] +1;
					}
					if ($totals[$term->name]['total'] > 0)
						$links[] = array("source" => get_the_title(), "target" => $term->name, "value" => $totals[$term->name]['total']); 
				}
				
				foreach ( $sec_terms as $term ) {
					$nodes[] = array("name" => $term->name, "url" => $base_link."evidence/sector/".$term->slug, "id" => $term->slug, "type" => "sector", "fill" => json_decode($term->description)->fill);
					foreach($totals as $key => $val){
						$totals[$key]["sector"][$term->name] = array("total" => 0, "ids" => array());
						foreach ($evidence as $ev) {
							if ($ev->sector == $term->name && $ev->polarity == $key){
								$totals[$key]["sector"][$term->name]["total"] = $totals[$key]["sector"][$term->name]["total"] +1;
								$totals[$key]["sector"][$term->name]["ids"][] = $ev->ID;
							}
						}
						if ($totals[$key]["sector"][$term->name]["total"] > 0) {
							$links[] = array("source" => $key, "target" => $term->name, "value" => $totals[$key]["sector"][$term->name]["total"]);
							//$totals[$key]["sub_ids"][] = $ev->ID;
						}
					}
					
				}
				$graph['nodes'] = $nodes;
				$graph['links'] = $links;
				
				if ($sankey == 1 && !empty($evidence)){ // <-- start of sankey if single
					?>
					<style>
			 
					.node rect {
					  /*cursor: move;*/
					  fill-opacity: .9;
					  shape-rendering: crispEdges;
					}
					 
					.node text {
					  pointer-events: none;
					  text-shadow: 0 1px 0 #fff;
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
			
					 <script> 
					 var graph = <?php print_r(json_encode($graph, JSON_PRETTY_PRINT)); ?>;
					 var margin = {top: 1, right: 1, bottom: 1, left: 1},
						width = document.getElementById("content").offsetWidth - margin.left - margin.right,
						height = 400 - margin.top - margin.bottom;
					
					</script>
					
					 <script src="<?php echo plugins_url( 'js/sankey.js' , EVIDENCE_HUB_REGISTER_FILE )?>"></script>
					 <script src="<?php echo plugins_url( 'js/sankey-control.js' , EVIDENCE_HUB_REGISTER_FILE )?>"></script>
				 <?php } // end of sankey if single ?>
			

				<?php if (empty($evidence)) { ?>
					<p><?php echo $no_evidence_message; ?></p>
				<?php } else { ?>
                	<div id="evidence-balance">
                   
                    <?php foreach ($totals as $polarity_key => $polarity) { ?>
                    
                      <div class="evidence-box <?php echo $polarity['slug'] ?>">
                        <h4><?php echo $polarity_key;?> Evidence (<?php echo $polarity['total']; ?>)</h4>
                        <ul>
                        <?php foreach ($polarity['sector'] as $sector_name => $sector){ 
							if ($sector['total']>0){ ?>
								<li><?php echo $sector_name;?> <ul>
								<?php foreach ($sector['ids'] as $ids) {
									echo "<li><a href='".get_permalink($ids)."'>".get_the_title($ids)."</a></li>";
								} ?>
								</ul></li>
					<?php 	} 
					 	} ?>
                        </ul></div>
				<?php } ?>
               </div>
			   <?php } ?>
		<?php } ?>
		</div>
	 <?php
	  return ob_get_clean();
	}
}