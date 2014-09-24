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
    <tr>
      <td><div class="evidence-scorecard neg">
          <div>-ve</div>
          <div class="count">-</div>
        </div></td>
      <td style="text-align:right"><div class="evidence-scorecard pos">
          <div>+ve</div>
          <div class="count">-</div>
        </div></td>
    </tr>
  </table>
  <div id="balance-vis" style="width:100%;height: 90px;"></div>
  <div id="balance-legend">
    <svg style="width:100%;height:15px">
      <g>
        <rect x="0" y="2.666666666666668" width="309" height="9" stroke="none" stroke-width="0" fill-opacity="0" fill="#ffffff"></rect>
        <g>
          <rect x="0" y="3" width="43" height="9" stroke="none" stroke-width="0" fill-opacity="0" fill="#ffffff"></rect>
          <g>
            <text text-anchor="start" x="12" y="10.65" font-family="Arial" font-size="9" stroke="none" stroke-width="0" fill="#222222">College</text>
          </g>
          <rect x="0" y="3" width="9" height="9" stroke="none" stroke-width="0" fill="#b544a5"></rect>
        </g>
        <g>
          <rect x="58" y="3" width="72" height="9" stroke="none" stroke-width="0" fill-opacity="0" fill="#ffffff"></rect>
          <g>
            <text text-anchor="start" x="70" y="10.65" font-family="Arial" font-size="9" stroke="none" stroke-width="0" fill="#222222">Higher Educ...</text>
            <rect x="70" y="3" width="60" height="9" stroke="none" stroke-width="0" fill-opacity="0" fill="#ffffff"></rect>
          </g>
          <rect x="58" y="3" width="9" height="9" stroke="none" stroke-width="0" fill="#fd9417"></rect>
        </g>
        <g>
          <rect x="145" y="3" width="47" height="9" stroke="none" stroke-width="0" fill-opacity="0" fill="#ffffff"></rect>
          <g>
            <text text-anchor="start" x="157" y="10.65" font-family="Arial" font-size="9" stroke="none" stroke-width="0" fill="#222222">Informal</text>
          </g>
          <rect x="145" y="3" width="9" height="9" stroke="none" stroke-width="0" fill="#a9c94d"></rect>
        </g>
        <g>
          <rect x="207" y="3" width="59" height="9" stroke="none" stroke-width="0" fill-opacity="0" fill="#ffffff"></rect>
          <g>
            <text text-anchor="start" x="219" y="10.65" font-family="Arial" font-size="9" stroke="none" stroke-width="0" fill="#222222">School-K12</text>
          </g>
          <rect x="207" y="3" width="9" height="9" stroke="none" stroke-width="0" fill="#1badcf"></rect>
        </g>
      </g>
      </svg>
  </div>
</div>
<script>
        jQuery(document).ready(function ($) {
            $(window).resize(function(){
                drawBalanceVisualization();
            });
            $('.evidence-scorecard.pos .count').html(dt_totals.pos);
            $('.evidence-scorecard.neg .count').html(dt_totals.neg);
        });
        function drawBalanceVisualization() {
            var data = new google.visualization.DataTable(dt_balance, 0.6);
			
			var total = dt_totals.pos + dt_totals.neg;
			var negPer = - dt_totals.neg / total;
			
            // Create and draw the visualization.
            new google.visualization.BarChart(document.getElementById('balance-vis')).
                draw(data,
                     {
                      legend: {position: 'none'},
                      chartArea:{width:'90%',height:'50%'},
                      vAxis: {textPosition: 'none'},
                      hAxis: {textPosition: 'none',
					  		  minValue: -Math.max(dt_totals.pos,dt_totals.neg),
							  maxValue: Math.max(dt_totals.pos,dt_totals.neg),
							  ticks: [-Math.max(dt_totals.pos,dt_totals.neg), 0, Math.max(dt_totals.pos,dt_totals.neg)]},
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
