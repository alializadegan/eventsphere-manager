<?php
namespace WPEMCLI;

if ( ! defined('ABSPATH') ) exit;

final class CLI extends \WP_CLI_Command {

    public static function register(): void {
        \WP_CLI::add_command('wpem', __CLASS__);
    }

    
    public function install($args, $assoc_args): void {
        PostTypes::register();
        flush_rewrite_rules();

        $page_id = wp_insert_post([
            'post_type'   => 'page',
            'post_status' => 'publish',
            'post_title'  => 'Events',
            'post_content'=> '[event_list]',
        ]);

        \WP_CLI::success('Installed. Page created ID=' . (int) $page_id);
    }

    
    public function seed($args, $assoc_args): void {
        $count = isset($assoc_args['count']) ? max(1, (int)$assoc_args['count']) : 5;

        $types = ['conference', 'meetup', 'workshop'];
        foreach ($types as $t) {
            if (!term_exists($t, PostTypes::TAX)) {
                wp_insert_term(ucfirst($t), PostTypes::TAX, ['slug' => $t]);
            }
        }

        for ($i=1; $i<=$count; $i++) {
            $id = wp_insert_post([
                'post_type'   => PostTypes::CPT,
                'post_status' => 'publish',
                'post_title'  => "Sample Event {$i}",
                'post_content'=> 'This is a seeded event.',
            ]);

            $date = gmdate('Y-m-d', time() + ($i * DAY_IN_SECONDS));
            update_post_meta($id, '_wpem_event_date', $date);
            update_post_meta($id, '_wpem_event_location', 'Berlin');

            wp_set_object_terms($id, $types[array_rand($types)], PostTypes::TAX);

            \WP_CLI::log("Created event ID={$id}");
        }

        \WP_CLI::success("Seeded {$count} events.");
    }
}