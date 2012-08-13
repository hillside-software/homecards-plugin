<?php

// NOTE: You must include AT LEAST the require_once($homecards_path . '/short-codes-2.php') file... PROBABLY NEEDS require_once('homecards-plugin.php')


function doCustomSearch() {
	hc_setupAjax();
	/********* CUSTOM SEARCH EXAMPLE *********/
	/********* EXAMPLE BEGIN *********/
	// Define a custom filter for the Field Rendering
	$custom_search_field_callback = function ($fieldData, $current_html) {
		// Note: $current_html is the old HTML - it's passed through so you have access to it's initial HTML (and parsable attributes)
		return '<li class=" hc-custom-item ' . $fieldData["class"] . '_parent"><label for="' . $fieldData["id"] . '">' . $fieldData["caption"] . ':</label>
							 <input type=\'text\' id=\'' . $fieldData["id"] . '\' name=\'' . $fieldData["_id"] . '\' class=\'custom-' . strtolower($fieldData["_id"]) . '\' data-type="' . $fieldData["type"] . '" />
						</li>' . "\n";
	};
	$customSearchFields = array('Area', 'Beds', 'Baths', 'SortResults');
	/// OR Something like this: $customSearchFields = explode("Area,TYPE,Beds,Baths,PriceFrom,PriceTo,zip,ELEM,JRHI,SRHI,subarea,BSM,Const,Style,SortResults", ",");
	foreach ( $customSearchFields as &$fieldName ) {
		$fieldName = getFieldHtml($fieldName, "hc-lbl-lrg", "full", $custom_search_field_callback);
	}
	echo implode( $customSearchFields, "\r\n<br />\r\n");
	/********* EXAMPLE END *********/
}

//Get JSON 
	function get_json_search_results() {
		$hc_proxy = new HomeCardsProxy();

		$queryString = array(
			'wID' => get_option('wp_hc_webid', -1),
			'Area' => 'Denver', 
			'TYPE' => '<ALL>',
			'Status' => 'A,P',
			'PriceFrom' => '100000',
			'PriceTo' => '1000000', 
			'Beds' => '1',
			'Baths' => '1', 
			'SortResults' => 'ListDate.Desc', 
			'DaysBack' => '',
			'SubArea' => '',
			'Warn' => 'False');

		$json = $hc_proxy->doSearch($limit, $queryString, $actionOverride = '', $outputMode = 'JSON');
		
		return $json;

	}

