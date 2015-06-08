/**
 *
 * 	World Oil Production & Consumption 
 *  An interactive data visualization for LERU Bright 2013

 main.js
 Executes all the logic
 @author			Timo Grossenbacher
 @copyright			CC BY-NC-SA 2.0 2013
 @license			MIT License (http://www.opensource.org/licenses/mit-license.php)

 */
var w = 1100; //window.innerWidth;
var h = 650; 
/* Parameters */
var LONCENTER = 10;
var LATCENTER = 30;
var MAPSCALE = 120;
var MAPTRANSLATEX = (w < 650) ? 10 : 500;
var MAPTRANSLATEY = 50;
var BARCHARTTRANSLATEX = -75;
var BARCHARTTRANSLATEY = 120;
var TSTRANSLATEX = 24;
var TSTRANSLATEY = 400;
var MAXCIRCLERADIUS = 60;
var MAXBARCHARTWIDTH = 100;
var TRANSDURATION = 1000;
var BARCHARTSHOWONLY = 10;
// Transitionsdauer in ms
/*var SELECTEDSTYLE = {
    'stroke' : 'rgb(0,0,0)',
    'stroke-width' : '3px'
}
var UNSELECTEDSTYLE = {
    'stroke' : '#FFFFFF',
    'stroke-width' : '0.8px'
}*/

/* Global Variables */
var numParts = 1;
var partsReady = 0;
var map = null;
var projection = null;
var path = null;

var barchart = null;
var timeseries = null;
//var data = null;
var path = null;
var dataByCountry = null;
var year = '2013';
var order = 'positive';
var san = null;
var centered;
var k = 1;
var positive, negative, marker;



/**
 * Konstruktor
 * Lädt die Geometrien, die wiederum die Daten laden, etc.
 */
var init = function() {

    if (!window.d3) {
        return;
    }

    // create svg panels
    svg = d3.select('#evidence-map').append('svg');  

    // Load all the data
    loadData();
    
   
}
/**
 * Erhöht den partsReady-Counter um eins und überprüft, ob alle Teile schon geladen sind.
 */
var partReady = function() {
    partsReady++;
    checkLoader();
}
/**
 * Überprüft, ob alle Teile schon geladen sind.
 * Wenn ja, wird der Loading-Screen entfernt  und der Benutzer kann auf die App zugreifen.
 */
var checkLoader = function() {
    if (partsReady >= numParts)
        createUi();
        createImpress();
        // inject header to bottom of container
        document.getElement('#loading').destroy();
    
}

/**
 * Creates the control panel on top to select the year
 */
var createUi = function() {
    // create a control container
    var ui = (new Element('div', {
        'id': 'ui'
    })).inject(document.getElement('#evidence-map'));
    
    // container for current year
    /*(new Element('div', {
        'id': 'yearDisplay',
        'text': 'Year: ' + year    
    })).inject(ui);*/
    
    // create slider and knob
    // container for current year
    var slider = (new Element('div', {
        'id': 'yearSlider'  
    })).inject(ui);
    var knob = (new Element('div', {
        'id': 'yearKnob'  
    })).inject(slider);
    // create slider for year selection
    var yearSlider = new Slider(slider, knob, {
        'snap': false,
        'initialStep': year,
        'range': [2000,2013],
        'steps': 13,
        // set the current year in the display
        /*'onChange': function(step){
            ui.getElement('#yearDisplay').set('text', 'Year: ' + step);
            if(data !== null){
                year = parseInt(step);
                displayData();
            }
        },*/
        // re-display the data for the now selected year
        'onComplete': function(step){
            if(data !== null){
                year = parseInt(step);
                displayData();
            }
        },
        'onTick': function(pos){
            this.knob.setStyle(this.property, pos);
        }
    });
    
    // create radio buttons for sort order
    (new Element('div', {
        'id': 'sortOrderChoice',
        'text': 'Order by: '   
    })).inject(ui);
    
    var choiceNegative = (new Element('input', {
        'class': 'option',
        'type': 'radio',
        'checked': (order == 'negative') ? true : false,
        'events': {
            'click': function(){
                if(data !== null && order != 'negative'){
                    order = 'negative';
                    displayData();
                    choicePositive.set('checked', false);
                }
            }
        }
    })).inject(ui);
    choiceNegative.grab((new Element('span', {'text':'Negative'})), 'after');
    var choicePositive = (new Element('input', {
        'class': 'option',
        'type': 'radio',
        'checked': (order == 'positive') ? true : false,
        'events': {
            'click': function(){
                if(data !== null && order != 'positive'){
                    order = 'positive';
                    displayData();
                    choiceNegative.set('checked', false);

                }
            }
        }
    })).inject(ui);
    choicePositive.grab((new Element('span', {'text':'Positive'})), 'after');

}
/**
 * Erstellt die Erläuterungen/das Impressum
 */
