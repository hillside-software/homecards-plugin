<?php
if (!isset($_SESSION)) { session_start(); }

function hc_getAccountJson() {
	$hc_proxy = new HomeCardsProxy();
	$json = $hc_proxy->getAccountJson();
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
		header( "Content-Type: text/javascript" );
		echo $json;
		exit();
	}
	return $json;
}

function hc_getFeaturedListingsJson() {
	$hc_proxy = new HomeCardsProxy();
	$json = $hc_proxy->getFeaturedListingsJson();
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
		header( "Content-Type: text/javascript" );
		echo $json;
		exit();
	}
	return $json;
}

function hc_get_fields() {
	$hc_proxy = new HomeCardsProxy();
	$json = $hc_proxy->getSearchFieldsJSON();
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
		header( "Content-Type: text/javascript" );
		echo $json;
		exit();
	}
	return $json;
}
function hc_get_fields_html() {
	$hc_proxy = new HomeCardsProxy();
	$json = $hc_proxy->getSearchFormHtmlAndJson();
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
		header( "Content-Type: text/javascript" );
		echo $json;
		exit();
	}
	return $json;
}


 
//Davesnewstuff
function hc_get_updatable_csv() {
	$hc_proxy = new HomeCardsProxy();
	return $hc_proxy->getAgentFields();
}
function hc_get_agent_json() {
	$hc_proxy = new HomeCardsProxy();
	$json = $hc_proxy->getWebAgent();
	return json_decode($json);
}
/*Needs Security FIXEDLIKESEX 
Example: 
hc_update_agent(json_encode( array('First'=>'Joe', 'Last'=> 'Steele') ));
*/
function hc_update_agent($json) {
	if(is_admin() || admin_head()) {
		$hc_proxy = new HomeCardsProxy();
		$json = str_replace('\"', "\"", $json);
		return $hc_proxy->updateAccountInfo($json);
		//return json_decode($json);
	}
}


//getSavedListingsJSON
function hc_ajax_get_lead_saved_listings() {
	$authToken = ''; //$_SESSION['HC_AuthToken'];
	$cid = $_REQUEST['cID'];
	if (empty($authToken) && isset($_SESSION['HC_AuthToken'])) {$authToken = $_SESSION['HC_AuthToken'];}
	if (empty($authToken) && isset($_REQUEST['AuthToken'])) {$authToken = $_REQUEST['AuthToken'];}
	if ( isset($_REQUEST['LeadToken'])) {$authToken .= '|' . $_REQUEST['LeadToken'];}
	if (empty($authToken) || strlen($authToken) < 5 ) {
		echo '{"Message": "Please login.", "Error": "Please login", "Token": "' . $authToken . '", "AuthToken": "' . $_REQUEST['AuthToken'] . '", "LeadToken": "' . $_REQUEST['LeadToken'] . '", "ListingID": "' . $listingID . '"}';
		exit();
	}
	$hc_proxy = new HomeCardsProxy(false);
	$html = $hc_proxy->getSavedListingsJSON($cid);
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
		header( "Content-Type: text/javascript" );
		echo trim($html);
		exit();
	}
	return $html;
}


function hc_ajax_lead_save_listing() {
	$notes = '';
	$cID = -1;
	if (isset($_REQUEST['cID']) === true) { $cid = $_REQUEST['cID']; }
	if (isset($_POST['notes']) === true) { $notes = $_POST['notes']; }
	if (isset($_REQUEST['ListingID']) === true) { $listingID = $_REQUEST['ListingID']; }
	if (isset($_POST['source']) === true) { $source = $_POST['source']; }
	$authToken = ''; //$_SESSION['HC_AuthToken'];
	if (empty($authToken) || isset($_SESSION['HC_AuthToken'])) {$authToken .= '|' . $_SESSION['HC_AuthToken'];}
	if (empty($authToken) || isset($_REQUEST['AuthToken'])) {$authToken .= '|' . $_REQUEST['AuthToken'];}
	if (empty($authToken) || isset($_REQUEST['LeadToken'])) {$authToken .= '|' . $_REQUEST['LeadToken'];}
	if (empty($authToken) || strlen($authToken) < 5 ) {
		echo '{"Message": "Please login.", "Error": "Please login", "Token": "' . $authToken . '", "AuthToken": "' . $_REQUEST['AuthToken'] . '", "LeadToken": "' . $_REQUEST['LeadToken'] . '", "ListingID": "' . $listingID . '"}';
		exit();
	}
	$hc_proxy = new HomeCardsProxy(false);
	$html = $hc_proxy->saveProperty($listingID, $cID, $source, $authToken, $notes);
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
		header( "Content-Type: text/javascript" );
		echo trim($html);
		exit();
	}
	return $html;
}

function hc_ajax_lead_login() {
	if (isset($_POST['email']) === true) { $email = $_POST['email']; }
	if (isset($_POST['pass']) === true) { $password = $_POST['pass']; }
	
	$hc_proxy = new HomeCardsProxy(false);
	$leadJSON = $hc_proxy->consumerLogin($email, $password);

	ob_clean();
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
		header( "Content-Type: text/javascript" );
		echo json_encode($leadJSON);
		exit();
	}

	return $leadJSON . "\n\n\n";
}


function hc_ajax_signup() {
	$SignupName = ''; $SignupPhone = ''; $SignupEmail = ''; $SignupPassword = ''; $source = ''; $refererURL = '';
	if (isset($_POST['SignupName']) === true) { $SignupName = $_POST['SignupName']; }
	if (isset($_POST['SignupPhone']) === true) { $SignupPhone = $_POST['SignupPhone']; }
	if (isset($_POST['SignupEmail']) === true) { $SignupEmail = $_POST['SignupEmail']; }
	if (isset($_POST['SignupPassword']) === true) { $SignupPassword = $_POST['SignupPassword']; }
	if (isset($_POST['Source']) === true) { $source = $_POST['Source']; }
	if (isset($_POST['RefererURL']) === true) { $refererURL = $_POST['RefererURL']; }


	$hc_proxy = new HomeCardsProxy(false);
	$leadJSON = $hc_proxy->consumerSignup($SignupName, $SignupPhone, $SignupEmail, $SignupPassword, $source, $refererURL);
	
	ob_clean();
	header( "Content-Type: text/javascript" );
	echo $_SESSION['HC_Login'];
	exit();
}
























