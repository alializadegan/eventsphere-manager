<?php
namespace WPEMCLI;

if ( ! defined('ABSPATH') ) exit;

final class PostTypes {
    public const CPT = 'event';
    public const TAX = 'event_type';

    public static function register(): void {
        register_post_type(self::CPT, [
            'labels' => [
                'name'          => __('Events', 'wp-event-manager-cli'),
                'singular_name' => __('Event', 'wp-event-manager-cli'),
            ],
            'public'       => true,
            'has_archive'  => true,
            'rewrite'      => ['slug' => 'events'],
            'menu_icon'    => 'dashicons-calendar-alt',
            'supports'     => ['title', 'editor', 'thumbnail', 'excerpt'],
            'show_in_rest' => true,
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);

        register_taxonomy(self::TAX, [self::CPT], [
            'labels' => [
                'name'          => __('Event Types', 'wp-event-manager-cli'),
                'singular_name' => __('Event Type', 'wp-event-manager-cli'),
            ],
            'public'       => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite'      => ['slug' => 'event-type'],
        ]);

        register_post_meta(self::CPT, '_wpem_event_date', [
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'auth_callback'     => fn() => current_user_can('edit_posts'),
            'sanitize_callback' => [self::class, 'sanitize_date'],
        ]);

        register_post_meta(self::CPT, '_wpem_event_location', [
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'auth_callback'     => fn() => current_user_can('edit_posts'),
            'sanitize_callback' => 'sanitize_text_field',
        ]);
    }

    public static function sanitize_date($value): string {
        $value = is_string($value) ? trim($value) : '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) return '';
        return $value;
    }
}