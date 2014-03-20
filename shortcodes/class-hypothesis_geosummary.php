<?php
/**
 * Shortcode to display hypotheses evidence breakdown for country
 *
 * Shortcode: [hypothesis_geosummary]
 * Options: post_id - hypothesis id (deafults to current post)
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */
 
new Evidence_Hub_Shortcode_Hypothesis_GeoSummary();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Hypothesis_GeoSummary extends Evidence_Hub_Shortcode {
	var $shortcode = 'hypothesis_geosummary';
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
        <div id="country-vis-map" style="width:100%"></div>
        <div id="country-vis-select"><label for="change-map">Filter map:</label>
        							 <select id="change-map">
        								<option value=4>Total</option>
                                        <option value=3>+ve</option>
                                        <option value=2>-ve</option>
                                     </select>
        </div>
        <div id="country-vis-table" style="width:100%"></div>
        
        <script type="text/javascript">
        jQuery(document).ready(function ($) {
            $(window).resize(function(){
                drawCountryVisualization();
            });
			$('#change-map').on('change', function(){
				console.log($(this).val());
				view.setColumns([0, parseInt($(this).val())]);
				var setViewOptions = viewOptions[$(this).val()] || {};
				setViewOptions.datalessRegionColor = '#FFFFFF';
				
				countryMap.draw(view, setViewOptions);
				
				tableOptions.sortColumn = $(this).val()-1;
				countryTable.draw(tableView, tableOptions);
			});
        });
		
		var countryTable, countryMap, data, view, tableView;
		var viewOptions = {};
		viewOptions[2] = {colorAxis: {colors: ['white', '#AAA']}};
		viewOptions[3] = {colorAxis: {colors: ['white', '#FF9206']}};
		viewOptions[4] = {colorAxis: {colors: ['white', '#0E66A4']}};
		
		var tableOptions = {'page': 'enable',
			  			  'pageSize': 5,
			  			  'sortColumn': 3,
						  'sortAscending': false};
		
        function drawCountryVisualization() {
            data = new google.visualization.DataTable(dt_country, 0.6);
			
			view = new google.visualization.DataView(data);
			view.setColumns([0, 4]);
			
			// Create and draw the visualization.
			countryMap = new google.visualization.GeoChart(document.getElementById('country-vis-map'));
			countryMap.draw(view, {datalessRegionColor: '#FFFFFF', colorAxis: viewOptions[4].colorAxis});
			
			tableView = new google.visualization.DataView(data);
			tableView.setColumns([0, 2, 3, 4]);
			countryTable = new google.visualization.Table(document.getElementById('country-vis-table'));
			countryTable.draw(tableView, tableOptions);
						  
			if(typeof renderSankey == 'function') {
				google.visualization.events.addListener(countryTable , 'select', function(){
						var sel = countryTable.getSelection();
						selectHandler(sel);
					});
			}
			google.visualization.events.addListener(countryMap , 'select', function(){
				var sel = countryMap.getSelection();
				countryTable.setSelection(sel);
				if(typeof renderSankey == 'function') {
					selectHandler(sel);
				}
			});
			
          }
		  
		function selectHandler(sel) {
			if  (sel[0] != null){
				renderSankey(data.getValue(sel[0].row,1), hyp_id);
				document.getElementById('sankey-display-title').innerHTML = data.getValue(sel[0].row,0);
			} else {
				renderSankey('World', hyp_id);
				document.getElementById('sankey-display-title').innerHTML = 'World';
			}
		}
		google.setOnLoadCallback(drawCountryVisualization); 
		 
		function getSum(data, column) {
			var total = 0;
			for (i = 0; i < data.getNumberOfRows(); i++)
			  total = total + data.getValue(i, column);
			return total;
		}
        </script>
        <?php
		return ob_get_clean();
	} // end of function content

} // end of class