var createImpress = function() {
    //impressum.setStyle('display', 'block');
}

/**
 * Loads all the data including the map geometries
 * 
 */
var loadData = function() {
    // Arbitrary delimiter loader
    var dsv = d3.dsv(';', 'text/plain');
    // Queue all the requests and specify a callback
    queue()
        .defer(d3.json, MyAjax.pluginurl+"lib/map/data/world-110m.json")
        .defer(dsv, MyAjax.pluginurl+"lib/map/data/world-country-names.csv")
        .await(prepareData)
}

/**
 * Prepares the data, i.e. fills the data dictionary and links the map
 * geometries to the data 
 */
var prepareData = function(error, world, names){
	console.log(error);
	
	
    // Prepare Map
    prepareMap(world, names);
    
    // Prepare BarChart
    prepareBarChart();
    
    // Prepare timeseries
    prepareSankey();
    
    // Prepare data
    // data is an object, containing all countries for every year, where the year
    // is the index and the value is an array with all countries (can be easily used with d3 data joins)
    // every year contains only the countries where at least one of production or consumption is not NaN
    //data = {};
	/*
    for(var i = 1965; i <= 2012; i++){
        var countryarray = [];
        consumption.forEach(function(d,j){
            var country = {'name': d.name, 'id': parseInt(d.id), 'consumption': parseInt(d[i + '']), 'production': parseInt(production[j][i + ''])};
            if(typeOf(parseInt(country.production)) == 'number' || typeOf(parseInt(country.consumption)) == 'number')
                countryarray.push(country);
        });
        data[i + ''] = countryarray;
    }
    // this is used for the timeseries
	*/
    dataByCountry = {};
    Object.each(data, function(yearData,year){
        yearData.forEach(function(country,i){
            // initialize array if not existing
            if(typeOf(dataByCountry[country.name]) != 'array'){
                dataByCountry[country.name] = [];
                
            } 
            dataByCountry[country.name].push({'year': year, 'positive': country.positive, 'negative': country.negative});         
        })
    });
    partReady();
	addTitle();
	displaySankey('World');
    //displayData();    
}

/*
 * INTERACTION FUNCTIONS
 */

/**
 * visually deselects country and hides the tooltip, switches back to world timeseries
 */
var unselectCountry = function(id, showTooltip){
    var country = d3.select('#map').selectAll('.country').filter(function(d){return d.id == id});
    country
        .classed('selected', false);
    // delete tooltip
    if(showTooltip){
        d3.select('#evidence-map')
            .selectAll('.tooltip')
            .transition()
            .duration(250)
            .style('opacity', 1e-6)
            .remove();
    }
    //displayTimeseries(dataByCountry['World'], 'World');
}

/** 
 * visually selects a country on the map and displays a tooltip, updates the timeseries
 */

var selectCountry = function(id, showTooltip){
    var numFormatter = d3.format(',');
    // select the country data from the map
    var country = d3.select('#map').selectAll('.country').filter(function(d){return d.id == id});
    country
        .classed('selected', true);
    // get the data from the circles
    var countryInfo = d3.select('#map').selectAll('.positive').filter(function(d){return d.id == id}).data();
    // if no positive circle, get production circle instead
    if(countryInfo.length == 0){
        countryInfo = d3.select('#map').selectAll('.negative').filter(function(d){return d.id == id}).data();
    }
    // construct data object for which one (!) tooltip will be added
    if(country[0].length > 0){
        var data = {
            'name': country.datum().name,
            'negative': (countryInfo[0] !== undefined && !isNaN(countryInfo[0].negative)) ? numFormatter(countryInfo[0].negative) : 'n/a',
            'positive': (countryInfo[0] !== undefined && !isNaN(countryInfo[0].positive)) ? numFormatter(countryInfo[0].positive) : 'n/a'
        };
    }
    // show tooltip (only the first time)
    if(showTooltip){
        var tooltip = d3.select('#evidence-map').append('div')
            .attr('class', 'tooltip')
            .html('<strong>' + data.name + '</strong><br/>Negative: ' + data.negative + '<br/>Positive: ' + data.positive)
            .attr('style', 'left:'+ (d3.event.pageX - 100) +'px;top:'+ (d3.event.pageY - 50) +'px')
            .transition()
            .duration(250)
            .style('opacity', 1)
        /* ugly hack */    
        /*d3.select('#evidence-map')
            .selectAll('.tooltip')
            .attr('style', 'left:'+ (d3.event.pageX - 150) +'px;top:'+ (d3.event.pageY - 75) +'px');*/
    }
    // if country has data, display it in timeseries
    if(id !== 900 && dataByCountry[data.name] !== undefined){
        //displayTimeseries(dataByCountry[country.datum().name], country.datum().name);
    }
}

