<?php
/*!
Plugin Name: HomeCards IDX Plugin
Plugin URI: http://www.hillsidesoftware.com/?Src=HC-Plugin
Description: WORDPRESS PLUGIN FOR HOMECARDS REAL ESTATE AGENTS!
Version: 1.6.2
Author: Hillside Software, Inc.
Author URI: http://www.hillsidesoftware.com
*/



/**** ENABLE FOR ERROR HANDLING 
ini_set('display_errors',1);
error_reporting(E_ALL);
//ini_set('display_errors',1);
//error_reporting(E_ALL);
*/
error_reporting(E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_ERROR|E_CORE_ERROR);

//ini_set('display_errors',1);
//error_reporting(E_ALL);

/**
*** IMPORTANT SETTING ***
	HC_CACHE_MODE Available Settings:
		globals: Uses php GLOBALS - IF GLOBALS IS SETUP FOR SHARED STATE ACROSS ALL HTTP REQUESTS (NOT DEFAULT/STANDARD), THIS MAY BE FASTEST ONLY FOR SCRIPT-REQUEST-DURATION SCOPE)
		transient: Uses WordPress get/set_transient - SUPPORTS EXPIRATION TIMEOUT
		file: Stores data in php's temp file directory - no timeout support
		none: disable caching - only use in development - *VERY* SLOW
**/
define("HC_CACHE_MODE", "transient");
// Note: Move this stuff later....
define("HC_BLEEDING_EDGE", true);
define('PLUGIN_ERROR_HTML', "<div class='hc-error'><strong>We're Sorry!</strong> The requested search form could not be loaded at the moment. Please try again later or verify your <a href='/wp-admin/admin.php?page=homecards-plugin'>plugin configuration</a>.</div>");



if (!isset($_SESSION)) { @session_start(); }
// Init required session value
if (empty($_SESSION['mls'])) { $_SESSION['mls'] = ''; }


@ob_start(); //output buffering to buffer any headers

@header('X-UA-Compatible: IE=Edge');


if(extension_loaded("zlib") && (ini_get("output_handler") != "ob_gzhandler")) {
	add_action('wp', create_function('', '@ob_end_clean();@ini_set("zlib.output_compression", 1);'));
}

if( !class_exists( 'WP_Http' ) ) { include_once( ABSPATH . WPINC . '/class-http.php' ); }

require_once( dirname(__FILE__) . '/homecards-proxy.php' );
require_once( dirname(__FILE__) . '/homecards-shortcodes.php' );
require_once( dirname(__FILE__) . '/homecards-styles.php' );
require_once( dirname(__FILE__) . '/homecards-settings.php' );
require_once( dirname(__FILE__) . '/homecards-rewrite.php' );
require_once( dirname(__FILE__) . '/homecards-localcache.php' );
require_once( dirname(__FILE__) . '/homecards-propertydetails.php' );
require_once( dirname(__FILE__) . '/homecards-users.php' );
include_once( dirname(__FILE__) . '/settings/shortcodes.php' );
include_once( dirname(__FILE__) . '/homecards-widgets.php' );
include_once( dirname(__FILE__) . '/short-codes-2.php');


class HomeCardsPlugin {
	
	static function install_plugin() {
		/* Setup URL Rewriting */
		if (!defined('HC_URL_REWRITE_ADDED')) {
			hc_add_url_rewrite();
		}
		hc_flushRules();
		
		/* install options */
		$searchFields = "Area,Beds,Baths,PriceFrom,PriceTo"; /* Default/Required Fields */
		if (get_option('wp_hc_search_form1', 'NULL') == 'NULL') { add_option('wp_hc_search_form1', $searchFields, '', 'no'); }
		if (get_option('wp_hc_webid', 'NULL') == 'NULL') { add_option('wp_hc_webid', -1); }
		if (get_option('wp_hc_token', 'NULL') == 'NULL') { add_option('wp_hc_token', ""); }
		if (get_option('wp_hc_siteurl', 'NULL') == 'NULL') { add_option('wp_hc_siteurl', ""); }
		if (get_option('hc_searchresultslimit', 'NULL') == 'NULL') { add_option('hc_searchresultslimit', '25'); }
		if (get_option('hc_disablecss', 'NULL') == 'NULL') { add_option('hc_disablecss', '0'); }
		if (get_option('wp_hc_details_url_prefix', 'NULL') == 'NULL') { add_option('wp_hc_details_url_prefix', "property-details"); }

		HomeCardsPlugin::SetUserRoles();
	}
	
	private static function SetUserRoles() {
		$role = add_role('hc_lead', 'HomeCards User', array(
		    'read' => true, // True allows that capability
		    'edit_posts' => false,
		    'delete_posts' => false, // Use false to explicitly deny
		));	
		
		return $role;
	}

	public function uninstall_plugin() {
		/* Delete obsolete options and files */
		$opts = split(',', 'wp_hc_search_form1,wp_hc_webid,wp_hc_token,wp_hc_siteurl,hc_searchresultslimit,hc_disablecss,wp_hc_details_url_prefix');
		for ($i = 0; $i < $opts.length; $i++) {
			delete_option($opts[$i]);
		}
	}
}


register_activation_hook( __FILE__, array('HomeCardsPlugin', 'install_plugin') );

add_filter('the_content', 'hc_content_filter');
add_filter('http_request_timeout', 'hc_get_http_timeout');
function hc_get_http_timeout($timeout) {
	return 12;
}

add_filter( 'default_content', 'hc_check_default_page_content' );
	function hc_check_default_page_content( $content ) {
		if (isset($_SESSION['hc_page_content'])) {
			$content = stripslashes($_SESSION['hc_page_content']);
		}
		unset($_SESSION['hc_page_content']);
		return $content;
	}
	function hc_set_default_page_content( $new_content ) {
		$_SESSION['hc_page_content'] = $new_content;
	}


