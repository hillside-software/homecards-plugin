<?php

function hc_getStyle_cityProfilePage() {
	wp_register_style("hc-city-page-style1", plugin_dir_url( __FILE__ ) . "css/city-page-style1.css");
	wp_enqueue_style( "hc-city-page-style1", plugin_dir_url( __FILE__ ) . "css/city-page-style1.css");
}

function hc_getStyle_leadLoginForm() { 
	return "<style type=\"text/css\">
			ul.hc_login_fields, ul.hc_login_fields li {list-style-type: none;}
			.hc-error-msg {color: #FF0000; font-size: 18px; font-weight: 700;}
		</style>";
}

function hc_getStyle_signupForm() { ?>
		<style type="text/css">
			ul.hc_signup_fields, ul.hc_signup_fields li {list-style-type: none;}
			.hc-error-msg {color: #FF0000; font-size: 18px; font-weight: 700;}
		</style>
<?php 
}

function hc_getStyle_searchresults() {
		?>
		<style type="text/css">
			ul.HCListings {
			  float: left;
			  width: 750px; /* ADJUST TO CHANGE OUTER 'BOX's' WIDTH */
			  margin: 0;
			  padding: 0;
			  list-style: none;
			} 
			ul.HCListings li {
				font-family: Tahoma, Verdana;
				font-size: 13px;
			  float: left;
			  width: 25%; /* <<<<<<< EDIT THIS VALUE FIRST <<<< ... TRY 20%, 30%, 40% .... ADJUST TO CHANGE COLUMNS/WIDTH */
			  margin: 0 1%;
			  padding: 0;
			}
			/* set caption/label default styles */
			span.HCLbl, div.HCAddress {font-weight: 700;}
			div.HCListor, div.HCSubarea, div.HCAddress {
				clear: both;
				overflow: hidden;
			  width: 100%;
			  height: 18px;
			}
			div.HCPhoto img {
				width: 90%; /* ADJUST TO CHANGE COLUMNS/WIDTH */
				margin: 0px 5%; /* ADJUST TO CHANGE COLUMNS/WIDTH */
			}
			div.HCPhoto {height: 172px; /* ADJUST TO CHANGE COLUMNS/WIDTH */
				overflow: hidden;}
			/* Put HCPrice, HCBeds and HCBaths on same line */
			div.HCPrice {float: left; width: 38%; font-weight: 700;}
			div.HCBeds {float: left; width: 24%;}
			div.HCBaths {float: left; width: 24%;}
			div.HCRemarks {height: 50px; overflow: hidden; font-style: italic;}
			div.HCRemarks span.HCLbl, div.HCPrice span.HCLbl {display: none;}
			div.HCListor span.HCLbl {font-weight: 400;}
			div.HCDisclaimer {padding-top: 25px; clear: both; font-size: 12px !important;}
			div.HCDisclaimer td {font-size: 13px !important;}
			div.HCListor {margin-bottom: 8px;}
		</style>
<?php 
}


function hc_getStyle_propertyDetails() {
		echo '<scr' . 'ipt src="' . plugin_dir_url( __FILE__ ) . 'js/jquery.cycle.lite.min.js"></scr' . 'ipt>';
		?>
		<style type="text/css">
			div.HC_Prop_Photos {
				height: 280px;
			}
			div.HC_Pic_Box {
			  float: left;
			  width: 370px;
			  height: 280px;
			  margin: 0;
			  padding: 0;
			  list-style: none;
			}
			div.HC_Pic_Box img {
				width: 350px;
				margin: 10px;
			}
			.HCFeaturesSubHeader {font-size: 24px; font-weight: 700;}
			.HCFeatCaption {font-weight: 700;}
		</style>
		<script type="text/javascript">
			jQuery(document).ready(function(event) {
				if (jQuery("#hc_next").length < 1) {
					jQuery("div.HC_Prop_Photos").after('<div class="HC_Photo_Nav"><a id="hc_prev" href="#">Prev</a> <a id="hc_next" href="#">Next</a></div>');
				}
				jQuery("div.HC_Prop_Photos").cycle({fx: 'fade', speed:  'fast', timeout: 0, next: '#hc_next', prev: '#hc_prev'});
			});
			
		</script>
		

<?php 
}
