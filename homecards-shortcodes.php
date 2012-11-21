<?php

require_once( dirname(__FILE__) . '/homecards-proxy.php' );

if (!isset($_SESSION)) { session_start(); }

// Dan 2012-04-01 - ---- The following hack is obsolete
if ( isset($_GET['gethtml']) ) {
	if ($_GET['gethtml'] == 'signup') {
		homecards_shortcode(array('page' => 'signup'));
		exit();
	}
}


// R E M O V E    M E ! 
function testFilters(){
	global $wp_filter ;
	if(isset($wp_filter['hc_search_results'])) { 
		echo "GREAT SUCCESS!!"; 
	} else { echo "EPIC FAIL!!"; }
}


// example output: 
// '<label for="mls">Select Desired MLS Provider: </label><select name="mls" id="mls" size="1"><option value="">Denver/Central-Colorado</option><option value="IRE">Boulder</option></select>';

function getMLSSelectHTML() {
	$availableMLS = json_decode(get_option('hc_available_mls',"{}")); 
	$html = "<select name='mls' id='hc_mls' class='hc_mls'>"; 
	
	foreach($availableMLS as $key => $value) {
		if(strlen($key) > 3) { $key = ""; }
		$selected = ($_SESSION['mls'] == $key ? " selected" : ""); 
		$html .= "<option value='".$key."' $selected>".$value."</option>"; 
	}	
	$html .= "</select>";
	$html .= "<input type='button' id='updateMLS' value='Update MLS' />";  
	return $html; 
}

function hc_get_search_results() {
	echo homecards_shortcode(array( 'page' => 'search', 'showmap' => 'false' ));
	exit();
}

// [homecardssearch area="Denver"]
function homecards_search_shortcode( $atts ) {
	/* Handle 'Short' format settings ... like price */
	if ( isset($atts['price']) && strpos($atts['price'], '-') > -1 ) {
		$myPrice = explode('-', $atts['price']);
		$atts['pricefrom'] = $myPrice[0];
		$atts['priceto'] = $myPrice[1];
		
		unset($atts['price']);
	}
	return homecards_shortcode( array( 'page' => 'runsearch', 
		'query' => http_build_query($atts, '', '&')));;
}

