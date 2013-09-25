=== WPGrabTwitterPics ===
Contributors: dimensionmedia
Donate link: http://davidbisset.com/
Tags: twitter
Requires at least: 3.6
Tested up to: 3.6.1

This plugin will search through recent tweets (containing a certain hashtag or keyword), and import those photos along with some metadata into a custom post type.

== Installation ==

1. Upload the WPGrabTwitterPics folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Update a key term in the options, click the "grab" button in the grab area and watch the magic (look in your media area). My test hashtag is #confrz.

== What Will I Need? ==

You need to apply to Twitter to obtain a consumer key and consumer secret. You also need a setOAuthToken and setOAuthTokenSecret. All four of these you can update in the settings screen.

== Changelog ==

= 0.4 =
* standalone_cron.php added for real-time cron jobs
* Significant cleanup of plugin code

= 0.3 =
* Significant changes to how plugin now works: instead of importing photos directly to the media gallery, we are using custom post types that store the metadata.
* Better process for detecting new Instagram posts via the API

= 0.1 =
* Basic bare-bones plugin.