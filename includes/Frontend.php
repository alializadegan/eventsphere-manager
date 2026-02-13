<?php
namespace WPEMCLI;

if ( ! defined('ABSPATH') ) exit;

final class Frontend {

    public static function init(): void {
        add_filter('template_include', [__CLASS__, 'template_loader']);
        add_shortcode('event_list', [__CLASS__, 'shortcode_event_list']);
        add_action('init', [__CLASS__, 'handle_rsvp_form']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_frontend_assets']);

        add_action('wp_ajax_wpemcli_filter', [__CLASS__, 'ajax_filter']);
        add_action('wp_ajax_nopriv_wpemcli_filter', [__CLASS__, 'ajax_filter']);
    }

    public static function enqueue_frontend_assets(): void {
        $should = is_singular(PostTypes::CPT) || is_post_type_archive(PostTypes::CPT);

        if (!$should) {
            global $post;
            if ($post instanceof \WP_Post && has_shortcode($post->post_content, 'event_list')) $should = true;
        }

        if (!$should) return;

        wp_enqueue_style('wpemcli-frontend', WPEMCLI_URL . 'assets/frontend.css', [], WPEMCLI_VERSION);

        wp_enqueue_script('wpemcli-frontend', WPEMCLI_URL . 'assets/frontend.js', [], WPEMCLI_VERSION, true);
        wp_localize_script('wpemcli-frontend', 'WPEMCLI_FRONTEND', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wpemcli_ajax'),
        ]);
    }

    public static function template_loader(string $template): string {
        if (is_singular(PostTypes::CPT)) {
            $override = locate_template('single-event.php');
            if ($override) return $override;
            $f = WPEMCLI_PATH . 'templates/single-event.php';
            if (is_readable($f)) return $f;
        }

        if (is_post_type_archive(PostTypes::CPT)) {
            $override = locate_template('archive-event.php');
            if ($override) return $override;
            $f = WPEMCLI_PATH . 'templates/archive-event.php';
            if (is_readable($f)) return $f;
        }

        return $template;
    }

    public static function event_status(int $event_id): string {
        $date = get_post_meta($event_id, '_wpem_event_date', true);
        if (!$date) return 'upcoming';

        $today = current_time('Y-m-d');
        if ($date < $today) return 'past';
        return 'upcoming';
    }

    public static function get_filters_from_request(): array {
        $type   = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';
        $s      = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $from   = isset($_GET['from']) ? PostTypes::sanitize_date(wp_unslash($_GET['from'])) : '';
        $to     = isset($_GET['to']) ? PostTypes::sanitize_date(wp_unslash($_GET['to'])) : '';
        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'upcoming';
        $sort   = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : 'date_asc';

        $allowed_status = ['upcoming','past','all'];
        if (!in_array($status, $allowed_status, true)) $status = 'upcoming';

        $allowed_sort = ['date_asc','date_desc','title_asc'];
        if (!in_array($sort, $allowed_sort, true)) $sort = 'date_asc';

        return ['type'=>$type,'s'=>$s,'from'=>$from,'to'=>$to,'status'=>$status,'sort'=>$sort];
    }

    private static function per_page_default(): int {
        return max(1, Settings::get_int('events_per_page', 9));
    }

    public static function build_query_args(array $filters, int $per_page, int $paged): array {
        $args = [
            'post_type'      => PostTypes::CPT,
            'post_status'    => 'publish',
            'posts_per_page' => max(1, $per_page),
            'paged'          => max(1, $paged),
            's'              => $filters['s'] ?? '',
        ];

        $sort = $filters['sort'] ?? 'date_asc';
        if ($sort === 'title_asc') {
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
        } else {
            $args['meta_key'] = '_wpem_event_date';
            $args['orderby']  = 'meta_value';
            $args['order']    = ($sort === 'date_desc') ? 'DESC' : 'ASC';
        }

        if (!empty($filters['type'])) {
            $args['tax_query'] = [[
                'taxonomy' => PostTypes::TAX,
                'field'    => 'slug',
                'terms'    => $filters['type'],
            ]];
        }

        $meta_query = [];

        $from = $filters['from'] ?? '';
        $to   = $filters['to'] ?? '';
        if ($from || $to) {
            $meta_query[] = [
                'key'     => '_wpem_event_date',
                'value'   => [$from ?: '0000-01-01', $to ?: '9999-12-31'],
                'compare' => 'BETWEEN',
                'type'    => 'CHAR',
            ];
        }

        $status = $filters['status'] ?? 'upcoming';
        $today = current_time('Y-m-d');
        if ($status === 'upcoming') {
            $meta_query[] = ['key'=>'_wpem_event_date','value'=>$today,'compare'=>'>=','type'=>'CHAR'];
        } elseif ($status === 'past') {
            $meta_query[] = ['key'=>'_wpem_event_date','value'=>$today,'compare'=>'<','type'=>'CHAR'];
        }

        if (!empty($meta_query)) $args['meta_query'] = $meta_query;

        return $args;
    }

