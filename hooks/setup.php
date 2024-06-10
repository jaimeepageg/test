<?php

if (!function_exists('ceremonies_load_main_page')) {
    function ceremonies_load_main_page() {
        include(CER_RESOURCES_ROOT . 'templates/admin-page.php');
    }
}

if (!function_exists('ceremonies_load_account_portal')) {
    function ceremonies_load_account_portal() {
        include(CER_RESOURCES_ROOT . 'templates/account-portal.php');
    }
    add_shortcode('ceremony-account-portal', 'ceremonies_load_account_portal');
}

// add a link to the WP Toolbar
function wpb_custom_toolbar_link($wp_admin_bar) {
    $args = array(
        'id' => 'sc-admin',
        'title' => 'CAP - Admin',
        'href' => '/wp-admin/admin.php?page=ceremonies#/bookings',
        'meta'   => array(
            'target'   => '_self',
            'title'    => 'CAP - View Admin',
//            'html'     => '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 32 32" height="16px" width="16px" xmlns="http://www.w3.org/2000/svg"><path d="M 16 2 C 14.74 2 13.850156 2.89 13.410156 4 L 5 4 L 5 29 L 27 29 L 27 4 L 18.589844 4 C 18.149844 2.89 17.26 2 16 2 z M 16 4 C 16.55 4 17 4.45 17 5 L 17 6 L 20 6 L 20 8 L 12 8 L 12 6 L 15 6 L 15 5 C 15 4.45 15.45 4 16 4 z M 7 6 L 10 6 L 10 10 L 22 10 L 22 6 L 25 6 L 25 27 L 7 27 L 7 6 z M 9 13 L 9 15 L 11 15 L 11 13 L 9 13 z M 13 13 L 13 15 L 23 15 L 23 13 L 13 13 z M 9 17 L 9 19 L 11 19 L 11 17 L 9 17 z M 13 17 L 13 19 L 23 19 L 23 17 L 13 17 z M 9 21 L 9 23 L 11 23 L 11 21 L 9 21 z M 13 21 L 13 23 L 23 23 L 23 21 L 13 21 z"></path></svg>',
        ),
    );
    $wp_admin_bar->add_node($args);
}
add_action('admin_bar_menu', 'wpb_custom_toolbar_link', 999);

function sc_load_admin_bar_styles() {
    wp_add_inline_style('sc-admin-bar', '
        #wp-admin-bar-sc-admin {
            display: inline-flex;
            flex-direction: row-reverse;
            align-items: center;
        }
        #wp-admin-bar-sc-admin svg {
            margin-left: 1rem;
        }
    ');
}
add_action('wp_enqueue_scripts', 'sc_load_admin_bar_styles');

/**
 * Make the user role available to JS. Used by admin
 * react app.
 */
add_action('admin_enqueue_scripts', function() {
    $user = wp_get_current_user();
    $role = $user->roles[0];
    echo "<script>const sc_admin_user = { role: '$role' }</script>";
});

