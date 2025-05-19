<?php
/**
 * Plugin Name: Bergen Bysykkel
 * Plugin URI: https://github.com/kalgraff/Bergen-Bysykkel
 * Description: Displays availability of bikes and docks at Nykirken and St. Jakobs Plass stations from Bergen Bysykkel API.
 * Version: 1.2.0
 * Author: Ove G. Kalgraff
 * Author URI: https://github.com/kalgraff
 * License: MIT
 * Text Domain: bergen-bysykkel
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('BERGEN_BYSYKKEL_VERSION', '1.2.0');
define('BERGEN_BYSYKKEL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BERGEN_BYSYKKEL_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Aktivering av plugin-en
 */
function bergen_bysykkel_activate() {
    // Ingen spesielle aktiveringsoppgaver foreløpig
}
register_activation_hook(__FILE__, 'bergen_bysykkel_activate');

/**
 * Last inn tekstdomenet for oversettelser
 */
function bergen_bysykkel_load_textdomain() {
    load_plugin_textdomain('bergen-bysykkel', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'bergen_bysykkel_load_textdomain');

/**
 * Legg til debug-funksjon for feilsøking
 */
function bergen_bysykkel_debug_log($message) {
    if (WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}

/**
 * Check if widget class exists
 */
if (class_exists('WP_Widget')) {

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
                array('description' => esc_html__('Viser tilgjengelighet for Bergen Bysykkel stasjoner', 'bergen-bysykkel')) // Args
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
                    <?php esc_attr_e('Tittel:', 'bergen-bysykkel'); ?>
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
                echo '<p>' . esc_html__('Kunne ikke hente stasjonsdata. Vennligst prøv igjen senere.', 'bergen-bysykkel') . '</p>';
                
                // Vis debugging informasjon i admin
                if (current_user_can('manage_options') && is_admin()) {
                    echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-top: 10px;">';
                    echo '<p><strong>Debug-informasjon (kun synlig for administratorer):</strong></p>';
                    echo '<p>Plugin versjon: ' . esc_html(BERGEN_BYSYKKEL_VERSION) . '</p>';
                    echo '<p>API URL: https://gbfs.urbansharing.com/bergenbysykkel.no/station_information.json</p>';
                    echo '</div>';
                }
                
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

            // Check if we found the stations
            $found_stations = false;
            foreach ($target_stations as $station_id) {
                if ($station_id !== null) {
                    $found_stations = true;
                    break;
                }
            }
            
            if (!$found_stations) {
                bergen_bysykkel_debug_log('Bergen Bysykkel API: Could not find target stations');
                echo '<p>' . esc_html__('Kunne ikke finne målstasjonene. Vennligst prøv igjen senere.', 'bergen-bysykkel') . '</p>';
                
                // Vis debugging informasjon i admin
                if (current_user_can('manage_options')) {
                    echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-top: 10px;">';
                    echo '<p><strong>Debug-informasjon (kun synlig for administratorer):</strong></p>';
                    echo '<p>Fant ikke stasjonene: Nykirken, St. Jakobs Plass</p>';
                    echo '<p>Sjekk stasjonsnavnene i API-et: <a href="https://gbfs.urbansharing.com/bergenbysykkel.no/station_information.json" target="_blank">API Link</a></p>';
                    echo '</div>';
                }
                
                return;
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
                echo '<span class="bergen-bysykkel-label">' . esc_html__('Ledige sykler', 'bergen-bysykkel') . '</span>';
                echo '</div>';
                echo '</div>';
                
                // Docks available
                echo '<div class="bergen-bysykkel-availability">';
                echo '<div class="bergen-bysykkel-indicator status-' . esc_attr($docks_status) . '"></div>';
                echo '<div class="bergen-bysykkel-info">';
                echo '<span class="bergen-bysykkel-count">' . esc_html($docks_available) . '</span>';
                echo '<span class="bergen-bysykkel-label">' . esc_html__('Ledige parkeringsplasser', 'bergen-bysykkel') . '</span>';
                echo '</div>';
                echo '</div>';
                
                echo '</div>'; // End of station box
            }
            
            // Add update time
            if (isset($stations_status['last_updated'])) {
                $update_time = date_i18n(get_option('time_format'), $stations_status['last_updated']);
                echo '<div class="bergen-bysykkel-update-time">';
                echo esc_html__('Sist oppdatert', 'bergen-bysykkel') . ': ' . esc_html($update_time);
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
         * Render CSS for the widget with unique class names to avoid conflicts
         */
        private function render_css() {
            ?>
            <style>
                .bergen-bysykkel-container {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                .bergen-bysykkel-station {
                    background-color: #f8f8f8;
                    border-radius: 6px;
                    margin-bottom: 15px;
                    padding: 15px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    box-sizing: border-box;
                }
                .bergen-bysykkel-station h3 {
                    margin-top: 0;
                    margin-bottom: 10px;
                    font-size: 18px;
                    color: #333;
                    font-weight: bold;
                }
                .bergen-bysykkel-availability {
                    display: flex;
                    align-items: center;
                    margin-bottom: 10px;
                    box-sizing: border-box;
                }
                .bergen-bysykkel-indicator {
                    width: 8px;
                    height: 35px;
                    border-radius: 4px;
                    margin-right: 10px;
                    flex-shrink: 0;
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
         * With improved error handling
         * 
         * @return array|false Station information or false on failure
         */
        private function get_station_information() {
            $url = 'https://gbfs.urbansharing.com/bergenbysykkel.no/station_information.json';
            $result = $this->make_api_request($url);
            
            if ($result === false) {
                bergen_bysykkel_debug_log('Bergen Bysykkel API: Failed to get station information');
                return false;
            }
            
            return $result;
        }

        /**
         * Get station status from Bergen Bysykkel API
         * With improved error handling
         * 
         * @return array|false Station status or false on failure
         */
        private function get_station_status() {
            $url = 'https://gbfs.urbansharing.com/bergenbysykkel.no/station_status.json';
            $result = $this->make_api_request($url);
            
            if ($result === false) {
                bergen_bysykkel_debug_log('Bergen Bysykkel API: Failed to get station status');
                return false;
            }
            
            return $result;
        }

        /**
         * Make an API request to Bergen Bysykkel API
         * With improved error handling and timeout settings
         * 
         * @param string $url API endpoint URL
         * @return array|false JSON decoded response or false on failure
         */
        private function make_api_request($url) {
            $args = array(
                'timeout' => 15, // Increased timeout
                'headers' => array(
                    'Client-Identifier' => 'kalgraff-bergen-bysykkel-wp-plugin',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
                )
            );

            $response = wp_remote_get($url, $args);
            
            if (is_wp_error($response)) {
                bergen_bysykkel_debug_log('Bergen Bysykkel API Error: ' . $response->get_error_message());
                return false;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                bergen_bysykkel_debug_log('Bergen Bysykkel API returned non-200 response code: ' . $response_code);
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            if (empty($body)) {
                bergen_bysykkel_debug_log('Bergen Bysykkel API returned empty response body');
                return false;
            }
            
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                bergen_bysykkel_debug_log('Bergen Bysykkel API returned invalid JSON: ' . json_last_error_msg());
                return false;
            }
            
            return $data;
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
        
        if (class_exists('Bergen_Bysykkel_Widget')) {
            $widget = new Bergen_Bysykkel_Widget();
            $widget->display_stations_info();
        } else {
            echo '<p>' . esc_html__('Bergen Bysykkel widget er ikke tilgjengelig.', 'bergen-bysykkel') . '</p>';
        }
        
        return ob_get_clean();
    }
    add_shortcode('bergen_bysykkel', 'bergen_bysykkel_shortcode');
} // End of class_exists('WP_Widget') check 