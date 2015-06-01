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

/*
 *	jquery.suggest 1.1b - 2007-08-06
 * Patched by Mark Jaquith with Alexander Dick's "multiple items" patch to allow for auto-suggesting of more than one tag before submitting
 * See: http://www.vulgarisoip.com/2007/06/29/jquerysuggest-an-alternative-jquery-based-autocomplete-library/#comment-7228
 *
 *	Uses code and techniques from following libraries:
 *	1. http://www.dyve.net/jquery/?autocomplete
 *	2. http://dev.jquery.com/browser/trunk/plugins/interface/iautocompleter.js
 *
 *	All the new stuff written by Peter Vulgaris (www.vulgarisoip.com)
 *	Feel free to do whatever you want with this file
 *
 */

(function($) {

	$.lookup = function(input, options) {
		var $input, $results, $results_block, timeout, prevLength, cache, cacheSize;

		$input = $(input).attr("autocomplete", "off");
		
		$results_block = $("<div><p>"+options.description+"</p></div>");
		$results = $("<ul/>");

		timeout = false;		// hold timeout ID for suggestion results to appear
		prevLength = 0;			// last recorded length of $input.val()
		cache = [];				// cache MRU list
		cacheSize = 0;			// size of cache in chars (bytes?)

		$results.addClass(options.resultsClass);
		$results_block.addClass(options.resultsBlockClass).insertAfter(options.attachTo);
		$results_block.append($results);
		$results_block.hide();

		$input.keydown(processKey);
		if ($.trim($input.val()).length >= options.minchars) {
			lookup();
		}

		function processKey(e) {

			// handling up/down/escape requires results to be visible
			// handling enter/tab requires that AND a result to be selected
			if ((/27$|38$|40$/.test(e.keyCode) && $results.is(':visible')) ||
				(/^13$|^9$/.test(e.keyCode) && getCurrentResult())) {

				if (e.preventDefault)
					e.preventDefault();
				if (e.stopPropagation)
					e.stopPropagation();

				e.cancelBubble = true;
				e.returnValue = false;

				switch(e.keyCode) {

					case 38: // up
						prevResult();
						break;

					case 40: // down
						nextResult();
						break;

					case 9:  // tab
					case 13: // return
						selectCurrentResult();
						break;

					case 27: //	escape
						$results_block.hide();
						break;

				}

			} else if ($input.val().length != prevLength) {

				if (timeout)
					clearTimeout(timeout);
				timeout = setTimeout(lookup, options.delay);
				prevLength = $input.val().length;

			}


		}


		function lookup() {

			var q = $.trim($input.val()), multipleSepPos, items;

			if ( options.multiple ) {
				multipleSepPos = q.lastIndexOf(options.multipleSep);
				if ( multipleSepPos != -1 ) {
					q = $.trim(q.substr(multipleSepPos + options.multipleSep.length));
				}
			}
			if (q.length >= options.minchars) {
				console.log("checking cached..");
				cached = checkCache(q);

				if (cached) {
					console.log("getting cached..");
					displayItems(cached['items']);

				} else {

					$.getJSON(options.source, {q: q, post_id: $('input[name="post_ID"]').val()}, function(txt) {
						console.log("getting results..");
						$results_block.hide();

						items = parseTxt(txt);

						displayItems(items);
						addToCache(q, items, txt.length);

					});

				}

			} else {
				console.log("I'm hiding..");
				$results_block.hide();

			}

		}


		function checkCache(q) {
			var i;
			for (i = 0; i < cache.length; i++)
				if (cache[i]['q'] == q) {
					cache.unshift(cache.splice(i, 1)[0]);
					return cache[0];
				}

			return false;

		}

		function addToCache(q, items, size) {
			var cached;
			while (cache.length && (cacheSize + size > options.maxCacheSize)) {
				cached = cache.pop();
				cacheSize -= cached['size'];
			}

			cache.push({
				q: q,
				size: size,
				items: items
				});

			cacheSize += size;

		}

		function displayItems(items) {
			var html = '', i;
			if (!items)
				return;

			if (!items.length) {
				$results_block.hide();
				return;
			}
			console.log(items);
			//resetPosition(); // when the form moves after the page has loaded

			for (i = 0; i < items.length; i++)
				html += '<li>' + items[i] + '</li>';

			$results.html(html);
			$results_block.show();

			/*$results
				.children('li')
				.mouseover(function() {
					$results.children('li').removeClass(options.selectClass);
					$(this).addClass(options.selectClass);
				})
				.click(function(e) {
					e.preventDefault();
					e.stopPropagation();
					selectCurrentResult();
				});*/

		}

		function parseTxt(results) {

			var items = [];
			
			for (i = 0; i < results.length; i++) {
				items.push('<strong>'+results[i].title+'</strong><br/> <a href="'+results[i].url+'" target="_blank">'+results[i].url+'</a>');
			}

			return items;
		}

		function getCurrentResult() {
			var $currentResult;
			if (!$results.is(':visible'))
				return false;

			$currentResult = $results.children('li.' + options.selectClass);

			if (!$currentResult.length)
				$currentResult = false;

			return $currentResult;

		}

		function selectCurrentResult() {

			$currentResult = getCurrentResult();

			if ($currentResult) {
				if ( options.multiple ) {
					if ( $input.val().indexOf(options.multipleSep) != -1 ) {
						$currentVal = $input.val().substr( 0, ( $input.val().lastIndexOf(options.multipleSep) + options.multipleSep.length ) );
					} else {
						$currentVal = "";
					}
					$input.val( $currentVal + $currentResult.text() + options.multipleSep);
					$input.focus();
				} else {
					$input.val($currentResult.text());
				}
				$results_block.hide();
				$input.trigger('change');

				if (options.onSelect)
					options.onSelect.apply($input[0]);

			}

		}

		function nextResult() {

			$currentResult = getCurrentResult();

			if ($currentResult)
				$currentResult
					.removeClass(options.selectClass)
					.next()
						.addClass(options.selectClass);
			else
				$results.children('li:first-child').addClass(options.selectClass);

		}

		function prevResult() {
			var $currentResult = getCurrentResult();

			if ($currentResult)
				$currentResult
					.removeClass(options.selectClass)
					.prev()
						.addClass(options.selectClass);
			else
				$results.children('li:last-child').addClass(options.selectClass);

		}
	}

	$.fn.lookup = function(source, options) {

		if (!source)
			return;

		options = options || {};
		options.multiple = options.multiple || false;
		options.multipleSep = options.multipleSep || ", ";
		options.source = source;
		options.delay = options.delay || 100;
		options.resultsBlockClass  = options.resultsBlockClass || 'lookup_results';
		options.resultsClass = options.resultsClass || 'lookup_results_list';
		options.selectClass = options.selectClass || 'lookup_over';
		options.matchClass = options.matchClass || 'lookup_match';
		options.minchars = options.minchars || 2;
		options.delimiter = options.delimiter || '\n';
		options.onSelect = options.onSelect || false;
		options.maxCacheSize = options.maxCacheSize || 65536;
		options.attachTo = options.attachTo || 'body';
		options.description = options.description || '';

		this.each(function() {
			new $.lookup(this, options);
		});

		return this;

	};

})(jQuery);

