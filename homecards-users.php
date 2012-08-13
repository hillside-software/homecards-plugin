<?php

global $current_user;

/*
Examples:

HC_Auth::checkCurrentUserLoginState()
HC_Auth::getCurrentLead()

json_encode(HC_Auth::getCurrentLead())

*/

class HC_Auth {
	var $user;
	var $hc_user;
	
	//private static $singleton;
	
	function __construct() {
    
	}
	
	static function getLeadID() {
		$currentLead = self::getCurrentLead();
		if ( isset( $currentLead['cID'] ) ) {
			return intval( $currentLead['cID'] );
		} else {
			return -1;
		}
	}

	/* TODO: We should probably cache the returned object here */
	static function getCurrentLead() {
		/* Check for HC Lead Session Data */
		if (isset($_SESSION['HC_Login'])) {
			$leadJSON = json_decode($_SESSION['HC_Login'], true);
			$leadJSON['IsValid'] = true;
		} else if (isset($_SESSION['HC_LeadJSON'])) {
			$leadJSON = json_decode($_SESSION['HC_LeadJSON'], true);
			$leadJSON['IsValid'] = true;
		} else {
			$leadJSON = array('cID' => -1, 'IsValid' => false );
		}
		return $leadJSON;
	}


/*
Call the HC_Auth::checkCurrentUserLoginState() function like this in homecards feature rendering code:

if ( get_current_user_id() != 0 ) {
	$leadID = HC_Auth::checkCurrentUserLoginState();
	if ( ! is_wp_error($leadID) ) {
		// Success! we can now use the HC_Auth::getCurrentLead() function
		$hc_lead = HC_Auth::getCurrentLead();
		//var_dump($hc_lead['SavedListings']);// This will be a list of the current user's saved listings MLS#'s
	}
}


if ( get_current_user_id() != 0) {
	$current_user = wp_get_current_user();
	var_dump(get_user_meta($current_user->user_id, 'hc_consumerid'));
}
*/
	static function checkCurrentUserLoginState() {
		$current_user = wp_get_current_user();
		if ( get_current_user_id() != 0 && self::getLeadID() < 1 ) {
			// We have a WP authenticated user, now we need to see if they have HC_Lead_ID in metadata
			$consID = get_user_meta($current_user->user_id, 'hc_consumerid', true);
			if ( $consID != "" && intval($consID) > 1000 ) {
				// We need to retrive a new HC auth session - this uses a trusted function and may provide a hacker an interesting attack vector
				$hc_proxy = new HomeCardsProxy(false);
				$leadJSON = $hc_proxy->consumerLoginWithToken($current_user->user_email);
				// check if the returned consumer has the same cID as our current WP user's metadata claims (in metakey=hc_consumerid)
				if ( $leadJSON['cID'] == $consID ) {
					return $leadJSON['cID'];
				}
				return $leadJSON['cID'];//return true;
			} else if ( empty($_SESSION['HC_AutoSignup_Flag']) ) {
				// There is no $consID from the current WP_User's metadata
				// We need to add the current user to HC //
				$hc_proxy = new HomeCardsProxy(false);
				$defaultPassword = 'from-wp';
				$_SESSION['HC_AutoSignup_Flag'] = true;
				$leadJSON = $hc_proxy->consumerSignup($current_user->user_firstname . ' ' . $current_user->user_lastname, '', $current_user->user_email, $defaultPassword, 'WP-Auto-Signup', getCurrentURL() );
				if ( isset($leadJSON['cID']) && $leadJSON['cID'] > 1000 ) {
					add_user_meta($current_user->user_id, 'hc_consumerid', $leadJSON['cID'], true);
					return $leadJSON['cID'];
				} else {
					return new WP_Error('Error auto-creating HomeCards account', 'Response Data: ' . json_encode($leadJSON));
				}
			} else {
				return -2;
			}
			
		}
		return -1;
	}

	
}


// The getCurrentURL() method fixes some IIS7 PHP issues
function getCurrentURL() {
	if (!isset($_SERVER['REQUEST_URI'])) {
		$_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'],1 );
		if (isset($_SERVER['QUERY_STRING'])) { $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING']; }
	}
	return $_SERVER['REQUEST_URI'];
}

/*
add_action('wp_authenticate','hc_checkCustomAuthentication');

add_filter('authenticate', 'hc_authenticate_username_password', 5, 3);

hc_authenticate_username_password

function hc_checkCustomAuthentication($username) {
	$hc_proxy = new HomeCardsProxy(false);
	$html = $hc_proxy->consumerLogin($username, $password);
	$html = trim(trim(trim($html)));
	if (stripos($html, 'Error') <= -1 && strlen($html) > 3) {
		
	}

	/// Check for HC account,
	if (!username_exists($username)) {
		return;
	}

}

*/
