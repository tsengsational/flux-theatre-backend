<?php
/**
 * Cleanup script to delete all pages and posts
 */

// Only run if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Function to delete all posts of a specific type
function flux_delete_all_posts($post_type) {
    $posts = get_posts(array(
        'post_type' => $post_type,
        'numberposts' => -1,
        'post_status' => 'any'
    ));

    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }
}

// Delete all pages
flux_delete_all_posts('page');

// Delete all posts
flux_delete_all_posts('post');

// Delete all productions
flux_delete_all_posts('production');

// Clear the trash
$trash_posts = get_posts(array(
    'post_type' => array('post', 'page', 'production'),
    'numberposts' => -1,
    'post_status' => 'trash'
));

foreach ($trash_posts as $post) {
    wp_delete_post($post->ID, true);
}

echo "All pages, posts, and productions have been deleted."; 