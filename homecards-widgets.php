<?php 



	if ( !function_exists("getAgentData") ) {
	function getAgentData() {
		$hc_proxy = new HomeCardsProxy();
		$json = $hc_proxy->getAccountJson();
		return json_decode($json);
	}
	}
	

if (!function_exists('getOptionsFromSelect')) {
function getOptionsFromSelect($html) {
	$rows = explode("\n",$html);
	foreach ( $rows as $r ) {
		if (stripos($r,'<option') > -1) {
			echo "$r\n";
		}
	}
}
}

if (!function_exists('countHtmlInputs')) {
	function countHtmlInputs($html) { return preg_match_all('/\<input/', $html); }
}
if (!function_exists('countHtmlSelects')) {
	function countHtmlSelects($html) { return preg_match_all('/\<select/', $html); }
}

if (!function_exists('addIdAttr')) {
	function addIdAttr($html, $idString) {
		$inputSelects = array( '/\<input/', '/\<select/' );
		return preg_replace($inputSelects, '$0 id="' . $idString . '" ', $html);
	}
}
if (!function_exists('addClass')) {
	function addClass($html, $classString) {
		$inputSelects = array( '/\<input/', '/\<select/' );
		return preg_replace($inputSelects, '$0 class="' . $classString . '" ', $html);
	}
}
if (!function_exists('addClassToLabel')) {
	function addClassToLabel($html, $classString) {
		$inputSelects = array( '/\<label/' );
		return preg_replace($inputSelects, '$0 class="' . $classString . '" ', $html);
	}
}
		//needed fields to make work as my html example:
		
if (!function_exists('generateForm')) {
			function generateForm($fieldNames) {
				$html = '';
			if (!isset($htmlFieldTable) ){ $htmlFieldTable = json_decode(hc_get_fields_html());}
			if (!isset($searchFields) ){ $searchFields = json_decode(hc_get_fields());}
				foreach ($fieldNames as $f) {
					$html .= $htmlFieldTable->{$f} . "<br />\n";
					//$html .= $searchFields
				}
				return $html;
			}
}
		//var_dump($htmlFieldTable);
		
/**********************************
 *  Agent Widget
 **********************************/

	
//CONSTRUCT Class
class HC_Agent_Widget extends WP_Widget {
	function HC_Agent_Widget() {
		$widget_ops = array('classname' => 'widget_hc_agent', 'description' => 'Agent Information and Social Networking Links' );
		//WIDGET Name
		$this->WP_Widget('hc_agent', 'HomeCards Agent ID', $widget_ops, array('classname' => 'DanTest'));
	}

	//WIDGET Args
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		echo $before_widget;
		//WIDGET Database Checks
		$title = empty($instance['title']) ? ' ' : apply_filters('widget_agent_title', $instance['title']);
		//$ag_img = empty($instance['ag_img']) ? ' ' : apply_filters('widget_agent_ag_img', $instance['ag_img']);
		//$co_img = empty($instance['co_img']) ? ' ' : apply_filters('widget_agent_co_img', $instance['co_img']);
		$img_switch = empty($instance['img_switch']) ? ' ' : apply_filters('widget_agent_img_switch', $instance['img_switch']);
		$direct_no = empty($instance['direct_no']) ? ' ' : apply_filters('widget_agent_direct_no', $instance['direct_no']);
		$cell_no = empty($instance['cell_no']) ? ' ' : apply_filters('widget_agent_cell_no', $instance['cell_no']);
		$email_ad = empty($instance['email_ad']) ? ' ' : apply_filters('widget_agent_email_ad', $instance['email_ad']);
		$company_no = empty($instance['company_no']) ? ' ' : apply_filters('widget_agent_company_no', $instance['company_no']);
		$fb_name = empty($instance['fb_name']) ? ' ' : apply_filters('widget_agent_fb_name', $instance['fb_name']);
		$tw_name = empty($instance['tw_name']) ? ' ' : apply_filters('widget_agent_tw_name', $instance['tw_name']);
		$ln_name = empty($instance['ln_name']) ? ' ' : apply_filters('widget_agent_ln_name', $instance['ln_name']);
		//GRAB Agent Data 
		$agentData = getAgentData();
		
		?>
			
