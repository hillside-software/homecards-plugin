<?php
include_once( dirname(__FILE__) . '/short-codes-2.php');

if (!isset($_SESSION)) { session_start(); }

if (empty($jsonFields)) { $jsonFields = ''; }
if (empty($searchFieldsJson)) { $searchFieldsJson = ''; }


function renderSettingsScript() {
	hc_reset_global_variable_cache();

	$siteToken = get_option('wp_hc_token', '');
	$currentWebID = get_option('wp_hc_webid', -1);

	if (!isset($hc_proxy)) {$hc_proxy = new HomeCardsProxy();}

	//echo "<!-- Token: $siteToken -->\n";
	//echo "<!-- currentWebID: $currentWebID -->\n";
		if (strlen($siteToken) < 1 || $currentWebID < 1000) {
			showAgentLoginForm();
		} else {
			if (!isset($jsonFields) || strlen($jsonFields) == 0) { $jsonFields = $hc_proxy->getSearchFieldsCSV();}
			if (!isset($searchFieldsJson) || strlen($searchFieldsJson) == 0) { $searchFieldsJson = $hc_proxy->getSearchFieldsJSON();}
		}
	if (empty($jsonFields) || strpos($jsonFields, '<') > -1) {$jsonFields = "{}";}
	?>
		<script type="text/javascript">
			var searchFields = <?php echo(($jsonFields)); ?>;
			var searchFieldsJson = <?php if (isset($searchFieldsJson)) {echo($searchFieldsJson);} else { echo '{}'; } ?>;
		</script>
		<script type="text/javascript">
			var wID = '<?php echo get_option('wp_hc_webid', '-1'); ?>';
			jQuery(document).ready(function($) {
				getServerSettings();

				jQuery(".hc-login-form").bind('submit', function(event) {
					getAccountInfo(jQuery("#hc_login").val(), jQuery("#hc_pass").val());
					jQuery('.hc_login .hc_ajax_msg').html('Please wait... Logging in...');
					return false;
				});
				
				jQuery("form#hc_form").submit(function(event) {
					if (jQuery("#hc_login").length >= 1) {
						return false;
					}
					updateFormValues();
					if (jQuery("#hc_siteurl").val().length >= 5) {
						/** SUCCESS **/
					} else {
						alert("Please enter a valid URL");
						jQuery("#hc_siteurl").focus();
						return false;
					}
					
				});
				
				jQuery("li.hc_FieldRow input").live("change mouseup keyup mouseout", function(event) {
					updateFormValues();
				});
				
				jQuery('.colorbox-inline').colorbox({inline:true, width:"85%"});
			});
	
	
			function getAccountInfo(loginid, password) {
				if (!loginid || loginid.length < 2) { return false; }
				jQuery.post(ajaxurl, {action: 'hc_agent_login', login: loginid, pass: password}, function(data) {
					if (data && data.length > 0 && data.toLowerCase().indexOf('error') > -1) {
						jQuery('.hc_login .hc_ajax_msg').html(data);
						return false;
					}
					if (data && data.length > 0) {
						jQuery('.hc_login .hc_ajax_msg').html('Successfully logged in...');
						self.location.reload();
						//jQuery('.shortcode_wrap').hide();
					}
				});
				
			}
			function getServerSettings() {
				// Not needed: jQuery.post(ajaxurl, {action: 'hc_get_fields'}, function(data) {
					/* Load fields from HC Proxy Response Data */
					var required = ",Area,Beds,Baths,PriceFrom,PriceTo,".toLowerCase();
					var currentFieldsList = ",<?php echo get_option('wp_hc_search_form1', '') ?>,".toLowerCase();
					var targetList = jQuery("#hc_search_available_fields");
					
					if (typeof searchFields != 'undefined' && typeof searchFields.NameCaptionList != 'undefined') {
						var fields = searchFields.NameCaptionList.split("|");
						for (var i = 0; i < fields.length; i++) {
							var t = fields[i].split(",");
							var readonly = "";
							var chk = "";
							if (required.indexOf(t[0].toLowerCase()) > 0) {readonly = " checked readonly='readonly' disabled ";}
							if (currentFieldsList.indexOf(t[0].toLowerCase()) > 0) {chk = " checked ";}
							//chk
							targetList.append('<li class="hc_FieldRow"><input type="checkbox" ' + readonly + ' ' + chk + ' value="' + t[0] + '" id="chk_' + t[0] + '" /> <label for="chk_' + t[0] + '">' + t[1] + '</label></li>\n');
						}
						/* MAKE SORTABLE */
						targetList.sortable({ axis: 'y', change: function(event, ui) { } });
					} else {
						if (wID.length >= 5) {
							alert("Warning: Please try again later or check for an updated version of this plugin.");
						}
					}
				//});
			}
			
			function updateFormValues() {
				var chkBoxes = jQuery("li.hc_FieldRow input");
				var fieldList = "";
				/* build list to send to server */
				for (var i = 0; i < chkBoxes.length; i++) {
					if (chkBoxes.eq(i).attr("checked")) {
						fieldList += chkBoxes.eq(i).val() + ",";
					}
				}
				jQuery("#hc_search_fields").val(fieldList);
			}
	
			jQuery(function($) {
				jQuery('#tabs-2 form').submit(function(e) {
					var $myForm = jQuery(this);
					var req = $.ajax({url: self.location.href, type: 'post', dataType: 'json', data: jQuery(this).serializeArray()})
					.done(function(data) {
						//console.log(jQuery(this));
						//console.log($myForm);
						//console.log(data);
												
						//if (typeof data != 'undefined') {
							//$myForm.find('.button-primary');
						//}
						jQuery('.button', $myForm).attr("disabled", true).after("<a href='"+ data.url +"' target='_blank' style='margin-left: 20px;'>Click Here to View this Page!</a>");
						return true;
					});
					
					
					return false;
				});
			});

			function saveUrlPathSettings() {
				var req = $.ajax({url: self.location.href, type: 'post', dataType: 'json', data: jQuery(this).serializeArray()
				}).done(function(data) {
					//console.log(jQuery(this));
											
					jQuery('.button', $myForm).attr("disabled", true).after("<a href='"+ data.url +"' target='_blank' style='margin-left: 20px;'>Click Here to View this Page!</a>");
					return true;
				});

			}
		</script>

<?php
}// END OF: renderSettingsScript()