class HomeCardsProxy {
	var $cURL;
	var $version = '1.8.1';
 	var $debugPath = '';
 	
 	var $enableCaching = true;
 	
 	/** SET YOUR HOMECARDS ACCOUNT'S VARIABLES - (OR YOU MUST UPDATE IN CODE) **/
 	//var $hc_domain_name = 'www.myhomecards.com';//'dev01.den.hillside-tech.com'; /* example: www.joshsellsdenver.com */
 	var $login_domain_name = "www.myhomecards.com"; //  'dev01.den.hillside-tech.com'; 
 	var $hc_domain_name = 'www.myhomecards.com'; //dev01.den.hillside-tech.com'; // "www.myhomecards.com";   example: www.joshsellsdenver.com 
 	var $webid = -1; /* example: 12345 ... Note: This is the "wID" at the end of almost all links in your site */
 	
 	/* Optional Traffic Source Modifier (used for special tracking of traffic using this HTTP Webservice Proxy) */
 	var $hc_source_tag = 'WP';
 	
 	//var $debugEnabled = false;
 	var $debugEnabled = false;
 	
 	var $token = '';
	
	function getHTML($url, $args) {
		if (isset($args)) {
			$response = wp_remote_get( $url, $args );
		} else {
			$args = array('headers' =>$this->hc_getNewHeaders() );
			$response = wp_remote_get( $url );
		}
		if (isset($response) && isset($response['body'])) {
			$s = $response['body'];
			if (stripos($s, 'Server Error') === false) {
				return $s;
			} else {
				return 'Alert: The HomeCards Plugin encountered an error from the remote server!';
			}
		}
		return "<h3>HC Plugin Error! Invalid Response Encountered</h3>";
	}

	function HomeCardsProxy($enableMessages = true) {
		global $wpdb;
		$agentData = hc_checkSession();
		
		$rootUrl = get_bloginfo('url');
		$this->token = get_option('wp_hc_token', 'NULL');
		
		if ( isset($_SESSION['HC_SiteToken']) && strlen($this->token) < 1 ) { 
			$this->token = $_SESSION['HC_SiteToken'];
			update_option('wp_hc_token', $this->token);
		}
		
		//$this->debugEnabled = true;
		$t = get_option('wp_hc_siteurl');
		if ( isset($t) && strlen($t) > 1 ) {$this->hc_domain_name = $t;}

		/// OK IF DOMAIN HAS PORT SPECIFIED: e.g. dev01.den.hillside-tech.com:8888
		if ((stripos($this->hc_domain_name, '.den.hillside') > 0 && stripos($this->hc_domain_name, ':') <= 0) || stripos($this->hc_domain_name, 'localhost') > -1) {
			$this->debugPath = '/HomeCards';
			$this->debugEnabled = true;
		} else if (stripos($this->hc_domain_name, '.den.hillside') > 0 && stripos($this->hc_domain_name, ':') >= 1) {
			$this->debugEnabled = true;
		} else {
			// FIX FOR MIS-CONFIGURED HC DOMAINS
			$this->hc_domain_name = "www.myhomecards.com";
			$this->debugPath = '';
		}
		$this->hc_source_tag = 'WP_' . $this->version;
		$this->webid = get_option('wp_hc_webid', -1);
		if ( $this->webid <= 0 ) {
			$this->webid = $agentData['WebID'];
			update_option('wp_hc_webid', $this->webid);
		}
		
		/* Validate fields */
		if ($enableMessages && $this->webid < 10000 && is_admin() && (isset($_POST) && isset($_POST['login']) == true)) {
			echo('<div id="message" class="updated"><p><h1>HomeCards Plugin: <a href="plugins.php?page=homecards-plugin.php">Please set your account options</a>!</h1></p></div>');
		}
		if ($enableMessages && $this->webid < 10000 && is_admin() == false && (isset($_POST) && isset($_POST['login']) && strlen($_POST['login']) === true)) {
			echo('<div id="message" class="updated"><p><h1>HomeCards Plugin: Please set your account options!</h1></p></div>');
		}
		//$this->cURL = new mycurl();
		if (isset($this->webid) && isset($_POST) == true && isset($_POST['login']) == true) {
			//$this->cURL->setCookiFileLocation('./HomeCards_Proxy_Cookie_' . $wpdb->prefix . '_' . $this->webid . '.txt');
		}
	}

	function checkResponseHtml($strHTML) {
		if (!isset($strHTML) || strlen($strHTML) < 3) {
			return "<div class='hc_error'>Results could not be loaded!</div>";
		}
		if (stripos($strHTML, 'Server Error in') === false) {
			return $strHTML;
		} else {
			return 'We have encountered an error. Please try again later.\n' . $strHTML;
		}
	}
/**** Example accepted $queryString values (either string literal or array): ****
	
	$queryString = array('Area' => '<ALL>', 
		'Available' => '<ALL>', 
		'AREABOX' => 'USECITY', 
		'TYPE' => '<ALL>',
		'Status' => 'A,P',
		'PriceFrom' => '100000',
		'PriceTo' => '99350000', 
		'Beds' => '>=2',
		'Baths' => '>=2', 
		'SortResults' => 'Price.Desc', 
		'DaysBack' => '0', 
		'Warn' => 'False');

		IMPORTANT: If $queryString is a String Literal, it's values MUST be URL Encoded!!!
 */
	function doSearch($limit, $queryString, $actionOverride = '', $outputMode = 'HTML') {
		$strHTML = "";
		
		if (! isset($limit)) {$limit = 30;}
		$queryString_str = '';
		/*$strHTML .= "<!-- doSearch.queryString: ";
		$strHTML .= print_r($queryString, true);
		$strHTML .= " -->\n";*/
		
		if (gettype($queryString) == 'array') {
			foreach ($queryString as $k => $v) {
				// EXCLUDE query values: action, wID, col1, col2, col3
				if ( strtolower($k) != 'action' && strtolower($k) != 'mls' && strtolower($k) != 'wid' && strtolower($k) != 'col1' && strtolower($k) != 'col2' && strtolower($k) != 'col3' ) {
					if (is_array($v) ) { $v = implode(',',$v);}
					if ( stripos( $k . $v, '%' ) <= 0 || stripos($k . $v, '&') >= 0  ) {
						// UrlEncode the values, skip this step if the value is already urlencoded (Percents should not normally occur in search queries)
						$k = urlencode($k);
						$v = urlencode($v);
					}
					$queryString_str .= $k .'='. $v . '&';
				}
			}
			$queryString_str = trim(trim($queryString_str), "&");
		} else {
			$queryString_str = $queryString;
		}

		//old: if ( empty($queryString['wID']) && empty($queryString['wid'])  ) {
		if ( stripos( strtolower($queryString_str), 'wid' ) <= -1 && $this->webid > 10000 ) {
			$queryString_str .= '&wID=' . $this->webid;
		} else {
			
		}


		$action = 'Search';
		if ( strlen($outputMode) >= 1 && strtolower($outputMode) == "mapjson" || strtolower($outputMode) == "map" ) {
			$limit = 200;
			$action = "Search";
			$outputMode = "MAPJSON";
			$strHTML = "";
		} else if ( strlen($actionOverride) >= 1 && $actionOverride == "SearchCount" ) {
			$action = "SearchCount";
			$outputMode = "JSON";
			$strHTML = "";
		} else if ( strlen($actionOverride) >= 1 && $actionOverride == "GetLocalStats" ) {
			$action = "GetLocalStats";
			$outputMode = "JSON";
			$strHTML = "";
		} else {// Only include the following dbg stuff if we are NOT getting the Count JSON
			//$strHTML .= "<!-- Search.query: $queryString_str -->\n";
		}
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?Action='. $action .'&OutputMode='. $outputMode .'&' .'mls='.$_SESSION['mls'] .'&' .$queryString_str . '&Limit=' . $limit . '&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token());
		
		
		/*
			check if .URL contains wID if not, add wID
		*/
		
		
		if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$strHTML .= wp_remote_retrieve_body($getReq);
		if ( strlen($actionOverride) > 1 ) {
			//@header( "Content-Type: text/javascript" );
			return $strHTML;
		}
		if (!isset($strHTML) || strlen($strHTML) < 3) {
			return "<div class='hc_error'>Results could not be loaded!</div>";
		}
		