			<div class="widget_wrap" itemscope itemtype="http://schema.org/RealEstateAgent">
			<?php if ( !empty( $title ) && (strlen($title) > 1 )) { echo $before_title . $title . $after_title; }; ?>
			<ul class="widget_area">
				<li>
					<?php 
								if ($img_switch == "photo") {
								echo '<img itemprop="image" src="http://myhomecards.com' . $agentData->PhotoPath120 . '" />';
							} elseif ($img_switch == "logo") {
								echo '<img itemprop="image" src="http://myhomecards.com' . $agentData->LogoPath120 . '" />';
							}
						?>
				</li>
				<li class="widget_aside">
					<p class="widget_text widget_text_title" itemprop="branchOf" ><?php echo $agentData->Company; ?></p>
					<?php if ($cell_no == "1") { echo '<p class="widget_text" itemprop="telephone">C: ' . $agentData->Cell . '</p>'; } ?>
					<?php if ($direct_no == "1") { echo '<p class="widget_text" itemprop="telephone">D: ' . $agentData->Direct . '</p>'; } ?>
					<?php	if ($company_no == "1") { echo '<p class="widget_text" itemprop="telephone">O: ' . $agentData->Office . '</p>'; } ?>
					<?php if ( isset( $fb_name ) && (strlen( $fb_name ) > 1 ) || isset( $ln_name ) && (strlen( $ln_name ) > 1 ) || isset( $tw_name ) && (strlen( $tw_name ) > 1 ) ) { echo '<p class="widget_text widget_text_title widget_social_title">Stay Connected</p>'; }?>
					<?php if ( isset( $fb_name ) && (strlen( $fb_name ) > 1 ) ) { echo '<span class="widget_imgs"><a id="fb_icon" href="http://www.facebook.com/'. $fb_name .'" target="_blank" title="Connect with me on Facebook"></a></span>'; }; ?>				
					<?php if ( isset( $tw_name ) && (strlen( $tw_name ) > 1 ) ) { echo '<span class="widget_imgs"><a id="tw_icon" href="http://www.twitter.com/'. $tw_name .'" target="_blank" title="Connect with me on Twitter"></a></span>'; }; ?>				
					<?php if ( isset( $ln_name ) && (strlen( $ln_name ) > 1 ) ) { echo '<span class="widget_imgs"><a id="ln_icon" href="'. $ln_name .'" target="_blank" title="Connect with me on LinkedIn"></a></span>'; }; ?>				
				</li>
			</ul>
			</div>
			
		<?php
		 
		echo $after_widget;
	}
	//WIDGET Save
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		//$instance['ag_img'] = strip_tags($new_instance['ag_img']);
		//$instance['co_img'] = strip_tags($new_instance['co_img']);
		$instance['img_switch'] = strip_tags($new_instance['img_switch']);
		$instance['direct_no'] = strip_tags($new_instance['direct_no']);
		$instance['cell_no'] = strip_tags($new_instance['cell_no']);
		$instance['email_ad'] = strip_tags($new_instance['email_ad']);
		$instance['company_no'] = strip_tags($new_instance['company_no']);
		$instance['fb_name'] = strip_tags($new_instance['fb_name']);
		$instance['tw_name'] = strip_tags($new_instance['tw_name']);
		$instance['ln_name'] = strip_tags($new_instance['ln_name']);
		return $instance;
	}

	//WIDGET Admin Form
	function form($instance) {
		//CREATE ARGS TO EXTRACT
		$instance = wp_parse_args( (array) $instance, array( 
		'title' => 'Agent Name', 'img_switch' => 'photo', 'direct_no' => '', 'cell_no' => '', 'email_ad' => '', 'company_no' => '', 'fb_name' => '', 'tw_name' => '', 'ln_name' => '' ) );
		$title = strip_tags($instance['title']);
		//$ag_img = strip_tags($instance['ag_img']);
		//$co_img = strip_tags($instance['co_img']);
		$img_switch = strip_tags($instance['img_switch']);
		$direct_no = strip_tags($instance['direct_no']);
		$cell_no = strip_tags($instance['cell_no']);
		$email_ad = strip_tags($instance['email_ad']);
		$company_no = strip_tags($instance['company_no']);
		$fb_name = strip_tags($instance['fb_name']);
		$tw_name = strip_tags($instance['tw_name']);
		$ln_name = strip_tags($instance['ln_name']);
?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr__($title); ?>" /></label></p>
			<p><input type="checkbox" id="<?php echo $this->get_field_id('direct_no'); ?>" name="<?php echo $this->get_field_name('direct_no'); ?>" value="1" <?php if ( $direct_no == "1" ) { echo 'checked'; } ?>/><label for="<?php echo $this->get_field_id('direct_no'); ?>"> Show Direct Number</label></p>
			<p><input type="checkbox" id="<?php echo $this->get_field_id('cell_no'); ?>" name="<?php echo $this->get_field_name('cell_no'); ?>" value="1" <?php if ( $cell_no == "1" ) { echo 'checked'; } ?>/><label for="<?php echo $this->get_field_id('cell_no'); ?>"> Show Cell Number</label></p>
			<p><input type="checkbox" id="<?php echo $this->get_field_id('company_no'); ?>" name="<?php echo $this->get_field_name('company_no'); ?>" value="1" <?php if ( $company_no == "1" ) { echo 'checked'; } ?>/><label for="<?php echo $this->get_field_id('company_no'); ?>"> Show Company Number</label></p>

			<p><input type="radio" id="<?php echo $this->get_field_id('img_switch'); ?>_photo" name="<?php echo $this->get_field_name('img_switch'); ?>" value="photo" <?php if ( $img_switch == "photo" ) { echo 'checked'; } ?>/><label for="<?php echo $this->get_field_id('img_switch'); ?>_photo"> Use Photo</label>
			<input type="radio" id="<?php echo $this->get_field_id('img_switch'); ?>_logo" name="<?php echo $this->get_field_name('img_switch'); ?>" value="logo" <?php if ( $img_switch == "logo" ) { echo 'checked'; } ?>/><label for="<?php echo $this->get_field_id('img_switch'); ?>_logo"> Use Logo</label></p>

			<p><label for="<?php echo $this->get_field_id('fb_name'); ?>">Facebook Username: <input class="widefat" id="<?php echo $this->get_field_id('fb_name'); ?>" name="<?php echo $this->get_field_name('fb_name'); ?>" type="text" value="<?php echo esc_attr__($fb_name); ?>" /></label></p>
			<p><em>Note: If you haven't set your Username through facebook <a href="http://www.facebook.com/username/" target="_blank">Click here</a>.</em></p>
			<p><label for="<?php echo $this->get_field_id('tw_name'); ?>">Twitter Username: <input class="widefat" id="<?php echo $this->get_field_id('tw_name'); ?>" name="<?php echo $this->get_field_name('tw_name'); ?>" type="text" value="<?php echo esc_attr__($tw_name); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('ln_name'); ?>">LinkedIn URL: <input class="widefat" id="<?php echo $this->get_field_id('ln_name'); ?>" name="<?php echo $this->get_field_name('ln_name'); ?>" type="text" value="<?php echo esc_attr__($ln_name); ?>" /></label></p>
			<p><em>Note: LinkedIn does not have a friendly URL Structure, so alternatively we allow you to copy and paste the entire URL to your page.</em></p>
<?php
	}
}
//CREATE Widget
add_action( 'widgets_init', create_function('', 'return register_widget("HC_Agent_Widget");') );

