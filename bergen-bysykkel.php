<?php
/**
 * Plugin Name: Bergen Bysykkel
 * Plugin URI: https://github.com/kalgraff/Bergen-Bysykkel
 * Description: Displays availability of bikes and docks at Nykirken and St. Jakobs Plass stations from Bergen Bysykkel API.
 * Version: 1.0.0
 * Author: Ove G. Kalgraff
 * Author URI: https://github.com/kalgraff
 * License: MIT
 * Text Domain: bergen-bysykkel
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Bergen Bysykkel Widget
 */
class Bergen_Bysykkel_Widget extends WP_Widget {

    /**
     * Register widget with WordPress.
     */
    public function __construct() {
        parent::__construct(
            'bergen_bysykkel_widget', // Base ID
            esc_html__('Bergen Bysykkel', 'bergen-bysykkel'), // Name
            array('description' => esc_html__('Displays bike availability for Bergen Bysykkel stations', 'bergen-bysykkel')) // Args
        );
    }

    /**
     * Front-end display of widget.
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        // Get the station data
        $this->display_stations_info();

        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @param array $instance Previously saved values from database.
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : esc_html__('Bergen Bysykkel', 'bergen-bysykkel');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_attr_e('Title:', 'bergen-bysykkel'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';

        return $instance;
    }

    /**
     * Display stations information
     */
    public function display_stations_info() {
        // Get station information and status
        $stations_info = $this->get_station_information();
        $stations_status = $this->get_station_status();
        
        if (!$stations_info || !$stations_status) {
            echo '<p>' . esc_html__('Could not retrieve station data. Please try again later.', 'bergen-bysykkel') . '</p>';
            return;
        }

        // Stations we want to display
        $target_stations = array(
            'Nykirken' => null,
            'St. Jakobs Plass' => null
        );

        // Find the station IDs for our target stations
        foreach ($stations_info['data']['stations'] as $station) {
            if (array_key_exists($station['name'], $target_stations)) {
                $target_stations[$station['name']] = $station['station_id'];
            }
        }

        // CSS styles for the widget
        $this->render_css();

        // Container for stations
        echo '<div class="bergen-bysykkel-container">';
        
        // Loop through each target station
        foreach ($target_stations as $station_name => $station_id) {
            if (!$station_id) {
                continue; // Skip if station ID was not found
            }
            
            // Find status for this station
            $status = null;
            foreach ($stations_status['data']['stations'] as $station_status) {
                if ($station_status['station_id'] === $station_id) {
                    $status = $station_status;
                    break;
                }
            }
            
            if (!$status) {
                continue; // Skip if status was not found
            }
            
            // Calculate status levels for bikes and docks
            $bikes_available = (int) $status['num_bikes_available'];
            $docks_available = (int) $status['num_docks_available'];
            $total_capacity = $bikes_available + $docks_available;
            
            $bikes_status = $this->get_status_level($bikes_available, $total_capacity);
            $docks_status = $this->get_status_level($docks_available, $total_capacity);
            
            // Render station box
            echo '<div class="bergen-bysykkel-station">';
            echo '<h3>' . esc_html($station_name) . '</h3>';
            
            // Bikes available
            echo '<div class="bergen-bysykkel-availability">';
            echo '<div class="bergen-bysykkel-indicator status-' . esc_attr($bikes_status) . '"></div>';
            echo '<div class="bergen-bysykkel-info">';
            echo '<span class="bergen-bysykkel-count">' . esc_html($bikes_available) . '</span>';
            echo '<span class="bergen-bysykkel-label">' . esc_html__('Bikes available', 'bergen-bysykkel') . '</span>';
            echo '</div>';
            echo '</div>';
            
            // Docks available
            echo '<div class="bergen-bysykkel-availability">';
            echo '<div class="bergen-bysykkel-indicator status-' . esc_attr($docks_status) . '"></div>';
            echo '<div class="bergen-bysykkel-info">';
            echo '<span class="bergen-bysykkel-count">' . esc_html($docks_available) . '</span>';
            echo '<span class="bergen-bysykkel-label">' . esc_html__('Docks available', 'bergen-bysykkel') . '</span>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>'; // End of station box
        }
        
        // Add update time
        if (isset($stations_status['last_updated'])) {
            $update_time = date('H:i:s', $stations_status['last_updated']);
            echo '<div class="bergen-bysykkel-update-time">';
            echo esc_html__('Last updated', 'bergen-bysykkel') . ': ' . esc_html($update_time);
            echo '</div>';
        }
        
        echo '</div>'; // End of container
    }

