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

/* Parameters */
var LONCENTER = 10;
var LATCENTER = 30;
var MAPSCALE = 210;
var MAPTRANSLATEX = 500;
var MAPTRANSLATEY = 50;
var BARCHARTTRANSLATEX = -50;
var BARCHARTTRANSLATEY = 200;
var TSTRANSLATEX = 60;
var TSTRANSLATEY = 560;
var MAXCIRCLERADIUS = 60;
var MAXBARCHARTWIDTH = 200;
var TRANSDURATION = 1000;
var BARCHARTSHOWONLY = 15;
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
var data = null;
var dataByCountry = null;
var year = '2012';
var order = 'consumption';



/**
 * Konstruktor
 * Lädt die Geometrien, die wiederum die Daten laden, etc.
 */
var init = function() {
    // create svg panels
    svg = d3.select('#container').append('svg');
    

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
    })).inject(document.getElement('#container'));
    
    // container for current year
    (new Element('div', {
        'id': 'yearDisplay',
        'text': 'Year: ' + year    
    })).inject(ui);
    
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
        'range': [1965,2012],
        'steps': 48,
        // set the current year in the display
        'onChange': function(step){
            ui.getElement('#yearDisplay').set('text', 'Year: ' + step);
            if(data !== null){
                year = parseInt(step);
                displayData();
            }
        },
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
    
    var choiceProduction = (new Element('input', {
        'class': 'option',
        'type': 'radio',
        'checked': (order == 'production') ? true : false,
        'events': {
            'click': function(){
                if(data !== null && order != 'production'){
                    order = 'production';
                    displayData();
                    choiceConsumption.set('checked', false);
                }
            }
        }
    })).inject(ui);
    choiceProduction.grab((new Element('span', {'text':'Production'})), 'after');
    var choiceConsumption = (new Element('input', {
        'class': 'option',
        'type': 'radio',
        'checked': (order == 'consumption') ? true : false,
        'events': {
            'click': function(){
                if(data !== null && order != 'consumption'){
                    order = 'consumption';
                    displayData();
                    choiceProduction.set('checked', false);

                }
            }
        }
    })).inject(ui);
    choiceConsumption.grab((new Element('span', {'text':'Consumption'})), 'after');

}
/**
 * Erstellt die Erläuterungen/das Impressum
 */
var createImpress = function() {
    impressum.setStyle('display', 'block');
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
        .defer(d3.json,'data/world-110m.json')
        .defer(dsv, 'data/consumption.csv')
        .defer(dsv, 'data/production.csv')
        .defer(dsv, 'data/world-country-names.csv')
        .await(prepareData)
}

/**
 * Prepares the data, i.e. fills the data dictionary and links the map
 * geometries to the data 
 */
var prepareData = function(error, world, consumption, production, names){

    // Prepare Map
    prepareMap(world, names);
    
    // Prepare BarChart
    prepareBarChart();
    
    // Prepare timeseries
    prepareTimeseries();
    
    // Prepare data
    // data is an object, containing all countries for every year, where the year
    // is the index and the value is an array with all countries (can be easily used with d3 data joins)
    // every year contains only the countries where at least one of production or consumption is not NaN
    data = {};
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
    dataByCountry = {};
    Object.each(data, function(yearData,year){
        yearData.forEach(function(country,i){
            // initialize array if not existing
            if(typeOf(dataByCountry[country.name]) != 'array'){
                dataByCountry[country.name] = [];
                
            } 
            dataByCountry[country.name].push({'year': year, 'consumption': country.consumption, 'production': country.production});         
        })
    });
	console.log(JSON.stringify(data));
    partReady();
    displayData();    
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
        d3.select('#container')
            .selectAll('.tooltip')
            .transition()
            .duration(250)
            .style('opacity', 1e-6)
            .remove();
    }
    displayTimeseries(dataByCountry['World'], 'World');
}

/** 
 * visually selects a country on the map and displays a tooltip, updates the timeseries
 */

