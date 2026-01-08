<?php

if (!defined('ABSPATH')) {
    exit;
}

class Eat_Cookies {
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once EAT_COOKIES_PATH . 'includes/class-eat-cookies-database.php';
        require_once EAT_COOKIES_PATH . 'admin/class-eat-cookies-admin.php';
        require_once EAT_COOKIES_PATH . 'includes/class-eat-cookies-public.php';
    }

    private function define_admin_hooks() {
        $admin = new Eat_Cookies_Admin();
        add_action('admin_menu', [$admin, 'add_plugin_admin_menu']);
        add_action('admin_init', [$admin, 'register_settings']);
        add_action('admin_enqueue_scripts', [$admin, 'enqueue_styles']);
        add_action('wp_ajax_eat_cookies_scan', [$admin, 'ajax_scan_cookies']);
        add_action('wp_ajax_eat_cookies_admin_scan', [$admin, 'ajax_admin_scan']);
    }

    private function define_public_hooks() {
        $public = new Eat_Cookies_Public();
        add_action('wp_enqueue_scripts', [$public, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$public, 'enqueue_scripts']);
        add_action('wp_head', [$public, 'render_consent_scripts']);
        add_action('wp_footer', [$public, 'render_cookie_popup']);
        add_action('wp_ajax_eat_cookies_save_consent', [$public, 'save_consent']);
        add_action('wp_ajax_nopriv_eat_cookies_save_consent', [$public, 'save_consent']);
    }

    public function run() {
        // Initialization if needed
    }
}
