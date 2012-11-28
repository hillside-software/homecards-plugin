<?php 
if (!isset($_SESSION)) { session_start(); }


add_action('init', 'hc_add_url_rewrite');


function hc_add_url_rewrite() {
	if ( isset($GLOBALS['HC_URL_REWRITE_ADDED']) && $GLOBALS['HC_URL_REWRITE_ADDED'] == true ) { 
		/*** DO NOTHING ***/
	} else {
		global $wp, $wp_rewrite;
		/*$wp_rewrite->add_rule('hc-home-search/([^/]+)/([^/]+)/([^/]+)','index.php?loc=$matches[1]&code=$matches[2]','top');*/
	  $wp->add_query_var('listingid');
	  $wp->add_query_var('addr');
	  $wp->add_query_var('city');
	  $wp->add_query_var('state');
	  $wp->add_query_var('zip');
	  
	  $rootSlug = hc_getRootSlug();
	  if ( $rootSlug == '/' ) { $rootSlug = ''; }
	  
		/* NOTE: $detailsPrefix IS OBSOLETE */
	  $detailsPrefix = get_option('wp_hc_details_url_prefix', '');
	
	  $wp_rewrite->add_rule($rootSlug . get_option('wp_hc_property_details_path', 'property-details') . '/([^/]+)/([^/]+)/?.?$','index.php?addr=$matches[1]&listingid=$matches[2]','top');
	
		/* NOTE: $detailsPrefix IS OBSOLETE */
		if (strlen($detailsPrefix) > 1) {
			$wp_rewrite->add_rule($rootSlug . $detailsPrefix . '/([^/]+)/([^/]+)/([^/]+)/?$','index.php?city=$matches[1]&addr=$matches[2]&listingid=$matches[3]','top');
		}
		if ( empty($GLOBALS['HC_URL_REWRITE_ADDED']) ) { $GLOBALS['HC_URL_REWRITE_ADDED'] = true; }
			
	}
	//$wp_rewrite->flush_rules(false);  // This should really be done in a plugin activation
}


function hc_flushRules() {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

add_action('parse_query', 'hc_apply_path_to_query');
function hc_apply_path_to_query(&$query) {
  if (isset($query->query['listingid'])) {
    if (isset($query->query['addr'])) { $query->query_vars['addr'] = $query->query['addr']; }
    if (isset($query->query['city'])) { $query->query_vars['city'] = $query->query['city']; }
    $query->query_vars['listingid'] = $query->query['listingid'];
  }

}

/*
Returns a value like: test-v3/ if the wp root url/install path is http://example.com/test-v3/
*/
if (!function_exists('hc_getRootSlug')) {
function hc_getRootSlug() {
	$rootSlug = '';
	$siteUrl = get_option('siteurl', '');
	$siteUrl = str_replace('https://', '', str_replace( 'http://', '', $siteUrl));
	if ( strpos($siteUrl, '/')  > 1 ) {
		$rootSlug = substr($siteUrl, strpos($siteUrl, '/') + 1);
		if ( strlen($rootSlug) < 1 ) { return '/'; }
	}
	return $rootSlug;
}
}
/*
**** DISABLED - hc_getRootSlug is 50-100% faster than the following RegEx method
if (!function_exists('getRootSlugRegex')) {
function getRootSlugRegex() {
	global $rootSlug;
	$rootSlug = preg_replace('\.[\w:]*(\/[\/\w]*$)', '$1', $siteUrl);
	return $rootSlug;
}
}
*/