    /**
     * Get status level based on availability
     *
     * @param int $available Available items
     * @param int $total Total capacity
     * @return string Status level (low, medium, high)
     */
    private function get_status_level($available, $total) {
        $percentage = ($total > 0) ? ($available / $total) * 100 : 0;
        
        if ($percentage < 20) {
            return 'low';
        } elseif ($percentage < 50) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    /**
     * Render CSS for the widget
     */
    private function render_css() {
        ?>
        <style>
            .bergen-bysykkel-container {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                margin: 0;
                padding: 0;
            }
            .bergen-bysykkel-station {
                background-color: #f8f8f8;
                border-radius: 6px;
                margin-bottom: 15px;
                padding: 15px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .bergen-bysykkel-station h3 {
                margin-top: 0;
                margin-bottom: 10px;
                font-size: 18px;
                color: #333;
            }
            .bergen-bysykkel-availability {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
            }
            .bergen-bysykkel-indicator {
                width: 8px;
                height: 35px;
                border-radius: 4px;
                margin-right: 10px;
            }
            .bergen-bysykkel-indicator.status-high {
                background-color: #4CAF50; /* Green */
            }
            .bergen-bysykkel-indicator.status-medium {
                background-color: #FF9800; /* Orange */
            }
            .bergen-bysykkel-indicator.status-low {
                background-color: #F44336; /* Red */
            }
            .bergen-bysykkel-info {
                display: flex;
                flex-direction: column;
            }
            .bergen-bysykkel-count {
                font-size: 22px;
                font-weight: bold;
                line-height: 1;
            }
            .bergen-bysykkel-label {
                font-size: 12px;
                color: #666;
                margin-top: 3px;
            }
            .bergen-bysykkel-update-time {
                font-size: 11px;
                color: #999;
                text-align: right;
                margin-top: 5px;
            }
        </style>
        <?php
    }

    /**
     * Get station information from Bergen Bysykkel API
     * 
     * @return array|false Station information or false on failure
     */
    private function get_station_information() {
        $url = 'https://gbfs.urbansharing.com/bergenbysykkel.no/station_information.json';
        return $this->make_api_request($url);
    }

    /**
     * Get station status from Bergen Bysykkel API
     * 
     * @return array|false Station status or false on failure
     */
    private function get_station_status() {
        $url = 'https://gbfs.urbansharing.com/bergenbysykkel.no/station_status.json';
        return $this->make_api_request($url);
    }

    /**
     * Make an API request to Bergen Bysykkel API
     * 
     * @param string $url API endpoint URL
     * @return array|false JSON decoded response or false on failure
     */
    private function make_api_request($url) {
        $args = array(
            'timeout' => 10,
            'headers' => array(
                'Client-Identifier' => 'kalgraff-bergen-bysykkel-wp-plugin'
            )
        );

        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}

/**
 * Register the widget
 */
function register_bergen_bysykkel_widget() {
    register_widget('Bergen_Bysykkel_Widget');
}
add_action('widgets_init', 'register_bergen_bysykkel_widget');

/**
 * Shortcode for displaying Bergen Bysykkel stations
 */
function bergen_bysykkel_shortcode($atts) {
    ob_start();
    
    $widget = new Bergen_Bysykkel_Widget();
    $widget->display_stations_info();
    
    return ob_get_clean();
}
add_shortcode('bergen_bysykkel', 'bergen_bysykkel_shortcode'); 