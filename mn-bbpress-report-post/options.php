<?php

if (!defined('ABSPATH')) {
    exit;
}

class MN_BBPress_Report_Post_Options {

	public function __construct() {
		//error_log('MN_BBPress_Report_Post_Options class initiated');
        add_action('admin_menu', array($this, 'settings_init'));
    }

    public function settings_init() {

        // Including files from the "includes" directory
        require_once plugin_dir_path(__FILE__) . 'includes/registered-users-section.php';
        //require_once plugin_dir_path(__FILE__) . 'includes/email-notifications-section.php';
        require_once plugin_dir_path(__FILE__) . 'includes/general-settings-section.php';

        // Initialize EmailNotificationsSection
        //$email_notifications_section = new EmailNotificationsSection();
        $general_settings_section = new GeneralSettingsSection();
		$registered_users_section = new RegisteredUsersSection();

		register_setting(
	        'mn_bbpress_report_post_settings_group_general',
	        'mn_bbpress_report_post_settings_general',
	        array($general_settings_section, 'sanitize_settings')
	    );

	    register_setting(
	        'mn_bbpress_report_post_settings_group_users',
	        'mn_bbpress_report_post_settings_users',
	        array($registered_users_section, 'sanitize_settings')
	    );

		/*register_setting(
		    'mn_bbpress_report_post_settings_group_email',
		    'mn_bbpress_report_email_notifications',
		    array($this, 'sanitize_email_settings')
		);*/


    }

    public function render_options_page() {
	?>
	    <div class="wrap">
	        <h2>Report post settings</h2>
	        
	        <?php 
	            $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
	        ?>
	        
	        <h2 class="nav-tab-wrapper">
	            <a href="?page=mn-bbpress-report-post-options&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General Settings</a>
	            <a href="?page=mn-bbpress-report-post-options&tab=registered_users" class="nav-tab <?php echo $active_tab == 'registered_users' ? 'nav-tab-active' : ''; ?>">Registered Users Report</a>
	            <!--<a href="?page=mn-bbpress-report-post-options&tab=email_notifications" class="nav-tab <?php //echo $active_tab == 'email_notifications' ? 'nav-tab-active' : ''; ?>">Email Notifications</a>-->
	        </h2>

	        <form method="post" action="options.php">
	            <?php 
	                if ($active_tab == 'general') {
	                    settings_fields('mn_bbpress_report_post_settings_group_general');
	                    do_settings_sections('mn-bbpress-report-post-options-general');
	                } elseif ($active_tab == 'registered_users') {
	                    settings_fields('mn_bbpress_report_post_settings_group_users');
	                    do_settings_sections('mn-bbpress-report-post-options-users');
	                } /*else {
	                    settings_fields('mn_bbpress_report_post_settings_group_email');
	                    do_settings_sections('mn-bbpress-report-post-options-email');
	                }*/
	            ?>
	            <?php submit_button(); ?>
	        </form>
	    </div>
	    <?php
	}



}
