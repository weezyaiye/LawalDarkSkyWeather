<?php
/*
Plugin Name: Lawal Darksky Weather
Plugin URI: http://github.com/weezyaiye/LawalDarkSkyWeather
Description: Reads the DarkSky REST API and Provides a shortcode and widget. Also does Google Reverse Goecoding.
Version: 1.0
Author: Lawal Aliu
Author URI: http://github.com/weezyaiye/
Text Domain: lawal-plugin
License: GPLv3
 */
register_activation_hook(__FILE__, 'ahuimanu_darksky_weather_install');
function ahuimanu_darksky_weather_install(){
    global $wp_version;
    if( version_compare($wp_version, '4.1', '<')){
        wp_die('This plugin requires WordPress Version 4.1 or higher.');
    }
}
register_deactivation_hook(__FILE__, 'ahuimanu_darksky_weather_deactivate');
function ahuimanu_darksky_weather_deactivate(){
    //do something when deactivating
}
/**
 * @param $apikey
 * @param $lat
 * @param $lon
 *
 * Constructs the Google Maps Geocoding URL
 * @return string
 */
function get_google_reverse_geocode_url($apikey, $lat, $lon){
    //https://maps.googleapis.com/maps/api/geocode/json?latlng=40.714224,-73.961452&key=YOUR_API_KEY
    $google_url = 'https://maps.googleapis.com/maps/api/geocode/json?';
    $google_url .= 'latlng=' . $lat . ',' . $lon;
    $google_url .= '&key=' . $apikey;
    return $google_url;
}
/**
 * @param $url
 * Gets JSON from the Google Reverse Geocode API
 * @return array|mixed|object|string
 */
function get_google_reverse_geocode_json($url){
    $request = wp_remote_get( $url );
    if( is_wp_error( $request ) ) {
        return 'could not obtain data'; // Bail early
    }else {
        //retreive message body from web service
        $body = wp_remote_retrieve_body( $request );
        //obtain JSON - as object or array
        $data = json_decode( $body, true );
        return $data;
    }
}
/**
 * @param $apikey
 * @param $lat
 * @param $lon
 *
 * Constructs the DarkSky API URL
 *
 * @return string
 */
function get_darksky_url($apikey, $lat, $lon){
    //'https://api.darksky.net/forecast/
    $darksky_url = 'https://api.darksky.net/forecast/';
    $darksky_url .= $apikey . '/';
    $darksky_url .= $lat . ',' . $lon;
    return $darksky_url;
}
/**
 * @param $url
 * Gets JSON from the DarkSky API
 * @return array|mixed|object|string
 */
