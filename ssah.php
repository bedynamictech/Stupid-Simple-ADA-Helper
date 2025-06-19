<?php
/**
 * Plugin Name: Stupid Simple ADA Helper
 * Description: Helps add common accessibility features like skip links, alt tag enforcement, and ARIA landmarks.
 * Version: 1.2
 * Author: Dynamic Technologies
 * Author URI: https://bedynamic.tech
 * Plugin URI: https://github.com/bedynamictech/Stupid-Simple-ADA-Helper
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Add ADA Helper submenu and sections
add_action('admin_menu', 'ssah_add_menu');

function ssah_add_menu() {
    global $menu;
    $parent_exists = false;
    foreach ($menu as $item) {
        if (!empty($item[2]) && $item[2] === 'stupidsimple') {
            $parent_exists = true;
            break;
        }
    }

    if (!$parent_exists) {
        add_menu_page(
            'Stupid Simple',
            'Stupid Simple',
            'manage_options',
            'stupidsimple',
            '__return_null',
            'dashicons-hammer',
            99
        );
    }

    add_submenu_page(
        'stupidsimple',
        'ADA Helper',
        'ADA Helper',
        'manage_options',
        'ssah-settings',
        'ssah_settings_page_content'
    );
}

// Add Settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ssah_action_links');
function ssah_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=ssah-settings') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Settings Page Content
function ssah_settings_page_content() {
    echo '<div class="wrap">';
    echo '<h2>Stupid Simple ADA Helper</h2>';
    echo '<p>This plugin helps add common accessibility features like skip links, alt tag enforcement, and ARIA landmarks.</p>';
    echo '<p>All features are automatically applied where possible.</p>';

    // Inline: List Images Missing Alt Text
    echo '<h3>Images Missing Alt Text</h3>';
    $args = array(
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_wp_attachment_image_alt',
                'compare' => 'NOT EXISTS'
            )
        )
    );

    $images = get_posts($args);

    if ($images) {
        echo '<ul>';
        foreach ($images as $image) {
            $edit_url = get_edit_post_link($image->ID);
            echo '<li><a href="' . esc_url($edit_url) . '" target="_blank">' . esc_html($image->post_title ?: 'Untitled Image (ID: ' . $image->ID . ')') . '</a></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>Great job! All images have alt text.</p>';
    }

    echo '</div>';
}

// Inject Skip Link
add_action('wp_body_open', 'ssah_add_skip_link');
function ssah_add_skip_link() {
    echo '<a href="#main-content" class="screen-reader-text" style="position:absolute;top:-40px;left:0;background:#000;color:#fff;padding:8px;z-index:1000;">Skip to main content</a>';
}

// Add ARIA landmarks to body classes for targeting in themes
add_filter('body_class', 'ssah_add_aria_roles');
function ssah_add_aria_roles($classes) {
    if (!is_admin()) {
        $classes[] = 'role-main';
    }
    return $classes;
}

// Add focus outline styles
add_action('wp_head', 'ssah_add_focus_styles');
function ssah_add_focus_styles() {
    echo '<style>button:focus, a:focus, input:focus, textarea:focus { outline: 2px solid #005fcc; outline-offset: 2px; }</style>';
}
