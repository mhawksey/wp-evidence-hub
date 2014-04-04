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
<script type="text/javascript">
      google.load('visualization', '1.1', {packages: ['corechart','charteditor']});
    </script>
<script type="text/javascript">

	
	var headings;
	var colNames = {};
	var globalColIdx = {};
	var data = {};

	
	var isFirstTime = true;
	var fetch = 'world';
    var headings, mapData;
	var table, barChartDiff, pieChartDiff, chart_editor, mainMap, mapUS;
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
	
    var queryInput;
	var url = 'http://www.google.com/fusiontables/gvizdata?tq=';
	var tableId = '1lfXxk0jLtB5uudZCWQsSsh7nBNpYTvuLpWGzcLen';
	
		
	var hFrom = 'FROM 166qDEJGvifnMWGXEppfOH4_AEYxr0zutfS2WxWOt WHERE List > 0 ';
	var hCols = 'Prompt, Response_Code, Option_Values';
	var hQuery = new google.visualization.Query(url);
	hQuery.setQuery('SELECT '+hCols+' '+ hFrom);
	
		
	query = new google.visualization.Query(url);
	
	
	
	function handleHeadingQueryResponse(response) {
	  if (response.isError()) {
        alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
      headings = response.getDataTable();
	  var headingRows = headings.getNumberOfRows();
	  
	  for (i=0; i < headingRows; i++){
		  if (colNames[headings.getValue(i,0)] == undefined){
			  colNames[headings.getValue(i,0)] = [{v:"'"+headings.getValue(i,1).replace("'", "''")+"'", o: headings.getValue(i,2)}];
		  } else {
			  colNames[headings.getValue(i,0)].push({v:"'"+headings.getValue(i,1).replace("'", "'''")+"'", o: headings.getValue(i,2)});
		  }
		  

	  }
	  jQuery.each(colNames, function(key, value) {
		  jQuery('#col_names').append(jQuery("<option/>", {
				value: key,
				text: key
		  }));
	  });
	  jQuery("#col_names").minimalect({
							onchange: function(value, text) {
								setQuestion(value);
						   	},
							placeholder: 'Select a question'});
	  jQuery("#col_names").val("Role").trigger("change");
      /*var table = new google.visualization.Table(document.getElementById('table'));
      table.draw(headings, {'showRowNumber': true});*/
	}
	
	function setQuestion(questionValue, region, type){
		//jQuery('#querymap_us').hide();
		if (region && type){
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
		}
		jQuery('#').text(questionValue);
		var colQuery = {};
		
		data[fetch] = new google.visualization.DataTable();

		var dataType = colNames[questionValue][0].o;
		var questionWhere = [];
		jQuery( "#tabs" ).tabs( "enable");
		//var counts = stringFill('COUNT(), ', questionValue.split("|||").length );
		if (dataType === 'GROUP BY'){
			colQuery = 	new google.visualization.Query(url);
			questionWhere.push(" "+colNames[questionValue][0].v+" NOT EQUAL TO '' "); 
			colQuery.setQuery("SELECT "+colNames[questionValue][0].v+", COUNT() FROM "+ tableId + " WHERE " + questionWhere + fetchWhere + " GROUP BY "+colNames[questionValue][0].v);
			colQuery.send(handleQueryResponse);
		} else if (dataType === 'YES_NO'){
			data[fetch].addColumn('string', 'Question');
			data[fetch].addColumn('number', 'Count');
			jQuery.each(colNames[questionValue], function(ke, va){
				  colQuery[ke] = new google.visualization.Query(url);
				  questionWhere.push(" "+va.v+" NOT EQUAL TO '' ");
				  colQuery[ke].setQuery("SELECT "+va.v+", COUNT() FROM "+ tableId +" WHERE "+va.v+" NOT EQUAL TO '' " +  fetchWhere + " GROUP BY "+va.v);
				  colQuery[ke].send(handleColResponse);
			});	
		} else if (dataType === 'LIKERT'){
			jQuery( "#tabs" ).tabs( "disable", 1 );
			//globalData.addColumn('number', 'Count');
			data[fetch].addColumn('string', 'Question');
			globalColIdx = {};
			jQuery.each(colNames[questionValue], function(ke, va){
				  colQuery[ke] = new google.visualization.Query(url);
				  questionWhere.push(" "+va.v+" NOT EQUAL TO '' ");
				  colQuery[ke].setQuery("SELECT "+va.v+", COUNT() FROM "+ tableId +" WHERE "+va.v+" NOT EQUAL TO '' " +  fetchWhere + " GROUP BY "+va.v);
				  colQuery[ke].send(handleLikertResponse);
			});	
		}
		if (fetch == 'US'){
			mapUS = new google.visualization.Query(url);
			mapUS.setQuery("SELECT region_code, COUNT() FROM "+ tableId + " WHERE " + colNames[questionValue][0].v +" NOT EQUAL TO '' AND country_name = 'United States' GROUP BY region_code");
			mapUS.send(handleUSMapResponse);
		} else {
			mapMain = new google.visualization.Query(url);
			mapMain.setQuery("SELECT country_name, COUNT() FROM "+ tableId + " WHERE " + colNames[questionValue][0].v +" NOT EQUAL TO '' GROUP BY country_name ORDER BY COUNT() DESC");	
			mapMain.send(handleMainMapResponse);
		}

	}
	
	function handleQueryResponse(response) {
      if (response.isError()) {
        alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
      data[fetch] = response.getDataTable();
	  data[fetch].setColumnLabel(1, "Count");
      sendAndDraw();
    }
	
	function handleColResponse(response) {
      if (response.isError()) {
        alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
      var col = response.getDataTable();
	  data[fetch].addRow([col.getColumnLabel(0),col.getValue(0,1)]);
	  sendAndDraw();
    }
	
	function handleLikertResponse(response) {
      if (response.isError()) {
        alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
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
		   setQuestion(jQuery("#col_names").val(), fetch, 'region');
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
		setQuestion(jQuery("#col_names").val());
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
	  
	  var pieOptions = { legend: { position: 'top' },
	 					 animation:{ duration: 1000,
					  			     easing: 'out', }};
	  var barOptions = { legend: { position: 'top' },
	  				     animation:{ duration: 1000,
					  			     easing: 'out',},
						 chartArea:{ left:100,
						 			 right: 50,
						 			 bottom: 30,
						 			 top:20,
									 width:"100%",
									 height:"100%"}
					};											 
	  
	  diffData = barChartDiff.computeDiff(data['world'], data[fetch]);
	  barChartDiff.draw(diffData, barOptions);
	  pieChartDiff.draw(diffData, pieOptions);
	  editorChart.setDataTable(data[fetch]);
	  
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
	
	function init() {
      isFirstTime = false;
	  
	  table = new google.visualization.Table(document.getElementById('querytable'));
	  
	  mainMap = new google.visualization.GeoChart(document.getElementById('querymap'));
	  google.visualization.events.addListener(mainMap, "regionClick", handleRegionClick);
	  
	  usMap = new google.visualization.GeoChart(document.getElementById('querymap_us'));
	  google.visualization.events.addListener(usMap, "regionClick", handleUSRegionClick);
	
	   
	  barChartDiff = new google.visualization.BarChart(document.getElementById('barchart_diff'));
	  pieChartDiff = new google.visualization.PieChart(document.getElementById('piechart_diff'));
	  
	  editorChart = new google.visualization.ChartWrapper({
		  dataTable: new google.visualization.DataTable(),
		  chartType: 'BarChart',
		  containerId: 'chart_editor',
		  options: {chartArea:{ left:100,
						 			 right: 50,
						 			 bottom: 30,
						 			 top:20,
									 width:"100%",
									 height:"100%"}
					}
	  	   });
	  
	  hQuery.send(handleHeadingQueryResponse);
	  
	  
	  //google.visualization.events.addListener(usMap, "regionClick", handleUSRegionClick);
	  
      //(new google.visualization.Table(document.getElementById('table'))).draw(data, options);
      //queryInput = document.getElementById('display-query');
    }
	

    
    function setQuery(queryString) {
      // Query language examples configured with the UI
      query.setQuery(queryString);
      sendAndDraw();
    }
    
    

    google.setOnLoadCallback(init);



    function setQueryFromUser() {
      setQuery(queryInput.value);
    }
	jQuery(function() {
		jQuery( "#tabs" ).tabs({
			activate: function(event, ui) {
				
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
		$( ".modal" ).hide();
				
	});
  </script>

<div class="modal"></div>
<select id="col_names" name="col_names" onchange="setQuestion(this.value);">
</select>
<h2 id=""></h2>
<div class="section group">
  <div class="col span_1_of_2">
    <div id="tabs">
      <ul>
        <li><a href="#tabs-1">Comparison Bar</a></li>
        <li><a href="#tabs-2">Comparison Pie</a></li>
        <li><a href="#tabs-3">Chart Editor</a></li>
      </ul>
      <div id="tabs-1">
        <div id="barchart_diff" style="height:500px"></div>
      </div>
      <div id="tabs-2">
        <div id="piechart_diff" style="height:500px"></div>
      </div>
      <div id="tabs-3">
        <input type='button' onclick='openEditor()' value='Open Editor'>
        <div id="chart_editor" style="height:500px"></div>
      </div>
    </div>
  </div>
  <div class="col span_1_of_2">
    <div id="querymap"></div>
    <div id="querymap_us"></div>
    <div id="querytable"></div>
  </div>
</div>
<?php
		return ob_get_clean();
	} // end of function content

} // end of class





