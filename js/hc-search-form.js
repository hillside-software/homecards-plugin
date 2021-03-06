﻿/*!
File Info: Copyright 2010-2012 Hillside Technology, Inc. And Hillside Software!!!!
Script Version: 1.80.3
*************/
var currentListingId;
//var hc_infowindow = [];
var hc_markers = [];
var isInitialLoading = true;
var hashValues = null;
window.hcMap = null;
window.currentPolygon = null;
window.currentCircle = null;

window.scrollTimeout = false;


if ( typeof window.ListingData == 'undefined' ) {
	window.ListingData = { viewed: [], hidden: [], viewedDetails: [] };
}

/* navigationMode Helps Make sure a Back button triggers the .ready() 
See: http://stackoverflow.com/questions/158319/cross-browser-onload-event-and-the-back-button/170478#170478*/
history.navigationMode = 'compatible';

// CASE-INSENSITIVE!!! Returns value by key name from hashValues array
function GetMyHash(findKey, defaultVal) {
	findKey = findKey.toLowerCase();
	for ( var keyName in hashValues ) {
		if ( findKey == keyName.toLowerCase() ) {
			return hashValues[keyName];
		}
	}
	if (typeof defaultVal == 'undefined' || defaultVal == null ) { defaultVal = ""; }
	return defaultVal;
}

jQuery(document).ready(function($) {
	$('#updateMLS').bind('click', function() { 
		hc_mls_selection({mls: jQuery('#hc_mls').val(), callback: function() { self.location.reload(); } }); 
	}); 

	// OBSOLETE/TEST: var pageLoadCount_Tracker = $('#hc_pageLoadCount').val();
	// make sure criteria is blank on load
	window.criteria = {};
	if ( $('form.hc-ajax').length > 0 ) { 
		// we found a form with a search page - (FYI: listings may or may not already be on the page)
		hc_fixLongCheckboxLists(); 
		// incremnet the hc_pageLoadCount
		$('#hc_pageLoadCount').val(parseInt($('#hc_pageLoadCount').val()) + 1);
		if ( typeof console != 'undefined' && typeof console.log != 'undefined') { console.log("*** pageLoadCount: " + $('#hc_pageLoadCount').val()); }
		var autoRunSearch = false;
		
		$(window).bind('scroll', function(e) {
			if ( scrollTimeout != false ) { clearTimeout(scrollTimeout); scrollTimeout = false; }
			scrollTimeout = setTimeout(function() {
				hc_update_page_state(); 
			}, 1000);
		});
			
		if (location.hash.length >= 3 ) {
			window.hashValues = makeNameValueObject(location.hash.substr(1));
			loadSearchState(location.hash.substr(1));
			if ( location.hash.indexOf('hc_search') > -1 ) {
				// Tell the app to run the search automatically ( but only after the polygon is loaded below )
				autoRunSearch = true;
			}
			// Make sure the polygoncsv input val() is set right
			if ( (isInitialLoading == true || $('.polygoncsv').val().length < 10) && (typeof GetMyHash('polygoncsv') != 'undefined' && GetMyHash('polygoncsv').length >= 10) ) {
				$('.polygoncsv').val(GetMyHash('polygoncsv').urlDecode());
				window.polygonCsvData = GetMyHash('polygoncsv').urlDecode();
			}
			
		} else if (typeof lastSearchJson != 'undefined' ) {
			window.hashValues = null;
			loadSearchState(lastSearchJson);
		}
		/* Check for a set of coords to load onto the map */
		if ($('.polygoncsv').length >= 1 && $('.polygoncsv').val().length > 5) {
			if ( typeof window.polygonCsvData == 'undefined' || window.polygonCsvData == null || window.polygonCsvData.length < 3 ) {
				window.polygonCsvData = $('.polygoncsv').val();
			}
			/*
			The following is an example of a dependency-check loader using a Timeout! ... (obviously google.maps is the dependency)
			*/
			window.polyTimout = null;
			var loadPolyFunc = function() {
				if (typeof google != "undefined" && typeof google.maps != "undefined") {
					clearTimeout(window.polyTimout);
					window.polyTimout = null;
					
					//hc_loadPolygon(decodeURIComponent(jQuery('.polygoncsv').val()));
				} else {
					// Here is where we recursively re-create the timeout for loadPolyFunc
					//console.log("****** RE-Creating the timeout for loadPolyFunc ******");
					window.polyTimout = setTimeout(loadPolyFunc, 500);
				}
			};
			// Here is where we FIRST create the timeout for loadPolyFunc
			window.polyTimout = setTimeout(loadPolyFunc, 250);
		}
		
		if ( autoRunSearch == true ) {
			isInitialLoading = true;
			setTimeout(function() {
				// set isInitialLoading in an overload !!!
				hc_submitAjaxSearch(null, 'search', function() {
					// here's where we need to set the isInitialLoading Flag to FALSE
					setTimeout(function() {
						window.isInitialLoading = false;
					}, 150);
				});
			}, 350);
		}
	
		$('form.hc-ajax').bind('submit', function(e) {
			if (jQuery('.hc-map-box').length <= 0) {
				/// This will force an old fashioned SUBMIT (this helps Search engines get to the site data)
				return true;
			}
			
			hc_submitAjaxSearch(this, 'search');
	
			return false;
		});

	}

	
	$(document).on('click', '.hc-prev, .hc-next', function(e) {
		var $btn = $(this);
		// Go Next!
		if ( $btn.is('.hc-next') ) {
			jQuery('#hc_pageNum')
				.val(parseInt(jQuery('#hc_pageNum').val()) + 1)
				.parent('form').submit();
		} else if ( $btn.is('.hc-prev') ) {
			jQuery('#hc_pageNum')
				.val(parseInt(jQuery('#hc_pageNum').val()) - 1)
				.parent('form').submit();
		}
		setDrawingMode('none');
	});
	
	$(document).on('click', '.hc-set-pagenum-btn', function(e) {
		var $btn = $(this);
		if ( $btn.is('.hc-prev, .hc-next') ) { return true; }
		var newPage = (typeof $btn.data('targetpagenum') != 'undefined' ? parseInt($btn.data('targetpagenum')) : -1);
		if (typeof $btn.attr('targetpagenum') != 'undefined') { newPage =  $btn.attr('targetpagenum'); }
		if ( newPage != 0 ) {
			$('#hc_pageNum').val(newPage);
			return true;
		} else {
			alert('Error: Could not load next page! Please try again soon.');
			return false;
		}
	});
	
	/* The following snippet fixes broken form labels to make them link to their neighbor INPUT */
	$('ul.hc-fields li span').each(function(i, obj) {
	  var myRow = jQuery(obj);
	  jQuery('input', myRow).attr('id', jQuery('label', myRow).attr('for'));
	});

	// OLD: See if we have an .hc-map-box AND .hc-listing - meaning we have listings to render into map!!!!
	// NEW: ALWAYS RENDER MAP IF WE CAN! Note: THIS ALWAYS ALLOWS DRAWING2SEARCH
	if ( $('.hc-map-box').length > 0 /* && $('.hc-listing').length > 0 */ ) {
		loadGoogleMaps();
	}

	
	if ( GetMyHash('polygoncsv').length > 5 ) {
		hc_loadPolygon(GetMyHash('polygoncsv'));
	}
});


