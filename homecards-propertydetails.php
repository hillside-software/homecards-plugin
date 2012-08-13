<?php
if (!isset($_SESSION)) { session_start(); }
/*!
Original Credit: : Scott Sherrill-Mix
*/
class PropertyDetails {
	/**
	 * The slug for the fake post.  This is the URL for your plugin, like:
	 * http://site.com/about-me or http://site.com/?page_id=about-me
	 * @var string
	 */
	var $page_slug = 'property-details';
	
	/**
	 * The title for your fake post.
	 * @var string
	 */
	var $page_title = 'Property Details';
	
	/**
	 * Allow pings?
	 * @var string
	 */
	var $ping_status = 'closed';
		
	/**
	 * Class constructor
	 */
	function PropertyDetails() {
		/**
		 * We'll wait til WordPress has looked for posts, and then
		 * check to see if the requested url matches our target.
		 */
		add_filter('the_posts',array(&$this,'detectPost'));
    define( 'SHOW_DISCLAIMER', true );    
	}

	
	/**
	 * Called by the 'detectPost' action
	 */
	function createPost() {
	
		/**
		 * What we are going to do here, is create a fake post.  A post
		 * that doesn't actually exist. We're gonna fill it up with
		 * whatever values you want.  The content of the post will be
		 * the output from your plugin.
		 */		 
		
		/**
		 * Create a fake post.
		 */
		$post = new stdClass;
		
		/**
		 * The author ID for the post.  Usually 1 is the sys admin.  Your
		 * plugin can find out the real author ID without any trouble.
		 */
		$post->post_author = 1;
		
		/**
		 * The safe name for the post.  This is the post slug.
		 */
		$post->post_name = $this->page_slug;
		
		/**
		 * Not sure if this is even important.  But gonna fill it up anyway.
		 */
		$post->guid = get_bloginfo('wpurl') . '/' . $this->page_slug;
		
		
		/**
		 * The title of the page.
		 */
		$post->post_title = $this->page_title;
		
		/**
		 * This is the content of the post.  This is where the output of
		 * your plugin should go.  Just store the output from all your
		 * plugin function calls, and put the output into this var.
		 */
		$post->post_content = $this->getContent();

    if (  defined( 'SHOW_DISCLAIMER') ) {
      add_filter( 'the_content', 'hc_hideDisclaimer', 100 );
    }
		
		/**
		 * Fake post ID to prevent WP from trying to show comments for
		 * a post that doesn't really exist.
		 */
		$post->ID = -1;
		
		/**
		 * Static means a page, not a post.
		 */
		$post->post_status = 'static';

		/* ADDED BY DAN L. 2011-07-11 */
		$post->post_type = 'custom';
		$post->post_parent = 0;
		
		/**
		 * Turning off comments for the post.
		 */
		$post->comment_status = 'closed';
		
		/**
		 * Let people ping the post?  Probably doesn't matter since
		 * comments are turned off, so not sure if WP would even
		 * show the pings.
		 */
		$post->ping_status = $this->ping_status;
		
		$post->comment_count = 0;
		
		/**
		 * You can pretty much fill these up with anything you want.  The
		 * current date is fine.  It's a fake post right?  Maybe the date
		 * the plugin was activated?
		 */
		$post->post_date = current_time('mysql');
		$post->post_date_gmt = current_time('mysql', 1);

		return($post);		
	}
	
	function getContent() {
		return hc_content_filter('');
	}

	function detectPost($posts) {
		global $wp;
		global $wp_query;
		/**
		 * Check if the requested page matches our target 
		 */
		 //echo 'strtolower($wp->request): ' . strtolower($wp->request) . "\n";
		if (stripos(strtolower($wp->request), strtolower($this->page_slug)) === true || isset($wp->query_vars['listingid'])) {
			//Add the fake post
			$posts=NULL;
			$posts[]=$this->createPost();
		
			/**
			 * Trick wp_query into thinking this is a page (necessary for wp_title() at least)
			 * Not sure if it's cheating or not to modify global variables in a filter 
			 * but it appears to work and the codex doesn't directly say not to.
			 */
			$wp_query->is_page = true;
			//Not sure if this one is necessary but might as well set it like a true page
			$wp_query->is_singular = true;
			$wp_query->is_home = false;
			$wp_query->is_archive = false;
			$wp_query->is_category = false;
			
			//Longer permalink structures may not match the fake post slug and cause a 404 error so we catch the error here
			unset($wp_query->query["error"]);
			$wp_query->query_vars["error"]="";
			$wp_query->is_404=false;
			
		}
		return $posts;
	}
}

new PropertyDetails();