jQuery.noConflict()(function(){
	
	jQuery.ui.autocomplete.prototype._resizeMenu = function () {
	  var ul = this.menu.element;
	  ul.outerWidth(this.element.outerWidth());
	}
	
	jQuery( 'input.lookup[type="text"]' ).each( function() {
		jQuery(this).lookup( ajaxurl + '?action=evidence_match_lookup&lookup_field=' + jQuery(this).attr('id'), { attachTo: jQuery(this), description: 'Possible existing results:', delay: 500, minchars: 10 } );
	});
	
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
				map.invalidateSize();
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
		jQuery( 'input.lookup[type="text"]' ).trigger('change');
		// multi-taxonomies
		if (typeof(typenow) !== 'undefined'){
			/*if (typenow == 'evidence'){ 
				$('#publish').click(function(){
						if (!isValidateForm()){
							$('#post').submit();
						} else {
							$('#publishing-action .spinner').hide();
							$('#publish').prop('disabled', false).removeClass('button-primary-disabled');
						}
						return false;
					
				});
			}*/
		} else {
		
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
			
			$( "select#type" ).on('change', function() {
				var what = "form#content_for_"+$(this).val();
				$( "form[id^=content_for]" ).fadeOut();
				$( what ).fadeIn(500); 			
			});
		}
		$('#evidence_hub_options .date').datepicker({dateFormat : 'dd/mm/yy'});
		
		$("#evidence_hub_hypothesis_id").change(function() {
			if ($("#evidence_hub_hypothesis_id option:selected").text() === "" ){
				$("input#evidence_hub_polarity").each(function(){
					$(this).prop('checked', false);
					$(this).attr("disabled", true);
				})
			} else {
				$("input#evidence_hub_polarity").removeAttr("disabled");
			}
		});
		jQuery("#evidence_hub_post_type").change(function() {
			$('#post').submit();
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
			msg_box = $('#submitpost');
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
			url: MyAjax.apiurl.replace('%s', 'get_nonce') + 'controller=hub&method=create_evidence',
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
				   console.log(data);
				   if(data.status == "ok"){
					   jQuery('#add_data').text("Submitted");
					   jQuery('#eh-entry').before('<div id="status"><div id="message" class="success fade"> <p>Thank you! Your contribution has been sent for review</p> <p>'+data.edit_link_html+'</p> </div></div>');
				   } else {
					   jQuery('#add_data').text("Submit for Review").removeAttr("disabled");
					   jQuery('#eh-entry').before('<div id="status"><div id="message" class="error fade"> <p>Oops something went wrong. Please try submitting again</p><p>Error: '+data.error+'</p> </div></div>');
				   }
				   jQuery('.spinner').hide();
				   jQuery('#eh-entry').hide();
			   },
			   error: function(data) {
				   jQuery('#add_data').text("Submit for Review").removeAttr("disabled");
				   jQuery('.action-area #status').append('<div id="message" class="error fade"> <p>Oops something went wrong. Please try submitting again</p><p>Error: '+data.error+'</p> </div>');
				   jQuery('.spinner').hide();
			   }
			 });
	
	}


	// when_call: https://gist.github.com/nfreear/f40470e1aec63f442a8a
	function when_call(when_true_FN, callback_FN, interval) {
		var int_id = setInterval(function () {
			if (when_true_FN()) {
				clearInterval(int_id);
				callback_FN();
			}
		}, interval || 200); // Milliseconds.
	}


	// Improve usability of evidence map search box [Bug: #46]
	when_call(function () {
		return jQuery(".leaflet-container #8-input").length;
	}
	, function () {
		console.log("when_call");

		jQuery(".leaflet-container #8-input").attr({
			//type: "search",
			placeholder: "Enter title keywords...",
			title: "Filter by keywords in the title of evidence",
			"aria-label": "Search"
		});
	});

	//console.log("End script.js");
});

