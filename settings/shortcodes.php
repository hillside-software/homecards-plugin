<?php

function shortcodes_submenu_cb() {
	
	if ( !is_admin() && !is_page('shortcodes-settings') ) {
		echo 'Not finding Page Yet';
	} else {
	
?>
<style>
	.shortcode_wrap {
		margin: 0 0 15px; 
		padding: 1px 25px; 
		font-size: 14px; 
		border: 1px solid #A3A3A3; 
		background: #f1f1f1; 
		border-radius: 10px; 
		box-shadow: inset 1px 1px 0 #fff;
		text-shadow: 1px 1px 0 #fff;
	}
</style>
<div class="clear"></div>
		<?php
		
		function hc_searchCreator($templateId, $pagetitle) {
			$templateList = array(
				"[homecards page=\"searchform\"]", 
				"[homecards page=\"city\" city=\"" . esc_attr( $_POST['city_name'] ) . "\" bedsrange=\"2-4\" bathsrange=\"1-3\" pricerange=\"300000-600000\" subareas=\"\"]",
				"[homecards page=\"mapsearch\" width=\"750\" height=\"720\"]",
				"[homecards page=\"featuredlistings\"]",
				"[homecards page=\"signupform\"]",
				"[homecards page=\"loginbox\"]");
				
			$templateId = intval($templateId);
			if (!isset($templateList[$templateId])) {
				$templateList[$templateId] = 'No shortcode found. Id '. $templateId . " is invalid!\n";
			};
			//var_dump($templateList[$templateId]);		
			
			$my_post = array(
				'post_title' => wp_strip_all_tags( $pagetitle ),
				'post_content' => $templateList[$templateId] . "\n",
				'post_status' => 'publish',
				'post_type' => 'page',
				'post_author' => 1,
				'post_category' => array( 8,39 )
			);
			if (isset( $_POST['post_title'] )) {
				// Insert the post into the database
			  $postid = wp_insert_post( $my_post );
				//get_page_link($postid)
				//return 'Great Success!';
				ob_clean();
				//$newest_post_id = $posts[0]->ID;
				//echo $newest_post_id;
				$last = wp_get_recent_posts( array('numberposts' => 1));

			echo json_encode( array('url' => get_page_link($postid)) );
			
				ob_flush();
				die();
			} else {
				return 'Error creating page.';
			}			
		}
   		$template = '-1';
			if (isset($_POST['template'])) {
				$template = $_POST['template'];
			}
			$createResult = "";
		  if ( isset( $_POST['post_title']) && strlen($_POST['post_title']) > 0 ) {
		  	$createResult = hc_searchCreator($_POST['template'], $_POST['post_title']);
			}
			//var_dump($template);
		?>

	
	<style>
		
		.tipsyWrapper a { margin: 0 0 0 8px; display: inline-block; line-height: 28px; height: 29px; width: 29px; background: <?php echo 'url("'. plugin_dir_url( __FILE__ ) . 'images/tool-tip-icon.png") no-repeat;'; ?>}
		.tipsyWrapper a:hover { cursor: pointer;}
		
		.specialInputs { 
			width: 350px;
			height: 1.8em;
			font-size: 1.3em;
		}	
	</style>
	<script type="text/javascript">
		
		jQuery(document).ready( function($) {
			$('.tipsyWrapper a[rel=tipsy]').tipsy({
				gravity: 'w'
			});
		});
		function fixCsv(csvData) {
			return csvData.replace(/, /g, ','); 
		}
	</script>
	<div id="icon-tools" class="icon32"></div>
	<h1>Shortcode Manager</h1>
	<p style="font-size: 1.3em;">The HomeCards Plug-in uses WordPress' Native Shortcode system to keep it simple. Shortcode = Shortcut.
	<span class="tipsyWrapper">
		<a rel="tipsy" title="A shortcode is a WordPress-specific code that lets you do nifty things with very little effort. Shortcodes can embed files or create objects that would normally require lots of complicated, ugly code in just one line. Shortcode = shortcut.">&nbsp;</a>
	</span>
	</p>
	<p style="font-size: 1.3em;">However, if you're asking <em>'Whats a Shortcode'</em> <a href="http://en.support.wordpress.com/shortcodes/" target="_blank" >Click here to learn all about them.</a></p>
	<h2>Search Form Shortcode
	<span class="tipsyWrapper">
		<a rel="tipsy" title="You can Copy & Paste this Shortcode or just Enter the Name of the Page Title you'd like and Select to Create Page Button.">&nbsp;</a>
	</span>
	</h2>
	<p>This will generate a Search Form on your Posts or Pages.</p>
	<div class="shortcode_wrap" style="width: 75%;">
		<p><strong>Search Form:</strong> <span id="search-copy-text">[homecards page=&quot;searchform&quot;]</span></p>
		<hr size="1px" />
			<p><form method="post">
				<label for="post_title" style="width: 175px; display: inline-block;">Search Page Title: </label>
					<input type="text" name="post_title" style="width: 30%;">
				  <input type="submit" value="Create Page" name="create_search" class="button" style="float: right;">
				  <input type="hidden" value="0" name="template">
			  <?php if ($template == '0') { echo $createResult; } ?>
			</form></p>	
	</div>
	<h2>Denver Canned Search Shortcode
	<span class="tipsyWrapper">
		<a rel="tipsy" title="Trick: The Shortcode System is Smart enough to let ou put in whatever search criteria you would like. Play Around with it, try changing Denver to Parker.">&nbsp;</a>
	</span>
	</h2>
	<p>This will generate a Canned Search in Denver on your Posts or Pages.</p>
	<div class="shortcode_wrap" style="width: 75%;">
		<p><strong>&quot;City&quot; Search Results:</strong> <span id="city-copy-text">[homecards page=&quot;city&quot; city=&quot;Denver&quot; bedsrange=&quot;2-4&quot; bathsrange=&quot;1-3&quot; pricerange=&quot;300000-600000&quot; subareas=&quot;&quot;]</span></p>
		<hr size="1px" />
			<p><form method="post">
				<label for="post_title" style="width: 175px; display: inline-block;">&quot;City&quot; Search Page Title: </label>
				<input type="text" name="post_title" style="width: 20%;" />
				
				<label for="post_title" style="width: 75px; display: inline-block;">City Name: </label>
				<input type="text" name="city_name" style="width: 20%;" />
			  
			  <input type="submit" value="Create Page" name="create_search" class="button" style="float: right;" />
			  <input type="hidden" value="1" name="template" />
			  <?php if ($template == '1') { echo $createResult; } ?>
			</form></p>	
	</div>
	<h2>Map Search Shortcode
	<span class="tipsyWrapper">
		<a rel="tipsy" title="The reason we keep it as Pop-Up is we want to keep it easy for anyone on any WordPress theme to benefit from this feature without Bugs.">&nbsp;</a>
	</span>
	</h2>
	<p>This will generate a Map Search Pop-Up on your Posts or Pages.</p>
	<div class="shortcode_wrap" style="width: 75%;">
		<p><strong>Map Search:</strong> <span id="mapsearch-copy-text">[homecards page=&quot;mapsearch&quot; width=&quot;750&quot; height=&quot;720&quot;]</span></p>
		<hr size="1px" />
			<p><form method="post">
				<label for="post_title" style="width: 175px; display: inline-block;">Map Search Page Title: </label>
					<input type="text" name="post_title" style="width: 30%;">
				  <input type="submit" value="Create Page" name="create_search" class="button" style="float: right;">
				  <input type="hidden" value="2" name="template">
			  <?php if ($template == '2') { echo $createResult; } ?>
			</form></p>	
	</div>
	<h2>Featured Listings Shortcode
	<span class="tipsyWrapper">
		<a rel="tipsy" title="We've designed this to be fairly easy to customize with CSS - an entry level Web Designer can handle this stuff.">&nbsp;</a>
	</span>
	</h2>
	<p>This will generate your Featured Listings on your Posts or Pages.</p>
	<div class="shortcode_wrap" style="width: 75%;">
		<p><strong>Featured Listings:</strong> <span id="featured-copy-text">[homecards page=&quot;featuredlistings&quot;]</span></p>
		<hr size="1px" />
			<p><form method="post">
				<label for="post_title" style="width: 175px; display: inline-block;">Featured Listings Page Title: </label>
					<input type="text" name="post_title" style="width: 30%;">
				  <input type="submit" value="Create Page" name="create_search" class="button" style="float: right;">
				  <input type="hidden" value="3" name="template">
			  <?php if ($template == '3') { echo $createResult; } ?>
			</form></p>	
	</div>
	<h2>Lead Capture Form Shortcode
	<span class="tipsyWrapper">
		<a rel="tipsy" title="This form will allow you to track your Leads and your users to get the benefits of Registering">&nbsp;</a>
	</span>
	</h2>
	<p>This will generate a Registration Form on your Posts or Pages.</p>
	<div class="shortcode_wrap" style="width: 75%;">
		<p><strong>Registration / Sign-up Form:</strong> <span id="registration-copy-text">[homecards page=&quot;signupform&quot;]</span></p>
		<hr size="1px" />
			<p><form method="post">
				<label for="post_title" style="width: 175px; display: inline-block;">Registration / Sign-up Page Title: </label>
					<input type="text" name="post_title" style="width: 30%;">
				  <input type="submit" value="Create Page" name="create_search" class="button" style="float: right;">
				  <input type="hidden" value="4" name="template">
			  <?php if ($template == '4') { echo $createResult; } ?>
			</form></p>	
	</div>
	<h2>Lead Login Form Shortcode
	<span class="tipsyWrapper">
		<a rel="tipsy" title="Allows your Leads to Login to HomeCards">&nbsp;</a>
	</span>
	</h2>
	<p>This will generate a Login Form on your Posts or Pages.</p>
	<div class="shortcode_wrap" style="width: 75%;">
		<p><strong>Log-in Form:</strong> <span id="login-copy-text">[homecards page=&quot;loginbox&quot;]</span></p>
		<hr size="1px" />
			<p><form method="post">
				<label for="post_title" style="width: 175px; display: inline-block;">Log-in Page Title: </label>
					<input type="text" name="post_title" style="width: 30%;">
				  <input type="submit" value="Create Page" name="create_search" class="button" style="float: right;">
				  <input type="hidden" value="5" name="template">
			  <?php if ($template == '5') { echo $createResult; } ?>
			</form></p>	
	</div>
	<h2>This creates a Listing Tile based on the MLS#
				<span class="tipsyWrapper">
		<a rel="tipsy" title="Example: 123456,678901,234567">&nbsp;</a>
	</span>
</h2>
	<p>You can enter multiple Listing Numbers, Seperate these with a Comma</p>
	<div class="shortcode_wrap" style="width: 75%;">
	<p><strong>Property Tile:</strong> <span id="property-tile-copy-text">[homecards listingIDs=&quot;MLS#'s&quot;]</span></p>
	<hr size="1px" />
		<p><form method="post">
			<label for="post_title" style="width: 175px; display: inline-block;">Listing ID's/MLS#'s: </label>
				<input type="text" name="post_listingids" id="hc_listingids" style="width: 30%;" />
			  <input type="button" value="Create Shortcode" name="create_shortcode" class="button hc-listingids-shortcode" style="float: right;" onclick="jQuery('#property-tile-copy-text').html('[homecards listingIDs=&quot;' + fixCsv(jQuery('#hc_listingids').val()) + '&quot;]')" />
			  <!--input type="submit" value="Create Page" name="create_search" class="button" style="float: right;" /-->
			  <input type="hidden" value="0" name="template" />
		</form></p>
	</div>
		<script>
			jQuery(function($) {
				jQuery('form').submit(function(e) {
					var $myForm = jQuery(this);
					var req = $.ajax({url: self.location.href, type: 'post', dataType: 'json', data: jQuery(this).serializeArray()})
					.done(function(data) {
						jQuery('.button', $myForm).attr("disabled", true).after("<a href='"+ data.url +"' style='font-weight: 700;  margin-left: 20px;'>Click Here to View this Page!</a>");
						return true;
					});
					return false;
				});
			});
		</script>
<?php
	}
}

