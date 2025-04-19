<?php
/**
 * Theme functions and definitions
 */

// Register navigation menus
function flux_theatre_register_menus() {
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'flux-theatre'),
        'footer' => __('Footer Menu', 'flux-theatre')
    ));

    // Add REST API support for menus
    add_action('rest_api_init', function() {
        register_rest_route('wp/v2', '/menus', array(
            'methods' => 'GET',
            'callback' => 'flux_get_menus',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('wp/v2', '/menus/(?P<location>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => 'flux_get_menu_by_location',
            'permission_callback' => '__return_true',
            'args' => array(
                'location' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Menu location (e.g., primary, footer)'
                )
            )
        ));
    });
}
add_action('init', 'flux_theatre_register_menus');

// Get all menus
function flux_get_menus() {
    $locations = get_nav_menu_locations();
    $menus = array();

    foreach ($locations as $location => $menu_id) {
        $menu = wp_get_nav_menu_object($menu_id);
        if ($menu) {
            $menus[$location] = array(
                'id' => $menu->term_id,
                'name' => $menu->name,
                'items' => flux_get_menu_items($menu_id)
            );
        }
    }

    return rest_ensure_response($menus);
}

// Get menu by location
function flux_get_menu_by_location($request) {
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
        'items' => flux_get_menu_items($menu_id)
    ));
}

