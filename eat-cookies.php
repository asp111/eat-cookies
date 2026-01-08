<?php
/**
 * Plugin Name: Eat Cookies
 * Plugin URI: https://github.com/asp111/eat-cookies
 * Description: A lightweight standalone privacy cookie consent plugin for WordPress and WooCommerce.
 * Version: 1.1.1
 * Author: ashish
 * Author URI: https://ukwebservices.uk
 * License: GPL2
 * Text Domain: eat-cookies
 */

if (!defined('ABSPATH')) {
    exit;
}

define('EAT_COOKIES_VERSION', '1.1.1');
define('EAT_COOKIES_PATH', plugin_dir_path(__FILE__));
define('EAT_COOKIES_URL', plugin_dir_url(__FILE__));

require_once EAT_COOKIES_PATH . 'includes/class-eat-cookies.php';

function run_eat_cookies() {
    $plugin = new Eat_Cookies();
    $plugin->run();
}

/**
 * The code that runs during plugin activation.
 */
function activate_eat_cookies() {
    require_once EAT_COOKIES_PATH . 'includes/class-eat-cookies-database.php';
    Eat_Cookies_Database::create_tables();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_eat_cookies() {
}

register_activation_hook(__FILE__, 'activate_eat_cookies');
register_deactivation_hook(__FILE__, 'deactivate_eat_cookies');

run_eat_cookies();