/*
	makeNameValueObjectArray Converts a QueryString into:
	[{name: 'wID', value: '10001'}, {name: 'action', value: 'search'}, {name: 'example', value: 'search'}]
*/
function makeNameValueObjectArray(query) {
	var q = query.split("&");
	return jQuery.map(jQuery(q), function(nameValue, i) {
		var eqPos = nameValue.indexOf("=");
		var pair = [nameValue.left(eqPos), nameValue.right(nameValue.length - eqPos - 1)];
		if ( pair.length == 2) {
			return {name: pair[0], value: pair[1].toString().urlDecode()};
		} else {
			return {name: pair[0], value: ""};
		}
	});
}
/*
	makeNameValueArray Converts a QueryString into:
	[{wID: 10001}, {action: 'search'}, {example: 'search'}]
*/
function makeNameValueArray(query) {
	var q = query.split("&");
	return jQuery.map(jQuery(q), function(nameValue, i) {
		var eqPos = nameValue.indexOf("=");
		var pair = [nameValue.left(eqPos), nameValue.right(nameValue.length - eqPos - 1)];
		var obj = {};
		if ( pair.length == 2) {
			obj[pair[0]] = pair[1].toString().urlDecode();
		} else {
			obj[pair[0]] = '';
		}
		return obj;
	});
}

/*
	makeNameValueArray Converts a QueryString into:
	{wID: 10001, action: 'search', example: 'search'}
*/
function makeNameValueObject(query) {
	var q = query.split("&");
	var obj = {};
	jQuery(q).each(function(i, nameValue) {
		var eqPos = nameValue.indexOf("=");
		var pair = [nameValue.left(eqPos), nameValue.right(nameValue.length - eqPos - 1)];
		if ( pair.length == 2) {
			obj[pair[0]] = pair[1].toString().urlDecode();
		} else {
			obj[pair[0]] = '';
		}
	});
	return obj;
}


