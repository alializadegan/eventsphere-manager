<?php
namespace WPEMCLI;

if ( ! defined('ABSPATH') ) exit;

final class Settings {
    public const OPTION_KEY = 'wpemcli_settings';

    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_init', [__CLASS__, 'register']);
    }

    public static function menu(): void {
        add_submenu_page(
            'edit.php?post_type=' . PostTypes::CPT,
            __('Event Settings', 'wp-event-manager-cli'),
            __('Settings', 'wp-event-manager-cli'),
            'manage_options',
            'wpemcli-settings',
            [__CLASS__, 'render']
        );
    }

    public static function register(): void {
        register_setting(self::OPTION_KEY, self::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize'],
            'default' => [],
        ]);

        add_settings_section('wpemcli_main', __('General', 'wp-event-manager-cli'), '__return_false', self::OPTION_KEY);

        add_settings_field('events_per_page', __('Events per page', 'wp-event-manager-cli'), [__CLASS__, 'field_events_per_page'], self::OPTION_KEY, 'wpemcli_main');
        add_settings_field('enable_notifications', __('Email notifications', 'wp-event-manager-cli'), [__CLASS__, 'field_enable_notifications'], self::OPTION_KEY, 'wpemcli_main');
        add_settings_field('default_locations', __('Default locations (suggestions)', 'wp-event-manager-cli'), [__CLASS__, 'field_default_locations'], self::OPTION_KEY, 'wpemcli_main');
    }

    public static function sanitize($value): array {
        $value = is_array($value) ? $value : [];
        $out = [];

        $out['events_per_page'] = isset($value['events_per_page']) ? max(1, min(60, (int) $value['events_per_page'])) : 9;
        $out['enable_notifications'] = !empty($value['enable_notifications']) ? 1 : 0;

        $loc = isset($value['default_locations']) ? (string) $value['default_locations'] : '';
        $lines = preg_split('/\r\n|\r|\n/', $loc);
        $lines = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array)$lines))));
        $out['default_locations'] = implode("\n", $lines);

        return $out;
    }

    public static function get(): array {
        $opt = get_option(self::OPTION_KEY, []);
        return is_array($opt) ? $opt : [];
    }

    public static function get_int(string $key, int $default): int {
        $opt = self::get();
        return isset($opt[$key]) ? (int) $opt[$key] : $default;
    }

    public static function get_bool(string $key, bool $default): bool {
        $opt = self::get();
        if (!isset($opt[$key])) return $default;
        return (bool) $opt[$key];
    }

    public static function get_locations(): array {
        $opt = self::get();
        if (empty($opt['default_locations'])) return [];
        $lines = preg_split('/\r\n|\r|\n/', (string)$opt['default_locations']);
        $lines = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array)$lines))));
        return $lines;
    }

    public static function field_events_per_page(): void {
        $opt = self::get();
        $val = isset($opt['events_per_page']) ? (int)$opt['events_per_page'] : 9;
        echo '<input type="number" min="1" max="60" name="' . esc_attr(self::OPTION_KEY) . '[events_per_page]" value="' . esc_attr($val) . '" />';
        echo '<p class="description">' . esc_html__('Used for archive + shortcode list.', 'wp-event-manager-cli') . '</p>';
    }

    public static function field_enable_notifications(): void {
        $opt = self::get();
        $val = !empty($opt['enable_notifications']) ? 1 : 0;
        echo '<label><input type="checkbox" name="' . esc_attr(self::OPTION_KEY) . '[enable_notifications]" value="1" ' . checked(1, $val, false) . ' /> ';
        echo esc_html__('Send emails on publish/update', 'wp-event-manager-cli') . '</label>';
    }

    public static function field_default_locations(): void {
        $opt = self::get();
        $val = isset($opt['default_locations']) ? (string)$opt['default_locations'] : '';
        echo '<textarea rows="8" style="width:420px;max-width:100%;" name="' . esc_attr(self::OPTION_KEY) . '[default_locations]">' . esc_textarea($val) . '</textarea>';
        echo '<p class="description">' . esc_html__('One location per line. Used as admin suggestions.', 'wp-event-manager-cli') . '</p>';
    }

    public static function render(): void {
        if (!current_user_can('manage_options')) return;
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Event Settings', 'wp-event-manager-cli') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields(self::OPTION_KEY);
        do_settings_sections(self::OPTION_KEY);
        submit_button();
        echo '</form>';
        echo '</div>';
    }
}