// [homecards page="search"]
function homecards_shortcode( $atts ) {
	
	hc_add_url_rewrite();
	if (function_exists('shortcode_atts') ) {
		$atts = shortcode_atts( array(
			'mls' => (isset($_SESSION['mls']) ? $_SESSION['mls'] : ""), 
			'page' => 'search',
			'query' => "", // Set this to override the POST values!
			'col1' => '',
			'col2' => '',
			'col3' => '',
			'width' => '600',
			'height' => '800',
			'city' => '', // For City Page Feature
			'pricerange' => '', // For City Page Feature
			'bedsrange' => '', // For City Page Feature
			'bathsrange' => '', // For City Page Feature
			'subareas' => '', // For City Page Feature
			'limit' => get_option('hc_searchresultslimit', '20'),
			'listingids' => '', // Search Feature - Overrides Regular 'Query' Parameters (from the POST or $atts['query'] value )
			'showmap' => 'true',
			'mapwidth' => '100%',
			'mapheight' => '350px',
			'mapwidth' => 'auto',
			'mapheight' => '350px',
			//'mlsProvider' => '',
			'mapresizerwidth' => '750px',
			'mapresizerheight' => '450px'
		), $atts );
	}

	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) == 'XMLHTTPREQUEST' ) {
		define('IS_AJAX', true); // Might be a bold assumption - might need to test for 'XMLHttpRequest'XMLHttpRequest
	} else {
		define('IS_AJAX', false);
	}
	$hc_html_form = "";
	$cannedSearchClass = '';
	$summaryHtml = "";
	$ajaxResultsHtml = "";
	
	$pagingHtml = "";
	$html = "";

	//$showDisclaimer = true;
	$polygonCsv = "";
	
	$atts['page'] = strtolower($atts['page']);

	if ( get_current_user_id() != 0 ) {
		$leadID = HC_Auth::checkCurrentUserLoginState();
		if ( ! is_wp_error($leadID) ) {
			// Success! we can now use the HC_Auth::getCurrentLead() function
			$hc_lead = HC_Auth::getCurrentLead();
			//var_dump($hc_lead['SavedListings']);// This will be a list of the current user's saved listings MLS#'s
		}
	}

	$hc_proxy = new HomeCardsProxy(true);

	if ( $hc_proxy->debugEnabled ) {
		/*$html .= "<!--\n";
		$html .= print_r($atts, true) . "\n";
		$html .= "\n-->\n";*/
	}
	/* Special code to turn listing request into a canned search query */
	if ( isset($atts['listingids']) && strlen($atts['listingids']) >= 6 ) {
		$atts['page'] = "search";
		$atts['query'] = "listingids=" . esc_attr($atts['listingids']);
	}

  if (!defined('SHOW_DISCLAIMER')) {
  	define( 'SHOW_DISCLAIMER', true );
  }
  if ( empty($_SESSION['viewedListings']) ) { $_SESSION['viewedListings'] = ''; }
  
	if (isset( $_REQUEST['hc_search']) ) {
		$atts['page'] = "search";
		$myArray = $_REQUEST;
	} elseif (isset($_GET['Area']) || isset($_GET['area']) || isset($_GET['Area[]'])) {
		$atts['page'] = "search";
		$myArray = $_GET;
	} elseif (isset($_POST['Area']) || isset($_POST['area']) || isset($_POST['Area[]'])) {
		$atts['page'] = "search";
		$myArray = $_POST;
	} elseif ( isset($atts['query']) && strlen($atts['query']) > 6 ) {
		/* NOTE: This is a CANNED SEARCH */
		$cannedSearchClass = ' hc-readonly hc-canned-search';
		$atts['page'] = "search";
		$atts['query'] = html_entity_decode($atts['query']);
		if ( stripos($atts['query'], "%3B") ) {
			// DONE: Check if URL Decode is needed on the following call... HINT: Look for %3B
			$atts['query'] = urldecode($atts['query']);
			var_dump($_POST);
		}
		$myArray = proper_parse_str(stripslashes($atts['query']));
	} else {
		/* No criteria found for search request ... will still work, just with odd results */
		$myArray = array();
	}
	
	if ( !IS_AJAX ) { 
		if (isset($_SESSION['HC_Login'])) {
			$leadJSON = json_decode($_SESSION['HC_Login'], true);
		} else {
			$leadJSON = array();
		}
		if (isset($_SESSION['mls'])) {		$leadJSON['Board'] = $_SESSION['mls'];	}
	}
	
	
	if ( get_current_user_id() != 0) {
		global $current_user;
		get_currentuserinfo();
		$html .= "<!--\n";
		$html .= "hc_consumerid: " . get_user_meta($current_user->user_id, 'hc_consumerid') . "\n";
		$html .= "\n-->\n";
	}

	/* THIS IS THE MAIN SEARCH SCRIPT - IT IS CRITICAL - IT WIRES UP THE MAPPING & AJAX EXPERIENCE */
	wp_enqueue_script( 'hc-search-form', plugin_dir_url( __FILE__ ) . 'js/hc-search-form.js', array( 'jquery', 'hc-ajax' ) );
	//wp_enqueue_script( 'smartinfowindow', plugin_dir_url( __FILE__ ) . 'js/smartinfowindow.js' );

	if ( isset($myArray['polygoncsv']) && strlen($myArray['polygoncsv']) > 5 ) {
		$polygonCsv = $myArray['polygoncsv'];
	}

	if ( $atts['page'] == "search" || $atts['page'] == "runsearch" || $atts['page'] == "dosearch" || $atts['page'] == "runsearch") {
		//hc_setupAjax();
		wp_enqueue_style( 'components', plugin_dir_url( __FILE__ ) . 'css/components.css' );	
		wp_enqueue_style( 'hc-search-results-theme', plugin_dir_url( __FILE__ ) . 'css/pd_style_blue.css' );	

		if ( isset($_REQUEST['PageNum']) && intval($_REQUEST['PageNum']) >= 1 ) {
			$requestedPageNum = intval($_REQUEST['PageNum']);
		} else {
			$requestedPageNum = 1;
		}
		
		// THE FOLLOWING CODE HAS BEEN MOVED TO THE CLIENT-SIDE
		// THE FOLLOWING CODE HAS BEEN MOVED TO THE CLIENT-SIDE
		// THE FOLLOWING CODE HAS BEEN MOVED TO THE CLIENT-SIDE
		/* TODO: 2012-03 Dan: $pagingHtml should be shot through a filter, et al. 
     * TODO: filter at top and bottom of the_content()? however this could be unreliable if someone has a theme that doesn't display the_content() maybe hook into wp_footer() and jQuery the buttons in place. -DAV
    //if ( strlen($atts['col1'] . $atts['col2'] . $atts['col3']) >= 1 ) {
			// Do basic paging here //
			if ($requestedPageNum > 1) { // Show PREVIOUS btn
				$pagingHtml .= "		<input type='button' name='setPageNum' class='hc-set-pagenum-btn hc-prev' onclick='document.getElementById(\"hc_pageNum\").value = (hc_currentPageNum + 1); jQuery(\"form.hc_search_form\").submit();' value='&laquo; Previous' targetpagenum='" . ($requestedPageNum - 1) . "' />\n";
			}
			if ($requestedPageNum < 100) { // Show NEXT btn - up to 100 * 100=10,000 listings could be paged
				// Should make sure the last call returned some listings...
				$pagingHtml .= "		<input type='button' name='setPageNum' class='hc-set-pagenum-btn hc-next' onclick='document.getElementById(\"hc_pageNum\").value = (hc_currentPageNum > 0 ? (hc_currentPageNum - 1) : 0); jQuery(\"form.hc_search_form\").submit();' value='Next &raquo;' targetpagenum='" . ($requestedPageNum + 1) . "' />\n";
			}
			if ($requestedPageNum >= 1 ) {
				$pagingHtml .= "		<span class=\"hc-pagenum-label\">Current Page #$requestedPageNum</span>\n";
			}
     */
		//}
		
		if ( strlen($atts['col1'] . $atts['col2'] . $atts['col3']) >= 1 ) {
			$showSearchForm = true;
		} else {
			$showSearchForm = false;
		}
		
		unset($myArray['action']);
		
		/* TODO: Check if we have a valid myArray to query */
		if ( isset($myArray['area']) && empty($myArray['Area']) ) { $myArray['Area'] = $myArray['area']; }
		
		/* Assign mlsProvider if different than default */ 
		//$myArray['mlsProvider'] = $_SESSION['mls']; 
		//$_SESSION['mls'] = ""; 
		
		if(isset($myArray['mls']) && strlen($myArray['mls']) >= 3) {
			$_SESSION['mls'] = $myArray['mls']; 
		} else {
			$_SESSION['mls'] = ""; 
		}	
		
		/*$html .= "myArray=".$myArray['mls']."<br />\r\n"; 
		$html .= "mls.post=".$_POST['mls']."<br />\r\n";
		$html .= "mls.get=".$_GET['mls']."<br />\r\n";
		$html .= "mls.session=".$_SESSION['mls']."<br />\r\n"; */
		
	
	
		
	
		//if ( empty($myArray['mlsProvider']) ) { $myArray['mlsProvider'] = ''; }
		if ( empty($myArray['Area']) ) { $myArray['Area'] = ''; }
		if (is_array($myArray['Area'])) {// This is to handle old functionality - Dan: 2012-02-22
			/* If we get an ARRAY for area, JOIN the Area into a CSV string */
			$temp_AreasCsv = $myArray['Area']; unset($myArray['Area']); $myArray['Area'] = implode('|', $temp_AreasCsv);
		}
		$myArray['Area'] = str_replace(',,,', ',,', $myArray['Area']);
		$myArray['Area'] = str_replace(',,', ',', $myArray['Area']);
		$myArray['Area'] = str_replace('|', ',', $myArray['Area']);

		//echo '<!-- wID: ' . $myArray['wID'] . " -->\n";
		//if ( !IS_AJAX ) { hc_getStyle_global(); }
		//if ( !IS_AJAX ) { hc_getStyle_searchresults(); }
		
		$myArray['Warn'] = 'false';
		// Obsolete: $lastSearchQuery = http_build_query($myArray, '', '&');

		$currentSearchJson = json_encode($myArray);
		if ( $showSearchForm ) {
			// ONLY STORE THE LAST SEARCH IN SESSION if we are rendering the search form (meaning it's a custom search being run by a lead )
			$_SESSION['lastSearchJSON'] = $currentSearchJson;//json_encode($myArray);
		}
		/*if (is_admin() && !IS_AJAX ) {
			$html .= "\n<!--\n	**************** SHORTCODE REFERENCE ******************\n Example Shortcode For Canned Search: \n[homecards page=\"runsearch\" query=\"" . $lastSearchQuery . "\"]\n-->\n";
		}*/
		// embed the css layout file
		if ( !IS_AJAX ) {
			wp_enqueue_style( 'hc-list-style', plugin_dir_url( __FILE__ ) . 'css/hc-list-style1.css' ); 
			// Make sure our settings get rendered  out right above the map ... this should change
			if ( empty($_SESSION['lastSearchJSON']) ) { $_SESSION['lastSearchJSON'] = "{}";}
			$html .= "<script type='text/javascript'>\n";
			$html .= "	window.lastSearchJson = " . $currentSearchJson . ";\n";
			$html .= "	if ( typeof window.ListingData == 'undefined' ) { window.ListingData = {viewed:[],viewedListings:[]}; };\n";
			$html .= "	window.ListingData.viewed = '" . $_SESSION['viewedListings'] . "'.split(',');\n";
			$html .= "	window.HCProxy = " . json_encode(array('leadJSON' => $leadJSON, 'urlRoot' => plugin_dir_url( __FILE__ ), 'ajaxurl' => admin_url('admin-ajax.php'))) . ";\n";
			$html .= "	window.hc_ajaxUrl = '" . admin_url('admin-ajax.php') . "';\n";
			$html .= "</s" . "cript>\n";

			$html .= '	<form class="hc_search_form hc-autocomplete hc-ajax' . $cannedSearchClass . '" method="post" action="' . $_SERVER["REQUEST_URI"] . '">' . "\n";
			$html .= "		<input type='hidden' name='PageLoadCount' class='hc_pageloadcount' id='hc_pageLoadCount' value='0' />\n"; // This is incremented by the .ready jQuery handler
			$html .= "		<input type='hidden' name='polygoncsv' class='polygoncsv' id='polygoncsv' value='" . $polygonCsv . "' />\n";
			$html .= "		<input type='hidden' name='PageNum' id='hc_pageNum' value='1' />\n";
			$html .= "		<input type='hidden' name='hc_search' id='hc_search' value='true' />\n";
		    $html .= "   	<input type='hidden' name='mls' class='mls' id='mls' value='".$_SESSION['mls']."' />\n"; 
			if ( strlen($atts['query']) > 1 ) {
				$q = $atts['query'];
				if ( strpos( $q, 'wid' ) == 0 ) { $q = substr($q, 10); }
				$html .= "		<input type='hidden' name='query' id='hc_query' value='" . esc_attr($q) . "' />\n";
				
			}

			if ( $atts['showmap'] == 'true' ) {
				$html .= "		<div class=\"hc-map-resizer\" style=\"margin: 0; padding: 0px;\">\n";
				$html .= '			<div class="hc-map-box" style="width: ' . $atts['mapwidth'] . '; height:'. $atts['mapheight'] .'; margin: 6px;">Loading Map... Please wait...</div>' . "\n";
				$html .= '			<div class="hc-map-status" style="width: ' . $atts['mapresizerwidth'] . '; height: 20px; margin: 6px;">Loading Map... Please wait...</div>' . "\n";
				$html .= "		</div>\n";
			}
			$html .= "		<div class='hc-results-paging'>\n\n</div>\n"; //TODO: Add paging numbers
      
		}

		if ( !IS_AJAX ) {
			wp_enqueue_style( 'hc-list-style', plugin_dir_url( __FILE__ ) . 'css/hc-list-style1.css' );	
			wp_enqueue_style( 'components', plugin_dir_url( __FILE__ ) . 'css/components.css' );
			//$html .= hc_addScript_search();
		}


		if ( $showSearchForm ) {
			$html .= "			<button name='hc_search_button' value='search'>Search Now</button>\n";
			$html .= "			<div class\"hc-search-counter-preview\"></div>\n";

			/* ... IT'S SO SMART IT HURTS 
			Let's re-render the search form above the "Search Results" - We can *easily* ajax call this...> because col1, col2, col3 will be set in the hidden fields of the form- ensuring it keeps re-rendering the same way
				.... , but ONLY show the form when the search results are generated by user input (e.g. NOT a canned search s) ...
			*** >>> This means we can support both form-POST (for SEO bots) and AJAX-based for real users
			... because we're awesome
			*/
			/***** COPIED FROM SEARCHFORM 'PAGE MODE' CODE BELOW ****/
				if (is_admin() && !IS_AJAX ) {
					$html .= "\n<!--	**************** SHORTCODE REFERENCE ******************\n	Example Shortcode For Search Form: \n[homecards page=\"searchform\" col1=\"" . $atts['col1'] . "\" col2=\"" . $atts['col2'] . "\" col3=\"" . $atts['col3'] . "\"]\n-->\n";
				}
				$html .= "			<input type='hidden' name='col1' value='" . $atts['col1'] . "' />\n			<input type='hidden' name='col2' value='" . $atts['col2'] . "' />\n			<input type='hidden' name='col3' value='" . $atts['col3'] . "' />\n";
				//echo $hc_proxy->getSearchForm($atts['col1'], $atts['col2'], $atts['col3']);
				$hc_html_form = hc_renderSearchForm($atts['col1'], $atts['col2'], $atts['col3']);
				$html .= $hc_html_form;
				// NOTE: </FORM> MOVED TO AFTER THE RESULTS: 
				// ^^^^ $html .= "		</form>\n";
			/****** END COPIED CODE ******/
		}/*if ( isset($atts['col1']) ) */
    if ( !IS_AJAX ) { $html .= "</form>\n"; }

		/* Here is where we actually get the pre-compiled HTML results from the server. */
		/* TODO: $html is being returned incorrectly, causing page to all load within this <div> element. */
		
		global $wp_filter ;
		if(isset($wp_filter['hc_search_results'])) { 
			// this is where custom rendered search code is injected
			$json = $hc_proxy->doSearch($atts['limit'], $myArray, '', 'JSON') ;
			$html .= apply_filters('hc_search_results',$json);
		} else {
			 
			// this is the default search pre-formatted HTML
			$searchResultsHtml = $hc_proxy->doSearch($atts['limit'], $myArray) . "<!-- ** END OF SEARCH RESPONSE ** -->\n";
			if ( !IS_AJAX ) { $html .= "	<div class='hc-results-list'>\n"; }
			if ( strlen($searchResultsHtml) < 10 || stripos($searchResultsHtml, 'error:') > 0 ) { 
				$html .= "		<h1 class='hc-no-results'>No Results Found.</h1>\n";
			} else {
				$html .= "		$searchResultsHtml\n";
			}
		}
		
		if ( !IS_AJAX ) { 
			$html .= "  </div>\n";
			// TODO: Numberic - Output Paging Buttons HTML - For Bottom of results
			$html .= "	<div class='hc-results-paging'>\n";
			$html .= "		\n";
			$html .= "	</div>\n";
		}
		
		/* Check if our plugin is connecting to the server */
		if ( strpos( $hc_html_form, 'data is empty' ) > -1 ) { 
			// OVERRIDE & SET THE HTML STRING TO THE ERROR STRING!!!
			 $html = PLUGIN_ERROR_HTML . "_1";
		}
		

	} else if ($atts['page'] == "searchform") {		/***** SEARCH FORM HANDLER CODE *****/
		// THIS LOADS THE PREVIOUS SEARCH OPTIONS: - SHOULD ONLY LOAD ON CANNED SEARCHES: hc_addScript_search();
		if (is_admin()) {
			$html .= "\n<!--	**************** SHORTCODE REFERENCE ******************\n	Example Shortcode For Search Form: \n[homecards page=\"searchform\" col1=\"" . $atts['col1'] . "\" col2=\"" . $atts['col2'] . "\" col3=\"" . $atts['col3'] . "\"]\n-->\n";
		}
		wp_enqueue_style( 'hc-list-style', plugin_dir_url( __FILE__ ) . 'css/hc-list-style1.css' );	
		wp_enqueue_style( 'components', plugin_dir_url( __FILE__ ) . 'css/components.css' );
	
		// Make sure our settings get rendered  out right above the map ... this should change
		if ( empty($_SESSION['lastSearchJSON']) ) { $_SESSION['lastSearchJSON'] = "{}";}
		$currentSearchJson = $_SESSION['lastSearchJSON'];
		$html .= "	<script type='text/javascript'>\n";
		// Not needed anymore: $html .= "		window.lastSearch = \"" . $_SESSION['lastSearchQuery'] . "\";\n";
		if ( strlen($currentSearchJson) < 4 ) {
			$default_search_json = stripslashes(get_option('hc_default_query', ''));
			if ( strlen($default_search_json) >= 4 ) {
				$currentSearchJson = $default_search_json;
			}
		}
		$html .= "		window.lastSearchJson = " . $currentSearchJson . ";\n";
		$html .= "		if ( typeof window.ListingData == 'undefined' ) { window.ListingData = {viewed:[],viewedListings:[]}; };\n";
		$html .= "		window.ListingData.viewed = '" . $_SESSION['viewedListings'] . "'.split(',');\n";
		$html .= "		window.HCProxy = " . json_encode(array('leadJSON' => $leadJSON, 'urlRoot' => plugin_dir_url( __FILE__ ), 'ajaxurl' => admin_url('admin-ajax.php'))) . ";\n";
		$html .= "		window.hc_ajaxUrl = '" . admin_url('admin-ajax.php') . "';\n";
		$html .= "	</s" . "cript>\n";
		
		$html .= '		<form class="hc_search_form hc-autocomplete hc-ajax" method="post" action="' . $_SERVER["REQUEST_URI"] . '">' . "\n";
		$html .= "			<input type='hidden' name='PageLoadCount' class='hc_pageloadcount' id='hc_pageLoadCount' value='0' />\n"; // This is incremented by the .ready jQuery handler
		$html .= "			<input type='hidden' name='PageNum' id='hc_pageNum' value='1' />\n";
		$html .= "			<input type='hidden' name='hc_search' id='hc_search' value='true' />\n";
		$html .= "			<input type='hidden' name='polygoncsv' class='polygoncsv' id='polygoncsv' value='" . $polygonCsv . "' />\n";
		// $html .= "   		<input type='hidden' name='mls' class='mls' id='mls' value='".$_SESSION['mls']."' />\n"; 
		if ( isset($atts['col1']) ) { $html .= "			<input type='hidden' name='col1' value='" . $atts['col1'] . "' />\n			<input type='hidden' name='col2' value='" . $atts['col2'] . "' />\n			<input type='hidden' name='col3' value='" . $atts['col3'] . "' />\n"; }
		if ( $atts['showmap'] == 'true' ) {
			$html .= "		<div class=\"hc-map-resizer\" style=\"margin: 0px; padding: 0px;\">\n";
				$html .= '			<div class="hc-map-box" style="width: ' . $atts['mapwidth'] . '; height:'. $atts['mapheight'] .'; margin: 6px;">Loading Map... Please wait...</div>' . "\n";
				$html .= '			<div class="hc-map-status" style="width: ' . $atts['mapresizerwidth'] . '; height: 20px; margin: 6px;">Loading Map... Please wait...</div>' . "\n";
			$html .= "		</div>\n";
		}
		// Output Paging Buttons HTML - For Top & Bottom of results
		$html .= "		<div class='hc-results-paging'>\n</div>\n";
		/* HERE'S WHERE THE FORM GET'S PUT TOGETHER & INCLUDED */
		$hc_html_form = hc_renderSearchForm($atts['col1'], $atts['col2'], $atts['col3']);
		$html .= getMLSSelectHTML(); // '<label for="mls">Select Desired MLS Provider: </label><select name="mls" id="mls" size="1"><option value="">Denver/Central-Colorado</option><option value="IRE">Boulder</option></select>';
		$html .= $hc_html_form;
		// Show button after form
		$html .= "			<button class=\"hc-btn-search\" name='hc_search_button' value='search'>Search Now</button>\n";

		// Output an empty 'hc-results-list' to recieve ajax query results
		$html .= "\n		<div class='hc-results-list'>\n		</div>\n\n";

		//$html .= "			<button class=\"hc-btn-search\" name='hc_search_button' value='search'>Search Now</button>\n";
		$html .= "		</form>\n";
    
		/* Check if our plugin is connecting to the server */
		if ( strpos( $hc_html_form, 'data is empty' ) > -1 ) { 
			// OVERRIDE & SET THE HTML STRING TO THE ERROR STRING!!!
			 $html = PLUGIN_ERROR_HTML . "_2\n <!-- " . $hc_html_form . " -->\n";
		}
    //TODO: Lets assume this needs to be everywhere :) -dd
    if (!defined('SHOW_DISCLAIMER')) {
      define( 'SHOW_DISCLAIMER', true );
    }
	} else if ($atts['page'] == "signup" || $atts['page'] == "signupform") {
		$html .= hc_buildLeadSignupForm(true);

	} else if ($atts['page'] == "city" || strlen($atts['city']) > 1) {
		/***** CITY DETAILS PAGE HANDLER CODE *****/
		/***** CITY DETAILS PAGE HANDLER CODE *****/
		/***** CITY DETAILS PAGE HANDLER CODE *****/
		$html .= $hc_proxy->getCityPage($atts['city'], $atts['pricerange'], $atts['bedsrange'], $atts['bathsrange'], $atts['subareas']);
		// MIGHT BE NEEDED: TODO: MERGE: wp_enqueue_style( 'hc-list-style', plugin_dir_url( __FILE__ ) . 'css/hc-list-style1.css' );	
		wp_enqueue_style( 'components', plugin_dir_url( __FILE__ ) . 'css/components.css' );	
		//wp_enqueue_style( 'city-page-style', plugin_dir_url( __FILE__ ) . 'css/city-page-style1.css' );	
		//wp_enqueue_style( 'hc-search-results-theme', plugin_dir_url( __FILE__ ) . 'css/pd_style_blue.css' );	
		
	} else if ($atts['page'] == "loginbox" || $atts['page'] == "login") {
		/**** RENDER LOGIN BOX ****/
		/**** RENDER LOGIN BOX ****/
		/**** RENDER LOGIN BOX ****/
		$html .= hc_buildLoginForm(true);

	} else if ($atts['page'] == "mapsearch" || $atts['page'] == "map") {
		/***** NEW MAP SEARCH HANDLER CODE *****/
		/***** MAP SEARCH is now on the regular search screen, 
			because it features a big freakin map by default *****/

		$html .= homecards_shortcode(array( 'page' => 'search', 'showmap' => 'true' ));
	} else if ( $atts['page'] == "oldmapsearch" ) {
		/***** MAP SEARCH HANDLER CODE *****/
		/***** MAP SEARCH HANDLER CODE *****/
		/***** MAP SEARCH HANDLER CODE *****/
		// SEE homecards-oldcode.php -> hc_oldmapsearch() // Temporary reference - not used anymore because we now have an awesome & fully integrated map search (no frame/iframe)
	} else if ($atts['page'] == "featuredlistings") {
		//if (get_option('wp_hc_disablecss') != '1') { hc_getStyle_searchresults(); }
		wp_enqueue_style( 'hc-list-style', plugin_dir_url( __FILE__ ) . 'css/hc-list-style1.css' );
		wp_enqueue_style( 'components', plugin_dir_url( __FILE__ ) . 'css/components.css' );	
		//wp_enqueue_style( 'hc-search-results-theme', plugin_dir_url( __FILE__ ) . 'css/pd_style_blue.css' );	
		$html .= $hc_proxy->getFeaturedListings($atts['limit']);
	}
  if ( !IS_AJAX ) { 
		if ( defined( 'SHOW_DISCLAIMER') ) {
			add_filter( 'the_content', 'hc_hideDisclaimer', 100 );
		}
	}	
	return $html;
}