/**********************************
 *  Badge Widget
 **********************************/ 

//CONSTRUCT Class
class HC_Products_Badge_Widget extends WP_Widget {
	function HC_Products_Badge_Widget() {
		$widget_ops = array('classname' => 'widget_hc_pro_badge', 'description' => 'Your list of technology tools.' );
		//WIDGET Name
		$this->WP_Widget('hc_pro_badge', 'HomeCards Apps Badge', $widget_ops);
	}
		
	//WIDGET Args
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		echo $before_widget;
		//WIDGET Database Checks
		$title = empty($instance['title']) ? ' ' : apply_filters('widget_pro_badge', $instance['title']);
		$iphone = empty($instance['iphone']) ? ' ' : apply_filters('widget_pro_badge_iphone', $instance['iphone']);
		$android = empty($instance['android']) ? ' ' : apply_filters('widget_pro_badge_android', $instance['android']);
		
		//GRAB Agent Data 
		$agentData = getAgentData();
		
		//function widgetMortCalc() {
		//	wp_enqueue_script( 'hc-widget-mort-calc', plugin_dir_url( __FILE__ ) . 'js/mort-calc.js', array( 'jquery' ) );
		//	wp_enqueue_style( 'hc-widget-mort-calc', plugin_dir_url( __FILE__ ) . 'css/widgets.css' );	
		//}
		//WIDGET View	
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
			<?php if ( !empty( $title ) && (strlen($title) > 1 )) { echo $before_title . $title . $after_title; }; ?>
			<p>Use My app to find homes on the go!</p>
			<p id="widget_app_badge_vip">VIP Access Code:</p>
			<h4 style="font-size: <?php echo $fontSize; ?>px;"><?php echo $agentData->MobileAccessCode; ?></h4>
			<p>Download My App for:</p>
			<?php if ($iphone == "1") { echo '<a href="' . $agentData->Products->mobile->iPhoneLink . '" id="widget_iphone" target="_blank">iPhone</a>'; } ?>
			<?php if ($android == "1") { echo '<a href="' . $agentData->Products->mobile->androidLink . '" id="widget_android" target="_blank">Android</a>'; } ?>
		</div>

