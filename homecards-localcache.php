<?php 

function hc_set($key, $data, $timeoutSecs) {
	if ( defined("HC_CACHE_MODE") ) { $cacheMode = HC_CACHE_MODE; } else { define("HC_CACHE_MODE", "globals"); $cacheMode = "globals";}
	if ( !isset($timeoutSecs)) { $timeoutSecs = 60*60*12; }
	$expireTime = time() + $timeoutSecs;
	if ($cacheMode == 'file' ) {
		// check for null $data - clean file
		if ( $data == null ) {
			return unlink($tmpfname);
		} else {
			// we have data to cache to file
			$tmpfname = tempnam("/tmp", "hc_cache_" . $key . '.tmp');
			$handle = fopen($tmpfname, "w");
			fwrite($handle, $data);
			fclose($handle);
		}
	} else if ($cacheMode == 'globals' || $cacheMode == 'memory' || $cacheMode == 'php' ) {
		// PER-SCRIPT-REQUEST VALUE CACHE - PREVENTS REPETITIVE OR RECURSIVE DATA CALLS TO THE HC PROXY SERVER
		// check for a delete request
		if ( $data == null ) {
			$GLOBALS[$key] = null;
			return false;
		}
		$GLOBALS[$key] = $data;
		$GLOBALS[$key . "_exptime"] = $expireTime;
	} else if ($cacheMode == 'transient' || $cacheMode == 'db' || $cacheMode == 'wp' ) {
		// This is probably the best bet to be compatible with 3rd party caching layers (like redis or mongoDB )
		// check for a delete request
		if ( $data == null ) {
			return delete_transient($key);
		} else {
			// We gots data to push
			set_transient($key, $data, $timeoutSecs);
		}
	}
}
function hc_get($key, $defaultValue = '') {
	if ( defined("HC_CACHE_MODE") ) { $cacheMode = HC_CACHE_MODE; } else { define("HC_CACHE_MODE", "globals"); $cacheMode = "globals";}
	if ($cacheMode == 'file' ) {
		$tmpfname = tempnam("/tmp", "hc_cache_" . $key . '.tmp');
		if ( file_exists($tmpfname) ) {
			$handle = fopen($tmpfname, "r");
			$contents = fread($handle, filesize($tmpfname));
			fclose($handle);
			//unlink($tmpfname);
			return $contents;
		}
	} else if ($cacheMode == 'globals' || $cacheMode == 'memory' || $cacheMode == 'php' ) {
		if (isset($GLOBALS[$key . "_exptime"])) {$expireTime = $GLOBALS[$key . "_exptime"];} else { $expireTime = time() + 60*60*1; }
		if (time() > $expireTime) { /* Expired Data, clear it out!!! */
			$GLOBALS[$key] = null;
			return $defaultValue;
		}
		if ( isset($GLOBALS[$key]) ) { return $GLOBALS[$key]; }
		return $defaultValue;
	} else if ($cacheMode == 'transient' || $cacheMode == 'db' || $cacheMode == 'wp' ) {
		if ( false === ( $value = get_transient( $key ) ) ) {
		     // this code runs when there is no valid transient set
		     return $defaultValue;
		} else {
			return $value;
		}
	}
	return $defaultValue;
}