function hc_buildLoginForm($returnString = false) {

	$html = "<script type='text/javascript'>\n";
	$html .= "	window.hc_ajaxUrl = '" . admin_url('admin-ajax.php') . "';\n";
	$html .= "</s" . "cript>\n";

	//var_dump($_SESSION['HC_Login']);
	//$_SESSION['HC_Login'] = null;
	if (isset($_SESSION['HC_Login']) && strlen($_SESSION['HC_Login']) > 6) {
		// already logged in
		$strJson = $_SESSION['HC_Login'];
		if (stripos($strJson, 'FirstName') > 0) {
			$leadJSON = json_decode($strJson, true);
			if (isset($leadJSON['FirstName'])) { $fName = $leadJSON['FirstName']; }
			if (isset($_SESSION['mls'])) {		$leadJSON['Board'] = $_SESSION['mls'];	}
		}
		$html .= "\r\n<div class='hc_login_msg hc_user_msg'>Welcome back " .  htmlspecialchars($fName) . "</div>";
		$html .= "<a class='hc_logout' onclick='hc_leadLogout()'>Click here to logout</a>";
		
	} else {
		$html = '';
		if ( function_exists('hc_getStyle_leadLoginForm') ) { $html .= hc_getStyle_leadLoginForm(); }
		$html .= '<form autocomplete="off" class="hc_login_form" method="get" action="" onsubmit="return hc_leadLogin(jQuery(\'.hc_login_email\', this).val(), jQuery(\'.hc_login_password\', this).val(), this)">';
		$html .= "	<ul class='hc_login_fields'>\n";
		$html .= "		<li>\n";
		$html .= "			<label for='hc_login_email'>Email:*</label> <input id='hc_login_email' name='hc_login_email' class='hc_input hc_get_focus hc_login_email' />\n";
		$html .= "		</li>\n";
		$html .= "		<li>\n";
		$html .= "			<label for='hc_login_password'>Password:*</label> <input id='hc_login_password' name='hc_login_password' class='hc_input hc_login_password' type='password' />\n";
		$html .= "		</li>\n";
		$html .= "		<li>\n";
		$html .= "			<div id='hc_login_ajax'></div>\r\n<input type='submit' name='hc_login_button' value='Submit' class='hc_button' />\n";
		$html .= "		</li>\n";
		$html .= "	</ul>\n";
		$html .= "</form>\n";
	}
	if ($returnString) { return $html; }
	echo($html);
	
}