	<?php 	
		echo $after_widget;
	}
	//WIDGET Save
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['iphone'] = strip_tags($new_instance['iphone']);
		$instance['android'] = strip_tags($new_instance['android']);
		return $instance;
	}
	//WIDGET Admin Form
	function form($instance) {
		//CREATE ARGS TO EXTRACT
		$instance = wp_parse_args( (array) $instance, array( 'title' => 'Get My Apps', 'iphone' => '', 'android' => '' ) );
		$title = strip_tags($instance['title']);
		$iphone = strip_tags($instance['iphone']);
		$android = strip_tags($instance['android']);
?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr__($title); ?>" /></label></p>
			<p><input type="checkbox" id="<?php echo $this->get_field_id('iphone'); ?>" name="<?php echo $this->get_field_name('iphone'); ?>" value="1" <?php if ( $iphone == "1" ) { echo 'checked'; } ?>/><label for="<?php echo $this->get_field_id('iphone'); ?>"> Show iPhone Link</label></p>
			<p><input type="checkbox" id="<?php echo $this->get_field_id('android'); ?>" name="<?php echo $this->get_field_name('android'); ?>" value="1" <?php if ( $android == "1" ) { echo 'checked'; } ?>/><label for="<?php echo $this->get_field_id('android'); ?>"> Show Android Link</label></p>
<?php
	}
}
//CREATE Widget
add_action( 'widgets_init', create_function('', 'return register_widget("HC_Products_Badge_Widget");') );

 
/**********************************
 *  Feat Widget
 **********************************/ 
 
 //CONSTRUCT Class
class HC_Feat_List_Widget extends WP_Widget {
	function HC_Feat_List_Widget() {
		$widget_ops = array('classname' => 'widget_hc_feat_list', 'description' => 'Sidebar Widget for your Featured Listings' );
		//WIDGET Name
		$this->WP_Widget('hc_feat_list', 'HomeCards Featured Listings', $widget_ops);
	}
	
	function getPropPhoto($listing, $picIndex, $size, $board) {
	// Note: picIndex MUST be between 1-99
		return "http://www.myhomecards.com/GetPhoto.aspx?LN=" . $listing . "&Size=" . ($size ? $size : "Medium") . "&PhotoIndex=" . $picIndex . "&Board=" . $board;
	}

	function getImgSizeOpts() {
		return array('Small', 'Medium', 'Large', 'ExtraLarge');
	}
	
	//WIDGET Args
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		echo $before_widget;

		//WIDGET Database Checks
		$title = empty($instance['title']) ? ' ' : apply_filters('widget_feat_list_title', $instance['title']);		
		$seconds = empty($instance['seconds']) ? ' ' : apply_filters('widget_feat_list_seconds', $instance['seconds']);		
		$effect = empty($instance['effect']) ? ' ' : apply_filters('widget_feat_list_effect', $instance['effect']);		
		$limit = empty($instance['limit']) ? ' ' : apply_filters('widget_feat_list_limit', $instance['limit']);		
		$imgsize = empty($instance['imgsize']) ? ' ' : apply_filters('widget_feat_list_imgsize', $instance['imgsize']);		

		//enqueue some scripts & styles 
			wp_enqueue_script( 'hc-widget-cycle', plugin_dir_url( __FILE__ ) . 'js/jquery.cycle.all.min.js', array( 'jquery' ) );

		//GRAB Agent Data
		$agentData = getAgentData();
	?>
	<?php if ( !empty( $title ) && (strlen($title) > 1 )) { echo $before_title . $title . $after_title; }; ?>
	
		<script	type="text/javascript">
			jQuery(function($){ 
				$('.widget_feat_slide').cycle({ 
			    fx:    '<?php echo $effect ?>', 
			    sync:   0, 
			    delay: <?php echo '-' . $seconds; ?>
				});
			});
		</script>
	<?php

	?>
	
	<div class="widget_feat_slide">
	<?php	
		$currentFeatIndex = 0;
		$featLimit = 10;
		if ($limit > $featLimit) {
			$limit = $featLimit;
		}
		if ( in_array($imgsize, $this->getImgSizeOpts()) ) {
			//echo "Valid IMG Size Detected";
		} else {	
			$imgsize = 'Medium';
		};
	
		$featListing = json_decode(hc_getFeaturedListingsJson());
		//var_dump($featListing);
				
		for ($i = 0; $i < 5; $i++) {
			$singleList = $featListing[$i]; 
	?>
	
		<div class="full_wrap feat_slide hc-listing">
			<p><h3><?php echo '<a href="/' . get_option('wp_hc_property_details_path', 'property-details') . '/' . hc_urlencode($singleList->P_AD) . '/' . hc_urlencode($singleList->CMY) . '/' . hc_urlencode($singleList->P_LN) . '">' . $singleList->Address . '</a>'; ?></h3></p>
			<p><?php echo '<a href="/' . get_option('wp_hc_property_details_path', 'property-details') . '/' . hc_urlencode($singleList->P_AD) . '/' . hc_urlencode($singleList->CMY) . '/' . hc_urlencode($singleList->P_LN) . '"><img class="hc-widget-feat-img" src="' . $this->getPropPhoto($singleList->P_LN, 1, $imgsize, $agentData->Board) . '"/></a>'; ?></p>
			<p>Price: <?php echo '$' . $singleList->Price; ?></p>
			<p>MLS#: <?php echo $singleList->P_LN; ?></p>
			<p>City: <?php echo $singleList->CMY; ?></p>
			<p>Listed By: <?php echo $singleList->Bkr; ?></p>
			<p><?php echo '<a href="/' . get_option('wp_hc_property_details_path', 'property-details') . '/' . hc_urlencode($singleList->P_AD) . '/' . hc_urlencode($singleList->CMY) . '/' . hc_urlencode($singleList->P_LN) . '">View Property</a>'; ?></p>
		</div>
		
	<?php
		$currentFeatIndex++;
		if ($currentFeatIndex >= $limit) {
			break;
		}
	}
	?>
		</div>
		
