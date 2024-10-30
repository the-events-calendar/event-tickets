<?php

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events_Pro\Custom_Tables\V1\Editors\Classic\Events_Metaboxes;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series;
use TEC\Tickets\Commerce\Module as Commerce;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;
use WP_Hook;

class EditorTest extends Controller_Test_Case {
	use SnapshotAssertions;

	protected string $controller_class = Editor::class;

	public function test_render_of_event_series_relationship(): void {
		/** @var Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$provider_meta_key = $tickets_handler->key_provider_field;

		$event_post_id = tribe_events()->set_args( [
			'title'      => 'Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 09:00:00',
			'duration'   => 5 * HOUR_IN_SECONDS,
		] )->create()->ID;

		// Create a Series with the Tickets Commerce provider.
		$tc_series = static::factory()->post->create( [
			'post_type'   => Series::POSTTYPE,
			'post_title'  => 'Series 1',
			'post_status' => 'publish',
		] );
		update_post_meta( $tc_series, $provider_meta_key, Commerce::class );

		// Fix the home URL.
		add_filter( 'home_url', fn() => 'https://wordpress.dev' );

		// Build and register the controller.
		$this->make_controller()->register();

		// Simulate the rendering in the context of the post edit screen.
		$GLOBALS['post'] = $event_post_id;

		// Render the metabox contents.
		$events_metaboxes = tribe( Events_Metaboxes::class );
		ob_start();
		$events_metaboxes->relationship();
		$html = ob_get_clean();

		// Stabilize the snapshot.
		$html = str_replace( [
			$event_post_id,
			$tc_series
		], [
			'EVENT_ID',
			'SERIES_ID'
		], $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function filter_tickets_editor_config_provider(): Generator {
		yield 'post' => [
			function () {
				$post            = static::factory()->post->create_and_get();
				$GLOBALS['post'] = $post;

				return [ $post->ID ];
			}
		];

		yield 'event not in series' => [
			function () {
				$event           = tribe_events()->set_args( [
					'title'      => 'Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 09:00:00',
					'duration'   => 5 * HOUR_IN_SECONDS,
				] )->create();
				$GLOBALS['post'] = $event;

				return [ $event->ID ];
			}
		];

		yield 'event in series' => [
			function () {
				$series          = static::factory()->post->create( [
					'post_type'   => Series::POSTTYPE,
					'post_status' => 'publish',
					'post_title'  => 'Test Series',
				] );
				$event           = tribe_events()->set_args( [
					'title'      => 'Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 09:00:00',
					'duration'   => 5 * HOUR_IN_SECONDS,
					'series'     => $series
				] )->create();
				$GLOBALS['post'] = $event;

				return [ $event->ID, $series ];
			}
		];
	}

	/**
	 * It should not filter editor config data if post not event
	 *
	 * @test
	 */
	public function should_not_filter_editor_config_data_if_post_not_event(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$GLOBALS['post'] = static::factory()->post->create_and_get();
		// Unhook any filtering function from `tribe_editor_config`: they will only add noise to the tests.
		global $wp_filter;
		$wp_filter['tribe_editor_config'] = new WP_Hook();

		$this->make_controller()->register();

		$configuration = apply_filters( 'tribe_editor_config', [] );

		$this->assertEquals( [], $configuration );
	}

	/**
	 * It should not filter editor config data if event not in series
	 *
	 * @test
	 */
	public function should_not_filter_editor_config_data_if_event_not_in_series(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$event           = tribe_events()->set_args( [
			'title'      => 'Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 09:00:00',
			'duration'   => 5 * HOUR_IN_SECONDS,
		] )->create();
		$GLOBALS['post'] = $event;
		// Unhook any filtering function from `tribe_editor_config`: they will only add noise to the tests.
		global $wp_filter;
		$wp_filter['tribe_editor_config'] = new WP_Hook();

		$this->make_controller()->register();

		$configuration = apply_filters( 'tribe_editor_config', [] );

		$this->assertEquals( [], $configuration );
	}

	/**
	 * It should filter editor config data if event in series
	 *
	 * @test
	 */
	public function should_filter_editor_config_data_if_event_in_series(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$series          = static::factory()->post->create( [
			'post_type'   => Series::POSTTYPE,
			'post_status' => 'publish',
			'post_title'  => 'Test Series',
		] );
		$event           = tribe_events()->set_args( [
			'title'      => 'Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 09:00:00',
			'duration'   => 5 * HOUR_IN_SECONDS,
			'series'     => $series
		] )->create();
		$GLOBALS['post'] = $event;
		// Unhook any filtering function from `tribe_editor_config`: they will only add noise to the tests.
		global $wp_filter;
		$wp_filter['tribe_editor_config'] = new WP_Hook();

		$this->make_controller()->register();

		$configuration = apply_filters( 'tribe_editor_config', [] );

        $this->assertMatchesStringSnapshot(
            str_replace(
                $series,
                'SERIES_ID',
                $configuration['tickets']['multipleProvidersNoticeTemplate']
            )
        );
	}
}
