<?php
/*
Plugin Name: MN bbPress Report Post
Description: Allows users to report inappropriate content in bbPress.
Version: 0.1
Author: Martin Naglič
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'options.php';


//define('MN_BBPRESS_MENU_SLUG', 'reported-posts');


class MN_bbPress_Report_Post {

	private $options_instance;

	public $plugin_version = "0.1";

    // Constructor function that is run when an instance of the class is created
    public function __construct() {

    	$this->options_instance = new MN_BBPress_Report_Post_Options();

        // Hook to add the report button after the bbPress reply content
	    add_action('bbp_theme_bottom_interaction', array($this, 'add_report_button'));
        
        // Hook to enqueue necessary JavaScript for this plugin
	    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Hook to handle AJAX request when a logged-in user reports a post
	    add_action('wp_ajax_report_post', array($this, 'handle_report'));
        
    	//hook to handle AJAX request when a moderator clears a report
    	add_action('wp_ajax_clear_report', array($this, 'handle_clear_report'));

    	//Admin menu hook
    	add_action('admin_menu', array($this, 'add_admin_menu'));

    	// Hook to show report count in the menu.
		add_filter('add_menu_classes', array($this, 'show_report_count_in_menu'));

		add_action('admin_enqueue_scripts', array($this, 'bbp_report_post_admin_scripts'));

		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));

		add_action('wp_footer', array($this, 'bbp_report_post_modal_markup'));

	}

	public function bbp_report_post_admin_scripts($hook) {
	    if ('settings_page_mn-bbpress-report-post' != $hook) {
	        return;
	    }
	    
	    // Enqueue styles and scripts here
	}

    public function add_report_button() {
	    $reporting_reasons = array('Nadlegovanje', 'Sovražni govor', 'Neprimerna vsebina', 'Spam');

	    if (is_user_logged_in()) {
	        $post_id = bbp_get_reply_id();
	        $user_id = get_current_user_id();

	        // Fetch all meta values once for optimization
	        $post_meta = get_post_meta($post_id);

	        // Simplified the checks using null coalescing operator
	        $users_reported = $post_meta['_reported_by'] ?? array();
	        $reported_data = $post_meta['_reported_data'][0] ?? array();
	        $reported_count = $post_meta['_reported_count'][0] ?? 0;

	        if (!in_array($user_id, $users_reported)) {
	            // HTML for the report button
	            ?>
	            <div class="bbp-quote report-post-btn" data-post-id="<?php echo $post_id ?>">
	                <a href="#" data-id="<?php bbp_reply_id(); ?>" class="button-icon report-post-btn" data-google-interstitial="false">
	                    <svg class="icon icon--small"><use xlink:href="#report"></use></svg>
	                    <span class="button-icon__text">Prijavi</span>
	                </a>
	            </div>
	            <?php
	            // Dropdown for reasons
	            echo '<select class="report-reason" style="display: none;" data-post-id="'. $post_id .'">';
	            echo '<option value="">Razlog za prijavo</option>';

	            foreach($reporting_reasons as $reason) {
	                echo '<option value="' . $reason . '">' . $reason . '</option>';
	            }

	            echo '</select>';
	        } else {
	            if (current_user_can('moderate') || current_user_can('keymaster')) {
	                echo '<span style="color:red;">Prijavljeno</span>';
	            } else {
	                echo '<span style="color:red;">Prijavljeno</span>';
	            }
	        }

	        // If user is a moderator or keymaster
	        if (current_user_can('moderate') || current_user_can('keymaster')) {
	            if($reported_count > 0) {
	                echo '<br><span>To sporočilo je bilo od zadnjega pregleda '. $reported_count .' krat prijavljeno.</span>';

	                if (is_array($reported_data)) {
	                    foreach ($reported_data as $reason => $count) {
	                        if ($count > 0) {
	                            echo '<br>- ' . esc_html($reason) . ': ' . intval($count) . ' times';
	                        }
	                    }
	                }

	                echo '<br><a href="#" class="clear-report-btn" data-post-id="'. $post_id .'">Počisti prijave</a>';
	            }
	        }
	    }
	}



	private function get_user_report_counts($user_id) {
	    // Fetch all user meta values once for optimization
	    $user_meta = get_user_meta($user_id);

	    $general_report_count = $user_meta['_user_general_report_count'][0] ?? 0;
	    $false_report_count = $user_meta['_user_false_report_count'][0] ?? 0;

	    return array($general_report_count, $false_report_count);
	}

	public function enqueue_styles(){
		wp_enqueue_style(
	        'bbp-report-post-main-styles',  // Handle
	        plugin_dir_url(__FILE__).'css/style.css',  // File URL
	        null,  // Dependencies 
	        $this->plugin_version  // Version number
	    );
	}

    // Function to enqueue the necessary JavaScript for the plugin
	public function enqueue_scripts() {
	    // Enqueue the JavaScript file located in the "js" folder
	    wp_enqueue_script('mn-report-post', plugin_dir_url(__FILE__) . 'js/report-post.js', array('jquery'), $this->plugin_version, true);

	    $general_options = get_option('mn_bbpress_report_post_settings_general');
		$message = isset($general_options['message_upon_report']) ? $general_options['message_upon_report'] : '';

		$user_id = get_current_user_id();
    	list($general_report_count, $false_report_count) = $this->get_user_report_counts($user_id);
    	$threshold_options = get_option('mn_bbpress_report_post_settings_users');
		$false_report_threshold = $threshold_options['false_report_count_threshold'] != false ? $threshold_options['false_report_count_threshold'] : 20;

		$flood_options = get_option('mn_bbpress_report_post_settings_users');
		$flood_protection = $flood_options['flood_protection'] != false ? $flood_options['flood_protection'] : 30;
        
        // Localize some PHP data for use in JavaScript
	    wp_localize_script('mn-report-post', 'mn_report_post', array(
	        'ajax_url' => admin_url('admin-ajax.php'), // The URL for handling AJAX requests in WordPress
	        'nonce'    => wp_create_nonce('report-post-nonce'), // A nonce for security
	        'message' => $message, //custom message
	        'false_report_threshold' => $false_report_threshold,
	        'general_report_count' => $general_report_count,
	        'false_report_count' => $false_report_count,
	        'flood_protection' => $flood_protection
	    ));
	}

	public function handle_report() {
	    check_ajax_referer('report-post-nonce', 'nonce');

	    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
	    $reason = sanitize_text_field($_POST['reason']);

	    $reporting_reasons = array('Nadlegovanje', 'Sovražni govor', 'Neprimerna vsebina', 'Spam');

	    if (!$post_id || !$reason || !in_array($reason, $reporting_reasons)) {
	        wp_send_json_error("Invalid post ID or reason.");
	        return;
	    }

	    $current_reports = get_post_meta($post_id, '_reported_count', true) ?: 0;
	    $reported_data = get_post_meta($post_id, '_reported_data', true) ?: array();

	    $user_id = get_current_user_id();

	    list($user_general_report_count, $false_report_count) = $this->get_user_report_counts($user_id);

	    $current_reports++;
	    $reported_data[$reason] = isset($reported_data[$reason]) ? $reported_data[$reason] + 1 : 1;

	    if ($user_id) {
	        add_post_meta($post_id, '_reported_by', $user_id);
	        $user_general_report_count++;
	        update_user_meta($user_id, '_user_general_report_count', $user_general_report_count);
	    }

	    update_post_meta($post_id, '_reported_count', $current_reports);
	    update_post_meta($post_id, '_reported_data', $reported_data);

	    $report_treshold_option = get_transient('report_count_threshold');
	    if (!$report_treshold_option) {
	        $report_treshold_option = get_option('report_count_threshold', 5);
	        set_transient('report_count_threshold', $report_treshold_option, DAY_IN_SECONDS);
	    }

	    if ($current_reports >= $report_treshold_option) {
	        wp_update_post(array('ID' => $post_id, 'post_status' => 'pending'));
	    }

	    wp_send_json_success();
	}


	public function handle_clear_report() {
	    check_ajax_referer('report-post-nonce', 'nonce');

	    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

	    if (!$post_id || !(current_user_can('moderate') || current_user_can('keymaster'))) {
	        wp_send_json_error("Invalid post ID or insufficient permissions.");
	        return;
	    }

	    $users_reported = (array) (get_post_meta($post_id, '_reported_by', false) ?: array());
		$false_reported = (array) (get_post_meta($post_id, '_false_by', false) ?: array());

	    // Get users who reported but haven't been flagged as falsely reporting this post before.
	    $valid_users_reported = array_diff($users_reported, $false_reported);  //this is the line referenced

	    foreach ($valid_users_reported as $user_id) {
	        list(, $user_false_report_count) = $this->get_user_report_counts($user_id);
	        $user_false_report_count++;
	        
	        // Instead of add_post_meta, we can just add the user_id to our $false_reported array and update it once later
	        $false_reported[] = $user_id;
	        
	        update_user_meta($user_id, '_user_false_report_count', $user_false_report_count);
	    }

	    // After processing all valid users, update the _false_by meta once.
	    update_post_meta($post_id, '_false_by', $false_reported);

	    update_post_meta($post_id, '_reported_count', 0);

	    $current_status = get_post_status($post_id);
	    if ($current_status == 'pending') {
	        wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'));
	    }

	    wp_send_json_success();
	}


	private $menu_slug = 'reported-posts';  


	public function add_admin_menu() {
		$menu_options =get_option('mn_bbpress_report_post_settings_general');
		$menu_position = $menu_options != false ? intval($menu_options['menu_position']) : 59;
	    $hook = add_menu_page(
	        'Reported Posts',          // Page title
	        'Reported Posts',          // Menu title
	        'manage_options',          // Capability
	        $this->menu_slug,          // Menu slug
	        array($this, 'render_reported_posts_page'),  // Callback function
	        'dashicons-warning',       // Icon URL (optional, 'dashicons-warning' is an exclamation mark icon)
	        $menu_position                         
	    );

	    // Override the default submenu with our preferred callback
	    add_submenu_page(
	        $this->menu_slug, 
	        'Reported Posts', 
	        'Reported Posts', 
	        'manage_options', 
	        $this->menu_slug,
	        array($this, 'render_reported_posts_page'),
	        0
	    );
	    add_submenu_page(
		    $this->menu_slug,
		    'Settings',
		    'Settings',
		    'manage_options',
		    'mn-bbpress-report-post-options',
		    array($this->options_instance, 'render_options_page')
		);

	}

    public function get_report_count() {  
	    global $wpdb;

	    $count = $wpdb->get_var(
	        $wpdb->prepare(
	            "SELECT COUNT(*) 
	             FROM {$wpdb->postmeta} AS pm
	             INNER JOIN {$wpdb->posts} AS p ON pm.post_id = p.ID
	             WHERE pm.meta_key = %s 
	             AND pm.meta_value > %d
	             AND p.post_status IN ('publish', 'pending')", 
	            '_reported_count', 
	            0
	        )
	    );

	    return intval($count);
	}

	public function show_report_count_in_menu( $menu ) {
		$report_count = $this->get_report_count();
		if ($report_count > 0) {
		    foreach ($menu as $menu_key => $menu_data) {
		        if ($menu_data[2] === $this->menu_slug) {
		            $menu[$menu_key][0] .= " <span class='update-plugins count-$report_count'><span class='plugin-count'>" . number_format_i18n($report_count) . "</span></span>";
		            return $menu;
		        }
		    }
		}
		return $menu;
	}

    public function render_reported_posts_page() {
	    echo '<div class="wrap">';
	    echo '<h1>Reported Posts</h1>';
	    
	    // Query to fetch reported posts
	    $args = array(
	        'post_type'   => array('reply', 'topic'), 
	        'meta_key'    => '_reported_count',
	        'meta_value'  => 0,
	        'meta_compare' => '>',
	        'posts_per_page' => -1,
	        'post_status' => array('publish', 'pending'),
	        'orderby' => '_reported_count'
	    );

	    $query = new WP_Query($args);
	    
	    if ($query->have_posts()) {
	        echo '<ul>';
	        while ($query->have_posts()) {
	            $query->the_post();
	            $post_id = get_the_ID();
	            $post_link = get_the_permalink($post_id);
	            $reported_count = get_post_meta($post_id, '_reported_count', true);

	            // Fetch reported reasons and their counts
	            $reported_data = get_post_meta($post_id, '_reported_data', true);

	            echo '<li>';
	            echo '<a href="'.$post_link.'" target="_blank"><strong>' . get_the_title() . '</strong></a> - Reported <strong>' . $reported_count . '</strong> times since last review';
	            
	            // If there are specific reporting reasons recorded, display them.
	            if ($reported_data && is_array($reported_data)) {
	                foreach ($reported_data as $reason => $count) {
	                    if ($count > 0) {
	                        echo '<br>- ' . esc_html($reason) . ': ' . intval($count) . ' times';
	                    }
	                }
	            }

	            echo '</li>';
	        }
	        echo '</ul>';
	    } else {
	        echo '<p>No reported posts found.</p>';
	    }
	    echo '</div>';
	    wp_reset_postdata(); // Reset post data after custom query
	}

	public function bbp_report_post_modal_markup() {

		$false_m_options = get_option('mn_bbpress_report_post_settings_general');
		$false_m = $false_m_options['message_upon_false_report'];
	    ?>
	    <div id="reportModal" style="display:none;">
	        <div class="modal-content">
	            <p id="countdownDisplay"></p>
	            <p class="countdownMessage"><?php echo $false_m; ?></p>
	            <div class="countdownButtons">
	            	<button id="confirmReport" class="button"disabled>Potrdi</button>
	            	<button id="closeModal" class="button">Zapri</button>
	            </div>
	        </div>
	    </div>
	    <?php
	}


}

// Create an instance of the class
new MN_bbPress_Report_Post();