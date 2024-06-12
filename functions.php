<?php
function storefront_child_enqueue_styles() {
    wp_enqueue_style( 'storefront-child-style', get_stylesheet_uri() );
}
add_action( 'wp_enqueue_scripts', 'storefront_child_enqueue_styles' );
function create_cities_post_type() {
    register_post_type( 'cities',
        array(
            'labels' => array(
                'name_admin_bar' => 'Cities',
                'name' => __( 'Cities' ),
                'ingular_name' => __( 'City' ),
                'enu_name' => __( 'Cities' ),
                'all_items' => __( 'All Cities' ),
                'add_new' => __( 'Add New City' ),
                'add_new_item' => __( 'Add New City' ),
                'edit_item' => __( 'Edit City' ),
                'new_item' => __( 'New City' ),
                'view_item' => __( 'View City' ),
                'earch_items' => __( 'Search Cities' ),
                'not_found' => __( 'No cities found' ),
                'not_found_in_trash' => __( 'No cities found in trash' ),
            ),
            'public' => true,
            'has_archive' => true,
            'upports' => array( 'title', 'editor' ),
            'enu_icon' => 'dashicons-location',
            'enu_position' => 20,
        )
    );
}
add_action( 'init', 'create_cities_post_type' );

function create_cities_meta_box() {
    add_meta_box(
        'cities_meta_box',
        __( 'City Coordinates' ),
        'cities_meta_box_callback',
        'cities',
        'advanced',
        'high'
    );
}
add_action( 'add_meta_boxes', 'create_cities_meta_box' );

function cities_meta_box_callback( $post ) {
    wp_nonce_field( 'cities_meta_box_nonce', 'cities_meta_box_nonce' );
   ?>
    <p>
        <label for="latitude"><?php _e( 'Latitude' );?></label>
        <input type="text" id="latitude" name="latitude" value="<?php echo get_post_meta( $post->ID, 'latitude', true );?>">
    </p>
    <p>
        <label for="longitude"><?php _e( 'Longitude' );?></label>
        <input type="text" id="longitude" name="longitude" value="<?php echo get_post_meta( $post->ID, 'longitude', true );?>">
    </p>
    <?php
}
function create_countries_taxonomy() {
    register_taxonomy(
        'countries',
        'cities',
        array(
            'labels' => array(
                'name' => __( 'Countries' ),
                'ingular_name' => __( 'Country' ),
                'enu_name' => __( 'Countries' ),
                'all_items' => __( 'All Countries' ),
                'add_new' => __( 'Add New Country' ),
                'add_new_item' => __( 'Add New Country' ),
                'edit_item' => __( 'Edit Country' ),
                'new_item' => __( 'New Country' ),
                'view_item' => __( 'View Country' ),
                'earch_items' => __( 'Search Countries' ),
                'not_found' => __( 'No countries found' ),
                'not_found_in_trash' => __( 'No countries found in trash' ),
            ),
            'public' => true,
            'hierarchical' => true,
            'how_ui' => true,
            'how_admin_column' => true,
            'query_var' => true,
            'ewrite' => array( 'lug' => 'countries' ),
        )
    );
}
add_action( 'init', 'create_countries_taxonomy' );
function create_weather_widget() {
    register_widget( 'Weather_Widget' );
}
add_action( 'widgets_init', 'create_weather_widget' );

class Weather_Widget extends WP_Widget {
    function __construct() {
        parent::__construct(
            'weather_widget',
            __( 'Weather' ),
            array( 'description' => __( 'Display weather information' ) )
        );
    }

    function form( $instance ) {
        $cities = get_posts( array(
            'post_type' => 'cities',
            'posts_per_page' => -1,
        ) );
        $countries = get_terms( array(
            'taxonomy' => 'countries',
            'hide_empty' => false,
        ) );
       ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'city' );?>"><?php _e( 'City');?></label>
            <select id="<?php echo $this->get_field_id( 'city' );?>" name="<?php echo $this->get_field_name( 'city' );?>">
                <?php foreach ( $cities as $city ) :?>
                    <option value="<?php echo $city->ID;?>"><?php echo $city->post_title;?></option>
                <?php endforeach;?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'country' );?>"><?php _e( 'Country' );?></label>
            <select id="<?php echo $this->get_field_id( 'country' );?>" name="<?php echo $this->get_field_name( 'country' );?>">
                <?php foreach ( $countries as $country ) :?>
                    <option value="<?php echo $country->term_id;?>"><?php echo $country->name;?></option>
                <?php endforeach;?>
            </select>
        </p>
        <?php
    }

    function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['city'] = (int) $new_instance['city'];
        $instance['country'] = (int) $new_instance['country'];
        return $instance;
    }

    function widget( $args, $instance ) {
        $city_id = $instance['city'];
        $country_id = $instance['country'];
        $city = get_post( $city_id );
        $country = get_term( $country_id );
        $api_key = '5e4eb0c6a57e9e3c0c99338147d5725d';
        $url = "https://api.openweathermap.org/data/2.5/weather?q={$city->post_title},{$country->name}&APPID={$api_key}";
        $response = wp_remote_get( $url );
        $data = json_decode( $response['body'], true );
        $temperature = $data['main']['temp'];
        $temperature_celsius = round( $temperature - 273.15 );
      ?>
        <div class="weather-widget">
            <h2><?php echo $city->post_title;?>, <?php echo $country->name;?></h2>
            <p>Temperature: <?php echo $temperature_celsius;?>°C</p>
        </div>
        <?php
    }
}
function display_weather_table() {
   ?>
    <form>
        <label for="filter_city">Filter by city:</label>
        <select id="filter_city" name="filter_city">
            <option value="">All cities</option>
            <?php $cities = get_posts( array( 'post_type' => 'cities', 'posts_per_page' => -1 ) );?>
            <?php foreach ( $cities as $city ) :?>
                <option value="<?php echo $city->ID;?>"><?php echo $city->post_title;?></option>
            <?php endforeach;?>
        </select>
        <button type="submit">Apply filter</button>
    </form>
    <?php
    $filter_city = $_GET['filter_city'];
    if ( $filter_city ) {
        $cities = get_posts( array( 'post_type' => 'cities', 'posts_per_page' => -1, 'p' => $filter_city ) );
    } else {
        $cities = get_posts( array( 'post_type' => 'cities', 'posts_per_page' => -1 ) );
    }
   ?>
    <table>
        <thead>
            <tr>
                <th>City</th>
                <th>Country</th>
                <th>Temperature (°C)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $cities as $city ) :?>
                <?php $terms = wp_get_post_terms( $city->ID, 'countries' );?>
                <?php $country = $terms[0]->name;?>
                <tr>
                    <td><?php echo $city->post_title;?></td>
                    <td><?php echo $country;?></td>
                    <td>
                        <?php
                        $api_key = '5e4eb0c6a57e9e3c0c99338147d5725d';
                        $url = "https://api.openweathermap.org/data/2.5/weather?q={$city->post_title},{$country}&APPID={$api_key}";
                        $response = wp_remote_get( $url );
                        $data = json_decode( $response['body'], true );
                        $temperature = $data['main']['temp'];
                        $temperature_celsius = round( $temperature - 273.15 );
                        echo $temperature_celsius;?>°C
                    </td>
                </tr>
            <?php endforeach;?>
        </tbody>
    </table>
    <?php
}

add_shortcode( 'weather_table', 'display_weather_table' );
