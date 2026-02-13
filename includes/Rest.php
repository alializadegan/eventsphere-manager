<?php
namespace WPEMCLI;

if ( ! defined('ABSPATH') ) exit;

final class Rest {
    public static function init(): void {
        add_action('rest_api_init', [__CLASS__, 'routes']);
    }

    public static function routes(): void {
        register_rest_route('wpemcli/v1', '/events/(?P<id>\d+)/rsvp', [
            'methods'  => 'POST',
            'callback' => [__CLASS__, 'rsvp'],
            'permission_callback' => '__return_true',
            'args' => [
                'name'  => ['type' => 'string', 'required' => false],
                'email' => ['type' => 'string', 'required' => true],
            ]
        ]);

        register_rest_route('wpemcli/v1', '/events/(?P<id>\d+)/rsvp-count', [
            'methods'  => 'GET',
            'callback' => [__CLASS__, 'rsvp_count'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function rsvp(\WP_REST_Request $req) {
        $event_id = absint($req['id']);
        if (!$event_id || get_post_type($event_id) !== PostTypes::CPT) {
            return new \WP_REST_Response(['message' => 'Invalid event'], 404);
        }

        $name  = sanitize_text_field((string) $req->get_param('name'));
        $email = sanitize_email((string) $req->get_param('email'));
        if (!$email || !is_email($email)) {
            return new \WP_REST_Response(['message' => 'Invalid email'], 400);
        }

        $user_id = get_current_user_id() ?: null;
        $ok = RSVP::add($event_id, $name, $email, $user_id);

        return new \WP_REST_Response([
            'success' => (bool) $ok,
            'count'   => RSVP::count_for_event($event_id),
        ], 200);
    }

    public static function rsvp_count(\WP_REST_Request $req) {
        $event_id = absint($req['id']);
        if (!$event_id || get_post_type($event_id) !== PostTypes::CPT) {
            return new \WP_REST_Response(['message' => 'Invalid event'], 404);
        }
        return new \WP_REST_Response(['count' => RSVP::count_for_event($event_id)], 200);
    }
}