		return $this->checkResponseHtml($strHTML);
	}
	/* Added By Dan: 2012-04-01 */
	function getSearchMapJSON() {
		return $this->doSearch(get_option('hc_searchresultslimit', '20'), $_REQUEST, "Search", "MAPJSON");
	}
	/* Added By Dan: 2012-04-01 */
	function getSearchCount() {
		return $this->doSearch(get_option('hc_searchresultslimit', '20'), $_REQUEST, "SearchCount", "JSON");
	}
	/* Added By Dan: 2012-04-17 
	getSearchStats Takes the same parameters as a SEARCH request... It returns stats based on the query */
	function getSearchStats() {
		return $this->doSearch(get_option('hc_searchresultslimit', '20'), $_REQUEST, "GetLocalStats", "JSON");
	}

	function getFeaturedListings($limit = 20) {
		$dbgHtml = "";
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=GetFeaturedListings&Limit=' . $limit . '&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token());
		if ($this->debugEnabled) {echo("<!-- URL: $url \n\n\n			****** 			" . $_SERVER['DOCUMENT_ROOT'] . " -->\n");}
		$cacheKey = "hc_ft_" . $this->webid . "";
		//if ($this->enableCaching) {
			$t = hc_get($cacheKey);
			if (strlen($t) > 5) {
				if ($this->debugEnabled) {$dbgHtml .= "<!-- Featured Listings Json Loaded from Cache!!! -->\n";} 		//if ($this->debugEnabled) {echo("<!-- *** SEARCH FORM IS CACHED *** -->\n");}
				return $this->checkResponseHtml($t);
			}	else {
				if ($this->debugEnabled) {$dbgHtml .= "<!-- Featured Listings Json Loaded from Cache!!! -->\n";} 		//if ($this->debugEnabled) {echo("<!-- *** SEARCH FORM IS CACHED *** -->\n");}
			}
		//}
		$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$strHTML = wp_remote_retrieve_body($getReq);
		
		if ($this->debugEnabled || $this->enableCaching == false) {
			$cacheTimeoutSec = 60; //Seconds
		} else {
			$cacheTimeoutSec = 60 * 60 * 1; //1 Hour
		}
		if (isset($strHTML) && stripos($strHTML, 'Error:') === false && strlen($strHTML) > 0) { 
			//if ($this->enableCaching) {
			hc_set($cacheKey, $strHTML, $cacheTimeoutSec);
			return $dbgHtml . $this->checkResponseHtml($strHTML);
		} else {
			return $dbgHtml . "\n<!-- Fatal Error: Could not get or load $cacheKey to WP Transients. getFeaturedListingsJson Not Loaded # " . stripos($strHTML, 'Error:') . " -->\n";
		}
		return $dbgHtml . $this->checkResponseHtml($strHTML);
	}

	function getFeaturedListingsJson($limit = 20) {
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=GetFeaturedListingsJson&Limit=' . $limit . '&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token());
		$cacheKey = "hc_ft_" . $this->webid . "_js";
		//if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		//if ($this->enableCaching) {
			$t = hc_get($cacheKey);
			if (strlen($t) > 5) {
				if ($this->debugEnabled) {$dbgHtml = "<!-- Featured Listings Json Loaded from Cache!!! -->\n";} 		//if ($this->debugEnabled) {echo("<!-- *** SEARCH FORM IS CACHED *** -->\n");}
				return $this->checkResponseHtml($t);
			}
		//}
		$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$strHTML = wp_remote_retrieve_body($getReq);
		if ($this->debugEnabled || $this->enableCaching == false) {
			$cacheTimeoutSec = 15; //Seconds
		} else {
			$cacheTimeoutSec = 60 * 60 * 4; //4 Hour
		}
		if (isset($strHTML) && stripos($strHTML, 'Error:') === false && strlen($strHTML) > 0) { 
			hc_set($cacheKey, $strHTML, $cacheTimeoutSec);
			return $this->checkResponseHtml($strHTML);
		} else {
			return "<!-- Fatal Error: Could not get or load $cacheKey to WP Transients. getFeaturedListingsJson Not Loaded -->\n";
		}
		return $this->checkResponseHtml($strHTML);
	}

	function getAccountJson() {
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=GetAccountJson&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&Roles=' . urlencode(hc_get_user_roles()) . '&WP_UserID=' . get_current_user_id();
		$cacheKey = "hc_act_" . $this->webid . "_js";
		//if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		//	if ($this->enableCaching) {
			$t = hc_get($cacheKey);
			if (strlen($t) > 5) {
				if ($this->debugEnabled) {$dbgHtml = "<!-- AccountJson Loaded from Cache!!! -->\n";} 		//if ($this->debugEnabled) {echo("<!-- *** SEARCH FORM IS CACHED *** -->\n");}
				return $this->checkResponseHtml($t);
			}
		//	}
		$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$strHTML = wp_remote_retrieve_body($getReq);
		if ($this->debugEnabled || $this->enableCaching == false) {
			$accountJsonTimeoutSec = 300; //Seconds
		} else {
			$accountJsonTimeoutSec = 60 * 60 * 1; //1 Hour
		}
		if (isset($strHTML) && stripos($strHTML, 'Error:') === false && strlen($strHTML) > 0) { 
			hc_set($cacheKey, $strHTML, $accountJsonTimeoutSec);
			return $this->checkResponseHtml($strHTML);
		} else {
			return "<!-- Fatal Error: Could not get or load $cacheKey to WP Transients. getAccountJson Not Loaded -->\n";
		}
	}
		
	/* NEW */
	function getFullPropertyDetails($listingId) {
		$start = microtime(true);
		if (! isset($limit)) {$limit = 20;}
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=GetFullPropertyDetails&ListingID=' . $listingId . '&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&WP_UserID=' . get_current_user_id();
		if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$strHTML = wp_remote_retrieve_body($getReq);
		return $this->checkResponseHtml($strHTML);
	}
	
	/* NEW */
	function getFullPropertyDetailsJSON($listingId) {
		$start = microtime(true);
		if (! isset($limit)) {$limit = 20;}
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=GetFullPropertyDetails&ListingID=' . $listingId . '&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&WP_UserID=' . get_current_user_id() . '&outputMode=JSON';
		if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$strHTML = wp_remote_retrieve_body($getReq);
		return $this->checkResponseHtml($strHTML);
	}
	
	// Note: Dan 2012-04-02 ... getSearchForm is probably not used anymore... 
	/* getSearchForm Returns GENERATED HTML */
	function getSearchForm($col1, $col2, $col3) {
		$start = microtime(true);
		if (! isset($col1)) {$col1 = "Area,Beds,Baths,PriceFrom,PriceTo,Subarea,MLSRemarks,Zip";}
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=GetHtmlSearchForm&CssPrefix=' . $formPrefix . '&IncludeFieldsCSV=' . $col1 . '&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&WP_UserID=' . get_current_user_id();
		if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		$cacheKey = "hc_search_" . $this->webid . "_" . substr(md5($col1 . $col2 . $col3, false), 0, 10);
		//if ($this->enableCaching) {
			$t = hc_get($cacheKey);
			if (strlen($t) > 5) {
				if ($this->debugEnabled) {$dbgHtml = "<!-- Search Form Loaded from Cache!!! -->\n";}
				if ($this->debugEnabled) {echo("<!-- *** SEARCH FORM IS CACHED *** -->\n");}
				return $t;
			}
		//}
		$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$strHTML = wp_remote_retrieve_body($getReq);
		
		/* Note: 60*60*12 = 12 hours */
		if (isset($strHTML) && stripos($strHTML, 'Error:') === false && strlen($strHTML) > 12) { hc_set($cacheKey, $strHTML, 60*60*1); }

		//if ($this->debugEnabled) { echo("<!-- *** getSearchForm " . (microtime(true) - $start) . " *** -->\n"); }
		return $this->checkResponseHtml($strHTML);
	}
	/* NEW */
	
	function getSearchFormHtmlAndJson() {
		$start = microtime(true);
		global $hc_getSearchFormHtmlAndJson;
		if ( isset($hc_getSearchFormHtmlAndJson) && strlen($hc_getSearchFormHtmlAndJson) > 5 ) { 
			//if ($this->debugEnabled) { echo("<!-- *** getSearchFormHtmlAndJson " . (microtime(true) - $start) . " GLOBALS *** -->\n"); }
			return $hc_getSearchFormHtmlAndJson; 
		}
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=GetSearchFormHtmlAndJson&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&WP_UserID=' . get_current_user_id();
		$cacheKey = "hc_searchform_json_" . $this->webid;
		if ($this->enableCaching) { $t = hc_get($cacheKey); } else {$t = "";}
		if (strlen($t) > 5) { return $t; }
		
		// /////// if ($this->debugEnabled) {echo("/* URL: $url */\n");}
		$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$strHTML = wp_remote_retrieve_body($getReq);
		if (isset($strHTML) && stripos($strHTML, 'Error:') === false && strlen($strHTML) > 12) { 
			//if ($this->enableCaching) { hc_set($cacheKey, $strHTML, 60*60*96); }
			$hc_getSearchFormHtmlAndJson = $strHTML;
		}

		//if ($this->debugEnabled) { echo("<!-- *** getSearchFormHtmlAndJson " . (microtime(true) - $start) . " " . $url . " *** -->\n"); }
		return $strHTML;
	}
	function getSearchFieldsCSV() {
		$start = microtime(true);
		global $hc_getSearchFieldsCSV;
		if ( isset($hc_getSearchFieldsCSV) && strlen($hc_getSearchFieldsCSV) > 5 ) { 
			//if ($this->debugEnabled) { echo("<!-- *** getSearchFieldsCSV " . (microtime(true) - $start) . " GLOBALS *** -->\n"); }
			return $hc_getSearchFieldsCSV; 
		}
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=GetSearchFieldsCSV&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&WP_UserID=' . get_current_user_id();
		$cacheKey = "hc_searchform_csv_" . $this->webid;
		$t = hc_get($cacheKey);
		if (strlen($t) > 5) { return $t; }

		if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$strHTML = wp_remote_retrieve_body($getReq);

		if (isset($strHTML) && stripos($strHTML, 'Error:') === false && strlen($strHTML) > 12) { 
			$hc_getSearchFieldsCSV = $strHTML;
			//hc_set($cacheKey, $strHTML, 60*60*96);
		}
		//if ($this->debugEnabled) { echo("<!-- *** getSearchFieldsCSV " . (microtime(true) - $start) . " *** -->\n"); }
		return $strHTML;
	}
	function getSearchFieldsJSON() {
		$start = microtime(true);
		global $hc_getSearchFieldsJSON;
		if ($this->debugEnabled && $this->enableCaching == false) { $hc_getSearchFieldsJSON = ""; }
		if ( isset($hc_getSearchFieldsJSON) && strlen($hc_getSearchFieldsJSON) > 5 ) { 
			//if ($this->debugEnabled) { echo("<!-- *** hc_getSearchFieldsJSON " . (microtime(true) - $start) . " GLOBALS *** -->\n"); }
			return $hc_getSearchFieldsJSON; 
		}
		$strHTML = "";
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=GetSearchFieldsJSON&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&WP_UserID=' . get_current_user_id();
		if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		//$cacheKey = "hc_getsearchfieldsjson_" . $this->webid;
		//if ($this->debugEnabled && $this->enableCaching == false) { hc_set($cacheKey); }
		//$t = hc_get($cacheKey);
		//if (strlen($t) > 5) { $strHTML = $t; }

		if (strlen($strHTML) < 1) {
			$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
			$strHTML = wp_remote_retrieve_body($getReq);
			$strHTML = $this->checkResponseHtml($strHTML);
			//if ($this->enableCaching) {hc_set($cacheKey, $strHTML, 60*60*12);}
			$hc_getSearchFieldsJSON = $strHTML;
			//hc_set($cacheKey, $strHTML, 60*60*96);
		}
		//var_dump($strHTML);
		return $strHTML;
	}
	function getDisclaimer() {
		$dbgHtml = '';
		if ($this->enableCaching && strlen(hc_get('hc_disclaimer')) > 20 && stripos(hc_get('hc_disclaimer', ''), 'error:') < 1) {
			if ($this->debugEnabled) {$dbgHtml = "<!-- Disclaimer Loaded from Cache!!! -->\n";}
			return $dbgHtml . hc_get('hc_disclaimer');	
		} else if ( $this->enableCaching == false ) {// let's remove any cached values
			hc_set('hc_disclaimer', null, 1);
		}
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=Disclaimer&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&WP_UserID=' . get_current_user_id();
		if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		$getReq = wp_remote_get($url);
		$strHTML = wp_remote_retrieve_body($getReq);
		/* Note: 60*60*12 = 12 hours */
		if ( $this->enableCaching && strlen($strHTML) > 25 && stripos($strHTML, 'IDX') > 1 ) { hc_set('hc_disclaimer', $strHTML, 10 * 60*60*12); }
		return $this->checkResponseHtml($strHTML);
	}

	function getAccountInfo($login, $pass) {
		global $hc_getAccountInfo;
		if ( isset($hc_getAccountInfo) && strlen($hc_getAccountInfo) > 5 ) { 
			return $hc_getAccountInfo; 
		}
		// Reset the wp_hc_siteurl option ... it might get messed up
		update_option('wp_hc_siteurl', $this->login_domain_name);
		// USE TO GET THE VALUES OUT OF RESPONSE: parse_str($qs, $pairs);
		// IMPORTANT: Now forcing &amp; always pointing to: login_domain_name
		$url = 'http://' . $this->login_domain_name . $this->debugPath . '/HCProxy.aspx';
		if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		$qs = 'Action=GetAccountInfo&Login=' . urlencode($login) . '&mls='.$_SESSION['mls'].'&Pass=' . urlencode($pass) . '&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&Roles=' . urlencode(hc_get_user_roles()) . '&WP_UserID=' . get_current_user_id();
		// USE TO GET THE VALUES OUT OF RESPONSE: parse_str($qs, $pairs);
		//parse_str($qs, $pairs);
		//if ($this->debugEnabled) {echo("<!-- QS: $qs -->\n");}
		//if ($this->debugEnabled) {echo("<!-- pairs: $pairs -->\n");}
		$httpOpts = array('method' => 'POST',
			'body' => $qs,
			'timeout' => 7,
			'blocking' => true);
		$request = new WP_Http;
		$result = $request->request( $url, $httpOpts );
		//return $url;
		// test $result['response'] and if OK do something with $result['body']
		$hc_getAccountInfo = $result['body'];
		return $result['body'];
		/// DONE ////
		/*$getReq = wp_remote_post($url, $httpOpts);
		$strHTML = wp_remote_retrieve_body($getReq);
		return $this->checkResponseHtml($strHTML);*/
	}
	/* NEW */
	function consumerLoginWithToken($email) {
		$this->checkRequestLimiter(10, 'trusted_login');
		/*			NONCE EXAMPLE CODE (should be generated, then passed into these proxy functions to it can be verified before submitting a server request.):
						$nonce = wp_create_nonce("hc-login"); */
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=LeadLoginWithToken&Email=' . urlencode($email) . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&Source=' . $this->hc_source_tag . '&Roles=' . urlencode(hc_get_user_roles()) . '&WP_UserID=' . get_current_user_id();
		//if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$strHTML = wp_remote_retrieve_body($getReq);
		if (stripos($strHTML, 'Error:') === false) {
			$_SESSION['HC_Login'] = $strHTML;
			if (stripos($strHTML, 'Token') > 0) {
				$_SESSION['HC_LeadJSON'] = $strHTML;
				$leadJSON = json_decode($strHTML, true);
				if (isset($leadJSON['Token'])) { $_SESSION['HC_AuthToken'] = $leadJSON['Token']; }
			}
		}
		if ( isset($leadJSON) ) { return $leadJSON; }
		return array('cID' => -1, 'IsValid' => false );
	}
	
	function consumerLogin($email, $password) {
		$this->checkRequestLimiter(30, 'login');
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx';
		$wp_username = 'n/a';
		$qs = 'wID=' . $this->webid . '&WebID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=LeadLogin&Email=' . urlencode($email) . '&Pass=' . urlencode($password) . '&Source=' . $this->hc_source_tag . '&BlogURL=' . urlencode(site_url()) . '&Username=' . urlencode($wp_username) . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&Roles=' . urlencode(hc_get_user_roles()) . '&WP_UserID=' . get_current_user_id();
		// USE TO GET THE VALUES OUT OF RESPONSE: parse_str($qs, $pairs);
		//parse_str($qs, $pairs);
		
		//if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		
		/*$getReq = wp_remote_post($url, $pairs);
		$strHTML = wp_remote_retrieve_body($getReq);*/
		$httpOpts = array('method' => 'POST',
			'body' => $qs,
			'timeout' => 15,
			'blocking' => true);
		$request = new WP_Http;
		$result = $request->request( $url, $httpOpts );
		// test $result['response'] and if OK do something with $result['body']
		$strHTML = $result['body'];
		
		if (stripos($strHTML, 'Error:') === false) {
			$_SESSION['HC_Login'] = $strHTML;
			if (stripos($strHTML, 'Token') > 0) {
				$_SESSION['HC_LeadJSON'] = $strHTML;
				$leadJSON = json_decode($strHTML, true);
				if (isset($leadJSON['Token'])) { $_SESSION['HC_AuthToken'] = $leadJSON['Token']; }
			}
		} else {// We got an error Message
			return array('cID' => -1, 'IsValid' => false, 'Error' => $strHTML );
		}

		if ( isset($leadJSON) ) { 
			// before finishing up, check and add the user meta consumer id
			if ( get_current_user_id() != 0) {
				// We are logged in as a WP user currently!!!
				$current_user = wp_get_current_user();
				if ( isset($leadJSON['cID']) && $leadJSON['cID'] > 1000 ) {
					// Attach the Consumer ID to the current user
					$addedMeta = add_user_meta($current_user->user_id, 'hc_consumerid', $leadJSON['cID'], true);
					if ( !$addedMeta ) { // Try an update instead
						$addedMeta = update_user_meta($current_user->user_id, 'hc_consumerid', $leadJSON['cID']);
					}
				}
			}
			// Now return the Valid server response to the caller
			return $leadJSON;
		}
		return array('cID' => -1, 'IsValid' => false );
		//return $this->checkResponseHtml($strHTML);
	}
	function consumerSignup($name, $phone, $email, $password, $source, $refererURL) {
		$this->checkRequestLimiter(4, 'signup');
		if (empty($source) || strlen($source) < 1) { $source = $this->hc_source_tag; }
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=LeadSignup&SignupName=' . urlencode($name) . '&SignupPhone=' . urlencode($phone) . '&SignupEmail=' . urlencode($email) . '&SignupPassword=' . urlencode($password) . '&Source=' . urlencode($source) . '&RefererURL=' . urlencode($refererURL) . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&Roles=' . urlencode(hc_get_user_roles()) . '&WP_UserID=' . get_current_user_id();
		
		//if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$strHTML = wp_remote_retrieve_body($getReq);
		if (stripos($strHTML, 'Fatal Error:') > -1 ) { return '{"Error": "true", "Message": "Error: Suspicious request detected."}';}
		if (stripos($strHTML, 'Error:') === false) {
			$_SESSION['HC_Login'] = $strHTML;
			if (stripos($strHTML, 'Token') > 0) {
				$leadJSON = json_decode($strHTML, true);
				if (isset($leadJSON['Token'])) { $_SESSION['HC_AuthToken'] = $leadJSON['Token']; }
				
				if (isset($leadJSON['cID']) && intval($leadJSON['cID']) > 1000 ) {
					/******************************************** 
					Add WP User AFTER SUCCESSFUL HOMECARDS SIGNUP 
					*********************************************/
					// Now make sure the validate_username, username_exists & email_exists checks pass
					$isNameSafe = validate_username($email); // << Validate runs sanitize_user and filters >>
					if ( $isNameSafe == 1 && !username_exists($email) && !email_exists($email) ) {
						$first = "";
						$last = "";
						if ( isset( $leadJSON['FirstName'] ) ) { $first = $leadJSON['FirstName']; } 
						if ( isset( $leadJSON['LastName'] ) ) { $last = $leadJSON['LastName']; } 
						$new_user = array('user_login' => $email, 
							'user_pass' => $password,
							'user_email' => $email,
							'display_name' => $first,
							'first_name' => $first,
							'last_name' => $last);
						$new_user_id = wp_insert_user( $new_user );
						if ( is_wp_error($new_user_id) == false ) {
							// Successfully created account !!!
							
							// NOW LET'S LINK THE ACCOUNTS BY SETTING METADATA
							update_user_meta( $new_user_id, 'hc_consumerid', $leadJSON['cID'] );
						} else {
							//TODO: Add error handling!!!!
						}

					}
					
				}
				
			}
		}
		if ( isset($leadJSON) ) { return $leadJSON; }
		return array('cID' => -1, 'IsValid' => false );
	}
	
	function is_crawler() {
   $sites = 'Googlebot|Slurp|msnbot'; // Add the rest of the search-engines 
   return (preg_match("/$sites/", $_SERVER['HTTP_USER_AGENT']) > 0) ? true : false;  
	}
	function checkRequestLimiter($hitLimit = 250, $requestPrefix = '') {
		//// **** Check for GOOGLE BOT, YAHOO! SLURP, ET AL. ****** //////
		if (isset($_SERVER['HTTP_USER_AGENT']) ) {
			$browser = $_SERVER['HTTP_USER_AGENT'];
			if ($this->is_crawler() == true) {/* Can be possibly spoofed, but probably ok as long as server logs requests */
				$hitLimit = ($hitLimit * 20);
			}
		} else {
			$browser = "N/A";
		}
		if (strpos($browser, "Googlebot")) {
			return true;
		} elseif (stripos($browser, "msnbot")) {
			return true;
		} elseif (stripos($browser, "Yahoo! Slurp")) {
			return true;
		}
		
		$key = 'HC_RequestLimiter_' . $requestPrefix . '_' . $_SERVER['REMOTE_ADDR'];
		$hitCountToken = array("0", time() + (60 * 60 * 2));
		if (isset($GLOBALS[$key]) && stripos($GLOBALS[$key], '|') !== false ) {
			$hitCountToken = explode('|', $GLOBALS[$key]);
		}
		$GLOBALS[$key] = implode('|', array((intval($hitCountToken[0]) + 1), $hitCountToken[1]));
		/* BLOCK EXCESSIVE REQUESTING IP ADDRESSES */
		if (intval($hitCountToken[0]) > $hitLimit && (time() <= $hitCountToken[1]) ) {
			echo( '{"Message": "Error: Too many requests from user."}');
			die();
		} else if (time() <= $hitCountToken[1]) { // Just an expired counter... Reset it
			$GLOBALS[$key] = implode('|', array("1", time() + (60 * 60 * 2) ));
		}
	}
	function getCityPage($area, $priceRange, $bedsRange, $bathsRange, $subarea) {
		$dbgHtml = "";
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=GetCityPage&Area=' . urlencode($area) . '&PriceRange=' . urlencode($priceRange) . '&BedsRange=' . urlencode($bedsRange) . '&BathsRange=' . urlencode($bathsRange) . '&Subarea=' . urlencode($subarea) . '&WP_UserID=' . get_current_user_id();
		$cacheKey = "hc_city_" . md5($url, false);

		if ($this->debugEnabled == false) {
			$t = hc_get($cacheKey);
			if (strlen($t) > 5) {
				$dbgHtml .= "<!-- CityPage Loaded from Cache!!! -->\n";
				return $t;
			}
		}
		if ($this->debugEnabled) {$dbgHtml .= "<!-- URL: $url -->\n";}
		$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$strHTML = wp_remote_retrieve_body($getReq);
		if (stripos($strHTML, 'Fatal Error:') === true) { return '{"Error": "true", "Message": "Error: Suspicious request detected."}';}
		hc_set($cacheKey, $this->checkResponseHtml($strHTML), 60*60*12);
		return "$dbgHtml\n" . $this->checkResponseHtml($strHTML);
	}

	//TODO: Make token handling better below
	function getSavedListingsJSON($cID) {
		$dbgHtml = "";
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=GetSavedListingsJSON' . '&Roles=' . urlencode(hc_get_user_roles()) . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token());
		
		if (isset($cID)) { $url .= "&cID=" . $cID;}
		//if ($this->debugEnabled) {$dbgHtml .= "<!-- URL: $url -->\n";}
		$getReq = wp_remote_get($url, array( 'headers' =>$this->hc_getNewHeaders() ) );
		$strHTML = wp_remote_retrieve_body( $getReq );
		if (stripos($strHTML, 'Error:') > -1) { return '{"Error": "true", "Message": "Error: Suspicious request detected."}';}
		
		header( "Content-Type: text/javascript" );
		return "/*$dbgHtml*/\n" . $this->checkResponseHtml($strHTML);
	}
	
	
	function saveProperty($listingId, $cID, $sourceName, $authToken, $notes) {
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=SavePropertyJson&ListingID=' . urlencode($listingId) . '&Source=' . urlencode($sourceName) . '&Roles=' . urlencode(hc_get_user_roles()) . '&WP_UserID=' . get_current_user_id() . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token());
		$postData = array('Notes' => $notes);
		$url .= "&cID=" . urlencode($cID);
		$url .= "&AuthToken=" . urlencode($authToken);
		if ($this->debugEnabled) {echo("/* URL: $url */\n");}
		$getReq = wp_remote_post($url, array('body' => $postData, 'headers' =>$this->hc_getNewHeaders()));
		$strHTML = wp_remote_retrieve_body($getReq);
		if (stripos($strHTML, 'Error:') > -1) { return '{"Error": "true", "Message": "Error: Suspicious request detected."}';}
		
		header( "Content-Type: text/javascript" );
		return $this->checkResponseHtml($strHTML);
	}

	/*
	ADDED BY: DAN L. @ 2012-04-19 9:15PM
AddToEmailLog - THIS FUNC DOES NOT ACTUALLY SEND AN EMAIL - IT LETS YOU TELL THE HOMECARDS DB ABOUT EMAILS SENT TO CONSUMERS/LEADS WHEN USING WP_MAIL OR WHATEVER !!!

*** param list & details:

 $emailData is an Object Array like this:
	{BatchID: "" //OPTIONAL - USE TO 'LINK' or 'TAG' MULTIPLE MESSAGES 
	EmailTo: [Either EmailAddress OR ConsumerID (integer)
	EmailFrom: [Default is SiteEmail from current Agent's token, or You can pass in an override, if required security clearance is met)
	Subject: Email Subject
	MessageLength: integer - should be = strlen(msgBody)
	Status MUST BE EITHER: SUCCESS OR FAIL -- ***** <<<< CRITICAL SETTING - LET'S OUR REPORTS CODE KNOW HOW TO TALLY UP RESULTS
	ConsumerID: The ConsumerID must be able to recieve messages from the current Sending User (Enforced on the HC Proxy)
	SourceName: Arbitrary tag to group logged emails by the part of the Application where it originated
	}
	*/
	function addToEmailLog($emailData) {
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=AddToEmailLog' . '&Roles=' . urlencode(hc_get_user_roles()) . '&WP_UserID=' . get_current_user_id();
		if ( isset($this->token) ) { $url .= "&AuthToken=" . urlencode($this->token); }
		if ( isset($_REQUEST['LeadToken']) ) { $url .= "&LeadToken=" . urlencode($_REQUEST['LeadToken']); }
		$getReq = wp_remote_post( $url, array('body' => $emailData, 'headers' =>$this->hc_getNewHeaders()) );
		$strHTML = wp_remote_retrieve_body($getReq);
		if (stripos($strHTML, 'Error:') > -1) { return '{"Error": "true", "Message": "Error: Suspicious request detected."}';}
		
		header( "Content-Type: text/javascript" );
		return $this->checkResponseHtml($strHTML);
	}

