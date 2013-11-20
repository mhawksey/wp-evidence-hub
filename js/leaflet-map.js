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
			
// Spiderfier close markers
var oms = new OverlappingMarkerSpiderfier(map);

// helper function to return icons for different types 
var customIcon = function (prop){
					if (prop.type == 'evidence' || prop.type == 'project'){
						var m = [prop.type || null, prop.polarity || null, prop.sector || null];
						var c = m;
					} else {
						var m = ['project'];
						var c = [prop.locale || null, prop.sector.replace(", ", " ") || null];	
					}
					m = m.filter(function(v) { return v !== null; });
					c = c.filter(function(v) { return v !== null; });
					return new LeafIcon({iconUrl: iconuri+'marker-'+m.join('-')+'.png',
										 className: ((prop.hypothesis_id) ? 'hyp-'+prop.hypothesis_id+' ' : '')+c.join(' ')})};
// construct custom icon
var LeafIcon = L.Icon.extend({
    options: {
        shadowUrl: iconuri+'marker-shadow.png',
		iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
		shadowSize: [41, 41],

    }
});

// add markers from geoJson written to page (doing it this way becase hubPoints will be cached)			
L.geoJson(hubPoints, {
	pointToLayer: function(feature, latlng) {
            var marker = new L.Marker(new L.LatLng(feature.geometry.coordinates[1],feature.geometry.coordinates[0]),{
                    	 			  icon: customIcon(feature.properties)});
			oms.addMarker(marker);
			return marker;
	}
}).addTo(map);

// popup handling using for OverlappingMarkerSpiderfier
var popup = new L.Popup();
oms.addListener('click', function(marker) {
  popup.setContent(formattedText(marker.feature.properties));
  popup.setLatLng(marker.getLatLng());
  map.openPopup(popup);
});

var formattedText = function (d){
	var tHyp = (d.hypothesis) ? '<div class="poptc h">Hypothesis:</div><div class="poptc v">'+(d.hypothesis)+'</div>' : '',
	tType = (d.type) ? '<div class="poptc h">Type:</div><div class="poptc v">'+toProperCase(d.type)+'</div>' : '',
	tSector = (d.sector) ? '<div class="poptc h">Sector:</div><div class="poptc v">'+toProperCase(d.sector)+'</div>' : '',
	tPol = (d.polarity) ? '<div class="poptc h">Polarity:</div><div class="poptc v">'+toVeCase(d.polarity)+'</div>' : '';
	return '<a href="'+d.url+'"><strong>'+d.name+'</strong></a>' +
			'<div class="popt">' +
			  '<div class="poptr">' + tType +'</div>' +
			  '<div class="poptr">' + tHyp +'</div>' +
			  '<div class="poptr">' + tPol +'</div>' +
			  '<div class="poptr">' + tSector +'</div>' + 
			'</div>' +
			'<div class="poptr">' + d.desc +'</div>';
}
function toProperCase(d){
    return d.replace('-',' ').replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
}
function toVeCase(d) {
    return (d == 'pos') ? '+ve' : '-ve';
}
jQuery('#evidence-map select').on('change', function() {
	var allOf = true;
	var show = [];
	var shadow = jQuery('.leaflet-shadow-pane img');
	var marks = jQuery('.leaflet-marker-pane img');
	shadow.hide();
	marks.hide();
	jQuery('#evidence-map select').each(function(i,v) {
        if (v.value.length > 0){
			if (v.id === 'evidence_hub_hypothesis_id')
				show.push('hyp-'+v.value);
			else 
				show.push(v.value);
			console.log(i);
			allOf = false;
        }
	});
	if (allOf){
		shadow.show();
		marks.show();
	} else {
		jQuery('.leaflet-shadow-pane img').filter('.'+show.join('.')).show();
		jQuery('.leaflet-marker-pane img').filter('.'+show.join('.')).show();
		
	}
})