<?php
/*
Plugin Name: Grab Twitter Pics
Plugin URI: http://www.davidbisset.com/wp-grab-twitter-pics
Description: This plugin will search through recent tweets with embedded photos (containing a certain hashtag), and import those photos along with the tweet into WP's media gallery.
Version: 0.1
Author: David Bisset
Author URI: http://www.davidbisset.com
Author Email: dbisset@dimensionmedia.com
License:

  Copyright 2013 David Bisset (dbisset@dimensionmedia.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

/**
 * TODO: 
 *
 * Rename this class to a proper name for your plugin. Give a proper description of
 * the plugin, it's purpose, and any dependencies it has.
 *
 * Use PHPDoc directives if you wish to be able to document the code using a documentation
 * generator.
 *
 * @version	1.0
 */
class WPGrabTwitterPics {

	/*--------------------------------------------*
	 * Attributes
	 *--------------------------------------------*/
	 
	/** Refers to a single instance of this class. */
	private static $instance = null;
	
	/** Refers to the slug of the plugin screen. */
	private $wpgtp_screen_slug = null;
	

	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/
	 
	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return	WPGrabTwitterPics	A single instance of this class.
	 */
	public function get_instance() {
		return null == self::$instance ? new self : self::$instance;
	} // end get_instance;

	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	private function __construct() {

	
		/**
		 * Load needed include files
		 */
		require( dirname( __FILE__ ) . '/includes/twitter.php' );

		/**
		 * Define globals
		 */
    	define("WP_GRAB_TWITTER_PICS_PERMISSIONS", "manage_options");

		/**
		 * Load plugin text domain
		 */
		add_action( 'init', array( $this, 'wpgtp_textdomain' ) );

	    /*
	     * Add the options page and menu item.
	     */
	    add_action( 'admin_menu', array( $this, 'wpgtp_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'wpgtp_admin_init' ) );

	    /*
		 * Register site stylesheets and JavaScript
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'register_wpgtp_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_wpgtp_scripts' ) );

	    /*
		 * Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
		 */
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

	    /*
	     * Here's where we define the custom functionality for this plugin.
	     */     
	     
		add_action( "admin_post_grab_tweets", array ( $this, 'wpgtp_grab_tweets' ) );	
		add_action( "admin_post_wpgtp_clear_settings", array ( $this, 'wpgtp_clear_settings' ) );	
        add_action( 'admin_notices', array ( $this, 'render_msg' ) );

	} // end constructor

	/**
	 * Fired when the plugin is activated.
	 *
	 * @param	boolean	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
	 */
	public function activate( $network_wide ) {

	} // end activate

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param	boolean	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
	 */
	public function deactivate( $network_wide ) {

	} // end deactivate

	/**
	 * Loads the plugin text domain for translation
	 */
	public function wpgtp_textdomain() {

		$domain = 'wp-grab-twitter-pics-locale';
		$locale = apply_filters( 'wpgtp_locale', get_locale(), $domain );
		
        load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

	} // end wpgtp_textdomain

	/**
	 * Registers and enqueues admin-specific styles.
	 */
	public function register_admin_styles() {

		/*
		 * Check if the plugin has registered a settings page
		 * and if it has, make sure only to enqueue the scripts on the relevant screens
		 */
		
	    if ( isset( $this->wpgtp_screen_slug ) ){
	    	
	    	/*
			 * Check if current screen is the admin page for this plugin
			 * Don't enqueue stylesheet or JavaScript if it's not
			 */
	    
			 $screen = get_current_screen();
			 if ( $screen->id == $this->wpgtp_screen_slug ) {
			 	wp_enqueue_style( 'plugin-name-admin-styles', plugins_url( 'css/admin.css', __FILE__ ) );
			 } // end if
	    
	    } // end if
	    
	} // end register_admin_styles

	/**
	 * Registers and enqueues admin-specific JavaScript.
	 */
	public function register_admin_scripts() {

		/*
		 * Check if the plugin has registered a settings page
		 * and if it has, make sure only to enqueue the scripts on the relevant screens
		 */
		
	    if ( isset( $this->wpgtp_screen_slug ) ){
	    	
	    	/*
			 * Check if current screen is the admin page for this plugin
			 * Don't enqueue stylesheet or JavaScript if it's not
			 */
	    
			 $screen = get_current_screen();
			 if ( $screen->id == $this->wpgtp_screen_slug ) {
			 	wp_enqueue_script( 'plugin-name-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ) );
			 } // end if
	    
	    } // end if

	} // end register_admin_scripts

	/**
	 * Registers and enqueues plugin-specific styles.
	 */
	public function register_wpgtp_styles() {
		wp_enqueue_style( 'plugin-name-plugin-styles', plugins_url( 'css/display.css', __FILE__ ) );
	} // end register_wpgtp_styles

	/**
	 * Registers and enqueues plugin-specific scripts.
	 */
	public function register_wpgtp_scripts() {
		wp_enqueue_script( 'plugin-name-plugin-script', plugins_url( 'js/display.js', __FILE__ ), array( 'jquery' ) );
	} // end register_wpgtp_scripts

	/**
	 * Registers the administration menu for this plugin into the WordPress Dashboard menu.
	 */
	public function wpgtp_admin_menu() {
	    	
	    add_menu_page(
	        __("Grab Twitter Pics : Settings"),
	        __("Grab Twitter Pics"),
	        WP_GRAB_TWITTER_PICS_PERMISSIONS,
	        "wp-grab-twitter-pics",
	        array( $this, 'wpgtp_settings_page' )
	    );
	    add_submenu_page(
	        "wp-grab-twitter-pics",
	        __("Grab Twitter Pics : Settings"),
	        __("Settings"),
	        WP_GRAB_TWITTER_PICS_PERMISSIONS,
	        "wp-grab-twitter-pics",
	        array( $this, 'wpgtp_settings_page' )
	    );
	    add_submenu_page(
	        "wp-grab-twitter-pics",
	        __("Grab Twitter Pics : Grab Tweets"),
	        __("Grab"),
	        WP_GRAB_TWITTER_PICS_PERMISSIONS,
	        "wp-grab-twitter-pics-items",
	        array( $this, 'wpgtp_grab_page' )
	    );
    	
	} // end wpgtp_admin_menu
	
	/**
	 * Renders the settings page for this plugin.
	 */

	public function wpgtp_settings_page() {
	
		$redirect = urlencode( remove_query_arg( 'msg', $_SERVER['REQUEST_URI'] ) );
        $redirect = urlencode( $_SERVER['REQUEST_URI'] );
        
        $action_name = "wpgtp_clear_settings";
        $nonce_name = "wp-grab-twitter-pics";
	
	    echo '
	    <div class="wrap">
	        <div id="icon-options-general" class="icon32"><br /></div>
	        <h2>'.__("Grab Twitter Pics Settings").'</h2>
	        <br />'; ?>
	        
	        <?php /* $max_id = esc_attr( get_option( 'wpgtp_twitter_gallery_max_id' ) ); ?>
	        <p>Currently, max_id of twitter is <?php echo $max_id; ?></p> */ ?>
	        
	        <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST">
	            <input type="hidden" name="action" value="<?php echo $action_name; ?>">
	            <?php wp_nonce_field( $action_name, $nonce_name . '_nonce', FALSE ); ?>
	            <input type="hidden" name="_wp_http_referer" value="<?php echo $redirect; ?>">
	            <?php do_settings_sections( 'wp-grab-twitter-pics-stats' ); ?>
	            <?php submit_button( 'Clear All Stats' ); ?>
	        </form>
	        

	        <form action="options.php" method="POST">
	            <?php settings_fields( 'plugin-options-group' ); ?>
	            <?php do_settings_sections( 'wp-grab-twitter-pics-options' ); ?>
	            <?php submit_button(); ?>
	        <?php echo '</form>
	    </div>';
	}
	
	/**
	 * Renders the grab page for this plugin.
	 */
	 
	public function wpgtp_grab_page() {
	
		$redirect = urlencode( remove_query_arg( 'msg', $_SERVER['REQUEST_URI'] ) );
        $redirect = urlencode( $_SERVER['REQUEST_URI'] );
        
        $action_name = "grab_tweets";
        $nonce_name = "wp-grab-twitter-pics";
	
	    echo '
	    <div class="wrap">
	        <div id="icon-edit-pages" class="icon32"><br /></div>
	        <h2>'.__("Grab Tweets").'</h2>
	        <br />'; ?>
	        <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST">
            <input type="hidden" name="action" value="<?php echo $action_name; ?>">
            <?php wp_nonce_field( $action_name, $nonce_name . '_nonce', FALSE ); ?>
            <input type="hidden" name="_wp_http_referer" value="<?php echo $redirect; ?>">

            <?php submit_button( 'Grab Tweets' ); ?>
        </form>
        <?php echo '
	    </div>';
	}
	 

	/**
	 * This inits the sections and fields in the settings screens
	 */
	
	public function wpgtp_admin_init() {
	
	    register_setting( 'plugin-stats-group', 'wpgtp-stats' );
	    add_settings_section( 'section-stats', 'Stats', array( $this, 'options_stats_callback' ), 'wp-grab-twitter-pics-stats' );
	    add_settings_field( 'section-stats-maxid', 'Twitter max_id', array( $this, 'options_maxid_field_callback' ), 'wp-grab-twitter-pics-stats', 'section-stats' );
	    add_settings_field( 'section-stats-last-grab', 'Last Grab', array( $this, 'options_lastgrab_field_callback' ), 'wp-grab-twitter-pics-stats', 'section-stats' );
	    	
	    register_setting( 'plugin-options-group', 'wpgtp-hashtag' );
	    register_setting( 'plugin-options-group', 'wpgtp-twitter-consumer-key' );
	    register_setting( 'plugin-options-group', 'wpgtp-twitter-consumer-secret' );
	    register_setting( 'plugin-options-group', 'wpgtp-setOAuthToken' );
	    register_setting( 'plugin-options-group', 'wpgtp-setOAuthTokenSecret' );
	    add_settings_section( 'section-options', 'Options', array( $this, 'options_section_callback' ), 'wp-grab-twitter-pics-options' );
	    add_settings_field( 'section-options-hashtag', 'Keyword', array( $this, 'options_hashtag_field_callback' ), 'wp-grab-twitter-pics-options', 'section-options' );
	    add_settings_field( 'section-options-twitter-consumer-key', 'Twitter Consumer Key', array( $this, 'options_twitter_consumer_key_field_callback' ), 'wp-grab-twitter-pics-options', 'section-options' );
	    add_settings_field( 'section-options-twitter-consumer-secret', 'Twitter Consumer Secret', array( $this, 'options_twitter_consumer_secret_field_callback' ), 'wp-grab-twitter-pics-options', 'section-options' );
	    add_settings_field( 'section-options-setOAuthToken', 'setOAuthToken', array( $this, 'options_setOAuthToken_field_callback' ), 'wp-grab-twitter-pics-options', 'section-options' );
	    add_settings_field( 'section-options-setOAuthTokenSecret', 'setOAuthTokenSecret', array( $this, 'options_setOAuthTokenSecret_field_callback' ), 'wp-grab-twitter-pics-options', 'section-options' );
	    
	} // end wpgtp_admin_init

		function options_stats_callback() {
			// nothing to say here, but just in case
		}

		function options_maxid_field_callback() {
		    $setting = esc_attr( get_option( 'wpgtp_twitter_gallery_max_id' ) );
		    if (!$setting) {
				echo '<em>No max_id yet</em>';   
		    } else { 
			    echo $setting;
			}
		}
		
		function options_lastgrab_field_callback() {
		    $setting = esc_attr( get_option( 'wpgtp_twitter_last_grab' ) );
		    if (!$setting) {
				echo '<em>Nothing has been attempted.</em>'; 
		    } else { 
			    echo date('F jS, Y g:ia T', $setting);
			}
		}
	
		function options_section_callback() {
		    echo 'Keywords are usually hashtags. Include the "#". For example: "#wcmia"';
		}
		
		function options_hashtag_field_callback() {
		    $setting = esc_attr( get_option( 'wpgtp-hashtag' ) );
		    echo "<input type='text' name='wpgtp-hashtag' value='$setting' />";
		}
		
		function options_twitter_consumer_key_field_callback() {
		    $setting = esc_attr( get_option( 'wpgtp-twitter-consumer-key' ) );
		    echo "<input type='text' name='wpgtp-twitter-consumer-key' value='$setting' />";
		}
		
		function options_twitter_consumer_secret_field_callback() {
		    $setting = esc_attr( get_option( 'wpgtp-twitter-consumer-secret' ) );
		    echo "<input type='text' name='wpgtp-twitter-consumer-secret' value='$setting' />";
		}
	
		function options_setOAuthToken_field_callback() {
		    $setting = esc_attr( get_option( 'wpgtp-setOAuthToken' ) );
		    echo "<input type='text' name='wpgtp-setOAuthToken' value='$setting' />";
		}
		
		function options_setOAuthTokenSecret_field_callback() {
		    $setting = esc_attr( get_option( 'wpgtp-setOAuthTokenSecret' ) );
		    echo "<input type='text' name='wpgtp-setOAuthTokenSecret' value='$setting' />";
		}
	
	
	/*--------------------------------------------*
	 * Core Functions
	 *---------------------------------------------*/

	/*
	 * This handles what happens when the 'clear all settings' button is pushed on the settings page.
	 * This attempts to remove and/or reset values.
	 */
	
	public function wpgtp_clear_settings() {

		// check nonce
        if ( ! wp_verify_nonce( $_POST[ 'wp-grab-twitter-pics' . '_nonce' ], 'wpgtp_clear_settings' ) )
            die( 'Invalid nonce.' . var_export( $_POST, true ) );
            
        // proceed with removing options and data

        	// remove the twitter max_id
        	
        	delete_option( 'wpgtp_twitter_gallery_max_id' );
        	
        	// clear the "last checked date"
        	
        	delete_option( 'wpgtp_twitter_last_grab' );        	
        
       // ok, let's get back to where we were, most likely the settings page
       
		$msg = "settingsreset";       
       
		$url = add_query_arg( 'msg', $msg, urldecode( $_POST['_wp_http_referer'] ) );
		
		wp_safe_redirect( $url );
		
		exit;


    } // end wpgtp_clear_settings
    
	/*
	 * wpgtp_grab_tweets() is the bulk of the plugin. It interacts with the twitter class to parse through tweets via the
	 * hashtag, find images, and save those images (along with tweet metadata) as a WordPress media item
	 */
	
	public function wpgtp_grab_tweets() {

		// check nonce
        if ( ! wp_verify_nonce( $_POST[ 'wp-grab-twitter-pics' . '_nonce' ], 'grab_tweets' ) )
            die( 'Invalid nonce.' . var_export( $_POST, true ) );
            
        // proceeding forward - woot!
        
        // let's grab the hashtag, henceforth known as the "tag"
	    $tag = esc_attr( get_option( 'wpgtp-hashtag' ) );
	    $msg = '';
           
		// create instance
		$twitter = new Twitter(esc_attr( get_option( 'wpgtp-twitter-consumer-key' ) ), esc_attr( get_option( 'wpgtp-twitter-consumer-secret' ) ));
		
		// set tokens
		$twitter->setOAuthToken(esc_attr( get_option( 'wpgtp-setOAuthToken' ) ));
		$twitter->setOAuthTokenSecret(esc_attr( get_option( 'wpgtp-setOAuthTokenSecret' ) ));
		
		// check and see if the twitter max_id exists in the site options - so we don't have to parse tweets we've parsed already		
		$twitter_max_id = get_option( 'wpgtp_twitter_gallery_max_id' );
		
		/**
		 * The following is the params for the searchTweets function - just leaving this here for easy reference
		 * and to understand what you are reading as you examine the next few lines of code.
		 *
		 * @return	array
		 * @param	string $q						Search query. Should be URL encoded. Queries will be limited by complexity.
		 * @param 	string[optional] $lang			Restricts tweets to the given language, given by an ISO 639-1 code.
		 * @param 	string[optional] $locale		Specify the language of the query you are sending (only ja is currently effective). This is intended for language-specific clients and the default should work in the majority of cases.
		 * @param 	int[optional] $rpp				The number of tweets to return per page, up to a max of 100.
		 * @param 	int[optional] $page				The page number (starting at 1) to return, up to a max of roughly 1500 results (based on rpp * page).
		 * @param 	string[optional] $sinceId		Returns results with an ID greater than (that is, more recent than) the specified ID. There are limits to the number of Tweets which can be accessed through the API. If the limit of Tweets has occured since the since_id, the since_id will be forced to the oldest ID available.
		 * @param 	string[optional] $until			Returns tweets generated before the given date. Date should be formatted as YYYY-MM-DD.
		 * @param 	string[optional] $geocode		Returns tweets by users located within a given radius of the given latitude/longitude. The location is preferentially taking from the Geotagging API, but will fall back to their Twitter profile. The parameter value is specified by "latitude,longitude,radius", where radius units must be specified as either "mi" (miles) or "km" (kilometers). Note that you cannot use the near operator via the API to geocode arbitrary locations; however you can use this geocode parameter to search near geocodes directly.
		 * @param 	bool[optional] $showUser		When true, prepends ":" to the beginning of the tweet. This is useful for readers that do not display Atom's author field. The default is false.
		 * @param 	string[optional] $resultType	Specifies what type of search results you would prefer to receive. The current default is "mixed." Valid values include: mixed, recent, popular.
		 */
				
		if ( $twitter_max_id ) {
			// get search timeline w/ max_id
			$response = $twitter->searchTweets($tag, null, null, null, 'recent', 100, 1, $twitter_max_id);		
		} else {
			// get search timeline w/o the max_id
			$response = $twitter->searchTweets($tag, null, null, null, 'recent', 100 );	
		}
		
		if ( $response ) {
					
			// if there are tweets...
						
			$images = array();
			$videos = array();
			$image_counter = 0;
			$video_counter = 0;
			
			// ok, set a site var for the "max_id" that twitter gives us - so next time we don't have to parse these tweets again
			
			if ($response['search_metadata']['max_id']) {
				update_option( 'wpgtp_twitter_gallery_max_id', $response['search_metadata']['max_id'] );
			}
			
			// let's update the "last tried" field so someone knows when we last attempted to look
			
			update_option( 'wpgtp_twitter_last_grab', time() );			
						
			if ( !empty($response['statuses']) ) {
			
				foreach ($response['statuses'] as $tweet) {
										
					// loop through the tweets, we are only looking for images		
					
					$medias = $tweet['entities']['media'];
					$from_user = $tweet['from_user'];
					$from_user_id = $tweet['from_user_id'];
					$from_user_name = $tweet['from_user_name'];
					$tweet_text = $tweet['text'];
					$created_at = $tweet['created_at'];
					
					if (!empty($medias)) {
						
						foreach ($medias as $media) {
							
							// loop through each media element - usually there's only one, but you never know
							
							$media_id = $media['id'];
							$media_url = $media['media_url'];
							$media_url_ssl = $media['media_url_https'];
							$url = $media['url'];
							$display_url = $media['expanded_url'];
														
							if ($media['type'] == "photo") { // we only want to pull photos 
							
						        $images[] = array(
							        "twitter_id" => $media_id,
							        "title" => htmlspecialchars($tweet_text),
							        "twitter_url" => htmlspecialchars($display_url),
							        "date_taken" => htmlspecialchars($created_at),
							        "owner_id" => htmlspecialchars($from_user_id),
							        "owner_name" => htmlspecialchars($from_user),
							        "date_added" => htmlspecialchars($date_taken),
							        "standard_src" => htmlspecialchars($media_url),
							        "thumbnail_src" => htmlspecialchars($media_url)
						        );
					        
					        }				
							
						}	
						
					} // empty media?
					
			        // check and see if there's a vine video (which this plugin will support in the next version)
			        			        
			        if ( !empty($tweet['entities']['urls']) ) {
			        			        
			        	foreach ( $tweet['entities']['urls'] as $url ) {
			        				        
							$pos = strpos($url['expanded_url'], 'vine.co');
							
							if ( $pos !== false ) { // there's a vine url, so let's take a look						
							
								$response = wp_remote_get ( $url['expanded_url'] );
								
								if ( is_wp_error( $response ) ) {
		
									$error_message = $response->get_error_message();
									echo "Something went wrong: $error_message";
		
								} else {
								
									$html = wp_remote_retrieve_body($response);
									
									//parsing begins here:
									$doc = new DOMDocument();
									@$doc->loadHTML($html);
									$nodes = $doc->getElementsByTagName('title');
									
									//get and display what you need:
									$title = $nodes->item(0)->nodeValue;
									
									$metas = $doc->getElementsByTagName('meta');
									
									for ($i = 0; $i < $metas->length; $i++)
									{
									    $meta = $metas->item($i);
									    if($meta->getAttribute('property') == 'twitter:image')
									        $image = $meta->getAttribute('content');
									    if($meta->getAttribute('property') == 'twitter:player:stream')
									        $video = $meta->getAttribute('content');
									}
									
									$from_user_id = $tweet['user']['id'];
									$from_user = $tweet['user']['screen_name'];
									
									$videos[] = array(
								        "twitter_id" => $tweet['id'],
								        "title" => htmlspecialchars($tweet_text),
								        "twitter_url" => htmlspecialchars($display_url),
								        "date_taken" => htmlspecialchars($created_at),
								        "owner_id" => htmlspecialchars($from_user_id),
								        "owner_name" => htmlspecialchars($from_user),
								        "date_added" => htmlspecialchars($created_at),
										"expanded_url" => $url['expanded_url'],
										"type" => 'vine',
										"video_image" => $image,
										"video_info" => $video
									);
									
								} // is_wp_error
														
							} // $pos !== false
							
						} // foreach
						
					} // if !empty
							
				}
			
			} else {
				
				$msg = "Nothing found.<br/>";
				
			}
						
		    $image_id_array = array();
		    
			$attachments = get_posts( array(
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'posts_per_page' => -1,
				'post_parent' => 0
			) );
		
			if ( $attachments ) {
				foreach ( $attachments as $attachment ) {
					$post_meta = get_post_meta ( $attachment->ID );
					$current_images[] = array ( 'post_data' => $attachment, 'post_meta' => $post_meta );
					$image_id_array[] = $post_meta['wpgtp_twitter_image_id'][0]; // makes finding duplicate items easier
				}
				
			}
		
			// Ok, now loop through the images grabbed and save into WP anything that we don't have
			if ($images) {	
				foreach ($images as $image) {
						
					if ( !in_array($image['twitter_id'], $image_id_array) ) { // let's just an make sure
						
						// image doesn't exist - let's upload and add to WP media lib
						// thanks to http://theme.fm/2011/10/how-to-upload-media-via-url-programmatically-in-wordpress-2657/
						
						$url = $image['standard_src'];
						$tmp = download_url( $url );
						$file_array = array(
						    'name' => basename( $url ),
						    'tmp_name' => $tmp
						);
									
						// Check for download errors
						if ( is_wp_error( $tmp ) ) {
						    @unlink( $file_array[ 'tmp_name' ] );
							print_r ($tmp); echo "test2";
						}
												
						$id = $this->wpgtp_media_handle_sideload( $file_array, 0 );
						
						// Check for handle sideload errors.
						if ( is_wp_error( $id ) ) {
							print_r ($id); echo "test";
						    @unlink( $file_array['tmp_name'] );
						    return $id;
						}
			
						$attachment_url = wp_get_attachment_url( $id );
						
						// add image title (which was the instagram's caption)
						
						$post_content = '<a href="'.$image['twitter_url'].'">Taken on ' . date('F jS, Y - g:ia', strtotime($image['date_taken']));
						
						if ( $image['owner_name'] ) {
							$post_content .= ' by '.$image['owner_name'].' ';	
						} 
						
						$post_content .= '</a> via Twitter.';
						
						$data = array(
							'ID' => $id,
						    'post_excerpt' => $image['title'],
						    'post_content' => $post_content,
						    'post_title' => $image['title']
						);
						
						wp_update_post( $data );
			
						// add image metadata
			
						add_post_meta($id, 'wpgtp_twitter_image_type', 'wpgtp_gallery_image', true);
						add_post_meta($id, 'wpgtp_twitter_image_id', $image['twitter_id'], true);
						if ( $image['twitter_url'] ) { add_post_meta($id, 'wpgtp_twitter_image_link', $image['twitter_url'], true); }
						if ( $image['owner_name'] ) { add_post_meta($id, 'wpgtp_twitter_image_caption_username', $image['owner_name'], true); }
						if ( $image['owner_id'] ) { add_post_meta($id, 'wpgtp_twitter_image_caption_username_id', $image['owner_id'], true); }
									
						$image_counter++;
			
					}
					
				}
			}
			
			$msg = "$image_counter images pulled from Twitter.";



		}


		$url = add_query_arg( 'msg', $msg, urldecode( $_POST['_wp_http_referer'] ) );

        wp_safe_redirect( $url );
        exit;


	} // end wpgtp_grab_tweets()
	
		
	/*
	 * Simple render message script
	 */
    public function render_msg()
    {
    
        if ( ! isset ( $_GET['msg'] ) )
            return;

        $text = FALSE;

        if ( 'settingsreset' === $_GET['msg'] )
            $this->msg_text = 'Settings Have Been Reset';
            
        if ( $this->msg_text ) {
        
	        echo '<div class="updated"><p>' . $this->msg_text . '</p></div>';
            
        }
    }
	
	
	/*
	 * I had to create my own media handle sideload function because i got a 'white screen' with the official one
	 * with no visible errors that i could see, even in the logs
	 */
	
	public function wpgtp_media_handle_sideload($file_array, $post_id, $desc = null, $post_data = array()) {
	        $overrides = array('test_form'=>false);
	
	        $file = wp_handle_sideload($file_array, $overrides);
	        if ( isset($file['error']) )
	                return new WP_Error( 'upload_error', $file['error'] );
	
	        $url = $file['url'];
	        $type = $file['type'];
	        $file = $file['file'];
	        $title = preg_replace('/\.[^.]+$/', '', basename($file));
	        $content = '';
	        
	        /* 
	
	        // use image exif/iptc data for title and caption defaults if possible
	        if ( $image_meta = @wp_read_image_metadata($file) ) {
	                if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) )
	                        $title = $image_meta['title'];
	                if ( trim( $image_meta['caption'] ) )
	                        $content = $image_meta['caption'];
	        }
	        
	        */
	
	        if ( isset( $desc ) )
	                $title = $desc;
	
	        // Construct the attachment array
	        $attachment = array_merge( array(
	                'post_mime_type' => $type,
	                'guid' => $url,
	                'post_parent' => $post_id,
	                'post_title' => $title,
	                'post_content' => $content,
	        ), $post_data );
	
	        // This should never be set as it would then overwrite an existing attachment.
	        if ( isset( $attachment['ID'] ) )
	                unset( $attachment['ID'] );
	
	        // Save the attachment metadata
	        $id = wp_insert_attachment($attachment, $file, $post_id);
	        if ( !is_wp_error($id) )
	                wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
	
	        return $id;
	}	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

} // end class


WPGrabTwitterPics::get_instance();