<?php
/**
 * Shortcode to display hypotheses evidence breakdown for sectors per polarity
 *
 * Shortcode: [hypothesis_breakdown]
 * Options: post_id - hypothesis id (deafults to current post)
 *			polarity = string of polarity name +ve|-ve
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */
 
new Evidence_Hub_Shortcode_Hypothesis_Breakdown();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Hypothesis_Breakdown extends Evidence_Hub_Shortcode {
	var $shortcode = 'hypothesis_breakdown';
	public $defaults = array('post_id' => false,
							 'polarity' => false);
	static $post_types_with_shortcode = array('hypothesis');
	/**
	* Generate post content. 
	*
	* @since 0.1.1
	* @return string.
	*/
	function content() {
		ob_start();
		extract($this->options);
		$id = ($post_id) ? $post_id : get_the_ID();

		// prep query to fetch all evidence post ids associated to hypothesis 
		$args = array('post_type' => 'evidence', // my custom post type
    				   'posts_per_page' => -1,
					   'post_status' => 'publish',
					   'fields' => 'ids',
					   'meta_query' => array(
									array(
										'key' => 'evidence_hub_hypothesis_id',
										'value' => $id,
										'compare' => '='
									)
								)); // show all posts);
		// add custom terms and fields
		$evidence = Evidence_Hub::add_terms(get_posts($args));
		echo '<div id="evidence-balance">'; //html start evidence-balance
		// if evidence do something with it 
		if (!empty($evidence)) :
			$bal = array();
			$sectors = get_terms('evidence_hub_sector', 'hide_empty=0');
			$pposts = Evidence_Hub::filterOptions($evidence, 'polarity', $polarity);
			foreach($sectors as $sector){	
				$sposts = Evidence_Hub::filterOptions($pposts, 'sector_slug', $sector->slug);
				$bal[$sector->name] = count($sposts);
			}
			?>
			<table class="evidence-breakdown">
			  <thead>
				<tr>
				  <td><?php echo implode('</td><td>', array_keys($bal)); ?></td>
				</tr>
			  </thead>
				<tbody>
					<tr>
					  <td><?php echo implode('</td><td>', array_values($bal)); ?></td>
					</tr>
				</tbody>
			</table>
            <?php
        else: 
			echo "<p>$no_evidence_message</p>"; //html
		endif; // end of if !empty($evidence)
		echo '</div>'; //html end of evidence-balance
		return ob_get_clean();
	} // end of function content
    
	function print_evidence_balance($data){
		?>
        <div class="evidence-box">
        	<div class="left">
            	We have found the following evidence against the hypothesis in these sectors:<?php $this->print_evidence_table($data['-ve']['sectors']);?>
            </div>
            <div class="right">
				We have found the following evidence for the hypothesis in these sectors:<?php $this->print_evidence_table($data['+ve']['sectors']);?>
            </div>
         </div>
	<?php		
	}
	
	function print_evidence_table($pol){
		?>
        <table class="evidence-breakdown">
          <thead>
            <tr>
              <td><?php echo implode('</td><td>', array_keys($pol)); ?></td>
            </tr>
          </thead>
            <tbody>
            	<tr>
                  <td><?php echo implode('</td><td>', array_values($pol)); ?></td>
            	</tr>
            </tbody>
        </table>
	<?php		
	}
	
	/**
	* Output evidence balance table pos/neg and return sankey links.
	*
	* @since 0.1.1
	* @param array $evidence posts.
	* @param array &$nodes used in sankey generation.
	* @param string $post_id of hypothesis
	* @return array Get array of links.
	*/
    function print_get_nodes_links($evidence, &$nodes, &$links, &$bal, $post_id) {			
			$sectors = get_terms('evidence_hub_sector', 'hide_empty=0');
			$pposts = Evidence_Hub::filterOptions($evidence, 'polarity_slug', $polarity->slug);
			$bal[$polarity->name] = array("total" => count($pposts), "sectors" => array());
			foreach($sectors as $sector){	
				$sposts = Evidence_Hub::filterOptions($pposts, 'sector_slug', $sector->slug);
				$bal[$polarity->name]['sectors'][$sector->name] = count($sposts);
			}
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
        var margin = {top: 20, right: 25, bottom: 20, left: 1},
            width = document.getElementById("content").offsetWidth - margin.left - margin.right,
            height = 400 - margin.top - margin.bottom;
        </script>
        <link rel="stylesheet" type="text/css" href="<?php echo plugins_url( 'lib/map/css/styles.css' , EVIDENCE_HUB_REGISTER_FILE )?>" />
        <script src="<?php echo plugins_url( 'js/sankey.js' , EVIDENCE_HUB_REGISTER_FILE )?>"></script>
        <script src="<?php echo plugins_url( 'js/sankey-control.js' , EVIDENCE_HUB_REGISTER_FILE )?>"></script>
    <?php 
    }

} // end of class