<?php

namespace TEC\Tickets\Seating\Admin\Events;

use Closure;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
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

		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );

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
				[
					'id'            => 'some-layout-3',
					'name'          => 'Some Layout 3',
					'seats'         => 30,
					'createdDate'   => time() * 1000,
					'mapId'         => 'some-map-3',
					'screenshotUrl' => 'https://example.com/some-layouts-3.png',
				],
			]
		);
	}

	public function tearDown() {
		parent::tearDown();
		Layouts_Service::invalidate_cache();
		unset( $_GET['layout'] );
	}

	public function events_list_data_provider(): Generator {
		yield 'No Layout or invalid ID given' => [
			function (): array {
				return [
					[],
					[],
				];
			},
		];

		yield 'Layout ID without attached event' => [
			function (): array {
				return [
					[ 'layout' => 'some-layout-3' ],
					[],
				];
			},
		];

		yield 'Events with attached layout: some-layout-1' => [
			function (): array {
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

				return [
					[ 'layout' => 'some-layout-1' ],
					[ $event_id, $post_id ],
				];
			},
		];

		yield 'Events with varying status with pagination' => [
			function (): array {
				$event_id = tribe_events()->set_args(
					[
						'title'         => 'Event with layout and draft status',
						'status'        => 'draft',
						'start_date'    => '2020-01-01 00:00:00',
						'duration'      => 2 * HOUR_IN_SECONDS,
						'post_date'     => '2020-01-01 00:00:00',
						'post_date_gmt' => '2020-01-01 00:00:00',
					]
				)->create()->ID;

				$post_id = self::factory()->post->create(
					[
						'post_title'  => 'Post with layout and pending status',
						'post_status' => 'pending',
					]
				);

				wp_update_post(
					[
						'ID'            => $post_id,
						'post_date'     => '2020-01-02 00:00:00',
						'post_date_gmt' => '2020-01-02 00:00:00',
					]
				);

				$event_id_2 = tribe_events()->set_args(
					[
						'title'         => 'Event with draft status',
						'status'        => 'publish',
						'start_date'    => '2020-01-01 00:00:00',
						'duration'      => 2 * HOUR_IN_SECONDS,
						'post_date'     => '2020-01-01 00:00:00',
						'post_date_gmt' => '2020-01-01 00:00:00',
					]
				)->create()->ID;

				$post_id_2 = self::factory()->post->create(
					[
						'post_title' => 'Post with published status',
					]
				);

				wp_update_post(
					[
						'ID'            => $post_id_2,
						'post_date'     => '2020-01-02 00:00:00',
						'post_date_gmt' => '2020-01-02 00:00:00',
					]
				);

				foreach ( [ $event_id, $event_id_2, $post_id, $post_id_2 ] as $id ) {
					update_post_meta( $id, Meta::META_KEY_ENABLED, true );
					update_post_meta( $id, Meta::META_KEY_LAYOUT_ID, 'some-layout-2' );
				}

				update_user_meta( get_current_user_id(), Associated_Events::OPTION_PER_PAGE, 1 );

				return [
					[
						'layout' => 'some-layout-2',
						'order'  => 'ASC',
					],
					[ $event_id, $post_id, $event_id_2, $post_id_2 ],
				];
			},
		];

		yield 'Events with search result for - future' => [
			function (): array {
				$event_id = tribe_events()->set_args(
					[
						'title'         => 'Event with future in title',
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

				$event_id_2 = tribe_events()->set_args(
					[
						'title'         => 'Event with private status',
						'status'        => 'private',
						'start_date'    => '2020-01-01 00:00:00',
						'duration'      => 2 * HOUR_IN_SECONDS,
						'post_date'     => '2020-01-01 00:00:00',
						'post_date_gmt' => '2020-01-01 00:00:00',
					]
				)->create()->ID;

				$post_id_2 = self::factory()->post->create(
					[
						'post_title' => 'Post with future in title',
					]
				);

				wp_update_post(
					[
						'ID'            => $post_id_2,
						'post_date'     => '2020-01-02 00:00:00',
						'post_date_gmt' => '2020-01-02 00:00:00',
					]
				);

				foreach ( [ $event_id, $event_id_2, $post_id, $post_id_2 ] as $id ) {
					update_post_meta( $id, Meta::META_KEY_ENABLED, true );
					update_post_meta( $id, Meta::META_KEY_LAYOUT_ID, 'some-layout-3' );
				}

				return [
					[
						'layout' => 'some-layout-3',
						's'      => 'future',
					],
					[ $event_id, $post_id, $event_id_2, $post_id_2 ],
				];
			},
		];
	}

	/**
	 * @dataProvider events_list_data_provider
	 */
	public function test_associated_events_list( Closure $fixture ): void {
		// Impose an order to stabilize the snapshot.
		$_GET['orderby'] = 'ID';
		[ $vars, $ids ] = $fixture();

		if ( ! empty( $vars ) ) {
			foreach ( $vars as $key => $value ) {
				$_GET[ $key ] = $value;
			}
		}

		$this->make_controller()->register();

		ob_start();
		$this->make_controller()->render();
		$html = ob_get_clean();

		if ( ! empty( $ids ) ) {
			// Remove the ids from the html.
			$html = str_replace( $ids, array_fill( 0, count( $ids ), '{{ID}}' ), $html );
		}

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
