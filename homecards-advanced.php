<?php

class AdvPropertyDetails {
	
		var $listingId = '';
		var $detailsHTML =  '';
		var $panels = 			array();
		var $panelNames = 	array();
		var $panelsHTML = 	'';
		var $listingJSON = null;
		var $agentData = null;
		var $counter = null;
		var $blocks = array();
		var $listImage = array();

	function __construct($listingId) {
		require_once(WP_PLUGIN_DIR . "\homecards-plugin\homecards-plugin.php");
		$this->get_property_json();
		$this->listingId = $listingId;
		$this->get_property_html();
		//$this->getPropPhoto();
		
	}
		
	private function get_property_json() {
		$hc_proxy = new HomeCardsProxy();
		$json = $hc_proxy->doSearch(20, array("listingids" => $this->listingId), "Search", "JSON");
		$listing = json_decode($json);
		if ( count($listing) >= 1 ) { $listing = $listing[0];/* we only care about the first item in the array */ }
		$this->listingJSON = $listing;
		
		//$counter = $this->listingJSON->PHOTOYN;

		return $listing;
	}

	private function getAgentData() {
		$hc_proxy = new HomeCardsProxy();
		$json = $hc_proxy->getAccountJson();
		
		$agentData = json_decode($json);
		
		return $agentData;
		//var_dump($agentData);
	}

	private function get_property_html() {
		$hc_proxy = new HomeCardsProxy();
		$html = $hc_proxy->getFullPropertyDetails($this->listingId);

		//here's where we parse some HTMILLZE
		$eof = true;
		$featureStart = stripos($html, 'HCFeaturesTable') - 57;
		
		$next = $featureStart;
		
		$i = 0;
		while ($eof && $next < mb_strlen($html)) {
			
			//table start tag
		  $currentTablePos = stripos($html, '<table', $next - 3);

		  //here's where we grab string using substr
			$next = stripos($html, '</table>', $featureStart) + 8;
			if ($next < $featureStart) {
				$eof = false;
			} else {
				$featureStart = $next;
				$this->panels[] = substr($html, $currentTablePos, $next - $currentTablePos);
			}

		}

	}
		
	public function getPanelsHTML()
	{
		return $this->panelsHTML;
	}
	public function getAllPanels()
	{
		return $this->panels;
	}

	public function getPropPhoto() {
		$board = $this->getAgentData(); //get board
		//$counter = $this->listingJSON->PHOTOYN;
		$counter = 10;
		$indexPhoto = range('a', 'z');

		$html = "<h2>" . $this->listingJSON->P_AD . "</h2>";
		$html .= "<div class='clearfix' style=\"margin-bottom: 20px;\">\r\n";
		$html .= "\t<ul id=\"pikame\">\r\n";
			for ($i=0; $i < $counter; $i++ ) { 
				//$listImage = "http://www.myhomecards.com/GetPhoto.aspx?LN=" . $this->listingId . "&Size=ExtraLarge&PhotoIndex=". $indexPhoto[$i] ."&Board=" . $board->Board;
				$listImage = "http://www.myhomecards.com/GetPhoto.aspx?PhotoNumber=". $indexPhoto[$i] ."&Size=ExtraLarge&Board=" . $board->Board . "&LN=" . $this->listingId;
				$html .= "\t\t<li><img src=". $listImage ." /></li>\r\n";
			}
		$html .="\t</ul>\r\n\t</div>";
		
		echo $html;	
	}

}

//add_filter('hc_search_results', 'get_json_search_results');
add_filter('hc_property_details', 'get_property_details');

function get_property_details($listingId) {
	@header( "Content-Type: text/html" );
	
	$Example = new AdvPropertyDetails($listingId);

	$panels = $Example->getAllPanels();
	$photoGallery = $Example->getPropPhoto();
	
	//print_r($Example->listingJSON);
	
	$html =	"<script type=\"text/javascript\">jQuery(document).ready(	function (){ $('#pikame').PikaChoose();	var tabber1 = new Yetii({	id: 'tab-container-1', }); });</script>";
	//$html .= 
	$html .= $photoGallery;
	$html .= "<div id='tab-container-1' class='clearfix'>\r\n\t<ul id='tab-container-1-nav'>\r\n";
	$html .= "\t\t<li><a href=\"#features\">Property Features</a></li>\r\n";
	//$html .= "\t\t<li><a href=\"#gallery\">Image Gallery</a></li>\r\n";
	$html .= "\t\t<li><a href=\"#location\">Location and Schools</a></li>\r\n";
	$html .= "\t\t<li><a href=\"#layout\">Property Layout</a></li>\r\n\t" . "</ul>\r\n";
	$html .= "\t<div id=\"features\" class='tab'>" . "<div>" . $panels[0] . "</div>\r\n" . "<div>" . $panels[4] . "</div>\r\n</div>\r\n";
	//$html .= "\t<div id=\"gallery\" class='tab'>" . $photoGallery . "</div>\r\n";
	$html .= "\t<div id=\"location\" class='tab'>\r\n\t<div>" . $panels[1] . "</div>\r\n" . "\t<div>" . $panels[2] . "</div>\r\n</div>\r\n";
	$html .= "\t<div id=\"layout\" class='tab'>" . $panels[3] . "</div>\r\n";
	$html .= "</div>\r\n";
	
	return $html;
}

function get_json_search_results($json) {
	var_dump(hc_get_fields());
	$results = json_decode($json); 
	$html = "<ul>"; 
	for($i = 0; $i < count($results); ++$i)
	{
		$html .= "<li>".$results[$i]["P_MLSREMARKS"]."</li>\r\n";
	}
	$html .= "</ul>"; 
	
	return $html;
}


add_action('wp_enqueue_scripts', 'add_gallery_scripts_styles_wp_head');

function add_gallery_scripts_styles_wp_head() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('yetii-min', plugin_dir_url( __FILE__ ) . 'js/' . 'yetii-min.js', array('jquery'));
		wp_enqueue_script('pikachoose', plugin_dir_url( __FILE__ ) . 'js/' . 'jquery.pikachoose.full.js', array('jquery'));
		wp_enqueue_style('components', plugin_dir_url( __FILE__ ) . 'css/'. 'components.css', false, false, 'screen');
	}
?>

