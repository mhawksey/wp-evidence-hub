/**
 @author			mhawksey
 @copyright			CC BY
 @license			MIT License (http://www.opensource.org/licenses/mit-license.php)
 
*/
var iconuri = pluginurl+'images/icons/';

//prepare the map
var map = L.map('map').setView([25, 0], 2);
L.tileLayer("http://{s}.tile.osm.org/{z}/{x}/{y}.png", {
			 attribution: "&copy; <a href=\"http://osm.org/copyright\">OpenStreetMap</a> contributors"}
			).addTo(map);
var MyControl = L.Control.extend({
    options: {
        position: 'topright'
    },

    onAdd: function (map) {
        // create the control container with a particular class name
        var container = L.DomUtil.create('div', 'my-custom-control');

        // ... initialize other DOM elements, add listeners, etc.

        return container;
    }
});

map.addControl(new MyControl());
			
// Spiderfier close markers
//var oms = new OverlappingMarkerSpiderfier(map);

// helper function to return icons for different types 
var customIcon = function (prop){
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
					
					options.iconUrl= iconuri+'marker-'+m.join('-')+'.png';
					
					return new LeafIcon(options)
				};
// construct custom icon
var LeafIcon = L.Icon.extend({});

var formattedText = function (d){
	var tHyp = (d.hypothesis) ? '<div class="poptc h">Hypothesis:</div><div class="poptc v">'+(d.hypothesis)+'</div>' : '',
	tType = (d.type) ? '<div class="poptc h">Type:</div><div class="poptc v">'+toProperCase(d.type)+'</div>' : '',
	tSector = (d.sector) ? '<div class="poptc h">Sector:</div><div class="poptc v">'+toProperCase((typeof d.sector === "string") ? d.sector : d.sector.join(", "))+'</div>' : '',
	tPol = (d.polarity) ? '<div class="poptc h">Polarity:</div><div class="poptc v">'+toVeCase(d.polarity)+'</div>' : '';
	tLoc = (d.locale) ? '<div class="poptc h">Locale:</div><div class="poptc v">'+toProperCase(d.locale)+'</div>' : '';
	return '<a href="'+d.url+'"><strong>'+d.name+'</strong></a>' +
			'<div class="popt">' +
			  '<div class="poptr">' + tType +'</div>' +
			  '<div class="poptr">' + tHyp +'</div>' +
			  '<div class="poptr">' + tPol +'</div>' +
			  '<div class="poptr">' + tSector +'</div>' + 
			  '<div class="poptr">' + tLoc +'</div>' + 
			'</div>' +
			'<div class="poptr">' + d.desc +'</div>';
}

var markers = L.markerClusterGroup({ spiderfyOnMaxZoom: true, showCoverageOnHover: false, zoomToBoundsOnClick: false});
// add markers from geoJson written to page (doing it this way becase hubPoints will be cached)		
	
var markerArray = [];
function renderLayer(switches){
	markerArray = [];
	L.geoJson(hubPoints, {
		onEachFeature: function (feature, layer) {
						var prop = feature.properties;
						if (testSwitches(switches, prop)){			
							marker = new L.Marker(new L.LatLng(feature.geometry.coordinates[1],feature.geometry.coordinates[0]),{
											  icon: customIcon(feature.properties)})
									 .bindPopup(formattedText(feature.properties));
							markerArray.push(marker);
						}
				}
	});
	markers.addLayers(markerArray);
}
var switches = [];
jQuery('#evidence-map select').each(function(i,v) {

	switches[v.id.substring(13)] = v.value;
});
renderLayer(switches);

function testSwitches(s, prop){
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
		if(s[k] !="" && (prop[k] == s[k] ||(typeof prop[k] !== 'string' && prop[k].indexOf(s[k]) > -1))){
			hit +='1';	
		} else {
			hit +='0';
		}
	}
	console.log(set+'|'+hit);
	if (set === hit){
		return true;	
	}
	
	/*if (count === set){
		console.log("Adding:"+prop['type']+":"+prop['name']);
		return true;
	} */
	return false;
}

markers.on('clusterclick', function (a) {
			a.layer.spiderfy();
			//a.layer.zoomToBounds();
		});
map.addLayer(markers);
map.fitBounds(markers.getBounds());
map.invalidateSize();

function toProperCase(d){
    return d.replace('-',' ').replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
}
function toVeCase(d) {
    return (d == 'pos') ? '+ve' : '-ve';
}
jQuery('#evidence-map select').on('change', function() {
	markers.removeLayers(markerArray);
	var switches = [];
	jQuery('#evidence-map select').each(function(i,v) {
		switches[v.id.substring(13)] = v.value;
	});
	renderLayer(switches);
})