<?php
namespace WPEMCLI;

if ( ! defined('ABSPATH') ) exit;

final class Admin {
    public static function init(): void {
        add_action('add_meta_boxes', [__CLASS__, 'metaboxes']);
        add_action('save_post_' . PostTypes::CPT, [__CLASS__, 'save_meta'], 10, 2);

        add_filter('manage_' . PostTypes::CPT . '_posts_columns', [__CLASS__, 'columns']);
        add_action('manage_' . PostTypes::CPT . '_posts_custom_column', [__CLASS__, 'column_value'], 10, 2);
        add_filter('manage_edit-' . PostTypes::CPT . '_sortable_columns', [__CLASS__, 'sortable_columns']);
        add_action('pre_get_posts', [__CLASS__, 'admin_orderby']);

        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        add_action('admin_notices', [__CLASS__, 'notices']);

        add_action('save_post_' . PostTypes::CPT, [__CLASS__, 'enforce_featured_image_after_save'], 20, 3);

}

    public static function enqueue_admin_assets(string $hook): void {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== PostTypes::CPT) return;

        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('jquery-ui-autocomplete');

        wp_enqueue_style('wpemcli-jquery-ui-datepicker', WPEMCLI_URL . 'assets/jquery-ui-datepicker.css', [], WPEMCLI_VERSION);
        wp_enqueue_style('wpemcli-admin', WPEMCLI_URL . 'assets/admin.css', ['wpemcli-jquery-ui-datepicker'], WPEMCLI_VERSION);

        wp_enqueue_script('wpemcli-admin', WPEMCLI_URL . 'assets/admin.js', ['jquery','jquery-ui-datepicker','jquery-ui-autocomplete'], WPEMCLI_VERSION, true);

        wp_localize_script('wpemcli-admin', 'WPEMCLI_ADMIN', [
            'locations' => array_values(array_unique(array_filter(array_map('strval', (array) self::default_locations())))),
        ]);
    }

    public static function default_locations(): array {
        $from_settings = Settings::get_locations();
        if (!empty($from_settings)) return $from_settings;

        $defaults = ['Istanbul', 'Tehran', 'Dubai', 'Berlin', 'London', 'New York'];
        return apply_filters('wpemcli_default_locations', $defaults);
    }

    public static function metaboxes(): void {
        add_meta_box('wpem_event_details', __('Event Details', 'wp-event-manager-cli'), [__CLASS__, 'metabox_render'], PostTypes::CPT, 'normal', 'high');
        add_meta_box('wpem_event_rsvp', __('RSVP Summary', 'wp-event-manager-cli'), [__CLASS__, 'metabox_rsvp'], PostTypes::CPT, 'side', 'default');
    }

    public static function metabox_render(\WP_Post $post): void {
        wp_nonce_field('wpem_save_event_meta', 'wpem_event_meta_nonce');

        $date = get_post_meta($post->ID, '_wpem_event_date', true);
        $loc  = get_post_meta($post->ID, '_wpem_event_location', true);
        $locations = self::default_locations();
        ?>
        <p>
            <label for="wpem_event_date"><strong><?php esc_html_e('Event Date', 'wp-event-manager-cli'); ?></strong></label><br/>
            <input type="text" id="wpem_event_date" name="wpem_event_date" value="<?php echo esc_attr($date); ?>" class="regular-text" placeholder="YYYY-MM-DD" autocomplete="off" />
        </p>

        <p>
            <label for="wpem_event_location"><strong><?php esc_html_e('Location', 'wp-event-manager-cli'); ?></strong></label><br/>
            <input type="text" id="wpem_event_location" name="wpem_event_location" value="<?php echo esc_attr($loc); ?>" class="regular-text" autocomplete="off" />
<br/>
            <span class="description"><?php esc_html_e('Tip: start typing to see suggestions. Manage list in Events → Settings.', 'wp-event-manager-cli'); ?></span>
        </p>
        <?php
    }

    public static function metabox_rsvp(\WP_Post $post): void {
        $count = RSVP::count_for_event((int) $post->ID);
        echo '<p>' . esc_html(sprintf(__('Total RSVPs: %d', 'wp-event-manager-cli'), $count)) . '</p>';

        $url = admin_url('edit.php?post_type=' . PostTypes::CPT . '&page=wpemcli-rsvps&event_id=' . (int) $post->ID);
        echo '<p><a class="button button-secondary" href="' . esc_url($url) . '">' . esc_html__('View RSVPs', 'wp-event-manager-cli') . '</a></p>';
    }


    public static function save_meta(int $post_id, \WP_Post $post): void {
        if (!isset($_POST['wpem_event_meta_nonce']) || !wp_verify_nonce($_POST['wpem_event_meta_nonce'], 'wpem_save_event_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $date = isset($_POST['wpem_event_date']) ? PostTypes::sanitize_date(wp_unslash($_POST['wpem_event_date'])) : '';
        $loc  = isset($_POST['wpem_event_location']) ? sanitize_text_field(wp_unslash($_POST['wpem_event_location'])) : '';

        update_post_meta($post_id, '_wpem_event_date', $date);
        update_post_meta($post_id, '_wpem_event_location', $loc);

        Frontend::flush_cache();
    }

    public static function columns(array $cols): array {
        $cols['wpem_date'] = __('Date', 'wp-event-manager-cli');
        $cols['wpem_loc']  = __('Location', 'wp-event-manager-cli');
        $cols['wpem_status'] = __('Status', 'wp-event-manager-cli');
        $cols['wpem_rsvp'] = __('RSVPs', 'wp-event-manager-cli');
        return $cols;
    }

    public static function column_value(string $col, int $post_id): void {
        if ($col === 'wpem_date') echo esc_html(get_post_meta($post_id, '_wpem_event_date', true));
        elseif ($col === 'wpem_loc') echo esc_html(get_post_meta($post_id, '_wpem_event_location', true));
        elseif ($col === 'wpem_status') echo esc_html(ucfirst(Frontend::event_status($post_id)));
        elseif ($col === 'wpem_rsvp') echo esc_html((string) RSVP::count_for_event($post_id));
    }

    public static function sortable_columns(array $cols): array {
        $cols['wpem_date'] = 'wpem_date';
        return $cols;
    }

    public static function admin_orderby(\WP_Query $q): void {
        if (!is_admin() || !$q->is_main_query()) return;
        if ($q->get('post_type') !== PostTypes::CPT) return;

        if ($q->get('orderby') === 'wpem_date') {
            $q->set('meta_key', '_wpem_event_date');
            $q->set('orderby', 'meta_value');
        }
    }

    public static function enforce_featured_image_on_publish(array $data, array $postarr): array {
        if (($data['post_type'] ?? '') !== PostTypes::CPT) return $data;
        if (($data['post_status'] ?? '') !== 'publish') return $data;

        $post_id = isset($postarr['ID']) ? (int) $postarr['ID'] : 0;
        if (!$post_id) return $data;

        if (!has_post_thumbnail($post_id)) {
            $data['post_status'] = 'draft';
            set_transient('wpemcli_notice_' . get_current_user_id(), 'featured_image_required', 60);
        }
        return $data;
    }

    public static function notices(): void {
        $key = 'wpemcli_notice_' . get_current_user_id();
        $notice = get_transient($key);
        if (!$notice) return;
        delete_transient($key);

        if ($notice === 'featured_image_required') {
            echo '<div class="notice notice-error is-dismissible"><p>';
            echo esc_html__('Featured image is required to publish an event. The event was saved as Draft.', 'wp-event-manager-cli');
            echo '</p></div>';
        }
    }
}