// Don't call this directly ... use hc_update_page_state BELOW
function hc_getSerializedState(fieldsQueryString) {
	// fieldsQueryString has the search parameters
	if (window.hcMap == null || fieldsQueryString == null || !fieldsQueryString || fieldsQueryString.length <= 6) { return ""; }
	var cent = window.hcMap.getCenter().toString().replace(/\s/g, "").trim(['(', ')']);
	return fieldsQueryString + "&Center=" + cent.urlEncode() + "&Zoom=" + window.hcMap.getZoom() + '&ScrollTop=' + jQuery(window).scrollTop();
}
/*

This is the main function to set the current page view state
*/
function hc_update_page_state(targetForm) {	
	if ( window.isInitialLoading ) { return ""; }
	if ( typeof targetForm == 'undefined' || targetForm == null || targetForm.length < 1 ) { targetForm = jQuery("form.hc-ajax"); }
	if ( targetForm.length < 1 ) { return false; }
	/// Only HASH-ify fields we need to track... 
	var newState = hc_getSerializedState(targetForm.serialize());
	if ( newState != false && newState != 'false' ) {
		self.location.hash = newState;
	}
}
/*
NOTE: The request for "MapJSON" below will return a lot of records - but it will just be a subset of fields
*/
/*
searchMode Options: Search (Default), MapJSON, Count and Stats
*/
function hc_submitAjaxSearch($frm, searchMode, callbackOverride) {
	hc_mapRemoveAllMarkers();
	var tmpCoords;
	var $myForm = jQuery($frm);
	if ( $myForm.length < 1 ) { $myForm = jQuery("form.hc-ajax");}
	var myCriteria = $myForm.serializeArray();
	window.fixedQS = {};
	var polygonCsvData;
	var currentAction = 'hc_get_search_results';
	var currentDataType = 'text';
	var returnCount = -1;
	//if ( typeof searchMode != 'undefined' && searchMode.toLowerCase().indexOf(",Search,MapJSON,Count,Stats,".toLowerCase()) < 0 ) {
		// invalid searchMode given, fall back
	//	searchMode = 'search';
	//} else {
		if ( typeof searchMode != 'string' ) { searchMode = 'search'; }
		searchMode = searchMode.toLowerCase();
	//}
	
	// Set the (location.hash) #LocationHash 

	hc_update_page_state();
	
	if ( searchMode == 'mapjson' ) { 
		currentAction = 'hc_get_map_search_json';
		currentDataType = "json";
		jQuery('.hc-search-counter-preview').html(" [ one moment... ] ").addClass("hc-loading");
	} else if ( searchMode == 'count' ) { 
		currentAction = 'hc_get_search_count';
		currentDataType = "json";
		jQuery('.hc-search-counter-preview').html(" [ one moment... ] ").addClass("hc-loading");
	} else if ( searchMode == 'stats' ) { 
		currentAction = 'hc_get_search_stats';
		currentDataType = "html";
		jQuery('.hc-search-counter-preview').html(" [ one moment... ] ").addClass("hc-loading");
	}
	// If we have a 'query' specified, we need to parse it out and merge it into the myCriteria
	if ( jQuery("input[name='query']").length == 1 && jQuery("input[name='query']").val().length > 1 ) {
		//console.log(jQuery("input[name='query']").val());
		myCriteria = jQuery.extend([{name:'hc_search', value: 'true'}], makeNameValueObjectArray(jQuery("input[name='query']").val()), myCriteria);
		// myCriteria is now an object with a bunch of name=value Objects... NOT AN ARRAY, let's enumerate and build an ARRAY of name=value objects again!
		var tempCriteria = [];
		for (var nvPair in myCriteria ) {
			tempCriteria.push(myCriteria[nvPair]);
		}
		myCriteria = tempCriteria;
		/*console.log(myCriteria);
		console.log("myCriteria.length: " + myCriteria.length);*/
	}
	// Turn any duplicate object names into a new ASP.NET compatible QS value
	var n = '';
	for (var i = 0; i < myCriteria.length; i++) {
		n = myCriteria[i].name.toLowerCase();
		if ( n == 'action' || n == 'polygoncsv' ) { 
			if ( typeof fixedQS[n] == 'undefined' || fixedQS[n].length < myCriteria[i].value.length ) fixedQS[n] = myCriteria[i].value;
		} else {
			if ( typeof fixedQS[n] == 'undefined' ) {
				fixedQS[n] = myCriteria[i].value;
			} else {
				// Append to the CSV value because we have multiple values for this field name
				if (typeof myCriteria[i].value != 'undefined' && myCriteria[i].value != null && myCriteria[i].value.length > 0 ) fixedQS[n] += "," + myCriteria[i].value;//turn duplicates into csv-like field
			}
		}
		fixedQS[n] = fixedQS[n].trim([' ', ',']);
	}
	
	fixedQS['action'] = currentAction;

	fixedQS['pageNum'] = $('#hc_pageNum').val();

	if ( window.currentPolygon != 'undefined' || window.currentPolygon != null && typeof window.currentCircle != 'undefined' || window.currentCircle != null) {
		polygonCsvData = getPolygonCsv();
	} else {
		polygonCsvData = '';
	}
	fixedQS['polygoncsv'] = polygonCsvData;
	jQuery('.polygoncsv').val(polygonCsvData);

	if ( searchMode == 'query' || searchMode == 'querystring' ) {
		return fixedQS;
	}
	
	
	hc_showMapLoading(10000);
	hc_google_trackevent('SearchResults', '');
	var req = jQuery.ajax({url: hc_ajaxUrl, type: 'post', dataType: currentDataType, data: fixedQS, 
		success: function(data) {
			//Updates ONLY MAP: loadMapProperties(jQuery(data));
			/// Alternate ONE-line: loadMapProperties(jQuery('.hc-results-list').html(data).find('.hc-listing'));
			if ( searchMode == 'count' ) {
				if (data.length < 10 ) { 
					returnCount = data; 
				} else {
					returnCount = NaN;
				}
				if ( returnCount != NaN && returnCount >= 1 ) {
					window.hc_estimatedSearchCount = data.count;
					$frm.trigger('searchcount', [data.count, data]);
					jQuery('.hc-search-counter-preview').html('Found ' + data.count + ' Matching Properties...');
					return true;
				}
			}
			jQuery('.hc-results-list').html(data);
			loadMapProperties(jQuery('.hc-results-list .hc-listing'));

			/* This is where we will probably be handling something like function(){isInitialLoading = false} */
			if ( typeof callbackOverride == 'function' ) { 
				callbackOverride(data);
			}

			var $firstListing = jQuery('.hc-results-list .hc-listing:first');
			window.hc_mapListingCount = $firstListing.data('totalcount');
			hc_hideMapLoading();

		}}
	);
}


// Note: hc_updateMapStatusBar sets these global vars: hc_recsPerPage, hc_mapListingCount, hc_currentPageNum, hc_totalNumberOfPages
function hc_updateMapStatusBar(defaultNoListingsMsg) {
	var $firstListing = jQuery('.hc-results-list .hc-listing:first');
	if ( $firstListing.length < 1 )  {
		window.hc_mapListingCount = 0;
		if ( ! defaultNoListingsMsg ) { defaultNoListingsMsg = "<div class='hc-warning-box'> No listings found </div>";}
		//Show Msg 
		jQuery('.hc-map-status').html(defaultNoListingsMsg);
		/*NEW, DON'T Hide the map box~~~~~~ This is old behaviour */
		//jQuery('.hc-map-box').slideUp();
		if (window.hc_currentPageNum >= 2) { window.hc_currentPageNum = 1; jQuery('#hc_pageNum').val("1").parent("form").submit(); } 
		return true;
	}
	window.hc_recsPerPage = 25;
	window.hc_mapListingCount = parseInt($firstListing.data('totalcount'));
	window.hc_currentPageNum = parseInt(jQuery('#hc_pageNum').val());
	window.hc_totalNumberOfPages = parseFloat((hc_mapListingCount / hc_recsPerPage));
	if ( window.hc_totalNumberOfPages != parseInt(window.hc_totalNumberOfPages) ) { window.hc_totalNumberOfPages = 1 + window.hc_totalNumberOfPages; }
	if ( window.hc_totalNumberOfPages < 1 ) { window.hc_totalNumberOfPages = 1; }
	hc_setPagingButtons();
	
	if ( window.hc_mapListingCount > 0 ) {
		jQuery('.hc-map-status').html("<span class='hc-left'>Page #: " + hc_currentPageNum + "/" + parseInt(hc_totalNumberOfPages) + "</span><span class='hc-right'>We Found " + hc_mapListingCount + " Listing(s)</span>");
	} else if ( window.hc_mapListingCount == 0 ) { // Nothing found
		jQuery('.hc-map-status').html("<div class='hc-warning-box'> No listings found </div>");
		/* (Don't) Hide the map box */
		//jQuery('.hc-map-box').slideUp();
	} else {
		jQuery('.hc-map-status').html("<strong>TEST: " + hc_mapListingCount + " CurrPage=" + hc_currentPageNum + " - TotalPages=" + hc_totalNumberOfPages + " </strong>");
	}
	// Check if we are on the last page
	if ( parseInt(hc_totalNumberOfPages) == 1 ) {
		jQuery('.hc-results-paging').hide(); //Hide paging Controls if we only have 1 page of results
	} else {
		jQuery('.hc-results-paging').slideDown();
	}
	if ( hc_currentPageNum == parseInt(hc_totalNumberOfPages) ) {
		//jQuery('.hc-results-paging').slideUp(); //Hide paging Controls if we only have 1 page of results
	}
}


	function loadSearchState(lastSearch) {
		hc_updateMapStatusBar();
		/*
		Normalize the lastSearch parameter
		*/
		if ( typeof lastSearch != 'string' ) {
			lastSearch = jQuery.param(lastSearch);
		}
		var searchArray = makeNameValueObjectArray(lastSearch);//.split("&");
		
		for (objPair in searchArray) {
			//console.log(name + " " + searchArray[name].name);
			var myPair = searchArray[objPair];
			var name = myPair.name;
			var myVal = myPair.value;
			if ( typeof(myVal) != 'undefined' && myVal != null ) {
				var namedInputs = document.getElementsByName(name);
				if (typeof namedInputs != 'undefined' && namedInputs != null && namedInputs.length > 0) {
					namedInputs[0].value = myVal ? myVal.replace(/\+/g, " ") : "";
				} else {
					// Try to get the item without being case sensitive
					jQuery('#hc_' + name + ', .hc_' + name.toLowerCase() + ', #hc_' + name.toLowerCase()).val(myVal ? myVal.replace(/\+/g, " ") : "");
				}
			}
		}
	}