function showAgentLoginForm() { ?>
	<style>
		.login_form_list { margin: 0; padding: 0; display: block; }
		.hc_login { margin: 20px 0 0; padding: 10px 20px 20px; }
		#hc_login_btn { float: right;}
		
		ul.login_form_list li label { width: 30%; display: inline-block;}
		ul.login_form_list li label input { display: block;}
	</style>
	<form method="post" class="hc-login-form" autocomplete="off">
		<div class="hc_login shortcode_wrap" style="width: 30%; display: block; margin: 20px auto;">
			<div id="icon-users" class="icon32"><br></div>
			<h2 style="color: #21759B;">Please Login to HomeCards</h2>
			<ul class="login_form_list">
				<li><label for="hc_login">Login ID: </label><input type="text" name="hc_login" id="hc_login" value="<?php echo get_option('wp_hc_login', '') ?>" style="width: 100%; height: 1.8em; font-size: 1.3em;" /></li>
				<li><label for="hc_pass">HomeCards Password: </label><input type="password" name="hc_pass" id="hc_pass" value="" style="width: 100%; height: 1.8em; font-size: 1.3em;" /></li>
			</ul>
			<div class='hc_ajax_msg hc_important'>
						
			</div>
			<input name="hc_login_btn" id="hc_login_btn" value="Login" type="submit" class="button-primary" style="width:100px; height: 1.8em;" />
			<div class="clear"></div>
		</div>
	</form>
<?php // Ending curlybrace for showAgentLoginForm
	} 

