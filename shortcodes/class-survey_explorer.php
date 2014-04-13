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

	// set some globals
	var headings,demos;
	var colNames = {};
	var globalColIdx = {};
	var data = {};
	var filtered = {};
	var selectedValues = [];

	
	var isFirstTime = true;
	var fetch = 'world';
    var headings, mapData;
	var table, barChartDiff, pieChartDiff, chart_editor, mainMap, mapMarker, mapUS, map, markers, mapQuery, offset;
	
	
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
	
    //var queryInput;
	// data url for Google Fusion Tables
	var url = 'http://www.google.com/fusiontables/gvizdata?tq=';
	var tableId = '1lfXxk0jLtB5uudZCWQsSsh7nBNpYTvuLpWGzcLen'; // FusionTable ID with all the survey data
	
	// function to initalise UI
	function init() {
		isFirstTime = false;
		// jQuery accordion for data filters
		jQuery( "#data-filters" ).accordion({header: "h3", 
											 heightStyle:'content',
											 collapsible: true});
		
		// Prep chart output (3 tabs bar, pie and chart editor
		//barChartDiff = new google.visualization.BarChart(document.getElementById('barchart_diff'));
		//pieChartDiff = new google.visualization.PieChart(document.getElementById('piechart_diff'));
		editorChart = new google.visualization.ChartWrapper({
		  dataTable: new google.visualization.DataTable(),
		  chartType: 'BarChart',
		  containerId: 'chart_editor',
		  options: {chartArea:{ }}});
		google.visualization.events.addListener(editorChart, 'ready', hideLoading);
		  
		//prepare the map
		map = L.map('map').setView([25, 0], 1);
		L.tileLayer("http://{s}.tile.osm.org/{z}/{x}/{y}.png", {
			 attribution: "&copy; <a href=\"http://osm.org/copyright\">OpenStreetMap</a> contributors"}
			).addTo(map);

		//map = L.map('map', {center: latlng, zoom: 2, layers: [tiles]});
		markers = L.markerClusterGroup({disableClusteringAtZoom: 3, showCoverageOnHover: false, chunkedLoading: true });
		
		// Data table with summary of charted data
		table = new google.visualization.Table(document.getElementById('querytable'));
		
		// Create maps (world and zoomed US states
		mainMap = new google.visualization.GeoChart(document.getElementById('querymap'));
		google.visualization.events.addListener(mainMap, "regionClick", handleRegionClick);
		usMap = new google.visualization.GeoChart(document.getElementById('querymap_us'));
		google.visualization.events.addListener(usMap, "regionClick", handleUSRegionClick);
				
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
	  
	  //google.visualization.events.addListener(usMap, "regionClick", handleUSRegionClick);
      //(new google.visualization.Table(document.getElementById('table'))).draw(data, options);
      //queryInput = document.getElementById('display-query');
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
		jQuery.each(colNames, function(key, value) {
		  jQuery('#col_names').append(jQuery("<option/>", {
				value: key,
				text: key
		  }));
		});
		// convert basic select/option list into more UI friendly select (mainly done to allow wrapping of long question text)
		jQuery("#col_names").minimalect({
							onchange: function(value, text) {
								setQuestion();
							},
							placeholder: 'Select a question'});
		jQuery("#col_names").val("How long have you been teaching?").trigger("change");
		/*var table = new google.visualization.Table(document.getElementById('table'));
		table.draw(headings, {'showRowNumber': true});*/
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
		  jQuery('#filter-select').append(jQuery("<option/>", {
				value: demos.getValue(i,1),
				text: demos.getValue(i,0)
		  }));
	  }
	  // convert to nice UI
	  jQuery("#filter-select").minimalect({
		  					onchange: function (value, text){ addFilter(value); },
							placeholder: 'Add filter'});
	}
	
	// for each filter build a table of options for the current question
	function addFilter(value){
		var qVal = jQuery("#col_names").val(); // current question
		
		// disable option from select when filter is added 
		jQuery("#filter-select option[value='"+value+"']").prop("disabled",true);
		jQuery('#add-filter [data-value="'+value+'"]').attr("data-disabled", "true");
		
		// prep filter holder
		jQuery("#data-filters").append('<div class="group" id="'+value+'"><h3><div><a href="#" class="close-filter">X</a></div>'+value+'</h3><div class="filter-table" id="filter-'+value+'" style="height:370px"></div></div>');
	  	jQuery("#data-filters").accordion( "refresh" );
		jQuery("#data-filters").accordion( {active: jQuery("#data-filters h3").length - 1});
		
		// Because Fusion Tables has no OR select (WTF) and because some questions have response data accross several columns
		// we get an estimated facet count based on first set of responses in the group
		filterQuery = 	new google.visualization.Query(url);
		filterQuery.setQuery("SELECT "+value+" FROM "+ tableId + " WHERE " + colNames[qVal][0].v +"  NOT EQUAL TO '' GROUP BY "+value+" ORDER BY COUNT() DESC");
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
	  /*respData.setColumnLabel(1, '');
	  var formatter = new google.visualization.NumberFormat({fractionDigits:0});
	  formatter.format(respData,1);*/
	  
      var filter = new google.visualization.DataView(respData);
	  filter.setColumns([{calc:filterCheckbox, type:'string', label:''}, {calc:filterBlank, type:'string', label:''}]);
	  //filter.setColumns([{calc:filterCheckbox, type:'string', label:''}]);

	  filtered[col] = new google.visualization.Table(document.getElementById('filter-'+col));
	  filtered[col].draw(filter, filterOptions);
	  //restoreSelectedFilter(selectedValues);
	}
	
	// add radio/check to table chart
	function filterCheckbox(dt, r){
		var checked = "";
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
	
	function setQuestion(qVal){
		jQuery( ".modal" ).show();
		var qVal = jQuery("#col_names").val();
		markers.clearLayers();
		offset = 0;
		//jQuery('#querymap_us').hide();
		/*if (region && type){
			var fetchWhere = " AND country_code = '" + region + "' ";
			fetch = region;
		} else {
			var fetchWhere = "";
			var fetchWhereMap = "";
			fetch = 'world';
			type = 'country';
			optionsMain.region = fetch;
	    	mainMap.draw(mapData, optionsMain);
			jQuery('#querymap').show();
			jQuery('#querymap_us').hide();
		}*/
		var fetchWhere = "";
		selectedValues = [];
		jQuery('#data-filters :checked').each(function() {
			fetchWhere += " AND "+jQuery(this).attr("name")+ " = '"+jQuery(this).val()+"' ";
			selectedValues.push(jQuery(this).attr("name")+"-"+jQuery(this).val());
		});
		// update values in any filters
		/*jQuery('#data-filters .group').each(function(){
			var value = jQuery(this).attr("id");
			filterQuery = 	new google.visualization.Query(url);
			filterQuery.setQuery("SELECT "+value+", COUNT() FROM "+ tableId + " WHERE " + colNames[qVal][0].v +"  NOT EQUAL TO '' GROUP BY "+value+" ORDER BY COUNT() DESC");
			filterQuery.send(handleFilterQueryResponse);
		});*/
		
		
		jQuery('#survey_title').text(qVal);
		var colQuery = {};
		
		data[fetch] = new google.visualization.DataTable();

		var dataType = colNames[qVal][0].o;
		var questionWhere = [];
		//jQuery( "#tabs" ).tabs( "enable");
		//var counts = stringFill('COUNT(), ', qVal.split("|||").length );
		if (dataType === 'GROUP BY'){
			colQuery = 	new google.visualization.Query(url);
			//questionWhere.push(" "+colNames[qVal][0].v+" NOT EQUAL TO '' "); 
			colQuery.setQuery("SELECT "+colNames[qVal][0].v+", COUNT() FROM "+ tableId + " WHERE "+colNames[qVal][0].v+" NOT EQUAL TO '' "+ fetchWhere + " GROUP BY "+colNames[qVal][0].v);
			colQuery.send(handleQueryResponse);
		} else if (dataType === 'YES_NO'){
			data[fetch].addColumn('string', 'Question');
			data[fetch].addColumn('number', 'Count');
			jQuery.each(colNames[qVal], function(ke, va){
				  colQuery[ke] = new google.visualization.Query(url);
				  //questionWhere.push(" "+va.v+" NOT EQUAL TO '' ");
				  colQuery[ke].setQuery("SELECT "+va.v+", COUNT() FROM "+ tableId +" WHERE "+va.v+" NOT EQUAL TO '' " +  fetchWhere + " GROUP BY "+va.v);
				  colQuery[ke].send(handleColResponse);
			});	
		} else if (dataType === 'LIKERT'){
			//jQuery( "#tabs" ).tabs( "disable", 1 );
			//globalData.addColumn('number', 'Count');
			data[fetch].addColumn('string', 'Question');
			globalColIdx = {};
			jQuery.each(colNames[qVal], function(ke, va){
				  colQuery[ke] = new google.visualization.Query(url);
				  //questionWhere.push(" "+va.v+" NOT EQUAL TO '' ");
				  colQuery[ke].setQuery("SELECT "+va.v+", COUNT() FROM "+ tableId +" WHERE "+va.v+" NOT EQUAL TO '' " +  fetchWhere + " GROUP BY "+va.v);
				  colQuery[ke].send(handleLikertResponse);
			});	
		}
		
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
	function restoreSelectedFilter(selV){
		for (i in selV){
			console.log(jQuery('.filter-check input[type="radio"][name="'+selV[i].name+'"][value="'+selV[i].value+'"]'));
			jQuery('.filter-check [name="'+selV[i].name+'"][value="'+selV[i].value+'"]').prop("checked", true);
		}
		
	}
	
	function handleQueryResponse(response) {
      if (response.isError()) {
        //alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
      data[fetch] = response.getDataTable();
	  data[fetch].setColumnLabel(1, "Count");
      sendAndDraw();
    }
	
	function handleColResponse(response) {
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
	  //console.log(col);
	  //globalData.addRow([col.getColumnLabel(0),col.getValue(1,1)]);
	  sendAndDraw();
    }
	
	function handleMapResponse(response) {
      if (response.isError()) {
        alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
	  	//map.clearLayers();
		mapData = response.getDataTable();
		for (var i = 0; i < mapData.getNumberOfRows(); i++) {
			//markerArray.push(L.marker(L.latLng(mapData.getValue(i,0), mapData.getValue(i,1))));
			var marker = L.marker(L.latLng(mapData.getValue(i,0), mapData.getValue(i,1)));
			markers.addLayer(marker);
			//markerArray.push(marker);
		}
		map.addLayer(markers);
		if (mapData.getNumberOfRows() === 500 && offset < 4000){
			offset += 500;
			mapMarker.setQuery(mapQuery+" OFFSET " + offset);	
			mapMarker.send(handleMapResponse);
		}
		//map.addLayer(markers);
    }
	
	function handleMainMapResponse(response) {
      if (response.isError()) {
        alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
	  mapData = response.getDataTable();
	  mapData.setColumnLabel(1, "Survey Responses (est.)");
	  mainMap.draw(mapData, optionsMain);

    }
	function handleUSMapResponse(response) {
      if (response.isError()) {
        alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
	  usMapData = response.getDataTable();
	  usMapData.setColumnLabel(1, "Survey Responses (est.)"); 
	  usMap.draw(usMapData, optionsUS);
	  //}
    }
	function handleRegionClick(event) {
       if (event.region != fetch){
		   fetch = event.region;
		   setQuestion();
	   } else {
			fetch = 'world';
			jQuery('#querymap').show();
	  		jQuery('#querymap_us').hide();
			sendAndDraw();
	   }
	   if (event.region=='US'){
		   	jQuery('#querymap').hide();
	  		jQuery('#querymap_us').show();
	   }
	   optionsMain.region = fetch;
	   mainMap.draw(mapData, optionsMain);
    }
	function handleUSRegionClick(event) {
		//jQuery('#querymap_us').hide();
		fetch = 'world';
		setQuestion();
		optionsMain.region = fetch;
	    mainMap.draw(mapData, optionsMain);
		jQuery('#querymap').show();
		jQuery('#querymap_us').hide();
    }


	// http://stackoverflow.com/questions/202605/repeat-string-javascript#comment25970782_202605
	function stringFill(s, n )	{
		return new Array( n + 1 ).join( s );
	}
	
	function sendAndDraw(aChart) {
	  if (isFirstTime) {	
      	init();
      }
	  
      table.draw(data[fetch], { 'cssClassNames': {tableRow: 'tbl-row',
												 oddTableRow: 'tbl-row'}});
	  /*
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
	  editorChart.setDataTable(data[fetch]);
	  editorChart.setOptions({ animation:{ duration: 1000,
					  			     	  easing: 'out', }, 
							   title: jQuery("#col_names").val()});
	  
	  if (aChart){
		editor.setChartWrapper(aChart);  
	  }
	  editorChart.draw();

      //query.send(handleQueryResponse);
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

    function setQuery(queryString) {
      // Query language examples configured with the UI
      query.setQuery(queryString);
      sendAndDraw();
    }
    
 	function hideLoading(){
	jQuery( ".modal" ).hide();	
	}

    google.setOnLoadCallback(init);


	/*
    function setQueryFromUser() {
      setQuery(queryInput.value);
    }*/
	jQuery(function() {
		/*jQuery( "#tabs" ).tabs({
			activate: function(event, ui) {
				sendAndDraw(editor.getChartWrapper(editorChart));
			}
		});*/
		jQuery( "#map-tabs" ).tabs({
			activate: function(event, ui) {
				map.invalidateSize();
				sendAndDraw(editor.getChartWrapper(editorChart));
			}
		});
	});
	jQuery( document ).ready(function( $ ) {
		$(window).resize(function(){
			sendAndDraw();
			mainMap.draw(mapData, optionsMain);
			usMap.draw(usMapData, optionsUS);
			
		});
		$('.filter-check input[type="radio"]').live("change click", function(e) {
			this.checked = !this.checked;
		});
		$('.filter-check input[type="radio"]').live("click", function(e) {
			setQuestion();
		});
		$('.close-filter').live("click", function(e) {
			var filterGroup = $(this).closest('.group');
			jQuery("#filter-select option[value='"+filterGroup.attr("id")+"']").prop("disabled",false);
			jQuery('#add-filter [data-value="'+filterGroup.attr("id")+'"]').attr("data-disabled", "false");
			filterGroup.remove();
			setQuestion();
			
		});
		var h = (jQuery('.span_2_of_3').width() > 820) ? parseInt(jQuery('.span_2_of_3').width()*9/16) : 400;
		jQuery('#map').css('height', h);
		$( ".modal" ).hide();
				
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












