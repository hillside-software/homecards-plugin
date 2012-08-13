<?php


function hc_oldmapsearch() {

	if (stripos($atts['width'], "%") > 0) {
		if (intval($atts['width']) < 550) {$atts['width'] = 550;}
		if (intval($atts['height']) < 475) {$atts['height'] = 475;}
	}
	//if (intval($atts['height']) == '100%') {$atts['height'] = '100%';} - djh doesn't accept a string, apparently.
	wp_enqueue_script( 'colorbox', plugin_dir_url( __FILE__ ) . 'js/jquery.colorbox.min.js', array( 'jquery' ) );
	wp_enqueue_style( 'colorbox', plugin_dir_url( __FILE__ ) . 'css/colorbox/colorbox.css');
	$mapFrameLink = 'http://' . $hc_proxy->hc_domain_name . $hc_proxy->debugPath . '/MapSearchControl.aspx?wID=' . get_option('wp_hc_webid', '0') . '&ShowExitButton=False';
	$html .= '<a href="' . $mapFrameLink . '" class="colorbox colorbox-mapsearch">Open Map Search in Full-screen mode</a>';
	//echo '<iframe id="MapBox" width="' . $atts['width'] . '" height="' . $atts['height'] . '" frameborder="0" marginheight="0" marginwidth="0" style="border: 1px solid #555; background-color: #FFFFFF; border-radius: 6px;" src="' . $mapFrameLink . '"></iframe>';
?>
<script type="text/javascript">
	jQuery(document).ready( function($) {
		jQuery('.colorbox-mapsearch').colorbox({inline:false, scrolling: false, iframe: "<?php echo $mapFrameLink; ?>", title: 'Map Search',
			width: parseInt(jQuery(document).width() * 0.9).toString() + 'px',
			maxHeight: jQuery(window).height() * 0.9,
			height: jQuery(window).height(),
			initialHeight: (jQuery(window).height() * 0.9),
			onClosed: function() {history.back(-1);}});
		jQuery('.colorbox-mapsearch').trigger('click');
	});
</script>
<style>
	.colorbox-mapsearch {font-size: 19px; font-weight: 700; font-family: 'Trebuchet MS', Tahoma, Verdana;}	
</style>
<?php

}

