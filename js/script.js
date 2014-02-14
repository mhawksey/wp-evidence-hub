// JavaScript Document
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
var tagBox;
var postL10n = {'comma':','};

// return an array with any duplicate, whitespace or values removed
function array_unique_noempty(a) {
	var out = [];
	jQuery.each( a, function(key, val) {
		val = jQuery.trim(val);
		if ( val && jQuery.inArray(val, out) == -1 )
			out.push(val);
		} );
	return out;
}

(function($){
	tagBox = {
		clean : function(tags) {
			var comma = postL10n.comma;
			if ( ',' !== comma )
				tags = tags.replace(new RegExp(comma, 'g'), ',');
			tags = tags.replace(/\s*,\s*/g, ',').replace(/,+/g, ',').replace(/[,\s]+$/, '').replace(/^[,\s]+/, '');
			if ( ',' !== comma )
				tags = tags.replace(/,/g, comma);
			return tags;
		},
	
		parseTags : function(el) {
			var id = el.id, num = id.split('-check-num-')[1], taxbox = $(el).closest('.tagsdiv'),
				thetags = taxbox.find('.the-tags'), comma = postL10n.comma,
				current_tags = thetags.val().split(comma), new_tags = [];
			delete current_tags[num];
	
			$.each( current_tags, function(key, val) {
				val = $.trim(val);
				if ( val ) {
					new_tags.push(val);
				}
			});
	
			thetags.val( this.clean( new_tags.join(comma) ) );
	
			this.quickClicks(taxbox);
			return false;
		},
	
		quickClicks : function(el) {
			var thetags = $('.the-tags', el),
				tagchecklist = $('.tagchecklist', el),
				id = $(el).attr('id'),
				current_tags, disabled;
	
			if ( !thetags.length )
				return;
	
			disabled = thetags.prop('disabled');
	
			current_tags = thetags.val().split(postL10n.comma);
			tagchecklist.empty();
	
			$.each( current_tags, function( key, val ) {
				var span, xbutton;
	
				val = $.trim( val );
	
				if ( ! val )
					return;
	
				// Create a new span, and ensure the text is properly escaped.
				span = $('<span />').text( val );
	
				// If tags editing isn't disabled, create the X button.
				if ( ! disabled ) {
					xbutton = $( '<a id="' + id + '-check-num-' + key + '" class="ntdelbutton">X</a>' );
					xbutton.click( function(){ tagBox.parseTags(this); });
					span.prepend('&nbsp;').prepend( xbutton );
				}
	
				// Append the span to the tag list.
				tagchecklist.append( span );
			});
		},
	
		flushTags : function(el, a, f) {
			var tagsval, newtags, text,
				tags = $('.the-tags', el),
				newtag = $('input.newtag', el),
				comma = postL10n.comma;
			a = a || false;
	
			text = a ? $(a).text() : newtag.val();
			tagsval = tags.val();
			newtags = tagsval ? tagsval + comma + text : text;
	
			newtags = this.clean( newtags );
			newtags = array_unique_noempty( newtags.split(comma) ).join(comma);
			tags.val(newtags);
			this.quickClicks(el);
	
			if ( !a )
				newtag.val('');
			if ( 'undefined' == typeof(f) )
				newtag.focus();
	
			return false;
		},
	
		get : function(id) {
			var tax = id.substr(id.indexOf('-')+1);
	
			$.post(ajaxurl, {'action':'get-tagcloud', 'tax':tax}, function(r, stat) {
				if ( 0 === r || 'success' != stat )
					r = wpAjax.broken;
	
				r = $('<p id="tagcloud-'+tax+'" class="the-tagcloud">'+r+'</p>');
				$('a', r).click(function(){
					tagBox.flushTags( $(this).closest('.inside').children('.tagsdiv'), this);
					return false;
				});
	
				$('#'+id).after(r);
			});
		},
	
		init : function() {
			var t = this, ajaxtag = $('div.ajaxtag');
	
			$('.tagsdiv').each( function() {
				tagBox.quickClicks(this);
			});
	
			$('input.tagadd', ajaxtag).click(function(){
				t.flushTags( $(this).closest('.tagsdiv') );
			});
	
			$('div.taghint', ajaxtag).click(function(){
				$(this).css('visibility', 'hidden').parent().siblings('.newtag').focus();
			});
	
			$('input.newtag', ajaxtag).blur(function() {
				if ( '' === this.value )
					$(this).parent().siblings('.taghint').css('visibility', '');
			}).focus(function(){
				$(this).parent().siblings('.taghint').css('visibility', 'hidden');
			}).keyup(function(e){
				if ( 13 == e.which ) {
					tagBox.flushTags( $(this).closest('.tagsdiv') );
					return false;
				}
			}).keypress(function(e){
				if ( 13 == e.which ) {
					e.preventDefault();
					return false;
				}
			}).each(function(){
				var tax = $(this).closest('div.tagsdiv').attr('id');
				$(this).suggest( ajaxurl + '?action=ajax-tag-search&tax=' + tax, { delay: 500, minchars: 2, multiple: true, multipleSep: postL10n.comma + ' ', resultsClass: 'eh_results' } );
				
			});
	
			// save tags on post save/publish
			$('#post').submit(function(){
				$('div.tagsdiv').each( function() {
					tagBox.flushTags(this, false, 1);
				});
			});
	
			// tag cloud
			$('a.tagcloud-link').click(function(){
				tagBox.get( $(this).attr('id') );
				$(this).unbind().click(function(){
					$(this).siblings('.the-tagcloud').toggle();
					return false;
				});
				return false;
			});
		}
	};
}(jQuery));

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
	



	jQuery(document).ready(function( $ ) {
		// multi-taxonomies
		if ( $('#tagsdiv-post_tag').length && typeof(eadminpage) == 'undefined') {
			tagBox.init();
			
		}
		$('#publish').click(function(){
			if (typenow == 'evidence'){ 
				event.preventDefault();
				if (!isValidateForm()){
					$('#post').submit();
				} else {
					$('#publishing-action .spinner').hide();
					$('#publish').prop('disabled', false).removeClass('button-primary-disabled');
				}
				return false;
			}
		});
		
		$('#add_data').click(function(){
			$('div.tagsdiv').each( function() {
					tagBox.flushTags(this, false, 1);
			});
			$('#tags').val($('.the-tags', $('div.tagsdiv')).val());
			if (!isValidateForm()){
				$(this).attr("disabled", true);
				$('.spinner').show();
				getNonce();	
			}
			return false;
		});
		
		$('#evidence_hub_options .date').datepicker({dateFormat : 'dd/mm/yy'});
		
		$("#evidence_hub_hypothesis_id").change(function() {
			if ($("#evidence_hub_hypothesis_id option:selected").text().indexOf("–") === -1 ){
				$("input#evidence_hub_polarity").each(function(){
					console.log($(this));
					$(this).prop('checked', false);
					$(this).attr("disabled", true);
				})
			} else {
				$("input#evidence_hub_polarity").removeAttr("disabled");
			}
		});
		
		$( "select#type" ).change(function() {
			var what = "form#content_for_"+$(this).val();
			$( "form[id^=content_for]" ).fadeOut();
			$( what ).fadeIn(500); 
			console.log($(this).val());
			
		});
	});
	
	function isValidateForm(){
		var $ = jQuery;
		var error = false;
		var title = $('#title');
		if (typeof(typenow) == 'undefined'){
			var type = $('select#type').val()
		} else {
			var type = typenow;
		}
		if (type === 'evidence'){
			var hyp = $('#evidence_hub_hypothesis_id');
			var pol = $('#evidence_hub_polarity');
		}
		if (typeof(adminpage) != 'undefined'){
			msg_box = $('.wrap h2');
		} else {
			msg_box = $('.action-area #status');
		}
		
		$('.error').remove();
		if(title.val() == ''){
			msg_box.append('<div id="message" class="error fade"><p> Please enter a title </p></div>');
			error = true;
		}
		if (type === 'evidence'){
			if(hyp.val() == ''){
				msg_box.append('<div id="message" class="error fade"><p> Please select a hypothesis </p></div>');
				error = true;
			}
			if(!($('#evidence_hub_hypothesis_id option:last').is(':selected')) && typeof($('#evidence_hub_polarity:checked').val()) =='undefined'){
				msg_box.append('<div id="message" class="error fade"><p> Please select a polarity </p></div>');	
				error = true;
			}
		}		
		return error;
	}
	
	function getNonce(){
		jQuery.ajax({
			url: MyAjax.apiurl+'/?json=get_nonce&controller=hub&method=create_evidence',
			type: 'GET',
			dataType: 'json',
			success: addContent
		});
	}
	
	function addContent(data){
		jQuery('input#nonce').val(data.nonce);
		tinyMCE.triggerSave();
		var type = jQuery('select#type').val();
		var content = jQuery('textarea#post_content_'+type ).val();
		jQuery('#common_entry > input#content').val(content);
		jQuery.ajax({
			   type: "POST",
			   url: MyAjax.apiurl+'/?json=hub.create_evidence',
			   data: jQuery("#common_entry, #tagsdiv-post_tag, #pgm_map, #content_for_"+type).serialize(), // serializes the form's elements.
			   success: function(data) {
				   jQuery('#add_data').text("Submitted");
				   jQuery('.action-area #status').append('<div id="message" class="success fade"> Thank you! Your contribution has been sent for review </div>');
				   jQuery('.spinner').hide();
			   },
			   error: function(data) {
				   jQuery('#add_data').text("Submit for Review").removeAttr("disabled");
				   jQuery('.action-area #status').append('<div id="message" class="error fade"> Oops something went wrong. Please try submitting again </div>');
				   jQuery('.spinner').hide();
			   }
			 });
	
	}
});

