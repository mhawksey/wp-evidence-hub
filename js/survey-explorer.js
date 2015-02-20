/*!
  Javascript for the survey data explorer shortcode.

  WordPress shortcode: 'survey_explorer'
*/

/*global jQuery: false, document: false, google: false, L: false, alert: false, console: false */

(function () {

	var $jq = jQuery.noConflict();
	// set some globals
	var headings, demos;
	var colNames = {};
	var globalColIdx = {};
	var data = {};
	var filtered = {};
	var selectedValues = [];
	var qOption = [];
	var qGroup = {};
	var filtersAdded = 0;
	var filtersReady = 0;
	
	var isFirstTime = true;
	var fetch = 'world';
	var mapData;
	var table, barChartDiff, pieChartDiff, chart_editor, mainMap, mapMarker, mapUS, map, markers, mapQuery, dQuery;
	var qVal, offset, nIntervId;
	
	var groupLookup = groupLookup || [];
	groupLookup["profiles"] = "Learning & Teaching Profiles";
    groupLookup["behaviours"] = "OER Behaviours";
    groupLookup["challenges"] = "Challenges &amp; Solutions";
    groupLookup["impact"] = "OER Impact";

	// initialise the chart editor
	var editor = new google.visualization.ChartEditor();
	var optionsMain = { 
			region: fetch,
			datalessRegionColor: '#FFFFFF'
			//colorAxis: {colors: ['white', '#3366cc']}
		};
	var optionsUS = {
			region: 'US',
			dataMode: 'regions',
			resolution: 'provinces',
			datalessRegionColor: '#FFFFFF'
			//colorAxis: {colors: ['white', '#3366cc']}
		};
	
	// data url for Google Fusion Tables
	var url = 'http://www.google.com/fusiontables/gvizdata?tq=';
	var tableId = explorer_options.table_data; // FusionTable ID with all the survey data
	var qTableId = explorer_options.table_headings; // FusionTable ID with all the survey data
	
	// http://stackoverflow.com/a/901144/1027723
	function getParameterByName(name) {
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
			results = regex.exec(location.search);
		return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	}
	
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
		  options: {chartArea:{ left:100,
						 			 right: 50,
						 			 bottom: 0,
						 			 top:10,
									 width:"100%",
									 height:"100%"}}});
		
		  
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
		console.log("Building filters..");
		// Prep query for Fusion Table with the question groups
		var hFrom = "FROM "+qTableId+" WHERE Grouping NOT EQUAL TO '' ";
		var hCols = "Grouping, Question, Column_Name, Option_Values, Question_Option";
		var hQuery = new google.visualization.Query(url);
		hQuery.setQuery('SELECT '+hCols+' '+ hFrom);
		console.log('SELECT '+hCols+' '+ hFrom);
		
		// Prep query for Fustion Table with questions used for filtering the data
		var dFrom = "FROM "+qTableId+" WHERE Filter NOT EQUAL TO '' ";
		var dCols = 'Question_Option, Column_Name';
		dQuery = new google.visualization.Query(url);
		dQuery.setQuery('SELECT '+dCols+' '+ dFrom);
		console.log("Sending filter query..");
		// fire queries for question select and data filters
		hQuery.send(handleHeadingQueryResponse);
		//dQuery.send(handleDemoQueryResponse);
    }
	
	// function to build question select options
	function handleHeadingQueryResponse(response) {
		console.log("Have headings..");
		var i;
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
			var qGrpIdx = headings.getValue(i,0);
			var qIdx = headings.getValue(i,1);
			var arr = {v:"'"+headings.getValue(i,2).replace("'", "''")+"'", 
					   o: headings.getValue(i,3)};
			if (qGroup[qGrpIdx] === undefined){
			  qGroup[qGrpIdx] = [];
			}
			if (colNames[qIdx] === undefined){
			  colNames[qIdx] = [arr];
			  qGroup[qGrpIdx].push(qIdx);
			} else {
			  colNames[qIdx].push(arr);
			}			
			qOption[headings.getValue(i,2)] = headings.getValue(i,4);
			//qGroup[headings.getValue(i,0)] = headings.getValue(i,4);
		}
		// use object to build question select option list
		$jq.each(qGroup, function (index) {
            var optgroup = $jq('<optgroup>');
            optgroup.attr('label',groupLookup[index]||index);
			//console.log(index);
             $jq.each(qGroup[index], function (i) {
                var option = $jq("<option></option>");
                option.val(qGroup[index][i]);
                option.text(qGroup[index][i]);
                optgroup.append(option);
             });
             $jq("#col_names").append(optgroup);

		});
		
		$jq("#col_names").select2({
			placeholder: "Select a question",
		});
		$jq("#col_names").on('change', function(e) {
			setQuestion(e.val);
		});
		var question = getParameterByName('question')-1;
		var group = getParameterByName('group');
		if (question && group) {
			$jq("#col_names").select2("val", qGroup[group][question]); //set the value
		} else {
			$jq("#col_names").select2("val", qGroup['profiles'][0]); //set the value
		}
		setQuestion();
		dQuery.send(handleDemoQueryResponse);
	}
	
	// function to handle data filters
	function handleDemoQueryResponse(response) {
	  console.log("Have demo..");
	  	
	  var i;
	  if (response.isError()) {
        alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
      demos = response.getDataTable();
	  var demosRows = demos.getNumberOfRows();
	  // build the filter select/option list
	  for (i=0; i < demosRows; i++){
		  filtersAdded ++;
		  addFilter(demos.getValue(i,1), demos.getValue(i,0));
	  }
	}
	
	// for each filter build a table of options for the current question
	function addFilter(value, name){
		qVal = $jq("#col_names").val(); // current question
		
		// disable option from select when filter is added 
		$jq("#filter-select option[value='"+value+"']").prop("disabled",true);
		$jq('#add-filter [data-value="'+value+'"]').attr("data-disabled", "true");
		
		// prep filter holder
		$jq("#data-filters").append('<div class="group" id="'+value+'"><h3><div class="filter_icon"></div>'+name+'</h3><div class="filter-table" id="f'+value+'" style="height:200px"></div></div>');
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
	  var col = "f"+respData.getColumnLabel(0);
	  respData.setColumnLabel(1, 'est.');
	  var formatter = new google.visualization.NumberFormat({fractionDigits:0});
	  formatter.format(respData,1);
	  
      var filter = new google.visualization.DataView(respData);
	  filter.setColumns([{calc:filterCheckbox, type:'string', label:''}, {calc:filterBlank, type:'string', label:''}, 1]);

	  filtered[col] = new google.visualization.Table(document.getElementById(col));
	  new google.visualization.events.addListener(filtered[col], 'ready', function(){filtersReady++;});
	  filtered[col].draw(filter, filterOptions);
	}

	// add radio/check to table chart
	function filterCheckbox(dt, r){
		var checked = ""; // code to keep selection
		if (selectedValues[dt.getColumnLabel(0) + "-" + dt.getValue(r, 0)] !== undefined) {
			checked = 'checked';	
		}
		return '<div class="filter-check"><input type="radio" tabindex="-1" name="'+dt.getColumnLabel(0)+'" value="'+dt.getValue(r, 0)+'" '+checked+'/></div>';
	}
	// if no values let the user know (rather than having blank cell
	function filterBlank(dt, r){
		if(dt.getValue(r, 0) === ""){
			return '<em>No Response/Not Asked</em>';	
		}
		return dt.getValue(r, 0);
	}
	
	// handles change of question
	function setQuestion(optQVal, skipFilterUpdate){
		// http://stackoverflow.com/a/19279268/1027723
		if (history.pushState) {
			$jq.each(qGroup, function (index) {
				 $jq.each(qGroup[index], function (i) {
					
					if (qGroup[index][i] == $jq("#col_names").val()){
						var query = '?group='+index+'&question='+(parseInt(i)+1);
						var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + query;
						window.history.pushState({path:newurl},'',newurl);
						
					}
				 });
			});
		}
		$jq('#modal_msg').text('');
		$jq( ".modal" ).show(); // load spinner
		filtersReady = 0;
		filtersAdded = 0;
		console.log("Current question "+$jq("#col_names").val() );

		// preserve checked responses in filters
		selectedValues = [];
		$jq('#data-filters :checked').each(function() {
			selectedValues[$jq(this).attr("name")+"-"+$jq(this).val()] = 1;
		});
		if (!skipFilterUpdate){
			// update values for each facet in any filters
			$jq('#data-filters .group').each(function(){
				filtersAdded ++;
				var value = $jq(this).attr("id");
				filterQuery = 	new google.visualization.Query(url);
				filterQuery.setQuery("SELECT "+value+", COUNT() FROM "+ tableId + " WHERE " + colNames[qVal][0].v +"  NOT EQUAL TO '' GROUP BY "+value+" ORDER BY COUNT() DESC");
				filterQuery.send(handleFilterQueryResponse);
			});
		}
		console.log(nIntervId);
		nIntervId = setInterval(setQuestionQuery, 500); // because filters are handled async we need to poll before we draw the charts
	}
	
	// builds all the queries for the questions to be displayed 
	function setQuestionQuery(optVal){
		// if the number of filters used equals the number ready proceed
		var qVal = $jq("#col_names").val();
		console.log(filtersReady+':'+filtersAdded);
		if (filtersReady === filtersAdded) {
			clearInterval(nIntervId); // clear the polling
			console.log("chart ready");
			
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
			if (fetch === 'US'){
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
	  if (col.getNumberOfRows() > 0){
	  	data[fetch].addRow([qOption[col.getColumnLabel(0)],col.getValue(0,1)]);
	  	sendAndDraw();
	  } else {
		notEnoughData()  
	  }
    }
	
	function notEnoughData() {
		$jq('#modal_msg').text('Not enough data to draw graph');
	}
	
	function handleLikertResponse(response) {
      var i;
      if (response.isError()) {
        //alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
      var col = response.getDataTable();
	  data[fetch].addRow();
	  var colRow = col.getNumberOfRows();
	  var rowIndex = data[fetch].getNumberOfRows() - 1;
	  data[fetch].setCell(rowIndex, 0, qOption[col.getColumnLabel(0)]); 
	  for (i=0; i < colRow; i++){
		if (globalColIdx[col.getValue(i, 0)] === undefined){
			globalColIdx[col.getValue(i, 0)] = Object.keys(globalColIdx).length+ 1;
			data[fetch].addColumn('number', col.getValue(i, 0));
		} 
		data[fetch].setCell(rowIndex, globalColIdx[col.getValue(i, 0)], col.getValue(i,1));
	  }
	  sendAndDraw();
    }
	
	// markers for leaflet map
	function handleMapResponse(response) {
      var i, marker;
	  if (response.isError()) {
        //alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
        return;
      }
		mapData = response.getDataTable();
		for (i = 0; i < mapData.getNumberOfRows(); i++) {
			marker = L.marker(L.latLng(mapData.getValue(i,0), mapData.getValue(i,1)));
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
		console.log(event.region);
       if (event.region !== fetch){
		   fetch = event.region;
		   setQuestion();
	   } else {
			fetch = 'world';
			$jq('#querymap').show();
	  		$jq('#querymap_us').hide();
			sendAndDraw();
	   }
	   if (event.region === 'US'){
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
							   title: $jq("#col_names").val()});
	  
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
		$jq('#editor_button').live("click", function(e) {
			e.preventDefault();
			openEditor();
		});
		// rendering radio buttons as checkboxes 
		$jq('.filter-check input[type="radio"]').live("change", function(e) {
			this.checked = !this.checked;
		});
		$jq('.filter-check input[type="radio"]').live("click", function(e) {
			this.checked = !this.checked;
			setQuestion($jq('#col_names').val(), true);
			if(!$jq(this).is(":checked")){
				$jq('#'+this.name+' h3 .filter_icon').removeClass('on');
			} else {
				$jq('#'+this.name+' h3 .filter_icon').addClass('on');
			};
		});	
		// close filter button removes it from the DOM and re-enables option
		$jq('.close-filter').live("click", function(e) {
			var filterGroup = $jq(this).closest('.group');
			$jq("#filter-select option[value='"+filterGroup.attr("id")+"']").prop("disabled",false);
			$jq('#add-filter [data-value="'+filterGroup.attr("id")+'"]').attr("data-disabled", "false");
			filterGroup.remove();
			setQuestion();
		});
		$jq('#csv').live("click", function(e) {
			e.preventDefault();
			var csv_data = google.visualization.dataTableToCsv(data[fetch]);
			saveTextAs(csv_data, "survey_data.csv");
			//$jq('#csv a').attr('href', 'data:application/csv;charset=utf-8,' + encodeURIComponent(csv_data));
		});
		$jq(window).resize(function(){
			sendAndDraw();
			mainMap.draw(mapData, optionsMain);
			usMap.draw(usMapData, optionsUS);
		});
		var h = ($jq('.span_2_of_3').width() > 820) ? parseInt($jq('.span_2_of_3').width()*9/16) : 400;
		$jq('#map').css('height', h);
		$jq( ".modal" ).hide();	
	});
})();

//End.
