<?php
/**
 * Shortcode to display survey data explorer
 *
 * Shortcode: [survey_explorer]
 * Options: tabla_data - Google Fusion Table ID with survey data
 *			table_headings - Google Fusion Table ID with survey data mappings
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */
 
new Evidence_Hub_Shortcode_Survey_Explorer2();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Survey_Explorer2 extends Evidence_Hub_Shortcode {
	var $shortcode = 'survey_explorer';
	public $defaults = array(
	'table_data' => '1szq2hshkKPmyXfo9tyYXLND4iS5vpTKOEKNX82uY',
	'table_headings' => '1IWMifnMW7OqJ1_QYYdfVeD5WiJxncMfDzBVwQ3rl',
	);
	/**
	* Generate post content. 
	*
	* @since 0.1.1
	* @return string.
	*/
	function content() {
		ob_start();
		extract($this->options); ?>
<link rel="stylesheet" href="<?php echo plugins_url( 'js/markercluster/MarkerCluster.css' , EVIDENCE_HUB_REGISTER_FILE )?>" />
<link rel="stylesheet" href="<?php echo plugins_url( 'js/markercluster/MarkerCluster.Default.css' , EVIDENCE_HUB_REGISTER_FILE )?>" />
<script>
/* <![CDATA[ */
var explorer_options = <?php echo json_encode($this->options);?>;
/* ]]> */
</script>
<script src="<?php echo plugins_url( 'js/markercluster/leaflet.markercluster-src.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
<script>
      google.load('visualization', '1.1', { packages: [ 'corechart', 'charteditor' ] });
</script>
<script src="<?php echo plugins_url( 'js/survey-explorer.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
<script src="<?php echo plugins_url( 'js/filesaver/filesaver.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
<script type="text/hide">
	var $jq = jQuery.noConflict();
	//...
  </script>

<h2 id="survey_title"></h2>
<div id="map">
  <?php $this->print_chart_loading_no_support_message( /*$is_map = TRUE -- Mixed! */ ) ?>
</div>
<select id="col_names" name="col_names" style="width:100%">
</select>
<div class="section group">
  <div class="col span_1_of_3">
  	<div>Filters:</div>
    <div id="data-filters"></div>
  </div>
  <div class="col span_2_of_3">
    <div id="map-tabs">
      <div class="modal"><div id="modal_msg"></div></div>
      <ul>
        <li><a href="#tabs-1">Chart</a></li>
        <li><a href="#tabs-2">Summary Map</a></li>
        <li><a href="#tabs-3">Summary Table</a></li>
      </ul>
      <div id="tabs-1">
        <div style="position: relative;z-index:2;float: right;">
          <a href="#" id="editor_button" class="button">Change Chart Type</a>
          <div id='png'></div>
        </div>
        <div id="chart_editor" style="height:550px"></div>
      </div>
      <div id="tabs-2">
        <div id="querymap"></div>
        <div id="querymap_us"></div>
      </div>
      <div id="tabs-3">
        <div id="querytable"></div>
        <div id="csv" style="text-align:right"><a href="#" >Download as CSV data</a></div>
      </div>
    </div>
  </div>
</div>
<?php
		return ob_get_clean();
	} // end of function content

} // end of class


