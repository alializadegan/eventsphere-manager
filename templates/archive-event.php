<?php
if ( ! defined('ABSPATH') ) exit;
get_header();

include WPEMCLI_PATH . 'templates/partials/filters.php';

$type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';
$s    = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$from = isset($_GET['from']) ? \WPEMCLI\PostTypes::sanitize_date(wp_unslash($_GET['from'])) : '';
$to   = isset($_GET['to']) ? \WPEMCLI\PostTypes::sanitize_date(wp_unslash($_GET['to'])) : '';

$args = [
    'post_type'      => \WPEMCLI\PostTypes::CPT,
    'post_status'    => 'publish',
    'posts_per_page' => 10,
    'paged'          => max(1, get_query_var('paged')),
    's'              => $s,
    'meta_key'       => '_wpem_event_date',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
];

if ($type) {
    $args['tax_query'] = [[
        'taxonomy' => \WPEMCLI\PostTypes::TAX,
        'field'    => 'slug',
        'terms'    => $type,
    ]];
}
if ($from || $to) {
    $args['meta_query'] = [[
        'key'     => '_wpem_event_date',
        'value'   => [$from ?: '0000-01-01', $to ?: '9999-12-31'],
        'compare' => 'BETWEEN',
        'type'    => 'CHAR',
    ]];
}

$q = new WP_Query($args);

echo '<main class="wpemcli-archive" style="max-width:900px; margin:0 auto;">';
echo '<h1>' . esc_html__('Events', 'wp-event-manager-cli') . '</h1>';

if ($q->have_posts()) {
    while ($q->have_posts()) { $q->the_post();
        include WPEMCLI_PATH . 'templates/partials/event-card.php';
    }
    wp_reset_postdata();

    echo paginate_links([
        'total' => $q->max_num_pages
    ]);
} else {
    echo '<p>' . esc_html__('No events found.', 'wp-event-manager-cli') . '</p>';
}
echo '</main>';

get_footer();