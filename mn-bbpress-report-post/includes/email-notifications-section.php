<?php

if (!defined('ABSPATH')) {
    exit;
}

/*class EmailNotificationsSection {

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings() {
        add_settings_section(
            'mn_bbpress_email_notifications_section',
            'Email Notifications',
            null,
            'mn-bbpress-report-post-options-email'
        );

        add_settings_field(
            'mn_bbpress_enable_email_notifications',
            'Enable email notifications for moderators',
            [$this, 'render_email_notifications_checkbox'],
            'mn-bbpress-report-post-options-email',
            'mn_bbpress_email_notifications_section'
        );

        add_settings_field(
            'mn_bbpress_admin_emails',
            'Admin Emails',
            [$this, 'render_admin_emails_input'],
            'mn-bbpress-report-post-options-email',
            'mn_bbpress_email_notifications_section'
        );

    }

    public function render_email_notifications_checkbox() {
        $options = get_option('mn_bbpress_report_email_notifications', array());
        $checked = isset($options['enable_email_notifications']) && $options['enable_email_notifications'] == '1' ? 'checked' : '';
        echo '<input type="checkbox" name="mn_bbpress_report_email_notifications[enable_email_notifications]" value="1" ' . $checked . ' id="mn_bbpress_enable_email_notifications">';
    }

    public function render_admin_emails_input() {
        $options = get_option('mn_bbpress_report_email_notifications', array());
        $emails = isset($options['admin_emails']) ? $options['admin_emails'] : '';
        echo '<input type="text" name="mn_bbpress_report_email_notifications[admin_emails]" value="' . esc_attr($emails) . '" id="mn_bbpress_admin_emails">';
    }


    public function sanitize_email_settings($input) {
        error_log(print_r($input, true));
        $output = array();
        
        // Checkbox
        $output['enable_email_notifications'] = isset($input['enable_email_notifications']) && $input['enable_email_notifications'] == '1' ? '1' : '0';
        
        // Emails
        if (isset($input['admin_emails'])) {
            $emails = explode(',', $input['admin_emails']);
            $sanitized_emails = array();
            foreach ($emails as $email) {
                $email = sanitize_email(trim($email));
                if (is_email($email)) {
                    $sanitized_emails[] = $email;
                }
            }
            $output['admin_emails'] = implode(', ', $sanitized_emails);
        } else {
            $output['admin_emails'] = '';
        }

        // Return sanitized array
        return $output;
    }
}*/