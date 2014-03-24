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
	public $defaults = array('polarity' => false);
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
		$polarity_slug = ($polarity === '+ve') ? 'pos' : 'neg';

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
			$pposts = Evidence_Hub::filterOptions($evidence, 'polarity_slug', $polarity);
			foreach($sectors as $sector){	
				$sposts = Evidence_Hub::filterOptions($pposts, 'sector_slug', $sector->slug);
				$bal[$sector->name] = count($sposts);
			}
			/*
			if ($polarity == '-ve'){
				$bal = array_reverse($bal);
			}*/
			?>
			<table class="evidence-breakdown <?php echo $polarity_slug; ?>">
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

} // end of class