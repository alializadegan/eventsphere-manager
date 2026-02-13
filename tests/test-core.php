<?php
class Test_WPEMCLI_Core extends WP_UnitTestCase {

    public function test_cpt_registered() {
        $this->assertTrue(post_type_exists('event'));
    }

    public function test_tax_registered() {
        $this->assertTrue(taxonomy_exists('event_type'));
    }

    public function test_meta_sanitize_date() {
        $this->assertSame('2026-02-11', \WPEMCLI\PostTypes::sanitize_date('2026-02-11'));
        $this->assertSame('', \WPEMCLI\PostTypes::sanitize_date('11-02-2026'));
    }

    public function test_event_status_past_upcoming() {
        $event_id = self::factory()->post->create([
            'post_type' => 'event',
            'post_status' => 'publish',
        ]);

        update_post_meta($event_id, '_wpem_event_date', '2000-01-01');
        $this->assertSame('past', \WPEMCLI\Frontend::event_status($event_id));

        update_post_meta($event_id, '_wpem_event_date', date('Y-m-d', strtotime('+10 days')));
        $this->assertSame('upcoming', \WPEMCLI\Frontend::event_status($event_id));
    }
}