if (typeof window.isArray == 'undefined') {
	window.isArray = function(obj) { return obj.constructor == Array; };
}


function loadGoogleMaps() {
	if (typeof google == "undefined" || typeof google.maps == "undefined") {
		// Asynchronously load google maps
		var element = document.createElement('script');element.src = 'http://maps.google.com/maps/api/js?v=3.7&libraries=drawing&key=AIzaSyALmXnBV-SN9wr65_DDh9Ivyq8iD55UV4s&sensor=false&callback=loadMapProperties';element.type = 'text/javascript';
		var scripts = document.getElementsByTagName('script')[0];
		scripts.parentNode.insertBefore(element, scripts);
	} else {
	  loadMapProperties(jQuery('.hc-listing[data-listingid]'));
	}
}

// TODO: Check for Removal
function getMapState() {
	if ( typeof window.hcMap != 'undefined' &&  window.hcMap != null ) {
		return window.hcMap.getCenter().toString().replace(/\s/g, "") + "|" + window.hcMap.getZoom();
	} else {
		return '';
	}
}
/****
IMPORTANT EXAMPLE FUNCTION 
TO CUSTOMIZE THE MAP ICON'S POPUP BOX HTML.... ^^^ YOU MUST CREATE A CUSTOM FUNCTION NAMED 'hc_getPropertyHtml'

THE EXAMPLE BELOW WILL ATTACH A FUNCTION TO THE 'GLOBAL' window SCOPE:
****/
if (typeof hc_getPropertyHtml == 'undefined' || hc_getPropertyHtml == null ) {
	window.hc_getPropertyHtml = function(listing) {
		var convertSecondsToDaysBack = function(timeStampInSeconds) {
			var today = new Date();
			var stamp = today.getTime();// Returns unix epoch
			if (("" + timeStampInSeconds).length >= 13) {timeStampInSeconds = 0.001 * timeStampInSeconds;}
			return parseInt(((stamp * 0.001) / 86400) - (timeStampInSeconds / 86400)) + " days ago";
		}
		/* TODO: Add inline logic to show save status save  			*/
		return "<div class='hc-map-infowindow'>\r\n" +
					 "	<h3><a href=\"javascript: hc_scrollToListingId('" + listing.data('listingid') + "')\">" + listing.data('addr') + "</a> &#160; &#160; " + jQuery('.hc_price', listing).text() + "</h3>\r\n" +
					 "	<table cellspacing='0' cellpadding='0' border='0'>\r\n" +
					 "		<tr>\r\n" +
					 "			<td valign='top' class='hc-left-col'>\r\n" +
					 "				<div class='hc-map-pic'><a href='" + listing.data('href') + "' /><img src='" + jQuery('img.hc_img', listing).attr('src') + "' /></a></div>\r\n" +
					 "			</td>\r\n" +
					 "			<td valign='top' class='hc-right-col'>\r\n" +
					 "				<div><b>Beds:</b> " + jQuery('.hc_beds', listing).text() + " &#160; <b>Baths:</b> " + jQuery('.hc_baths', listing).text() + "</div>\r\n" +
					 "				<div><b>Subarea:</b> " + listing.data('subarea') + "</div>\r\n" +
					 "				<div><b>Est. Age:</b> " + convertSecondsToDaysBack(listing.data('listdatesecs')) + "</div>\r\n" +
					 "				<div><b>MLS#:</b> " + listing.data('listingid') + "</div>\r\n" +
					 "				<div><b>Presented by:</b><br />" + jQuery('.hc_bkr', listing).text() + "</div>\r\n" +
					 "				<div><img src=\"http://www.myhomecards.com/images/idx.gif\" alt=\"idx logo\" /></div>\r\n" +
					 "			</td>\r\n" +
					 "		</tr>\r\n" +
					 "	</table>\r\n" +
					 "</div>";
					 
	}
}

