<?php

class GA_Popular_Posts_Widget extends WP_Widget {
    public $text_domain = 'ga_popular_posts';

    function __construct(){
        parent::__construct(
            'ga_popular_posts_w', // Base ID
            __('GA Popular Posts Widget', $this->text_domain), // Name
            array('classname' => 'ga_popular_posts', 'description' => __( 'Get popular posts from Google Analytics.', $this->text_domain ), ) // Args
        );
    }
    function widget($args, $instance){
        $defaults = array(
            'show_cat' => 1,
            'show_post_excerpt' => 1,
            'show_post_author' => 1,
            'show_post_author_avatar' => 0,
            'show_post_date' => 1,
        );
        $instance = wp_parse_args( (array) $instance, $defaults );
        $show_cat = (bool)$instance['show_cat'];
        $show_post_excerpt = (bool)$instance['show_post_excerpt'];
        $show_post_author = (bool)$instance['show_post_author'];
        $show_post_author_avatar = (bool)$instance['show_post_author_avatar'];
        $show_post_date = (bool)$instance['show_post_date'];
        echo $args['before_widget'];
        if(isset($_REQUEST['dbga']) && $_REQUEST['dbga']==true){
            $data = get_option( 'gapp_settings');
            if(isset( $data['ga_view_id']) && $data['ga_view_id']) $viewID = $data['ga_view_id'];
            else $viewID = '';
            $keyFile = get_option('gapp_key_file');
            if( $viewID && $keyFile ){
                require_once GAPP_FUNC_PATH . '/lib/google-api-php-client-2.2.0/vendor/autoload.php';
                $analytics = $this->initializeAnalytics($keyFile);
                $results = $this->getResults($analytics, $viewID, 100, '60daysAgo');
                echo '<pre>';
                print_r($results);
                echo '</pre>';
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
                echo '<pre>';
                print_r($postIDs);
                echo '</pre>';
                /*if( ($handle = fopen(GAPP_FUNC_PATH.'/gapp.csv', 'w')) !== FALSE ){
                    foreach( $postIDs as $ID ) fputcsv( $handle, $ID);
                }
                fclose($handle);*/
            }
        }
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }
        $maxResults = (int)$instance['maxResults'];
        if( empty( $maxResults ) ) $maxResults  = 10;
        if ( !empty( $instance['timeRange'] ) ) {
            $timeRange = $instance['timeRange'];
        } else $timeRange = '30daysAgo';
        $types = explode(",", $instance['post_type']);
        if( count($types) > 0 ){foreach( $types as &$type ) $type = trim($type);}
        $instance['thumbnail']['active'] = $instance['thumbnail']['active'] ? true: false;
        /*$instance['stats_tag']['date']['active'] = ( $instance['stats_tag']['date']['active'] ) ? true : false;
        $instance['stats_tag']['date']['format'] = empty($instance['stats_tag']['date']['format'])
            ? 'F j, Y'
            : $instance['stats_tag']['date']['format'];*/

