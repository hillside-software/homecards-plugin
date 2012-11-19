/*!
File Info: Copyright 2010-2012 Hillside Technology, Inc. And Hillside Software!!!!
Script Version: 1.82
*************/
jQuery(document).ready(function($) {
	$(document).on('click', 'div.hc-map-loading', function(e) {
		$('.hc-map-loading').hide();
	});
	
	if ( $(".twitter-share-button").length > 0	&&	typeof window.twttr == 'undefined') {
		var element = document.createElement('script');
		element.src = 'http://platform.twitter.com/widgets.js';
		element.type = 'text/javascript';
		var scripts = document.getElementsByTagName('script')[0];
		scripts.parentNode.insertBefore(element, scripts);
	}

	if (jQuery('.hc-listing').length > 0) {
		jQuery('.hc-listing').each(function (index, obj) {
			if (new Date(parseInt(jQuery(this).data('listdatesecs')) * 1000) >= new Date(new Date() - (1000 * 60 * 60 * 24 * 6))) { /*NEW LISTING FOUND */
				jQuery(this).find('.hc_ribbonContainer').fadeIn();
			}
		});
	}

	jQuery(document).on('click', 'a.hc_mapPopup, .hc_mapPopup a, .hc_mapPopup div', function(event) {
		var href = jQuery(this).attr('href') ? jQuery(this).attr('href') : jQuery(this).parent("a").attr('href');
		if ( href.length > 4 ) {
			jQuery('<a class="colorbox" href="' + href + '">[Show Map]</a>').colorbox({href: href, iframe: true, width: "50%", height: '480px', open: true, fixed: true});
		}
		var $prop = jQuery(this).parent('.hc-listing');
		showGoogleMapsPopup($prop);
		return false;
	});

	jQuery(document).on('click', 'a.hc_addFav, .hc_addFav a, .hc_addFav div', function(event) {
		var $prop = jQuery(this).parent('.hc-listing');
		if ( $prop.length < 1 ) { $prop = jQuery('.hc-listing:first'); }
		//console.log($prop);
		hc_saveProperty($prop);
		return false;
	});
	jQuery(document).on('click', '.hc-login-button', function(e) {
		hc_leadLogin(jQuery('#hc_login_email').val(), jQuery('#hc_login_password').val(), $(this).parent('form'));
		return false;
	});
	
	if ( $('.HC_Prop_Photos').length > 0 ) {
		var $photoBox = $('.HC_Prop_Photos');
		$photoBox.after("<div class='cycleControls' style=\"font-size: 20px; font-weight: 700; color: #666666; width: " + $photoBox.width() + "px;\"><a class='hc_iconic hc_arrow_left'></a><span class='pics-caption'></span><a style='float:right;' class='hc_iconic hc_arrow_right'></a></div>");
		$('.cycleControls .hc_iconic').bind('mouseover', function (e) {
			$(this).animate({fontSize: '33px'}, {duration: 200});
		}).bind('mouseout', function (e) {
			$(this).animate({fontSize: '20px'}, {duration: 200});
		});
		$('.HC_Prop_Photos').cycle({prev: '.hc_arrow_left', next: '.hc_arrow_right'});
	}

	autoInitAutoComplete();

	$(".hc-colorbox-close").live("click", function(e) {
		jQuery.colorbox.remove();
	});
});

function hc_loginRequired(autoShowLogin) {
	if (typeof HCProxy == 'undefined' || typeof HCProxy.leadJSON == 'undefined' || HCProxy.leadJSON == null || typeof HCProxy.leadJSON.Token == 'undefined' || HCProxy.leadJSON.Token.length < 3 ) { 
		if (autoShowLogin) {
			hc_showLoginPopup();
		}
		
		return false;
	} else if (HCProxy.leadJSON.Token.length > 3) {
		return true;
	}
	if (typeof autoShowLogin == 'undefined' || autoShowLogin == null ) { autoShowLogin = true; } 
	if (typeof hc_ajaxUrl == 'undefined') { /* New 2012-03*/ window.hc_ajaxUrl = HCProxy.ajaxurl; }
	if (typeof HCProxy != 'undefined' && typeof HCProxy.leadJSON != 'undefined' && typeof HCProxy.leadJSON.Token == 'undefined' && HCProxy.leadJSON.Token.length < 3 ) {
		if (autoShowLogin) {
			hc_showLoginPopup();
		}
		return false;
	} else {
		// No login needed - most likely (nb: it's possible the token may have expired)
		return false;
	}
}

