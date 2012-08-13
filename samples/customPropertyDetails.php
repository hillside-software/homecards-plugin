<?php
	/*
		This is how we make a custom property details screen: 
			
			'hc_property_details'
	
	*/
	
	add_filter('hc_property_details', array('MyPropertyDetails', 'getHTML'));
	static class MyPropertyDetails{
		static function getHTML($listingId){
			$hc_proxy = new HomeCardsProxy();
			return $hc_proxy->getFullPropertyDetailsJSON($listingId);
		}
	}
?>