function displaySankey(d){
	var slug = (d && d.slug) || "World";
	console.log(slug);
	//if (slug != "World"){
	var country = map.selectAll('.country').filter(function(d,i){
		return d.slug === slug;
	});
	 
	var x, y;
	if (d && centered !== d && slug != "World") {
		var centroid = path.centroid(country.datum().geometry);
		var b = path.bounds(country.datum().geometry);
		x = centroid[0];
		y = centroid[1];
		//k = 4;
		k = .95 / Math.max((b[1][0] - b[0][0]) / w, (b[1][1] - b[0][1]) / h);
		o = 0;
		centered = d;
	  } else {
		x = w / 2;
		y = h / 2;
		k = 1;
		slug = "World";
		centered = null;
		map.selectAll('.marker').remove();
	  }
	
	  map.selectAll("path")
		  .classed("active", centered && function(d) { return d === centered; });
	
	  map.transition()
		  .duration(750)
		  .attr("transform", "translate(" + w / 2 + "," + h / 2 + ")scale(" + k + ")translate(" + -x + "," + -y + ")")
		  .style("stroke-width", 1.0 / k + "px");
	  console.log(k);
	  displayData();	  


	d3.json(MyAjax.apiurl.replace('%s', 'hub.get_sankey_data') + 'country_slug=' + slug, function (graph) {
		displayMarkers(graph.markers);
			
		var margin = {top: 0, right: 20, bottom: 10, left: 60},
		width = 500 - margin.left - margin.right,
		height = 250 - margin.top - margin.bottom;
		
		var units = "connections";
 
		var formatNumber = d3.format(",.0f"),    // zero decimal places
			format = function(d) { return formatNumber(d) + " " + units; },
			color = d3.scale.category20();
			
		// Set the sankey diagram properties
		var sankey = d3.sankey()
			.nodeWidth(10)
			.nodePadding(10)
			.size([width, height]);
			
		var spath = sankey.link();
		
		var nodeMap = {};
		graph.nodes.forEach(function(x) { nodeMap[x.name] = x; });
		graph.links = graph.links.map(function(x) {
		  return {
			source: nodeMap[x.source],
			target: nodeMap[x.target],
			value: x.value
		  };
		}); 
	 
		sankey
		  .nodes(graph.nodes)
		  .links(graph.links)
		  .layout(32);
		  
		  // add in the links
 
		san.selectAll('.link').remove();
		san.selectAll('.node').remove();
		san.selectAll('.santitle').remove();
			
		if(graph.links.length > 0){ 		
			var link = san.append("g").selectAll(".linkpath")
			  .data(graph.links)
			.enter()
			.append("svg:a")
			  .attr("xlink:href", function(d) { 
			  					var ev = (d.source.type == 'hypothesis') ? '/evidence' : '';
								return d.source.url+ev+'/'+d.target.type+'/'+d.target.id; })
			  .attr("class", "linkpath")
			  .sort(function(a, b) { return b.dy - a.dy; });
			
			// add the link titles
			link
			  .append("title")
				.text(function(d) {
				return d.source.name + " → " + 
						d.target.name + "\n" + format(d.value); });
			
			link 
			.append("path")
			  .attr("class", "link")
			  .attr("d", spath)
			  .style("stroke-width", function(d) { return Math.max(1, d.dy); });
			  //
			
			// add in the nodes
			var node = san.append("g").selectAll(".node")
			  .data(graph.nodes)
			.enter()
			  .append("g")
			  .attr("class", "node")
			  .attr("transform", function(d) { 
				  return "translate(" + d.x + "," + d.y + ")"; });
			/*.call(d3.behavior.drag()
			  .origin(function(d) { return d; })
			  .on("dragstart", function() { 
				  this.parentNode.appendChild(this); })
			  .on("drag", dragmove));*/
			
			// add the rectangles for the nodes
			node.append("svg:a")
			  .attr("xlink:href", function(d) { return d.url; })
			  .append("rect")
			  .attr("height", function(d) { return d.dy; })
			  .attr("width", sankey.nodeWidth())
			  .attr("class", function(d) { return d.id ;})
			  .style("fill", function(d) { 
				  return d.color = d.fill || color(d.name.replace(/ .*/, "")); })
			  .style("stroke", function(d) { 
				  return d3.rgb(d.color).darker(2); })
			.append("title")
			  .text(function(d) { 
				  return d.name + "\n" + format(d.value); });
			
			// add in the title for the nodes
			node.append("text")
			  .attr("x", -6)
			  .attr("y", function(d) { return d.dy / 2; })
			  .attr("dy", ".35em")
			  .attr("text-anchor", "end")
			  .attr("class", function(d) { if (d.value === 0 ) return "hide"; })
			  .attr("transform", null)
			  .text(function(d) { return d.name; })
			.filter(function(d) { return d.x < width / 2; })
			  .attr("x", 6 + sankey.nodeWidth())
			  .attr("text-anchor", "start");
			  
		  var title = san.append("text")
			.attr("class", "santitle")
			.attr("x", 0)             
			.attr("y", -10)
			.attr("text-anchor", "start")  
			.style("font-size", "12px") 
			.style("font-style", "italic")
			.text("Evidence Flow - "+ graph.title);
			
			
		}

					
	})
    .header("Content-type", "application/x-www-form-urlencoded");
	
}