if (function_exists('is_admin') && is_admin()) {
	add_action('admin_menu', 'hc_admin_menu_action');
	function hc_admin_menu_action() {
		add_menu_page('Account Settings', 'HomeCards', 'administrator', 'homecards-plugin', 'hc_render_settings_form', '', 4);		
		//add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position 
	}
	if (intval(get_option('wp_hc_webid', '0')) > 1000) { 
		add_action('admin_menu', 'register_shortcodes_settings');
		function register_shortcodes_settings() {
			add_submenu_page( 'homecards-plugin', 'Shortcode Creator', 'Shortcode Tools', 'administrator', 'shortcodes-settings', 'shortcodes_submenu_cb' ); 
			add_submenu_page( 'homecards-plugin', 'Search Designer', 'Search Designer', 'administrator', 'search-designer', 'hc_render_search_designer' ); 
			add_submenu_page( 'homecards-plugin', 'Search & Property Tools', 'Search & Property Tools', 'administrator', 'canned-search', 'hc_canned_search_wizard' ); 
			// $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function
		}
	}
}
function hc_ajax_lead_logout() {
	header( "Content-Type: text/javascript" );
	$_SESSION['HC_Login'] = null;
	$_SESSION['HC_LeadJSON'] = null;
	wp_logout(); /// This kills the WP session !!!
	ob_clean();
	echo '{"Message":"Logged out!"}';
	exit();
}
///DISABLED/UNTESTED - 2012-02-20: add_action('wp_login', 'hc_wp_user_login');

// if both logged in and not logged in users can send this AJAX request,
// add both of these actions, otherwise add only the appropriate one
/*FOR ANONYMOUS USERS: add_action( 'wp_ajax_nopriv_hc-lead-login', 'hc_ajax_USER_login' );*/
add_action( 'wp_ajax_hc_agent_login', 'hc_ajax_agent_login' );
add_action( 'wp_ajax_nopriv_hc_agent_login', 'hc_ajax_agent_login' );

add_action( 'wp_ajax_hc_login', 'hc_ajax_lead_login' );
add_action( 'wp_ajax_nopriv_hc_login', 'hc_ajax_lead_login' );

add_action( 'wp_ajax_hc_logout', 'hc_ajax_lead_logout' );
add_action( 'wp_ajax_nopriv_hc_logout', 'hc_ajax_lead_logout' );

add_action( 'wp_ajax_nopriv_hc_lead_save_listing', 'hc_ajax_lead_save_listing' );
add_action( 'wp_ajax_hc_lead_save_listing', 'hc_ajax_lead_save_listing' );

add_action( 'wp_ajax_nopriv_hc_get_lead_saved_listings', 'hc_ajax_get_lead_saved_listings' ); // << SEE THE hc_get_lead_saved_listings FUNCTION IN homecards-proxy.php 
add_action( 'wp_ajax_hc_get_lead_saved_listings', 'hc_ajax_get_lead_saved_listings' ); // << SEE THE hc_get_lead_saved_listings FUNCTION IN homecards-proxy.php 

add_action( 'wp_ajax_hc_signup', 'hc_ajax_signup' );
add_action( 'wp_ajax_nopriv_hc_signup', 'hc_ajax_signup' );

add_action( 'wp_ajax_nopriv_hc_get_fields', 'hc_get_fields' );
add_action( 'wp_ajax_hc_get_fields', 				'hc_get_fields' );
add_action( 'wp_ajax_hc_get_fields_html', 			 'hc_get_fields_html' );
add_action( 'wp_ajax_nopriv_hc_get_fields_html', 'hc_get_fields_html' );


add_action( 'wp_ajax_hc_get_featured_listings_json', 			  'hc_getFeaturedListingsJson' );
add_action( 'wp_ajax_nopriv_hc_get_featured_listings_json', 'hc_getFeaturedListingsJson' );

add_action( 			 'wp_ajax_hc_get_account_json', 'hc_getAccountJson' );
add_action( 'wp_ajax_nopriv_hc_get_account_json', 'hc_getAccountJson' );

// Awesome Code!
add_action( 'wp_ajax_nopriv_hc_get_html_tile', 'hc_get_html_tile' );
add_action( 			 'wp_ajax_hc_get_html_tile', 'hc_get_html_tile' );

add_action( 'wp_ajax_nopriv_hc_get_search_count', 'hc_get_search_count' );
add_action( 			 'wp_ajax_hc_get_search_count', 'hc_get_search_count' );
add_action( 'wp_ajax_nopriv_hc_get_search_results', 'hc_get_search_results' );
add_action( 			 'wp_ajax_hc_get_search_results', 'hc_get_search_results' );
// Are these 2 lines needed?
//add_action( 'wp_ajax_nopriv_Search', 'hc_get_search_results' );
//add_action( 			 'wp_ajax_Search', 'hc_get_search_results' );
add_action( 'wp_ajax_nopriv_hc_get_map_search_json', 'hc_get_map_search_json' );
add_action( 			 'wp_ajax_hc_get_map_search_json', 'hc_get_map_search_json' );


add_action( 'wp_ajax_nopriv_hc_get_search_stats', 'hc_get_search_stats' );
add_action( 			 'wp_ajax_hc_get_search_stats', 'hc_get_search_stats' );

add_action( 'wp_ajax_hc_json_updates', 'hc_doAgentUpdate' );

add_action( 'wp_ajax_hc_ajax_set_default_content', 'hc_ajax_set_default_content' );


add_action( 'wp_ajax_hc_add_viewed_listing', 'hc_add_viewed_listing' );
add_action( 'wp_ajax_nopriv_hc_add_viewed_listing', 'hc_add_viewed_listing' );

add_action( 'wp_ajax_hc_mls_selection', 'hc_mls_selection' );
add_action( 'wp_ajax_nopriv_hc_mls_selection', 'hc_mls_selection' );

