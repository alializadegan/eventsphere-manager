<?php
namespace WPEMCLI;

if ( ! defined('ABSPATH') ) exit;

final class Notifications {
    public static function init(): void {
        add_action('transition_post_status', [__CLASS__, 'on_transition'], 10, 3);
        add_action('post_updated', [__CLASS__, 'on_updated'], 10, 3);
    }

    private static function enabled(): bool {
        return Settings::get_bool('enable_notifications', true);
    }

    public static function on_transition(string $new, string $old, \WP_Post $post): void {
        if (!self::enabled()) return;
        if ($post->post_type !== PostTypes::CPT) return;
        if ($old === 'publish' || $new !== 'publish') return;

        self::send_mail($post, 'published');
    }

    public static function on_updated(int $post_id, \WP_Post $post_after, \WP_Post $post_before): void {
        if (!self::enabled()) return;
        if ($post_after->post_type !== PostTypes::CPT) return;
        if ($post_after->post_status !== 'publish') return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        self::send_mail($post_after, 'updated');
    }

    private static function recipients(string $type, \WP_Post $post): array {
        $emails = [];

        $admin = get_option('admin_email');
        if ($admin && is_email($admin)) $emails[] = $admin;

        $subs = get_users([
            'role__in' => ['subscriber'],
            'fields'   => ['user_email'],
            'number'   => 500,
        ]);
        foreach ((array) $subs as $u) {
            $e = sanitize_email($u->user_email ?? '');
            if ($e && is_email($e)) $emails[] = $e;
        }

        if ($type === 'updated') {
            $emails = array_merge($emails, RSVP::emails_for_event((int) $post->ID));
        }

        $emails = array_values(array_unique(array_filter($emails)));

        return apply_filters('wpemcli_notification_recipients', $emails, $type, $post);
    }

    private static function send_mail(\WP_Post $post, string $type): void {
        $to = self::recipients($type, $post);
        if (!$to) return;

        $title = get_the_title($post);
        $link  = get_permalink($post);

        $subject = ($type === 'published')
            ? sprintf(__('New Event Published: %s', 'wp-event-manager-cli'), $title)
            : sprintf(__('Event Updated: %s', 'wp-event-manager-cli'), $title);

        $body = sprintf(
            __("Event: %s\nLink: %s\n", 'wp-event-manager-cli'),
            $title,
            $link
        );

        wp_mail($to, $subject, $body);
    }
}