/**
 * Prepares the time series group 
 */
 
var prepareSankey = function(){
    san = svg
        .append('g')
        .attr('id', 'sandisplay')
        .attr('transform', 'translate(' + [TSTRANSLATEX, TSTRANSLATEY] + ')');
}

var addTitle = function(){
	d3.xml(MyAjax.pluginurl + MyAjax.svg_logo, "image/svg+xml", function(xml) {
	  var importedNode = importNode(xml.documentElement, true);
	  var logo = svg
		.append("g")
		.attr('id', 'maplogo')
		.attr('transform', 'scale(' + MyAjax.svg_scale + ')translate(' + [24, 14] + ')');

		document.getElementById('maplogo').appendChild(importedNode.cloneNode(true)); 
		});
    /*svg
        .append('g')
        .attr('id', 'maptitle')
        .attr('transform', 'translate(' + [20, 80] + ')')
		.append("text")
		.attr("text-anchor", "start")  
		.style("font-size", "19px") 
		.text("Evidence Map");*/
}

/**
 * Draws the initial map without any thematic data 
 */
var prepareMap = function(world, names){
    // Projection
    projection = d3.geo.mercator().scale(MAPSCALE).translate([w/2+150, h/2]);
    //var graticule = d3.geo.graticule();
    // Path for projection
    path = d3.geo.path().projection(projection);
    
    map = svg
		.attr("preserveAspectRatio", "xMinYMin meet")
		.attr("viewBox", "0 0 " + w + " " + h )
        .append('g')
        .attr('id', 'map');	
        //.attr('transform', 'translate(' + [MAPTRANSLATEX, MAPTRANSLATEY] + ')');
		
    
    var countries = topojson.feature(world, world.objects.countries).features;
    
    // attach names to country
    countries.forEach(function(d){
        var country = names.filter(function(n) { 
            return d.id == parseInt(n.id); 
        });
        if(country.length !== 0){
            d.name = country[0].name;
			d.slug = country[0].slug;
			d.id = country[0].id;
        }
    });
    
    var country = map.append('g').selectAll('.country').data(countries);

    country
        .enter()
        .append('path')
        .attr('class', 'country')    
        .attr('d', path)
        .attr('id', function(d){ return 'map-' + parseInt(d.id);})
        // tooltip /mouseover
        .on('mouseover', function(d){
            selectCountry(d.id, true);
        })
        .on('mouseout', function(d){
            unselectCountry(d.id, true);
        })
		.on('click', displaySankey)

    // mesh for boundaries
    map
        .append('path')
        .datum(topojson.mesh(world, world.objects.countries, function(a, b) { return a !== b; }))
        .attr('d', path)
        .attr('class', 'country-boundaries');
        
    /*map
        .append('path')
        .datum(graticule)
        .attr('class','graticule')
        .attr('d', path);*/
    
    // groups for circles    
    map
        .append('g')
        .attr('id', 'positiveGroup');
    map
        .append('g')
        .attr('id', 'negativeGroup');
		
	var marker = map
        .append('g')
        .attr('id', 'markers');
		
	
	
    // clip path definitions
    map.select('#positiveGroup').append('defs');
    map.select('#negativeGroup').append('defs');
    var negativeClipPaths = map.select('#negativeGroup').select('defs').selectAll('.negativeClipPath')
        .data(countries);
    // clipping mask for positive
    var positiveClipPaths = map.select('#positiveGroup').select('defs').selectAll('.positiveClipPath')
        .data(countries);
    positiveClipPaths
        .enter()
        .append('clipPath')
        .attr('id', function(d){ return 'cut-off-positive-' + d.id})
        .append('rect')
        // give high enough width and height
        .attr('width', 300)
        .attr('height', 300)
        .attr('x', function(d){ return getCentroid(d.id)[0];})
        .attr('y', function(d){ return getCentroid(d.id)[1] - 150;})
    // clipping mask for production
    negativeClipPaths
        .enter()
        .append('clipPath')
        .attr('id', function(d){ return 'cut-off-negative-' + d.id})
        .append('rect')
        // give high enough width and height
        .attr('width', 300)
        .attr('height', 300)
        .attr('x', function(d){ return getCentroid(d.id)[0] - 300;})
        .attr('y', function(d){ return getCentroid(d.id)[1] - 150;})
}

