<?php
namespace WPEMCLI;

if ( ! defined('ABSPATH') ) exit;

final class RSVP {
    public static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'wpem_rsvps';
    }

    public static function maybe_create_table(bool $force = false): void {
        $ver = get_option('wpemcli_db_ver');
        if (!$force && $ver === '1') return;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        global $wpdb;

        $charset = $wpdb->get_charset_collate();
        $table = self::table();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            name VARCHAR(200) NOT NULL DEFAULT '',
            email VARCHAR(200) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY event_id (event_id),
            KEY email (email)
        ) {$charset};";

        dbDelta($sql);
        update_option('wpemcli_db_ver', '1');
    }

    public static function add(int $event_id, string $name, string $email, ?int $user_id): bool {
        $res = self::add_with_status($event_id, $name, $email, $user_id);
        return ($res === 'added' || $res === 'exists');
}


    
public static function add_with_status(int $event_id, string $name, string $email, ?int $user_id): string {
    global $wpdb;
    $table = self::table();

    $email = sanitize_email($email);
    if (!$email || !is_email($email)) return 'invalid_email';

    $exists = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE event_id=%d AND email=%s",
        $event_id, $email
    ));
    if ($exists > 0) return 'exists';

    $ok = (bool) $wpdb->insert($table, [
        'event_id'   => $event_id,
        'user_id'    => $user_id,
        'name'       => $name,
        'email'      => $email,
        'created_at' => current_time('mysql'),
    ], ['%d','%d','%s','%s','%s']);

    return $ok ? 'added' : 'db_error';
}


public static function admin_list(int $event_id, string $search, int $paged, int $per_page): array {
    global $wpdb;
    $table = self::table();

    $paged = max(1, (int)$paged);
    $per_page = max(1, (int)$per_page);
    $offset = ($paged - 1) * $per_page;

    $where = '1=1';
    $params = [];

    if ($event_id > 0) {
        $where .= ' AND event_id = %d';
        $params[] = $event_id;
    }

    if ($search !== '') {
        $where .= ' AND (email LIKE %s OR name LIKE %s)';
        $like = '%' . $wpdb->esc_like($search) . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $sql_total = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
    $total = (int) $wpdb->get_var($wpdb->prepare($sql_total, $params));

    $sql = "SELECT event_id, name, email, created_at
            FROM {$table}
            WHERE {$where}
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d";
    $params2 = array_merge($params, [$per_page, $offset]);
    $rows = $wpdb->get_results($wpdb->prepare($sql, $params2), ARRAY_A);

    return ['total' => $total, 'rows' => (array) $rows];
}

public static function count_for_event(int $event_id): int {
        global $wpdb;
        $table = self::table();
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE event_id=%d",
            $event_id
        ));
    }

    public static function emails_for_event(int $event_id): array {
        global $wpdb;
        $table = self::table();
        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT email FROM {$table} WHERE event_id=%d",
            $event_id
        ));
        $emails = [];
        foreach ((array) $rows as $email) {
            $email = sanitize_email((string) $email);
            if ($email && is_email($email)) $emails[] = $email;
        }
        return array_values(array_unique($emails));
    }
}