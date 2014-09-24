<?php
/**
 * Shortcode to display survey data explorer
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
 
new Evidence_Hub_Shortcode_Survey_Explorer();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Survey_Explorer extends Evidence_Hub_Shortcode {
	var $shortcode = 'survey_explorer';
	public $defaults = array();
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
<script src="<?php echo plugins_url( 'js/markercluster/leaflet.markercluster-src.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>
<script>
      google.load('visualization', '1.1', { packages: [ 'corechart', 'charteditor' ] });
</script>
<script src="<?php echo plugins_url( 'js/survey-explorer.js' , EVIDENCE_HUB_REGISTER_FILE )?>" charset="utf-8"></script>

<script type="text/hide">
	var $jq = jQuery.noConflict();
	//...
  </script>


<h2 id="survey_title"></h2>
<div class="section group">
  <div class="col span_1_of_3">
    <select id="col_names" name="col_names">
    </select>
    <div>
      <div id="add-filter">
        <select id="filter-select" name="filter-select">
        </select>
      </div>
      <div id="data-filters"></div>
    </div>
  </div>
  <div class="col span_2_of_3">
    <div id="map-tabs">
    <div class="modal"></div>
      <ul>
        <li><a href="#tabs-1">Chart</a></li>
        <li><a href="#tabs-2">Marker Map</a></li>
        <li><a href="#tabs-3">Summary Map</a></li>
        <li><a href="#tabs-4">Summary Table</a></li>
      </ul>
      <div id="tabs-1">
        <div id="chart_editor" style="height:500px"></div>
        <div style="text-align:right">
          <input type='button' onclick='openEditor()' value='Change Chart Type'>
        </div>
      </div>
      <div id="tabs-2">
        <div id="map"><?php $this->print_chart_loading_no_support_message( /*$is_map = TRUE -- Mixed! */ ) ?></div>
      </div>
      <div id="tabs-3">
        <div id="querymap"></div>
        <div id="querymap_us"></div>
      </div>
      <div id="tabs-4">
        <div id="querytable"></div>
      </div>
    </div>
  </div>
</div>
<?php
		return ob_get_clean();
	} // end of function content

} // end of class