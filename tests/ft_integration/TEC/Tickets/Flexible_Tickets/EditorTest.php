<?php

namespace TEC\Tickets\Flexible_Tickets;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events_Pro\Custom_Tables\V1\Editors\Classic\Events_Metaboxes;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;
use TEC\Tickets\Commerce\Module as Commerce;

class EditorTest extends Controller_Test_Case {
	use SnapshotAssertions;

	protected string $controller_class = Editor::class;

	public function test_render_of_event_series_relationship(): void {
		/** @var Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );
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
		$html = str_replace( [ $event_post_id ], [ 'EVENT_ID' ], $html );
		$html = str_replace(  $tc_series , 'SERIES_ID', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