/*
ADDED BY: DAN L. @ 2012-04-19 9:15PM
Appointments
Bookmark
Charts
FeaturedListings
HomePage
ListingInfo
ListingInfo-Mobile
LoanApp
LoginVerify_Success
MapSearch
MarketData
PropertyAlerts
RequestCMA
Search
SearchResults
ShowPage
Signup
eventType Must be One of these options: Appointments, Bookmark, Charts, FeaturedListings, HomePage, ListingInfo, ListingInfo-Mobile, LoanApp, LoginVerify_Success, MapSearch, MarketData, PropertyAlerts, RequestCMA, Search, SearchResults, ShowPage, Signup
ASPX's POST Fields: eventType, subType, referrerUrl, url, remoteIP, remoteHost, remoteUserAgent, customSessionID
*/
	function logHomeCardsEvent($eventType, $subtype, $log_url, $remoteIp, $remoteHost, $listingId = '') {
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=LogActivity' . '&Roles=' . urlencode(hc_get_user_roles()) . '&WP_UserID=' . get_current_user_id();
		if ( isset($this->token) ) { $url .= "&AuthToken=" . urlencode($this->token); }
		if ( isset($_REQUEST['LeadToken']) ) { $url .= "&LeadToken=" . urlencode($_REQUEST['LeadToken']); }
		$getReq = wp_remote_post( $url, array('body' => array(
			'eventType' => $eventType, 
			'subType' => $subtype,
			'referrerUrl' => $url/*<< this is the HCProxy SERVICE URL, & NOT real referer */,
			'url' => $log_url/*<< this is the URL specified by the client code (probably just current url) */,
			'remoteIP' => $remoteIp,
			'remoteHost' => $remoteHost,
			'remoteUserAgent' => $_SERVER['HTTP_USER_AGENT'],
			'customSessionID' => session_id()),
		'headers' => $this->hc_getNewHeaders()) );
		$strHTML = wp_remote_retrieve_body($getReq);
		if (stripos($strHTML, 'Error:') > -1) { return '{"Error": "true", "Message": "Error: Suspicious request detected."}';}
		
		header( "Content-Type: text/javascript" );
		return $this->checkResponseHtml($strHTML);
	}

	/* getAgentJSON */
	function getWebAgent() {
		$agInfo = hc_checkSession();
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=GetWebAgent&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&Roles=' . urlencode(hc_get_user_roles()) . '&WP_UserID=' . get_current_user_id();
		if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$strHTML = wp_remote_retrieve_body($getReq);
		return $this->checkResponseHtml($strHTML);
	}
	/* getAgentFields Gets CSV of Available Fields */
	function getAgentFields() {
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=GetAgentFields&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&Roles=' . urlencode(hc_get_user_roles()) . '&WP_UserID=' . get_current_user_id();
		if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$strHTML = wp_remote_retrieve_body($getReq);
		return $this->checkResponseHtml($strHTML);
	}
	function getLocalStatsByRadius() {
		$radius = '1'; /* Should now be in miles - Dan: 2012-04-01 */
		if ( isset($_REQUEST['radius']) ) {$radius = $_REQUEST['radius'];}
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=GetLocalStats&Lat=' . urlencode($_REQUEST['lat']) . '&Lon=' . urlencode($_REQUEST['lon']) . '&Radius=' . $radius . '&OutputMode=HTML&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&Roles=' . urlencode(hc_get_user_roles()) . '&WP_UserID=' . get_current_user_id();
		if ($this->debugEnabled) {echo("<!-- URL: $url -->\n");}
		$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$strHTML = wp_remote_retrieve_body($getReq);
		return $this->checkResponseHtml($strHTML);
	}
	
	/* UpdatedJSON */
	function updateAccountInfo($json) {
		/* TODO: Add security here */
		$url = 'http://' . $this->hc_domain_name . $this->debugPath . '/HCProxy.aspx?wID=' . $this->webid . '&mls='.$_SESSION['mls'].'&Action=UpdateAccountInfo&Source=' . $this->hc_source_tag . '&SiteToken=' . urlencode($this->hc_get_site_token()) . '&LeadToken=' . urlencode($this->hc_get_lead_token()) . '&Roles=' . urlencode(hc_get_user_roles()) . '&WP_UserID=' . get_current_user_id();// . '&JSON=' . urlencode($json);
		if ( isset( $_REQUEST['hc_custom_signup_html']) ) { add_option('hc_signup_html', $_REQUEST['hc_custom_signup_html']);}
		//if ($this->debugEnabled) {echo("<!-- URL: $url -->\n"); echo("<!-- JSON: $json -->\n");}
		//$getReq = wp_remote_get($url, array('headers' =>$this->hc_getNewHeaders() ) );
		$getReq = wp_remote_post($url, array(
					'body' => array('JSON' => $json, 'Roles' => hc_get_user_roles() ),
					'headers' =>$this->hc_getNewHeaders() ));
		$strHTML = wp_remote_retrieve_body($getReq);
		ob_clean();
		return $this->checkResponseHtml($strHTML);
	}


	function hc_getNewHeaders() {
		return array('X-Forwarded-For' => $_SERVER['REMOTE_ADDR'],
								'X-Forwarded-WP-Roles' => hc_get_user_roles(),
								'X-Forwarded-USER-AGENT' => $_SERVER['HTTP_USER_AGENT'],
								'X-FORWARDED-URL' => $_SERVER["REQUEST_URI"] );
	}
	function hc_get_site_token() {
		if ( isset($this->token) ) { return $this->token; }
		if ( isset($_SESSION['HC_SiteToken']) ) { return $_SESSION['HC_SiteToken']; }
		if ( isset($GLOBALS['HC_SiteToken']) ) { return $GLOBALS['HC_SiteToken']; }
		return "";
	}
	function hc_get_lead_token() {
		if (isset($_SESSION['HC_Login'])) {
			$leadJSON = json_decode($_SESSION['HC_Login'], true);
		} else if (isset($_SESSION['HC_LeadJSON'])) {
			$leadJSON = json_decode($_SESSION['HC_LeadJSON'], true);
		} else {
			$leadJSON = json_decode("{\"Token\":\"\"}", true);
		}
		if ( isset($leadJSON) && isset($leadJSON['Token']) ) { return $leadJSON['Token'] . '|'; }

		return "";
	}

}


