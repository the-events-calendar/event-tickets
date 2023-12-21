<?php

namespace Tribe\Tickets;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Events__Main as TEC;
use Tribe__Tickets__Main as Tickets_Main;
use Tribe__Tickets__Metabox as Metabox;

class MetaboxTest extends WPTestCase {
	use SnapshotAssertions;
	use RSVP_Ticket_Maker;
	use Series_Pass_Factory;
	use With_Uopz;

	/**
	 * @before
	 */
	public function ensure_preconditions(): void {
		tribe( Module::class );
		$ticketable_post_types   = Tickets_Main::instance()->post_types();
		$ticketable_post_types[] = 'post';
		$ticketable_post_types[] = Series_Post_Type::POSTTYPE;
		$ticketable_post_types[] = TEC::POSTTYPE;
		$ticketable_post_types   = array_unique( $ticketable_post_types );
		tribe_update_option( 'ticket-enabled-post-types', $ticketable_post_types );
		// To be able to edit the posts.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function get_panels_provider(): Generator {
		yield 'post without ticket' => [
			function (): array {
				$post_id = $this->factory()->post->create( [ 'post_title' => 'Test post' ] );

				return [ $post_id ];
			},
		];

		yield 'post with ticket' => [
			function (): array {
				$post_id   = $this->factory()->post->create( [ 'post_title' => 'Test post' ] );
				$ticket_id = $this->create_tc_ticket(
					$post_id,
					23
				);

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'post with RSVP' => [
			function (): array {
				$post_id   = $this->factory()->post->create( [ 'post_title' => 'Test post' ] );
				$ticket_id = $this->create_rsvp_ticket(
					$post_id,
					[
						'meta_input' => [
							'_ticket_start_date' => '2020-01-02',
							'_ticket_end_date'   => '2050-03-01',
						],
					] 
				);

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'post with tickets and RSVPs' => [
			function (): array {
				$post_id  = $this->factory()->post->create( [ 'post_title' => 'Test post' ] );
				$rsvp_1   = $this->create_rsvp_ticket(
					$post_id,
					[
						'meta_input' => [
							'_ticket_start_date' => '2020-01-02',
							'_ticket_end_date'   => '2050-03-01',
						],
					] 
				);
				$rsvp_2   = $this->create_rsvp_ticket(
					$post_id,
					[
						'meta_input' => [
							'_ticket_start_date' => '2020-01-02',
							'_ticket_end_date'   => '2050-03-01',
						],
					] 
				);
				$ticket_1 = $this->create_tc_ticket( $post_id, 25 );
				$ticket_2 = $this->create_tc_ticket( $post_id, 26 );
				// Sort the tickets "manually".
				wp_update_post(
					[
						'ID'         => $rsvp_1,
						'menu_order' => 1,
					] 
				);
				wp_update_post(
					[
						'ID'         => $rsvp_2,
						'menu_order' => 0,
					] 
				);
				wp_update_post(
					[
						'ID'         => $ticket_1,
						'menu_order' => 1,
					] 
				);
				wp_update_post(
					[
						'ID'         => $ticket_2,
						'menu_order' => 0,
					] 
				);

				return [ $post_id, $rsvp_1, $rsvp_2, $ticket_1, $ticket_2 ];
			},
		];

		yield 'single event without tickets' => [
			function (): array {
				$post_id = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
					] 
				)->create()->ID;

				return [ $post_id ];
			},
		];

		yield 'single event with ticket' => [
			function (): array {
				$post_id   = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
					] 
				)->create()->ID;
				$ticket_id = $this->create_tc_ticket( $post_id, 23 );

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'single event with RSVP' => [
			function (): array {
				$post_id = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
					] 
				)->create()->ID;
				$rsvp_id = $this->create_rsvp_ticket(
					$post_id,
					[
						'meta_input' => [
							'_ticket_start_date' => '2020-01-02',
							'_ticket_end_date'   => '2050-03-01',
						],
					] 
				);

				return [ $post_id, $rsvp_id ];
			},
		];

		yield 'single event with tickets and RSVPs' => [
			function (): array {
				$post_id  = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
					] 
				)->create()->ID;
				$rsvp_1   = $this->create_rsvp_ticket(
					$post_id,
					[
						'meta_input' => [
							'_ticket_start_date' => '2020-01-02',
							'_ticket_end_date'   => '2050-03-01',
						],
					] 
				);
				$rsvp_2   = $this->create_rsvp_ticket(
					$post_id,
					[
						'meta_input' => [
							'_ticket_start_date' => '2020-01-02',
							'_ticket_end_date'   => '2050-03-01',
						],
					] 
				);
				$ticket_1 = $this->create_tc_ticket( $post_id, 25 );
				$ticket_2 = $this->create_tc_ticket( $post_id, 26 );

				// Sort the tickets "manually".
				wp_update_post(
					[
						'ID'         => $rsvp_1,
						'menu_order' => 1,
					] 
				);
				wp_update_post(
					[
						'ID'         => $rsvp_2,
						'menu_order' => 2,
					] 
				);
				wp_update_post(
					[
						'ID'         => $ticket_1,
						'menu_order' => 1,
					] 
				);
				wp_update_post(
					[
						'ID'         => $ticket_2,
						'menu_order' => 2,
					] 
				);

				return [ $post_id, $rsvp_1, $rsvp_2, $ticket_1, $ticket_2 ];
			},
		];

		yield 'single event part of a series with no tickets and no series passes' => [
			function (): array {
				$series_id = static::factory()->post->create(
					[
						'post_type'  => Series_Post_Type::POSTTYPE,
						'post_title' => 'Test series',
					] 
				);
				$post_id   = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
						'series'     => $series_id,
					] 
				)->create()->ID;

				return [ $post_id, $series_id ];
			},
		];

		yield 'single event part of a series with no tickets, series has passes' => [
			function (): array {
				$series_id = static::factory()->post->create(
					[
						'post_type'  => Series_Post_Type::POSTTYPE,
						'post_title' => 'Test series',
					] 
				);
				$pass_1    = $this->create_tc_series_pass( $series_id, 23 )->ID;
				$pass_2    = $this->create_tc_series_pass( $series_id, 89 )->ID;
				// Sort the tickets "manually".
				wp_update_post(
					[
						'ID'         => $pass_1,
						'menu_order' => 1,
					] 
				);
				wp_update_post(
					[
						'ID'         => $pass_2,
						'menu_order' => 2,
					] 
				);
				$post_id = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
						'series'     => $series_id,
					] 
				)->create()->ID;

				return [ $post_id, $pass_1, $pass_2, $series_id ];
			},
		];

		yield 'single event part of a series with tickets, RSVPs and passes' => [
			function (): array {
				$series_id = static::factory()->post->create(
					[
						'post_type'  => Series_Post_Type::POSTTYPE,
						'post_title' => 'Test series',
					] 
				);
				$pass_1    = $this->create_tc_series_pass( $series_id, 23 )->ID;
				$pass_2    = $this->create_tc_series_pass( $series_id, 89 )->ID;
				$post_id   = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
						'series'     => $series_id,
					] 
				)->create()->ID;
				$ticket_1  = $this->create_tc_ticket( $post_id, 25 );
				$ticket_2  = $this->create_tc_ticket( $post_id, 26 );
				$rsvp_1    = $this->create_rsvp_ticket(
					$post_id,
					[
						'meta_input' => [
							'_ticket_start_date' => '2020-01-02',
							'_ticket_end_date'   => '2050-03-01',
						],
					] 
				);
				$rsvp_2    = $this->create_rsvp_ticket(
					$post_id,
					[
						'meta_input' => [
							'_ticket_start_date' => '2020-01-02',
							'_ticket_end_date'   => '2050-03-01',
						],
					] 
				);
				// Sort the tickets "manually".
				wp_update_post(
					[
						'ID'         => $pass_1,
						'menu_order' => 1,
					] 
				);
				wp_update_post(
					[
						'ID'         => $pass_2,
						'menu_order' => 2,
					] 
				);
				wp_update_post(
					[
						'ID'         => $ticket_1,
						'menu_order' => 0,
					] 
				);
				wp_update_post(
					[
						'ID'         => $ticket_2,
						'menu_order' => 1,
					] 
				);
				wp_update_post(
					[
						'ID'         => $rsvp_1,
						'menu_order' => 0,
					] 
				);
				wp_update_post(
					[
						'ID'         => $rsvp_2,
						'menu_order' => 1,
					] 
				);

				return [ $post_id, $ticket_1, $ticket_2, $pass_1, $pass_2, $rsvp_1, $rsvp_2, $series_id ];
			},
		];

		yield 'recurring event part of series with no passes' => [
			function (): array {
				$series_id                   = static::factory()->post->create(
					[
						'post_type'  => Series_Post_Type::POSTTYPE,
						'post_title' => 'Test series',
					] 
				);
				$post_id                     = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
						'recurrence' => 'RRULE:FREQ=DAILY;COUNT=2',
						'series'     => $series_id,
					] 
				)->create()->ID;
				$occurrences_provisional_ids = occurrence::where( 'post_id', '=', $post_id )
					->map( fn( occurrence $o ) => $o->provisional_id );

				return [ $post_id, $series_id, ...$occurrences_provisional_ids ];
			},
		];

		yield 'recurring event part of a series with passes' => [
			function (): array {
				$series_id = static::factory()->post->create(
					[
						'post_type'  => Series_Post_Type::POSTTYPE,
						'post_title' => 'Test series',
					] 
				);
				$pass_1    = $this->create_tc_series_pass( $series_id, 23 )->ID;
				$pass_2    = $this->create_tc_series_pass( $series_id, 89 )->ID;
				// Sort the tickets "manually".
				wp_update_post(
					[
						'ID'         => $pass_1,
						'menu_order' => 1,
					] 
				);
				wp_update_post(
					[
						'ID'         => $pass_2,
						'menu_order' => 0,
					] 
				);
				$post_id = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
						'recurrence' => 'RRULE:FREQ=DAILY;COUNT=2',
						'series'     => $series_id,
					] 
				)->create()->ID;

				$occurrences_provisional_ids = Occurrence::where( 'post_id', '=', $post_id )
					->map( fn( Occurrence $o ) => $o->provisional_id );

				return [ $post_id, $pass_1, $pass_2, $series_id, ...$occurrences_provisional_ids ];
			},
		];

		yield 'series with no passes' => [
			function (): array {
				$series_id = static::factory()->post->create(
					[
						'post_type'  => Series_Post_Type::POSTTYPE,
						'post_title' => 'Test series',
					] 
				);

				return [ $series_id ];
			},
		];

		yield 'series with passes' => [
			function (): array {
				$series_id = static::factory()->post->create(
					[
						'post_type'  => Series_Post_Type::POSTTYPE,
						'post_title' => 'Test series',
					] 
				);
				$pass_1    = $this->create_tc_series_pass( $series_id, 23 )->ID;
				$pass_2    = $this->create_tc_series_pass( $series_id, 89 )->ID;
				// Sort the tickets "manually".
				wp_update_post(
					[
						'ID'         => $pass_1,
						'menu_order' => 1,
					] 
				);
				wp_update_post(
					[
						'ID'         => $pass_2,
						'menu_order' => 0,
					] 
				);

				return [ $series_id, $pass_1, $pass_2 ];
			},
		];
	}

	public function placehold_post_ids(
		string $snapshot,
		array $ids
	): string {
		return str_replace(
			$ids,
			array_fill( 0, count( $ids ), '{{ID}}' ),
			$snapshot
		);
	}

	/**
	 * @dataProvider get_panels_provider
	 */
	public function test_get_panels(
		Closure $fixture
	): void {
		// Mock the current date to consolidate the snapshots.
		$post_ids = $fixture();
		// Render for the first post ID in the set.
		$post_id = reset( $post_ids );
		$this->set_fn_return( 'wp_create_nonce', '33333333' );

		$metabox = tribe( Metabox::class );
		// Rend for a new ticket.
		$panels = $metabox->get_panels( $post_id, null );
		$html   = implode( '', $panels );
		$html   = $this->placehold_post_ids( $html, $post_ids );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