/**
 * Generates a group within the svg element to contain the barchart elements 
 */
var prepareBarChart = function(){
    // barchart group
    barchart = svg
                .append('g')
                .attr('id', 'barchart')
                .attr('transform', 'translate(' + [BARCHARTTRANSLATEX, BARCHARTTRANSLATEY] + ')');
}
/**
 * Displays the data
 * Calls the function which displays the map, the barchart and the time series
 */
var displayData = function() {    
    var currentData = data[year].clone();
    // strip off world data
    var worlddata = currentData.pop();
    // sort data according to either production or positive
    currentData.sort(function(a, b){ 
        if (a[order] > b[order] || (isNaN(b[order]) && !isNaN(a[order]))) {
            return 1;
        } else if (a[order] < b[order] || (isNaN(a[order]) && !isNaN(b[order]))) {
            return -1;
        // if both are equal (or NaN), sort according to the other attribute
        } else {
            var otherorder = (order === 'negative') ? 'positive' : 'negative';
            if (a[otherorder] > b[otherorder] || (isNaN(b[otherorder]) && !isNaN(a[otherorder]))) {
                return 1;
            } else if (a[otherorder] < b[otherorder] || (isNaN(a[otherorder]) && !isNaN(b[otherorder]))) {
                return -1;
            } else {
                return 0;
            }
        }       
    });
    currentData.reverse();
    // barchart data
    displayBarchartData(currentData, worlddata);
    // map data
    displayMapData(currentData);

    // ts data (initially showing the world)
    //displaySankey(worlddata);
    
}

/*
 * Helper function which returns the geographical centroid for the polygon with the given
 * id
 */
function getCentroid(id){
    // filter for the country with specified id
    var country = map.selectAll('.country').filter(function(d,i){
        return d.id === id;
    });
	
    // certain countries are spread all across the world...
    // in such cases, we need to "nudge" the centroids
    // USA
    if(id === 840){
        return projection([-97, 40]);
    // Russia
    } else if(id === 643){
        return projection([60, 60]);
    // France   
    } else if(id === 250){
        return projection([3, 47]);
    // Norway
    } else if(id === 578){
        return projection([6, 61]);
    // Canada
    } else if(id === 124){
        return projection([-110, 60]);
    }
    // the following is for cases where an object in the data has no counterpart in the geometry (for example Hong Kong, for some reason)
    else if(country[0].length == 0){
        return [10000,10000];
    } else {
        return path.centroid(country.datum().geometry);
    }
}

/**
 * Helper function
 * checks whether a value is NaN 
 */
var isNaN = function(number){
    return (typeOf(number) === 'null') ? true : false;
}

/**
 * Will be called by displayData
 * Shows the bar charts on the left side 
 */
