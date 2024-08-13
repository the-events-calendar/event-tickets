<?php

namespace TEC\Tickets\Seating\Admin\Events;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Seating\Admin\Events\Controller;
use TEC\Tickets\Seating\Service\Layouts as Layouts_Service;
use TEC\Tickets\Seating\Meta;
use Tribe\Tests\Traits\With_Uopz;

class Controller_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	
	protected string $controller_class = Controller::class;
	
	/**
	 * @before
	 */
	public function setup_conditions() {
		$this->set_fn_return( 'wp_create_nonce', 'xxxxxx' );
		$this->set_fn_return( 'is_admin', true );
		$this->set_fn_return( 'get_column_headers', [] );
		
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
					'seats'         => 10,
					'createdDate'   => time() * 1000,
					'mapId'         => 'some-map-2',
					'screenshotUrl' => 'https://example.com/some-layouts-2.png',
				],
			]
		);
	}
	
	public function tearDown() {
		parent::tearDown();
		Layouts_Service::invalidate_cache();
		unset( $_GET['layout'] );
	}

	public function test_rendering_associated_events_list() {
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
	}

	public function test_associated_events_list_without_layout() {
		$this->make_controller()->register();
		ob_start();
		$this->make_controller()->render();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}
	
	public function test_list_with_layout_without_association() {
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
		$this->make_controller()->register();

		ob_start();
		$this->make_controller()->render();
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