add_action( 'wp_ajax_hc_save_default_criteria', 'hc_save_default_criteria' );

add_action( 'wp_ajax_homecardsevent', 'hc_log_homecardsevent' );


function hc_mls_selection() {
	/*
		$json =  json_encode(array(""=>"Denver & Central CO","IRE"=>"Boulder")); 
		add_option("hc_available_mls", $json); 
		echo $json . "\r\n";
	*/
	$action = strtolower($_REQUEST['mode']); 
	if($action == "available") {
		$json = get_option("hc_available_mls", ""); 
		if(strlen($json) < 1) {
			// TODO: Add API CALL 
			$json =  json_encode(array(
				"selected"=> $_SESSION['mls'],
				"available" => array(""=>"Denver & Central CO","IRE"=>"Boulder")));
			update_option("hc_available_mls", $json); 
		}
		echo $json . "\r\n";
	} else if ($action == "set") {
		$newMLS = $_REQUEST['hc_mls'];
		if(strlen($newMLS) < 5 && strlen($newMLS) >= 0) {
			$_SESSION['mls'] = $newMLS;
			$json =  json_encode(array(
				"selected"=> $_SESSION['mls'],
				"available" => array(""=>"Denver & Central CO","IRE"=>"Boulder")));
			echo $json . "\r\n";
		}
	}
	exit();
}