function hc_render_settings_form($userMsg) {
	/* Get the Available Fields */
	$hc_proxy = new HomeCardsProxy();
	//$GLOBAL['rootPath'];
	if ( !function_exists("getAgentData") ) {
		function getAgentData() {
			$hc_proxy = new HomeCardsProxy();
			$json = $hc_proxy->getAccountJson();
			return json_decode($json);
		}
	}	
	//GRAB Agent Data 
	// Limited Data Set: $agentData = getAgentData();
	$agentData = hc_get_agent_json();
	/* Check for an Error Response */
	if ( isset( $agentData->Error) && $agentData->Error == "true") {
		update_option('wp_hc_webid', -1);
		update_option('wp_hc_token', '');
		echo "<h2 class='hc-warn hc-alert'>" . $agentData->Message . "</h2>\n";
		//showAgentLoginForm();
		//renderSettingsScript();
		//return "";
	}
	
	//var_dump($agentData);
	if (!isset($jsonFields) || strlen($jsonFields) == 0) { $jsonFields = $hc_proxy->getSearchFieldsCSV();}
	if (!isset($searchFieldsJson) || strlen($searchFieldsJson) == 0) { $searchFieldsJson = $hc_proxy->getSearchFieldsJSON();}
	/*$jsonFields = $hc_proxy->getSearchFieldsCSV();
	$searchFieldsJson = $hc_proxy->getSearchFieldsJSON();*/	//var_dump($jsonFields);
	if (strlen($jsonFields) < 2) { $jsonFields = '{}';}
	if (strlen($searchFieldsJson) < 2) { $searchFieldsJson = '{}';}

	wp_enqueue_script( 'colorbox', plugin_dir_url( __FILE__ ) . 'js/jquery.colorbox.min.js', array( 'jquery' ) );
	wp_enqueue_style( 'colorbox', plugin_dir_url( __FILE__ ) . 'css/colorbox/colorbox.css');
	//Script for Copy to clipboard (3 fallbacks)
	//wp_enqueue_script( 'zclip', plugin_dir_url( __FILE__ ) . 'js/zclp/jquery.zclip.min.js', array( 'jquery' ) );
	
	wp_deregister_script( 'jquery' );
	wp_deregister_script( 'jquery-ui' );
	wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js');
	wp_register_script( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js');
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui' );

?>
<style type="text/css">
	.hc_FieldRow {cursor: move; font-size: 1.3em;}
	div.hc_tips {margin: 0px 0px 50px 0px;}
	div.hc_tips h2 {margin: 4px 0px;}
	ol.hc_search_available_fields { margin: 10px 0px }
	.hc_admin_settings_form div {line-height: 32px;}
	.hc_important {font-weight: 700; font-size: 1.2em; }
	.clr {clear: both; }
	.colorbox-content {display: none;}
	#cboxLoadedContent {background: #fff !important;}
	.shortcode-result { font-size: 16.5px; font-weight: 700; line-height: 18.5px; }
	.hc_settings_table td { vertical-align: middle; font-size: 100%;}
	.manage-column { font-family: Arial, Verdana, Tahoma; }
	.shortcode_wrap { margin: 0 0 15px; padding: 1px 25px; font-size: 14px; border: 1px solid #A3A3A3; background: #F0F0EE; border-radius: 5px; box-shadow: inset 1px 1px 0 #fff, inset -1px -1px 0 #d9d9d9; text-shadow: 1px 1px 0 #fff;}
</style>
<div style="clear: both;">
	<?php 
	if (isset($userMsg) || isset($_POST['login']) && strlen($_POST['login']) < 1) { 
		echo $userMsg;
	}
?>
</div>
<script>
	jQuery.ajaxSetup({cache: false});
</script>
	<?php
		if (intval(get_option('wp_hc_webid', '0')) > 1000) {
	?>	
	<script type="text/javascript">

			function ajaxAsyncFix() {
				self.location.reload();
			}
			
			jQuery(document).ready( function($) {
				$('.ajaxsync').bind("mouseup", function(e){
					ajaxAsyncFix();
					$('.login_wrap').hide();
					ajaxAsyncFix();
				
				});
				$('input.hc-track-changes, textarea.hc-track-changes, select.hc-track-changes, .hc-track-changes input, .hc-track-changes textarea, .hc-track-changes select').bind('change focus blur', function(e) {
					$(this).data('modified', true).addClass('hc-is-modified');
				});
				
				
			});
			function onSubmit_UpdateAgent(opts) {
				var jsonUpdates = {};
				jQuery('.hc-is-modified').each(function(index, obj) {
					jsonUpdates[jQuery(this).attr('id').replace('txt', '')] = jQuery(this).val();
				});
				// now send only changed data to sthe server!
				jQuery.post(ajaxurl, jQuery.extend({action: 'hc_json_updates', JSON: JSON.stringify(jsonUpdates)}, jsonUpdates), function(data) {
					if (data) {window.agentUpdateResponse = data;}
					if (data && opts != null && opts.success) {opts.success(data);}
					if (data && typeof data.Message != 'undefined') {alert(data.Message);}
				}, 'json');
				return false;
			}
	</script>
	<style type="text/css">
		#hc_settingsWrap {
			margin: 0;
			padding: 0;
			display: block;
			width: 99%;
			clear: both;
		}
		#hc_settingContainer {
			margin: 0;
			padding: 0;
		}
		ul#hc_settingContainer li {
			width: 33%;
			display: inline-block;
			margin: 0;
			padding: 0;
			vertical-align: middle;
		}
		.hc_settingsInner {
			margin: 1em;
		}
	</style>
	<div id="hc_settingsWrap">
		<ul id="hc_settingContainer">
			<li>
				<div class="hc_settingsInner">
					<h1>HomeCards WordPress Settings</h1>
					<p></p>
				</div>
			</li>
			<li>
				<div class="hc_settingsInner">
				</div>
			</li>
			<li>
				<div class="hc_settingsInner">
				<a href="http://www.hillsidesoftware.com/?Src=WP-Plugin" target="_blank">
					<?php echo '<img src="'. plugins_url() .'/homecards-plugin/images/hc-wordpress-logo-sml.png" style="float: right;" />'; ?>
				</a>
				</div>
			</li>
		</ul>
	<?php
	//if(!function_exists(hc_addActionForScripts())) {
	//		wp_enqueue_script( 'tipsy', plugin_dir_path( __FILE__ ) . 'js/jquery.tipsy.min.js', array( 'jquery', 'jquery-ui' ) );
	//		wp_enqueue_style( 'tipsy', plugin_dir_path( __FILE__ ) . 'css/tipsy.css' );
	//add_action ( 'init', 'hc_addActionForScripts');
	//};
	
	?>
	</div>	<div class="login_wrap">


	<script type="text/javascript">
		
		jQuery(document).ready( function($) {
			$('.tipsyWrapper a[rel=tipsy]').tipsy({
				gravity: 'w'
			});
		});
		
	</script>
	
	<style>
		
		.tipsyWrapper a { margin: 0 0 0 8px; display: inline-block; line-height: 28px; height: 29px; width: 29px; background: <?php echo 'url("'. plugin_dir_url( __FILE__ ) . 'images/tool-tip-icon.png") no-repeat;'; ?>}
		.tipsyWrapper a:hover { cursor: pointer;}
		
		.specialInputs { 
			width: 350px;
			height: 1.8em;
			font-size: 1.3em;
		}	
	</style>
	<!-- <?php /*var_dump($agentData);*/ ?> -->
	<form method="post" onsubmit="return onSubmit_UpdateAgent()">
		<table class="wp-list-table widefat hc_settings_table hc-custom-wide-fat hc-track-changes" style="width: 99%; margin-bottom: 25px;">
			<thead>
				<tr>
					<th colspan="2">
						<h2 style="font-family: Arial, Tahoma, Verdana; text-shadow: 1px 1px 0 #fff; ">
							HomeCards Account Information
							<span class="tipsyWrapper">
								<a rel="tipsy" title="These settings can also be updated from your HomeCards Agent Backend. You can also set and manage many of your HomeCards Options here, keeping things tidy and consolidated to one platform as much as possible.">&nbsp;</a>
							</span>
						</h2>
					</th>
				</tr>
			</thead>
			<tr>
				<td colspan="2"><p></p></td>
			</tr>
			<tbody>
				<tr><td style="width: 20%;">
						<p>Agent Name:</p>
				</td><td>
						<input type="text" value="<?php echo $agentData->AG; ?>" id="txtAG" data-modified="false" class="hc-track-changes specialInputs" />
					<span class="tipsyWrapper">
						<a rel="tipsy" title="Set as your Full Name">&nbsp;</a>
					</span>
				</td></tr>
				<tr><td style="width: 20%;">
						<p>Company Name:</p>
				</td><td>
						<input type="text" value="<?php echo $agentData->Company; ?>" id="txtCompany" data-modified="false" class="hc-track-changes specialInputs" />
					<span class="tipsyWrapper">
						<a rel="tipsy" title="Set as your Company Name">&nbsp;</a>
					</span>					
				</td></tr>
				<tr><td style="width: 20%;">
						<p>Agent Code:</p>
				</td><td>
						<span><?php echo $agentData->SiteAgCode; ?></span>
				</td></tr>
				<tr><td style="width: 20%;">
						<p>Broker Code:</p>
				</td><td>
						<?php echo $agentData->SiteBkrCode; ?>
				</td></tr>
				<tr><td style="width: 20%;">
						<p>Direct Number:</p>
				</td><td>
						<input type="text" value="<?php echo $agentData->Direct; ?>" id="txtDirect" data-modified="false" class="hc-track-changes specialInputs" />
					<span class="tipsyWrapper">
						<a rel="tipsy" title="You may use whatever number you prefer, and you always can choose what to show your visitors.">&nbsp;</a>
					</span>					
				</td></tr>
				<tr><td style="width: 20%;">
						<p>Cell Number:</p>
				</td><td>
						<input type="text" value="<?php echo $agentData->Pager; ?>" id="txtPager" data-modified="false" class="hc-track-changes specialInputs" />
					<span class="tipsyWrapper">
						<a rel="tipsy" title="You may use whatever number you prefer, and you always can choose what to show your visitors.">&nbsp;</a>
					</span>					
				</td></tr>
				<tr><td style="width: 20%;">				
						<p>Office Number:</p>
				</td><td>
						<input type="text" value="<?php echo $agentData->Office; ?>" id="txtOffice" data-modified="false" class="hc-track-changes specialInputs" />
					<span class="tipsyWrapper">
						<a rel="tipsy" title="You may use whatever number you prefer, and you always can choose what to show your visitors.">&nbsp;</a>
					</span>					
				</td></tr>
				<tr><td style="width: 20%;">
						<p>Fax Number:</p>
				</td><td>
						<input type="text" value="<?php echo $agentData->Fax; ?>" id="txtFax" data-modified="false" class="hc-track-changes specialInputs" />
					<span class="tipsyWrapper">
						<a rel="tipsy" title="You may use whatever number you prefer, and you always can choose what to show your visitors.">&nbsp;</a>
					</span>					
				</td></tr>
				<tr><td style="width: 20%;">
						<p>Slogan:</p>
				</td><td>
						<input type="text" value="<?php echo $agentData->Slogan; ?>" id="txtSlogan" data-modified="false" class="hc-track-changes specialInputs" />
					<span class="tipsyWrapper">
						<a rel="tipsy" title="This can be anything, from your favorite quite to your personal slogan.">&nbsp;</a>
					</span>					
				</td></tr>
				<tr><td style="width: 20%;">
						<p>Slogan Url:</p>
				</td><td>
						<input type="text" value="<?php echo $agentData->SloganUrl; ?>" id="txtSloganUrl" data-modified="false" class="hc-track-changes specialInputs" />
					<span class="tipsyWrapper">
						<a rel="tipsy" title="You can input the URL you'd like to link with your slogan">&nbsp;</a>
					</span>					
				</td></tr>
				<tr><td style="width: 20%;">
						<p>Board:</p>				
				</td><td>
						<?php echo $agentData->Board; ?>
				</td></tr>
				</tbody>
			</table>


	<table class="wp-list-table widefat hc_settings_table hc-track-changes" style="width: 99%; margin-bottom: 25px;">
		<thead>
			<tr>
				<th colspan="3">
					<h2 style="font-family: Arial, Tahoma, Verdana; text-shadow: 1px 1px 0 #fff; ">Advanced HomeCards Settings</h2>
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td style="width: 20%;"><label for="hc_webid">HomeCards Account ID (aka wID):</label></td>
				<td><span style="color: #858585; font-size: 18px; line-height: 29px; font-weight: 700;"><?php echo get_option('wp_hc_webid', '') ?></span></td>
				<td><span class="tipsyWrapper"><a rel="tipsy" title="Your HomeCards wID is Unique to your Account. It is used when generating security tokens" style="position: relative; top: 8px;"></a></span></td>
			</tr>
			<tr>
				<td style="width: 20%;"><label for="hc_siteurl">HomeCards Site URL:</label></td>
				<td><input type="text" name="hc_siteurl" id="hc_siteurl" value="<?php echo $agentData->SiteLink ?>" class="hc-track-changes" style="width: 350px; height: 1.8em; font-size: 1.3em;" /></td>
				<td>
					<span class="tipsyWrapper">
						<a rel="tipsy" title="Set as your website URL ie: www.example.com">&nbsp;</a>
					</span>
				</td>
			</tr>
			<tr>
				<td style="width: 20%;">
					<label for="hc_property_details_path">Property Details Path:</label>
				</td>
				<td>
					<input type="text" name="hc_property_details_path" id="hc_property_details_path" value="<?php echo get_option('wp_hc_property_details_path', 'property-details') ?>" style="width: 200px; height: 1.8em; font-size: 1.3em;" />
					<input id="hc_save_path_changes" value="Check &amp; Update" class="button-primary" type="button" />
				</td>
				<td><span class="tipsyWrapper"><a rel="tipsy" title="This will override the default property details' page's path.">&nbsp;</a></span></td>
			</tr>
			<tr>
				<td style="width: 20%;"><label>Signup HTML Message:</label></td>
				<td valign="top">
					<textarea cols="40" rows="12" name="hc_custom_signup_html" id="hc_custom_signup_html"><?php echo get_option('hc_signup_html', ''); ?></textarea>
				</td>
				<td><span class="tipsyWrapper"><a rel="tipsy" title="This changes the text potential leads see when signing up. LEAVE BLANK FOR DEFAULT!" style="position: relative; top: 8px;"></a></span></td>
			</tr>
		</tbody>
	</table>


		<input name="hc_save" value="Update" class="button-primary" type="submit" style="font-size: 24px !important; line-height: 28px !important;" />

	</form>
	<br />


	<form method="post">
	<?php 
	function hcRemoveAgent() {
		delete_option('wp_hc_agentdata');
		delete_option('wp_hc_token');
		delete_option('wp_hc_webid');
	}
	$delOpts = -1;
	if ( isset($_REQUEST['deleteoptions']) ) { $delOpts = intval($_REQUEST['deleteoptions']); }
	if ($delOpts == 1) {
		hcRemoveAgent();
	}
	?>
	<label id="confirmDeleteOptsCheckLbl" for="confirmDeleteOptsCheck">
	<input type="checkbox" name="deleteoptions" id="confirmDeleteOptsCheck" value="1" <?php if ( $delOpts == 1 ) { echo 'checked'; } ?> /> Delete Account Settings</label>
	<input type="submit" name="confirm_delete" class='confirm_delete' value="Confirm Delete" class="button ajaxsync" />	
		<span class="tipsyWrapper">
			<a rel="tipsy" title="This allows you to change your HomeCards Account Easily. Most likely only useful to Web Developers using the Plug-in.">&nbsp;</a>
		</span>
	<p id="confirmDeleteOpts" style="display: none;"><strong style="color: red; font-size: 11px;">This will DELETE your account, make sure you want to do this.</strong></p>
	<script type="text/javascript">
		jQuery(document).ready( function($){
			$("#confirmDeleteOptsCheckLbl, #confirmDeleteOptsCheck").bind('mouseup change', function() {
				var confirmYN = jQuery('#confirmDeleteOptsCheck').attr('checked');
				$('#confirmDeleteOpts').css('display', (confirmYN ? '' : 'none') );
			});				
			$(".confirm_delete").bind('click', function() {					
				var confirmYN = jQuery('#confirmDeleteOptsCheck').attr('checked');
				if (!confirmYN) { return false; }
			});
		});
	</script>
	</form>
	<!-- old dan commented out need $args <div>
		Disable CSS: <input type="checkbox" name="hc_disablecss" id="hc_disablecss" value="1" <?php if (intval(get_option('wp_hc_disablecss', '0')) > 0) {echo ' checked '; } ?>/>
	</div>-->
	<!--			<a href="#hc_shortcode_builder_window" class="colorbox colorbox-inline">Create Canned Search Page or Shortcode</a>		-->
	<div class='hc-help-info'>
		<b>Note:</b> You can improve the HomeCards Plugin's speed by installing one of the following plugins: WP Super Cache or WP Total Cache. 
	</div>
</div>

<?php
	}

	// call on every request (gets skipped if being called from hc_render_settings_form)
	renderSettingsScript();
}


function hc_canned_search_wizard() {
	echo "<div class='clr' id='hc_canned_search_window hc-autocomplete'>\n";
	hcSearchShortCode(array('formId' => 'cannedSearch',
		'formClassName' => 'hc-canned-search hc-autocomplete hc-ajax',
		'formTitle' => 'Create a \'Canned Search\' Easily!',
		'pageInfoHtml' => '',
		'mode' => 'shortcode'));
	echo "</div>\n";
}

function hc_render_search_designer() {
	echo "<div class='clr' id='hc_shortcode_builder_window'>\n";
	hcSearchShortCode(array('formId' => 'shortcodeBuilder',
		'showMap' => false,
		'formClassName' => 'hc-shortcode-builder',
		'formTitle' => 'Create a Custom Search Form to Use Anywhere on Your Site!',
		'pageInfoHtml' => 'Please drag your desired Form Field Box\'s into the 3 available columns to design your search form.',
		'mode' => 'builder'));
	echo "</div>\n";
}