var selectCountry = function(id, showTooltip){
    var numFormatter = d3.format('.2f');
    // select the country data from the map
    var country = d3.select('#map').selectAll('.country').filter(function(d){return d.id == id});
    country
        .classed('selected', true);
    // get the data from the circles
    var countryInfo = d3.select('#map').selectAll('.consumption').filter(function(d){return d.id == id}).data();
    // if no consumption circle, get production circle instead
    if(countryInfo.length == 0){
        countryInfo = d3.select('#map').selectAll('.production').filter(function(d){return d.id == id}).data();
    }
    // construct data object for which one (!) tooltip will be added
    if(country[0].length > 0){
        var data = {
            'name': country.datum().name,
            'production': (countryInfo[0] !== undefined && !isNaN(countryInfo[0].production)) ? numFormatter(countryInfo[0].production / 1000) + ' mb/d': 'n/a',
            'consumption': (countryInfo[0] !== undefined && !isNaN(countryInfo[0].consumption)) ? numFormatter(countryInfo[0].consumption / 1000) + ' mb/d': 'n/a'
        };
    }
    // show tooltip (only the first time)
    if(showTooltip){
        var tooltip = d3.select('#container').append('div')
            .attr('class', 'tooltip')
            .html('<strong>' + data.name + ', ' + year + '</strong><br/>Production: ' + data.production + '<br/>Consumption: ' + data.consumption)
            .attr('style', 'left:'+ (d3.event.pageX ) +'px;top:'+ (d3.event.pageY - 10) +'px')
            .transition()
            .duration(250)
            .style('opacity', 1)
        /* ugly hack */    
        /*d3.select('#container')
            .selectAll('.tooltip')
            .attr('style', 'left:'+ (d3.event.pageX - 150) +'px;top:'+ (d3.event.pageY - 75) +'px');*/
    }
    // if country has data, display it in timeseries
    if(id !== 900 && dataByCountry[data.name] !== undefined){
        displayTimeseries(dataByCountry[country.datum().name], country.datum().name);
    }
}

/**
 * Prepares the time series group 
 */
var prepareTimeseries = function(){
    ts = svg
        .append('g')
        .attr('id', 'timeseries')
        .attr('transform', 'translate(' + [TSTRANSLATEX, TSTRANSLATEY] + ')');
}
/**
 * Draws the initial map without any thematic data 
 */