function hc_buildLeadSignupForm($returnString = false) {
	if ( function_exists('hc_getStyle_signupForm') ) { hc_getStyle_signupForm(); }
	$html = "<script type='text/javascript'>\n";
	$html .= "	window.hc_ajaxUrl = '" . admin_url('admin-ajax.php') . "';\n";
	$html .= "</s" . "cript>\n";

	if (isset($_SESSION['HC_Login']) && strlen($_SESSION['HC_Login']) > 6) {
		// already logged in
		$strHTML = $_SESSION['HC_Login'];
		if (stripos($strHTML, 'FirstName') > 0) {
			$leadJSON = json_decode($strHTML, true);
			if (isset($leadJSON['FirstName'])) { $fName = $leadJSON['FirstName']; }
			if (isset($_SESSION['mls'])) {		$leadJSON['Board'] = $_SESSION['mls'];	}
			
		}
		$strHTML = "<div class='hc_login_msg hc_user_msg' style='font-weight: 700;'>Welcome back " .  htmlspecialchars($fName) . "</div>";
		$strHTML .= "<a class='hc_logout' onclick='hc_leadLogout()' style='text-decoration: underline; font-size: small; margin: 4px; cursor: pointer;'>Click here to logout</a>";
		
	} else {
		$strHTML = '<form class="hc_signup_form" method="get" action="" onsubmit="hc_leadSignup(this); return false;">';
		$strHTML .= "	<ul class='hc_signup_fields' >\n";
		$strHTML .= "		<li>\n";
		$strHTML .= "			<label for='hc_signup_name'>Name:*</label> <input id='hc_signup_name' name='SignupName' class='hc_input hc_get_focus' />\n";
		$strHTML .= "		</li>\n";
		$strHTML .= "		<li>\n";
		$strHTML .= "			<label for='hc_signup_phone'>Phone:</label> <input id='hc_signup_phone' name='SignupPhone' class='hc_input' />\n";
		$strHTML .= "		</li>\n";
		$strHTML .= "		<li>\n";
		$strHTML .= "			<label for='hc_signup_email'>Email:*</label> <input id='hc_signup_email' name='SignupEmail' class='hc_input' />\n";
		$strHTML .= "		</li>\n";
		$strHTML .= "		<li>\n";
		$strHTML .= "			<label for='hc_signup_password'>Password:*</label> <input id='hc_signup_password' name='SignupPassword' class='hc_input' type='password' />\n";
		$strHTML .= "		</li>\n";
		$strHTML .= "		<li>\n";
		$strHTML .= "			<input type='submit' name='hc_signup_button' value='Submit' class='hc_button' />\n<div class='hc_signup_ajax'></div>\n";
		$strHTML .= "		</li>\n";
		$strHTML .= "	</ul>\n";
		$strHTML .= "</form>\n";
	}
	
	if ($returnString) { return $strHTML; }
	echo($strHTML);
}

