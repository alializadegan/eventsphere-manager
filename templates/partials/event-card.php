<?php
if ( ! defined('ABSPATH') ) exit;

$id   = get_the_ID();
$date = get_post_meta($id, '_wpem_event_date', true);
$loc  = get_post_meta($id, '_wpem_event_location', true);
$types = get_the_terms($id, \WPEMCLI\PostTypes::TAX);
$type_name = (!is_wp_error($types) && !empty($types)) ? $types[0]->name : '';
$status = \WPEMCLI\Frontend::event_status($id);
?>
<article class="wpemcli-card">
  <a class="wpemcli-card__thumb" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>">
    <?php if (has_post_thumbnail()): ?>
      <?php the_post_thumbnail('large'); ?>
    <?php else: ?>
      <span aria-hidden="true"></span>
    <?php endif; ?>
  </a>

  <div class="wpemcli-card__body">
    <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
      <h3 class="wpemcli-card__title" style="margin-right:8px;">
        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
      </h3>
      <span class="wpemcli-badge <?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status)); ?></span>
    </div>

    <ul class="wpemcli-meta">
      <?php if ($date): ?><li class="wpemcli-pill"><?php echo esc_html($date); ?></li><?php endif; ?>
      <?php if ($loc): ?><li class="wpemcli-pill"><?php echo esc_html($loc); ?></li><?php endif; ?>
      <?php if ($type_name): ?><li class="wpemcli-pill"><?php echo esc_html($type_name); ?></li><?php endif; ?>
    </ul>

    <p class="wpemcli-card__excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), 22)); ?></p>

    <div class="wpemcli-card__footer">
      <a class="wpemcli-button primary" href="<?php the_permalink(); ?>"><?php esc_html_e('View details', 'wp-event-manager-cli'); ?></a>
      <span class="wpemcli-pill"><?php echo esc_html(sprintf(__('RSVPs: %d', 'wp-event-manager-cli'), \WPEMCLI\RSVP::count_for_event($id))); ?></span>
    </div>
  </div>
</article>