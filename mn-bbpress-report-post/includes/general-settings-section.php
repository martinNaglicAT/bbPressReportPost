<?php 

if (!defined('ABSPATH')) {
    exit;
}

class GeneralSettingsSection {



    public function __construct() {
    	//error_log('GeneralSettingsSection class initiated');
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_settings() {
        add_settings_section(
            'mn_bbpress_general_section',
            'General Settings',
            null,
            'mn-bbpress-report-post-options-general'
        );

        add_settings_field(
            'mn_bbpress_message_upon_report',
            'Message Upon Report',
            array($this, 'render_message_upon_report_input'),
            'mn-bbpress-report-post-options-general',
            'mn_bbpress_general_section'
        );

        add_settings_field(
            'mn_bbpress_message_upon_false_report',
            'Message Upon False Report Action',
            array($this, 'render_message_upon_false_report_input'),
            'mn-bbpress-report-post-options-general',
            'mn_bbpress_general_section'
        );

        add_settings_field(
            'mn_bbpress_menu_position',
            'Menu position',
            array($this, 'render_menu_position'),
            'mn-bbpress-report-post-options-general',
            'mn_bbpress_general_section'
        );
    }


    public function sanitize_settings($input) {
	    $sanitized_input = array();
	    
	    if (isset($input['message_upon_report'])) {
	        $sanitized_input['message_upon_report'] = $this->sanitize_text($input['message_upon_report']);
	    }

	    if (isset($input['message_upon_false_report'])) {
	        $sanitized_input['message_upon_false_report'] = $this->sanitize_text($input['message_upon_false_report']);
	    }

	    if (isset($input['menu_position'])) {
	        $sanitized_input['menu_position'] = $this->sanitize_number($input['menu_position']);
	    }

	    return $sanitized_input;
	}

	private function sanitize_text($text) {
	    $allowed_chars = "a-zA-Z0-9.,:?! čšžČŠŽ";
	    return preg_replace("/[^$allowed_chars]/", '', $text);
	}

	private function sanitize_number($number) {
	    return absint($number); // Makes sure it's a positive integer.
	}



	public function render_message_upon_report_input() {
	    $options = get_option('mn_bbpress_report_post_settings_general');
	    $message = isset($options['message_upon_report']) ? $options['message_upon_report'] : '';
	    echo '<textarea name="mn_bbpress_report_post_settings_general[message_upon_report]" id="mn_bbpress_message_upon_report" rows="4" cols="50">' . esc_textarea($message) . '</textarea>';
	    echo '<p><strong>Saved Value:</strong> ' . esc_html($message) . '</p>';
	}

	public function render_message_upon_false_report_input() {
	    $options = get_option('mn_bbpress_report_post_settings_general');
	    $message = isset($options['message_upon_false_report']) ? $options['message_upon_false_report'] : '';
	    echo '<textarea name="mn_bbpress_report_post_settings_general[message_upon_false_report]" id="mn_bbpress_message_upon_false_report" rows="4" cols="50">' . esc_textarea($message) . '</textarea>';
	    echo '<p><strong>Saved Value:</strong> ' . esc_html($message) . '</p>';
	}

	public function render_menu_position() {
	    $options = get_option('mn_bbpress_report_post_settings_general');
	    $position = isset($options['menu_position']) ? $options['menu_position'] : 59; // Default value is 10
	    echo '<input type="number" name="mn_bbpress_report_post_settings_general[menu_position]" id="mn_bbpress_menu_position" value="' . esc_attr($position) . '">';
	    echo '<p><strong>Saved Value:</strong> ' . esc_html($position) . '</p>';
	}




}