function hc_scrollToListingId(listingId) {
	var $listing = jQuery("[data-listingid='" + listingId + "']")
		.addClass('hc-selected-listing');
	
	hc_log('ListingInfo', '', listingId);
	
	if ( $listing.length >= 1 ) {
		jQuery(document).scrollTop($listing.offset().top * 0.95);
		setTimeout("jQuery('.hc-selected-listing').removeClass('hc-selected-listing')", 5500);
	}
}
function hc_mapRemoveAllMarkers() {
	// Kill info windows !!! - may need to use special handlers (like .setMap(null) for markers below )
	//hc_infowindow = [];
	// Remove all markers from map
	if ( hc_markers.length >= 1 ) {
		for (var i = 0; i < hc_markers.length; i++) {
			if ( typeof hc_markers[i] != 'undefined' ) {hc_markers[i].setMap(null);}
		}
	}
	hc_markers = [];
}

if (typeof window.hc_setPagingButtons == 'undefined' || window.hc_setPagingButtons == null ) {
	window.hc_setPagingButtons = function() {
		var nextBtn = "<input type='button' name='setPageNum' class='hc-next hc-set-pagenum-btn' targetpagenum='" + (window.hc_currentPageNum + 1) + "' value='Next &raquo;' />";
		var prevBtn = "<input type='button' name='setPageNum' class='hc-prev hc-set-pagenum-btn' targetpagenum='" + (window.hc_currentPageNum - 1) + "' value='&laquo; Prev' />";
		//var html = '';
		
		jQuery('.hc-results-paging').html(prevBtn + nextBtn);
		// note: use these vars: hc_recsPerPage, hc_mapListingCount, hc_currentPageNum, hc_totalNumberOfPages
		if ( hc_currentPageNum == parseInt(hc_totalNumberOfPages) && hc_totalNumberOfPages >= 2 ) {
			jQuery('.hc-next').attr('disabled', true);
			jQuery('.hc-prev').attr('disabled', false);
		} else if ( hc_currentPageNum == 1 && hc_totalNumberOfPages >= 2 ) { // Nothing found
			jQuery('.hc-prev').attr('disabled', true);
			jQuery('.hc-next').attr('disabled', false);
		} else if ( hc_currentPageNum >= 2 && hc_totalNumberOfPages >= 2 ) {
			jQuery('.hc-next').attr('disabled', false);
			jQuery('.hc-prev').attr('disabled', false);
		}
	}
}

function getPolygonCsv() {
  var polyCsv = [];

  if (window.currentCircle != null ) {
	  return window.currentCircle.getCenter().toString().replace(/[\(\)\|\s]/g, '') + "|^" + window.currentCircle.getRadius();
	}
	if ( window.currentPolygon != null) {
		window.currentPolygon.getPath().forEach(function(a) {
			a = a.toString().replace(/[\(\)\|\s]/g, '');
			tmpCoords = a.split(',');
			// do basic check to make sure lat & long have the same floating point precision
			tmpCoords[0] = (tmpCoords[0].length > 7 ? tmpCoords[0].substring(0, 7).trim('0') : tmpCoords[0]);
			tmpCoords[1] = (tmpCoords[1].length > 9 ? tmpCoords[1].substring(0, 9).trim('0') : tmpCoords[1]);
			a = tmpCoords.join(',');
			polyCsv.push(a); 
		});
	}
	return polyCsv.join("|");
}
function hc_loadPolygon(polygonData) {
	var isMapEditAllowed = true;
	if ( jQuery('form.hc-ajax').is('.hc-readonly') ) {
		isMapEditAllowed = false;
	}

  clearAnyCurrentShapes();

	window.polygonCsvData = polygonData;
	var myPolygonPoints = [];
	var myPoints = polygonData.split("|");
	/* Check for a circle .. Indicated by a ^ OR > char */
	if (myPoints.length == 2 && (myPoints[1][0] == '>' || myPoints[1][0] == '^') ) {
		var t = myPoints[0].split(',');
		var centerLatLng = new google.maps.LatLng(parseFloat(t[0]), parseFloat(t[1]));
	  window.currentCircle = new google.maps.Circle({ editable: isMapEditAllowed,
	    radius: parseFloat(myPoints[1].substring(1)), center: centerLatLng,
	  	fillColor: "#336699", fillOpacity: 0.3, strokeColor: "#336699", strokeOpacity: 0.8, strokeWeight: 3,
			map: window.hcMap });
		setDrawingMode('none');
	} else if ( myPoints.length > 2 ) {
  	/* We have a Polygon!! Not a Circle! */
 		if (myPoints[0] != myPoints[myPoints.length - 1]) { myPoints.push( myPoints[0] );} // Add missing closing point
 		for (var i = 0; i < myPoints.length; i++) {
 			tmpLatLon = myPoints[i].split(',');
 			myPolygonPoints.push(new google.maps.LatLng(parseFloat(tmpLatLon[0]), parseFloat(tmpLatLon[1])));
 		}
 		if ( myPolygonPoints.length > 40 ) { 
 			// CRITICAL/IMPORTANT: Behaviour note: do not allow editing the shape if it has a ton of points!!
 			isMapEditAllowed = false;
 		}
	  window.currentPolygon = new google.maps.Polygon({ editable: isMapEditAllowed,
	    paths: myPolygonPoints,
	  	fillColor: "#336699", fillOpacity: 0.3, strokeColor: "#336699", strokeOpacity: 0.8, strokeWeight: 3,
			map: window.hcMap });
		setDrawingMode('none');
	}
}
function checkForChangedShape() {
	if ( isInitialLoading ) { return false; }
  var polyCsv = null;
  if (window.currentPolygon != null ) {
  	polyCsv = getPolygonCsv();
	}
  if (window.currentCircle != null ) {
	  polyCsv = getPolygonCsv();
	}
  if ( polyCsv != null && window.polygonCsvData != polyCsv ) {
		jQuery(document).trigger('shapechanged', {'newCsv': polyCsv, 'oldCsv': window.polygonCsvData});
		jQuery('.polygoncsv').val(polyCsv);
		window.polygonCsvData = polyCsv;
		hc_update_page_state();
		hc_runSearch(); /// Perhaps this needs to auto-update a search COUNT feature - add link to easily refresh the search
	}
}