	<?php 
		echo $after_widget;
		}
		//WIDGET Save
		function update($new_instance, $old_instance) {
			$instance = $old_instance;
			$instance['title'] = strip_tags($new_instance['title']);
			$instance['seconds'] = strip_tags($new_instance['seconds']);
			$instance['effect'] = strip_tags($new_instance['effect']);
			$instance['limit'] = strip_tags($new_instance['limit']);
			$instance['imgsize'] = strip_tags($new_instance['imgsize']);
			return $instance;
		}
		//WIDGET Admin Form
		function form($instance) {
			//CREATE ARGS TO EXTRACT
			$instance = wp_parse_args( (array) $instance, array( 'title' => 'Featured Listings', 'seconds' => '7000', 'effect' => 'fade', 'limit' => '5', 'imgsize' => 'Large' ) );
			$title = strip_tags($instance['title']);
			$seconds = strip_tags($instance['seconds']);			
			$effect = strip_tags($instance['effect']);			
			$limit = strip_tags($instance['limit']);			
			$imgsize = strip_tags($instance['imgsize']);			
	?>
		
		<p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr__($title); ?>" /></label></p>

		<p><label for="<?php echo $this->get_field_id('imgsize'); ?>">Featured Listings Image Size: 
				<select id="<?php echo $this->get_field_id('imgsize'); ?>" name="<?php echo $this->get_field_name('imgsize'); ?>" class="widefat">
					<option value="Small" id="<?php echo $this->get_field_id('imgsize'); ?>" <?php if ($imgsize == 'Small') { echo 'selected="selected"'; } ?>>Small</option> 
					<option value="Medium" id="<?php echo $this->get_field_id('imgsize'); ?>" <?php if ($imgsize == 'Medium') { echo 'selected="selected"'; } ?>>Medium</option> 
					<option value="Large" id="<?php echo $this->get_field_id('imgsize'); ?>" <?php if ($imgsize == 'Large') { echo 'selected="selected"'; } ?>>Large</option> 
					<option value="ExtraLarge" id="<?php echo $this->get_field_id('imgsize'); ?>" <?php if ($imgsize == 'ExtraLarge') { echo 'selected="selected"'; } ?>>Extra Large</option> 
				</select>
			</label>
	 	</p>


		<p><label for="<?php echo $this->get_field_id('limit'); ?>">Featured Listings Limit: 
				<select id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" class="widefat">
					<option value="5" id="<?php echo $this->get_field_id('limit'); ?>" <?php if ($limit == 5) { echo 'selected="selected"'; } ?>>5 Listings</option> 
					<option value="6" id="<?php echo $this->get_field_id('limit'); ?>" <?php if ($limit == 6) { echo 'selected="selected"'; } ?>>6 Listings</option> 
					<option value="7" id="<?php echo $this->get_field_id('limit'); ?>" <?php if ($limit == 7) { echo 'selected="selected"'; } ?>>7 Listings</option> 
					<option value="8" id="<?php echo $this->get_field_id('limit'); ?>" <?php if ($limit == 8) { echo 'selected="selected"'; } ?>>8 Listings</option> 
					<option value="9" id="<?php echo $this->get_field_id('limit'); ?>" <?php if ($limit == 9) { echo 'selected="selected"'; } ?>>9 Listings</option> 
					<option value="10" id="<?php echo $this->get_field_id('limit'); ?>" <?php if ($limit == 10) { echo 'selected="selected"'; } ?>>10 Listings</option> 
				</select>
			</label>
	 	</p>

		<p><label for="<?php echo $this->get_field_id('seconds'); ?>">Select Transition Delay: 
				<select id="<?php echo $this->get_field_id('seconds'); ?>" name="<?php echo $this->get_field_name('seconds'); ?>" class="widefat">
					<option value="6000" id="<?php echo $this->get_field_id('seconds'); ?>" <?php if ($seconds == 6000) { echo 'selected="selected"'; } ?>>5 Seconds</option> 
					<option value="7000" id="<?php echo $this->get_field_id('seconds'); ?>" <?php if ($seconds == 7000) { echo 'selected="selected"'; } ?>>6 Seconds</option> 
					<option value="8000" id="<?php echo $this->get_field_id('seconds'); ?>" <?php if ($seconds == 8000) { echo 'selected="selected"'; } ?>>7 Seconds</option> 
					<option value="9000" id="<?php echo $this->get_field_id('seconds'); ?>" <?php if ($seconds == 9000) { echo 'selected="selected"'; } ?>>8 Seconds</option> 
					<option value="10000" id="<?php echo $this->get_field_id('seconds'); ?>" <?php if ($seconds == 10000) { echo 'selected="selected"'; } ?>>9 Seconds</option> 
					<option value="11000" id="<?php echo $this->get_field_id('seconds'); ?>" <?php if ($seconds == 11000) { echo 'selected="selected"'; } ?>>10 Seconds</option> 
				</select>
			</label>
	 	</p>
	 	
		<p><label for="<?php echo $this->get_field_id('effect'); ?>">Select Transition Effect: 
				<select id="<?php echo $this->get_field_id('effect'); ?>" name="<?php echo $this->get_field_name('effect'); ?>" class="widefat">
					<option value="blindX" id="<?php echo $this->get_field_id('effect'); ?>" <?php if ($effect == 'blindX') { echo 'selected="selected"'; } ?>>Effect #1: Horizontal Blinds</option> 
					<option value="cover" id="<?php echo $this->get_field_id('effect'); ?>" <?php if ($effect == 'cover') { echo 'selected="selected"'; } ?>>Effect #2: Cover</option> 
					<option value="curtainY" id="<?php echo $this->get_field_id('effect'); ?>" <?php if ($effect == 'curtainY') { echo 'selected="selected"'; } ?>>Effect #3: Vertical Curtain</option> 
					<option value="fade" id="<?php echo $this->get_field_id('effect'); ?>" <?php if ($effect == 'fade') { echo 'selected="selected"'; } ?>>Effect #4: Fade Out/In</option> 
					<option value="growX" id="<?php echo $this->get_field_id('effect'); ?>" <?php if ($effect == 'growX') { echo 'selected="selected"'; } ?>>Effect #5: Horizontal Grow</option> 
					<option value="scrollUp" id="<?php echo $this->get_field_id('effect'); ?>" <?php if ($effect == 'scrollUp') { echo 'selected="selected"'; } ?>>Effect #6: Scroll Up</option> 
					<option value="scrollHorz" id="<?php echo $this->get_field_id('effect'); ?>" <?php if ($effect == 'scrollHorz') { echo 'selected="selected"'; } ?>>Effect #7: Scroll Horizontal</option> 
					<option value="shuffle" id="<?php echo $this->get_field_id('effect'); ?>" <?php if ($effect == 'shuffle') { echo 'selected="selected"'; } ?>>Effect #8: Shuffle</option> 
					<option value="slideX" id="<?php echo $this->get_field_id('effect'); ?>" <?php if ($effect == 'slideX') { echo 'selected="selected"'; } ?>>Effect #9: Horizontal Slide</option> 
					<option value="slideY" id="<?php echo $this->get_field_id('effect'); ?>" <?php if ($effect == 'slideY') { echo 'selected="selected"'; } ?>>Effect #10: Vertical Slide</option> 
					<option value="turnUp" id="<?php echo $this->get_field_id('effect'); ?>" <?php if ($effect == 'turnUp') { echo 'selected="selected"'; } ?>>Effect #11: Turn Up</option> 
					<option value="turnLeft" id="<?php echo $this->get_field_id('effect'); ?>" <?php if ($effect == 'turnLeft') { echo 'selected="selected"'; } ?>>Effect #12: Turn Left</option> 
					<option value="uncover" id="<?php echo $this->get_field_id('effect'); ?>" <?php if ($effect == 'uncover') { echo 'selected="selected"'; } ?>>Effect #13: Reveal</option> 
					<option value="wipe" id="<?php echo $this->get_field_id('effect'); ?>" <?php if ($effect == 'wipe') { echo 'selected="selected"'; } ?>>Effect #14: Wipe</option> 
					<option value="zoom" id="<?php echo $this->get_field_id('effect'); ?>" <?php if ($effect == 'zoom') { echo 'selected="selected"'; } ?>>Effect #15: Zoom</option> 
				</select>
			</label>
	 	</p>

<?php
	}
}
//CREATE Widget
add_action( 'widgets_init', create_function('', 'return register_widget("HC_Feat_List_Widget");') );

