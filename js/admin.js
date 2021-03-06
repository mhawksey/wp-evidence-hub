// JavaScript Document
/*
Modified from:

Plugin Name: Authors Autocomplete Meta Box
Plugin URI: http://wordpress.org/plugins/authors-autocomplete-meta-box
Description: Replaces the default WordPress Author meta box (that has an author dropdown) with a meta box that allows you to select the author via Autocomplete.
Version: 1.1
Author: Rachel Carden
Author URI: http://www.rachelcarden.com
*/

var errorDNA = "The project does not exist. <a href='?post_type=project' target='_blank'>Add New Project if required</a>";

jQuery.noConflict()(function(){
	jQuery.ui.autocomplete.prototype._resizeMenu = function () {
	  var ul = this.menu.element;
	  ul.outerWidth(this.element.outerWidth());
	}
	jQuery( 'input#evidence_hub_project_id_field' ).each( function() {
	
		var $evidence_hub_project_id_field = jQuery( 'input#evidence_hub_project_id_field' );	

		
		// autocomplete new tags
		if ( $evidence_hub_project_id_field.size() > 0 ) {
			$evidence_hub_project_id_field.autocomplete({
				delay: 100,
				minLength: 1,
				appendTo: '#menu-container',
				source: function( $request, $response ){
					jQuery.ajax({
						url: ajaxurl,
						type: 'POST',
						async: true,
						cache: false,
						dataType: 'json',
						data: {
							action: 'evidence_hub_project_callback',
							evidence_hub_project_search_term: $request.term,
						},
						success: function( $data ){
							$response( jQuery.map( $data, function( $item ) {
								return {
									project_id: $item.project_id,
									address: $item.address,
									value: $item.label,
									label: $item.label,
								};
							}));
						}
					});
				},
				search: function( $event, $ui ) {
					autocomplete_eh_remove_error_message();
				},
				select: function( $event, $ui ) {
				
					// stop the loading spinner
					autocomplete_eh_stop_loading_spinner();
				
					// make sure any errors are removed
					autocomplete_eh_remove_error_message();
					
					// change the saved post author
					autocomplete_eh_change_project( $ui.item.project_id, $ui.item.label  );
					
				},
				response: function( $event, $ui ) {
					autocomplete_eh_stop_loading_spinner();
				},
				focus: function( $event, $ui ) {
					autocomplete_eh_stop_loading_spinner();
				},
				close: function( $event, $ui ) {
					autocomplete_eh_stop_loading_spinner();
				},
				change: function( $event, $ui ) {
					// stop the loading spinner
					autocomplete_eh_stop_loading_spinner();
					
					// remove any existing message
					autocomplete_eh_remove_error_message();
					
					// get the saved author display name. we'll need it later.
					if ($ui.item != null)
						autocomplete_eh_change_project( $ui.item.project_id, $ui.item.label );
					else 
						autocomplete_eh_add_error_message( errorDNA );
				}

			}).data( "ui-autocomplete" )._renderItem = function( $ul, $item ) {
				return jQuery( '<li>' ).append( '<a><strong>' + $item.label + '</strong><br />Address: <em>' + $item.address + '</em></a>' ).appendTo( $ul );
			};
	    }		
	});
});

function autocomplete_eh_stop_loading_spinner() {
	jQuery( 'input#evidence_hub_project_id_field' ).removeClass( 'ui-autocomplete-loading' );
}

function autocomplete_eh_remove_error_message() {
	jQuery( '#autocomplete_eh_error_message' ).remove();
}

function autocomplete_eh_add_error_message( $message ) {

	// remove any existing error message
	autocomplete_eh_remove_error_message();
	//jQuery( '#pronamicMapHolder' ).empty();
	// add a new error message
	var $autocomplete_eh_error_message = jQuery( '<div id="autocomplete_eh_error_message">' + $message + '</div>' );
	jQuery( '#evidence_hub_project_id' ).after( $autocomplete_eh_error_message );
	
}

function autocomplete_eh_change_project(id, label){
	var $evidence_hub_project_id_field = jQuery( 'input#evidence_hub_project_id_field' );	
	var $evidence_hub_project_id = jQuery( 'input#evidence_hub_project_id' );
	
	$evidence_hub_project_id_field.val(label);
	$evidence_hub_project_id.val(id);

		
	var $saved_project_id = $evidence_hub_project_id.val();
	
	var $entered_user_value = $evidence_hub_project_id_field.val();

	// see if the user exists
	jQuery.ajax({
		url: ajaxurl,
		type: 'POST',
		async: true,
		cache: false,
		dataType: 'json',
		data: {
			action: 'evidence_hub_if_project_exists_by_value',
			autocomplete_eh_project_value: $entered_user_value,
			autocomplete_eh_project_id: $saved_project_id
		},
		success: function( $project ){
			
			// if the user exists
			if ( $project.valid ) {
				jQuery( '#MapHolder' ).show();
				map.setView([$project.lat, $project.lng], $project.zoom);
				marker.setLatLng([$project.lat, $project.lng]).update();	
				
				if ($project.lat)
					jQuery( '#pgm-lat-field' ).val($project.lat);
				
				if ($project.lng)
					jQuery( '#pgm-lng-field' ).val($project.lng);
					
				if ($project.country)
					jQuery( '#evidence_hub_country' ).val($project.country);
				else
					jQuery( '#evidence_hub_country' ).val('');
				
			} else if ( $project.notamatch ||  $project.noid) {
				jQuery( '#MapHolder' ).hide();
				autocomplete_eh_add_error_message( errorDNA );
			} 			
		}
	});
}

//jQuery(document).ready(function($) {
	jQuery("#pgm-reverse-geocode-button").on('click' ,function() {
		jQuery("#pronamic-google-maps-meta-box").data('pgm-meta-box').reverseGeocode = function() {
			var $ = jQuery;
		    var geocoder = new google.maps.Geocoder();
			var fields = {};
			fields.latitude = $("#pgm-lat-field");
			fields.longitude = $("#pgm-lng-field");
			fields.address = $("#pgm-address-field");
			var project =  new google.maps.LatLng(fields.latitude.val(), fields.longitude.val());

			geocoder.geocode({"latLng": project} , function(results, status) {
				if(status == google.maps.GeocoderStatus.OK) {
					if(results[0]) {
						var address = results[0].formatted_address;
						fields.address.val(address);
						var arrAddress = results[0].address_components;
						$.each(arrAddress, function (i, address_component) {
							if (address_component.types[0] == "country"){ 
								$("#evidence_hub_country").val(address_component.short_name.toLowerCase());
        						console.log("country:"+address_component.short_name.toLowerCase()); 
								return false;
							}
						});
					}
				} else {
					alert(status);
				}
			});
		};
		return false;
	});
	
//});
jQuery("#evidence_hub_hypothesis_id").change(function() {
	var $ = jQuery;
    if ($("#evidence_hub_hypothesis_id option:selected").text().indexOf("–") === -1 ){
		console.log("add");
		$("#evidence_hub_polarity option[value]").css('display','none');
		$("#evidence_hub_polarity").selectedIndex = 0;
    	$("#evidence_hub_polarity option:empty").text('Neutral').removeAttr("style");
		
	} else {
		console.log("remove");
		$("#evidence_hub_polarity option:contains(Neutral)").text('');
		$("#evidence_hub_polarity option[value]").removeAttr("style");
	}
});
