<?php
//include_once 'hc-proxy-functions.php';



$htmlFieldTable = array();
$searchFields = array();

	
	function hc_renderAllFields($returnMode = 'full') {
		if (!isset($searchFields) || count($searchFields) < 1 ){ $searchFields = json_decode(hc_get_fields());}
		$html = '';
		foreach ($searchFields->fieldDetails as $fld) {
			$html .= getFieldHtml($fld[0], '', $returnMode);
		}
		return "<ul class='hc-available-fields hc-fields' style='display: inline-block;'>\n" . $html . "\n</ul>\n";
	}
	function getField($fieldId, $defaultId = '') {
		if ( empty($fieldId) || strlen($fieldId) <= 1 ) { return $defaultId; }
		if (!isset($searchFields) || count($searchFields) < 1 ){ $searchFields = json_decode(hc_get_fields());}
		foreach ($searchFields->fieldDetails as $fld) {
			if (strtolower($fld[0]) == strtolower($fieldId)) { return array('id' => 'hc_' . strtolower($fld[0]), '_id' => $fld[0], 'caption' => $fld[1], 'type' => $fld[2], 'class' => 'hc_' . strtolower($fld[0])); }
		}
		return $defaultId;
	}
	function getFieldHtml(&$fieldData, $lblCss = 'hc-lbl-lrg', $returnMode = 'full', $fieldFilterCallback = NULL) {
		if (is_string($fieldData)) {$fieldData = getField($fieldData);}
		if (!isset($htmlFieldTable) || count($htmlFieldTable) < 1 ){ $htmlFieldTable = json_decode(hc_get_fields_html());}
		if ($fieldData == null) {return '<h4>$fieldData is empty</h4>' . "\n";}
		if ($returnMode == 'placeholder') {
			//return '				<div class="ui-widget-content hc-drag ' . trim($lblCss . ' ' . $fieldData["class"]) . '" data-id="' . $fieldData["_id"] . '" data-type="' . $fieldData["type"] . '"><input type="checkbox" title="enabled?" class="hc-fld-chk" />' . $fieldData["caption"] . "</div>\n";
			return '				<li class="ui-widget-content hc-drag ' . trim($lblCss . ' ' . $fieldData["class"]) . '" data-id="' . $fieldData["_id"] . '" data-type="' . $fieldData["type"] . '">' . $fieldData["caption"] . "</li>\n";
		}
		$fieldHtmlContent = $htmlFieldTable->{strtolower($fieldData["_id"])};
		$fieldHtmlContent = addIdAttr(addClass($fieldHtmlContent, 'headSearchFormFields ' . $fieldData["class"]), $fieldData["id"]);
		if (strpos($fieldHtmlContent, 'checkbox') > 0) { 
			$fieldWidth = " style=\"width: 200px; display: block; clear: both;\"";
			$fieldWidth = "";
		} else {
			$fieldWidth = "";
		}
		$preFilterHtml = '				<li' . $fieldWidth . ' class="' . $fieldData["class"] . '_parent"><label for="' . $fieldData["id"] . '">' . $fieldData["caption"] . ':</label>' . $fieldHtmlContent . "</li>\n";
		if ( $fieldFilterCallback != null ) {
			// Execute the callback - this must be a custom search
			return call_user_func_array($fieldFilterCallback, array($fieldData, $preFilterHtml) );
		}
		/*if ( has_filter($fieldFilterName) && is_callable('hc_' . $fieldFilterName, false) == true ) {
			$preFilterHtml = apply_filter($fieldFilterName, create_function('$field,$current_html', 'return hc_' . $fieldFilterName . '($field,$current_html);'));
		}*/
		return $preFilterHtml;
	}



	add_shortcode('homecards2', 'hcSearchShortCode');
	add_shortcode('badge', 'hcBadgeShortCode');
	add_shortcode('loan-calc', 'hcMortShortCode');
	
	// Note: This function can be called explicitly in code - or via WP shortcode handlers
	function hcSearchShortCode($atts) {
		$atts = shortcode_atts( array(
			'page' => 'search',
			'searchBaseUrl' => '/search/',
			'formId' => 'headForm',
			'formClassName' => '',
			'showMap' => true,
			'formTitle' => 'Find a Property Quickly and Easily',
			'pageInfoHtml' => '',
			'mode' => 'searchform' /* Modes available: 'builder' AND 'shortcode'|'sc' and 'page'|'post' ... Respectively, adds a Shortcode/Canned search builder... or Can create a custom post or page */
		), $atts );
		
		$returnMode = 'full';
		if ($atts['mode'] == 'shortcode' || $atts['mode'] == 'sc' || $atts['mode'] == 'page' || $atts['mode'] == 'post') {
			$returnMode = 'full';
		} elseif ( $atts['mode'] == 'formbuilder' || $atts['mode'] == 'builder' ) {
			$returnMode = 'placeholder';
		}
		
		
		if (isset($atts['page']) && $atts['page'] == 'search') {
			//enqueue some scripts for awesome.
			wp_enqueue_style( 'hc-shortcode-search', plugin_dir_url( __FILE__ ) . 'css/search.css' );	

			//these need to be defined anywhere used at the moment
			if (!isset($searchFields) ){ $searchFields = json_decode(hc_get_fields());}
			if (!isset($htmlFieldTable) ){ $htmlFieldTable = json_decode(hc_get_fields_html());}
			$agentData = getAgentData();
			?>
		<?php
		if ( isset($atts['formTitle']) && strlen($atts['formTitle']) > 1 ) { echo('<h2>' . $atts['formTitle'] . '</h2>'); }
		if ( isset($atts['pageInfoHtml']) && strlen( $atts['pageInfoHtml'] ) > 1 ) { echo('<p>' . $atts['pageInfoHtml'] . "</p>\n"); }
		
		
		/*wp_deregister_script( 'jquery' );
		wp_deregister_script( 'jquery-ui' );
		wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js');
		wp_register_script( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js');
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui' );*/
		
		hc_setupAjax();
		
		wp_enqueue_script( 'hc-search-form', plugin_dir_url( __FILE__ ) . 'js/hc-search-form.js', array( 'jquery', 'hc-ajax' ) );

		/*wp_enqueue_script( 'hc-autocomplete', plugin_dir_url( __FILE__ ) . 'js/jquery.tokeninput.js', array( 'jquery', 'jquery-ui' ) );	
		wp_enqueue_style( 'hc-autocomplete', plugin_dir_url( __FILE__ ) . 'css/token-input.css' );
		wp_enqueue_style( 'hc-autocomplete-fb', plugin_dir_url( __FILE__ ) . 'css/token-input-facebook.css' );*/
		hc_addscripts();
		if ( $atts['showMap'] == true ) {
			echo '		<div class="hc-map-box" style="width: 550px; height: 440px; margin: 6px;">Loading Map... Please wait...</div>' . "\n";
			echo '		<div class="hc-map-status" style="width: 550px; height: 20px; margin: 6px;">Loading Map... Please wait...</div>' . "\n";
		}

		?>
		<div class="clear"></div>
		<form id="<?php echo $atts['formId']; ?>" action="<?php echo $atts['searchBaseUrl']; ?>" class="<?php echo $atts['formClassName']; ?>" method="POST">
			<input type="hidden" name="wID" value="<?php echo $agentData->WebID; ?>" />	
			<div class='hc-drag-start'>
				<?php
				echo hc_renderAllFields($returnMode);
				?>
			</div>
			<div class="clear">
<?php
	if (strlen($atts['formClassName']) > 1) {
		$frmClass = "." . $atts['formClassName'];
	} else {
		$frmClass = "";
	}
switch ($atts['mode']) {
	case 'shortcode':
	case 'sc':
	case 'cannedsearch':
		echo '				<div class="clear"></div>' . "\n";
		echo '				<div class="shortcode-result">click the button when you have completed your search criteria</div>' . "\n";
		echo getMLSSelectHTML(); 
		echo '				<input type="button" value="Make Shortcode" class="clear headSearchSubmit search-shortcode-btn button-primary" />' . "\n";
		echo '				<input type="button" value="Make Page w/ Shortcode" class="clear headSearchSubmit create-page-shortcode-btn" />' . "\n";
		echo '				<input type="submit" value="Set Search Defaults" class="clear headSearchSubmit hc-set-search-defaults" />' . "\n";
		echo '				<input type="submit" value="Search" id="headSubmit" class="clear headSearchSubmit" />' . "\n";
		break;
	case 'page':
	case 'post':
	case 'pagebuilder':
	case 'postbuilder':
		echo '				<input type="button" value="Finish" class="clear headSearchSubmit postbuilder-submit" />';
		break;
	case 'formbuilder':
	case 'builder':
?>
</div>
<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td valign="top">
			<ul class="hc-selected-fields hc-fields-col1 hc-fields">
			</ul>
		</td>
		<td valign="top">
			<ul class="hc-selected-fields hc-fields-col2 hc-fields">
			</ul>
		</td>
		<td valign="top">
			<ul class="hc-selected-fields hc-fields-col3 hc-fields">
			</ul>
		</td>
	</tr>
</table>
	<script type='text/javascript'>
		jQuery(document).ready( function($) {
			$("ul.hc-fields").sortable({
				connectWith: "ul.hc-fields",
				helper: "original",
				stop: function(event, ui) {
					$('.shortcode-result').html(getSearchFormShortCode());
					$("ul.hc-fields").not("li").css({backgroundColor: '#DFDFDF'});
					$("ul.hc-fields").has("li").css({backgroundColor: '#FFFFFF'});
				}
			});
			$( "ul.hc-fields, ul.hc-fields li" ).disableSelection();
		});
	</script>

	<div class="clear"></div><div>
<?php
		echo '				<div class="shortcode-result">choose a button below when you have completed designing your search form</div>' . "\n";
		echo '				<input type="button" value="Make Shortcode" class="clear search-designer-shortcode-btn button-primary" />' . "\n";
		echo '				<input type="button" value="Make Page w/ Shortcode" class="clr headSearchSubmit search-form-creator-btn" />' . "\n";
		break;
	default:
		echo '				<input type="submit" value="Search" id="headSubmit" class="headSearchSubmit" />';
}
/*		nb: Filter on: default_content */
?>
			</div>
		</form>

		<script type="text/javascript">
			/** DEFAULT JAVASCRIPT **/
			jQuery(document).ready( function($){
				if (self.location.href.indexOf('admin.php?') < 1) {
					$('.scSlideDownWrap').css({display: 'none'});
					$('.scSlideDownOpts')
						.html('Show/Hide Advanced Options')
						.css({cursor: 'pointer'})
						.bind("click", function(e) {
							$('.scSlideDownWrap').slideToggle({duration:500, easing:"easeOutExpo"});//, complete:function(){ $('.scSlideDownOpts').html('Show/Hide Advanced Options'); }});
					});
				}
			});
			function getSearchShortcode(queryData) {
				var shortCode = "",
					newData = {}; // Note: newData's structure (not exactly like queryData): []
				// First pass through the Shortcode's given queryData, COMBINING multiple checkbox values into a CSV
				for (var i = 0; i < queryData.length; i++) {
					field = queryData[i];
					//isMultipleInput = jQuery('[name="' + field.name + '"]').first().is('[type="checkbox"]');
					if ( typeof newData[field.name] != 'undefined' ) {
						// Remove/prevent dups & append here
						newData[field.name] += ',' + field.value;
					} else {
						newData[field.name] = field.value.replace(/^,|,$/g, "");
					}
				}
				for (fieldName in newData) {
					field = {name: fieldName, value: newData[fieldName] };
					// TODO: Remove this hackish fix for allowing default behavior of mls="" => mls="DEN"
					if (field.value && field.value.length >=1 ) {
						if(field.value == "DEN" && field.name == "mls") { } else { 
							shortCode += " " + field.name + "=\"" + field.value.urlDecode().trim(",").htmlEncode() + "\"";
						}
					}
				}
				return '[homecardssearch' + shortCode + ' polygonCsv="' + (typeof polygonCsvData == 'undefined' ? '' : polygonCsvData) + '"]';
			}


			<?php
			switch ($atts['mode']) {
				case 'formbuilder':
				case 'builder':

			 ?>
			/** JAVASCRIPT BEGIN **/
			jQuery(document).ready( function($){
				var $form = jQuery('form<?php echo $frmClass; ?>');
				var getShortCodeWrapper = function($me) {return getSearchFormShortCode(jQuery($form).serialize()); };
				$('.search-designer-shortcode-btn').live('click', function() {hc_showShortcode(getShortCodeWrapper());});
				$('.search-form-creator-btn').live('click', function() {hc_createNewPageWithShortcode(getShortCodeWrapper());});
				
				var $availFields = jQuery(".hc-available-fields li");
				for (var i = 0; i < $availFields.length; i++) {
					if ( i >= 0 && i <= 4 ) {// Only add first few fields
						jQuery('.hc-fields-col1').append( jQuery($availFields[i]).remove() );
					}
				} 
			});

			


			function getSearchFormJson() {
				/* Build the special json/settings string to hold the form settings */
				var col1Csv = getFieldsDataAttrCSV(jQuery('.hc-fields-col1 li'), 'data-id', ','),
						col2Csv = getFieldsDataAttrCSV(jQuery('.hc-fields-col2 li'), 'data-id', ','),
						col3Csv = getFieldsDataAttrCSV(jQuery('.hc-fields-col3 li'), 'data-id', ',');
				return {layout: {col1: col1Csv, col2: col2Csv, col3: col3Csv} };
			}
			<?php /* getFieldsDataAttrCSV turns the specified field list (array of elements or jQuery object) into a csv based on the specified attribute */ ?>
			function getFieldsDataAttrCSV(fieldList, attrName, seperatorChar) {
				var fields = [];
				for (var i = 0; i < fieldList.length; i++) {
					if (attrName.substring(0, 4)=="data") {
						fields[fields.length] = jQuery(fieldList[i]).data(attrName.split('-')[1]);
					} else {
						fields[fields.length] = jQuery(fieldList[i]).attr(attrName);
					}
				}
				return fields.join((seperatorChar ? seperatorChar : ","));
			}
			function getSearchFormShortCode() {
				var searchJSON = getSearchFormJson();
				return '[homecards page="searchform" col1="' + hc_htmlEncode(unescape(searchJSON.layout.col1)) + '" col2="' + hc_htmlEncode(unescape(searchJSON.layout.col2)) + '" col3="' + hc_htmlEncode(unescape(searchJSON.layout.col3)) + '"]';
			}
			function hc_searchFormShortcode(event) {
				var $me = jQuery(this);
				var $form = jQuery('#<?php echo $atts["formId"]; ?>');
				var myShortcode = getSearchShortcode($form.serializeArray()).replace('>=', '').replace('&gt;=', '').replace('<=', '').replace('&lt;=', '');
				
				/* set global var to keep track of the last shortcode generated */
				window.lastShortcode = myShortcode;
				/* now update the Div.shortcode-result box */
				jQuery('.shortcode-result').text(myShortcode).css({margin: '6px', borderRadius: '6px', lineHeight: '28px'});
				/* Check for a global 'shortcode' event handler (sorta callback) */
				if (typeof window.onShortcode != 'undefined') {
					window.onShortcode(event, myShortcode);
				}
			}
			/** JAVASCRIPT END **/
			<?php
			break;
				case 'shortcode':
				case 'sc':
				case 'cannedsearch':
			 ?>
			/** JAVASCRIPT BEGIN  **/
			jQuery(document).ready( function($){
				var getSearchShortCode = function($me) {
					var myData = jQuery($me).parents('form:eq(0)').serializeArray();
					myData = $.merge([], myData, {polygonCsv: typeof polygonCsvData == 'undefined' ? '' : polygonCsvData});
					return getSearchShortcode(myData);
				};
				$('.search-shortcode-btn').live('click', function() {hc_showShortcode(getSearchShortCode(this));});
				$('.create-page-shortcode-btn').live('click', function() {hc_createNewPageWithShortcode(getSearchShortCode(this));});
				$('.hc-set-search-defaults').live('click', function(e) {
					e.preventDefault();
					var myQuery = hc_submitAjaxSearch(jQuery('form.hc-ajax'), 'query', null);
					jQuery.ajax({url: ajaxurl, type: 'post', data: {action: 'hc_save_default_criteria', query: JSON.stringify(myQuery)}, success: function(data) {
							// *** Do nothing!!!
							//if ( data && data.message ) { }
					}, dataType: 'json'});
		
				});
				return false;
				
			});

			/** JAVASCRIPT END **/
			<?php
					break;
				case 'page':
				case 'post':
				case 'pagebuilder':
				case 'postbuilder': ?>
			/** JAVASCRIPT BEGIN **/


			/** JAVASCRIPT END **/
			<?php
					break;
			}
			?>

			function hc_setDefaultContent(content, success) {
				return jQuery.post(ajaxurl, {action: 'hc_ajax_set_default_content', shortcode: content}, function(data) {
					if (data && typeof success == 'function') {success(data);}
				}, 'json');
			}
	
			function hc_showShortcode(myShortcode) {
				/* now update the Div.shortcode-result box */
				jQuery('.shortcode-result').text(myShortcode).css({margin: '6px', borderRadius: '6px', lineHeight: '28px'});
			}
			function hc_createNewPageWithShortcode(myShortcode) {
				window.lastShortcode = myShortcode;
				/* now update the Div.shortcode-result box */
				jQuery('.shortcode-result').html("One moment please... ").css({fontSize: '22px', fontWeight: 700, margin: '6px', lineHeight: '22px'});
				/* Next func will auto-redirect the user to create a new page in wordpress */
				hc_setDefaultContent(myShortcode, function() {jQuery('.shortcode-result').html("Opening New Page Editor..."); self.location.href = '/wp-admin/post-new.php?post_type=page';});
			}

		</script>

	<?php
		}
	}
	
	
	function hcBadgeShortCode($atts) {
		$atts = shortcode_atts( array(
			'title' => 'Get My Apps'
		), $atts );
		
		if (isset($atts['title'] )) {
			//these need to be defined anywhere used at the moment
			$agentData = getAgentData();
			$iphoneLink = $agentData->Products->mobile->iPhoneLink;
			$androidLink = $agentData->Products->mobile->androidLink;
			if ( strlen($agentData->MobileAccessCode) <= 6 ) {
					$fontSize = 24;
				}
				elseif ( strlen($agentData->MobileAccessCode) <= 9 ) {
					$fontSize = 18;
				}
				elseif ( strlen($agentData->MobileAccessCode) <= 10 ) {
					$fontSize = 14;
				}				
				elseif ( strlen($agentData->MobileAccessCode) <= 15 ) {
					$fontSize = 8;
				}
			?>
			<div class="widget_app_badge_wrap">
				<h3><?php echo $atts['title']; ?></h3>
				<p>Use My app to find homes on the go!</p>
				<p id="widget_app_badge_vip">VIP Access Code:</p>
				<h4 style="font-size: <?php echo $fontSize; ?>px;"><?php echo $agentData->MobileAccessCode; ?></h4>
				<p>Download My App for:</p>
				<?php if (isset($iphoneLink) && strlen($iphoneLink) > 5 ) { echo '<a href="' . $iphoneLink . '" id="widget_iphone" target="_blank">iPhone</a>'; } ?>
				<?php if (isset($androidLink) && strlen($androidLink) > 5 ) { echo '<a href="' . $androidLink . '" id="widget_android" target="_blank">Android</a>'; } ?>
			</div>
	<?php
		}
	}

	function hcMortShortCode($atts) {
		$atts = shortcode_atts( array(
			'title' => 'Loan Calculator'
		), $atts );
		
		if (isset($atts['title'] )) {
			//these need to be defined anywhere used at the moment
			//$agentData = getAgentData();
			wp_enqueue_script( 'hc-widget-mort-calc', plugin_dir_url( __FILE__ ) . 'js/mort-calc.js', array( 'jquery' ) );
			wp_enqueue_style( 'hc-widget-mort-calc', plugin_dir_url( __FILE__ ) . 'css/widgets.css' );	
			wp_enqueue_style( 'hc-shortcode', plugin_dir_url( __FILE__ ) . 'css/search.css' );	
		//}
		?>
				
				<form onsubmit="return false;">
					<div id="w-mort-wrap" class="short-code-wrap">
						<h3><?php echo $atts['title']; ?></h3>
						<div id="w-mort-child-left">
							<p><span class="w-mort-widget" for="price">Price: </span><input type="TEXT" name="price" value="200000" onblur="checkForZero(this)" onchange="checkForZero(this)" class="w-mort-input" /></p>
							<p><span class="w-mort-widget" for="dp">Down Payment: </span><input type="TEXT" name="dp" value="0" onchange="calculatePayment(this.form)" class="w-mort-input" /></p>
							<p><span class="w-mort-widget" for="ir">Interest Rate: </span><input type="text" name="ir" value="7.5" onblur="checkForZero(this)" onchange="checkForZero(this)" class="w-mort-input" /></p>
							<p><span class="w-mort-widget" for="term">Term: </span><input type="TEXT" name="term" value="30" onblur="checkForZero(this)" onchange="checkForZero(this)" class="w-mort-input" /></p>
						</div>
						<div id="w-mort-child-right">
							<p><span class="w-mort-widget" for="principle">Principle: </span><span id="w-mort-principle"></span></p>
							<p><span class="w-mort-widget" for="payments">Payments: </span><span id="w-mort-payments"></span></p>
							<p><span class="w-mort-widget w-mort-output" for="pmt">Monthly Payment: </span><span id="w-mort-pmt"></span></p>
						</div>
					<input class="w-mort-btn widget-float" type="BUTTON" name="cmdCalc" value="Calculate" onclick="cmdCalc_Click(this.form)">
					<div class="clear"></div>
					</div>					
				</form>
	<?php
		}
	}