/**********************************
 *  Login Widget
 **********************************/ 
 
 //CONSTRUCT Class
class HC_Login_Widget extends WP_Widget {
	function HC_Login_Widget() {
		$widget_ops = array('classname' => 'widget_hc_login', 'description' => 'HomeCards Client and Agent Login Portal' );
		//WIDGET Name
		$this->WP_Widget('hc_login', 'HomeCards Login Portal', $widget_ops);
	}
	//WIDGET Args
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		echo $before_widget;
		//WIDGET Database Checks
		$title = empty($instance['title']) ? ' ' : apply_filters('widget_login_title', $instance['title']);
		//WIDGET View
		//if (isset($_SESSION['HC_Login'])) { echo "<!-- 	" . $_SESSION['HC_Login'] . " -->\n"; }
		if (isset($_SESSION['HC_Login']) && strlen($_SESSION['HC_Login']) > 6) {
			// already logged in
			$json = $_SESSION['HC_Login'];
			if (stripos($json, 'FirstName') > 0) {
				$leadJSON = json_decode($json, true);
				if (isset($leadJSON['FirstName'])) { $fName = $leadJSON['FirstName']; } else { $fName = "n/a";}
			}
			$strHTML = "<div class='hc_login_msg hc_user_msg' style='font-weight: 700;'>Welcome back " .  htmlspecialchars($fName) . "</div>";
			$strHTML .= "<a class='hc_logout' onclick='hc_leadLogout()' style='text-decoration: underline; font-size: small; margin: 4px; cursor: pointer;'>Click here to logout</a>";
			echo $strHTML;
		} else { ?>
			<form class="widget_wrap">
			<?php if ( !empty( $title ) && (strlen($title) > 1 )) { echo $before_title . $title . $after_title; }; ?>
				<ul class="widget_area widget_login">
					<li>
						<label for='hc_login_email'>Email:*</label> <input id='hc_login_email' name='hc_login_email' class='hc_input' />
					</li>
					<li>
						<label for='hc_login_password'>Password:*</label> <input id='hc_login_password' name='hc_login_password' class='hc_input' type='password' />
					</li>
					<li>
						<span class="widget-login-helper">Forgot your password?</span>
						<input type='submit' name='hc_login_button' value='Submit' class='hc-login-button widget-btn widget-float' />
						<div class="clr"></div>
					</li>
					<li>
						<div id='hc_login_ajax'></div>
					</li>
				</ul>
			</form>
		<?php
		}
		echo $after_widget;
	}
	//WIDGET Save
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}
	//WIDGET Admin Form
	function form($instance) {
		//CREATE ARGS TO EXTRACT
		$instance = wp_parse_args( (array) $instance, array( 'title' => 'HomeCards Login' ) );
		$title = strip_tags($instance['title']);
?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr__($title); ?>" /></label></p>
<?php
	}
}
//CREATE Widget
add_action( 'widgets_init', create_function('', 'return register_widget("HC_Login_Widget");') );

 /**********************************
 *  Mortgage Calc Widget
 **********************************/ 

