<?php

namespace Tribe\Tickets;

use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Attendees as Attendees;
use \Tribe__Tickets__Global_Stock as Global_Stock;

class AttendeesTest extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;

	/**
	 * @before
	 */
	public function ensure_posts_are_ticketable(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	public function get_render_context_data_provider(): \Generator {
		yield 'post without tickets' => [
			static function (): array {
				$post_id = static::factory()->post->create();

				return [
					$post_id,
					[
						'event_id'          => $post_id,
						'tickets_by_type'   =>
							[
								'rsvp'    =>
									[
									],
								'default' =>
									[
									],
							],
						'ticket_totals'     =>
							[
								'sold'      => 0,
								'available' => 0,
							],
						'type_icon_classes' =>
							[
								'default' => 'tec-tickets__admin-attendees-overview-ticket-type-icon--ticket',
								'rsvp'    => 'tec-tickets__admin-attendees-overview-ticket-type-icon--rsvp',
							],
						'type_labels'       =>
							[
								'default' => 'Standard Tickets',
								'rsvp'    => 'RSVPs',
							],
					]
				];
			}
		];

		yield 'post with an unlimited ticket' => [
			function (): array {
				$post_id                = static::factory()->post->create();
				$own_capacity_ticket    = $this->create_tc_ticket( $post_id, 1, [
					'tribe-ticket' => [
						'mode'     => Global_Stock::OWN_STOCK_MODE,
						'capacity' => 17,
					],
				] );
				$capped_capacity_ticket = $this->create_tc_ticket( $post_id, 1, [
					'tribe-ticket' => [
						'mode'           => Global_Stock::CAPPED_STOCK_MODE,
						'event_capacity' => 89,
						'capacity'       => 23,
					],
				] );
				$shared_capacity_ticket = $this->create_tc_ticket( $post_id, 1, [
					'tribe-ticket' => [
						'mode'           => Global_Stock::GLOBAL_STOCK_MODE,
						'event_capacity' => 89,
						'capacity'       => 89,
					],
				] );
				$unlimited_ticket       = $this->create_tc_ticket( $post_id, 1, [
					'tribe-ticket' => [
						'mode' => '',
					]
				] );

				return [
					$post_id,
					[
						'event_id'          => $post_id,
						'tickets_by_type'   =>
							[
								'rsvp'    =>
									[
									],
								'default' => Module::get_instance()->get_tickets( $post_id ),
							],
						'ticket_totals'     =>
							[
								'sold'      => 0,
								'available' => -1,
							],
						'type_icon_classes' =>
							[
								'default' => 'tec-tickets__admin-attendees-overview-ticket-type-icon--ticket',
								'rsvp'    => 'tec-tickets__admin-attendees-overview-ticket-type-icon--rsvp',
							],
						'type_labels'       =>
							[
								'default' => 'Standard Tickets',
								'rsvp'    => 'RSVPs',
							],
					]
				];
			}
		];

		yield 'post with shared, capped and own capacity tickets' => [
			function () {
				$post_id                = static::factory()->post->create();
				$own_capacity_ticket    = $this->create_tc_ticket( $post_id, 1, [
					'tribe-ticket' => [
						'mode'     => Global_Stock::OWN_STOCK_MODE,
						'capacity' => 17,
					],
				] );
				$capped_capacity_ticket = $this->create_tc_ticket( $post_id, 1, [
					'tribe-ticket' => [
						'mode'           => Global_Stock::CAPPED_STOCK_MODE,
						'event_capacity' => 89,
						'capacity'       => 23,
					],
				] );

				$shared_capacity_ticket = $this->create_tc_ticket( $post_id, 1, [
					'tribe-ticket' => [
						'mode'           => Global_Stock::GLOBAL_STOCK_MODE,
						'event_capacity' => 89,
						'capacity'       => 89,
					],
				] );

				return [
					$post_id,
					[
						'event_id'          => $post_id,
						'tickets_by_type'   =>
							[
								'rsvp'    =>
									[
									],
								'default' => Module::get_instance()->get_tickets( $post_id ),
							],
						'ticket_totals'     =>
							[
								'sold'      => 0,
								'available' => 89 + 17,
							],
						'type_icon_classes' =>
							[
								'default' => 'tec-tickets__admin-attendees-overview-ticket-type-icon--ticket',
								'rsvp'    => 'tec-tickets__admin-attendees-overview-ticket-type-icon--rsvp',
							],
						'type_labels'       =>
							[
								'default' => 'Standard Tickets',
								'rsvp'    => 'RSVPs',
							],
					]
				];
			}
		];

		yield 'post with shared, capped, and own capacity from different posts' => [

			function () {
				$post_id                = static::factory()->post->create();
				$own_capacity_ticket    = $this->create_tc_ticket( $post_id, 1, [
					'tribe-ticket' => [
						'mode'     => Global_Stock::OWN_STOCK_MODE,
						'capacity' => 17,
					],
				] );
				$capped_capacity_ticket = $this->create_tc_ticket( $post_id, 1, [
					'tribe-ticket' => [
						'mode'           => Global_Stock::CAPPED_STOCK_MODE,
						'event_capacity' => 89,
						'capacity'       => 23,
					],
				] );
				$shared_capacity_ticket = $this->create_tc_ticket( $post_id, 1, [
					'tribe-ticket' => [
						'mode'           => Global_Stock::GLOBAL_STOCK_MODE,
						'event_capacity' => 89,
						'capacity'       => 89,
					],
				] );

				// Create a second post with tickets.
				$post_id2                = static::factory()->post->create();
				$own_capacity_ticket2    = $this->create_tc_ticket( $post_id2, 1, [
					'tribe-ticket' => [
						'mode'     => Global_Stock::OWN_STOCK_MODE,
						'capacity' => 19,
					],
				] );
				$shared_capacity_ticket2 = $this->create_tc_ticket( $post_id2, 1, [
					'tribe-ticket' => [
						'mode'           => Global_Stock::GLOBAL_STOCK_MODE,
						'event_capacity' => 29,
						'capacity'       => 29,
					],
				] );
				$capped_capacity_ticket2 = $this->create_tc_ticket( $post_id2, 1, [
					'tribe-ticket' => [
						'mode'           => Global_Stock::CAPPED_STOCK_MODE,
						'event_capacity' => 29,
						'capacity'       => 11,
					],
				] );

				// Make sure the tickets from the second post are included among the ones for the first post.
				add_filter( 'tec_tickets_repository_filter_by_event_id', fn() => [ $post_id, $post_id2 ] );

				return [
					$post_id,
					[
						'event_id'          => $post_id,
						'tickets_by_type'   =>
							[
								'rsvp'    =>
									[
									],
								'default' => Module::get_instance()->get_tickets( $post_id ),
							],
						'ticket_totals'     =>
							[
								'sold'      => 0,
								'available' => 89 + 17 + 19 + 29,
							],
						'type_icon_classes' =>
							[
								'default' => 'tec-tickets__admin-attendees-overview-ticket-type-icon--ticket',
								'rsvp'    => 'tec-tickets__admin-attendees-overview-ticket-type-icon--rsvp',
							],
						'type_labels'       =>
							[
								'default' => 'Standard Tickets',
								'rsvp'    => 'RSVPs',
							],
					]
				];
			}
		];
	}

	/**
	 * @dataProvider  get_render_context_data_provider
	 */
	public function test_render_context_for_tickets( \Closure $fixture ): void {
		[ $post_id, $expected ] = $fixture();

		$attendees      = new Attendees();
		$render_context = $attendees->get_render_context( $post_id );

		$this->assertInstanceOf( Attendees::class, $render_context['attendees'] );
		unset( $render_context['attendees'] );
		$this->assertEquals( $expected, $render_context );
	}
}
