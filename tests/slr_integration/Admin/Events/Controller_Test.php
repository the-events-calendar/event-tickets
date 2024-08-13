<?php

namespace TEC\Tickets\Seating\Admin\Events;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Seating\Admin\Events\Controller;
use TEC\Tickets\Seating\Service\Layouts as Layouts_Service;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Tables\Layouts as Layouts_Table;

class Controller_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	
	protected string $controller_class = Controller::class;
	
	public function test_rendering_associated_events_list() {
		Layouts_Service::insert_rows_from_service(
			[
				[
					'id'            => 'some-layout-1',
					'name'          => 'Some Layout 1',
					'seats'         => 10,
					'createdDate'   => time() * 1000,
					'mapId'         => 'some-map-1',
					'screenshotUrl' => 'https://example.com/some-layouts-1.png',
				],
				[
					'id'            => 'some-layout-2',
					'name'          => 'Some Layout 2',
					'seats'         => 20,
					'createdDate'   => time() * 1000,
					'mapId'         => 'some-map-2',
					'screenshotUrl' => 'https://example.com/some-layouts-2.png',
				],
			]
		);
		
		$event_id = tribe_events()->set_args(
			[
				'title'         => 'Event with layout',
				'status'        => 'publish',
				'start_date'    => '2020-01-01 00:00:00',
				'duration'      => 2 * HOUR_IN_SECONDS,
				'post_date'     => '2020-01-01 00:00:00',
				'post_date_gmt' => '2020-01-01 00:00:00',
			]
		)->create()->ID;
		
		$post_id = self::factory()->post->create(
			[ 'post_title' => 'Post with layout' ]
		);
		
		wp_update_post(
			[
				'ID'            => $post_id,
				'post_date'     => '2020-01-02 00:00:00',
				'post_date_gmt' => '2020-01-02 00:00:00',
			]
		);
		
		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );
		update_post_meta( $post_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-1' );
		
		$_GET['layout'] = 'some-layout-1';
		
		ob_start();
		$this->make_controller()->render();
		
		$html = ob_get_clean();
		
		$html = str_replace( [ $event_id, $post_id ], [ '{{EVENT_ID}}', '{{POST_ID}}' ], $html );
		
		$this->assertMatchesHtmlSnapshot( $html );
		
		Layouts_Table::truncate();
	}
	
	public function test_associated_events_list_without_layout() {
		ob_start();
		$this->make_controller()->render();
		$html = ob_get_clean();
		
		$this->assertMatchesHtmlSnapshot( $html );
	}
	
	public function test_list_with_layout_without_association() {
		Layouts_Service::insert_rows_from_service(
			[
				[
					'id'            => 'some-layout-2',
					'name'          => 'Some Layout 2',
					'seats'         => 10,
					'createdDate'   => time() * 1000,
					'mapId'         => 'some-map-2',
					'screenshotUrl' => 'https://example.com/some-layouts-2.png',
				],
			]
		);
		
		$post_id = self::factory()->post->create(
			[ 'post_title' => 'Post with layout' ]
		);
		
		wp_update_post(
			[
				'ID'            => $post_id,
				'post_date'     => '2020-01-02 00:00:00',
				'post_date_gmt' => '2020-01-02 00:00:00',
			]
		);
		
		$_GET['layout'] = 'some-layout-2';
		
		ob_start();
		$this->make_controller()->render();
		$html = ob_get_clean();
		
		$this->assertMatchesHtmlSnapshot( $html );
		
		Layouts_Table::truncate();
	}
}