var prepareMap = function(world, names){
    // Projection
    projection = d3.geo.mercator().rotate([0,0,0]).center([LONCENTER, LATCENTER]).scale(MAPSCALE);
    //var graticule = d3.geo.graticule();
    // Path for projection
    path = d3.geo.path().projection(projection);
    
    map = svg
        .append('g')
        .attr('id', 'map')
        .attr('transform', 'translate(' + [MAPTRANSLATEX, MAPTRANSLATEY] + ')');
    
    var countries = topojson.feature(world, world.objects.countries).features;
    
    // attach names to country
    countries.forEach(function(d){
        var country = names.filter(function(n) { 
            return d.id == parseInt(n.id); 
        });
        if(country.length !== 0){
            d.name = country[0].name;
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
        .attr('id', 'consumptionGroup');
    map
        .append('g')
        .attr('id', 'productionGroup');
        
    // clip path definitions
    map.select('#consumptionGroup').append('defs');
    map.select('#productionGroup').append('defs');
    var productionClipPaths = map.select('#productionGroup').select('defs').selectAll('.productionClipPath')
        .data(countries);
    // clipping mask for consumption
    var consumptionClipPaths = map.select('#consumptionGroup').select('defs').selectAll('.consumptionClipPath')
        .data(countries);
    consumptionClipPaths
        .enter()
        .append('clipPath')
        .attr('id', function(d){ return 'cut-off-consumption-' + d.id})
        .append('rect')
        // give high enough width and height
        .attr('width', 300)
        .attr('height', 300)
        .attr('x', function(d){ return getCentroid(d.id)[0];})
        .attr('y', function(d){ return getCentroid(d.id)[1] - 150;})
    // clipping mask for production
    productionClipPaths
        .enter()
        .append('clipPath')
        .attr('id', function(d){ return 'cut-off-production-' + d.id})
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
    // sort data according to either production or consumption
    currentData.sort(function(a, b){ 
        if (a[order] > b[order] || (isNaN(b[order]) && !isNaN(a[order]))) {
            return 1;
        } else if (a[order] < b[order] || (isNaN(a[order]) && !isNaN(b[order]))) {
            return -1;
        // if both are equal (or NaN), sort according to the other attribute
        } else {
            var otherorder = (order === 'production') ? 'consumption' : 'production';
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
    displayTimeseries(dataByCountry['World'], 'World');
    
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
    var numFormatter = d3.format('.2f');
    data = data.slice(0, BARCHARTSHOWONLY);
    // add world data
    data.push(worlddata);
    // dimensions
    var width = 600;
    var groupHeight = 20;
    var textWidth = 120;
    var margin = 0;
    var barWidth = (width / 2) - (textWidth / 2) - margin;
    var barHeight = groupHeight - 1;
    // the highest consumption (USA) is higher than the highest production, thus always use
    // highest consumption as scale reference
    var barchartScale = d3.scale.linear().domain([0,20732]).range([1,MAXBARCHARTWIDTH]); 
    
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
        });

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
    // add production chart
    onlycountriesEnter
        .append('rect')
        .attr('class', 'production')
        .attr('height', barHeight)
        .attr('width', 0)
        .attr('x', barWidth + margin);
    // add consumption chart
    onlycountriesEnter
        .append('rect')
        .attr('class', 'consumption')
        .attr('height', barHeight)
        .attr('width', 0)
        .attr('x', margin + barWidth + textWidth);
    // add values
    // production
    onlycountriesEnter
        .append('text')
        .attr('x', barWidth + margin - 30)
        .attr('y', barHeight / 2)
        .attr('dy', '0.35em')
        .attr('text-anchor', 'right')
        .attr('class', 'valueProduction')
        .text(function(d){
            if(!isNaN(d.production)){
                return numFormatter(d.production / 1000);
            } else {
                return 'n/a';
            }
        });
    // consumption
    onlycountriesEnter
        .append('text')
        .attr('x', barWidth + textWidth + margin + 5)
        .attr('y', barHeight / 2)
        .attr('dy', '0.35em')
        .attr('text-anchor', 'left')
        .attr('class', 'valueConsumption')
        .text(function(d){
            if(!isNaN(d.consumption)){
                return numFormatter(d.consumption / 1000);
            } else {
                return 'n/a';
            }
        });
        
    // the following applies only to world
    var onlyworldEnter = countriesEnter.filter(function(d){
        return d.id === 900;
    });
    // add consumption chart
    onlyworldEnter
        .append('rect')
        .attr('class', 'consumption')
        .attr('height', barHeight / 2)
        .attr('width', 0)
        .attr('x', margin + barWidth + textWidth);
    onlyworldEnter
        .append('rect')
        .attr('class', 'production')
        .attr('height', barHeight / 2)
        .attr('width', 0)
        .attr('x', margin + barWidth + textWidth)
        .attr('y', barHeight / 2)
        //.attr('transform', 'translate(0,'+ barHeight / 2 + ')')
    // add values
    // production
    onlyworldEnter
        .append('text')
        .attr('x', barWidth + textWidth + margin + 5)
        .attr('y', barHeight / 2 + 5)
        .attr('dy', '0.35em')
        .attr('text-anchor', 'right')
        .attr('class', 'valueProduction')
        .text(function(d){
                return numFormatter(d.production / 1000);
        })
        .classed('world', true);
    // consumption
    onlyworldEnter
        .append('text')
        .attr('x', barWidth + textWidth + margin + 5)
        .attr('y', 4)
        .attr('dy', '0.35em')
        .attr('text-anchor', 'left')
        .attr('class', 'valueConsumption')
        .text(function(d){
            return numFormatter(d.consumption / 1000);
        })
        .classed('world', true);    
    
    // annotation
    barchart
        .selectAll('.annotation')
        .data([{a:'* million barrels per day, only top ' + BARCHARTSHOWONLY + ' countries are shown'}])
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
    }).select('.production')
        .transition()
        .duration(TRANSDURATION)
        .attr('width', function(d){ 
            if(!isNaN(d.production)){
                return barchartScale(d.production);  
            } else {
                return 0;
            }
        })
        .attr('x', function(d){ 
            if(!isNaN(d.production)){
                return barWidth - barchartScale(d.production) + margin;  
            } else {
                return 0;
            }
        });
    // the following only applies to the world
    barUpdate.filter(function(d){
        return d.id === 900;
    }).select('.production')
        .transition()
        .duration(TRANSDURATION)
        .attr('width', function(d){             
            return barchartScale(d.production);  
        });
        
    barUpdate.select('.consumption')
        .transition()
        .duration(TRANSDURATION)
        .attr('width', function(d){ 
            if(!isNaN(d.consumption)){
                return barchartScale(d.consumption);  
            } else {
                return 0;
            }
        }); 
    // movement of values
    // the following only applies to countries
    barUpdate.filter(function(d){
        return d.id !== 900;
    }).select('.valueProduction')    
        .transition()
        .duration(TRANSDURATION)
        .attr('x', function(d){ 
            if(!isNaN(d.production)){
                return barWidth - barchartScale(d.production) + margin - 30;  
            } else {
                return barWidth - 20;
            }
        })
        .text(function(d){
            if(!isNaN(d.production)){
                return numFormatter(d.production / 1000);
            } else {
                return 'n/a';
            }
        });
    // the following only applies to the world
    barUpdate.filter(function(d){
        return d.id === 900;
    }).select('.valueProduction')    
        .transition()
        .duration(TRANSDURATION)
        .attr('x', function(d){      
            return barWidth + textWidth + barchartScale(d.production) + margin + 5;  
        })
        .text(function(d){
            return numFormatter(d.production / 1000);
        });
    barUpdate.select('.valueConsumption')    
        .transition()
        .duration(TRANSDURATION)
        .attr('x', function(d){ 
            if(!isNaN(d.consumption)){
                return barWidth + textWidth + barchartScale(d.consumption) + margin + 5;  
            } else {
                return barWidth + textWidth + margin + 5;
            }
        })
        .text(function(d){
            if(!isNaN(d.consumption)){
                return numFormatter(d.consumption / 1000);
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
/**
 * Will be called by displayData
 * Fills the map with thematic data
 */
var displayMapData = function(data) {
    // the highest consumption (USA) is higher than the highest production, thus always use
    // highest consumption as scale reference
    var consumptionMapScale = d3.scale.sqrt().domain([0,20732]).range([1,MAXCIRCLERADIUS]);
    
    // DATA JOIN
    var consumption = map.select('#consumptionGroup').selectAll('.consumption').data(function(d){
        return data.filter(function(d){ return !isNaN(d.consumption)});
    }, function(d){ 
        return d.id;
    });

    var production = map.select('#productionGroup').selectAll('.production').data(function(d){
        return data.filter(function(d){ return !isNaN(d.production)});
    }, function(d){ 
        return d.id;
    });
    
    // UPDATE
    consumption
        .transition()
        .duration(TRANSDURATION)
        // includes a short delay because barchart ordering is done first
        .delay(TRANSDURATION)
        .attr('r', function(d){ return consumptionMapScale(d.consumption);});
    production
        .transition()
        .duration(TRANSDURATION)
        // includes a short delay because barchart ordering is done first
        .delay(TRANSDURATION)
        //.delay((!production.exit().empty() + !production.enter().empty()) * TRANSDURATION)
        .attr('r', function(d){ return consumptionMapScale(d.production);});
    
    // ENTER
    // consumption half circles
    consumption
        .enter()
        .append('circle')
        .attr('class', 'consumption')
        .attr('cx', function(d){ return getCentroid(d.id)[0];})
        .attr('cy', function(d){ return getCentroid(d.id)[1];})
        // interaction
        .on('mouseover', function(d){
            selectCountry(d.id, true);
        })
        .on('mouseout', function(d){
            unselectCountry(d.id, true);
        })
        // clip path so only half circle is shown
        .attr('clip-path', function(d){ return 'url(#cut-off-consumption-' + d.id + ')'})
        .attr('r', 0)
        .transition()
        .duration(TRANSDURATION)
        .delay((!consumption.exit().empty() + !consumption.enter().empty()) * TRANSDURATION)
        .attr('r', function(d){ return consumptionMapScale(d.consumption);})
        
    // production half circles
    production
        .enter()
        .append('circle')
        .attr('class', 'production')
        .attr('cx', function(d){ return getCentroid(d.id)[0];})
        .attr('cy', function(d){ return getCentroid(d.id)[1];})
        // interaction
        .on('mouseover', function(d){
            selectCountry(d.id, true);
        })
        .on('mouseout', function(d){
            unselectCountry(d.id, true);
        })
        // clip path so only half circle is shown
        .attr('clip-path', function(d){ return 'url(#cut-off-production-' + d.id + ')'})
        .attr('r', 0)
        .transition()
        .duration(TRANSDURATION)
        .delay((!production.exit().empty() + !production.enter().empty()) * TRANSDURATION)
        .attr('r', function(d){ return consumptionMapScale(d.production);});
    
    // EXIT  
    consumption
        .exit()
        .transition()
        .duration(TRANSDURATION)
        .attr('r', 0)
        .remove()
    production
        .exit()
        .transition()
        .duration(TRANSDURATION)
        .attr('r', 0)
        .remove()    
}

/**
 * displays the timeseries data for a particular country/ the world 
 */
var displayTimeseries = function(data, country){
    // pass by value instead of by reference
    var dataToDisplay = data.clone();
    // num formatter
    var numFormatter = d3.format('.2f');
    // dimensions
    var margin = {top: 0, right: 20, bottom: 20, left: 60},
    width = 650 - margin.left - margin.right,
    height = 240 - margin.top - margin.bottom;
    
    // date parser
    var parseDate = d3.time.format('%Y').parse;
    // scales
    var y = d3.scale.linear()
        .range([height, 0]);
    var x = d3.time.scale()
        .range([0, width]);
    // color scale maps production and consumption to their respective colors
    var color = d3.scale.ordinal().range(['rgb(255, 146, 6)', 'rgb(85, 85, 85)']);
    // set domain (using the keys)
    color.domain(d3.keys(dataToDisplay[0]).filter(function(key) { return key !== "year"; }));

    var xAxis = d3.svg.axis()
        .scale(x)
        .orient("bottom");

    var yAxis = d3.svg.axis()
        .scale(y)
        .orient("left");
        
    var line = d3.svg.line()
        .interpolate("linear")
        .x(function(d) { return x(d.date); })
        .y(function(d) { return y(d.value); });
    
    // line is not defined everywhere
    line.defined(function(d){
        return !isNaN(d.value);
    });
    // preprocess date
    dataToDisplay.forEach(function(d){
            d.date = parseDate(d.year);
    })
    // convert data array to date-amount pairs
    var yearlyAmounts = color.domain().map(function(type){
       return {
           'type' : type.charAt(0).toUpperCase() + type.slice(1),
           'values' : dataToDisplay.map(function(d){
               return { 
                   'date': d.date, 
                   'value': +numFormatter(+d[type] / 1000)};
           })
       }
    });
    // specify axis domains
    x.domain(d3.extent(dataToDisplay, function(d) { return d.date; }));
    y.domain([
        0,//d3.min(yearlyAmounts, function(c) { return d3.min(c.values, function(v) { return v.value; }); }),
        d3.max(yearlyAmounts, function(c) { return d3.max(c.values, function(v) { return v.value; }); })
    ]);
    // begin construction of panel
    // delete prior axis
    ts.selectAll('.axis').remove();
    ts.selectAll('.type').remove();
    ts
        .attr('width', width + margin.left + margin.right)
        .attr('height', height + margin.top + margin.bottom)
        
    ts.append('g')
      .attr('class', 'x axis')
      .attr('transform', 'translate(0,' + height + ')')
      .call(xAxis)
      .append('text')
      .attr('class', 'axisText')
      .attr('x', margin.left)
      .attr('y', - 20)
      .attr('dy', '.71em')
      .style('text-anchor', 'start')
      .text(function(){
          return country;
      });

    ts.append('g')
      .attr('class', 'y axis')
      .call(yAxis)
    .append('text')
      .attr('transform', 'rotate(-90)')
      .attr('y', 6)
      .attr('dy', '.71em')
      .style('text-anchor', 'end')
      .text('million barrels per day');
    
    // data join
    var type = ts.selectAll('.type')
        .data(yearlyAmounts)
        .enter()
        .append('g')
        .attr('class', 'type');
    // append curves
    type.append('path')
      .attr('class', 'line')
      .attr('d', function(d) { return line(d.values); })
      .style('stroke', function(d) { return color(d.type); });

    /*city.append('text')
      .datum(function(d) { return {name: d.name, value: d.values[d.values.length - 1]}; })
      .attr('transform', function(d) { return 'translate(' + x(d.value.date) + ',' + y(d.value.temperature) + ')'; })
      .attr('x', 3)
      .attr('dy', '.35em')
      .text(function(d) { return d.name; });*/

    
   
}