// Get menu items
function flux_get_menu_items($menu_id) {
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

// Enqueue productions styles
function flux_theatre_enqueue_productions_styles() {
    if (is_page_template('page-productions.php')) {
        wp_enqueue_style(
            'flux-theatre-productions',
            get_template_directory_uri() . '/assets/css/productions.css',
            array(),
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'flux_theatre_enqueue_productions_styles');

/**
 * Set up the shows page as the productions archive
 */
function flux_theatre_setup_shows_page() {
    // Check if the shows page exists
    $shows_page = get_page_by_path('shows');
    
    if (!$shows_page) {
        // Create the shows page if it doesn't exist
        $page_data = array(
            'post_title'    => 'Shows',
            'post_name'     => 'shows',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_content'  => '[productions_timeline]'
        );
        
        $page_id = wp_insert_post($page_data);
        
        // Set this page as the productions archive
        update_option('page_for_productions', $page_id);
    } else {
        // If the page exists, make sure it's set as the productions archive
        update_option('page_for_productions', $shows_page->ID);
    }
}
add_action('init', 'flux_theatre_setup_shows_page');

/**
 * Add rewrite rules for the shows page
 */
function flux_theatre_add_rewrite_rules() {
    add_rewrite_rule(
        '^shows/?$',
        'index.php?post_type=production',
        'top'
    );
}
add_action('init', 'flux_theatre_add_rewrite_rules');

/**
 * Add shortcode for productions timeline
 */
function flux_theatre_productions_timeline_shortcode() {
    ob_start();
    
    $args = array(
        'post_type' => 'production',
        'posts_per_page' => -1,
        'meta_key' => '_performance_dates',
        'orderby' => 'meta_value',
        'order' => 'DESC'
    );
    
    $productions = new WP_Query($args);
    
    if ($productions->have_posts()) {
        echo '<div class="productions-timeline">';
        while ($productions->have_posts()) {
            $productions->the_post();
            ?>
            <article class="production">
                <div class="production-content">
                    <h2 class="production-title"><?php the_title(); ?></h2>
                    <div class="production-meta">
                        <?php
                        $dates = get_post_meta(get_the_ID(), '_performance_dates', true);
                        if (!empty($dates)) {
                            echo '<span class="performance-date">' . esc_html($dates[0]) . '</span>';
                        }
                        ?>
                    </div>
                </div>
            </article>
            <?php
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p class="no-productions">No productions found.</p>';
    }
    
    return ob_get_clean();
}
add_shortcode('productions_timeline', 'flux_theatre_productions_timeline_shortcode');

/**
 * Initialize theme customizer
 */
function flux_theatre_customizer_init() {
    // Only load customizer code in the customizer
    if (!is_customize_preview()) {
        return;
    }

    // Load the customizer class file
    require_once get_template_directory() . '/inc/class-flux-customize-carousel-control.php';

    // Add the customize register action
    add_action('customize_register', 'flux_theatre_customize_register');
}
add_action('init', 'flux_theatre_customizer_init');

/**
 * Add Hero Media Settings to the Customizer
 */
function flux_theatre_customize_register($wp_customize) {
    // Add Hero Media section
    $wp_customize->add_section('flux_hero_media', array(
        'title' => __('Hero Media', 'flux-theatre'),
        'priority' => 30,
    ));

    // Add Hero Media Type setting
    $wp_customize->add_setting('flux_hero_media_type', array(
        'default' => 'image',
        'transport' => 'postMessage',
        'sanitize_callback' => 'flux_sanitize_media_type',
    ));

    // Add Hero Media Type control
    $wp_customize->add_control('flux_hero_media_type', array(
        'label' => __('Media Type', 'flux-theatre'),
        'section' => 'flux_hero_media',
        'type' => 'select',
        'choices' => array(
            'image' => __('Image', 'flux-theatre'),
            'video' => __('Video', 'flux-theatre'),
            'carousel' => __('Carousel', 'flux-theatre'),
        ),
    ));

    // Add Hero Image setting
    $wp_customize->add_setting('flux_hero_image', array(
        'default' => '',
        'transport' => 'postMessage',
        'sanitize_callback' => 'absint',
    ));

    // Add Hero Image control
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'flux_hero_image', array(
        'label' => __('Hero Image', 'flux-theatre'),
        'section' => 'flux_hero_media',
        'mime_type' => 'image',
    )));

    // Add Hero Image Alt Text setting
    $wp_customize->add_setting('flux_hero_image_alt', array(
        'default' => '',
        'transport' => 'postMessage',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    // Add Hero Image Alt Text control
    $wp_customize->add_control('flux_hero_image_alt', array(
        'label' => __('Hero Image Alt Text', 'flux-theatre'),
        'section' => 'flux_hero_media',
        'type' => 'text',
    ));

    // Add Hero Video URL setting
    $wp_customize->add_setting('flux_hero_video_url', array(
        'default' => '',
        'transport' => 'postMessage',
        'sanitize_callback' => 'esc_url_raw',
    ));

    // Add Hero Video URL control
    $wp_customize->add_control('flux_hero_video_url', array(
        'label' => __('Hero Video URL', 'flux-theatre'),
        'section' => 'flux_hero_media',
        'type' => 'url',
        'description' => __('Enter a YouTube or Vimeo URL', 'flux-theatre'),
    ));

    // Add Carousel Images setting
    $wp_customize->add_setting('flux_hero_carousel_images', array(
        'default' => array(),
        'transport' => 'postMessage',
        'sanitize_callback' => 'flux_sanitize_carousel_images',
    ));

    // Add Carousel Images control
    $wp_customize->add_control(new Flux_Customize_Carousel_Control($wp_customize, 'flux_hero_carousel_images', array(
        'label' => __('Carousel Images', 'flux-theatre'),
        'section' => 'flux_hero_media',
        'description' => __('Add images to the carousel', 'flux-theatre'),
    )));

    // Add Carousel Autoplay setting
    $wp_customize->add_setting('flux_hero_carousel_autoplay', array(
        'default' => true,
        'transport' => 'postMessage',
        'sanitize_callback' => 'flux_sanitize_boolean',
    ));

    // Add Carousel Autoplay control
    $wp_customize->add_control('flux_hero_carousel_autoplay', array(
        'label' => __('Enable Autoplay', 'flux-theatre'),
        'section' => 'flux_hero_media',
        'type' => 'checkbox',
    ));

    // Add Carousel Interval setting
    $wp_customize->add_setting('flux_hero_carousel_interval', array(
        'default' => 5000,
        'transport' => 'postMessage',
        'sanitize_callback' => 'absint',
    ));

    // Add Carousel Interval control
    $wp_customize->add_control('flux_hero_carousel_interval', array(
        'label' => __('Autoplay Interval (ms)', 'flux-theatre'),
        'section' => 'flux_hero_media',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 1000,
            'step' => 500,
        ),
    ));
}

/**
 * Sanitize media type
 */
function flux_sanitize_media_type($input) {
    $valid = array('image', 'video', 'carousel');
    if (in_array($input, $valid)) {
        return $input;
    }
    return 'image';
}

/**
 * Sanitize carousel images
 */
function flux_sanitize_carousel_images($input) {
    if (!is_array($input)) {
        return array();
    }
    return array_map('absint', $input);
}

/**
 * Sanitize boolean
 */
function flux_sanitize_boolean($input) {
    return (bool) $input;
}

/**
 * Enqueue customizer scripts and styles
 */
function flux_theatre_customize_enqueue() {
    wp_enqueue_style(
        'flux-theatre-customizer',
        get_template_directory_uri() . '/css/customizer.css',
        array(),
        '1.0.0'
    );

    wp_enqueue_script(
        'flux-theatre-customizer',
        get_template_directory_uri() . '/js/customizer.js',
        array('jquery', 'customize-controls'),
        '1.0.0',
        true
    );

    wp_localize_script('flux-theatre-customizer', 'fluxCustomizer', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('flux_customizer_nonce'),
    ));
}

/**
 * AJAX handler for getting attachment URL
 */
function flux_get_attachment_url() {
    check_ajax_referer('flux_customizer_nonce', 'nonce');
    
    $attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id']) : 0;
    if (!$attachment_id) {
        wp_send_json_error();
    }
    
    $url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
    if (!$url) {
        wp_send_json_error();
    }
    
    wp_send_json_success(array('url' => $url));
}

// Add actions
add_action('after_setup_theme', 'flux_theatre_customizer_init');
add_action('customize_controls_enqueue_scripts', 'flux_theatre_customize_enqueue');
add_action('wp_ajax_flux_get_attachment_url', 'flux_get_attachment_url');

// Enable Contact Form 7 REST API
add_filter('wpcf7_rest_api_enabled', '__return_true');

// Handle CORS for Contact Form 7
add_action('rest_api_init', function() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        $origin = get_http_origin();
        if ($origin) {
            header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
        }
        return $value;
    });
}, 15); 