var displayBarchartData = function(data, worlddata) {
    var numFormatter = d3.format(',');
    data = data.slice(0, BARCHARTSHOWONLY);
	var maxVal = d3.max(data, function(d) { return Math.max(d.negative, d.positive);} );
    // add world data
    data.push(worlddata);
    // dimensions
    var width = 600;
    var groupHeight = 20;
    var textWidth = 120;
    var margin = 0;
    var barWidth = (width / 2) - (textWidth / 2) - margin;
    var barHeight = groupHeight - 1;
    // the highest positive (USA) is higher than the highest production, thus always use
    // highest positive as scale reference
    var barchartScale = d3.scale.linear().domain([0,maxVal]).range([1,MAXBARCHARTWIDTH]); 
    
    // DATA JOIN
    var countries = barchart.selectAll('.country').data(data, function(d){ return d.id;});
    
    
    // ENTER
    var countriesEnter = countries
        .enter()
        .append('g')
        .attr('class', 'country')
        // position according to index
        .attr('transform', function(d,i) { return 'translate(0,' + (i * groupHeight + margin) +')'})
        .style('fill-opacity', 1e-6)
        // tooltip /mouseover
        .on('mouseover', function(d){
            selectCountry(d.id, false);
            d3.select(this).select('text').classed('selected', true);
        })
        .on('mouseout', function(d){
            unselectCountry(d.id, false);
            d3.select(this).select('text').classed('selected', false);
        })
		.on('click', displaySankey);

    // add text 
    countriesEnter
        .append('text')
        .attr('x', barWidth + margin + textWidth / 2)
        .attr('y', barHeight / 2)
        .attr('dy', '0.35em')
        .attr('class', 'label')
        .attr('text-anchor', 'middle')
        .text(function(d){ return d.name;});
    // add rectangle with opacity zero behind text (for easier mouseover)  
    countriesEnter
        .append('rect')
        .attr('class', 'hidden')
        .attr('x', barWidth + margin)
        .attr('y', 0)
        .attr('width', textWidth)
        .attr('height', barHeight);
    // the following only applies to ordinary countries
    var onlycountriesEnter = countriesEnter.filter(function(d){
        return d.id !== 900;
    });
    // add negative chart
    onlycountriesEnter
        .append('rect')
        .attr('class', 'negative')
        .attr('height', barHeight)
        .attr('width', 0)
        .attr('x', barWidth + margin);
    // add positive chart
    onlycountriesEnter
        .append('rect')
        .attr('class', 'positive')
        .attr('height', barHeight)
        .attr('width', 0)
        .attr('x', margin + barWidth + textWidth);
    // add values
    // negative
    onlycountriesEnter
        .append('text')
        .attr('x', barWidth + margin - 9)
        .attr('y', barHeight / 2)
        .attr('dy', '0.35em')
        .attr('text-anchor', 'right')
        .attr('class', 'valueNegative')
        .text(function(d){
            if(!isNaN(d.negative)){
                return numFormatter(d.negative);
            } else {
                return 'n/a';
            }
        });
    // positive
    onlycountriesEnter
        .append('text')
        .attr('x', barWidth + textWidth + margin + 5)
        .attr('y', barHeight / 2)
        .attr('dy', '0.35em')
        .attr('text-anchor', 'left')
        .attr('class', 'valuePositive')
        .text(function(d){
            if(!isNaN(d.positive)){
                return numFormatter(d.positive);
            } else {
                return 'n/a';
            }
        });
        
    // the following applies only to world
    var onlyworldEnter = countriesEnter.filter(function(d){
        return d.id === 900;
    });
    // add positive chart
    onlyworldEnter
        .append('rect')
        .attr('class', 'positive')
        .attr('height', barHeight / 2)
        .attr('width', 0)
        .attr('x', margin + barWidth + textWidth);
    onlyworldEnter
        .append('rect')
        .attr('class', 'negative')
        .attr('height', barHeight / 2)
        .attr('width', 0)
        .attr('x', margin + barWidth + textWidth)
        .attr('y', barHeight / 2)
        //.attr('transform', 'translate(0,'+ barHeight / 2 + ')')
    // add values
    // negative
    onlyworldEnter
        .append('text')
        .attr('x', barWidth + textWidth + margin + 5)
        .attr('y', barHeight / 2 + 5)
        .attr('dy', '0.35em')
        .attr('text-anchor', 'right')
        .attr('class', 'valueNegative')
        .text(function(d){
                return numFormatter(d.negative);
        })
        .classed('world', true);
    // positive
    onlyworldEnter
        .append('text')
        .attr('x', barWidth + textWidth + margin + 5)
        .attr('y', 4)
        .attr('dy', '0.35em')
        .attr('text-anchor', 'left')
        .attr('class', 'valuePositive')
        .text(function(d){
            return numFormatter(d.positive);
        })
        .classed('world', true);    
    
    // annotation
    barchart
        .selectAll('.annotation')
        .data([{a:'Only top ' + BARCHARTSHOWONLY + ' countries are shown'}])
        .enter()
        .append('g')
        .append('text')
        .text(function(d){ return d.a;})
        .attr('class','annotation')
        .attr('y', function(){ return data.length * groupHeight + margin + 10;})
        .attr('x', barWidth + textWidth + margin);
    
     
    // UPDATE
    
    // movement of whole group
    var barUpdate = countries
        .transition()
        .duration(TRANSDURATION)
        // position according to index
        .attr('transform', function(d,i) { return 'translate(0,' + (i * groupHeight + margin) +')'})
        .style('fill-opacity', 1);
    
    // transition of charts
    // the following only applies to countries
    barUpdate.filter(function(d){
        return d.id !== 900;
    }).select('.negative')
        .transition()
        .duration(TRANSDURATION)
        .attr('width', function(d){ 
            if(!isNaN(d.negative)){
                return barchartScale(d.negative);  
            } else {
                return 0;
            }
        })
        .attr('x', function(d){ 
            if(!isNaN(d.negative)){
                return barWidth - barchartScale(d.negative) + margin;  
            } else {
                return 0;
            }
        });
    // the following only applies to the world
    barUpdate.filter(function(d){
        return d.id === 900;
    }).select('.negative')
        .transition()
        .duration(TRANSDURATION)
        .attr('width', function(d){             
            return barchartScale(d.negative);  
        });
        
    barUpdate.select('.positive')
        .transition()
        .duration(TRANSDURATION)
        .attr('width', function(d){ 
            if(!isNaN(d.positive)){
                return barchartScale(d.positive);  
            } else {
                return 0;
            }
        }); 
    // movement of values
    // the following only applies to countries
    barUpdate.filter(function(d){
        return d.id !== 900;
    }).select('.valueNegative')    
        .transition()
        .duration(TRANSDURATION)
        .attr('x', function(d){ 
            if(!isNaN(d.negative)){
                return barWidth - barchartScale(d.negative) + margin - 9;  
            } else {
                return barWidth - 20;
            }
        })
        .text(function(d){
            if(!isNaN(d.negative)){
                return numFormatter(d.negative);
            } else {
                return 'n/a';
            }
        });
    // the following only applies to the world
    barUpdate.filter(function(d){
        return d.id === 900;

    }).select('.valueNegative')    
        .transition()
        .duration(TRANSDURATION)
        .attr('x', function(d){      
            return barWidth + textWidth + barchartScale(d.negative) + margin + 5;  
        })
        .text(function(d){
            return numFormatter(d.negative);
        });
    barUpdate.select('.valuePositive')    
        .transition()
        .duration(TRANSDURATION)
        .attr('x', function(d){ 
            if(!isNaN(d.positive)){
                return barWidth + textWidth + barchartScale(d.positive) + margin + 5;  
            } else {
                return barWidth + textWidth + margin + 5;
            }
        })
        .text(function(d){
            if(!isNaN(d.positive)){
                return numFormatter(d.positive);
            } else {
                return 'n/a';
            }
        });
    // movement of annotation
    barchart
        .selectAll('.annotation')
        .transition()
        .duration(TRANSDURATION)
        .attr('y', function(){ return data.length * groupHeight + margin + 10;});

    // EXIT
    countries
        .exit()
        .transition()
        .duration(TRANSDURATION)
        .style('fill-opacity', 1e-6)
        .remove();
        
}

