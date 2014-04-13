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
<script src="<?php echo plugins_url( 'js/markercluster/leaflet.markercluster-src.js' , EVIDENCE_HUB_REGISTER_FILE )?>" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript">
      google.load('visualization', '1.1', {packages: ['corechart','charteditor']});
    </script>
<script type="text/javascript">
	var $jq = jQuery.noConflict();
	// set some globals
	var headings,demos;
	var colNames = {};
	var globalColIdx = {};
	var data = {};
	var filtered = {};
	var selectedValues = [];
	var filtersAdded = 0;
	var filtersReady = 0;
	
	var isFirstTime = true;
	var fetch = 'world';
    var headings, mapData;
	var table, barChartDiff, pieChartDiff, chart_editor, mainMap, mapMarker, mapUS, map, markers, mapQuery;
	var qVal, offset, nIntervId;
	
	
	// initialise the chart editor
	var editor = new google.visualization.ChartEditor();
	var optionsMain = { 
			region: fetch,
			datalessRegionColor: '#FFFFFF',
			//colorAxis: {colors: ['white', '#3366cc']}
		};
	var optionsUS = {
			region: 'US',
			dataMode: 'regions',
			resolution: 'provinces',
			datalessRegionColor: '#FFFFFF',
			//colorAxis: {colors: ['white', '#3366cc']}
		};
	
	// data url for Google Fusion Tables
	var url = 'http://www.google.com/fusiontables/gvizdata?tq=';
	var tableId = '1lfXxk0jLtB5uudZCWQsSsh7nBNpYTvuLpWGzcLen'; // FusionTable ID with all the survey data
	
	// function to initalise UI
	function init() {
		isFirstTime = false;
		// $jq accordion for data filters
		$jq( "#data-filters" ).accordion({header: "h3", 
											 heightStyle:'content',
											 collapsible: true});
		
		// Prep chart output (3 tabs bar, pie and chart editor (dropping diff charts for now)
		//barChartDiff = new google.visualization.BarChart(document.getElementById('barchart_diff'));
		//pieChartDiff = new google.visualization.PieChart(document.getElementById('piechart_diff'));
		editorChart = new google.visualization.ChartWrapper({
		  dataTable: new google.visualization.DataTable(),
		  chartType: 'BarChart',
		  containerId: 'chart_editor',
		  options: {chartArea:{ }}});
		
		  
		// Prep the leafletjs map
		map = L.map('map').setView([25, 0], 1);
		L.tileLayer("http://{s}.tile.osm.org/{z}/{x}/{y}.png", {
			 attribution: "&copy; <a href=\"http://osm.org/copyright\">OpenStreetMap</a> contributors"}
			).addTo(map);

		// using marker cluster
		markers = L.markerClusterGroup({disableClusteringAtZoom: 3, showCoverageOnHover: false, chunkedLoading: true });
		
		// Create maps (world and zoomed US states)
		mainMap = new google.visualization.GeoChart(document.getElementById('querymap'));
		google.visualization.events.addListener(mainMap, "regionClick", handleRegionClick);
		usMap = new google.visualization.GeoChart(document.getElementById('querymap_us'));
		google.visualization.events.addListener(usMap, "regionClick", handleUSRegionClick);
		
		// Data table with summary of charted data
		table = new google.visualization.Table(document.getElementById('querytable'));
		google.visualization.events.addListener(table, 'ready', function() { $jq( ".modal" ).hide();});
				
		// Prep query for Fusion Table with the question groups
		var hFrom = 'FROM 166qDEJGvifnMWGXEppfOH4_AEYxr0zutfS2WxWOt WHERE List = 1 ';
		var hCols = 'Prompt, Response_Code, Option_Values';
		var hQuery = new google.visualization.Query(url);
		hQuery.setQuery('SELECT '+hCols+' '+ hFrom);
		
		// Prep query for Fustion Table with questions used for filtering the data
		var dFrom = 'FROM 166qDEJGvifnMWGXEppfOH4_AEYxr0zutfS2WxWOt WHERE List = 2 ';
		var dCols = 'Prompt, Response_Code';
		var dQuery = new google.visualization.Query(url);
		dQuery.setQuery('SELECT '+dCols+' '+ dFrom);
		
		// fire queries for question select and data filters
		hQuery.send(handleHeadingQueryResponse);
		dQuery.send(handleDemoQueryResponse);
    }
	
	// function to build question select options
	function handleHeadingQueryResponse(response) {
		// Browser alert if error in query
		if (response.isError()) {
			alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
			return;
		}
		// DataTable with the questions and question response groups
		headings = response.getDataTable();
		var headingRows = headings.getNumberOfRows();
		
		// loop through and build helper object with question, question responses and question type
		for (i=0; i < headingRows; i++){
			if (colNames[headings.getValue(i,0)] == undefined){
			  colNames[headings.getValue(i,0)] = [{v:"'"+headings.getValue(i,1).replace("'", "''")+"'", o: headings.getValue(i,2)}];
			} else {
			  colNames[headings.getValue(i,0)].push({v:"'"+headings.getValue(i,1).replace("'", "'''")+"'", o: headings.getValue(i,2)});
			}
		}
		// use object to build question select option list
		$jq.each(colNames, function(key, value) {
		  $jq('#col_names').append($jq("<option/>", {
				value: key,
				text: key
		  }));
		});
		// convert basic select/option list into more UI friendly select (mainly done to allow wrapping of long question text)
		$jq("#col_names").minimalect({
							onchange: function(value, text) {
								setQuestion();
							},
							placeholder: 'Select a question'});
		$jq("#col_names").val("How long have you been teaching?").trigger("change"); // set default question
	}
	
	// function to handle data filters
	function handleDemoQueryResponse(response) {
	  if (response.isError()) {
        alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
      demos = response.getDataTable();
	  var demosRows = demos.getNumberOfRows();
	  // build the filter select/option list
	  for (i=0; i < demosRows; i++){
		  $jq('#filter-select').append($jq("<option/>", {
				value: demos.getValue(i,1),
				text: demos.getValue(i,0)
		  }));
	  }
	  // convert to nice UI
	  $jq("#filter-select").minimalect({
		  					onchange: function (value, text){ addFilter(value); },
							placeholder: 'Add filter'});
	}
	
	// for each filter build a table of options for the current question
	function addFilter(value){
		//var qVal = $jq("#col_names").val(); // current question
		
		// disable option from select when filter is added 
		$jq("#filter-select option[value='"+value+"']").prop("disabled",true);
		$jq('#add-filter [data-value="'+value+'"]').attr("data-disabled", "true");
		
		// prep filter holder
		$jq("#data-filters").append('<div class="group" id="'+value+'"><h3><div><a href="#" class="close-filter">X</a></div>'+value+'</h3><div class="filter-table" id="filter-'+value+'" style="height:370px"></div></div>');
	  	$jq("#data-filters").accordion( "refresh" );
		$jq("#data-filters").accordion( {active: $jq("#data-filters h3").length - 1});
		
		// Because Fusion Tables has no OR select (WTF) and because some questions have response data accross several columns
		// we get an estimated facet count based on first set of responses in the group
		filterQuery = 	new google.visualization.Query(url);
		filterQuery.setQuery("SELECT "+value+", COUNT() FROM "+ tableId + " WHERE " + colNames[qVal][0].v +"  NOT EQUAL TO '' GROUP BY "+value+" ORDER BY COUNT() DESC");
		filterQuery.send(handleFilterQueryResponse);	
	}
	
	// handle data filter options
	function handleFilterQueryResponse(response) {
	  if (response.isError()) {
        alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
	  filterOptions = { height: '299px', 
	  					width:'100%',
						allowHtml:true,
						cssClassNames: {tableRow: 'tbl-row',
												 oddTableRow: 'tbl-row'}}
	  // get the filter options and render additional ui (radio/check select)
	  // (using a Google Visualisation Table chart handles a lot of this for us)
	  var respData = response.getDataTable();
	  var col = respData.getColumnLabel(0);
	  respData.setColumnLabel(1, 'est.');
	  var formatter = new google.visualization.NumberFormat({fractionDigits:0});
	  formatter.format(respData,1);
	  
      var filter = new google.visualization.DataView(respData);
	  filter.setColumns([{calc:filterCheckbox, type:'string', label:''}, {calc:filterBlank, type:'string', label:''}, 1]);

	  filtered[col] = new google.visualization.Table(document.getElementById('filter-'+col));
	  new google.visualization.events.addListener(filtered[col], 'ready', function(){filtersReady++;});
	  filtered[col].draw(filter, filterOptions);
	}

	// add radio/check to table chart
	function filterCheckbox(dt, r){
		var checked = ""; // code to keep selection
		if (selectedValues[dt.getColumnLabel(0)+"-"+dt.getValue(r, 0)] != undefined){
			checked = "checked";	
		}
		return '<div class="filter-check"><input type="radio" tabindex="-1" name="'+dt.getColumnLabel(0)+'" value="'+dt.getValue(r, 0)+'" '+checked+'/></div>';
	}
	// if no values let the user know (rather than having blank cell
	function filterBlank(dt, r){
		if(dt.getValue(r, 0) ==""){
			return '<em>no value</em>';	
		}
		return dt.getValue(r, 0);
	}
	
	// handles change of question
	function setQuestion(){
		$jq( ".modal" ).show(); // load spinner
		filtersReady = 0;
		filtersAdded = 0;
		qVal = $jq("#col_names").val(); // current question

		// preserve checked responses in filters
		selectedValues = [];
		$jq('#data-filters :checked').each(function() {
			selectedValues[$jq(this).attr("name")+"-"+$jq(this).val()] = 1;
		});
		// update values for each facet in any filters
		$jq('#data-filters .group').each(function(){
			filtersAdded ++;
			var value = $jq(this).attr("id");
			filterQuery = 	new google.visualization.Query(url);
			filterQuery.setQuery("SELECT "+value+", COUNT() FROM "+ tableId + " WHERE " + colNames[qVal][0].v +"  NOT EQUAL TO '' GROUP BY "+value+" ORDER BY COUNT() DESC");
			filterQuery.send(handleFilterQueryResponse);
		});
		nIntervId = setInterval(setQuestionQuery, 1000); // because filters are handled async we need to poll before we draw the charts
	}
	
	// builds all the queries for the questions to be displayed 
	function setQuestionQuery(){
		// if the number of filters used equals the number ready proceed
		if (filtersReady == filtersAdded) {
			clearInterval(nIntervId); // clear the polling
			var fetchWhere = "";
			markers.clearLayers(); // clear existing markers from map
			offset = 0; // because quering Fusion Tables with gViz limits to 500 rows set this up for some potential paging
			// iterate across the filters to build WHERE query
			$jq('#data-filters :checked').each(function() {
				fetchWhere += " AND "+$jq(this).attr("name")+ " = '"+$jq(this).val()+"' ";
			});
			$jq('#survey_title').text(qVal);
			var colQuery = {}; // because some of the question responses are across multiple columns we need an object to pass to response handlers
			data[fetch] = new google.visualization.DataTable(); // fetch is a global used for the summary maps
			// currently handle 3 types of Qs: 
			// GROUP BY single column responses with checkbox responses
			// YES_NO multiple columns where text is Yes or null
			// LIKERT multiple coulmns with likert repsonses
			var dataType = colNames[qVal][0].o; 
			if (dataType === 'GROUP BY'){
				colQuery = 	new google.visualization.Query(url);
				colQuery.setQuery("SELECT "+colNames[qVal][0].v+", COUNT() FROM "+ tableId + " WHERE "+colNames[qVal][0].v+" NOT EQUAL TO '' "+ fetchWhere + " GROUP BY "+colNames[qVal][0].v);
				colQuery.send(handleGroupByResponse);
			} else if (dataType === 'YES_NO'){
				data[fetch].addColumn('string', 'Question');
				data[fetch].addColumn('number', 'Count');
				$jq.each(colNames[qVal], function(ke, va){
					  colQuery[ke] = new google.visualization.Query(url);
					  colQuery[ke].setQuery("SELECT "+va.v+", COUNT() FROM "+ tableId +" WHERE "+va.v+" NOT EQUAL TO '' " +  fetchWhere + " GROUP BY "+va.v);
					  colQuery[ke].send(handleYesNoResponse);
				});	
			} else if (dataType === 'LIKERT'){
				data[fetch].addColumn('string', 'Question');
				globalColIdx = {};
				$jq.each(colNames[qVal], function(ke, va){
					  colQuery[ke] = new google.visualization.Query(url);
					  colQuery[ke].setQuery("SELECT "+va.v+", COUNT() FROM "+ tableId +" WHERE "+va.v+" NOT EQUAL TO '' " +  fetchWhere + " GROUP BY "+va.v);
					  colQuery[ke].send(handleLikertResponse);
				});	
			}
			// Now handle geo queries. Because Fusion Tables has no OR operator and some of the question types span multiple columns we cheat and just take first column
			// we still respect any data filters
			if (fetch == 'US'){
				mapUS = new google.visualization.Query(url);
				mapUS.setQuery("SELECT region_code, COUNT() FROM "+ tableId + " WHERE " + colNames[qVal][0].v +" NOT EQUAL TO '' AND country_name = 'United States' " +  fetchWhere + " GROUP BY region_code");
				mapUS.send(handleUSMapResponse);
			} else {
				mapMain = new google.visualization.Query(url);
				mapMain.setQuery("SELECT country_name, COUNT() FROM "+ tableId + " WHERE " + colNames[qVal][0].v +" NOT EQUAL TO '' " +  fetchWhere + " GROUP BY country_name ORDER BY COUNT() DESC");	
				mapMain.send(handleMainMapResponse);
			}
			mapMarker = new google.visualization.Query(url);
			mapQuery = "SELECT latitude, longitude FROM "+ tableId + " WHERE " + colNames[qVal][0].v +" NOT EQUAL TO '' AND latitude NOT EQUAL TO ''  AND longitude NOT EQUAL TO '' " +  fetchWhere + " ";
			mapMarker.setQuery(mapQuery);	
			mapMarker.send(handleMapResponse);
		}
	}
	/*
	* block of setQuestionQuery responses handlers
	* pattern is get data, reshape as needed and hit sendAndDraw function to render
	*/
	function handleGroupByResponse(response) {
      if (response.isError()) {
        //alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
      data[fetch] = response.getDataTable();
	  data[fetch].setColumnLabel(1, "Count");
      sendAndDraw();
    }
	
	function handleYesNoResponse(response) {
      if (response.isError()) {
        //alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
      var col = response.getDataTable();
	  data[fetch].addRow([col.getColumnLabel(0),col.getValue(0,1)]);
	  sendAndDraw();
    }
	
	function handleLikertResponse(response) {
      if (response.isError()) {
        //alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
      var col = response.getDataTable();
	  data[fetch].addRow();
	  var colRow = col.getNumberOfRows();
	  var rowIndex = data[fetch].getNumberOfRows()-1;
	  data[fetch].setCell(rowIndex, 0, col.getColumnLabel(0)); 
	  for (i=0; i < colRow; i++){
		if (globalColIdx[col.getValue(i,0)] == undefined){
			globalColIdx[col.getValue(i,0)] = i+1;
			data[fetch].addColumn('number',col.getValue(i,0))	
		} 
		data[fetch].setCell(rowIndex, globalColIdx[col.getValue(i,0)], col.getValue(i,1));
	  }
	  sendAndDraw();
    }
	
	// markers for leaflet map
	function handleMapResponse(response) {
      if (response.isError()) {
        //alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
		mapData = response.getDataTable();
		for (var i = 0; i < mapData.getNumberOfRows(); i++) {
			var marker = L.marker(L.latLng(mapData.getValue(i,0), mapData.getValue(i,1)));
			markers.addLayer(marker);
		}
		map.addLayer(markers);
		// if we hit the 500 row limit from Fusion Tables add an offset and get more
		// set a hard limit of 4500 rows
		if (mapData.getNumberOfRows() === 500 && offset < 4000){
			offset += 500;
			mapMarker.setQuery(mapQuery+" OFFSET " + offset);	
			mapMarker.send(handleMapResponse);
		}
    }
	
	function handleMainMapResponse(response) {
      if (response.isError()) {
        //alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
	  mapData = response.getDataTable();
	  mapData.setColumnLabel(1, "Survey Responses (est.)");
	  mainMap.draw(mapData, optionsMain);
    }
	
	function handleUSMapResponse(response) {
      if (response.isError()) {
        //alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
	  usMapData = response.getDataTable();
	  usMapData.setColumnLabel(1, "Survey Responses (est.)"); 
	  usMap.draw(usMapData, optionsUS);
    }
	
	/*
	* event handlers for when the summary map is clicked
	* 
	*/
	function handleRegionClick(event) {
       if (event.region != fetch){
		   fetch = event.region;
		   setQuestion();
	   } else {
			fetch = 'world';
			$jq('#querymap').show();
	  		$jq('#querymap_us').hide();
			sendAndDraw();
	   }
	   if (event.region=='US'){
		   	$jq('#querymap').hide();
	  		$jq('#querymap_us').show();
	   }
	   optionsMain.region = fetch;
	   mainMap.draw(mapData, optionsMain);
    }
	
	function handleUSRegionClick(event) {
		fetch = 'world';
		setQuestion();
		optionsMain.region = fetch;
	    mainMap.draw(mapData, optionsMain);
		$jq('#querymap').show();
		$jq('#querymap_us').hide();
    }

	/*
	* Having got the data we need to render it
	* aChart is a chart wrapper object used to preserve the chart editor when switching tabs
	*/
	function sendAndDraw(aChart) {
	  if (isFirstTime) {	
      	init(); // creates all the chart objects we need
      }
	  
	  // Summary Table
      table.draw(data[fetch], { 'cssClassNames': {tableRow: 'tbl-row',
												 oddTableRow: 'tbl-row'}});
	  /*
	  // There were diff charts to compare world to region
	  // keeping in here as might reuse if A-B filters are added
	  var pieOptions = { legend: { position: 'top' },
	 					 animation:{ duration: 1000,
					  			     easing: 'out', }};
	  var barOptions = { legend: { position: 'top' },
	  				     animation:{ duration: 1000,
					  			     easing: 'out',},
						 chartArea:{ left:100,
						 			 right: 50,
						 			 bottom: 0,
						 			 top:20,
									 width:"100%",
									 height:"90%"}
					};											 
	  
	  diffData = barChartDiff.computeDiff(data['world'], data[fetch]);
	  barChartDiff.draw(diffData, barOptions);
	  pieChartDiff.draw(diffData, pieOptions);
	  */
	  
	  // the lovely chart editor (let users choose the type of chart for rendering data)
	  data[fetch].sort(1);
	  editorChart.setDataTable(data[fetch]);
	  editorChart.setOptions({ animation:{ duration: 1000,
					  			     	  easing: 'out', }, 
							   title: qVal});
	  
	  // existing chart wrapper object use it
	  if (aChart){
		editor.setChartWrapper(aChart);  
	  }
	  editorChart.draw();
    }
	
	function openEditor() {
      // Handler for the "Open Editor" button.
      google.visualization.events.addListener(editor, 'ok',
        function() {
          editorChart = editor.getChartWrapper();
          editorChart.draw(document.getElementById('visualization'));
      });
      editor.openDialog(editorChart);
    }
    
    google.setOnLoadCallback(init);

	// some extra jQuery UI handling
	$jq(function() {
		$jq( "#map-tabs" ).tabs({
			activate: function(event, ui) {
				map.invalidateSize();
				sendAndDraw(editor.getChartWrapper(editorChart));
			}
		});
		
		// rendering radio buttons as checkboxes 
		$jq('.filter-check input[type="radio"]').live("change click", function(e) {
			this.checked = !this.checked;
		});
		$jq('.filter-check input[type="radio"]').live("click", function(e) {
			setQuestion();
		});
		
		// close filter button removes it from the DOM and re-enables option
		$jq('.close-filter').live("click", function(e) {
			var filterGroup = $jq(this).closest('.group');
			$jq("#filter-select option[value='"+filterGroup.attr("id")+"']").prop("disabled",false);
			$jq('#add-filter [data-value="'+filterGroup.attr("id")+'"]').attr("data-disabled", "false");
			filterGroup.remove();
			setQuestion();
		});
	});
	$jq( document ).ready(function( $ ) {
		$jq(window).resize(function(){
			sendAndDraw();
			mainMap.draw(mapData, optionsMain);
			usMap.draw(usMapData, optionsUS);
		});
		var h = ($jq('.span_2_of_3').width() > 820) ? parseInt($jq('.span_2_of_3').width()*9/16) : 400;
		$jq('#map').css('height', h);
		$jq( ".modal" ).hide();	
	});
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
        <div id="map"></div>
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