//CONSTRUCT Class
class HC_Mortgage_Calc_Widget extends WP_Widget {
	function HC_Mortgage_Calc_Widget() {
		$widget_ops = array('classname' => 'widget_hc_mort_calc', 'description' => 'Easy to use Mortgage Calculator for your visitors.' );
		//WIDGET Name
		$this->WP_Widget('hc_mort_calc', 'HomeCards Mortgage Calculator', $widget_ops);
	}
	//WIDGET Args
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		echo $before_widget;
		//WIDGET Database Checks
		$title = empty($instance['title']) ? ' ' : apply_filters('widget_mort_calc', $instance['title']);
		
		//WIDGET View
		
		//function widgetMortCalc() {
			wp_enqueue_script( 'hc-widget-mort-calc', plugin_dir_url( __FILE__ ) . 'js/mort-calc.js', array( 'jquery' ) );
			wp_enqueue_style( 'hc-widget-mort-calc', plugin_dir_url( __FILE__ ) . 'css/widgets.css' );	
		//}
		?>
				
				<form onsubmit="return false;">
					<div id="w-mort-wrap">
						<?php if ( !empty( $title ) ) { echo $before_title . $title . $after_title; }; ?>
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
		echo $after_widget;
	}
	//WIDGET Save
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}
	//WIDGET Admin Form
	function form($instance) {
		//CREATE ARGS TO EXTRACT
		$instance = wp_parse_args( (array) $instance, array( 'title' => 'Loan Calculator' ) );
		$title = strip_tags($instance['title']);
?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr__($title); ?>" /></label></p>
<?php
	}
}
//CREATE Widget
add_action( 'widgets_init', create_function('', 'return register_widget("HC_Mortgage_Calc_Widget");') );

  /**********************************
 *  Twitter Calc Widget
 **********************************/ 
 
 //CONSTRUCT Class