/*
Tracks activity using the HC logging and reporting system!
*/
function hc_log_homecardsevent() {
	$hc_proxy = new HomeCardsProxy(false);
	//Form Fields: eventType, subType, listingID, referrerUrl, url, remoteIP, remoteHost, remoteUserAgent, customSessionID
	if ( isset($_REQUEST['listingId']) ) {
		$listingId = $_REQUEST['listingId'];
	} else {
		$listingId = '';
	}
	$hc_proxy->logHomeCardsEvent($_REQUEST['eventType'], $_REQUEST['subType'], $_REQUEST['url'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_HOST'], $listingId);
}

function hc_save_default_criteria() {
	@header( "Content-Type: text/html" );
	if ( isset($_REQUEST['query']) ) { update_option('hc_default_query', $_REQUEST['query']); }
	echo '{"message":"Done"}';
	exit();
}

function hc_add_viewed_listing() {
	@header( "Content-Type: text/html" );
	// TODO: Might need to add 'HomeCardsLog Support' code here!
	
	if ( empty($_SESSION['viewedListings']) ) { $_SESSION['viewedListings'] = ''; }
	$viewedListingsCSV = $_SESSION['viewedListings'] + "";
	$listingId = $_REQUEST['listingId'];
	if ( empty($_REQUEST['listingId']) && isset($_REQUEST['listingid']) ) { $listingId = $_REQUEST['listingid']; }
	
	if ( isset($listingId) && strlen($listingId) > 5 && strlen($listingId) < 32 ) {
		// we have a valid listing #... let's check if it has been viewed or not
		if ( stripos($viewedListingsCSV, $listingId) == false ) {
			// now we can update the session value
			$_SESSION['viewedListings'] .= ',' . $listingId ;
			echo '{"message":"Done"}';
		} else {
			echo '{"message":"N/A"}';
		}
	} else {
		echo '{"message":"No listingId!"}';
	}
	exit();
}
/// This is an ajax handler !!
function hc_get_local_stats() {
	// note: this function should be sent a Radius & Lat & Lon in order to do proximity-based stats calculations
	$hc_proxy = new HomeCardsProxy(false);
	$response = $hc_proxy->getLocalStats(); /* The variables/parameters are pulled inside getLocalStats()*/
	@header( "Content-Type: text/html" );
	ob_clean();
	echo $response;
	exit();
}
//()
function hc_get_map_search_json() {
	$hc_proxy = new HomeCardsProxy(false);
	$response = $hc_proxy->getSearchMapJSON(); /* The variables/parameters are pulled like doSearch()*/
	@header("Content-Type: text/javascript");
	ob_clean();
	echo $response;
	exit();
}
function hc_get_search_stats() {
	$hc_proxy = new HomeCardsProxy(false);
	$response = $hc_proxy->getSearchStats(); /* The variables/parameters are pulled like doSearch()*/
	@header("Content-Type: text/javascript");
	ob_clean();
	echo $response;
	exit();
}
function hc_get_html_tile() {
	$contentName = strtolower($_REQUEST['name']);
	$html = "";

	if ($contentName == 'login') { $html .= hc_buildLoginForm(true); }
	if ($contentName == 'signup') { $html .= hc_renderSignupHtml() . "\n" . hc_buildLeadSignupForm(true); }
	if ($contentName == 'loginandsignup') { 
		/*$html .= "	<div class='hc-signup-window-toggle'>\n";
		$html .= "		<input type='button' id='btnNewUser' value='New User? Signup Now' />\n";
		$html .= "		<input type='button' id='btnOldUser' value='Returning Visitor? Login Here' />\n";
		$html .= "	</div>\n";*/
		//$html .= "<div id=\"hc-colorbox-close\" style=\"width: 98.5%; position: absolute; z-index: 999999;\" align=\"right\"><div style=\"float: right;\"><span class=\"hc_iconic hc_x hc-font-icon\"></span>Close</div></div>\n";
		$html .= "
		<ul class='hc-tabs'>
	    <li><a href='#hc_signup_box'>Free Signup</a></li>
	    <li><a href='#hc_login_box'>Returning User?</a></li>
	  </ul>\n";
		/// Bad UI $html .= "<div onclick='jQuery.colorbox.close()' class=\"hc-colorbox-close\" style=\"width: 75%; float: right; position: absolute; right: 5px; z-index: 999999;\" align=\"right\"><div style=\"float: right; text-decoration: underline;\"><span class=\"iconic x hc-font-icon\"></span>Close</div></div>\n";
		$html .= "<div class=\"hc-colorbox-close\" style=\"width: 75%; float: right; position: absolute; right: 5px; z-index: 999999;\" align=\"right\"><div style=\"float: right; text-decoration: underline;\"><span class=\"iconic x hc-font-icon\"></span>Close</div></div>\n";
		$html .= "	<div id='hc_signup_box' class='hc-tab-content hc-signup-box'>\n";
		$html .= 		hc_renderSignupHtml() . "\n";
		$html .= 		hc_buildLeadSignupForm(true) . "\n";
		$html .= "	</div>\n";
		$html .= "	<div id='hc_login_box' class='hc-tab-content hc-login-box'>\n";
		$html .= 		hc_buildLoginForm(true) . "\n<br />\n";
		$html .= "	</div>\n";
	}
	$html = str_replace('hc_signup_form', 'hc_signup_form_ajax', $html);
	ob_clean();
	echo json_encode(array( 'html' => $html, 'contentName' => $contentName ));
	exit();
}
function hc_addActionForScripts() {
		//Tipsy Tool-Tip Library
		wp_enqueue_script( 'tipsy', plugin_dir_url( __FILE__ ) . 'js/jquery.tipsy.min.js', array( 'jquery' ) );
		//wp_enqueue_style( 'tipsy', plugin_dir_url( __FILE__ ) . 'css/tipsy.css' );
		//Happy.js Library
		wp_enqueue_script( 'happy', plugin_dir_url( __FILE__ ) . 'js/happy.js', array( 'jquery' ) );
		wp_enqueue_script( 'happy-methods', plugin_dir_url( __FILE__ ) . 'js/happy.methods.js', array( 'jquery' ) );
		//wp_enqueue_style( 'happy', plugin_dir_url( __FILE__ ) . 'css/happy.css' );
		wp_enqueue_style('components', plugin_dir_url( __FILE__ ) . 'css/components.css');
}
add_action ( 'admin_head', 'hc_addActionForScripts');


// This function lets you get just the SQL COUNT(*) of the given query (see the regular )
function hc_get_search_count() {
	$hc_proxy = new HomeCardsProxy(false);
	$response = $hc_proxy->getSearchCount(); /* The variables/parameters are pulled like doSearch()*/
	@header( "Content-Type: text/html" );
	ob_clean();
	echo $response;
	exit();
}

/*
function hc_hideDisclaimer() {
?>
<style type="text/css">
#hcLegalToggle {font-size: 1.1em; font-weight: 700; display: block; clear: both;}
</style>
<script type="text/javascript">
jQuery(document).ready( function($) {
$('.hc-legal:eq(0)')
.before('<a href="javascript:jQuery(\'.hc-legal\').toggle()" id=\'hcLegalToggle\'>Click Here For MLS Disclaimer</a>')
.hide();
});
</script>

<?php
} */


//add_action( 'wp_head', 'hc_hideDisclaimer' );

//add_filter( 'the_content', 'hc_hideDisclaimer' );

function hc_hideDisclaimer($content) {
     global $post;
     if ($post) {
          
          $content .= '<br><a href="javascript:jQuery(\'.hc-legal\').toggle()" id=\'hcLegalToggle\'>Click Here For MLS Disclaimer</a>';
          $content .= hc_output_disclaimer();
          }
     return $content;
     }

// WARNING: ADMIN ONLY FUNCTION
function hc_doAgentUpdate() {
	header( "Content-Type: text/javascript" );
	$respJson = "";
	$json = $_REQUEST['JSON'];
	$json = str_replace('\"', "\"", $json);
	if ( isset($json) ) { $respJson = hc_update_agent($json); }
	echo $respJson;
	exit;
}

function hc_ajax_set_default_content() {
	$shortcode = $_REQUEST['shortcode'];
	hc_set_default_page_content($shortcode);
	echo '{"message": "Success"}';
	exit;
}

//function hc_admin_menu() {
//	add_submenu_page('plugins.php', 'HomeCards Settings', 'HomeCards Settings', 'manage_options', basename(__FILE__), 'hc_settings');
//}
function hc_get_wp_info() {
	return "<!--	\nis_single: $is_single<br />\nis_page: $is_page<br />\nis_archive: $is_archive<br />\nis_preview: $is_preview<br />\nis_date: $is_date<br />\nis_year: $is_year<br />\nis_month: $is_month<br />\nis_time: $is_time<br />\nis_author: $is_author<br />\nis_category: $is_category<br />\nis_tag: $is_tag<br />\nis_tax: $is_tax<br />\nis_search: $is_search<br />\nis_feed: $is_feed<br />\nis_comment_feed: $is_comment_feed<br />\nis_trackback: $is_trackback<br />\nis_home: $is_home<br />\nis_404: $is_404<br />\nis_comments_popup: $is_comments_popup<br />\nis_admin: $is_admin<br />\nis_attachment: $is_attachment<br />\nis_singular: $is_singular<br />\nis_robots: $is_robots<br />\nis_posts_page: $is_posts_page<br />\nis_paged: $is_paged	\n-->";
}

function hc_checkSession() {
	if (strlen(get_option('wp_hc_agentdata', '')) > 0) {
		$siteOpts = get_option('wp_hc_agentdata');
		parse_str($siteOpts, $agentInfoArr);
		if (!isset($_SESSION['HC_SiteToken']) || strlen($_SESSION['HC_SiteToken']) < 5 ) {
			if (isset($agentInfoArr['Token']) && strlen($agentInfoArr['Token']) > 1 ) { 
				$_SESSION['HC_SiteToken'] = $agentInfoArr['Token'];
				if ( strlen(get_option('wp_hc_token', '')) < 1 ) { update_option('wp_hc_token', $_SESSION['HC_SiteToken']); }
			}
		}
	}
	if ( isset($agentInfoArr) ) {return $agentInfoArr;}
}



function hc_wp_user_login($name) {
 global $current_user;// $current_user->user_login, $current_user->user_pass, $current_user->user_registered, $current_user->user_email, $current_user->user_firstname, $current_user->user_lastname, $current_user->display_name, $current_user->ID
 get_currentuserinfo();
	/* Validate Data */	
	$email = $current_user->user_email;
	if (strlen($email) > 5) {
		/* Submit Email & Password Hash to relay HC Proxy */
		$hc_proxy = new HomeCardsProxy(false);
		// DON'T: Get MD5 of HC Token CONCATENATED with the user_email
		$authToken = get_option('wp_hc_token', '');
		$html = $hc_proxy->consumerLoginWithToken($email, $authToken);

		if (stripos($html, 'Error') <= -1 && strlen($html) > 3) {
			/* Store SESSION value if valid data detected */
			$_SESSION['HC_Login'] = trim($html);
			if (stripos($html, 'Token') > 0) {
				$leadJSON = json_decode($html);
				if (isset($leadJSON['FirstName'])) { $leadFirstName = $leadJSON['FirstName']; }
			} else {
				$hc_errMsg = "Your Email and Password were rejected, please try again.";
			}
		} else {
			$hc_errMsg = "Could not login with your Email and Password. Please try again.";
		}
		
		if (isset($hc_errMsg)) { die($hc_errMsg);}
		ob_clean();
		echo trim(trim(trim($html)));
		exit();

	}
}
/*$json_array;*/

function hc_parseQueryString($str) { 
  $op = array();
  $pairs = explode("&", $str);
  foreach ($pairs as $pair) {
    list($k, $v) = array_map("urldecode", explode("=", $pair));
    $op[$k] = $v;
  }
  return $op;
}

/*
$user_id = username_exists( $user_name );
if ( !$user_id ) {
	$random_password = wp_generate_password( 12, false );
	$user_id = wp_create_user( $user_name, $random_password, $user_email );
} else {
	$random_password = __('User already exists.  Password inherited.');
}
*/

/// The global vars below speed things up considerably... we might need to add more global cache vars
function hc_reset_global_variable_cache() {
	global $hc_getSearchFormHtmlAndJson, $hc_getSearchFieldsCSV, $hc_getSearchFieldsJSON, $hc_getAccountInfo;
	$hc_getSearchFormHtmlAndJson = ''; $hc_getSearchFieldsCSV = ''; $hc_getSearchFieldsJSON = ''; $hc_getAccountInfo = '';
}

function hc_ajax_agent_login() {
	if (isset($_POST['login']) === true) { $login = $_POST['login']; }
	if (isset($_POST['pass']) === true) { $pass = $_POST['pass']; }
	
	$hc_proxy = new HomeCardsProxy(false);
	$qsResults = $hc_proxy->getAccountInfo($login, $pass);
	/* Reset/Clear Global Values */
	hc_reset_global_variable_cache();
	
	if (stripos($qsResults, 'WebID') !== false) {
		/* Update if valid data detected */
		if (strlen(get_option('wp_hc_agentdata', '')) > 0) {
			delete_option('wp_hc_agentdata');
		}
		add_option('wp_hc_agentdata', $qsResults, '', 'yes');
	}

	parse_str($qsResults, $agentInfoArr);
	//$json_array = json_encode($json);
	//print_r($json_array);
	//header( "Content-Type: text/plain" );
	$wID = 0;
	$hcLink = '';
	/*echo '$qsResults: ' . $qsResults;
	//print_r($agentInfoArr);
	die();*/
	
	if (isset($agentInfoArr['Token'])) { $_SESSION['HC_SiteToken'] = $agentInfoArr['Token'];}
	if (isset($agentInfoArr['WebID'])) { $wID = intval($agentInfoArr['WebID']);}
	if (isset($agentInfoArr['DefaultDomain'])) { $hcLink = $agentInfoArr['DefaultDomain'];}
	/* if valid login, then set the server's options */
	if ($wID > 1000) {
		hc_set_option('wp_hc_webid', $wID, 'yes');
		hc_set_option('wp_hc_siteurl', $hcLink, 'yes');
		hc_set_option('wp_hc_token', $_SESSION['HC_SiteToken'] . "", 'yes');
	}
	echo trim(trim(trim($qsResults)));
	exit();
}

function hc_set_option($option_name, $newvalue, $autoload = 'on') {
	if ( get_option( $option_name ) != $newvalue ) {
		update_option( $option_name, $newvalue );
	} else {
		add_option( $option_name, $newvalue, '', $autoload );
	}
	return $newvalue;
}
function hc_settings() {
	//MOVED BACK TO TOP require_once( dirname(__FILE__) . '/homecards-settings.php' );

	$userMsg = "";
	/* Check for submit POST !!! */
	if (isset($_POST['hc_login']) && strlen($_POST['hc_login']) > 1) {
		if (isset($_POST['hc_login']) === true) { $login = $_POST['hc_login']; }
		if (isset($_POST['hc_pass']) === true) { $pass = $_POST['hc_pass']; }

		$hc_proxy = new HomeCardsProxy();
		$qsResults = $hc_proxy->getAccountInfo($login, $pass);

		if (stripos($qsResults, 'WebID') >= 0) {
			/* Update if valid data detected */
			if (strlen(get_option('wp_hc_agentdata', '')) > 0) {
				delete_option('wp_hc_agentdata');
			}
			add_option('wp_hc_agentdata', $qsResults, '', 'yes');
		}
	
		parse_str($qsResults, $agentInfoArr);
		//$json_array = json_encode($json);
		//print_r($json_array);
		//header( "Content-Type: text/plain" );
		$wID = 0;
		$hcLink = '';
		//print_r($agentInfoArr);
		
		if (isset($agentInfoArr['Token'])) { $_SESSION['HC_SiteToken'] = $agentInfoArr['Token'];}
		if (isset($agentInfoArr['WebID'])) { $wID = intval($agentInfoArr['WebID']);}
		if (isset($agentInfoArr['DefaultDomain'])) { $hcLink = $agentInfoArr['DefaultDomain'];}
		/* if valid login, then set the server's options */
		if ($wID > 1000) {
			hc_set_option('wp_hc_webid', $wID, 'yes');
			hc_set_option('wp_hc_siteurl', $hcLink, 'yes');
			if ( isset($_SESSION['HC_SiteToken']) && strlen($_SESSION['HC_SiteToken']) > 1 ) { hc_set_option('wp_hc_token', $_SESSION['HC_SiteToken'] . "", 'yes'); }
			$userMsg = '<div id="message" class="updated"><p><h1>Great Success!</h1></p></div>';
		}
	
	/* MOVE LATER */
		
	} elseif (isset($_POST['hc_search_fields']) && strlen($_POST['hc_search_fields']) > 1) {
		/* Save settings */
		update_option('wp_hc_search_form1', $_POST['hc_search_fields']);
		// DON'T ALLOW UPDATES FROM USER!!!! update_option('wp_hc_webid', $_POST['hc_webid']);
		if ( isset($_POST['hc_siteurl']) ) { update_option('wp_hc_siteurl', $_POST['hc_siteurl']); }
		
		//if (isset(hc_get('Disclaimer'))) {
			delete_transient('hc_disclaimer');
		//}
		//if (isset(hc_get('hc_search_form1_html'))) {
			delete_transient('hc_search_form1_html');
		//}
		
		$userMsg = '<div id="message" class="updated"><p><h1>Great Success!</h1></p></div>';
		/* Flush Rules After Saving Profile Changes */
		hc_flushRules();
	}

	hc_render_settings_form($userMsg);
}




/*function hc_addAjaxUrl() {
	//$ajaxHandlerPath = admin_url('admin-ajax.php');
	$ajaxUrl = array( 'ajaxurl' => admin_url('admin-ajax.php') );
	wp_localize_script( 'hc-ajax', 'HCProxy', $ajaxUrl );
	wp_enqueue_script( 'hc-ajax', plugin_dir_url( __FILE__ ) . 'js/hc-plugin.js', array( 'jquery' ) );
}*/



if (!isset($_SESSION['lastSearchQuery'])) {$_SESSION['lastSearchQuery'] = '';}

function hc_setupAjax() {
	wp_deregister_script( 'jquery' );
	wp_deregister_script( 'jquery-ui' );
	wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js');
	wp_register_script( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js');
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui' );

	/* Add Dan's StringHelpers MAGIC ... Beta!!! */
	wp_enqueue_script( 'string-helpers', plugin_dir_url( __FILE__ ) . 'js/StringHelpers.js' );

	wp_enqueue_script( 'colorbox', plugin_dir_url( __FILE__ ) . 'js/jquery.colorbox.min.js', array( 'jquery' ) );
	wp_enqueue_style( "colorbox", plugin_dir_url( __FILE__ ) . "css/colorbox/colorbox.css");
	
	// THIS IS THE SCRIPT THAT AUTO-WIRES THE MAP-RESULTS SCREEN ************
	//wp_enqueue_script( 'hc-mapify', plugin_dir_url( __FILE__ ) . 'js/hc-mapify.js', array( 'jquery' /* Possibly add GMaps to dependancies list ! */) );
	

	if (isset($_SESSION['HC_Login'])) {
		$leadJSON = json_decode($_SESSION['HC_Login'], true);
	} else if (isset($_SESSION['HC_LeadJSON'])) {
		$leadJSON = json_decode($_SESSION['HC_LeadJSON'], true);
	} else {
		$leadJSON = array();
	}
	
	if (isset($_SESSION['mls'])) {		$leadJSON['Board'] = $_SESSION['mls'];	}
	
	// embed the javascript file that makes the AJAX request
	wp_localize_script( 'hc-ajax', 'HCProxy', array(
			'leadJSON' => $leadJSON,
			'ajaxurl' => admin_url('admin-ajax.php')
			)
	);
	wp_enqueue_script( 'hc-ajax', plugin_dir_url( __FILE__ ) . 'js/hc-plugin.js' );
}

function hc_content_filter($content = '', $listingId = '') {
	global $wp_query;
	$agentData = hc_get_agent_json();
	$currentUserHitcount = addToHitCounter('propDetails');
	if ($currentUserHitcount <= 0) {
		//echo(__("Error: You cannot be too fast with property details... Take your time..."));
		die(__("Error: You cannot be so fast requesting so many property details... Please, take your time") . "...\n\ncounter: " . $_SESSION['hitCount_' . 'propDetails'] . "\n\n user activity code: " . time());
	}
	/*if ($currentUserHitcount > intval($agentData->AnonSearchLimit)) {
		$content .= hc_buildLoginForm(true);
		$content .= "<br />\n";
		$content .= hc_buildLeadSignupForm(true);
		return $content;
	}*/

	// embed the javascript file that makes the AJAX request
	//wp_enqueue_script( 'hc-ajax', plugin_dir_url( __FILE__ ) . 'js/hc-plugin.min.js', array( 'jquery' ) );
	//hc_addAjaxUrl();
	hc_setupAjax();
	/* Call the initialization code! */
	hc_checkSession();

	if (isset($wp_query->query_vars['listingid'])) {
		$listingId = $wp_query->query_vars['listingid'];
	}

	//echo '			Listing ID: ' . $wp_query->query_vars['listingid'] . " -->\n";
	if (isset($listingId) && strlen($listingId) > 2) {
		wp_enqueue_script( 'colorbox', plugin_dir_url( __FILE__ ) . 'js/jquery.colorbox.min.js', array( 'jquery' ) );
		wp_enqueue_script( 'cycle-lite', plugin_dir_url( __FILE__ ) . 'js/jquery.cycle.lite.js', array( 'jquery' ) );
		//wp_register_style("colorbox", plugin_dir_url( __FILE__ ) . "css/colorbox/colorbox.css");
		wp_enqueue_style( "colorbox", plugin_dir_url( __FILE__ ) . "css/colorbox/colorbox.css");
		wp_enqueue_style( "hc-details-1", plugin_dir_url( __FILE__ ) . "details-view/Style_1/Style.css");
		
	  //echo '<!-- 	Addr: ' . $wp_query->query_vars['addr'] . "\n";
		//
		/* Inject custom Listing HTML & CSS */
		//add_filter('the_title', function($title) { global $wp_query; echo '<b>Address: '. $wp_query->query_vars['addr'] . '</b>'; return '<b>Address: '. $wp_query->query_vars['addr'] . '</b>';}, 1);
		
		$hc_proxy = new HomeCardsProxy();
		$strHTML = $hc_proxy->getFullPropertyDetails($listingId);
		$strHTML .= "\n<script type='text/javascript'>\n";
		$strHTML .= "	jQuery(document).ready(function() {\n";
		$strHTML .= "		jQuery('.tb-2 a').attr('href', 'http://maps.google.com/maps?oi=map&q=' + escape(jQuery('.hdr-addr h2').text()) + '&output=embed');\n";
		$strHTML .= "		jQuery('a.colorbox').colorbox({iframe: true});\n";		//$strHTML .= "		jQuery('.toolbar-child ul').remove();\n";	// **** REMOVES THE NAV BAR/TOOLBAR!!!
		$strHTML .= "		if ( jQuery('.HC_Prop_Photos').length > 0 ) {;\n";
		$strHTML .= "			jQuery('.HC_Prop_Photos').cycle({ timeout: 0, prev: '.hc_arrow_left', right: '.hc_arrow_right', after: function(e) {  /* Update The Photo Count Label */  } });\n";
		//hc-prop-photo
		$strHTML .= "			var pics = jQuery('.hc-prop-photo');\n";
		//$strHTML .= "			var pics = jQuery('.hc-prop-photo');\n";
		//$strHTML .= "			var pics = jQuery('.hc-prop-photo');\n";
		//$strHTML .= "			//jQuery('.HC_Prop_Photos').cycle({ prev: '.hc_arrow_left', right: '.hc_arrow_right' });\n";
		$strHTML .= "		}\n";
		//var picBox = $(".content-child-cont"); $('.HC_Prop_Photos').after("<div style=\"width: " + picBox.width() + "px;\"><span class='hc_iconic hc_arrow_left'></span><span style='float:right;' class='hc_iconic hc_arrow_right'></span></div>")
		$strHTML .= "	});\n";
		$strHTML .= "</script>\n";

		/*
		 * One suggestion I have for this function is:
		 * 
		 * DHigginbotham
		 * 
		 * Usage:
		 * 
		 * add_filter('hc_property_...','hc_get_property_details');
		 * 
		 * function hc_get_property_details($listingID) {
		 * 
		 * $tableClass = "css-class-of-lightswabers"; //this way we can design multiple layouts within the same HTML blocks
		 * 
		 * 	$html = "<h1>'..'<h1>";
		 *  $html .= "...Custom Header HTML..."; //ideally, I doubt many developers will need to design something more than this
		 * 	$html .= $listing->propertydetailHTML['main'];
		 *  $html .= $listing->propertyFeaturesHtml; //modular blocks of tables, making fields say within compliance for all rules + regs
		 *  $html .= $listing->locationInformationHtml;
		 * 	$html .= $listing->schoolsHtml;
		 * 	$html .= $listing->layoutRoomDetailsHtml;
		 * 
		 * 	//let's not give them the ability to show disclaimers, we'll have to pass that through the original function
		 * 
		 * 		return $html;
		 * 
		 * }
		 * 
		 * //this is panel names
		 * $panels = array('Property Features', 'Schools', 'Location Information', 'Schools', 'Layout and Room Details' );
		 * 
		 * usage:
		 * 
		 * ...function from top:
		 * 
		 * $html .= $panels[0];
		 * 
		 */


		//add_action('wp_footer', 'hc_output_disclaimer');
		$content = $strHTML;
		$alternateHTML = apply_filters('hc_property_details', $listingId);
		if(isset($alternateHTML) && strlen($alternateHTML) > 5) { //strlen on int > 3 w/ PHP is not reliable enough use 'mb_strlen' for PHP. in JS strlen is meh, it's JS afterall.
			$strHTML = $alternateHTML; //this is where the listing filter gets decided -- very nice and elegant imo.
		}
		return $strHTML;
	} else {
		if (isset($_SESSION['HC_SiteToken'])) {$content .= "<!--	*******		HC Plugin Note:	ListingID is NULL ! 	'thumbprint':	" . $_SESSION['HC_SiteToken'] . "	-->\n";}
	}

	return $content;
	
}

function hc_output_disclaimer() {
	$hc_proxy = new HomeCardsProxy();
	$d = $hc_proxy->getDisclaimer();
	return $d;
	if (!defined('HC_DISCLAIMER_ADDED') || HC_DISCLAIMER_ADDED == false) {
		define('HC_DISCLAIMER_ADDED', true);
		$hc_proxy = new HomeCardsProxy();
		$d = $hc_proxy->getDisclaimer();
		
    if(stripos($d, '</div>') > -1 ) {
      $d = substr($d, stripos($d, '</div>') +6);
    }
    return $d;
	}
	return '<!-- Disclaimer: HC_DISCLAIMER_ADDED: ' + HC_DISCLAIMER_ADDED + ' -->';
}

//add_action('wp_enqueue_scripts', 'hc_addAjaxUrl');
add_action('wp_enqueue_scripts', 'hc_setupAjax');

//super

add_action( 'init', 'hc_addscripts' );

function hc_addscripts() {
	hc_checkSession();
	
	hc_add_url_rewrite();
	
	/*
	Font/Symbol CSS:
		http://www.myhomecards.com/Common/iconic_fill.css
		http://www.myhomecards.com/Common/raphaelicons.css *** NOTE: BIG ***
		http://www.myhomecards.com/Common/websymbols.css
			<span style="font-family: WebSymbolsRegular; color: #999;">R</span>=STAR ICON
			<span style="font-family: WebSymbolsRegular; color: #999;">N</span>=HEART ICON
			<span style="font-family: WebSymbolsRegular; color: #999;">.</span>=CHECK MARK
			Note: #'s 0-7 are the 8 states of a SPINNER progress ICON
			
		http://www.myhomecards.com/Common/iconsweets.css
	*/
	//ENQUEUE SCRIPT
	//wp_enqueue_script( 'hc-autocomplete', plugin_dir_url( __FILE__ ) . 'js/jquery.tokeninput.js', array( 'jquery', 'jquery-ui' ) );	
	wp_enqueue_style( 'components', plugin_dir_url( __FILE__ ) . 'css/components.css' );
	wp_enqueue_style( 'hc-websymbols', 'http://www.myhomecards.com/Common/websymbols.css' );
	if ( ! is_admin() ) { wp_enqueue_style( 'hc-iconic-fill', 'http://www.myhomecards.com/Common/iconic_fill.css' ); }
	//wp_enqueue_style( 'hc-autocomplete-fb', plugin_dir_url( __FILE__ ) . 'css/token-input-facebook.css' );
	//wp_enqueue_style( 'hc-widget-mort-calc', plugin_dir_url( __FILE__ ) . 'css/widgets.css' );	

	//echo '<script type="text/javascript" href="' . plugin_dir_url( __FILE__ ) . 'js/token-input-facebook.css"></script>';
}

//	$hc_proxy = new HomeCardsProxy();
//	$json = $hc_proxy->getAccountJson();
//	$myData = json_decode($json);
//	echo $myData['Ag'];


/***
 * 
 * Let's load some scripts, make things a bit more magical
 * 
 ***/

//This is the session limiter/counter
/*
	Updated by Dan to support expiration!!! - 2012-03-25
	IMPORTANT: if _SESSION tracking below is changed to use $GLOBALS, MAKE SURE TO ALWAYS INCLUDE THE REMOTE_ADDR IN THE $suffix
*/
function addToHitCounter($suffix = '') {
	if ( empty($suffix) || strlen($suffix) < 1 ) { $suffix = $_SERVER['REMOTE_ADDR']; }
	$newExpiresTimestamp = time() + (60 * 60);
	//Set Initial Count
	if ( empty($_SESSION['hitCount_' . $suffix])) { $_SESSION['hitCount_' . $suffix] = 1 . "|" . $newExpiresTimestamp; }
	$hitData = explode('|', $_SESSION['hitCount_' . $suffix]);
	/* Check for an expired hitCounter - reset count if our current hitLimiter # is invalid  */
	if ( isset($hitData[1]) && intval($hitData[1]) <= time()) {
		// This is where the counter gets reset
		$_SESSION['hitCount_' . $suffix] = 1 . "|" . $newExpiresTimestamp;
		$hitData = explode('|', $_SESSION['hitCount_' . $suffix]);
	}
	$hitData[0] = intval($hitData[0]) + 1;
	/* update the $hitData array and update the session token */
	$_SESSION['hitCount_' . $suffix] = implode('|', $hitData);
	if (!is_admin() && $_SESSION['hitCount_' . $suffix] > 800) {
		return -1;
	}
	return $_SESSION['hitCount_' . $suffix];
}

function hc_urlencode($string) {
	return str_replace(array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D'), array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]"), urlencode($string));
}

function hc_widget_css() { ?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			jQuery('#available-widgets .widget').each(function(index, obj) {
				var ctx = jQuery(obj);
				var title = null;
				var top = ctx;
				if (ctx.find('h4').text().indexOf("HomeCards") > -1) {
					title = ctx.find('.widget-title');
					title.addClass('hc-widget-box');
					title.find('h4').css({
						'color' : '#FFF',
						textShadow : '1px 1px 0 #034769'
					});
					top.css({
						border : '1px solid #e2e2e2',
						background : '#f4f4f4'
					});
					//title.parent().addClass('hc-widget-box-top');

				}
			})
		});
	</script>
<?php
}

add_action( 'admin_head', 'hc_widget_css' );

function hc_renderSignupHtml() {
if (get_option('hc_signup_html', '') != '') {
return get_option('hc_signup_html', '');
} else {
return "	<h2 class=\"hc-signup-title\">New to the site? Create a New Account	</h2>
<div class=\"hc-signup-info\">
<span>In order to save this property you must first create an account. Once you have an account you get much more than simply a place to track your favorite properties, you also get:</span>
<ul class=\"hc-signup-info\">
<li><span style=\"font-family: WebSymbolsRegular; color: #333;\">.</span> Your own Password Protected Personal Buyer Website</li>
<li><span style=\"font-family: WebSymbolsRegular; color: #333;\">.</span> Email Notifications for new listings that come on the Market</li>
<li><span style=\"font-family: WebSymbolsRegular; color: #333;\">.</span> Access all Virtual tours</li>
<li><span style=\"font-family: WebSymbolsRegular; color: #333;\">.</span> And more...</li>
</ul>
<strong>This is a completely free service so sign up and get started today!</strong>
</div>";
}
}

function hc_get_user_roles() {
global $current_user;
if ( isset($current_user) && isset($current_user->roles) ) {
$user_roles = $current_user->roles;
return implode(',', $current_user->roles);
} else {
return 'anonymous';
}
}
