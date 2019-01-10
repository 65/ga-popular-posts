<?php
/**
 * Plugin Name: GA Popular Posts
 * Description: Get popular posts from Google Analytics.
 * Author:      khanhvo678
 * Version:     1.1
 * License:     GPL2
 */

if( !class_exists('GA__Popular_Posts') ){
class GA__Popular_Posts {
    public function __construct(){
        $this->constants();
        // register new widget
        add_action( 'widgets_init', array( $this, '_load_widgets' ) );

        add_action( 'wp_enqueue_scripts', array( $this, '_enqueue_scripts' ) );

        register_activation_hook( __FILE__, array( $this, '_install' ) );
        add_action('ga_popular_posts_event', array($this, 'ga_popular_posts_event_func'));

        //Add Admin menu
        add_action( 'admin_menu',  array( $this, 'admin_menu') );
        // Add setting link
        $plugin = plugin_basename( __FILE__ );
        add_filter( "plugin_action_links_$plugin", array($this,'gapp_plugin_add_settings_link'));
        // add additional file types
        add_filter( 'upload_mimes', array($this,'gapp_mimes_types'));
    }
    protected function constants() {
        define( 'GAPP_FUNC_PATH', plugin_dir_path( __FILE__ ) );
        define( '__GAPP_URL__', plugin_dir_url( __FILE__ ) );
        define('__GAPP_DEFAULT_THUMB__',__GAPP_URL__.'/no_thumb.jpg');
    }

    function _load_widgets(){
        require_once GAPP_FUNC_PATH.'/ga_popular_posts_widget.php';
        register_widget( 'GA_Popular_Posts_Widget' );
    }
    function _enqueue_scripts(){
        wp_enqueue_style( 'gapp_css', plugins_url( 'assets/css/gpp.css' , __FILE__ ) );
    }
    function _install(){
        wp_clear_scheduled_hook( 'ga_popular_posts_event' );
        wp_schedule_event(time(), 'hourly', 'ga_popular_posts_event');
    }
    function ga_popular_posts_event_func(){
        $data = get_option( 'gapp_settings');
        if(isset( $data['ga_view_id']) && $data['ga_view_id']) $viewID = $data['ga_view_id'];
        else $viewID = '';
        $keyFile = get_option('gapp_key_file');
        if( $viewID && $keyFile ){
            require_once GAPP_FUNC_PATH . '/lib/google-api-php-client-2.2.0/vendor/autoload.php';
            $analytics = $this->initializeAnalytics($keyFile);
            $results = $this->getResults($analytics, $viewID, 100, '60daysAgo');
            $_postIDs = array();
            if (count($results->getRows()) > 0) {
                $rows = $results->getRows();
                foreach($rows as $row) {
                    $postID = url_to_postid($row[1]);
                    if (!$postID) continue;
                    $_postIDs[] = $postID;
                }
            }
            $_postIDs = array_unique($_postIDs);
            $postIDs = array();
            foreach ($_postIDs as $_postID) $postIDs[] = array($_postID);
            /*echo '<pre>';
            print_r($postIDs);
            echo '</pre>';*/
            if( ($handle = fopen(GAPP_FUNC_PATH.'/gapp.csv', 'w')) !== FALSE ){
                foreach( $postIDs as $ID ) fputcsv( $handle, $ID);
            }
            fclose($handle);
        }
    }
    function initializeAnalytics($keyFile){
        //$KEY_FILE_LOCATION = GAPP_FUNC_PATH . '/lib/GA_API-eae4129237e4.json';
        $KEY_FILE_LOCATION = $keyFile;

        // Create and configure a new client object.
        $client = new Google_Client();
        $client->setApplicationName("GA_Popular_Post");
        $client->setAuthConfig($KEY_FILE_LOCATION);
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        $analytics = new Google_Service_Analytics($client);

        return $analytics;
    }
    function getResults($analytics, $profileId, $maxResults=5, $timeRange='30daysAgo') {
        // Calls the Core Reporting API and queries for the number of sessions
        // for the last seven days.
        $optParams = array(
            'max-results' => $maxResults,
            'dimensions' => 'ga:pageTitle,ga:pagePath',
            'sort' => '-ga:pageviews',
            'filters' => 'ga:pagePath!=/'
        );

        return $analytics->data_ga->get(
            'ga:' . $profileId,
            $timeRange,
            'today',
            'ga:pageviews',
            $optParams);
    }
    // add Admin menu
    public function admin_menu(){
        add_options_page( 'GA Popular Posts Settings', 'GA Popular Posts', 'manage_options', 'ga-popular-posts', array( $this, 'ga_popular_posts_settings' ) );
    }
    // load settings page
    public function ga_popular_posts_settings(){
        include "templates/ga-popular-posts-settings.php";
    }
    function gapp_plugin_add_settings_link($links){
        $settings_link = '<a href="options-general.php?page=ga-popular-posts">' . __( 'Settings' ) . '</a>';
        array_push( $links, $settings_link );
        return $links;
    }
    // add additional file types to WordPress
    function gapp_mimes_types($mime_types){
        $mime_types['json'] = 'application/json'; // Adding .json extension

        return $mime_types;
    }
}

new GA__Popular_Posts();
}