if (!function_exists('getQrCodeImageUrl')) {
/* New QR Code Helper Function */
function getQrCodeImageUrl($url, $sizeID, $siteToken) {
	if (empty($sizeID)) { $sizeID = 10; }
	if (empty($siteToken)) {$siteToken = "";}
	if (empty($url)) { return "";}
	if (intval($sizeID) > 40) { $sizeID = 40; }
	if (intval($sizeID) <  2) { $sizeID = 2; }
	return "http://www.qrleads4me.com/GetQRCode.aspx?Scale=" . $sizeID . "&URL=" . urlencode($url) . "&SiteToken=" . urlencode($siteToken);
}
}







/***** EXAMPLES *****

$hc_proxy = new HomeCardsProxy('tom.myhomecards.com', 10001);
echo $hc_proxy->doSearch(8, 'Area=%3CALL%3E&SearchName=&AlertButtons=&Email=&available=%3CALL%3E&AREABOX=USECITY&TYPE=RES&Status=A,P&PriceFrom=100000&PriceTo=99350000&Beds=%3E%3D2&Baths=%3E%3D2&SortResults=Price.Desc&Button0.x=48&Button0.y=6&hsnfrom=&hsnto=&str=&MLSRemarks=&zip=&subarea=&mcp=&grid=&CarStorage=&CarSpaces=0&SqFt=&acs=&YearBuilt=&LN=&daysback=0&Warn=False');
echo $hc_proxy->getFeaturedListings(8);
*/
