<?php

namespace TEC\Tickets\RSVP\V2;

use Closure;
use Generator;
use TEC\Common\Tests\Testcases\REST\TEC\V1\REST_Test_Case;
use TEC\Tickets\REST\TEC\V1\Endpoints\Ticket;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use TEC\Tickets\Commerce\Module as TC_Provider;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;

class RSVP_Ticket_REST_Snapshot_Test extends REST_Test_Case {
	use Ticket_Maker;
	use Order_Maker;

	protected $endpoint_class = Ticket::class;

	/**
	 * Creates test data: one published page with 5 tickets.
	 *
	 * 1. RSVP ticket (show_not_going=true, no attendees)
	 * 2. RSVP ticket (show_not_going=true, 2 going attendees)
	 * 3. RSVP ticket (show_not_going=true, 3 attendees: 2 going + 1 not-going)
	 * 4. RSVP ticket (show_not_going=false)
	 * 5. Regular TC ticket ($25)
	 *
	 * @return array{0: int[], 1: int[]} Array containing [post_ids, ticket_ids].
	 */
	private function create_test_data(): array {
		wp_set_current_user( 1 );

		$post_id = static::factory()->post->create(
			[
				'post_title'   => 'RSVP Event Page',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => 'Event with RSVP tickets',
			]
		);

		// 1. RSVP ticket with show_not_going=true, no attendees.
		$rsvp_no_attendees = $this->create_tc_rsvp_ticket( $post_id );
		update_post_meta( $rsvp_no_attendees, Constants::SHOW_NOT_GOING_META_KEY, '1' );

		// 2. RSVP ticket with show_not_going=true, 2 going attendees.
		$rsvp_going = $this->create_tc_rsvp_ticket( $post_id );
		update_post_meta( $rsvp_going, Constants::SHOW_NOT_GOING_META_KEY, '1' );
		$this->create_order( [ $rsvp_going => 2 ] );

		// 3. RSVP ticket with show_not_going=true, 2 going + 1 not-going.
		$rsvp_mixed = $this->create_tc_rsvp_ticket( $post_id );
		update_post_meta( $rsvp_mixed, Constants::SHOW_NOT_GOING_META_KEY, '1' );
		$this->create_order( [ $rsvp_mixed => 3 ] );
		$mixed_attendee_ids = tribe( 'tickets.attendee-repository.rsvp' )
			->by( 'event_id', $post_id )
			->by( 'ticket_id', $rsvp_mixed )
			->order_by( 'ID', 'DESC' )
			->get_ids();
		// Mark the most recent attendee as not going.
		update_post_meta( $mixed_attendee_ids[0], Constants::RSVP_STATUS_META_KEY, 'no' );

		// 4. RSVP ticket with show_not_going=false.
		$rsvp_disabled = $this->create_tc_rsvp_ticket( $post_id );
		update_post_meta( $rsvp_disabled, Constants::SHOW_NOT_GOING_META_KEY, '' );

		// 5. Regular TC ticket ($25).
		$regular_ticket = $this->create_tc_ticket( $post_id, 25 );

		// Flush caches to ensure fresh metadata.
		wp_cache_flush();

		wp_set_current_user( 0 );

		return [
			[ $post_id ],
			[ $rsvp_no_attendees, $rsvp_going, $rsvp_mixed, $rsvp_disabled, $regular_ticket ],
		];
	}

	public function test_get_formatted_entity(): void {
		[ $post_ids, $ticket_ids ] = $this->create_test_data();

		$data = [];
		foreach ( $ticket_ids as $ticket_id ) {
			$ticket_post = tec_tc_get_ticket( $ticket_id );
			$this->assertInstanceOf( WP_Post::class, $ticket_post );
			$data[] = $this->endpoint->get_formatted_entity( $ticket_post );
		}

		$json = wp_json_encode( $data, JSON_SNAPSHOT_OPTIONS );
		$json = str_replace( $post_ids, '{POST_ID}', $json );
		$json = str_replace( $ticket_ids, '{TICKET_ID}', $json );

		$this->assertMatchesJsonSnapshot( $json );
	}

	/**
	 * @dataProvider different_user_roles_provider
	 */
	public function test_read_responses( Closure $fixture ): void {
		if ( ! $this->is_readable() ) {
			return;
		}

		[ $post_ids, $ticket_ids ] = $this->create_test_data();
		$fixture();

		$responses = [];
		foreach ( $ticket_ids as $ticket_id ) {
			$responses[] = $this->assert_endpoint( '/tickets/' . $ticket_id );
		}

		$json = wp_json_encode( $responses, JSON_SNAPSHOT_OPTIONS );
		$json = str_replace( $post_ids, '{POST_ID}', $json );
		$json = str_replace( $ticket_ids, '{TICKET_ID}', $json );

		$this->assertMatchesJsonSnapshot( $json );
	}

	public function test_not_going_count_accuracy(): void {
		wp_set_current_user( 1 );

		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish', 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );
		update_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, '1' );

		$order_id = $this->create_order( [ $ticket_id => 4 ] )->ID;

		$tc_provider = tribe( TC_Provider::class );

		$attendee_ids = $tc_provider->get_attendees_by_order_id( $order_id );

		update_post_meta( $attendee_ids[0]['ID'], Constants::RSVP_STATUS_META_KEY, 'no' );
		update_post_meta( $attendee_ids[1]['ID'], Constants::RSVP_STATUS_META_KEY, 'no' );

		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );

		// Directly invoke the REST_Properties callback with fresh data to verify the count.
		$rest_properties = tribe( REST_Properties::class );
		$properties      = [
			'type' => Constants::TC_RSVP_TYPE,
		];

		wp_cache_flush_group( 'post-queries' );
		$result = $rest_properties->add_show_not_going_to_properties( $properties, get_post( $ticket_id ) );

		$this->assertArrayHasKey( 'not_going_count', $result );
		$this->assertEquals( 2, $result['not_going_count'] );
		$this->assertArrayHasKey( 'show_not_going', $result );
		$this->assertTrue( $result['show_not_going'] );

		$ticket = Tickets::load_ticket_object( $ticket_id );
		$this->assertEquals( 4, $ticket->qty_sold() );
	}
}