function get_darksky_json($url){
    $request = wp_remote_get( $url );
    if( is_wp_error( $request ) ) {
        return 'could not obtain data'; // Bail early
    }else {
        //retreive message body from web service
        $body = wp_remote_retrieve_body( $request );
        //obtain JSON - as object or array
        $data = json_decode( $body, true );
        return $data;
    }
}
add_action( 'widgets_init', 'ahuimanu_darksky_weather_create_widgets' );
function ahuimanu_darksky_weather_create_widgets() {
    register_widget( 'Lawal_DarkySky_Weather' );
}
class Lawal_DarkySky_Weather extends WP_Widget {
    // Construction function
    function __construct () {
        parent::__construct( 'Lawal_DarkySky_Weather', 'DarkSky Weather',
            array( 'description' =>
                'Displays current weather from the DarkSky API' ) );
    }
    /**
     * @param array $instance
     * Code to show the administrative interface for the Widget
     */
    function form( $instance ) {
        // Retrieve previous values from instance
        // or set default values if not present
        $darksky_api_key = ( !empty( $instance['darksky_api_key'] ) ?
            esc_attr( $instance['darksky_api_key'] ) :
            'error' );
        $darksky_api_lat = ( !empty( $instance['darksky_api_lat'] ) ?
            esc_attr( $instance['darksky_api_lat'] ) : 'error');
        $darksky_api_lon = ( !empty( $instance['darksky_api_lon'] ) ?
            esc_attr( $instance['darksky_api_lon'] ) :
            'error' );
        $google_maps_api_key = ( !empty( $instance['google_maps_api_key'] ) ?
            esc_attr( $instance['google_maps_api_key'] ) :
            'error' );
        $widget_title = ( !empty( $instance['widget_title'] ) ?
            esc_attr( $instance['widget_title'] ) :
            'Dark Sky Weather' );
        ?>
        <!-- Display fields to specify title and item count -->
        <p>
            <label for="<?php echo
            $this->get_field_id( 'widget_title' ); ?>">
                <?php echo 'Widget Title:'; ?>
                <input type="text"
                       id="<?php echo
                       $this->get_field_id( 'widget_title' );?>"
                       name="<?php
                       echo $this->get_field_name( 'widget_title' ); ?>"
                       value="<?php echo $widget_title; ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo
            $this->get_field_id( 'darksky_api_key' ); ?>">
                <?php echo 'Darksky API Key:'; ?>
                <input type="text"
                       id="<?php echo
                       $this->get_field_id( 'darksky_api_key' );?>"
                       name="<?php
                       echo $this->get_field_name( 'darksky_api_key' ); ?>"
                       value="<?php echo $darksky_api_key; ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo
            $this->get_field_id( 'darksky_api_lat' ); ?>">
                <?php echo 'Darksky API Latitude:'; ?>
                <input type="text"
                       id="<?php echo
                       $this->get_field_id( 'darksky_api_lat' );?>"
                       name="<?php
                       echo $this->get_field_name( 'darksky_api_lat' ); ?>"
                       value="<?php echo $darksky_api_lat; ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo
            $this->get_field_id( 'darksky_api_lon' ); ?>">
                <?php echo 'Darksky API Longitude:'; ?>
                <input type="text"
                       id="<?php echo
                       $this->get_field_id( 'darksky_api_lon' );?>"
                       name="<?php
                       echo $this->get_field_name( 'darksky_api_lon' ); ?>"
                       value="<?php echo $darksky_api_lon; ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo
            $this->get_field_id( 'google_maps_api_key' ); ?>">
                <?php echo 'Google Maps API Key:'; ?>
                <input type="text"
                       id="<?php echo
                       $this->get_field_id( 'google_maps_api_key' );?>"
                       name="<?php
                       echo $this->get_field_name( 'google_maps_api_key' ); ?>"
                       value="<?php echo $google_maps_api_key; ?>" />
            </label>
        </p>
        <script>
            jQuery(document).ready(function(){
                if(navigator.geolocation){
                    navigator.geolocation.getCurrentPosition(showLocation);
                }else{
                    console.log('Geolocation is not supported by this browser.');
                    jQuery('#location').html('Geolocation is not supported by this browser.');
                }
            });
            function showLocation(position){
                var latitude = position.coords.latitude;
                console.log("latitude: " + latitude);
                document.getElementById('<?php echo $this->get_field_id( 'darksky_api_lat' ); ?>')
                    .setAttribute('value', latitude);
                var longitude = position.coords.longitude;
                console.log("longitude: " + longitude);
                document.getElementById('<?php echo $this->get_field_id( 'darksky_api_lon' ); ?>')
                    .setAttribute('value', longitude);
            }
        </script>

    <?php }
    /**
     * @param array $new_instance
     * @param array $instance
     *
     * Code to update the admin interface for the widget
     *
     * @return array
     */
    function update( $new_instance, $instance ) {
        $instance['widget_title'] =
            sanitize_text_field( $new_instance['widget_title'] );
        $instance['darksky_api_key'] =
            sanitize_text_field( $new_instance['darksky_api_key'] );
        $instance['darksky_api_lat'] =
            sanitize_text_field( $new_instance['darksky_api_lat'] );
        $instance['darksky_api_lon'] =
            sanitize_text_field( $new_instance['darksky_api_lon'] );
        $instance['google_maps_api_key'] =
            sanitize_text_field( $new_instance['google_maps_api_key'] );
        return $instance;
    }
    /**
     * @param array $args
     * @param array $instance
     *
     * Code for the display of the widget
     *
     */
    function widget( $args, $instance ) {
        // Extract members of args array as individual variables
        extract( $args );
        $widget_title = ( !empty( $instance['widget_title'] ) ?
            esc_attr( $instance['widget_title'] ) :
            'Dark Sky Weather' );
        $widget_darksky_api_key = ( !empty( $instance['darksky_api_key'] ) ?
            esc_attr( $instance['darksky_api_key'] ) :
            '0' );
        $widget_lat = ( !empty( $instance['darksky_api_lat'] ) ?
            esc_attr( $instance['darksky_api_lat'] ) :
            '0' );
        $widget_lon = ( !empty( $instance['darksky_api_lon'] ) ?
            esc_attr( $instance['darksky_api_lon'] ) :
            '0' );
        $widget_google_maps_api_key = ( !empty( $instance['google_maps_api_key'] ) ?
            esc_attr( $instance['google_maps_api_key'] ) :
            '0' );
        //get URLs
        $url_darksky = get_darksky_url($widget_darksky_api_key, $widget_lat, $widget_lon);
        $url_google = get_google_reverse_geocode_url($widget_google_maps_api_key, $widget_lat, $widget_lon);
        //obtain JSON - as object or array
        $data_darksky = get_darksky_json($url_darksky);
        $data_google = get_google_reverse_geocode_json($url_google);
        //$output .= print_r($data_darksky);
        // Display widget title
        echo $before_widget . $before_title;
        echo apply_filters( 'widget_title', $widget_title );
        echo $after_title;
        //echo "Weather information for: " . $data_google['results'][0]['address_components']['long_name'];
        echo "Weather information for: <br>";
        echo $data_google['results'][0]['address_components'][2]['long_name'] . ", ";
        echo $data_google['results'][0]['address_components'][3]['long_name'] . ", ";
        echo $data_google['results'][0]['address_components'][4]['long_name'] . ", ";
        echo $data_google['results'][0]['address_components'][5]['long_name'];
        echo '<br>';
        echo date("l jS \of F Y h:i:s A", intval($data_darksky['currently']['time'])) . " UTC";
        echo '<br>';
        //https://wordpress.stackexchange.com/questions/60230/how-to-call-images-from-your-plugins-image-folder
        echo '<img src="' . plugin_dir_url( __FILE__ ) .
            'DarkSky-icons/PNG/' . $data_darksky['currently']['icon'] . '.png">';
        echo '<br>';
        echo "Latitude: " . $data_darksky['latitude'];
        echo '<br>';
        echo "Longitude: " . $data_darksky['longitude'];
        echo '<br>';
        echo "Temperature: " . round($data_darksky['currently']['temperature']) . " °F";
        echo '<br>';
        echo "Dew Point: " . round($data_darksky['currently']['dewPoint']) . " °F";
        echo '<br>';
        echo "Humdity: " . (floatval($data_darksky['currently']['humidity']) * 100) . "%";
        echo '<br>';
        echo "Wind Direction: " . $data_darksky['currently']['windBearing'] . "°";
        echo '<br>';
        echo "Wind Speed: " . round($data_darksky['currently']['windSpeed']) . " mph";
        echo '<br>';
        echo "Pressure: " . round($data_darksky['currently']['pressure']) . " mb";
        echo '<br>';
        echo "Visibility: " . round($data_darksky['currently']['visibility']) . " miles";
        echo '<br>';
        echo $after_widget;
    }
}
?>
