/**
 @author			mhawksey
 @copyright			CC BY
 @license			MIT License (http://www.opensource.org/licenses/mit-license.php)
 
*/
var OERRH = OERRH || {};

window.console && console.log("OERRH:", OERRH);

var iconuri = pluginurl+'images/icons/';

//prepare the map
var map = OERRH.geomap.map = L.map('map', { minZoom: 1 }).setView(OERRH.geomap.center || [25, 0], 2);
L.tileLayer("http://{s}.tile.osm.org/{z}/{x}/{y}.png", {
	attribution:
		"&copy; <a href=\"http://osm.org/copyright\">OpenStreetMap</a> contributors" +
		(OERRH.geomap.attribution || "")
	}).addTo(map);
var filterControl = L.Control.extend({
    options: {
        position: OERRH.geomap.filter_position || 'topright'
    },

    onAdd: function (map) {
        // create the control container with a particular class name
        var controlDiv = L.DomUtil.create('div', 'my-custom-control');
		L.DomEvent.disableClickPropagation(controlDiv);
		return controlDiv;
    }
});
map.addControl(new filterControl());



// Spiderfier close markers
//var oms = new OverlappingMarkerSpiderfier(map);

// helper function to return icons for different types 
var customIcon = function (prop, className) {
					var options = {	shadowUrl: iconuri+'marker-shadow.png',
									iconSize: [25, 41],
									iconAnchor: [12, 41],
									popupAnchor: [1, -34],
									shadowSize: [41, 41]};
	
					if (prop.type == 'evidence' || prop.type == 'project'){
						var m = [prop.type || null, prop.polarity || null];
					} else if (prop.type == 'policy'){
						var m = [prop.type || null, prop.locale || null];
						var options = {	shadowUrl: iconuri+'marker-shadow.png',
										iconSize: [34, 34],
										iconAnchor: [12, 34],
										popupAnchor: [1, -34],
										shadowSize: [34, 34]};
					} else {
						var m = ['project'];
					}
					m = m.filter(function(v) { return v !== null; });
					
					options.iconUrl = iconuri+'marker-'+m.join('-')+'.png';
					options.className = 'id-' + prop.id +' '+ className;
					return new LeafIcon(options)
				};
// construct custom icon
var LeafIcon = L.Icon.extend({});

var formattedText = function (d){
	var hypothesis_word = OERRH.geomap.hypothesis_word || 'Hypothesis',
	tHyp = d.hypothesis ? '<div class="poptc h">'+ hypothesis_word +':</div><div class="poptc v">'+d.hypothesis+'</div>' : '',
	tType = (d.type) ? '<div class="poptc h">Type:</div><div class="poptc v">'+toProperCase(d.type)+'</div>' : '',
	tSector = (d.sector) ? '<div class="poptc h">Sector:</div><div class="poptc v">'+toProperCase((typeof d.sector === "string") ? d.sector : d.sector.join(", "))+'</div>' : '',
	tPol = /*(d.polarity) ?*/ '<div class="poptc h">Polarity:</div><div class="poptc v">'+toVeCase(d.polarity)+'</div>'; //: '';
	tLoc = (d.locale) ? '<div class="poptc h">Locale:</div><div class="poptc v">'+toProperCase(d.locale)+'</div>' : '';
	tUrl = (d.url) ? '<a href="'+d.url+'" class="geo_pop">Read more..</a>' : '';
	return '<a href="'+d.url+'"><strong>'+d.name+'</strong></a>' +
			'<div class="popt">' +
			  '<div class="poptr">' + tType +'</div>' +
			  '<div class="poptr">' + tHyp +'</div>' +
			  '<div class="poptr">' + tPol +'</div>' +
			  '<div class="poptr">' + tSector +'</div>' + 
			  '<div class="poptr">' + tLoc +'</div>' + 
			'</div>' +
			'<div class="poptr">' + d.desc +' '+tUrl+'</div>';
}

//var markers = L.markerClusterGroup({ spiderfyOnMaxZoom: true, showCoverageOnHover: false, zoomToBoundsOnClick: false, disableClusteringAtZoom: 4});
var markers = L.markerClusterGroup({ spiderfyOnMaxZoom: true, showCoverageOnHover: false, zoomToBoundsOnClick: false});
// add markers from geoJson written to page (doing it this way becase hubPoints will be cached)		
	
var markerArray = [];
//var tableArray = [];
var markerMap = {};
var row = [];
var switches = [];
jQuery('#evidence-map select').each(function(i,v) {
	switches[v.id.substring(13)] = v.value;
	row.push(v.id.substring(13));
});

/*generateTable();

function generateTable(){
	var d = json['geoJSON'] || null;
	var row = ["id", "type", "name", "desc", "url", "sector", "polarity", "project", "hypothesis_id", "hypothesis", "locale"];
	if (d){
		tableArray.push(row);
		for (var i=0,  tI=d.length; i < tI; i++) {
			var row = [];
			for (var j=0,  tJ=tableArray[0].length; j < tJ; j++) {
				if (d[i].properties[tableArray[0][j]] instanceof Array) {
					row.push(d[i].properties[tableArray[0][j]].join(","));
				} else {
					row.push(d[i].properties[tableArray[0][j]]);
				}
			}
			tableArray.push(row);
		}
	}
}*/


renderLayer(switches);