// loadMapProperties() is Automatically called after google maps script is initialized 
function loadMapProperties(hcListingsList) {
	// Define google maps options when the JS is loaded
	window.Pushpins = {active: new google.maps.MarkerImage('/wp-content/plugins/homecards-plugin/images/Pushpin-Green-20x29.png'),
									active_visited: new google.maps.MarkerImage('/wp-content/plugins/homecards-plugin/images/Pushpin-Yellow-20x29.png')};
	//Pushpins.active_visited Pushpins.active

	var polygonCsvDataToLoad = ( jQuery('.polygoncsv').length >= 1 ? jQuery('.polygoncsv').val() : "" );
	
	var isMapEditAllowed = true;
	if ( jQuery('form.hc-ajax').is('.hc-readonly') ) {
		isMapEditAllowed = false;
	}
	
	if (typeof window.hcMap == 'undefined' || window.hcMap == null) {
		var myZoom = 9;
		var myCenter = new google.maps.LatLng(39.755, -104.956);
		if ( window.hashValues != null ) {
			// Check for a polygoncsv in hashValues
			if ( isInitialLoading == true && typeof GetMyHash('polygoncsv') != 'undefined' && GetMyHash('polygoncsv').length >= 10 ) {
				polygonCsvDataToLoad = GetMyHash('polygoncsv').urlDecode();
			}
			if ( typeof window.GetMyHash('zoom') != 'undefined' && window.GetMyHash('zoom').length >= 1 ) {
				myZoom = parseInt(window.GetMyHash('zoom', 14));
			}
			if ( typeof window.GetMyHash('center') != 'undefined' && window.GetMyHash('center').length > 5 ) {
				var latAndLonStr = window.GetMyHash('center').urlDecode().split(",");
				myCenter = new google.maps.LatLng(parseFloat(latAndLonStr[0]), parseFloat(latAndLonStr[1]));
			}
		}
  	window.hcMap = new google.maps.Map(jQuery('.hc-map-box')[0], { scrollwheel: false, center: myCenter, zoom: myZoom, mapTypeId: google.maps.MapTypeId.ROADMAP});
  	
  	window.drawingManager = new google.maps.drawing.DrawingManager({
		  drawingMode: null /*google.maps.drawing.OverlayType.POLYGON*/,
		  drawingControl: false,
		  markerOptions: { icon: Pushpins.active },
		  polygonOptions: { clickable: true,
		  	editable: isMapEditAllowed,
		  	geodesic: true, //When true, render each edge as a geodesic (a segment of a "great circle"). A geodesic is the shortest path between two points along the surface of the Earth. When false, render each edge as a straight line on screen. Defaults to false.
 				map: window.hcMap,
		  	fillColor: "#336699", fillOpacity: 0.3, strokeColor: "#336699", strokeOpacity: 0.8, strokeWeight: 3},
		  circleOptions: { clickable: false,
		    editable: isMapEditAllowed,
		  	fillColor: "#336699", fillOpacity: 0.3, strokeColor: "#336699", strokeOpacity: 0.8, strokeWeight: 3}
		});
		
		if ( isInitialLoading == true && polygonCsvDataToLoad.length >= 1 && polygonCsvDataToLoad.length > 10) {
			hc_loadPolygon( polygonCsvDataToLoad );
		}
		if ( isMapEditAllowed == true ) {
			hc_initMapToolbar();
			window.hc_polygonTimer = setInterval('checkForChangedShape()', 2000);
		}
		
		
		google.maps.event.addListener(window.hcMap, 'idle', function(e) {
			if ( isInitialLoading == false ) { hc_update_page_state(); }
		});
		/*google.maps.event.addListener(window.hcMap, 'center_changed', function(e) {
			if ( isInitialLoading == false ) { hc_update_page_state(); }
		});*/
		google.maps.event.addListener(window.hcMap, 'dragend', function(e) {
			if ( isInitialLoading == false ) { hc_update_page_state(); }
		});
		google.maps.event.addListener(drawingManager, 'polygoncomplete', function(polygon) {
			if ( (typeof polygon == 'undefined' || polygon == null) && currentPolygon != null ) { polygon = currentPolygon; }
			if (typeof polygon == 'undefined' || polygon == null) { return false; /* This shouldn't be possible, right? */}
		  clearAnyCurrentShapes();
		  if ( polygon.getPath().length <= 2 ) { return true; }
		  window.currentPolygon = polygon;
			polygonCsvData = getPolygonCsv();
			jQuery('.polygoncsv').val(polygonCsvData);
			window.hc_currentPageNum = 1;
			jQuery('#hc_pageNum').val("1");
			/// RE-RUNNING THE SEARCH AFTER POLYGON SET !!!
			jQuery('form.hc_search_form').submit();
		  setDrawingMode('none');
		});
		google.maps.event.addListener(drawingManager, 'circlecomplete', function(circle) {
			if ( typeof circle == 'undefined' && currentCircle != null ) { circle = currentCircle; }
			if (typeof circle == 'undefined' || circle == null) { return false; /* This shouldn't be possible, right? */}
		  clearAnyCurrentShapes();
		  window.currentCircle = circle;
			polygonCsvData = getPolygonCsv();
			jQuery('.polygoncsv').val(polygonCsvData);
			window.hc_currentPageNum = 1;
			jQuery('#hc_pageNum').val("1");
			/// RE-RUNNING THE SEARCH AFTER POLYGON SET !!!
			jQuery('form.hc_search_form').submit();
		  setDrawingMode('none');
		});
		drawingManager.setMap(window.hcMap);
		/* if no listing data passed & it's our first time initializing the map object */
		jQuery('.hc-map-box').animate({height: '440px'}, function() {google.maps.event.trigger(hcMap, "resize"); hc_updateMapStatusBar("<div class='hc-warning-box' align='center'> Please Draw a Shape on the Map Or Complete the Form below</div>");} );
		
		//return false;
	}
	hc_mapRemoveAllMarkers();
	if ( isMapEditAllowed == false ) {
		setDrawingMode('none');
	}
	

	/* Check if we were sent a valid object hcListingsList - should be something like jQuery('.hc-listing') */
	var LatInfo={sum:0,min:999,max:-999}, LonInfo={sum:0,min:999,max:-999}, props = hcListingsList, mappedCount = 0; // NOTE: might pick up any and all HC-based listing records on the page - including Featured Listing Tiles
	if (typeof props == 'undefined' || props == null) { props = jQuery('.hc-listing[data-listingid]'); } // Default to get all listings on a page.
	/* Check if No listings Found */
	if ( typeof props == 'undefined' || props == null || props.length < 1 ) {
		hc_updateMapStatusBar();
		return false;
	}
	/** ALERT: If no Listings found, we won't continue **/



	// HERE's Where we process listing results !!!! TODO: Refactor into sep function in a CLASS!!!!

	var latLngBounds = new google.maps.LatLngBounds();


	
	// The following is quite fast on many browsers - especially new browsers
	for (var i = 0; i < props.length; i++) {
		var _p   = jQuery(props[i]);

		if (parseInt(_p.data('lat')) != -1 && parseInt(_p.data('lat')) != 0 ) {
			mappedCount += 1;
			var _lat = parseFloat(_p.data('lat')),
					_lon = parseFloat(_p.data('lon'));

		  latLngBounds.extend( new google.maps.LatLng(_lat, _lon) );

// TODO: Remove the following avg calculating code... not needed with the 'fitBounds' support
			// Sum up the current lat/lon values so we can get The Average LAT & Long
			LatInfo.sum += _lat;
			LonInfo.sum += _lon;
			// Figure out the min and max
			if (LatInfo.min > _lat) { LatInfo.min = _lat; };
			if (LonInfo.min > _lon) { LonInfo.min = _lon; };
			if (LatInfo.max < _lat) { LatInfo.max = _lat; };
			if (LonInfo.max < _lon) { LonInfo.max = _lon; };
		}
	}
	LatInfo.avg = (LatInfo.sum / mappedCount);
	LonInfo.avg = (LonInfo.sum / mappedCount);

	// now that we have the avg's calculated, set map options with new center
  /*var mapOptions = { scrollwheel: false,
    center: new google.maps.LatLng(LatInfo.avg, LonInfo.avg),
    zoom: 9, mapTypeId: google.maps.MapTypeId.ROADMAP
  };
  if ( window.hashValues != null && typeof window.GetMyHash('center') != 'undefined' && window.GetMyHash('center').length > 5 ) {
  */
	if (typeof window.hcMap == 'undefined' || window.hcMap == null) {
  	alert("Map not yeat ready...");
  	//window.hcMap = new google.maps.Map(jQuery('.hc-map-box')[0], mapOptions);
  	//window.hcMap.setCenter(new google.maps.LatLng(LatInfo.avg, LonInfo.avg));
  	
  			////////////hcMap.fitBounds( LatLngBounds );

				////////////setDrawingMode('none');
		// Auto-fit applied to map now that it's been created!
		//window.hcMap.fitBounds(new google.maps.LatLngBounds(new google.maps.LatLng(LatInfo.max, LonInfo.min), new google.maps.LatLng(LatInfo.min, LonInfo.max)));
	} else {
		// Map already exists - set new center (may want to provide an option to set a default for this - only needed for polygon map-interactive/map-based searching )
		if ( window.isInitialLoading == true && window.hashValues != null && typeof window.GetMyHash('center') != 'undefined' && window.GetMyHash('center').length > 5) {
			/* Do nothing, or recenter on the GetMyHash('center') */
		} else {
			//console.log("window.isInitialLoading: " + window.isInitialLoading);
			window.hcMap.fitBounds(new google.maps.LatLngBounds(new google.maps.LatLng(LatInfo.max, LonInfo.min), new google.maps.LatLng(LatInfo.min, LonInfo.max)));
		}
	}
	//hcMap.setZoom(hcMap.getZoom() + 1);
	
	window.hc_infowindow = new google.maps.InfoWindow();
	var newMarker = null;
	// Let's add Pushpins: loop through .hc-listings *AGAIN*, now that the map is ready for us
	for (var i = 0; i < props.length; i++) {
		var p = jQuery(props[i]);
		if (parseInt(p.data('lat')) != -1 ) {
			currentListingId = p.data('listingid');
			var myIcon = Pushpins.active;
			if ( ListingData.viewed.indexOf(currentListingId.toString()) > -1 ) { /* Override the icon to the 'viewed' image */
				myIcon = Pushpins.active_visited;
			}
			newMarker = new google.maps.Marker({
	      position: new google.maps.LatLng(parseFloat(p.data('lat')), parseFloat(p.data('lon')) ), 
	      map: window.hcMap,
	      title: p.data('addr') + ', ' + p.data('subarea'),
	      icon: myIcon  });
	    
	    p.data('infoindex', i);
	    //console.log(" *** Adding marker to " + p.data('listingid') + ' i: ' + i);
	    /*
	    IMPORTANT: hc_getPropertyHtml is a custom function to support setting default HTML for the property tile.
	    
	    IMPORTANT: Some fields are required by MLS rules & regs!
	    */
			google.maps.event.addListener(newMarker, 'click', (function(newMarker, i) {
    		return function() {
    			var _p = jQuery(props[i]);
    			//console.log(_p);
					var intInfo = i; //p.data('infoindex');
					if ( ListingData.viewed.indexOf(_p.data('listingid').toString()) < 0 ) { 
						ListingData.viewed.push(_p.data('listingid').toString());
						hc_viewListing(_p.data('listingid'))
						newMarker.setIcon(Pushpins.active_visited);
					}

		    	if (typeof window.hc_getPropertyHtml != 'undefined' ) {
			 			// Note: 2012-03-25 - Disabling the google InfoWindow native code, Now using the smartinfowindow.js code!!!
	 			    //NEW: var infobox = new SmartInfoWindow({position: newMarker.getPosition(), map: window.hcMap, content: window.hc_getPropertyHtml(_p)});
			 			hc_infowindow.setContent(window.hc_getPropertyHtml(_p));
			 			hc_infowindow.open(window.hcMap, newMarker);
			 		} else {
			 			self.location.href = _p.data('href');
			 		}
		    }
		  })(newMarker, i));
			
			window.isInitialLoading = false;

			hc_markers[hc_markers.length] = newMarker;
		}
	}
	hc_updateMapStatusBar();

	if ( hc_currentPageNum == 1 && isMapEditAllowed == true ) {
		// ONLY Automatically enable drawing mode when properties load on the first page (generally on the initial load) ... otherwise every page 'turn' re-set the drawing mode!! 
		//setDrawingMode('polygon');
	}
	
	
}

  function clearAnyCurrentShapes() {
  	if ( window.currentCircle != null ) {
  		window.currentCircle.setMap(null); window.currentCircle = null;
  	}
  	if ( window.currentPolygon != null ) {
  		window.currentPolygon.setMap(null); window.currentPolygon = null;
  	}
  }