function hc_showLoginPopup() {
	hc_google_trackevent('OpenLoginSignupPopup', '');
	$.ajax({ type: 'POST', url: HCProxy.ajaxurl + '?action=hc_get_html_tile&name=' + 'LoginAndSignup', data: {},
	  success: function(data) {
			if (data && data.html && data.html.length > 0) {
				if (data.html.indexOf('Error') <= -1) {
					jQuery('<a class="inline colorbox" href="">Signup Now</a>').colorbox({title: 'Free Registration and Login', html: data.html, width:"50%", open: true, height: '430px', initialHeight: 430});
					hc_tabifyPage();
				} else {
					alert('Error: Could not get the login/signup screen. Please try again later.');
				}
			}
		},
	  dataType: 'json'
	});

}
function hc_htmlEncode(str) {
	return str.replace(/&/g, '&amp;').replace(/\"/g, '&quot;').replace(/\'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

/* New QR Code Helper Function */
function getQrCodeImageUrl(url, sizeID, siteToken) {
	hc_google_trackevent('GetQR', 'For ' + url + '');
	if (typeof sizeID == 'undefined' || !sizeID) { sizeID = 6; }
	if (typeof siteToken == 'undefined') {siteToken = "";}
	if (typeof url == 'undefined' || url.length < 5 || url.length > 1000) { return "ERROR: Invalid URL.";}
	if (parseInt(sizeID) > 40) { sizeID = 40; }
	if (parseInt(sizeID) <  2) { sizeID = 2; }
	return "http://www.qrleads4me.com/GetQRCode.aspx?Scale=" + sizeID.toString() + "&URL=" + encodeURIComponent(url) + "&SiteToken=" + encodeURIComponent(siteToken);
}

/// csvList might look like: Denver,Cherry Hills North
function getTokenDataArray(csvList) {
	if (typeof csvList == 'undefined' || csvList.length < 1 ) { return []; }
	var optResults = [],
			values	   = csvList.replace(/,,/g, ',').replace(/,,/g, ',').replace(/^[\s<,]|[,>\s]$/g, '').split(",");
	for (var i = 0; i < values.length; i++) {
		if ((values[i]).toString().replace(/^[\s<,]|[,>\s]$/g, '').toUpperCase() != 'ALL') {
			optResults[optResults.length] = {id: values[i], name: values[i]};
		}
	}
	return optResults;
}

function autoInitAutoComplete() {
	if (typeof jQuery.ui.autocomplete != 'undefined') {
		var updateSelects = jQuery("select.hc-autocomplete, .hc-autocomplete select[multiple], select[data-mode]");
		var optGroupOpts = updateSelects.find('optgroup option').remove();
		if (optGroupOpts.length > 0) {
			updateSelects.prepend(optGroupOpts);
		}
		if (updateSelects.length > 0) {
			for (var i = 0; i < updateSelects.length; i++) {
				var sel = jQuery(updateSelects[i]);
				if ( jQuery('#' + sel.attr('id') + "_auto").length < 1 ) {
					sel.after("<input class='hc-text-auto hc-text-" + sel.attr('name') + " hc_" + sel.attr('name').toLowerCase() + "' type='text' name='" + sel.attr('name') + "' id='" + sel.attr('id') + "_auto' />");
					//Call after these two loops: autoCreateAutoComplete(sel);
					//jQuery('#' + sel.attr('id') + "_auto").tokenInput('http://www.myhomecards.com/AjaxHandler.aspx?Action=GetFieldOptions&FieldName=' + sel.attr('name') + '&MLS=DEN', {crossDomain: true, allowCustomEntry: true, searchDelay: 400, jsonContainer: 'data',  theme: "facebook", preventDuplicates: true } ); /* pre-set default values: prePopulate: optObjectList, */
					sel.hide();
				}
			}
		}
		
		/*** Now add data-mode="ajax" fields ***/
		var updateAjaxFields = jQuery("input[data-mode]");
		for (var i = 0; i < updateAjaxFields.length; i++) {
			var sel = jQuery(updateAjaxFields[i]);
			if ( jQuery('#' + sel.attr('id') + "_auto").length < 1 ) {
				var csvData = [];
				if ( typeof(lastSearchJson) != 'undefined' && typeof lastSearchJson[sel.attr('name')] != 'undefined') {  csvData = getTokenDataArray( lastSearchJson[sel.attr('name')] ) ;  }
				sel.addClass('hc-text-auto');
				/*sel.after("<input class='hc-text-auto hc-text-" + sel.attr('name') + " hc_" + sel.attr('name').toLowerCase() + "' type='text' name='" + sel.attr('name') + "' id='" + sel.attr('id') + "_auto' />");
				//Call after these two loops: autoCreateAutoComplete(sel);
				//jQuery('#' + sel.attr('id') + "_auto").tokenInput('http://www.myhomecards.com/AjaxHandler.aspx?Action=GetFieldOptions&FieldName=' + sel.attr('name') + '&MLS=DEN', {prePopulate: csvData, crossDomain: true, allowCustomEntry: true, searchDelay: 400, jsonContainer: 'data',  theme: "facebook", preventDuplicates: true } ); / * pre-set default values: prePopulate: optObjectList, * /
				sel.hide();*/
			}
		}
		
		autoCreateAutoComplete();
		
	} else {
		alert('Error: Javascript Library jQuery UI Not Found');
	}
}

function autoCreateAutoComplete() {
	var splitCsv = function (val) { return val.split(/,\s*/); }
	var extractLast = function (term) { return splitCsv(term).pop(); }
	if (typeof jQuery.ui.autocomplete != 'undefined') {
		var ajaxFields = jQuery("input.hc-text-auto");
		ajaxFields.bind("keydown", function (event) {
			if (event.keyCode === jQuery.ui.keyCode.TAB && jQuery(this).data("autocomplete").menu.active) {
				event.preventDefault();
			}
		}).bind("blur", function (event) {
			if (jQuery(this).val().length > 0) {
				jQuery(this).val(jQuery(this).val().trim([',', ' ']))
			}
		});

		for (var i = 0; i < ajaxFields.length; i ++) {
			var sel = jQuery(ajaxFields[i]);
			sel
			.autocomplete({
				minLength: 0,
				source: function (request, response) {
					jQuery.ajax({
						url: 'http://www.myhomecards.com/AjaxHandler.aspx?Action=GetFieldOptions&FieldName=' + this.element[0].name + '&MLS=' + (typeof HCProxy.leadJSON.Board == 'undefined' ? "DEN" : HCProxy.leadJSON.Board),
						dataType: "jsonp",
						data: { Query: extractLast(request.term) },
						success: function (data) {
							if (typeof data != 'undefined' && data != null && typeof data.data != 'undefined' && data.data.length >= 1) {
								response(jQuery.map(data.data, function (item) {
									return {
										label: item.name,
										value: item.id
									}
								}));
							}
						}
					});
				},
				search: function (event, ui) {
					/*console.log("^^^ ON: search");
					console.log(event);
					console.log(ui);*/
					// Do nothing: return true;
					// custom minLength
					//var term = (extractLast(this.value) + "").trim([',', ' ']);
					/*if (term.length < 1) {
					return false;
					}*/
				},
				focus: function (event, ui) {
					/*console.log("^^^ ON: focus");
					console.log(event);
					console.log(ui);*/
					// prevent value inserted on focus
					return false; // Changed by dan!
				},
				select: function (event, ui) {
					var searchTerm = (extractLast(this.value) + "").trim([',', ' ']);
					var terms = splitCsv(this.value);
					// check for similar ui.item.value && searchTerm
					if (searchTerm != null && searchTerm.length >= 1 && ui.item.value.toLowerCase().indexOf(searchTerm.toLowerCase()) > -1) {
						var removed = terms.pop();
						//console.log("Removed: " + removed);
					}
					if (terms.length <= 1 || terms.indexOf(ui.item.value) <= -1) {
						// add the selected item
						terms.push(ui.item.value);
						// add placeholder to get the comma-and-space at the end
						//terms.push("");
					}
					this.value = terms.join(", ").trim([',', ' ']);
					//this.value = this.value.replace(searchTerm + ', ', '');
					sel.autocomplete('close');
					return false;
				},
				change: function (event, ui) {
					jQuery(sel).autocomplete('close');
				}
			});
			jQuery('#' + sel.attr('id')).live('focus', function (e) {
				if (jQuery(this).autocomplete && jQuery(this).val() == "" ) { jQuery(this).autocomplete('search', ' '); }
			});
			/*jQuery('#' + sel.attr('id')).live('change', function (e) {
				if (jQuery(this).autocomplete) { jQuery(this).autocomplete('close'); }
			});*/

		}
	} else {
		alert('Error: Javascript Library jQuery UI Not Found');
	}
}

function hc_getAccountJson(opts) {// opts EXAMPLE: {success: function(data) {console.log(data)} }
	return jQuery.post((window.hc_ajaxUrl || HCProxy.ajaxurl), {action: 'hc_get_account_json'}, function(data) {
		window.currentAgent = data;
		if (data) {
			if (opts && opts.success) {opts.success(data);}
		} else {
			if (opts && (opts.failure || opts.error)) {opts.failure ? opts.failure(data) : opts.error(data);}
		}
	}, 'json');
}

function hc_getFeaturedListingsJson(opts) {// opts EXAMPLE: {success: function(data) {console.log(data)} }
	hc_google_trackevent('FeaturedListings', '');
	
	return jQuery.post((window.hc_ajaxUrl || HCProxy.ajaxurl), {action: 'hc_get_featured_listings_json', limit: 10}, function(data) {
		window.featuredListings = data;
		if (data) {
			if (opts && opts.success) {opts.success(data);}
		} else {
			if (opts && (opts.failure || opts.error)) {opts.failure ? opts.failure(data) : opts.error(data);}
		}
	}, 'json');
}

// opts should look like {success: function(data) {/* Handle new data */}}
function hc_getFieldNameJson(opts) {// hc_getFieldNameJson({success: function(data) {console.log(data)} })
	return jQuery.post((window.hc_ajaxUrl || HCProxy.ajaxurl), {action: 'hc_get_fields'}, function(data) {
		window.searchFields = data;
		if (data && opts && opts.success) {opts.success(data);}
	}, 'json');
}
// opts should look like {success: function(data) {/* Handle new data */}}
// hc_getSearchFieldsJsonHtml({success: function(data) {console.log(data)} })
function hc_getSearchFieldsJsonHtml(opts) {
	return jQuery.post((window.hc_ajaxUrl || HCProxy.ajaxurl), {action: 'hc_get_fields_html'}, function(data) {
		window.searchFieldsHtml = data;
		if (data && opts && opts.success) {opts.success(data);}
	}, 'json');
}
function hc_getHtmlTile(tileName, opts) {
	return jQuery.post((window.hc_ajaxUrl || HCProxy.ajaxurl), {action: 'hc_get_html_tile', name: tileName}, function(data) {
		window.searchFieldsHtml = data;
		if (data && opts && opts.success) {opts.success(data);}
	}, 'json');
}


function hc_leadLogout() {
	hc_google_trackevent('Logout', '');

	window.HCProxy = { ajaxurl: (window.hc_ajaxUrl || HCProxy.ajaxurl), leadJSON: {} };
	jQuery.ajax({ type: 'POST', url: (window.hc_ajaxUrl || HCProxy.ajaxurl), data: {action: 'hc_logout'},
	  success: function(data) {
			alert("Logged out successfully!");
		},
	  dataType: 'json'
	});
}

function hc_leadLogin(email, password, frm) {
	if (typeof frm == 'undefined' || frm == null) {frm = jQuery('form.hc_login_form');}
	if (typeof email != 'undefined' && email.length > 5) {
		hc_google_trackevent('Login', email);
		jQuery('#hc_login_ajax').html('<h3 class="ui-loading">Please wait... Logging into Property Database...</h3>');
		jQuery.ajax({ type: 'POST', url: (window.hc_ajaxUrl || HCProxy.ajaxurl), data: {action: 'hc_login', email: email, pass: password},
		  success: function(data) {
				jQuery('#hc_login_ajax').html('<h3 class="ui-loading">... Done!</h3>');
				if (data && data.Token && data.Token.length > 0) {
					jQuery('#hc_login_ajax').html('<h3 class="ui-loading">Welcome Back ' + data.FirstName + '!</h3>');
					HCProxy.leadJSON = data;
					jQuery(frm).slideUp().before("<h2 class='hc_login_msg'>Welcome Back " + data.FirstName + "!</h2>"); 	//jQuery("#hc_login_ajax").text("Welcome Back " + data.FirstName + "!");
					var popupWindow = setTimeout('jQuery.colorbox.remove()', 2200);
				} else if (data && data.Error) {
					msg = 'We ran into a problem: ' + "\n" + data.Error;
					jQuery('#hc_login_ajax').html('<h3 class="ui-loading">' + msg + '</h3>');
					if (jQuery('#hc_login_ajax').length < 1) {alert(msg);}
				} else {
					msg = 'We could not log you in with the specified username and password. Please try again.';
					jQuery('#hc_login_ajax').html('<h3 class="ui-loading ui-warn">' + msg + '</h3>');
					if (jQuery('#hc_login_ajax').length < 1) {alert(msg);}
				}
			},
		  dataType: 'json'
		});
	}
	return false;
}


function hc_leadSignup(frm) {
	window.leadData = null;
	//if (frm) {leadData = jQuery(frm).serializeArray();}
	if ( !leadData ) { leadData = {SignupName: jQuery("#hc_signup_name").val(), SignupPhone: jQuery("#hc_signup_phone").val(), SignupEmail: jQuery("#hc_signup_email").val(), SignupPassword: jQuery("#hc_signup_password").val()};}
	leadData.action = "hc_signup";
	hc_google_trackevent('Signup', '');

	if (leadData.SignupEmail && leadData.SignupEmail.length > 5) {
		jQuery.ajax({ type: 'POST', url: (window.hc_ajaxUrl || HCProxy.ajaxurl), data: leadData,
		  success: function(data) {
				if (data && typeof data.Token != 'undefined') {
					HCProxy.leadJSON = data;
					jQuery.colorbox.remove();
					alert('Successfully Logged In!');
					if (jQuery("#hc_signup_ajax").length > 0) {
						jQuery("#hc_signup_ajax").html(data.Message);
					} else if (data && data.length > 0) {
						jQuery("#hc_signup_ajax").html(data);
					}
				}
		}, dataType: 'json'});
		
	} else {
		jQuery(".hc_signup_ajax").html('<div class="hc-error-msg">Please enter a valid Email.</div>');
		jQuery('input[name="SignupEmail"]', frm).focus();
	}
	return false;
}

function showGoogleMapsPopup(hcListing) {
	window.listingToMap = hcListing;
	if (typeof google == "undefined" || typeof google.maps == "undefined") {
		// Asynchronously load google maps
		var element = document.createElement('script');element.src = 'http://maps.google.com/maps/api/js?v=3.7&libraries=drawing&key=AIzaSyALmXnBV-SN9wr65_DDh9Ivyq8iD55UV4s&sensor=false&callback=hc_showListingOnMap';element.type = 'text/javascript';
		var scripts = document.getElementsByTagName('script')[0];
		scripts.parentNode.insertBefore(element, scripts);
	} else {
		hc_showListingOnMap();
	}
}
/*

*/
function hc_showListingOnMap(hcListing) {
	hc_google_trackevent('ShowOnMap', '');	
	if ( typeof hcListing == 'undefined' || hcListing == null ) {
		// Check for a global var set before this is called (this is needed to support async loading of the google maps JS library)
		if ( typeof window.listingToMap != 'undefined' ) { hcListing = window.listingToMap; }
	}
	if ( typeof hcListing == 'undefined' || hcListing == null ) { alert('No map-able listing was found'); return false; }
	if ( hcListing.is('.hc-listing') == false && hcListing.parent('.hc-listing').length > 0 ) {
		hcListing = hcListing.parent('.hc-listing');
	}
	/* Show the popup colorbox window - note: set initial 'loading...' message HTML in the "html" option passed into .colorbox() below */
	//jQuery('<a class="inline colorbox" href="">Signup Now</a>').colorbox({html: "<div id='#hc_map_popup' style='width:90%; box-sizing: border-box; height: 280px;'>Loading...</div>", width: "50%", height: '280px', open: true});
	return true;
	if (hcListing.length > 0) {
		var addr = hcListing.data('addr');
		var lat = parseFloat(hcListing.data('lat'));
		var lon = parseFloat(hcListing.data('lon'));
		/* TODO: Show the property in the map */
	  var mapOptions = { scrollwheel: false, center: new google.maps.LatLng(lat, lon), zoom: 10, mapTypeId: google.maps.MapTypeId.ROADMAP };
		if (typeof window.hcPopupMap == 'undefined' || window.hcPopupMap == null) {
	  	window.hcPopupMap = new google.maps.Map( document.getElementById('hc_map_popup') , mapOptions);
		}
	}
}
//OLD:function hc_saveProperty(listingID, listingData, listingDivElement) {
function hc_saveProperty(listingDivElement) {
	if ( hc_loginRequired(true) == false ) {return false;}
	if (typeof listingDivElement == 'undefined') { alert("Error: Could not save property. Please try again later.\n #05"); }
	var cid = -1;
	try {
		cid = HCProxy.leadJSON.cID;
	} catch (e) {
		// de nada
	}
	//if (typeof listingID == 'function') {listingDivElement = listingID; listingID = ''}
	if (typeof listingDivElement != 'undefined' && listingDivElement.data) {
		if (typeof listingID == 'undefined' || listingID.length < 1) {listingID = listingDivElement.data('listingid');}
	}
	if (typeof hc_ajaxUrl == 'undefined') { /* New 2012-03*/ window.hc_ajaxUrl = HCProxy.ajaxurl; }

	var postData = jQuery.extend({action: 'hc_lead_save_listing', cID: cid, LeadToken: HCProxy.leadJSON.Token, ListingID: listingID, notes: 'From Wordpress Site: ' + self.location.href, source: 'WP' }, listingDivElement.data());
	if (listingID && listingID.toString().length > 2) {
		hc_google_trackevent('SaveListing', 'Listing ID: ' + listingID);	

		jQuery.ajax({ type: 'POST', url: (HCProxy.ajaxurl) + '?action=' + postData.action, data: postData,
		  success: function(data) {
				if (data && data.Message && data.Message.length > 0) {
					if (data.Message.indexOf('Error') < -1) {
						alert("Saved");
						// show the saved msg
						jQuery('.hc_addFav a.hc_lnk', listingDivElement).html('SAVED');
						jQuery('.hc_addFav_msg').html(data.Message);
					}
				}
			},
		  dataType: 'json'
		});
		return false;
	}
}

function hc_getMySavedProperties() {
	if ( hc_loginRequired(true) == false ) {return false;}
	//hc_get_lead_saved_listings
	var cid = -1;
	try {
		cid = HCProxy.leadJSON.cID;
	} catch (e) {
		// de nada
	}
	var postData = {action: 'hc_get_lead_saved_listings', LeadToken: HCProxy.leadJSON.Token, source: 'WP', cID: cid };
	jQuery.ajax({ type: 'POST', url: (HCProxy.ajaxurl) + '?action=' + postData.action, data: postData,
	  success: function(data) {
			if (data && data.Message && data.Message.length > 0) {
				if (data.Message.indexOf('Error') < -1) {
					// show the saved msg
					jQuery('.hc_addFav a.hc_lnk', listingDivElement).html('SAVED');
					jQuery('.hc_addFav_msg').html(data.Message);
				}
			}
		},
	  dataType: 'json'
	});
	return false;
}

/*
eventType Must be One of these options: Appointments, Bookmark, Charts, FeaturedListings, HomePage, ListingInfo, ListingInfo-Mobile, LoanApp, LoginVerify_Success, MapSearch, MarketData, PropertyAlerts, RequestCMA, Search, SearchResults, ShowPage, Signup
//Form Fields: eventType, subType, listingID, url
*/
function hc_log(eventType, subType, listingID, callback) {
	var postData = {LeadToken: HCProxy.leadJSON.Token, source: 'WP',
		eventType: eventType, subType: subType, listingID: listingID, url: self.location.href };
	if ( typeof _gaq != 'undefined' ) {
		var detailsTag = subType + "";
		if ( listingID != '' ) { detailsTag = "ListingID=" + listingID; }
		_gaq.push(['_trackEvent', 'HomeCards', eventType, detailsTag]);
	}
	jQuery.ajax({ type: 'POST', url: (HCProxy.ajaxurl) + '?action=homecardsevent', data: postData,
	  success: function(data) {
			if (typeof callback == 'function' ) { callback(data); }
		}, dataType: 'json'});
	return false;
}

/**
 * Examples:
 * hc_mls_selection( {mls: 'IRE'} )     	<- sets mls equal to "IRE"
 * hc_mls_selection( {mls: ''} )    		<- sets mls equal to Primary MLS stored in options table
 * hc_mls_selection( )    					<- gets available MLS
*/
function hc_mls_selection(opts) {
	var postData = {action: 'hc_mls_selection', source: 'WP' };
	var mode = "";
	if (typeof(opts.mls) == 'undefined') {
		mode = 'available'; 
		jQuery.extend(postData, {mode: mode}); 
	} else { 
		mode = "set";
		jQuery.extend(postData, {mode: mode, mls: opts.mls}); 
	}
	jQuery.ajax({ type: 'POST', url: ajaxurl, data: postData,
	  success: function(data) {
			console.log('success'); 
			if (typeof opts != 'undefined' && typeof opts.callback == 'function' ) { opts.callback(data); }
		},
	  dataType: 'json'
	});
	return false;
}


function hc_viewListing(listingId, opts) {
	hc_google_trackevent('PreviewedListing', listingId);
	var postData = {action: 'hc_add_viewed_listing', listingId: listingId, LeadToken: (HCProxy.leadJSON != null && typeof HCProxy.leadJSON.Token != 'undefined' ? HCProxy.leadJSON.Token : ""), source: 'WP' };
	jQuery.ajax({ type: 'POST', url: (HCProxy.ajaxurl) + '?action=' + postData.action, data: postData,
	  success: function(data) {
			if (typeof opts != 'undefined' && typeof opts.callback == 'function' ) { opts.callback(data); }
		},
	  dataType: 'json'
	});
	return false;
}

function hc_tabifyPage() {
	jQuery('ul.hc-tabs').each(function(){
		if ( jQuery(this).data('tabify') == true ) { return true; }
		// For each set of tabs, we want to keep track of which tab is active and it's associated content
		var $active, $content, $links = jQuery(this).find('a');
	
		// Use the first link as the initial active tab
		$active = $links.first().addClass('active');
		$content = jQuery($active.attr('href'));
	
		// Hide the remaining content
		$links.not(':first').each(function () {
			jQuery(jQuery(this).attr('href')).slideUp();
		});
	
		jQuery(this).on('click', 'a', function(e){
			// Make the old tab inactive.
			$active.removeClass('active');
			$content.slideUp();
			// Update the variables with the new link and content
			$active = jQuery(this);
			$content = jQuery(jQuery(this).attr('href'));
			// Make the tab active.
			$active.addClass('active');
			$content.slideDown(500, 'swing', function() {jQuery.colorbox.resize();});
			// Prevent the anchor's default click action
			return false;//e.preventDefault();
		});
		jQuery(this).data('tabify', true);
	});
}


/*
hc_track is a google analytics tracking helper so our AJAX activity is not lost !!!!
	GA Method: _trackEvent(category, action, opt_label, opt_value, opt_noninteraction)
	Async ex.: _gaq.push(['_trackEvent', 'HomeCards', 'Search', 'Beds=2-3 Baths=1-3']);
				See: https://developers.google.com/analytics/devguides/collection/gajs/eventTrackerGuide#SettingUpEventTracking

eventType Should be One of these options: Appointments, Bookmark, Charts, FeaturedListings, HomePage, ListingInfo, ListingInfo-Mobile, LoanApp, LoginVerify_Success, MapSearch, MarketData, PropertyAlerts, RequestCMA, Search, SearchResults, ShowPage, Signup
*/
function hc_google_trackevent(eventType, detailsTag) {
	if ( typeof _gaq != 'undefined' ) {
		_gaq.push(['_trackEvent', 'HomeCards', eventType, detailsTag]);
	}
}

