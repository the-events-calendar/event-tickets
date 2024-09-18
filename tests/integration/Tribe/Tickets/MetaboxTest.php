<?php

namespace Tribe\Tickets;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Metabox as Metabox;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use TEC\Tickets\Commerce\Module as Commerce;
use Tribe__Events__Main as TEC;
use Tribe__Date_Utils as Date_Utils;

class MetaboxTest extends WPTestCase {
	use SnapshotAssertions;
	use Ticket_Maker;
	use RSVP_Ticket_Maker;
	use With_Uopz;

	/**
	 * @before
	 */
	public function ensure_ticketable_post_types(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		$ticketable[] = TEC::POSTTYPE;
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
		// Set up a fake "now".
		$date = new \DateTime( '2019-09-11 22:00:00', new \DateTimeZone( 'America/New_York' ) );
		$now  = $date->getTimestamp();
		// Alter the concept of the `now` timestamp to return the timestamp for `2019-09-11 22:00:00` in NY timezone.
		uopz_set_return(
			'strtotime', static function ( $str ) use ( $now ) {
			return $str === 'now' ? $now : strtotime( $str );
		},  true
		);
		// Make sure that `now` (string) will be resolved to the fake date object.
		uopz_set_return( Date_Utils::class, 'build_date_object', $date );
	}

	/**
	 * Low-level registration of the Commerce provider. There is no need for a full-blown registration
	 * at this stage: having the module as active and as a valid provider is enough.
	 *
	 * @before
	 */
	public function activate_commerce_tickets(): void {
		add_filter(
			'tribe_tickets_get_modules',
			static function ( array $modules ): array {
				$modules[ Commerce::class ] = 'Tickets Commerce';

				return $modules;
			}
		);
		// Regenerate the Tickets Data API to pick up the filtered providers.
		tribe()->singleton( 'tickets.data_api', new \Tribe__Tickets__Data_API() );
	}

	public function get_panels_provider(): Generator {
		yield 'post without ticket' => [
			function (): array {
				$post_id   = $this->factory()->post->create();
				$ticket_id = null;

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'post with ticket' => [
			function (): array {
				$post_id   = $this->factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 23 );

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'event without ticket' => [
			function (): array {
				$post_id   = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
					]
				)->create()->ID;
				$ticket_id = null;

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'event with ticket' => [
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

		yield 'post with RSVP' => [
			function (): array {
				$post_id   = $this->factory()->post->create();
				$ticket_id = $this->create_rsvp_ticket(
					$post_id,
					[
						'meta_input' => [
							'_ticket_start_date' => '2021-01-01 10:00:00',
							'_ticket_end_date'   => '2021-01-31 12:00:00',
						],
					]
				);

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'event with RSVP' => [
			function (): array {
				$post_id   = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
					]
				)->create()->ID;
				$ticket_id = $this->create_rsvp_ticket(
					$post_id,
					[
						'meta_input' => [
							'_ticket_start_date' => '2021-01-01 10:00:00',
							'_ticket_end_date'   => '2021-01-31 12:00:00',
						],
					]
				);

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'post with ticket and sale price' => [
			function (): array {
				$post_id   = $this->factory()->post->create();
				$ticket_id = $this->create_tc_ticket(
					$post_id,
					20,
					[
						'ticket_add_sale_price'  => 'on',
						'ticket_sale_price'      => 10,
						'ticket_sale_start_date' => '2010-03-01',
						'ticket_sale_end_date'   => '2040-03-01',
					]
				);

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'event with ticket and sale price' => [
			function (): array {
				$post_id = tribe_events()->set_args(
					[
						'title'      => 'Test Event with sale price',
						'status'     => 'publish',
						'start_date' => '2022-10-01 10:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;

				$ticket_id = $this->create_tc_ticket(
					$post_id,
					20,
					[
						'ticket_add_sale_price'  => 'on',
						'ticket_sale_price'      => 10,
						'ticket_sale_start_date' => '2010-03-01',
						'ticket_sale_end_date'   => '2040-03-01',
					]
				);

				return [ $post_id, $ticket_id ];
			},
		];
	}

	public function placehold_post_ids( string $snapshot, array $ids ): string {
		return str_replace(
			array_values( $ids ),
			array_map( static fn( string $name ) => "{{ $name }}", array_keys( $ids ) ),
			$snapshot
		);
	}

	/**
	 * @dataProvider get_panels_provider
	 */
	public function test_get_panels( Closure $fixture ): void {
		[ $post_id, $ticket_id ] = $fixture();
		$this->set_fn_return( 'wp_create_nonce', '33333333' );

		$metabox = tribe( Metabox::class );
		$panels  = $metabox->get_panels( $post_id, $ticket_id );
		$html    = implode( '', $panels );
		$html    = $this->placehold_post_ids(
			$html,
			[
				'post_id'   => $post_id,
				'ticket_id' => $ticket_id,
			]
		);
		// Depending on the Common versions, the assets might be loaded from ET or TEC; this should not break the tests.
		$html = str_replace( 'the-events-calendar/common', 'event-tickets/common', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function panels_with_no_provider_data_provider(): \Generator {
		yield 'post' => [
			static function () {
				return static::factory()->post->create( [ 'post_type' => 'post' ] );
			},
		];

		yield 'event' => [
			static function () {
				return tribe_events()->set_args(
					[
						'title'      => 'Test Event',
						'status'     => 'publish',
						'start_date' => '2022-10-01 10:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;
			},
		];
	}

	/**
	 * @dataProvider panels_with_no_provider_data_provider
	 */
	public function test_get_panels_with_no_providers( Closure $fixture ): void {
		// Equivalent to deactivating Commerce.
		add_filter( 'tribe_tickets_get_modules', '__return_empty_array' );
		$post_id = $fixture();
		$this->set_fn_return( 'wp_create_nonce', '33333333' );

		$metabox = tribe( Metabox::class );
		$panels  = $metabox->get_panels( $post_id );
		$html    = implode( '', $panels );
		$html    = $this->placehold_post_ids(
			$html,
			[
				'post_id' => $post_id,
			]
		);
		// Depending on the Common versions, the assets might be loaded from ET or TEC; this should not break the tests.
		$html = str_replace( 'the-events-calendar/common', 'event-tickets/common', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function tearDown() {
		parent::tearDown();
		uopz_unset_return( 'strtotime' );
		uopz_unset_return( Date_Utils::class, 'build_date_object' );
	}
}