    private static function pagination_html(int $paged, int $max_pages): string {
        $current_url = remove_query_arg('paged');
        $links = paginate_links([
            'base'      => esc_url_raw(add_query_arg('paged', '%#%', $current_url)),
            'format'    => '',
            'current'   => max(1, $paged),
            'total'     => max(1, $max_pages),
            'type'      => 'array',
            'prev_text' => __('Previous', 'wp-event-manager-cli'),
            'next_text' => __('Next', 'wp-event-manager-cli'),
        ]);

        if (!is_array($links)) return '';
        $out = '<nav class="wpemcli-pagination" aria-label="Pagination"><ul>';
        foreach ($links as $link) $out .= '<li>' . $link . '</li>';
        $out .= '</ul></nav>';
        return $out;
    }

    private static function results_html(\WP_Query $q, int $paged): string {
        ob_start();

        if ($q->have_posts()) {
            echo '<div class="wpemcli-grid">';
            while ($q->have_posts()) {
                $q->the_post();
                include WPEMCLI_PATH . 'templates/partials/event-card.php';
            }
            echo '</div>';
            wp_reset_postdata();

            echo self::pagination_html($paged, (int)$q->max_num_pages);
        } else {
            echo '<div class="wpemcli-empty"><h3>' . esc_html__('No events found', 'wp-event-manager-cli') . '</h3><p>' . esc_html__('Try adjusting your filters.', 'wp-event-manager-cli') . '</p></div>';
        }

        return (string) ob_get_clean();
    }

    public static function render_event_list(array $opts = []): string {
        $opts = wp_parse_args($opts, [
            'per_page' => 0,
            'paged'    => 0,
            'show_filters' => true,
            'cache'    => true,
        ]);

        $filters = self::get_filters_from_request();

        $paged = (int) $opts['paged'];
        if ($paged <= 0) {
            $paged = (int) get_query_var('paged');
            if ($paged <= 0 && isset($_GET['paged'])) $paged = (int) absint($_GET['paged']);
            if ($paged <= 0) $paged = 1;
        }

        $per_page = (int) $opts['per_page'];
        if ($per_page <= 0) $per_page = self::per_page_default();

        $cache_key = 'wpemcli_list_' . md5(wp_json_encode([$filters, $paged, $per_page]));
        if (!empty($opts['cache'])) {
            $cached = get_transient($cache_key);
            if ($cached !== false) return $cached;
        }

        $q = new \WP_Query(self::build_query_args($filters, $per_page, $paged));

        ob_start();
        echo '<div class="wpemcli-container">';
        if (!empty($opts['show_filters'])) {
            $wpemcli_filters = $filters;
            include WPEMCLI_PATH . 'templates/partials/filters.php';
        }

        echo '<div data-wpemcli-results>';
        echo self::results_html($q, $paged);
        echo '</div>';

        echo '</div>';

        $html = (string) ob_get_clean();
        if (!empty($opts['cache'])) set_transient($cache_key, $html, MINUTE_IN_SECONDS * 5);
        return $html;
    }

    public static function shortcode_event_list(array $atts): string {
        $atts = shortcode_atts(['per_page' => 0], $atts, 'event_list');
        return self::render_event_list(['per_page' => (int)$atts['per_page'], 'show_filters' => true, 'cache' => true]);
    }

    public static function ajax_filter(): void {
        if (!check_ajax_referer('wpemcli_ajax', 'nonce', false)) {
            wp_send_json_error('bad_nonce', 403);
        }

        $params = isset($_POST['params']) ? (string) wp_unslash($_POST['params']) : '';
        parse_str($params, $get);
        $get = is_array($get) ? $get : [];

        $old_get = $_GET;
        $_GET = array_map('wp_unslash', $get);

        $filters = self::get_filters_from_request();
        $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        if ($paged <= 0) $paged = 1;

        $per_page = self::per_page_default();
        $q = new \WP_Query(self::build_query_args($filters, $per_page, $paged));
        $html = self::results_html($q, $paged);

        $_GET = $old_get;

        wp_send_json_success($html);
    }

    public static function flush_cache(): void {
    }

    public static function handle_rsvp_form(): void {
        if (!isset($_POST['wpem_rsvp_submit'])) return;
        if (!isset($_POST['wpem_rsvp_nonce']) || !wp_verify_nonce($_POST['wpem_rsvp_nonce'], 'wpem_rsvp')) return;

        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        if (!$event_id || get_post_type($event_id) !== PostTypes::CPT) return;

        $name  = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
$email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

$result = RSVP::add_with_status($event_id, $name, $email, (get_current_user_id() ?: null));

if ($result === 'added') {
    wp_safe_redirect(add_query_arg('rsvp', 'success', get_permalink($event_id)));
    exit;
}

if ($result === 'exists') {
    wp_safe_redirect(add_query_arg('rsvp', 'exists', get_permalink($event_id)));
    exit;
}

if ($result === 'invalid_email') {
    wp_safe_redirect(add_query_arg('rsvp', 'invalid_email', get_permalink($event_id)));
    exit;
}

wp_safe_redirect(add_query_arg('rsvp', 'error', get_permalink($event_id)));
exit;
    }
}