var displayMarkers = function(data){
	marker = map.select('#markers').selectAll('.marker').data(data);

	function _X_1_tpl(str, args) {
		for (var it in args) {
			str = str.replace(it, args[ it ]);
		}
		return str;
	}

	function _tpl(str, obj, pre, suf) {
		var re = new RegExp((pre || "_") + "([a-z]+)" + (suf || ""), "g");
		return str.replace(re, function (m, p1, of) { return obj[ p1 ] });
	}

	// UPDATE
    marker
        .transition()
        .duration(TRANSDURATION)
        // includes a short delay because barchart ordering is done first
        .delay(TRANSDURATION)
        .attr('r', 6/k);
	
	marker.enter()
		.append("svg:a")
	    .attr("xlink:href", function(d) { return d.url; })
	    .attr("xlink:title", function (d) {
	    	return _tpl("_n\nPolarity: _p | Sector: _s", { n: d.name, p: d.polarity, s: d.sector });
	    })
	    .append('circle')
		.attr('class', function(d){ return 'marker '+d.polarity+' '+d.sector} )
		.attr('cx', function(d){ return projection([d.lng,d.lat])[0];})
		.attr('cy', function(d){ return projection([d.lng,d.lat])[1];})
		.style("stroke-width", 2.0 / k + "px")
		/*.on('mouseover', function(d){
			d3.select('#evidence-map').append('div')
            .attr('class', 'tooltip')
            .html('<strong>' + d.name + '</strong><br/>Polarity: ' + d.polarity + '<br/>Sector: ' + d.sector)
            .attr('style', 'left:'+ (d3.event.pageX - 100) +'px;top:'+ (d3.event.pageY - 50) +'px')
            .transition()
            .duration(250)
            .style('opacity', 1);
		})
		.on('mouseout', function() {
				$('.tooltip').hide(250);
				$('.tooltip').html('');
		})*/
		.attr('r', 0)
		.transition()
		.duration(TRANSDURATION)
		.delay(500)
		.attr('r', 6/k);
	
	marker.exit()
        .transition()
        .duration(TRANSDURATION)
        .attr('r', 0)
        .remove();
};