function setDrawingMode(mode) {
 	jQuery('.hc-map-btn').css({fontWeight: 400});
	if ( mode.toLowerCase() == 'polygon' && drawingManager.getDrawingMode() != google.maps.drawing.OverlayType.POLYGON ) {
  	drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
  	jQuery('.hc-polygon-btn').css({fontWeight: 700});
	} else if (mode.toLowerCase() == 'circle' && drawingManager.getDrawingMode() != google.maps.drawing.OverlayType.CIRCLE ) {
  	drawingManager.setDrawingMode(google.maps.drawing.OverlayType.CIRCLE);
  	jQuery('.hc-circle-btn').css({fontWeight: 700});
	} else {
		drawingManager.setDrawingMode(null);
		jQuery('.hc-pan-btn').css({fontWeight: 700});
	}
}
/* Added By: Dan L. 2012-04-02 */
function hc_initMapToolbar() {
	hc_addButtonToMapToolbar(hc_getGoogleMapIconHtml('Hand', false) + 'Pan &amp; Zoom', 'hc-map-btn hc-pan-btn', function() {
   	setDrawingMode('none');
	});
	hc_addButtonToMapToolbar(hc_getGoogleMapIconHtml('Polygon', false) + 'Draw Polygon', 'hc-map-btn hc-polygon-btn', function() {
   	setDrawingMode('polygon');
	});
	hc_addButtonToMapToolbar(hc_getGoogleMapIconHtml('Circle', false) + 'Draw Circle', 'hc-map-btn hc-circle-btn', function() {
   	setDrawingMode('circle');
	});
}