function renderLayer(switches){
	markerArray = [];
	L.geoJson(hubPoints, {
		onEachFeature: function (feature, layer) {
						var prop = feature.properties
						  , coord = feature.geometry.coordinates
						  , no_loc = OERRH.geomap.no_location_latlng
						  // Evidence located at or v. near [0, 0] degrees.
						  , near_zero = function (deg) { return Math.abs(deg) < 2; }
						  , class_name = ''
						  , no_loc_text= '';

						if (testSwitches(switches, prop)){

							// Move "no location" markers [LACE][Bug: #50]
							//if (no_loc && !coord[0] && !coord[1])
							if (no_loc && near_zero(coord[0]) && near_zero(coord[1])) {
								window.console && console.log("No location?", coord, prop);

								// Re-locate ... swap!
								coord[ 0 ] = no_loc[ 1 ];
								coord[ 1 ] = no_loc[ 0 ];
								class_name = "lace-no-location-marker";
								no_loc_text = "No location given";
							}

							marker = new L.Marker(new L.LatLng(coord[1], coord[0]), {
									icon: customIcon(prop, class_name),
									title: no_loc_text
								})
								.bindPopup(formattedText(prop));
							
							markerMap[prop.id] = marker;
							markerArray.push(marker);
						}
				}
	});
	markers.addLayers(markerArray);
}

function testSwitches(s, prop){
	var row = [];
	if (s['type'] == 'evidence' && prop['polarity'] == '' && prop['hypothesis'] != 'Unassigned'){
		return false;	
	}
	var allS = Object.keys(s).map(function(x){return s[x];}).join('');
	if (allS == ""){
		return true;	
	}
	var set = "";
	var hit = "";
	for (var k in s) {
		if (s[k] !=""){
			set += '1';	
		} else {
			set += '0';
		}
		if(s[k] !="" && (prop[k] == s[k] || (prop[k]!="" && prop[k] instanceof Array && prop[k].indexOf(s[k]) > -1))){
			hit +='1';	
		} else {
			hit +='0';
		}
		row.push(prop[k]);
	}
	if (set === hit){
		tableArray.push(row);
		return true;	
	}
	
	return false;
}

markers.on('clusterclick', function (a) {
			a.layer.spiderfy();
		});
map.addLayer(markers);
var addTitle = function(){
	
	d3.xml(pluginurl+'images/logo.svg', "image/svg+xml", function(xml) {  
	  var importedNode = importNode(xml.documentElement, true);
		jQuery('.leaflet-top.leaflet-left').append('<div id="maplogoholder"></div>'); 
		var logo = d3.select('#maplogoholder')
		.append('svg')
		.append("g")
		.attr('id', 'maplogo')
		.attr('transform', 'scale(' + [0.7,0.7] + ')translate(' + [24, 14] + ')');;
		
		document.getElementById('maplogo').appendChild(importedNode.cloneNode(true)); 
	});
};
//map.fitBounds(markers.getBounds());
//map.invalidateSize();
//addTitle();

function toProperCase(d){
    return d.replace('-',' ').replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
}
// [Bug: #55]
function toVeCase(d) {
    //return ('pos' === d) ? '+ve' : ('neg' === d) ? '-ve' : 'neutral';
    switch (d) {
        case 'pos': return '+ve';
        case 'neg': return '-ve';
        case 'neutral': return 'neutral';
        default: return '(not given)';
    }
}
jQuery('#evidence-map select').on('touchstart change', function() {
	markers.removeLayers(markerArray);
	var switches = [];
	jQuery('#evidence-map select').each(function(i,v) {
		switches[v.id.substring(13)] = v.value;
	});
	renderLayer(switches);
});

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
function customPop(url) {
	var D=500,A=600,C=screen.height,B=screen.width,H=Math.round((B/2)-(D/2)),G=0,F=document,E;
	if(C>A){G=Math.round((C/2)-(A/2))}
	shareWin=window.open(url,'ShareWin','left='+H+',top='+G+',width='+D+',height='+A+',personalbar=0,toolbar=0,scrollbars=1,resizable=1');
	if (window.focus) {shareWin.focus()}
	/*E=F.createElement('script');
	E.src='//platform.twitter.com/bookmarklets/share.js?v=1';
	F.getElementsByTagName('head')[0].appendChild(E);*/
}

jQuery(document).ready(function($){
		jQuery('.google-visualization-table-td').each(function(i,v) {

		v.addEventListener(
					 'click',
					 console.log("T"),
					 false
				  );
				 
	});
	$(".google-visualization-table-td").click(function () {
		console.log(this);
	});
	$(".tbl-header").click(function () {
	
		$header = $(this);
		//getting the next element
		$content = $header.next();
		//open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
		$content.slideToggle(100, function () {
			//execute this after slideToggle is done
			//change text of header based on visibility of content div
			$header.find('.expander').text(function () {
				//change text based on condition
				return $content.is(":visible") ? "▼" : "▲";
			});
		});
	
	});
	$('a.geo_pop').live('click', function(){
		customPop($(this).attr('href'));
		return false;
	});


	// Mark "no location" evidence [LACE][Bug: #50]
	var no_loc = OERRH.geomap.no_location_latlng;
	if (no_loc) {

		var lace_no_location_pop = L.popup({ className: "lace-no-location-pop" })
			.setLatLng(no_loc)
			.setContent("No location given")
			.openOn(map);
	}

});