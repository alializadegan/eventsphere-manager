<?php
if ( ! defined('ABSPATH') ) exit;

get_header();

the_post();
$id   = get_the_ID();
$date = get_post_meta($id, '_wpem_event_date', true);
$loc  = get_post_meta($id, '_wpem_event_location', true);
$types = get_the_terms($id, \WPEMCLI\PostTypes::TAX);
$type_name = (!is_wp_error($types) && !empty($types)) ? $types[0]->name : '';
$status = \WPEMCLI\Frontend::event_status($id);

echo '<main class="wpemcli-container">';

if (has_post_thumbnail()) {
  echo '<div class="wpemcli-card" style="margin-bottom:16px;">';
  echo '<div class="wpemcli-card__thumb" style="aspect-ratio: 21/9;">';
  the_post_thumbnail('large');
  echo '</div></div>';
}

echo '<div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">';
echo '<h1 class="wpemcli-title-xl" style="margin:0;">' . esc_html(get_the_title()) . '</h1>';
echo '<span class="wpemcli-badge ' . esc_attr($status) . '">' . esc_html(ucfirst($status)) . '</span>';
echo '</div>';

echo '<div class="wpemcli-single__meta"><ul class="wpemcli-meta">';
if ($date) echo '<li class="wpemcli-pill">' . esc_html($date) . '</li>';
if ($loc)  echo '<li class="wpemcli-pill">' . esc_html($loc) . '</li>';
if ($type_name) echo '<li class="wpemcli-pill">' . esc_html($type_name) . '</li>';
echo '</ul></div>';

$rsvp_state = isset($_GET['rsvp']) ? sanitize_text_field(wp_unslash($_GET['rsvp'])) : '';

if ($rsvp_state === 'success') {
    echo '<div class="wpemcli-notice wpemcli-notice--success">' . esc_html__('RSVP saved. See you there!', 'wp-event-manager-cli') . '</div>';
} elseif ($rsvp_state === 'exists') {
    echo '<div class="wpemcli-notice wpemcli-notice--warning">' . esc_html__('You have already registered with this email for this event.', 'wp-event-manager-cli') . '</div>';
} elseif ($rsvp_state === 'invalid_email') {
    echo '<div class="wpemcli-notice wpemcli-notice--error">' . esc_html__('Please enter a valid email address.', 'wp-event-manager-cli') . '</div>';
} elseif ($rsvp_state === 'error') {
    echo '<div class="wpemcli-notice wpemcli-notice--error">' . esc_html__('Something went wrong. Please try again.', 'wp-event-manager-cli') . '</div>';
}

echo '<article class="wpemcli-section">';
the_content();
echo '</article>';
?>
<section class="wpemcli-section">
  <h2 style="margin:0;"><?php esc_html_e('RSVP', 'wp-event-manager-cli'); ?></h2>
  <form method="post" class="wpemcli-form">
    <?php wp_nonce_field('wpem_rsvp', 'wpem_rsvp_nonce'); ?>
    <input type="hidden" name="event_id" value="<?php echo esc_attr($id); ?>" />

    <div class="wpemcli-field">
      <label><?php esc_html_e('Name', 'wp-event-manager-cli'); ?></label>
      <input type="text" name="name" value="" />
    </div>

    <div class="wpemcli-field">
      <label><?php esc_html_e('Email (required)', 'wp-event-manager-cli'); ?></label>
      <input type="email" name="email" required />
    </div>

    <div class="wpemcli-actions">
      <button type="submit" class="wpemcli-button primary" name="wpem_rsvp_submit" value="1">
        <?php esc_html_e('Confirm Attendance', 'wp-event-manager-cli'); ?>
      </button>
      <span class="wpemcli-pill">
        <?php echo esc_html(sprintf(__('Current RSVPs: %d', 'wp-event-manager-cli'), \WPEMCLI\RSVP::count_for_event($id))); ?>
      </span>
    </div>
  </form>
</section>
<?php
echo '</main>';
get_footer();