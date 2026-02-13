<?php
namespace WPEMCLI\Admin;

use WPEMCLI\PostTypes;
use WPEMCLI\RSVP;

if ( ! defined('ABSPATH') ) exit;

final class RsvpsPage {

    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_post_wpemcli_export_rsvps', [__CLASS__, 'export_csv']);
    }

    public static function menu(): void {
        add_submenu_page(
            'edit.php?post_type=' . PostTypes::CPT,
            __('RSVPs', 'wp-event-manager-cli'),
            __('RSVPs', 'wp-event-manager-cli'),
            'edit_posts',
            'wpemcli-rsvps',
            [__CLASS__, 'render']
        );
    }

    public static function render(): void {
        if (!current_user_can('edit_posts')) return;

        $event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
        $search   = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $paged    = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $per_page = 20;

        $data  = RSVP::admin_list($event_id, $search, $paged, $per_page);
        $rows  = $data['rows'] ?? [];
        $total = (int) ($data['total'] ?? 0);

        $export_url = wp_nonce_url(
            admin_url('admin-post.php?action=wpemcli_export_rsvps&event_id=' . $event_id . '&s=' . rawurlencode($search)),
            'wpemcli_export_rsvps'
        );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('RSVPs', 'wp-event-manager-cli') . '</h1>';

        echo '<form method="get" style="margin:10px 0;">';
        echo '<input type="hidden" name="post_type" value="' . esc_attr(PostTypes::CPT) . '">';
        echo '<input type="hidden" name="page" value="wpemcli-rsvps">';

        echo '<label style="margin-right:8px;">' . esc_html__('Event ID', 'wp-event-manager-cli') . ':</label>';
        echo '<input type="number" name="event_id" value="' . esc_attr($event_id) . '" style="width:120px;margin-right:12px;">';

        echo '<input type="search" name="s" value="' . esc_attr($search) . '" placeholder="' . esc_attr__('Search email/name', 'wp-event-manager-cli') . '" style="width:280px;margin-right:8px;">';
        submit_button(__('Filter', 'wp-event-manager-cli'), 'secondary', '', false);

        echo ' <a class="button" href="' . esc_url($export_url) . '">' . esc_html__('Export CSV', 'wp-event-manager-cli') . '</a>';
        echo '</form>';

        echo '<p style="margin:10px 0 12px;">' . esc_html(sprintf(__('Total RSVPs: %d', 'wp-event-manager-cli'), $total)) . '</p>';

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Name', 'wp-event-manager-cli') . '</th>';
        echo '<th>' . esc_html__('Email', 'wp-event-manager-cli') . '</th>';
        echo '<th>' . esc_html__('Event', 'wp-event-manager-cli') . '</th>';
        echo '<th>' . esc_html__('Date', 'wp-event-manager-cli') . '</th>';
        echo '</tr></thead><tbody>';

        if (!empty($rows)) {
            foreach ($rows as $r) {
                $eid = isset($r['event_id']) ? (int)$r['event_id'] : 0;
                $event_title = $eid ? get_the_title($eid) : '';
                $event_link  = $eid ? get_edit_post_link($eid) : '';

                echo '<tr>';
                echo '<td>' . esc_html($r['name'] ?? '') . '</td>';
                echo '<td>' . esc_html($r['email'] ?? '') . '</td>';
                echo '<td>' . ($event_link ? '<a href="' . esc_url($event_link) . '">' . esc_html($event_title) . '</a>' : '-') . '</td>';
                echo '<td>' . esc_html($r['created_at'] ?? '') . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="4">' . esc_html__('No RSVPs found.', 'wp-event-manager-cli') . '</td></tr>';
        }

        echo '</tbody></table>';

        $total_pages = max(1, (int) ceil($total / $per_page));
        if ($total_pages > 1) {
            $base = add_query_arg([
                'post_type' => PostTypes::CPT,
                'page'      => 'wpemcli-rsvps',
                'event_id'  => $event_id,
                's'         => $search,
                'paged'     => '%#%',
            ], admin_url('edit.php'));

            echo '<div style="margin-top:12px;">' . paginate_links([
                'base'    => $base,
                'format'  => '',
                'current' => $paged,
                'total'   => $total_pages,
            ]) . '</div>';
        }

        echo '</div>';
    }

    public static function export_csv(): void {
        if (!current_user_can('edit_posts')) wp_die('Forbidden');
        check_admin_referer('wpemcli_export_rsvps');

        $event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
        $search   = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

        $data = RSVP::admin_list($event_id, $search, 1, 5000);
        $rows = $data['rows'] ?? [];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=wpemcli-rsvps.csv');

        $out = fopen('php:
        fputcsv($out, ['name','email','event_id','created_at']);

        foreach ((array)$rows as $r) {
            fputcsv($out, [
                $r['name'] ?? '',
                $r['email'] ?? '',
                $r['event_id'] ?? '',
                $r['created_at'] ?? '',
            ]);
        }

        fclose($out);
        exit;
    }
}