<?php 

if (!defined('ABSPATH')) {
    exit;
}

class RegisteredUsersSection {

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_settings() {
        add_settings_section(
            'mn_bbpress_registered_users_section',
            'Registered Users Report',
            array($this, 'preview_stored_settings'),
            'mn-bbpress-report-post-options-users'
        );

         add_settings_field(
            'mn_bbpress_flood_protection_timeout',
            'Disable users from submiting new report for x seconds',
            array($this, 'render_flood_protection_field'),
            'mn-bbpress-report-post-options-users',
            'mn_bbpress_registered_users_section',
            array('field' => 'flood_protection')
        );

        add_settings_field(
            'mn_bbpress_report_count_threshold',
            'Set post to pending after reports',
            array($this, 'render_report_count_threshold_field'),
            'mn-bbpress-report-post-options-users',
            'mn_bbpress_registered_users_section',
            array('field' => 'report_count_threshold')
        );

        add_settings_field(
            'mn_bbpress_false_report_count_threshold',
            'Slow down user after false reports',
            array($this, 'render_false_report_count_threshold_field'),
            'mn-bbpress-report-post-options-users',
            'mn_bbpress_registered_users_section',
            array('field' => 'false_report_count_threshold')
        );

        /*add_settings_field(
            'mn_bbpress_false_report_count_threshold_final',
            'Restrict user after false reports',
            array($this, 'render_false_report_count_threshold_final_field'),
            'mn-bbpress-report-post-options-users',
            'mn_bbpress_registered_users_section',
            array('field' => 'false_report_count_threshold_final')
        );*/
    }

    public function render_flood_protection_field($args) {
        $this->render_field($args);
    }

    public function render_report_count_threshold_field($args) {
        $this->render_field($args);
    }

    public function render_false_report_count_threshold_field($args) {
        $this->render_field($args);
    }

    /*public function render_false_report_count_threshold_final_field($args) {
        $this->render_field($args);
    }*/

    public function render_field($args) {
        $options = get_option('mn_bbpress_report_post_settings_users', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : 0; // Default to 0 if not set
        echo '<input type="number" id="'.$field.'" name="mn_bbpress_report_post_settings_users['.$field.']" value="'.esc_attr($value).'" />';
    }

    public function sanitize_settings($input) {
        $output = array();
        
        // Define all the fields we want to sanitize
        $fields = array(
            'flood_protection',
            'report_count_threshold',
            'false_report_count_threshold'/*,
            'false_report_count_threshold_final'*/
        );

        foreach ($fields as $field) {
            if (isset($input[$field])) {
                // Ensure the input is an integer
                $output[$field] = intval($input[$field]);
            } else {
                $output[$field] = 0;
            }
        }
        
        return $output;
    }

    public function preview_stored_settings() {
        $options = get_option('mn_bbpress_report_post_settings_users', array());

        echo '<div class="settings-preview">';
        echo '<h4>Stored Settings:</h4>';
        echo '<ul>';
        foreach ($options as $key => $value) {
            echo '<li><strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}