add_shortcode('homecards', 'homecards_shortcode');

add_shortcode('homecardssearch', 'homecards_search_shortcode');






function proper_parse_str($str) {
  # result array
  $arr = array();
  # split on outer delimiter
  $pairs = explode('&', $str);
  # loop through each pair
  foreach ($pairs as $i) {
    # split into name and value
    list($name,$value) = explode('=', $i, 2);
    # if name already exists
    if( isset($arr[$name]) ) { /* stick multiple values into an array */
      if( is_array($arr[$name]) ) {
        $arr[$name][] = $value;
      } else {
        $arr[$name] = array($arr[$name], $value);
      }
    } else {/* otherwise, simply stick it in a scalar*/
      $arr[$name] = $value;
    }
  }

  # return result array
  return $arr;
}




/***** COPIED FROM short-codes-2.php *** REFACTOR AFTER SPEC COMPLETE *****/
$htmlFieldTable = array();
$searchFields = array();

	
	function hc_renderSearchForm($col1Csv, $col2Csv, $col3Csv) {
		
		$html = '';
		$htmlLI = '';
		$tdWidth = '100%';
		if (strlen($col1Csv) >= 1) { $col1 = explode(',', $col1Csv); }
		if (strlen($col2Csv) >= 1) { $col2 = explode(',', $col2Csv); $tdWidth = '50%'; }
		if (strlen($col3Csv) >= 1) { $col3 = explode(',', $col3Csv); $tdWidth = '30%'; /* nb: 33% does not account for padding/margin */ }
		
		
		$html .= "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" class='hc-search-table'>\n	<tr>\n";
		if (strlen($col1Csv) >= 1 && is_array($col1) && count($col1) >= 1) {
			foreach ($col1 as $fld) {
				$htmlLI .= getSearchFieldHtml($fld, '', 'full');
			}
			$html .= "		<td width='" . $tdWidth . "' valign=\"top\"><ul class='hc-searchform hc-searchform-col1'>\n" . $htmlLI . "\n</ul>\n</td>\n";
		}
		if ( strpos($html, 'data is empty') > -1 ) { return PLUGIN_ERROR_HTML . "_3\n <!-- " . $html . " -->\n"; }
		$htmlLI = '';
		if (strlen($col2Csv) >= 1 && is_array($col2) && count($col2) >= 1) {
			foreach ($col2 as $fld) {
				$htmlLI .= getSearchFieldHtml($fld, '', 'full');
			}
			$html .= "		<td width='" . $tdWidth . "' valign=\"top\"><ul class='hc-searchform hc-searchform-col2'>\n" . $htmlLI . "\n</ul>\n</td>\n";
		}
		if ( strpos($html, 'data is empty') > -1 ) { return PLUGIN_ERROR_HTML . "_4"; }
		$htmlLI = '';
		if (strlen($col3Csv) >= 1 && is_array($col3) && count($col3) >= 1) {
			foreach ($col3 as $fld) {
				$htmlLI .= getSearchFieldHtml($fld, '', 'full');
			}
			$html .= "		<td width='" . $tdWidth . "' valign=\"top\"><ul class='hc-searchform hc-searchform-col3'>\n" . $htmlLI . "\n</ul>\n</td>\n";
		}
		$html .= "	</tr>\n</table>";
		if ( strpos($html, 'data is empty') > -1 ) { return PLUGIN_ERROR_HTML . "_5"; }
		
		return $html;
	}
	function getSearchFieldHtml($fieldData, $lblCss = 'hc-lbl-lrg', $returnMode = 'full') {
		$myFieldId = "";
		if (is_string($fieldData)) {$myFieldId = $fieldData; $fieldData = getField($fieldData);}
		//var_dump($fieldData);
		if (!isset($htmlFieldTable) || count($htmlFieldTable) < 1 ){ $htmlFieldTable = json_decode(hc_get_fields_html());}
		if ($fieldData == null) { return "";/*'<h4>field data is empty</h4>' . "\n";*/ }
		$fieldHtmlContent = $htmlFieldTable->{strtolower($fieldData["_id"])};
		$fieldHtmlContent = addIdAttr(addClass($fieldHtmlContent, 'headSearchFormFields ' . $fieldData["class"]), $fieldData["id"]);
		return '				<li class="' . $fieldData["class"] . '_parent"><label for="' . $fieldData["id"] . '">' . $fieldData["caption"] . ':</label>' . $fieldHtmlContent . "</li>\n";
	}

