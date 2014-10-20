<?php
/**
 * Dummy Shortcode to intercept hypothesis details page
 *
 * Shortcode: [hypothesis_summary]
 * Options: do_cache - boolean to disable cache option default: true
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */
 
new Evidence_Hub_Shortcode_Hypothesis_Summary();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Hypothesis_Summary extends Evidence_Hub_Shortcode {

	const SHORTCODE = 'hypothesis_summary';

	public $defaults = array(
		'title' => false,
		'display_sankey' => true,
		'no_evidence_message' => "There is no evidence yet for this hypothesis",
		'title_tag' => 'h3',
	);

	static $post_types_with_shortcode = array('hypothesis');

	public function __construct() {
		parent::__construct();
		// Apply specific filters [Bug: #31].
		add_filter( 'hypothesis_excerpt', 'strip_shortcodes' );
		add_filter( 'hypothesis_excerpt', 'wpautop' );
	}

	/**
	* Adds shortcode content to named post post types. 
	*
	* @since 0.1.1 
	*/
	protected function add_to_page($content) {
		if (in_array(get_post_type(), self::$post_types_with_shortcode)) {
			$template_id = get_option( 'hypothesis_template_page' );
			if ($template_id) {
				if (is_single()) {
					$included_page = get_page( $template_id );

					$content = <<<EOT
			<script src="//www.google.com/jsapi"></script>
			<script>
			google.load('visualization', '1', {packages: ['corechart', 'geochart', 'table']});
			</script>
EOT;
					$content .= $this->get_google_visualisation_data(get_the_ID())
						. $this->safe_excerpt()
						. $included_page->post_content;
				}
			} else {
				$this->_require( 'shortcodes/class-evidence_summary.php' );
				if (is_single()) {
					$content = preg_replace(
						'/(<span id=\"more-[0-9]*\"><\/span>)/',
						'$1' . do_shortcode( '[evidence_summary]' ) . '<h3>Hypothesis Details</h3>',
						$content, 1 );
				} else {
					$content .= do_shortcode('[evidence_summary display_sankey=0]');
				}
				if (function_exists('the_ratings')){
					$content .= '<div id="postvoting">'.the_ratings('div', get_the_ID(), false).'</div>';
				}
			}
		}
		return $content;
	}

	/** NOT just `the_excerpt()` - risk of recursion, if no explicit
	*   and the generated excerpt contains shortcodes.. [Bug: #31][Bug: #33]
	*/
	protected function safe_excerpt() {
		global $post;
		if ( !has_excerpt() ) {
			$this->debug(array( __FUNCTION__, $post ));
			return '<p class="ev-hub-error no-excerpt">'.
				'No explicit excerpt found in post &mdash; please correct me!' . '</p>';
		}
		return apply_filters( 'hypothesis_excerpt', $post->post_excerpt );
	}

	protected function content(){
	}

	protected function get_google_visualisation_data($id){
		ob_start();
		extract($this->options);

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
		// if evidence do something with it 
		if (!empty($evidence)) :
			$bal = array();
			$country_bal = array();
			$dt = array('cols'=>array(), 'rows'=>array());
			$dt['cols'] = array(array('label' => 'Hyp', 'type'=> 'string'));
			$dt['rows'] = array(array('c' => array(array('v'=>get_the_title($id)))));
			$series = array();
			$sectors = get_terms('evidence_hub_sector', 'hide_empty=0');
			$polarities = get_terms('evidence_hub_polarity', 'hide_empty=0&order=DESC');
			
			foreach($evidence as $post){
				if ($post['polarity_slug'] !== ""){
						if (!isset($country_bal[$post['country_slug']])){
							$country_bal[$post['country_slug']] = array('country' => $post['country'],
																		'country_code' => strtoupper($post['country_slug']), 
																		'pos' => 0,
																		'neg' => 0,
																		'total' => 0);
						}
				}
			}
			
			foreach ($polarities as $polarity){
				$pposts = Evidence_Hub::filterOptions($evidence, 'polarity_slug', $polarity->slug);
				$bal[$polarity->slug] = count($pposts);
				foreach($pposts as $post){
					if (isset($country_bal[$post['country_slug']][$polarity->slug])){
						$country_bal[$post['country_slug']][$polarity->slug] ++;
						$country_bal[$post['country_slug']]['total'] ++;
					}
				}
				foreach($sectors as $sector){	
					$sposts = Evidence_Hub::filterOptions($pposts, 'sector_slug', $sector->slug);
					$dt['cols'][] = array('label' => $sector->name, 'type'=> 'number');
					$dt['rows'][0]['c'][] = array('v' => ($polarity->slug == 'pos') ? count($sposts) : -count($sposts), 'f' => (string)count($sposts) );
					$series[] = array( 'color' => self::json_get( $sector->description, 'fill' ));  //Was: json_decode($sector->description)->fill;
				}
				
			}
			
			$dt_country = array('cols'=>array(), 'rows'=>array());
			$dt_country['cols'] = array(array('label' => 'Country', 'type'=> 'string'),
								array('label' => 'Country Code', 'type'=> 'string'),
								array('label' => 'Negative', 'type'=> 'number'),
								array('label' => 'Positive', 'type'=> 'number'),
								array('label' => 'Total', 'type'=> 'number'));
			foreach ($country_bal as $country){
					$dt_country['rows'][]['c'] = array(array('v' => $country['country']),
													   array('v' => $country['country_code']),
													   array('v' => $country['neg']),
													   array('v' => $country['pos']),
													   array('v' => $country['total']));
			}
			
			?>
            <script>
				var dt_balance = <?php print_r(json_encode($dt)); ?>;
				var dt_totals = <?php print_r(json_encode($bal)); ?>;
				var dt_country = <?php print_r(json_encode($dt_country)); ?>;
				var dt_series = <?php print_r(json_encode($series)); ?>;
				var hyp_id = <?php print_r($id); ?>;
				function drawVisualization() {
  // Create and populate the data table.
 
  var data = new google.visualization.DataTable(dt_country, 0.6);
  var view = new google.visualization.DataView(data);
  view.setColumns([1, 2, 3, 4]);

  // Create and draw the visualization.
  new google.visualization.IntensityMap(document.getElementById('visualization')).
      draw(view, null);
}
            </script>
            <?php
        else:
			echo '<p class="no-ev">' . $this->defaults[ 'no_evidence_message' ] .'</p>'; //html
		endif; // end of if !empty($evidence)
		return ob_get_clean();
	}

} // end of class