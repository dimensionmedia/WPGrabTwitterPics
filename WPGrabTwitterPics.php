<?php
/*
Plugin Name: Grab Twitter Pics
Plugin URI: http://www.davidbisset.com/wp-grab-twitter-pics
Description: This plugin will search through recent tweets with embedded photos (containing a certain hashtag), and import those photos along with the tweet metadata into a custom post type.
Version: 0.3
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
		require_once( dirname( __FILE__ ) . '/includes/twitter.php' );

		/**
		 * Define globals
		 */
    		if ( ! defined('WP_GRAB_TWITTER_PICS_PERMISSIONS') ) define("WP_GRAB_TWITTER_PICS_PERMISSIONS", "manage_options");

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
	     
		add_action( "admin_post_wpgtp_grab_twitter_posts", array ( $this, 'wpgtp_grab_twitter_posts' ) );	
		add_action( "admin_post_wpgtp_clear_settings", array ( $this, 'wpgtp_clear_settings' ) );	
        add_action( "admin_notices", array ( $this, 'render_msg' ) );
        add_action( "init", array ( $this, 'wpgtp_register_cpt' ) );
        add_action( "save_post", array ( $this, 'wpgtp_save_events_meta' ) , 1, 2);
        add_action( "init", array ( $this, 'wpgtp_register_tax' ) );        
        
        

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
		// wp_enqueue_style( 'plugin-name-plugin-styles', plugins_url( 'css/display.css', __FILE__ ) );
	} // end register_wpgtp_styles

	/**
	 * Registers and enqueues plugin-specific scripts.
	 */
	public function register_wpgtp_scripts() {
		// wp_enqueue_script( 'plugin-name-plugin-script', plugins_url( 'js/display.js', __FILE__ ), array( 'jquery' ) );
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
        
        $action_name = "wpgtp_grab_twitter_posts";
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
	 * Register CTP
	 */
	 
	public function wpgtp_register_cpt() {
		
	    $labels = array( 
	        'name' => _x( 'Tweets', 'faq' ),
	        'singular_name' => _x( 'Tweet', 'faq' ),
	        'add_new' => _x( 'Add New', 'faq' ),
	        'add_new_item' => _x( 'Add New Tweet', 'faq' ),
	        'edit_item' => _x( 'Edit Tweet', 'faq' ),
	        'new_item' => _x( 'New Tweet', 'faq' ),
	        'view_item' => _x( 'View Tweet', 'faq' ),
	        'search_items' => _x( 'Search Tweets', 'faq' ),
	        'not_found' => _x( 'No Tweets found', 'faq' ),
	        'not_found_in_trash' => _x( 'No Tweets found in Trash', 'faq' ),
	        'parent_item_colon' => _x( 'Parent Tweet:', 'faq' ),
	        'menu_name' => _x( 'Tweets', 'faq' ),
	    );
	    
	    //set up the rewrite rules
	    $rewrite = array(
	        'slug' => 'tweets'
	    );
	
	    $args = array( 
	        'labels' => $labels,
	        'hierarchical' => false,
	        'description' => 'Stored tweets from Twitter.',
	        'supports' => array( 'title', 'page-attributes', 'editor', 'thumbnail', 'comments' ),        
	        'public' => true,
	        'show_ui' => true,
	        'show_in_menu' => true,
	        'show_in_nav_menus' => false,
	        'publicly_queryable' => true,
	        'exclude_from_search' => false,
	        'has_archive' => false,
	        'query_var' => true,
	        'can_export' => true,
	        'rewrite' => $rewrite,
	        'capability_type' => 'post',
	        'register_meta_box_cb' => array ( $this, 'wpgtp_add_tweets_metabox' )
	    );
	
	    register_post_type( 'wpgtp_tweets', $args );
    
	}
	
	/*
	 * Add Meta Box For This Post Type
	 */
	
	public function wpgtp_add_tweets_metabox() {
		
		add_meta_box('wpgtp_tweet_information', 'Tweet Information', array ( $this, 'wpgtp_tweets_meta' ), 'wpgtp_tweets', 'normal', 'default');
		
	}

	/*
	 * Add Fields For Meta Box
	 */
	
	public function wpgtp_tweets_meta() {
		global $post;
		
		// Noncename needed to verify where the data originated
		echo '<input type="hidden" name="tweetmeta_noncename" id="tweetmeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
		
		$wpgtp_tw_user_name = get_post_meta($post->ID, 'wpgtp_tw_user_name', true);
		$wpgtp_tw_user_id = get_post_meta($post->ID, 'wpgtp_tw_user_id', true);		
		$wpgtp_tw_id = get_post_meta($post->ID, 'wpgtp_tw_id', true);
		$wpgtp_tw_user_screen_name = get_post_meta($post->ID, 'wpgtp_tw_user_screen_name', true);
		$wpgtp_tw_user_profile_avatar_url = get_post_meta($post->ID, 'wpgtp_tw_user_profile_avatar_url', true);
		
		$wpgtp_tw_video_expanded_url = get_post_meta($post->ID, 'wpgtp_tw_video_expanded_url', true);
		$wpgtp_tw_video_type = get_post_meta($post->ID, 'wpgtp_tw_video_type', true);
		$wpgtp_tw_video_image = get_post_meta($post->ID, 'wpgtp_tw_video_image', true);
		$wpgtp_tw_video_info = get_post_meta($post->ID, 'wpgtp_tw_video_info', true);
		
		// Echo out the fields
		echo '<label>Twitter Username:</label> <input type="text" name="wpgtp_tw_user_name" value="' . $wpgtp_tw_user_name  . '" class="widefat" />';
		echo '<label>Twitter Screen Name:</label> <input type="text" name="wpgtp_tw_user_screen_name" value="' . $wpgtp_tw_user_screen_name  . '" class="widefat" />';
		echo '<label>Twitter User ID:</label> <input type="text" name="wpgtp_tw_user_id" value="' . $wpgtp_tw_user_id  . '" class="widefat" />';
		echo '<label>Tweet ID:</label> <input type="text" name="wpgtp_tw_id" value="' . $wpgtp_tw_id  . '" class="widefat" />';
		echo '<label>Profile Avatar Url:</label> <input type="text" name="wpgtp_tw_user_profile_avatar_url" value="' . $wpgtp_tw_user_profile_avatar_url  . '" class="widefat" />';
		echo '<br/>';
		echo '<label>Video Expanded URL:</label> <input type="text" name="wpgtp_tw_video_expanded_url" value="' . $wpgtp_tw_video_expanded_url  . '" class="widefat" />';
		echo '<label>Video Type:</label> <input type="text" name="wpgtp_tw_video_type" value="' . $wpgtp_tw_video_type  . '" class="widefat" />';
		echo '<label>Video Image (Thumbnail):</label> <input type="text" name="wpgtp_tw_video_image" value="' . $wpgtp_tw_video_image  . '" class="widefat" />';
		echo '<label>Video Info:</label> <input type="text" name="wpgtp_tw_video_info" value="' . $wpgtp_tw_video_info  . '" class="widefat" />';
		
	}


	
	/*
	 * Saving Metabox Data
	 */
	
	public function wpgtp_save_events_meta($post_id, $post) {
	
		if ( isset( $_POST['tweetmeta_noncename'] ) ) {
		
			// verify this came from the our screen and with proper authorization,
			// because save_post can be triggered at other times
					
			if ( !wp_verify_nonce( $_POST['tweetmeta_noncename'], plugin_basename(__FILE__) )) {
				return $post->ID;
			}
		
			// Is the user allowed to edit the post or page?
			
			if ( !current_user_can( 'edit_post', $post->ID ))
				return $post->ID;
		
			// OK, we're authenticated: we need to find and save the data
			// We'll put it into an array to make it easier to loop though.
			
			$tweets_meta['wpgtp_tw_user_name'] = $_POST['wpgtp_tw_user_name'];
			$tweets_meta['wpgtp_tw_user_id'] = $_POST['wpgtp_tw_user_id'];
			$tweets_meta['wpgtp_tw_id'] = $_POST['wpgtp_tw_id'];
			$tweets_meta['wpgtp_tw_user_profile_avatar_url'] = $_POST['wpgtp_tw_user_profile_avatar_url'];
			$tweets_meta['wpgtp_tw_video_expanded_url'] = $_POST['wpgtp_tw_video_expanded_url'];
			$tweets_meta['wpgtp_tw_video_type'] = $_POST['wpgtp_tw_video_type'];
			$tweets_meta['wpgtp_tw_video_image'] = $_POST['wpgtp_tw_video_image'];
			$tweets_meta['wpgtp_tw_video_info'] = $_POST['wpgtp_tw_video_info'];
			
			// Add values of $events_meta as custom fields
			
			foreach ($tweets_meta as $key => $value) { // Cycle through the $tweets_meta array
			
				if( $post->post_type == 'revision' ) return; // Don't store custom data twice
				
				$value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
				
				if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
					update_post_meta($post->ID, $key, $value);
				} else { // If the custom field doesn't have a value
					add_post_meta($post->ID, $key, $value);
				}
				
				if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
			}
		
		}
	
	}
	


	/*
	 * Register Tax Term
	 */
	 
	public function wpgtp_register_tax() {
	

		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
		    'name' => _x( 'Tweet Types', 'taxonomy general name' ),
		    'singular_name' => _x( 'Tweet Types', 'taxonomy singular name' ),
		    'search_items' =>  __( 'Search Tweet Types' ),
		    'all_items' => __( 'All Tweet Types' ),
		    'parent_item' => __( 'Parent Tweet Type' ),
		    'parent_item_colon' => __( 'Parent Tweet Type:' ),
		    'edit_item' => __( 'Edit Tweet Type' ), 
		    'update_item' => __( 'Update Tweet Type' ),
		    'add_new_item' => __( 'Add New Tweet Type' ),
		    'new_item_name' => __( 'New Tweet Type Name' ),
		    'menu_name' => __( 'Tweet Types' ),
		); 	
		
		register_taxonomy('wpgtp_tweet_types',array('wpgtp_tweets'), array(
		    'hierarchical' => true,
		    'labels' => $labels,
		    'show_ui' => true,
		    'query_var' => true
		));
		

		// add the media category option, if it exits	

		if ( taxonomy_exists('wpgtp_media_categories') ) {
	
			$term = term_exists('Twitter', 'wpgtp_media_categories');
			
			if ($term !== 0 && $term !== null) {
			
				// this exists, do nothing
				
			} else {

				$parent_term_id = 0; // there's no parent (yet)
				
				wp_insert_term(
				  'Twitter', // the term 
				  'wpgtp_media_categories', // the taxonomy
				  array(
				    'description'=> 'Tweets from the Twitter social network.',
				    'slug' => 'twitter',
				    'parent'=> $parent_term_id
				  )
				);
				
			} // if term isn't null
			
		} // if tax exists
		
	} // wpgtp_register_tax
	
	
	/*
	 * Clear Settings Page
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
	 * wpgtp_grab_twitter_posts() wraps around wpgtp_do_grab_twitter_posts() and handles security when
	 * the grabbing is called manually via the WordPress backend on the grab page
	 */
	
	public function wpgtp_grab_twitter_posts() {

		// check nonce
		if ( ! wp_verify_nonce( $_POST[ 'wp-grab-twitter-pics' . '_nonce' ], 'wpgtp_grab_twitter_posts' ) )
			die( 'Invalid nonce.' . var_export( $_POST, true ) );

		// since nonce checks out, call the main function
		$msg = $this->wpgtp_do_grab_twitter_posts();

		$url = add_query_arg( 'msg', $msg, urldecode( $_POST['_wp_http_referer'] ) );

		wp_safe_redirect( $url );
		exit;

	}
    
    
	/*
	 * wpgtp_do_grab_twitter_posts() is the bulk of the plugin. It interacts with the twitter class to parse through tweets via the
	 * hashtag, find images, and save those images (along with tweet metadata) as a WordPress media item
	 */    
    
	public function wpgtp_do_grab_twitter_posts() {
	            
        // proceeding forward - woot!
        
        // let's grab the hashtag, henceforth known as the "tag"
	    $tag = esc_attr( get_option( 'wpgtp-hashtag' ) );
	    $msg = '';
           
		// create instance
		$twitter = new Twitter(esc_attr( get_option( 'wpgtp-twitter-consumer-key' ) ), esc_attr( get_option( 'wpgtp-twitter-consumer-secret' ) ));
		
		// set tokens
		$twitter->setOAuthToken(esc_attr( get_option( 'wpgtp-setOAuthToken' ) ));
		$twitter->setOAuthTokenSecret(esc_attr( get_option( 'wpgtp-setOAuthTokenSecret' ) ));
			
		/**
		 * The following is the params for the searchTweets function - just leaving this here for easy reference
		 * and to understand what you are reading as you examine the next few lines of code.
		 *
	    /**
	     * Returns tweets that match a specified query.
	     *
	     * @param  string           $q               A UTF-8, URL-encoded search query of 1,000 characters maximum, including operators. Queries may additionally be limited by complexity.
	     * @param  string[optional] $geocode         Returns tweets by users located within a given radius of the given latitude/longitude. The location is preferentially taking from the Geotagging API, but will fall back to their Twitter profile. The parameter value is specified by "latitude,longitude,radius", where radius units must be specified as either "mi" (miles) or "km" (kilometers). Note that you cannot use the near operator via the API to geocode arbitrary locations; however you can use this geocode parameter to search near geocodes directly. A maximum of 1,000 distinct "sub-regions" will be considered when using the radius modifier.
	     * @param  string[optional] $lang            Restricts tweets to the given language, given by an ISO 639-1 code. Language detection is best-effort.
	     * @param  string[optional] $locale          Specify the language of the query you are sending (only ja is currently effective). This is intended for language-specific consumers and the default should work in the majority of cases.
	     * @param  string[optional] $resultType      Specifies what type of search results you would prefer to receive. The current default is "mixed." Valid values include: mixed: Include both popular and real time results in the response, recent: return only the most recent results in the response, popular: return only the most popular results in the response.
	     * @param  int[optional]    $count           The number of tweets to return per page, up to a maximum of 100. Defaults to 15. This was formerly the "rpp" parameter in the old Search API.
	     * @param  string[optional] $until           Returns tweets generated before the given date. Date should be formatted as YYYY-MM-DD. Keep in mind that the search index may not go back as far as the date you specify here.
	     * @param  string[optional] $sinceId         Returns results with an ID greater than (that is, more recent than) the specified ID. There are limits to the number of Tweets which can be accessed through the API. If the limit of Tweets has occured since the since_id, the since_id will be forced to the oldest ID available.
	     * @param  string[optional] $maxId           Returns results with an ID less than (that is, older than) or equal to the specified ID.
	     * @param  bool[optional]   $includeEntities The entities node will be disincluded when set to false.
	     * @return array
	     */
						 
		$twitter_max_id = get_option( 'wpgtp_twitter_gallery_max_id', 0 );			

		if ( $twitter_max_id && $twitter_max_id > 0 ) {
			// get search timeline w/ max_id
			$response = $twitter->searchTweets($tag, null, null, null, 'recent', 100, null, $twitter_max_id);		
		} else {
			// get search timeline w/o the max_id
			$response = $twitter->searchTweets($tag, null, null, null, 'recent', 100 );	
		}
				
		if ( $response ) {
					
			// if there are tweets...
						
			$new_tweets = array();
		    $existing_tweet_id_array = array();
			$image_counter = 0;
			$video_counter = 0;
			$tw_medias = array();
			$max_id_from_twitter = 0;
			$grabbed_latest = false;
			
			if ( !empty($response['statuses']) ) {

//				echo "-<br/>"; print_r ($response); exit;
				
				// ok, set a site var for the "max_id" that twitter gives us - so next time we don't have to parse these tweets again
				
				$max_id_from_twitter = $response['search_metadata']['max_id']; // grab the max_id from the twitter response
				
				if ( isset( $max_id_from_twitter ) && $max_id_from_twitter > $twitter_max_id ) { // if we gotten this far, this should be true but it's a double-check
				
					$grabbed_latest = true; // we made it this far, so make this true
				
					// now go through the tweets response, only adding tweets that we don't already have
				
					foreach ($response['statuses'] as $tweet) { // get tweets from response
					
						$images = array(); // this stores media, mostly photos
						$videos = array();
											
						// loop through the tweets
						
						if ( $tweet['id'] > $current_twitter_gallery_max_id && !isset($tweet['retweeted_status']) ) { // if we don't already have this tweet, and we don't want retweets
						
							// get basic metadata
							
							$tw_user_name = $tweet['user']['name']; // twitter's user name (first/last name)
							$tw_user_screen_name = $tweet['user']['screen_name']; // twitter's user name (first/last name)						
							$tw_user_profile_avatar_url = $tweet['user']['profile_image_url'];
							$tw_user_id = $tweet['user']['id']; // twitter's user ID
							$tw_text = $tweet['text']; // actual <140 character tweet
							$tw_created_at = $tweet['created_at']; // date tweet was created
							$tw_id = $tweet['id']; // twitter ID		
						
						
							// check tweet's media
						
							if ( isset ( $tweet['entities']['media'] ) ) { 
							
								$tw_medias = $tweet['entities']['media']; // any attached media - entities/hashtags also exist
														
								foreach ( $tw_medias as $tw_media ) {
									
									// loop through each media element - usually there's only one, but you never know
									
									if ($tw_media['type'] == "photo") { // we only want to pull photos 
									
								        $images[] = array(
									        "media_url" => htmlspecialchars($tw_media['media_url'])
								        );
							        
							        }				
									
								}	
								
							} // if !empty $tw_medias
							
					        // check and see if there's a vine video
					        			        
					        if ( !empty($tweet['entities']['urls']) ) {
					        				        			        
					        	foreach ( $tweet['entities']['urls'] as $url ) {
					        				        
									$pos = strpos($url['expanded_url'], 'vine.co');
									
									if ( $pos !== false ) { // there's a vine url, so let's take a look						
									
										$response = wp_remote_get ( $url['expanded_url'] , array( 'sslverify' => false ) );
										
										if ( is_wp_error( $response ) ) {
				
											$error_message = $response->get_error_message();
											echo "Something went wrong: $error_message"; die();
				
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
												"expanded_url" => $url['expanded_url'],
												"video_type" => 'vine',
												"video_image" => $image,
												"video_info" => $video
											);
											
										} // is_wp_error
																
									} // $pos !== false
									
								} // foreach
								
							} // if !empty
								
								
							// add to new tweets array
							
							$new_tweets[$tw_id] = array (
								'tw_user_name' => $tw_user_name,
								'tw_user_screen_name' => $tw_user_screen_name,
								'tw_user_profile_avatar_url' => $tw_user_profile_avatar_url,
								'tw_user_id' => $tw_user_id,
								'tw_text' => $tw_text,
								'tw_created_at' => $tw_created_at,
								'tw_id' => $tw_id,
								'tw_images' => $images,
								'tw_videos' => $videos	
							);
							
						
						} // if !in_array
						
					} // foreach
					
					
				} // if max_id
				
				if ( $max_id_from_twitter > 0 ) { // an attempt was made so update the max id and last grab date
				
					update_option( 'wpgtp_twitter_gallery_max_id', $max_id_from_twitter );
							
					// let's update the "last tried" field so someone knows when we last attempted to look
				
					update_option( 'wpgtp_twitter_last_grab', time() );					
				
				}
			
			} else {
				
				$msg = "Nothing found.<br/>";
				
			} // if reponse empty
			
			
//			echo "-"; print_r ($new_tweets); exit;
			
								
			//
			// Ok, now loop through the $new_tweets array and save them as WP posts
			//
			
			if ( $new_tweets ) {
				
				foreach ( $new_tweets as $new_tweet ) {
				
					// Let's define the post title
					
					$post_title = wp_strip_all_tags($new_tweet['tw_text']);
				
					// Create post object
					$tweet_post = array(
					  'post_title'    	=> $post_title,
					  'post_content'  	=> wp_strip_all_tags ( $new_tweet['tw_text'] ),
					  'post_date'		=> date('Y-m-d H:i:s', strtotime($new_tweet['tw_created_at'])),
					  'post_type'	  	=> 'wpgtp_tweets',
					  'post_status'   	=> 'publish',
					  'ping_status'	  	=> 'closed'
					);
					
					// Insert the post into the database
					$post_id = wp_insert_post( $tweet_post );
					
					if ( $post_id ) {
					
						// add to the image counter
						
						$image_counter++;
						
						// if there's an image, we need to upload and attach it to the post, make it featured
						
						if ( !empty($new_tweet['tw_images']) ) {
						
							$featured_image_done_yet = false;
							
							foreach ( $new_tweet['tw_images'] as $tweet_media_image ) {
														
								// image doesn't exist - let's upload and add to WP media lib
								// thanks to http://theme.fm/2011/10/how-to-upload-media-via-url-programmatically-in-wordpress-2657/
								
								$tmp = download_url( $tweet_media_image['media_url'] );
								$file_array = array(
								    'name' => basename( $tweet_media_image['media_url'] ),
								    'tmp_name' => $tmp
								);
								
								// Check for download errors
								if ( is_wp_error( $tmp ) ) {
								    @unlink( $file_array[ 'tmp_name' ] );
									print_r ("error: " . $tmp); die();
								}
														
								$attachment_id = $this->wpgtp_media_handle_sideload( $file_array, $post_id ); // the $post_id makes this attachment associated with the tweet post
								
								// Check for handle sideload errors.
								
								if ( is_wp_error( $attachment_id ) ) {
								
								    @unlink( $file_array['tmp_name'] );
									print_r ("error: " . $attachment_id); die();
								
								} else {
									
									// no errors? Woot.
									
									if ( !$featured_image_done_yet ) { // make the image the featured image, if there isn't one already
										
										set_post_thumbnail( $post_id, $attachment_id );
										$featured_image_done_yet = true;
										
									}
									
								}
								
								
							} // foreach tw_images
							
						} // end adding tw_images
						
						// if there's a video (only supporting vine at the moment) add the metadata
						// we are only taking the first vine video
						
						if ( !empty($new_tweet['tw_videos']) ) {
						
							if ( $new_tweet['tw_videos'][0]['expanded_url'] ) { add_post_meta($post_id, 'wpgtp_tw_video_expanded_url', $new_tweet['tw_videos'][0]['expanded_url'], true); }
							if ( $new_tweet['tw_videos'][0]['video_type'] ) { add_post_meta($post_id, 'wpgtp_tw_video_type', $new_tweet['tw_videos'][0]['video_type'], true); }
							if ( $new_tweet['tw_videos'][0]['video_image'] ) { add_post_meta($post_id, 'wpgtp_tw_video_image', $new_tweet['tw_videos'][0]['video_image'], true); }
							if ( $new_tweet['tw_videos'][0]['video_info'] ) { add_post_meta($post_id, 'wpgtp_tw_video_info', $new_tweet['tw_videos'][0]['video_info'], true); }
						
						}
						
						// add metadata to post
												
						if ( $new_tweet['tw_user_name'] ) { add_post_meta($post_id, 'wpgtp_tw_user_name', $new_tweet['tw_user_name'], true); }
						if ( $new_tweet['tw_user_id'] ) { add_post_meta($post_id, 'wpgtp_tw_user_id', $new_tweet['tw_user_id'], true); }
						if ( $new_tweet['tw_id'] ) { add_post_meta($post_id, 'wpgtp_tw_id', $new_tweet['tw_id'], true); }
						if ( $new_tweet['tw_user_screen_name'] ) { add_post_meta($post_id, 'wpgtp_tw_user_screen_name', $new_tweet['tw_user_screen_name'], true); }
						if ( $new_tweet['tw_user_profile_avatar_url'] ) { add_post_meta($post_id, 'wpgtp_tw_user_profile_avatar_url', $new_tweet['tw_user_profile_avatar_url'], true); }					
						
					}
				
						

					
				}
			}
			
			$msg = "$image_counter tweets pulled from Twitter.";

		}
		
		return $msg;

	} // end wpgtp_grab_twitter_posts()
	
		
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
            
        if ( isset($this->msg_text) ) {
        
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