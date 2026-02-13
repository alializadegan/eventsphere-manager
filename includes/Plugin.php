<?php
namespace WPEMCLI;

if ( ! defined('ABSPATH') ) exit;

final class Plugin {
    private static ?Plugin $instance = null;

    public static function instance(): Plugin {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'load_textdomain']);
        add_action('init', [PostTypes::class, 'register']);
        add_action('init', [RSVP::class, 'maybe_create_table'], 5);

        Admin::init();
        Settings::init();
        \WPEMCLI\Admin\RsvpsPage::init();
        Frontend::init();
        Notifications::init();
        Rest::init();

        if (defined('WP_CLI') && WP_CLI) {
            CLI::register();
        }

        register_activation_hook(WPEMCLI_PATH . 'wp-event-manager-cli.php', [$this, 'activate']);
        register_deactivation_hook(WPEMCLI_PATH . 'wp-event-manager-cli.php', [$this, 'deactivate']);
    }

    public function load_textdomain(): void {
        load_plugin_textdomain(
            'wp-event-manager-cli',
            false,
            dirname(plugin_basename(WPEMCLI_PATH . 'wp-event-manager-cli.php')) . '/languages'
        );
    }

    public function activate(): void {
        PostTypes::register();
        RSVP::maybe_create_table(true);
        flush_rewrite_rules();
    }

    public function deactivate(): void {
        flush_rewrite_rules();
    }
}