<?php

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use Spatie\Snapshots\MatchesSnapshots;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Editors\Classic\Series_Metaboxes;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Commerce\Module as Commerce;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_PayPal;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;

class Series_MetaboxesTest extends WPTestCase {
	use MatchesSnapshots;
	use With_Uopz;
	use RSVP_Ticket_Maker;
	use Ticket_Maker;
	use Series_Pass_Factory;
	use With_Tickets_Commerce;
	use With_PayPal;

	public function series_data_provider(): Generator {
		yield 'Series has single event with no tickets' => [
			function () {
				$series = $this->factory()->post->create_and_get( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Series with events',
				] );

				$event_1 = tribe_events()->set_args( [
					'title'      => 'Event 1',
					'start_date' => '2222-02-10 17:30:00',
					'duration'   => 5 * HOUR_IN_SECONDS,
					'series'     => $series->ID,
				] )->create();

				return [
					$series->ID,
					$event_1->ID
				];
			}
		];

		yield 'Series has single event with RSVP ticket' => [
			function () {
				$series = $this->factory()->post->create_and_get( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Series with events',
				] );

				$event_1 = tribe_events()->set_args( [
					'title'      => 'Event 1',
					'start_date' => '2222-02-10 17:30:00',
					'duration'   => 5 * HOUR_IN_SECONDS,
					'series'     => $series->ID,
				] )->create();

				$overrides = [
					'meta_input' => [
						'_type' => 'rsvp'
					],
				];

				$rsvp_id = $this->create_rsvp_ticket( $event_1->ID, $overrides );

				return [
					$series->ID,
					$event_1->ID,
					$rsvp_id
				];
			}
		];

		yield 'Series has single event with RSVP and ticket' => [
			function () {
				$series = $this->factory()->post->create_and_get( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Series with events',
				] );

				$event_1 = tribe_events()->set_args( [
					'title'      => 'Event 1',
					'start_date' => '2222-02-10 17:30:00',
					'duration'   => 5 * HOUR_IN_SECONDS,
					'series'     => $series->ID,
				] )->create();

				$rsvp_id = $this->create_rsvp_ticket( $event_1->ID, [
					'meta_input' => [
						'_type' => 'rsvp'
					],
				] );

				$ticket_id = $this->create_tc_ticket( $event_1->ID, 10 );

				return [
					$series->ID,
					$event_1->ID,
					$rsvp_id,
					$ticket_id
				];
			}
		];

		yield 'Series has single event with RSVP, ticket and series pass' => [
			function () {
				$series = $this->factory()->post->create_and_get( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Series with events',
				] );

				$event_1 = tribe_events()->set_args( [
					'title'      => 'Event 1',
					'start_date' => '2222-02-10 17:30:00',
					'duration'   => 5 * HOUR_IN_SECONDS,
					'series'     => $series->ID,
				] )->create();

				$rsvp_id = $this->create_rsvp_ticket( $event_1->ID, [
					'meta_input' => [
						'_type' => 'rsvp'
					],
				] );

				$ticket_id   = $this->create_tc_ticket( $event_1->ID, 10 );
				$series_pass = $this->create_tc_series_pass( $series->ID, 10 );

				return [
					$series->ID,
					$event_1->ID,
					$rsvp_id,
					$ticket_id,
					$series_pass->ID
				];
			}
		];

		yield 'Series has recurring event with series pass' => [
			function () {

				// A private Recurring Event will generate a private Series.
				$recurring = tribe_events()->set_args( [
					'title'      => 'Recurring event 1',
					'status'     => 'publish',
					'start_date' => '2222-02-15 17:30:00',
					'duration'   => 5 * HOUR_IN_SECONDS,
					'recurrence' => 'RRULE:FREQ=WEEKLY;COUNT=3',
				] )->create();

				$series_id   = tec_series()->where( 'event_post_id', $recurring->ID )->first_id();
				$occurrences = Occurrence::where( 'post_id', '=', $recurring->ID )
				                         ->all();
				foreach ( $occurrences as $occurrence ) {
					$occurrence_provisional_ids[] = $occurrence->provisional_id;
				}

				$series_pass = $this->create_tc_series_pass( $series_id, 10 );

				return [
					$series_id,
					$recurring->ID,
					...$occurrence_provisional_ids,
					$series_pass->ID
				];
			}
		];
	}

	public function placehold_post_ids( string $snapshot, array $ids ): string {
		return str_replace(
			$ids,
			array_fill( 0, count( $ids ), '{{ID}}' ),
			$snapshot
		);
	}

	/**
	 * @dataProvider series_data_provider
	 *
	 * @covers       \TEC\Tickets\Flexible_Tickets\Series_Passes\Base::filter_attendees_event_details_top_label
	 * @covers       \TEC\Tickets\Flexible_Tickets\Series_Passes\Base::filter_series_editor_occurrence_list_columns
	 * @covers       \TEC\Tickets\Flexible_Tickets\Series_Passes\Base::render_series_editor_occurrence_list_column_ticket_types
	 */
	public function test_events_list( Closure $fixture ) {
		$post_ids  = $fixture();
		$series_id = reset( $post_ids );

		global $post;
		$post = get_post( $series_id );

		$this->set_fn_return( 'wp_create_nonce', '###' );
		$series_metabox = tribe( Series_Metaboxes::class );

		ob_start();
		$series_metabox->events_list();
		$html = ob_get_clean();

		$html = $this->placehold_post_ids( $html, $post_ids );

		$this->assertMatchesSnapshot( $html );
	}

	public function relationship_data_provider(): Generator {
		$provider_meta_key = Tickets_Handler::instance()->key_provider_field;

		yield 'no available events, one ticket provider' => [
			function () use ( $provider_meta_key ): array {
				$series_id = $this->factory()->post->create_and_get( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Test Series',
				] )->ID;
				update_post_meta( $series_id, $provider_meta_key, str_replace( '\\', '\\\\', Commerce::class ) );

				add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
					$modules[ PayPal::class ] = tribe( Commerce::class )->plugin_name;

					return $modules;
				} );

				return [ $series_id ];
			}
		];
		yield 'no available events, multiple ticket providers' => [
			function () use ( $provider_meta_key ): array {
				$series_id = $this->factory()->post->create_and_get( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Test Series',
				] )->ID;
				update_post_meta( $series_id, $provider_meta_key, str_replace( '\\', '\\\\', Commerce::class ) );

				add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
					$modules[ PayPal::class ] = tribe( Commerce::class )->plugin_name;

					return $modules;
				} );

				return [ $series_id ];
			}
		];
		yield 'events available , one ticket provider' => [
			function () use ( $provider_meta_key ): array {
				$series_id = $this->factory()->post->create_and_get( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Test Series',
				] )->ID;
				update_post_meta( $series_id, $provider_meta_key, str_replace( '\\', '\\\\', Commerce::class ) );

				add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
					$modules[ PayPal::class ] = tribe( Commerce::class )->plugin_name;

					return $modules;
				} );

				$event_ids = [];
				foreach ( range( 1, 3 ) as $k ) {
					$event_id = tribe_events()->set_args( [
						'title'      => 'Event ' . $k,
						'status'     => 'publish',
						'start_date' => "2222-0$k-10 17:30:00",
						'duration'   => 5 * HOUR_IN_SECONDS,
					] )->create()->ID;
					update_post_meta( $event_id, $provider_meta_key, str_replace( '\\', '\\\\', Commerce::class ) );
					$event_ids[] = $event_id;
				}

				return [ $series_id, ...$event_ids ];
			}
		];

		yield 'events available , multiple ticket providers' => [
			function () use ( $provider_meta_key ): array {
				$series_id = $this->factory()->post->create_and_get( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Test Series',
				] )->ID;
				update_post_meta( $series_id, $provider_meta_key, str_replace( '\\', '\\\\', Commerce::class ) );

				$event_ids = [];
				foreach ( range( 1, 3 ) as $k ) {
					$event_id = tribe_events()->set_args( [
						'title'      => 'Event ' . $k,
						'status'     => 'publish',
						'start_date' => "2222-0$k-10 17:30:00",
						'duration'   => 5 * HOUR_IN_SECONDS,
					] )->create()->ID;
					update_post_meta( $event_id, $provider_meta_key, str_replace( '\\', '\\\\', Commerce::class ) );
					$event_ids[] = $event_id;
				}

				return [ $series_id, ...$event_ids ];
			}
		];

		yield 'events available, each with diff ticket provider' => [
			function () use ( $provider_meta_key ): array {
				$series_id = $this->factory()->post->create_and_get( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Test Series',
				] )->ID;
				update_post_meta( $series_id, $provider_meta_key, str_replace( '\\', '\\\\', Commerce::class ) );

				$event_ids = [];
				$providers = [
					1 => Commerce::class,
					2 => PayPal::class,
					3 => Commerce::class,
				];
				foreach ( range( 1, 3 ) as $k ) {
					$event_id = tribe_events()->set_args( [
						'title'      => 'Event ' . $k,
						'status'     => 'publish',
						'start_date' => "2222-0$k-10 17:30:00",
						'duration'   => 5 * HOUR_IN_SECONDS,
					] )->create()->ID;
					update_post_meta( $event_id, $provider_meta_key, str_replace( '\\', '\\\\', $providers[ $k ] ) );
					$event_ids[] = $event_id;
				}

				return [ $series_id, ...$event_ids ];
			}
		];
	}

	/**
	 * @dataProvider relationship_data_provider
	 * @covers       \TEC\Tickets\Flexible_Tickets\Series_Passes\Editor::remove_diff_ticket_provider_events
	 * @covers       \TEC\Tickets\Flexible_Tickets\Series_Passes\Editor::print_multiple_providers_notice
	 */
	public function test_series_event_relationship( Closure $fixture ): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_ids  = $fixture();
		$series_id = reset( $post_ids );

		global $post;
		$post = get_post( $series_id );

		$this->set_fn_return( 'wp_create_nonce', '###' );
		$series_metabox = tribe( Series_Metaboxes::class );

		ob_start();
		$series_metabox->relationship();
		$html = ob_get_clean();

		$html = $this->placehold_post_ids( $html, $post_ids );

		$this->assertMatchesSnapshot( $html );
	}
}