/* Added By: Dan L. 2012-04-02 */
function hc_addButtonToMapToolbar(htmlLabel, addClass, clickCallback) {
	var $toolbarBtn = jQuery('<div class="hc-map-toolbar-item" style="background-color: white; border: 1px solid rgb(113, 123, 135); cursor: pointer; text-align: center; border-width: 1px; -webkit-box-shadow: rgba(0, 0, 0, 0.398438) 0px 2px 4px; box-shadow: rgba(0, 0, 0, 0.398438) 0px 2px 4px;line-height: 18px;" title="Click to start drawing"><div style="font-family: Arial, sans-serif; font-size: 12px; padding-left: 4px; padding-right: 4px;" class="' + addClass + ' toolbar-item-html">' + htmlLabel + '</div></div>')[0];
  hcMap.controls[google.maps.ControlPosition.TOP_LEFT].push($toolbarBtn);
  google.maps.event.addDomListener($toolbarBtn, 'click', function() {
    if ( clickCallback != null ) { clickCallback(); }
  });
}
/* Added By: Dan L. 2012-04-02 */
function hc_getGoogleMapIconHtml(iconName, bold) {
	var heightOffset = 0;
	iconName = iconName.toLowerCase();
	if ( iconName == "polygon" ) { heightOffset = (bold ? -96 : -64); }
	if ( iconName == "rectangle" || iconName == "square" ) { heightOffset = (bold ? -48 : -16); }
	if ( iconName == "circle" ) { heightOffset = (bold ? 0 : -160); }
	if ( iconName == "hand" || iconName == "pointer" ) { heightOffset = (bold ? -144 : -80); iconName = "hand"; }
	return '<span style="display: inline-block; "><div style="overflow: hidden; position: relative; width: 16px; height: 16px; "><img style="top: ' + heightOffset + 'px; position: absolute; left: 0px; -webkit-user-select: none; border: 0px; border-image: initial; padding: 0px; margin: 0px; -webkit-user-drag: none; width: 16px; height: 192px;" src="http://maps.gstatic.com/mapfiles/drawing.png"></div></span>';
}


function hc_runSearch() {
	jQuery('form.hc_search_form').submit();
}
function hc_showMapLoading(timeout) {
	var $box = jQuery('.hc-map-box');
	if ( jQuery('.hc-map-loading').length < 1 ) {
		// Add the "map loading" ui dialog html to the page... -------- left: ' + $box.offset().left + 'px; top: ' + $box.offset().top + 'px;
		$box.before('<div class="hc-map-loading" style="display: none; position: absolute; z-index: 9999999; "><div style="width: ' + $box.width() + 'px; height: ' + $box.height() + 'px; background-color: #999999; opacity: 0.8;">' +
			'<span class="ui-loading" style="position: absolute; display: block; margin-left: ' + ($box.width() * 0.5) + 'px; margin-top: ' + ($box.height() * 0.5) + 'px; ">Loading</span>' +
			'</div></div>');
	} else {
		// already added html !!!
	}
	jQuery('.hc-map-loading').fadeIn();
	if ( typeof timeout != 'undefined' || timeout != null || parseInt(timeout) > 0 ) {
		setTimeout('hc_hideMapLoading()', timeout);
	}
}
function hc_hideMapLoading() {
	jQuery('.hc-map-loading').fadeOut();
}

function hc_fixLongCheckboxLists() {
	var ua = navigator.userAgent.toLowerCase();
	if ( ua.indexOf('ipad') > -1 || ua.indexOf('iphone') > -1 || ua.indexOf('android') > -1 ) { return true; }
	jQuery('.hc-searchform li:has(input[type="checkbox"])').each(function(i, obj) { 
	  var $myLi = jQuery(this);
	  if (jQuery('input', obj).length > 7) {
	    $myLi.css({height: '115px', overflowY: 'scroll'});
	  }
	});
	return true;
}


