<?php
if ( ! defined('ABSPATH') ) exit;

$filters = isset($wpemcli_filters) && is_array($wpemcli_filters)
  ? $wpemcli_filters
  : ['type'=>'','s'=>'','from'=>'','to'=>'','status'=>'upcoming','sort'=>'date_asc'];

$types = get_terms(['taxonomy' => \WPEMCLI\PostTypes::TAX, 'hide_empty' => false]);

$type   = $filters['type'] ?? '';
$s      = $filters['s'] ?? '';
$from   = $filters['from'] ?? '';
$to     = $filters['to'] ?? '';
$status = $filters['status'] ?? 'upcoming';
$sort   = $filters['sort'] ?? 'date_asc';

$action = esc_url(remove_query_arg('paged'));
?>
<form method="get" action="<?php echo $action; ?>" class="wpemcli-filters" role="search" aria-label="<?php esc_attr_e('Event filters', 'wp-event-manager-cli'); ?>">
  <div class="wpemcli-field">
    <label for="wpemcli_s"><?php esc_html_e('Search', 'wp-event-manager-cli'); ?></label>
    <input id="wpemcli_s" class="wpemcli-input" type="text" name="s" value="<?php echo esc_attr($s); ?>" placeholder="<?php esc_attr_e('Search events…', 'wp-event-manager-cli'); ?>" />
  </div>

  <div class="wpemcli-field">
    <label for="wpemcli_type"><?php esc_html_e('Type', 'wp-event-manager-cli'); ?></label>
    <select id="wpemcli_type" class="wpemcli-select" name="type">
      <option value=""><?php esc_html_e('All types', 'wp-event-manager-cli'); ?></option>
      <?php foreach ((array) $types as $t): ?>
        <option value="<?php echo esc_attr($t->slug); ?>" <?php selected($type, $t->slug); ?>><?php echo esc_html($t->name); ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="wpemcli-field">
    <label for="wpemcli_from"><?php esc_html_e('From', 'wp-event-manager-cli'); ?></label>
    <input id="wpemcli_from" class="wpemcli-input" type="date" name="from" value="<?php echo esc_attr($from); ?>" placeholder="YYYY-MM-DD" />
  </div>

  <div class="wpemcli-field">
    <label for="wpemcli_to"><?php esc_html_e('To', 'wp-event-manager-cli'); ?></label>
    <input id="wpemcli_to" class="wpemcli-input" type="date" name="to" value="<?php echo esc_attr($to); ?>" placeholder="YYYY-MM-DD" />
  </div>

  <div class="wpemcli-field">
    <label for="wpemcli_status"><?php esc_html_e('Status', 'wp-event-manager-cli'); ?></label>
    <select id="wpemcli_status" class="wpemcli-select" name="status">
      <option value="upcoming" <?php selected($status, 'upcoming'); ?>><?php esc_html_e('Upcoming', 'wp-event-manager-cli'); ?></option>
      <option value="past" <?php selected($status, 'past'); ?>><?php esc_html_e('Past', 'wp-event-manager-cli'); ?></option>
      <option value="all" <?php selected($status, 'all'); ?>><?php esc_html_e('All', 'wp-event-manager-cli'); ?></option>
    </select>
  </div>

  <div class="wpemcli-field">
    <label for="wpemcli_sort"><?php esc_html_e('Sort', 'wp-event-manager-cli'); ?></label>
    <select id="wpemcli_sort" class="wpemcli-select" name="sort">
      <option value="date_asc" <?php selected($sort, 'date_asc'); ?>><?php esc_html_e('Date (oldest first)', 'wp-event-manager-cli'); ?></option>
      <option value="date_desc" <?php selected($sort, 'date_desc'); ?>><?php esc_html_e('Date (newest first)', 'wp-event-manager-cli'); ?></option>
      <option value="title_asc" <?php selected($sort, 'title_asc'); ?>><?php esc_html_e('Title (A→Z)', 'wp-event-manager-cli'); ?></option>
    </select>
  </div>

  <div class="wpemcli-actions">
    <button class="wpemcli-button primary" type="submit"><?php esc_html_e('Filter', 'wp-event-manager-cli'); ?></button>
    <a class="wpemcli-button" href="<?php echo esc_url($action); ?>"><?php esc_html_e('Reset', 'wp-event-manager-cli'); ?></a>
  </div>
</form>