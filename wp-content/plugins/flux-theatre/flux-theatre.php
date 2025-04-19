<?php
/**
 * Plugin Name: Flux Theatre Custom Post Types
 * Description: Custom post types for productions and bylines
 * Version: 1.0.0
 * Author: Flux Theatre
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flux_Theatre_CPT {
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('rest_api_init', array($this, 'add_rest_api_support'));
        add_action('rest_api_init', array($this, 'register_debug_endpoints'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_create_venue', array($this, 'handle_ajax_requests'));
        add_action('wp_ajax_convert_page_to_production', array($this, 'handle_ajax_requests'));
        
        // Register REST API fields
        add_action('rest_api_init', array($this, 'register_rest_fields'));
        
        // Add debug hooks
        add_action('init', array($this, 'debug_post_type_registration'));
        add_action('rest_api_init', array($this, 'debug_rest_api_init'));

        // Add cleanup endpoint
        add_action('rest_api_init', function () {
            register_rest_route('flux/v1', '/cleanup', array(
                'methods' => 'POST',
                'callback' => 'flux_run_cleanup',
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                }
            ));
        });

        // Add new endpoints
        add_action('rest_api_init', array($this, 'register_rest_endpoints'));

        // Add convert to production action to pages
        add_filter('page_row_actions', array($this, 'add_convert_to_production_action'), 10, 2);
        add_action('admin_footer', array($this, 'add_convert_to_production_script'));

        // Add featured image support
        add_action('admin_enqueue_scripts', array($this, 'enqueue_featured_image_styles'));
        add_action('add_meta_boxes', array($this, 'add_featured_image_meta_box'));

        // Add theme settings
        add_action('init', array($this, 'add_theme_settings'));
    }

    public function debug_post_type_registration() {
        // Only log errors, not successful registrations
        if (!post_type_exists('production')) {
            error_log('Flux Theatre: ERROR - Production post type does not exist in WordPress');
        }
    }

    public function debug_rest_api_init() {
        error_log('Flux Theatre: Debug - REST API initialization started');
        global $wp_rest_server;
        if ($wp_rest_server) {
            error_log('Flux Theatre: Debug - REST API server is initialized');
            $routes = $wp_rest_server->get_routes();
            error_log('Flux Theatre: Debug - Available routes: ' . print_r(array_keys($routes), true));
        } else {
            error_log('Flux Theatre: Debug - REST API server is not initialized');
        }
    }

    public function register_post_types() {
        // Register Production post type
        register_post_type('production', array(
            'labels' => array(
                'name' => __('Productions', 'flux-theatre'),
                'singular_name' => __('Production', 'flux-theatre'),
                'add_new' => __('Add New', 'flux-theatre'),
                'add_new_item' => __('Add New Production', 'flux-theatre'),
                'edit_item' => __('Edit Production', 'flux-theatre'),
                'new_item' => __('New Production', 'flux-theatre'),
                'view_item' => __('View Production', 'flux-theatre'),
                'search_items' => __('Search Productions', 'flux-theatre'),
                'not_found' => __('No productions found', 'flux-theatre'),
                'not_found_in_trash' => __('No productions found in Trash', 'flux-theatre'),
                'all_items' => __('All Productions', 'flux-theatre'),
                'menu_name' => __('Productions', 'flux-theatre')
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'productions'),
            'menu_icon' => 'dashicons-tickets-alt',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest' => true,
            'rest_base' => 'production',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'rest_namespace' => 'wp/v2'
        ));

        // Debug logging
        error_log('Flux Theatre: Production post type registered');
        error_log('Flux Theatre: REST API endpoint should be available at /wp-json/wp/v2/production');
        
        // Check if the post type is actually registered
        if (post_type_exists('production')) {
            error_log('Flux Theatre: Production post type exists in WordPress');
            $post_type = get_post_type_object('production');
            error_log('Flux Theatre: Production post type REST settings:');
            error_log('Flux Theatre: - show_in_rest: ' . ($post_type->show_in_rest ? 'true' : 'false'));
            error_log('Flux Theatre: - rest_base: ' . $post_type->rest_base);
            error_log('Flux Theatre: - rest_namespace: ' . $post_type->rest_namespace);
        } else {
            error_log('Flux Theatre: ERROR - Production post type does not exist in WordPress');
        }

        // Bylines Post Type
        register_post_type('bylines', array(
            'labels' => array(
                'name' => 'Bylines',
                'singular_name' => 'Bylines',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Bylines',
                'edit_item' => 'Edit Bylines',
                'new_item' => 'New Bylines',
                'view_item' => 'View Bylines',
                'search_items' => 'Search Bylines',
                'not_found' => 'No bylines found',
                'not_found_in_trash' => 'No bylines found in Trash'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'thumbnail', 'editor'),
            'menu_icon' => 'dashicons-groups',
            'rewrite' => array('slug' => 'bylines'),
            'show_in_rest' => true
        ));

        // Register Venue post type
        register_post_type('venue', array(
            'labels' => array(
                'name' => __('Venues', 'flux-theatre'),
                'singular_name' => __('Venue', 'flux-theatre'),
                'add_new' => __('Add New', 'flux-theatre'),
                'add_new_item' => __('Add New Venue', 'flux-theatre'),
                'edit_item' => __('Edit Venue', 'flux-theatre'),
                'new_item' => __('New Venue', 'flux-theatre'),
                'view_item' => __('View Venue', 'flux-theatre'),
                'search_items' => __('Search Venues', 'flux-theatre'),
                'not_found' => __('No venues found', 'flux-theatre'),
                'not_found_in_trash' => __('No venues found in Trash', 'flux-theatre'),
                'all_items' => __('All Venues', 'flux-theatre'),
                'menu_name' => __('Venues', 'flux-theatre')
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'venues'),
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-location',
            'show_in_rest' => true, // Enable REST API support
            'rest_base' => 'venues', // Change the REST API base to 'venues'
            'rest_controller_class' => 'WP_REST_Posts_Controller'
        ));
    }

    public function register_taxonomies() {
        // Production Categories
        register_taxonomy('production_category', 'production', array(
            'labels' => array(
                'name' => 'Production Categories',
                'singular_name' => 'Production Category',
                'search_items' => 'Search Categories',
                'all_items' => 'All Categories',
                'parent_item' => 'Parent Category',
                'parent_item_colon' => 'Parent Category:',
                'edit_item' => 'Edit Category',
                'update_item' => 'Update Category',
                'add_new_item' => 'Add New Category',
                'new_item_name' => 'New Category Name',
                'menu_name' => 'Categories'
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'production-category'),
            'show_in_rest' => true
        ));
    }

    public function add_meta_boxes() {
        // Production Meta Boxes
        add_meta_box(
            'performance_dates',
            'Performance Dates',
            array($this, 'performance_dates_meta_box'),
            'production',
            'normal',
            'high'
        );

        add_meta_box(
            'production_bylines',
            'Associated Bylines',
            array($this, 'render_production_bylines_meta_box'),
            'production',
            'normal',
            'high'
        );

        add_meta_box(
            'production_venue',
            'Venue',
            array($this, 'render_production_venue_meta_box'),
            'production',
            'normal',
            'high'
        );

        add_meta_box(
            'production_featured',
            'Featured Production',
            array($this, 'render_production_featured_meta_box'),
            'production',
            'side',
            'high'
        );

        // Bylines Meta Boxes
        add_meta_box(
            'bylines_social',
            'Social Media Links',
            array($this, 'render_bylines_social_meta_box'),
            'bylines',
            'normal',
            'high'
        );
    }

    public function performance_dates_meta_box($post) {
        wp_nonce_field('performance_dates_meta_box', 'performance_dates_meta_box_nonce');
        
        $dates = get_post_meta($post->ID, '_performance_dates', true);
        if (!is_array($dates)) {
            $dates = array();
        }
        
        // Get current year and create year options
        $current_year = date('Y');
        $year_options = array();
        // Add 10 years in the past and 2 years in the future
        for ($i = $current_year - 10; $i <= $current_year + 2; $i++) {
            $year_options[] = $i;
        }
        
        ?>
        <div class="performance-dates-container">
            <div class="performance-dates-header">
                <label for="performance_dates_year">Select Year:</label>
                <select id="performance_dates_year" class="performance-dates-year-select">
                    <?php foreach ($year_options as $year) : ?>
                        <option value="<?php echo esc_attr($year); ?>" <?php selected($year, $current_year); ?>>
                            <?php echo esc_html($year); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="performance-dates-calendar" class="performance-dates-calendar"></div>
            
            <div class="performance-dates-selected">
                <h4>Selected Dates:</h4>
                <div id="performance-dates-selected-dates" class="performance-dates-selected-dates">
                    <?php foreach ($dates as $date) : ?>
                        <span class="performance-dates-selected-date" data-date="<?php echo esc_attr($date); ?>">
                            <?php echo esc_html(date('F j, Y', strtotime($date))); ?>
                            <button type="button" class="performance-dates-remove-date">&times;</button>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <input type="hidden" name="performance_dates" id="performance_dates_input" value="<?php echo esc_attr(implode(',', $dates)); ?>" />
        </div>
        <?php
    }

    public function render_production_bylines_meta_box($post) {
        wp_nonce_field('production_bylines_meta_box', 'production_bylines_meta_box_nonce');
        
        $bylines = get_post_meta($post->ID, '_production_bylines', true);
        $all_bylines = get_posts(array(
            'post_type' => 'bylines',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        ?>
        <div class="production-bylines-container">
            <select name="production_bylines[]" multiple class="regular-text" style="width: 100%;">
                <?php foreach ($all_bylines as $bylines_post) : ?>
                    <option value="<?php echo $bylines_post->ID; ?>" <?php selected(in_array($bylines_post->ID, (array)$bylines)); ?>>
                        <?php echo $bylines_post->post_title; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    public function render_production_venue_meta_box($post) {
        wp_nonce_field('production_venue_meta_box', 'production_venue_meta_box_nonce');
        
        $selected_venue = get_post_meta($post->ID, '_production_venue', true);
        $venues = get_posts(array(
            'post_type' => 'venue',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        ?>
        <div class="production-venue-container">
            <select name="production_venue" id="production_venue" class="regular-text" style="width: 100%;">
                <option value="">No venue selected</option>
                <?php foreach ($venues as $venue) : ?>
                    <option value="<?php echo $venue->ID; ?>" <?php selected($selected_venue, $venue->ID); ?>>
                        <?php echo $venue->post_title; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="button" id="add-new-venue" style="margin-top: 10px;">Add New Venue</button>
            <p class="description">Select a venue or add a new one. This field is optional.</p>
        </div>

        <div id="new-venue-form" style="display: none; margin-top: 10px;">
            <input type="text" name="new_venue_name" id="new_venue_name" class="regular-text" placeholder="Venue Name" style="width: 100%; margin-bottom: 10px;">
            <textarea name="new_venue_address" id="new_venue_address" class="regular-text" placeholder="Venue Address" style="width: 100%; margin-bottom: 10px;"></textarea>
            <button type="button" class="button" id="save-new-venue">Save Venue</button>
            <button type="button" class="button" id="cancel-new-venue">Cancel</button>
        </div>
        <?php
    }

    public function render_production_featured_meta_box($post) {
        wp_nonce_field('production_featured_meta_box', 'production_featured_meta_box_nonce');
        
        $is_featured = get_post_meta($post->ID, '_is_featured', true);
        ?>
        <div class="production-featured-container">
            <label for="is_featured">
                <input type="checkbox" name="is_featured" id="is_featured" value="1" <?php checked($is_featured, '1'); ?> />
                Feature this production on the homepage
            </label>
            <p class="description">Featured productions will appear in the homepage carousel.</p>
        </div>
        <?php
    }

    public function render_bylines_social_meta_box($post) {
        wp_nonce_field('bylines_social_meta_box', 'bylines_social_meta_box_nonce');
        
        $social_links = get_post_meta($post->ID, '_bylines_social_links', true);
        ?>
        <div class="bylines-social-container">
            <p>
                <label for="twitter">Twitter:</label>
                <input type="url" name="bylines_social[twitter]" value="<?php echo esc_attr($social_links['twitter'] ?? ''); ?>" class="regular-text">
            </p>
            <p>
                <label for="facebook">Facebook:</label>
                <input type="url" name="bylines_social[facebook]" value="<?php echo esc_attr($social_links['facebook'] ?? ''); ?>" class="regular-text">
            </p>
            <p>
                <label for="instagram">Instagram:</label>
                <input type="url" name="bylines_social[instagram]" value="<?php echo esc_attr($social_links['instagram'] ?? ''); ?>" class="regular-text">
            </p>
            <p>
                <label for="linkedin">LinkedIn:</label>
                <input type="url" name="bylines_social[linkedin]" value="<?php echo esc_attr($social_links['linkedin'] ?? ''); ?>" class="regular-text">
            </p>
        </div>
        <?php
    }

    public function save_meta_boxes($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            error_log('Flux Theatre: Autosave detected, skipping save_meta_boxes');
            return;
        }

        // Check if the user has permissions to save
        if (!current_user_can('edit_post', $post_id)) {
            error_log('Flux Theatre: User does not have permission to edit post');
            return;
        }

        // Debug logging for POST data
        error_log('Flux Theatre: POST data: ' . print_r($_POST, true));

        // Save Production Dates
        if (isset($_POST['performance_dates']) && !empty($_POST['performance_dates'])) {
            $dates = explode(',', sanitize_text_field($_POST['performance_dates']));
            $dates = array_map('trim', $dates);
            $dates = array_filter($dates); // Remove empty values
            update_post_meta($post_id, '_performance_dates', $dates);
            error_log('Flux Theatre: Saved production dates: ' . print_r($dates, true));
        } else {
            delete_post_meta($post_id, '_performance_dates');
            error_log('Flux Theatre: No production dates found, removed existing dates');
        }

        // Save Featured Status
        if (isset($_POST['production_featured_meta_box_nonce']) && wp_verify_nonce($_POST['production_featured_meta_box_nonce'], 'production_featured_meta_box')) {
            $is_featured = isset($_POST['is_featured']) ? '1' : '0';
            update_post_meta($post_id, '_is_featured', $is_featured);
            error_log('Flux Theatre: Saved featured status: ' . $is_featured);
        }

        // Save Production Bylines
        if (isset($_POST['production_bylines_meta_box_nonce']) && wp_verify_nonce($_POST['production_bylines_meta_box_nonce'], 'production_bylines_meta_box')) {
            if (isset($_POST['production_bylines'])) {
                update_post_meta($post_id, '_production_bylines', array_map('intval', $_POST['production_bylines']));
                error_log('Flux Theatre: Saved production bylines');
            }
        }

        // Save Bylines Social Links
        if (isset($_POST['bylines_social_meta_box_nonce']) && wp_verify_nonce($_POST['bylines_social_meta_box_nonce'], 'bylines_social_meta_box')) {
            if (isset($_POST['bylines_social'])) {
                update_post_meta($post_id, '_bylines_social_links', $_POST['bylines_social']);
                error_log('Flux Theatre: Saved bylines social links');
            }
        }

        // Save Production Venue
        if (isset($_POST['production_venue_meta_box_nonce']) && wp_verify_nonce($_POST['production_venue_meta_box_nonce'], 'production_venue_meta_box')) {
            if (isset($_POST['production_venue']) && !empty($_POST['production_venue'])) {
                update_post_meta($post_id, '_production_venue', intval($_POST['production_venue']));
                error_log('Flux Theatre: Saved production venue');
            } else {
                delete_post_meta($post_id, '_production_venue');
                error_log('Flux Theatre: Removed production venue');
            }
        }

        // Save Featured Image
        if (isset($_POST['_thumbnail_id'])) {
            $thumbnail_id = intval($_POST['_thumbnail_id']);
            if ($thumbnail_id > 0) {
                set_post_thumbnail($post_id, $thumbnail_id);
                error_log('Flux Theatre: Set featured image');
            } else {
                delete_post_thumbnail($post_id);
                error_log('Flux Theatre: Removed featured image');
            }
        }

        // Debug logging
        error_log('Flux Theatre: Completed saving post meta for post ID: ' . $post_id);
    }

    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if (($hook == 'post-new.php' || $hook == 'post.php') && ('production' === $post_type || 'venue' === $post_type)) {
            // Enqueue jQuery UI
            wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
            wp_enqueue_script('jquery-ui-datepicker');
            
            // Enqueue WordPress media scripts
            wp_enqueue_media();
            
            // Enqueue our custom files
            wp_enqueue_script('flux-theatre-admin', plugins_url('js/admin.js', __FILE__), array('jquery', 'jquery-ui-datepicker', 'media-upload'), '1.0.0', true);
            wp_enqueue_style('flux-theatre-admin', plugins_url('css/admin.css', __FILE__), array('jquery-ui'), '1.0.0');
            
            // Add AJAX URL and nonce
            wp_localize_script('flux-theatre-admin', 'fluxTheatre', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('flux_theatre_nonce')
            ));
            
            // Debug information
            error_log('Flux Theatre: Enqueuing scripts for ' . $post_type);
            error_log('Flux Theatre: JS Path: ' . plugins_url('js/admin.js', __FILE__));
            error_log('Flux Theatre: CSS Path: ' . plugins_url('css/admin.css', __FILE__));
        }
    }

    public function handle_ajax_requests() {
        if (isset($_POST['action']) && $_POST['action'] === 'create_venue') {
            check_ajax_referer('flux_theatre_nonce', 'nonce');

            $venue_name = sanitize_text_field($_POST['name']);
            $venue_address = sanitize_textarea_field($_POST['address']);

            $venue_id = wp_insert_post(array(
                'post_title' => $venue_name,
                'post_content' => $venue_address,
                'post_type' => 'venue',
                'post_status' => 'publish'
            ));

            if (!is_wp_error($venue_id)) {
                wp_send_json_success(array(
                    'id' => $venue_id,
                    'title' => $venue_name
                ));
            } else {
                wp_send_json_error('Failed to create venue');
            }
        }

        if (isset($_POST['action']) && $_POST['action'] === 'convert_page_to_production') {
            $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
            $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
            
            if (!$post_id || !$nonce) {
                wp_send_json_error('Invalid request parameters');
                return;
            }

            if (!wp_verify_nonce($nonce, 'convert_to_production_' . $post_id)) {
                wp_send_json_error('Invalid nonce');
                return;
            }

            if (!current_user_can('edit_posts')) {
                wp_send_json_error('Insufficient permissions');
                return;
            }

            $page = get_post($post_id);
            if (!$page || $page->post_type !== 'page') {
                wp_send_json_error('Invalid page ID or post type');
                return;
            }

            // Create new production
            $production_data = array(
                'post_title' => $page->post_title,
                'post_content' => $page->post_content,
                'post_excerpt' => $page->post_excerpt,
                'post_status' => $page->post_status,
                'post_type' => 'production',
                'comment_status' => $page->comment_status,
                'ping_status' => $page->ping_status,
                'post_author' => $page->post_author
            );

            // Insert the production
            $production_id = wp_insert_post($production_data);
            if (is_wp_error($production_id)) {
                wp_send_json_error($production_id->get_error_message());
                return;
            }

            // Copy featured image if exists
            $featured_image_id = get_post_thumbnail_id($post_id);
            if ($featured_image_id) {
                set_post_thumbnail($production_id, $featured_image_id);
            }

            // Copy all meta data
            $meta_keys = get_post_custom_keys($post_id);
            if ($meta_keys) {
                foreach ($meta_keys as $key) {
                    $values = get_post_custom_values($key, $post_id);
                    foreach ($values as $value) {
                        add_post_meta($production_id, $key, $value);
                    }
                }
            }

            // Delete original page
            wp_delete_post($post_id, true);

            // Get the new production
            $production = get_post($production_id);

            wp_send_json_success(array(
                'production' => array(
                    'id' => $production->ID,
                    'title' => $production->post_title,
                    'slug' => $production->post_name,
                    'status' => $production->post_status
                )
            ));
        }

        wp_die();
    }

    public function register_rest_fields() {
        register_rest_field('production', 'performance_dates', array(
            'get_callback' => array($this, 'get_performance_dates'),
            'update_callback' => null,
            'schema' => array(
                'description' => 'Production performance dates',
                'type' => 'array',
                'items' => array(
                    'type' => 'string',
                    'format' => 'date'
                )
            )
        ));

        register_rest_field('production', 'venue', array(
            'get_callback' => array($this, 'get_production_venue'),
            'update_callback' => null,
            'schema' => array(
                'description' => 'Production venue',
                'type' => 'object'
            )
        ));

        register_rest_field('production', 'is_featured', array(
            'get_callback' => array($this, 'get_is_featured'),
            'update_callback' => null,
            'schema' => array(
                'description' => 'Whether the production is featured on the homepage',
                'type' => 'boolean'
            )
        ));
    }

    public function get_performance_dates($object) {
        return get_post_meta($object['id'], '_performance_dates', true) ?: array();
    }

    public function get_production_venue($object) {
        $venue_id = get_post_meta($object['id'], '_production_venue', true);
        if (!$venue_id) {
            return null;
        }
        
        $venue = get_post($venue_id);
        if (!$venue) {
            return null;
        }
        
        return array(
            'id' => $venue->ID,
            'title' => $venue->post_title,
            'content' => $venue->post_content
        );
    }

    public function get_is_featured($object) {
        return get_post_meta($object['id'], '_is_featured', true) === '1';
    }

    public function add_rest_api_support() {
        global $wp_post_types;
        
        // Ensure production post type exists
        if (isset($wp_post_types['production'])) {
            $wp_post_types['production']->show_in_rest = true;
            $wp_post_types['production']->rest_base = 'production';
            $wp_post_types['production']->rest_controller_class = 'WP_REST_Posts_Controller';
            
            error_log('Flux Theatre: REST API support added to production post type');
        } else {
            error_log('Flux Theatre: ERROR - Production post type not found when adding REST API support');
        }
    }

    public function register_debug_endpoints() {
        register_rest_route('flux-theatre/v1', '/debug', array(
            'methods' => 'GET',
            'callback' => array($this, 'debug_endpoint'),
            'permission_callback' => '__return_true'
        ));
    }

    public function debug_endpoint($request) {
        global $wp_post_types;
        
        $debug_info = array(
            'production_post_type_exists' => post_type_exists('production'),
            'production_post_type' => isset($wp_post_types['production']) ? $wp_post_types['production'] : null,
            'rest_namespaces' => rest_get_server()->get_namespaces(),
            'rest_routes' => rest_get_server()->get_routes(),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => phpversion()
        );
        
        return rest_ensure_response($debug_info);
    }

    public function register_rest_endpoints() {
        // Register hero media endpoint
        register_rest_route('wp/v2', '/hero-media', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_hero_media'),
            'permission_callback' => '__return_true',
            'args' => array(
                'type' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('image', 'video', 'carousel')
                )
            )
        ));

        // Register featured productions endpoint
        register_rest_route('wp/v2', '/productions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_productions'),
            'permission_callback' => '__return_true'
        ));

        // Register menus endpoint
        register_rest_route('wp/v2', '/menus', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_menus'),
            'permission_callback' => '__return_true'
        ));

        // Register single menu endpoint
        register_rest_route('wp/v2', '/menus/(?P<location>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_menu_by_location'),
            'permission_callback' => '__return_true',
            'args' => array(
                'location' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Menu location (e.g., primary, footer)'
                )
            )
        ));

        // Register convert page to production endpoint
        register_rest_route('wp/v2', '/convert-page-to-production/(?P<id>\d+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'convert_page_to_production'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'ID of the page to convert'
                ),
                'delete_original' => array(
                    'required' => false,
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Whether to delete the original page after conversion'
                )
            )
        ));

        // Add CORS headers
        add_action('rest_api_init', function() {
            remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
            add_filter('rest_pre_serve_request', function($value) {
                $origin = get_http_origin();
                if ($origin) {
                    header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
                    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                    header('Access-Control-Allow-Credentials: true');
                    header('Access-Control-Allow-Headers: Authorization, Content-Type');
                }
                return $value;
            });
        }, 15);
    }

    public function get_hero_media() {
        $hero_type = get_theme_mod('flux_hero_media_type', 'image');
        $hero_content = array();

        if ($hero_type === 'video') {
            $hero_content = array(
                'url' => get_theme_mod('flux_hero_video_url', '')
            );
        } elseif ($hero_type === 'carousel') {
            $hero_content = array(
                'images' => get_theme_mod('flux_hero_carousel_images', array()),
                'interval' => get_theme_mod('flux_hero_carousel_interval', 5000)
            );
        } else {
            // Default to image type
            $image_id = get_theme_mod('flux_hero_image', '');
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'full') : '';
            $hero_content = array(
                'url' => $image_url,
                'alt' => get_theme_mod('flux_hero_image_alt', '')
            );
        }

        return array(
            'type' => $hero_type,
            'content' => $hero_content,
            'title' => get_theme_mod('flux_hero_title', ''),
            'subtitle' => get_theme_mod('flux_hero_subtitle', ''),
            'cta' => array(
                'message' => get_theme_mod('flux_hero_cta_message', ''),
                'link' => get_theme_mod('flux_hero_cta_link', '')
            )
        );
    }

    public function get_productions($request) {
        $args = array(
            'post_type' => 'production',
            'posts_per_page' => $request->get_param('per_page') ?: 10,
            'meta_query' => array()
        );

        // Add slug filter if provided
        if ($request->get_param('slug')) {
            $args['name'] = $request->get_param('slug');
            $args['posts_per_page'] = 1;
        }

        if ($request->get_param('featured')) {
            $args['meta_query'][] = array(
                'key' => '_is_featured',
                'value' => '1',
                'compare' => '='
            );
        }

        $query = new WP_Query($args);
        $productions = array();

        foreach ($query->posts as $post) {
            // Get excerpt or create a fallback from content
            $excerpt = get_the_excerpt($post);
            if (empty($excerpt)) {
                $excerpt = wp_trim_words(strip_shortcodes($post->post_content), 55, '...');
            }

            // Get featured image URL with fallback
            $featured_image_url = false;
            if (has_post_thumbnail($post->ID)) {
                $featured_image_url = get_the_post_thumbnail_url($post->ID, 'full');
            }

            // Get all performance dates
            $performance_dates = get_post_meta($post->ID, '_performance_dates', true);
            if (!is_array($performance_dates)) {
                $performance_dates = array();
            }

            // Get ticket link
            $ticket_link = get_post_meta($post->ID, '_ticket_link', true);

            // Get venue
            $venue_id = get_post_meta($post->ID, '_production_venue', true);
            $venue = '';
            if ($venue_id) {
                $venue_post = get_post($venue_id);
                if ($venue_post) {
                    $venue = $venue_post->post_title;
                }
            }

            $production = array(
                'id' => $post->ID,
                'title' => array(
                    'rendered' => get_the_title($post)
                ),
                'slug' => $post->post_name,
                'content' => array(
                    'rendered' => apply_filters('the_content', $post->post_content)
                ),
                'excerpt' => array(
                    'rendered' => $excerpt
                ),
                'featured_image' => array(
                    'url' => $featured_image_url,
                    'alt' => get_post_meta(get_post_thumbnail_id($post->ID), '_wp_attachment_image_alt', true) ?: get_the_title($post)
                ),
                'venue' => $venue,
                'performance_dates' => $performance_dates,
                'ticket_link' => $ticket_link
            );
            $productions[] = $production;
        }

        // If filtering by slug and we found a production, return just that production
        if ($request->get_param('slug') && !empty($productions)) {
            return $productions[0];
        }

        return $productions;
    }

    public function get_menus() {
        $locations = get_nav_menu_locations();
        $menus = array();

        foreach ($locations as $location => $menu_id) {
            $menu = wp_get_nav_menu_object($menu_id);
            if ($menu) {
                $menus[$location] = array(
                    'id' => $menu->term_id,
                    'name' => $menu->name,
                    'items' => $this->get_menu_items($menu_id)
                );
            }
        }

        return rest_ensure_response($menus);
    }

    public function get_menu_by_location($request) {
        $location = $request->get_param('location');
        $locations = get_nav_menu_locations();

        if (!isset($locations[$location])) {
            return new WP_Error(
                'menu_location_not_found',
                'Menu location does not exist',
                array('status' => 404)
            );
        }

        $menu_id = $locations[$location];
        $menu = wp_get_nav_menu_object($menu_id);

        if (!$menu) {
            return new WP_Error(
                'menu_not_found',
                'Menu not found',
                array('status' => 404)
            );
        }

        return rest_ensure_response(array(
            'id' => $menu->term_id,
            'name' => $menu->name,
            'items' => $this->get_menu_items($menu_id)
        ));
    }

    private function get_menu_items($menu_id) {
        $menu_items = wp_get_nav_menu_items($menu_id);
        $items = array();

        if (!$menu_items) {
            return array();
        }

        foreach ($menu_items as $item) {
            $items[] = array(
                'id' => $item->ID,
                'title' => $item->title,
                'url' => $item->url,
                'target' => $item->target,
                'classes' => $item->classes,
                'description' => $item->description,
                'parent' => $item->menu_item_parent,
                'order' => $item->menu_order,
                'type' => $item->type,
                'type_label' => $item->type_label,
                'object' => $item->object,
                'object_id' => $item->object_id
            );
        }

        return $items;
    }

    public function convert_page_to_production($request) {
        $page_id = $request->get_param('id');
        $delete_original = $request->get_param('delete_original', false);

        // Get the page
        $page = get_post($page_id);
        if (!$page || $page->post_type !== 'page') {
            return new WP_Error(
                'invalid_page',
                'Invalid page ID or post type',
                array('status' => 400)
            );
        }

        // Create new production
        $production_data = array(
            'post_title' => $page->post_title,
            'post_content' => $page->post_content,
            'post_excerpt' => $page->post_excerpt,
            'post_status' => $page->post_status,
            'post_type' => 'production',
            'comment_status' => $page->comment_status,
            'ping_status' => $page->ping_status,
            'post_author' => $page->post_author
        );

        // Insert the production
        $production_id = wp_insert_post($production_data);
        if (is_wp_error($production_id)) {
            return $production_id;
        }

        // Copy featured image if exists
        $featured_image_id = get_post_thumbnail_id($page_id);
        if ($featured_image_id) {
            set_post_thumbnail($production_id, $featured_image_id);
        }

        // Copy all meta data
        $meta_keys = get_post_custom_keys($page_id);
        if ($meta_keys) {
            foreach ($meta_keys as $key) {
                $values = get_post_custom_values($key, $page_id);
                foreach ($values as $value) {
                    add_post_meta($production_id, $key, $value);
                }
            }
        }

        // Delete original page if requested
        if ($delete_original) {
            wp_delete_post($page_id, true);
        }

        // Get the new production
        $production = get_post($production_id);

        return rest_ensure_response(array(
            'success' => true,
            'production' => array(
                'id' => $production->ID,
                'title' => $production->post_title,
                'slug' => $production->post_name,
                'status' => $production->post_status
            ),
            'original_page_deleted' => $delete_original
        ));
    }

    public function add_convert_to_production_action($actions, $post) {
        if (current_user_can('edit_posts')) {
            $actions['convert_to_production'] = sprintf(
                '<a href="#" class="convert-to-production" data-id="%d" data-nonce="%s">%s</a>',
                $post->ID,
                wp_create_nonce('convert_to_production_' . $post->ID),
                __('Convert to Production', 'flux-theatre')
            );
        }
        return $actions;
    }

    public function add_convert_to_production_script() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'edit-page') {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('.convert-to-production').on('click', function(e) {
                        e.preventDefault();
                        
                        var $link = $(this);
                        var postId = $link.data('id');
                        var nonce = $link.data('nonce');
                        
                        if (!confirm('Are you sure you want to convert this page to a production?')) {
                            return;
                        }
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'convert_page_to_production',
                                post_id: postId,
                                nonce: nonce,
                                delete_original: true
                            },
                            beforeSend: function() {
                                $link.text('Converting...');
                            },
                            success: function(response) {
                                if (response.success) {
                                    $link.closest('tr').fadeOut(400, function() {
                                        $(this).remove();
                                    });
                                    alert('Page successfully converted to production!');
                                } else {
                                    alert('Error: ' + response.data);
                                    $link.text('Convert to Production');
                                }
                            },
                            error: function() {
                                alert('An error occurred while converting the page.');
                                $link.text('Convert to Production');
                            }
                        });
                    });
                });
            </script>
            <?php
        }
    }

    public function enqueue_featured_image_styles($hook) {
        global $post_type;
        
        if (($hook == 'post-new.php' || $hook == 'post.php') && 'production' === $post_type) {
            wp_enqueue_style('flux-theatre-admin', plugins_url('css/admin.css', __FILE__));
        }
    }

    public function add_featured_image_meta_box() {
        add_meta_box(
            'production_featured_image',
            __('Featured Image', 'flux-theatre'),
            array($this, 'render_featured_image_meta_box'),
            'production',
            'side',
            'high'
        );
    }

    public function render_featured_image_meta_box($post) {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        ?>
        <div class="featured-image-container">
            <div class="featured-image-preview">
                <?php if ($thumbnail_id) : ?>
                    <?php echo wp_get_attachment_image($thumbnail_id, 'medium'); ?>
                <?php else : ?>
                    <div class="no-image-placeholder">
                        <span class="dashicons dashicons-format-image"></span>
                        <p><?php _e('No featured image set', 'flux-theatre'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <p class="hide-if-no-js">
                <a href="#" class="set-featured-image" data-title="<?php esc_attr_e('Set featured image', 'flux-theatre'); ?>">
                    <?php _e('Set featured image', 'flux-theatre'); ?>
                </a>
                <?php if ($thumbnail_id) : ?>
                    <a href="#" class="remove-featured-image">
                        <?php _e('Remove featured image', 'flux-theatre'); ?>
                    </a>
                <?php endif; ?>
            </p>
            <input type="hidden" name="_thumbnail_id" id="_thumbnail_id" value="<?php echo esc_attr($thumbnail_id); ?>" />
        </div>
        <?php
    }

    public function add_theme_settings() {
        // Add theme settings section
        add_action('customize_register', function($wp_customize) {
            // Hero Media Section
            $wp_customize->add_section('flux_hero_media', array(
                'title' => __('Hero Media', 'flux-theatre'),
                'priority' => 30,
            ));

            // Hero Title
            $wp_customize->add_setting('flux_hero_title', array(
                'default' => '',
                'transport' => 'refresh',
            ));

            $wp_customize->add_control('flux_hero_title', array(
                'label' => __('Hero Title', 'flux-theatre'),
                'section' => 'flux_hero_media',
                'type' => 'text',
            ));

            // Hero Subtitle
            $wp_customize->add_setting('flux_hero_subtitle', array(
                'default' => '',
                'transport' => 'refresh',
            ));

            $wp_customize->add_control('flux_hero_subtitle', array(
                'label' => __('Hero Subtitle', 'flux-theatre'),
                'section' => 'flux_hero_media',
                'type' => 'textarea',
            ));

            // CTA Message
            $wp_customize->add_setting('flux_hero_cta_message', array(
                'default' => '',
                'transport' => 'refresh',
            ));

            $wp_customize->add_control('flux_hero_cta_message', array(
                'label' => __('CTA Message', 'flux-theatre'),
                'section' => 'flux_hero_media',
                'type' => 'text',
            ));

            // CTA Link
            $wp_customize->add_setting('flux_hero_cta_link', array(
                'default' => '',
                'transport' => 'refresh',
            ));

            $wp_customize->add_control('flux_hero_cta_link', array(
                'label' => __('CTA Link', 'flux-theatre'),
                'section' => 'flux_hero_media',
                'type' => 'url',
            ));
        });
    }
}

new Flux_Theatre_CPT();

function flux_run_cleanup() {
    require_once plugin_dir_path(__FILE__) . 'cleanup.php';
    return new WP_REST_Response('Cleanup completed successfully', 200);
} 