        $postIDs = array();
        if( ($handle = fopen(GAPP_FUNC_PATH.'/gapp.csv', 'r')) !== false ){
            while( ($data = fgetcsv($handle, 10000, ',')) !== false )
                $postIDs[] = $data;
        } else {
            echo 'GA Popular Posts - File open failed!';
            return;
        }
        if(isset($_REQUEST['dbga']) && $_REQUEST['dbga'] == true){
            echo '<pre>';
            print_r($postIDs);
            echo '</pre>';
        }
        fclose($handle);
        if(count($postIDs) > 0){
            if($instance['thumbnail']['active']) $class = '';
            else $class = 'noThumbnail';
            echo '<div class="gapp_list '.$class.'">';
            $i = 0;
            $t = 0;
            foreach($postIDs as $ID){
                if( $i >= $maxResults ) break;
                $postID = $ID[0];
                // compare defined post-types
                if( count($types) > 0 ){
                    $postType = get_post_type($postID);
                    if(!in_array( $postType, $types )) continue;
                }
                if($timeRange != 'all'){
                    switch ($timeRange){
                        case '1dayAgo':
                            $time = '-1 day';
                            break;
                        case '7daysAgo':
                            $time = '-1 week';
                            break;
                        case '30daysAgo':
                            $time = '-1 month';
                            break;
                        case '60daysAgo':
                            $time = '-2 month';
                            break;
                        case '180daysAgo':
                            $time = '-6 month';
                            break;
                        case '365daysAgo':
                            $time = '-1 year';
                            break;
                        default: $time = '-1 month';
                    }
                    $postDate = get_the_date('Y/m/d H:i', $postID);
                    if( strtotime($postDate) < strtotime($time) ) {
                        $t++;
                        continue;
                    }
                }
                $i++;
                //$post = get_post($postID);
                $permalink = get_permalink($postID);
                $title = get_the_title($postID);
                $_excerpt = wp_strip_all_tags(get_the_excerpt($postID));
                echo '<div class="gappItem">';
                if($instance['thumbnail']['active']){
                    if (has_post_thumbnail( $postID ) ){
                        $image = wp_get_attachment_image_src( get_post_thumbnail_id( $postID ), '110x110' );
                        $avatar = '<img src="'.$image[0].'" width="55" class="avatar avatar-32 photo avatar-default">';
                        $avatar .= '<!-- post thumb '.$avatar.' -->';
                    } else $avatar = '<img src="'.__GAPP_DEFAULT_THUMB__.'" width="55" class="avatar avatar-32 photo avatar-default">';
                    echo '<a class="gappThumb" href="'.$permalink.'" title="'.esc_attr($title).'" target="_self">'.$avatar.'</a>';
                }
                if ($show_cat) echo '<h6 class="postCat">'.cf_cat_name($postID).'</h6>';
                echo '<h5 class="postTitle"><a class="gappTitle" href="'.$permalink.'" title="'.$title.'" target="_self">'.$title.'</a></h5>';
                if ($show_post_excerpt) echo '<div class="postExcerpt">'.$_excerpt.'</div>';
                /*if($instance['stats_tag'] && $instance['stats_tag']['date']['active']){
                    $stats = array();
                    if ($instance['stats_tag']['date']['active']) {
                        $date = get_the_date($instance['stats_tag']['date']['format'], $postID);
                        $stats[] = '<span class="wpp-date">' . sprintf(__('posted on %s', $this->text_domain), $date) . '</span>';
                    }
                    $_stats = join(' | ', $stats);
                    echo '<span class="post-stats">'.$_stats.'</span>';
                }*/
                echo cf_post_authors_date(false, null, $show_post_author_avatar, $show_post_author, $show_post_date);
                echo '</div>';
            }
            if($i == 0){
                if( $t > 0 ) echo 'There\'s no posts within that time range.';
                else echo 'There\'s no posts with post type(s): '.$instance['post_type'].'.';
            }
            echo '</div>';
        } else echo 'No results found!';

