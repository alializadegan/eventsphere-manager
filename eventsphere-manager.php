<?php
/**
 * Plugin Name: EventSphere Manager
 * Description: Custom Event CPT + taxonomy, templates, filtering, RSVP, notifications, REST API, and WP-CLI commands.
 * Version: 1.1.1
 * Author: Ali Alizadegan
 * Text Domain: wp-event-manager-cli
 * Domain Path: /languages
 */

if ( ! defined('ABSPATH') ) exit;

define('WPEMCLI_VERSION', '1.1.1');
define('WPEMCLI_PATH', plugin_dir_path(__FILE__));
define('WPEMCLI_URL', plugin_dir_url(__FILE__));

require_once WPEMCLI_PATH . 'includes/Autoloader.php';
\WPEMCLI\Autoloader::init();

add_action('plugins_loaded', function () {
    \WPEMCLI\Plugin::instance();
});