class HC_Tweet_Widget extends WP_Widget {
	function HC_Tweet_Widget() {
		$widget_ops = array('classname' => 'widget_hc_tweet', 'description' => 'Sidebar Widget for Twitters Feed API' );
		//WIDGET Name
		$this->WP_Widget('hc_tweet', 'HomeCards Twitter Feed', $widget_ops);
	}
	//WIDGET Args
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		echo $before_widget;
		//WIDGET Database Checks
		$title = empty($instance['title']) ? ' ' : apply_filters('widget_tweet_title', $instance['title']);
		$tw_user = empty($instance['tw_user']) ? ' ' : apply_filters('widget_tweet_tw_user', $instance['tw_user']);
		$tw_img = empty($instance['tw_img']) ? ' ' : apply_filters('widget_tweet_tw_img', $instance['tw_img']);
		$tw_count = empty($instance['tw_count']) ? ' ' : apply_filters('widget_tweet_tw_count', $instance['tw_count']);
		$tw_load_txt = empty($instance['tw_load_txt']) ? ' ' : apply_filters('widget_tweet_tw_load_txt', $instance['tw_load_txt']);
		
		//enqueue widget scripts & styles 
			wp_enqueue_script( 'hc-widget-tweet', plugin_dir_url( __FILE__ ) . 'js/jquery.tweet.min.js', array( 'jquery' ) );
			wp_enqueue_style( 'hc-widget-tweet', plugin_dir_url( __FILE__ ) . 'css/tweet.css' );	
		?>
		<script type="text/javascript">

		  jQuery(function($){
		    $(".tweet").tweet({
		      join_text: "auto",
		      username: "<?php echo $tw_user ?>",
		      avatar_size: 48,
		      count: <?php echo $tw_count ?>,
		      auto_join_text_default: ",",
		      auto_join_text_ed: "",
		      auto_join_text_ing: "",
		      auto_join_text_reply: "",
		      auto_join_text_url: "",
		      loading_text: "<?php echo $tw_load_txt ?>",
					<?php if ($tw_img == "1") {
						 echo 'template: "{avatar}{time}{join}{text}"'; 
					} elseif ($tw_img) {
						 echo 'template: "{time}{join}{text}"';	
					}; 
					?>


		    });
		  });
		</script>			
		<div class="widget_wrap">
			<?php if ( !empty( $title ) && (strlen($title) > 1 )) { echo $before_title . $title . $after_title; }; ?>
			<div class="tweet"></div>
		</div>
		<?php
		echo $after_widget;
	}
	//WIDGET Save
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['tw_user'] = strip_tags($new_instance['tw_user']);
		$instance['tw_img'] = strip_tags($new_instance['tw_img']);
		$instance['tw_count'] = strip_tags($new_instance['tw_count']);
		$instance['tw_load_txt'] = strip_tags($new_instance['tw_load_txt']);
				return $instance;
	}
	//WIDGET Admin Form
	function form($instance) {
		//CREATE ARGS TO EXTRACT
		$instance = wp_parse_args( (array) $instance, array( 'title' => 'Recent Tweets', 'tw_user' => '', 'tw_img' => '1', 'tw_count' => '3', 'tw_load_txt' => 'Loading tweets...' ) );
		$title = strip_tags($instance['title']);
		$tw_user = strip_tags($instance['tw_user']);
		$tw_img = strip_tags($instance['tw_img']);
		$tw_count = strip_tags($instance['tw_count']);
		$tw_load_txt = strip_tags($instance['tw_load_txt']);
?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr__($title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('tw_user'); ?>">Twitter Username: <input class="widefat" id="<?php echo $this->get_field_id('tw_user'); ?>" name="<?php echo $this->get_field_name('tw_user'); ?>" type="text" value="<?php echo esc_attr__($tw_user); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('tw_count'); ?>">Tweet Count: </label><input style="width: 13%;" class="widefat" id="<?php echo $this->get_field_id('tw_count'); ?>" name="<?php echo $this->get_field_name('tw_count'); ?>" type="number" value="<?php echo esc_attr__($tw_count); ?>" />
			<input style="margin: 0 0 0 20px;" type="checkbox" id="<?php echo $this->get_field_id('tw_img'); ?>" name="<?php echo $this->get_field_name('tw_img'); ?>" value="1" <?php if ( $tw_img == "1" ) { echo 'checked'; } ?>/><label for="<?php echo $this->get_field_id('tw_img'); ?>"> Show Icon</label></p>		
			<p><label for="<?php echo $this->get_field_id('tw_load_txt'); ?>">Loading Text: <input class="widefat" id="<?php echo $this->get_field_id('tw_load_txt'); ?>" name="<?php echo $this->get_field_name('tw_load_txt'); ?>" type="text" value="<?php echo esc_attr__($tw_load_txt); ?>" /></label></p>
<?php
	}
}
//CREATE Widget
add_action( 'widgets_init', create_function('', 'return register_widget("HC_Tweet_Widget");') );

 