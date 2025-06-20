<?php
/**
 * Plugin Name: Stupid Simple ADA Helper
 * Description: Helps your WordPress site move toward ADA compliance by injecting accessibility enhancements like skip links, ARIA roles, and alt tag checks.
 * Version: 1.3
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

// AJAX handler for saving alt text
add_action('wp_ajax_ssah_save_alt_text', 'ssah_save_alt_text');
function ssah_save_alt_text() {
    if (current_user_can('edit_posts') && isset($_POST['id']) && isset($_POST['alt'])) {
        update_post_meta(intval($_POST['id']), '_wp_attachment_image_alt', sanitize_text_field($_POST['alt']));
        wp_send_json_success();
    }
    wp_send_json_error();
}

// Settings Page Content
function ssah_settings_page_content() {
    echo '<div class="wrap">';
    echo '<h2>Stupid Simple ADA Helper</h2>';
    echo '<p>Helps add common accessibility features like skip links, alt tag enforcement, and ARIA landmarks.</p>';
    echo '<p>All features are automatically applied where possible.</p>';

    // Inline: List Images Missing Alt Text with inline editor
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
        echo '<ul id="ssah-alt-list">';
        foreach ($images as $image) {
            $thumb = wp_get_attachment_image($image->ID, array(64, 64), true);
            echo '<li data-id="' . esc_attr($image->ID) . '">' .
                 $thumb .
                 ' <strong>' . esc_html($image->post_title ?: 'Untitled Image') . '</strong><br>' .
                 '<input type="text" placeholder="Enter alt text">' .
                 ' <button class="button button-primary">Save</button>' .
                 '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>Great job! All images have alt text.</p>';
    }

    echo '</div>';

    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll("#ssah-alt-list button").forEach(function(btn) {
            btn.addEventListener("click", function() {
                var li = btn.closest("li");
                var id = li.getAttribute("data-id");
                var alt = li.querySelector("input").value;
                var data = new FormData();
                data.append("action", "ssah_save_alt_text");
                data.append("id", id);
                data.append("alt", alt);

                fetch(ajaxurl, {
                    method: "POST",
                    credentials: "same-origin",
                    body: data
                }).then(res => res.json()).then(json => {
                    if (json.success) {
                        li.style.opacity = 0.5;
                        btn.textContent = "Saved";
                    } else {
                        btn.textContent = "Error";
                    }
                });
            });
        });
    });
    </script>';
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