/**
 * Will be called by displayData
 * Fills the map with thematic data
 */
var displayMapData = function(data) {
	var maxVal = d3.max(data, function(d) { return Math.max(d.negative, d.positive);} );
    // the highest positive (USA) is higher than the highest negative, thus always use
    // highest positive as scale reference
    var positiveMapScale = d3.scale.sqrt().domain([0,maxVal]).range([1,MAXCIRCLERADIUS]);
    
    // DATA JOIN
    positive = map.select('#positiveGroup').selectAll('.positive').data(function(d){
        return data.filter(function(d){ return !isNaN(d.positive)});
    }, function(d){ 
        return d.id;
    });

    negative = map.select('#negativeGroup').selectAll('.negative').data(function(d){
        return data.filter(function(d){ return !isNaN(d.negative)});
    }, function(d){ 
        return d.id;
    });
    
    // UPDATE
    positive
        .transition()
        .duration(TRANSDURATION)
        // includes a short delay because barchart ordering is done first
        .delay(TRANSDURATION)
        .attr('r', function(d){ return positiveMapScale(d.positive)*1/k;});
    negative
        .transition()
        .duration(TRANSDURATION)
        // includes a short delay because barchart ordering is done first
        .delay(TRANSDURATION)
        //.delay((!negative.exit().empty() + !negative.enter().empty()) * TRANSDURATION)
        .attr('r', function(d){ return positiveMapScale(d.negative)*1/k;});
    
    // ENTER
    // positive half circles
    positive
        .enter()
        .append('circle')
        .attr('class', 'positive')
        .attr('cx', function(d){ return getCentroid(d.id)[0];})
        .attr('cy', function(d){ return getCentroid(d.id)[1];})
        // interaction
        .on('mouseover', function(d){
            selectCountry(d.id, true);
        })
        .on('mouseout', function(d){
            unselectCountry(d.id, true);
        })
		.on('click', displaySankey)
        // clip path so only half circle is shown
        .attr('clip-path', function(d){ return 'url(#cut-off-positive-' + d.id + ')'})
        .attr('r', 0)
        .transition()
        .duration(TRANSDURATION)
        .delay((!positive.exit().empty() + !positive.enter().empty()) * TRANSDURATION)
        .attr('r', function(d){ return positiveMapScale(d.positive)*1/k;})
        
    // negative half circles
    negative
        .enter()
        .append('circle')
        .attr('class', 'negative')
        .attr('cx', function(d){ return getCentroid(d.id)[0];})
        .attr('cy', function(d){ return getCentroid(d.id)[1];})
        // interaction
        .on('mouseover', function(d){
            selectCountry(d.id, true);
        })
        .on('mouseout', function(d){
            unselectCountry(d.id, true);
        })
		.on('click', displaySankey)
        // clip path so only half circle is shown
        .attr('clip-path', function(d){ return 'url(#cut-off-negative-' + d.id + ')'})
        .attr('r', 0)
        .transition()
        .duration(TRANSDURATION)
        .delay((!negative.exit().empty() + !negative.enter().empty()) * TRANSDURATION)
        .attr('r', function(d){ return positiveMapScale(d.negative)*1/k;});
    
    // EXIT  
    positive
        .exit()
        .transition()
        .duration(TRANSDURATION)
        .attr('r', 0)
        .remove()
    negative
        .exit()
        .transition()
        .duration(TRANSDURATION)
        .attr('r', 0)
        .remove()    
}

// https://gist.github.com/camupod/5165619
function importNode(node, allChildren, doc) {
    var a, i, il;
    doc = doc || document;
    try {
        return doc.importNode(node, allChildren);
    } catch (e) {
        switch (node.nodeType) {
            case document.ELEMENT_NODE:
                var newNode = doc.createElementNS(node.namespaceURI, node.nodeName);
                if (node.attributes && node.attributes.length > 0) {
                    for (i = 0, il = node.attributes.length; i < il; i++) {
                        a = node.attributes[i];
                        try {
                            newNode.setAttributeNS(a.namespaceURI, a.nodeName, node.getAttribute(a.nodeName));
                        } catch (err) {
                            // ignore this error... doesn't seem to make a difference
                        }
                    }
                }
                if (allChildren && node.childNodes && node.childNodes.length > 0) {
                    for (i = 0, il = node.childNodes.length; i < il; i++) {
                        newNode.appendChild(importNode(node.childNodes[i], allChildren));
                    }
                }
                return newNode;
            case document.TEXT_NODE:
            case document.CDATA_SECTION_NODE:
            case document.COMMENT_NODE:
                return doc.createTextNode(node.nodeValue);
        }
    }
}