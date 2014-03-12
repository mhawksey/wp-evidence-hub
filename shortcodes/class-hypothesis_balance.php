<?php
/**
 * Shortcode to display hypotheses evidence breakdown for sectors per polarity
 *
 * Shortcode: [hypothesis_balance]
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
 
new Evidence_Hub_Shortcode_Hypothesis_Balance();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Hypothesis_Balance extends Evidence_Hub_Shortcode {
	var $shortcode = 'hypothesis_balance';
	public $defaults = array('post_id' => false);
	static $post_types_with_shortcode = array('hypothesis');
	/**
	* Generate post content. 
	*
	* @since 0.1.1
	* @return string.
	*/
	function content() {
		ob_start();
		extract($this->options); ?>
        <div class="evidence-summary">
            <table class="balance-holder">
                <tr><td>
                <div class="evidence-scorecard neg">
                    <div>-ve</div>
                    <div class="count">-</div>
                </div>
                </td>
                <td style="text-align:right">
                <div class="evidence-scorecard pos">
                    <div>+ve</div>
                    <div class="count">-</div>
                </div>
                </td></tr>
                </table>
            <div id="balance-vis" style="width:100%;height: 70px;"></div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function ($) {
            $(window).resize(function(){
                drawBalanceVisualization();
            });
            $('.evidence-scorecard.pos .count').html(dt_totals.pos);
            $('.evidence-scorecard.neg .count').html(dt_totals.neg);
        });
        function drawBalanceVisualization() {
            var data = new google.visualization.DataTable(dt_balance, 0.6);
			
            // Create and draw the visualization.
            new google.visualization.BarChart(document.getElementById('balance-vis')).
                draw(data,
                     {
                      legend: {position: 'none'},
                      chartArea:{width:"100%"},
                      vAxis: {textPosition: 'none'},
                      hAxis: {textPosition: 'none'},
                      series: dt_series,
                      isStacked: true,}
                );
          }
          google.setOnLoadCallback(drawBalanceVisualization);
        </script>
        <?php
		return ob_get_clean();
	} // end of function content

} // end of class