        echo $args['after_widget'];
    }
    public function form( $instance ) {
        $defaults = array(
            'show_cat' => 1,
            'show_post_excerpt' => 1,
            'show_post_author' => 1,
            'show_post_author_avatar' => 0,
            'show_post_date' => 1,
        );
        $instance = wp_parse_args( (array) $instance, $defaults );
        $title = !empty( $instance['title'] ) ? $instance['title'] : esc_html__('Most Viewed', $this->text_domain);
        if ( isset( $instance[ 'maxResults' ] ) ) $maxResults = $instance[ 'maxResults' ];
        else $maxResults = 10;
        if ( !empty( $instance['timeRange'] ) ) {
            $timeRange = $instance['timeRange'];
        } else $timeRange = '30daysAgo';
        $instance['post_type'] = ( '' == $instance['post_type'] )
            ? 'post,page'
            : $instance['post_type'];
        $instance['thumbnail']['active'] = $instance['thumbnail']['active'] ? true: false;
        /*$instance['stats_tag']['date']['active'] = ( $instance['stats_tag']['date']['active'] ) ? true : false;
        $instance['stats_tag']['date']['format'] = empty($instance['stats_tag']['date']['format'])
            ? 'F j, Y'
            : $instance['stats_tag']['date']['format'];*/
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'text_domain' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('maxResults'); ?>"><?php _e( 'Show:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('maxResults'); ?>" name="<?php echo $this->get_field_name('maxResults'); ?>" type="text" value="<?php echo esc_attr($maxResults);?>">
        </p>
        <br />
        <legend><strong><?php _e('Filters', ''); ?></strong></legend>
        <p>
            <label for="<?php echo $this->get_field_id( 'timeRange' ); ?>"><?php _e( 'Time Range:' ); ?></label>
            <select name="<?php echo $this->get_field_name( 'timeRange' ); ?>" id="<?php echo $this->get_field_id( 'timeRange' ); ?>">
                <option value="1dayAgo" <?php selected( $timeRange, '1dayAgo', true )?>>Last 24 hours</option>
                <option value="7daysAgo" <?php selected( $timeRange, '7daysAgo', true )?>>Last 7 days</option>
                <option value="30daysAgo" <?php selected( $timeRange, '30daysAgo', true )?>>Last 30 days</option>
                <option value="60daysAgo" <?php selected( $timeRange, '60daysAgo', true )?>>Last 2 months</option>
                <option value="180daysAgo" <?php selected( $timeRange, '180daysAgo', true )?>>Last 6 months</option>
                <option value="365daysAgo" <?php selected( $timeRange, '365daysAgo', true )?>>Last 1 year</option>
                <option value="all" <?php selected( $timeRange, 'all', true )?>>All-time</option>
            </select>
        </p>
        <label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php _e('Post type(s)', ''); ?>:</label> <small>[<a href="https://github.com/cabrerahector/wordpress-popular-posts/wiki/5.-FAQ#what-is-post-type-for" title="<?php _e('What is this?', ''); ?>" target="_blank">?</a>]</small>
        <input type="text" id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>" value="<?php echo $instance['post_type']; ?>" class="widefat" /><br /><br />
        <hr />
        <p>
            <input type="checkbox" class="checkbox" <?php echo ($instance['thumbnail']['active']) ? 'checked="checked"' : ''; ?> id="<?php echo $this->get_field_id( 'thumbnail-active' ); ?>" name="<?php echo $this->get_field_name( 'thumbnail-active' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'thumbnail-active' ); ?>"><?php _e('Display post thumbnail'); ?></label>
        </p>
        <!--<p>
            <input type="checkbox" class="checkbox" <?php /*echo ($instance['stats_tag']['date']['active']) ? 'checked="checked"' : '';*/?> id="<?php /*echo $this->get_field_id('date'); */?>" name="<?php /*echo $this->get_field_name('date');*/?>" />
            <label for="<?php /*echo $this->get_field_id( 'date' ); */?>"><?php /*_e('Display date',$this->text_domain); */?></label><br />
        </p>
        <div style="display:<?php /*if ($instance['stats_tag']['date']['active']) : */?>block<?php /*else: */?>none<?php /*endif; */?>; width:90%; margin:10px 0; padding:3% 5%; background:#f5f5f5;">
            <legend><strong><?php /*_e('Date Format',$this->text_domain); */?></strong></legend><br />

            <label title='<?php /*echo get_option('date_format'); */?>'><input type='radio' name='<?php /*echo $this->get_field_name( 'date_format' ); */?>' value='<?php /*echo get_option('date_format'); */?>' <?php /*echo ($instance['stats_tag']['date']['format'] == get_option('date_format')) ? 'checked="checked"' : ''; */?> /><?php /*echo date_i18n(get_option('date_format'), time()); */?></label> <small>(<a href="<?php /*echo admin_url('options-general.php'); */?>" title="<?php /*_e('WordPress Date Format'); */?>" target="_blank"><?php /*_e('WordPress Date Format'); */?></a>)</small><br />
            <label title='F j, Y'><input type='radio' name='<?php /*echo $this->get_field_name( 'date_format' ); */?>' value='F j, Y' <?php /*echo ($instance['stats_tag']['date']['format'] == 'F j, Y') ? 'checked="checked"' : ''; */?> /><?php /*echo date_i18n('F j, Y', time()); */?></label><br />
            <label title='Y/m/d'><input type='radio' name='<?php /*echo $this->get_field_name( 'date_format' ); */?>' value='Y/m/d' <?php /*echo ($instance['stats_tag']['date']['format'] == 'Y/m/d') ? 'checked="checked"' : ''; */?> /><?php /*echo date_i18n('Y/m/d', time()); */?></label><br />
            <label title='m/d/Y'><input type='radio' name='<?php /*echo $this->get_field_name( 'date_format' ); */?>' value='m/d/Y' <?php /*echo ($instance['stats_tag']['date']['format'] == 'm/d/Y') ? 'checked="checked"' : ''; */?> /><?php /*echo date_i18n('m/d/Y', time()); */?></label><br />
            <label title='d/m/Y'><input type='radio' name='<?php /*echo $this->get_field_name( 'date_format' ); */?>' value='d/m/Y' <?php /*echo ($instance['stats_tag']['date']['format'] == 'd/m/Y') ? 'checked="checked"' : ''; */?> /><?php /*echo date_i18n('d/m/Y', time()); */?></label>
        </div>-->

        <p>
            <input type="checkbox" class="widefat" <?php checked($instance['show_cat']); ?> id="<?php echo $this->get_field_id( 'show_cat' ); ?>" name="<?php echo $this->get_field_name( 'show_cat' ); ?>">
            <label for="<?php echo $this->get_field_id( 'show_cat' ); ?>"><?php _e( 'Show category?' ); ?></label>
        </p>
        <p>
            <input type="checkbox" class="widefat" <?php checked($instance['show_post_excerpt']); ?> id="<?php echo $this->get_field_id( 'show_post_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'show_post_excerpt' ); ?>">
            <label for="<?php echo $this->get_field_id( 'show_post_excerpt' ); ?>"><?php _e( 'Show post excerpt?' ); ?></label>
        </p>
        <p>
            <input type="checkbox" class="widefat" <?php checked($instance['show_post_author']); ?> id="<?php echo $this->get_field_id( 'show_post_author' ); ?>" name="<?php echo $this->get_field_name( 'show_post_author' ); ?>">
            <label for="<?php echo $this->get_field_id( 'show_post_author' ); ?>"><?php _e( 'Show author of posts?' ); ?></label>
        </p>
        <p>
            <input type="checkbox" class="widefat" <?php checked($instance['show_post_author_avatar']); ?> id="<?php echo $this->get_field_id( 'show_post_author_avatar' ); ?>" name="<?php echo $this->get_field_name( 'show_post_author_avatar' ); ?>">
            <label for="<?php echo $this->get_field_id( 'show_post_author_avatar' ); ?>"><?php _e( 'Show author\'s avatar?' ); ?></label>
        </p>
        <p>
            <input type="checkbox" class="widefat" <?php checked($instance['show_post_date']); ?> id="<?php echo $this->get_field_id( 'show_post_date' ); ?>" name="<?php echo $this->get_field_name( 'show_post_date' ); ?>">
            <label for="<?php echo $this->get_field_id( 'show_post_date' ); ?>"><?php _e( 'Show date of posts?' ); ?></label>
        </p>
        <?php
    }
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['maxResults'] = ( ! empty( $new_instance['maxResults'] ) ) ? (int)$new_instance['maxResults'] : 0;
        $instance['timeRange'] = ( ! empty( $new_instance['timeRange'] ) ) ? strip_tags( $new_instance['timeRange'] ) : '';
        $instance['post_type'] = ( '' == $new_instance['post_type'] )
            ? 'post,page'
            : $new_instance['post_type'];

        $instance['thumbnail']['active'] = false;
        $instance['thumbnail']['active'] = isset( $new_instance['thumbnail-active'] );

        /*$instance['stats_tag']['date']['active'] = false;
        $instance['stats_tag']['date']['active'] = isset( $new_instance['date'] );
        $instance['stats_tag']['date']['format'] = empty($new_instance['date_format'])
            ? 'F j, Y'
            : $new_instance['date_format'];*/

        $instance['show_cat'] = isset( $new_instance['show_cat'] ) ? (bool) $new_instance['show_cat'] : false;
        $instance['show_post_excerpt'] = isset( $new_instance['show_post_excerpt'] ) ? (bool) $new_instance['show_post_excerpt'] : false;
        $instance['show_post_author'] = isset( $new_instance['show_post_author'] ) ? (bool) $new_instance['show_post_author'] : false;
        $instance['show_post_author_avatar'] = isset( $new_instance['show_post_author_avatar'] ) ? (bool) $new_instance['show_post_author_avatar'] : false;
        $instance['show_post_date'] = isset( $new_instance['show_post_date'] ) ? (bool) $new_instance['show_post_date'] : false;

        return $instance;
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
    private function __is_numeric($number){
        return !empty($number) && is_numeric($number) && (intval($number) == floatval($number));
    }
}