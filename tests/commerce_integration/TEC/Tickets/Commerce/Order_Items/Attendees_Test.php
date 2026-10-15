<?php

namespace TEC\Tickets\Commerce\Order_Items;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

class Attendees_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	public function test_it_stores_the_ticket_name_on_each_new_attendee(): void {
		$post_id     = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id_1 = $this->create_tc_ticket( $post_id, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post_id, 20, [ 'ticket_name' => 'Second ticket' ] );

		$order = $this->create_order( [ $ticket_id_1 => 2, $ticket_id_2 => 1 ] );

		$attendees = tec_tc_attendees()->by( 'parent', $order->ID )->by( 'status', 'any' )->all();
		$this->assertCount( 3, $attendees );

		foreach ( $attendees as $attendee ) {
			$ticket_id = (int) get_post_meta( $attendee->ID, '_tec_tickets_commerce_ticket', true );
			$this->assertSame(
				get_post_field( 'post_title', $ticket_id ),
				get_post_meta( $attendee->ID, Attendees::TICKET_NAME_META_KEY, true )
			);
		}
	}

	public function test_a_ticket_named_zero_is_stored(): void {
		$post_id   = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 10, [ 'ticket_name' => '0' ] );

		$order    = $this->create_order( [ $ticket_id => 1 ] );
		$attendee = tec_tc_attendees()->by( 'parent', $order->ID )->by( 'status', 'any' )->first();

		$this->assertSame( '0', get_post_meta( $attendee->ID, Attendees::TICKET_NAME_META_KEY, true ) );
	}

	public function test_the_commerce_provider_hooks_it_to_the_create_args_filter(): void {
		$ticket       = new Ticket_Object();
		$ticket->name = 'A ticket';
		$create_args  = [ 'title' => 'An attendee' ];

		$filtered = apply_filters( 'tec_tickets_commerce_attendee_create_args', $create_args, null, $ticket, [] );

		$this->assertSame( $ticket->name, $filtered[ Attendees::TICKET_NAME_META_KEY ] );
		$this->assertSame( $create_args['title'], $filtered['title'] );
	}

	public function test_it_is_hooked_once_with_the_same_callback_the_container_hands_out(): void {
		$callback = tribe()->callback( Attendees::class, 'add_ticket_name' );

		$this->assertSame( 10, has_filter( 'tec_tickets_commerce_attendee_create_args', $callback ) );
		$this->assertSame( $callback, tribe()->callback( Attendees::class, 'add_ticket_name' ) );
	}

	public function test_a_ticket_with_an_empty_name_stores_nothing(): void {
		$ticket       = new Ticket_Object();
		$ticket->name = '';
		$create_args  = [ 'title' => 'An attendee' ];

		$this->assertSame( $create_args, tribe( Attendees::class )->add_ticket_name( $create_args, null, $ticket ) );
	}

	public function test_it_leaves_create_args_it_cannot_use_untouched(): void {
		$ticket       = new Ticket_Object();
		$ticket->name = 'A ticket';

		$this->assertSame( 'not an array', tribe( Attendees::class )->add_ticket_name( 'not an array', null, $ticket ) );
		$this->assertSame( [ 'title' => 'An attendee' ], tribe( Attendees::class )->add_ticket_name( [ 'title' => 'An attendee' ], null, 'not a ticket' ) );
	}

	public function test_renaming_the_ticket_keeps_the_name_it_was_bought_under(): void {
		$post_id    = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id  = $this->create_tc_ticket( $post_id, 10 );
		$first_name = get_post_field( 'post_title', $ticket_id );

		$first_order = $this->create_order( [ $ticket_id => 1 ] );
		wp_update_post(
			[
				'ID'         => $ticket_id,
				'post_title' => $first_name . ' renamed',
			]
		);
		$second_order = $this->create_order( [ $ticket_id => 1 ] );

		$first_attendee  = tec_tc_attendees()->by( 'parent', $first_order->ID )->by( 'status', 'any' )->first();
		$second_attendee = tec_tc_attendees()->by( 'parent', $second_order->ID )->by( 'status', 'any' )->first();
		$this->assertSame( $first_name, get_post_meta( $first_attendee->ID, Attendees::TICKET_NAME_META_KEY, true ) );
		$this->assertSame(
			$first_name . ' renamed',
			get_post_meta( $second_attendee->ID, Attendees::TICKET_NAME_META_KEY, true )
		);
	}

	public function test_a_name_with_a_backslash_is_stored_as_typed(): void {
		$post_id   = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		// Saved slashed, as the editor's request is: wp_insert_post() unslashes, so a plain title would lose its backslash.
		wp_update_post( [ 'ID' => $ticket_id, 'post_title' => wp_slash( 'Ticket A \\o/' ) ] );

		$order    = $this->create_order( [ $ticket_id => 1 ] );
		$attendee = tec_tc_attendees()->by( 'parent', $order->ID )->by( 'status', 'any' )->first();

		$this->assertSame( 'Ticket A \\o/', get_post_meta( $attendee->ID, Attendees::TICKET_NAME_META_KEY, true ) );
	}

	public function test_a_ticket_with_a_non_string_name_stores_nothing(): void {
		$ticket       = new Ticket_Object();
		$ticket->name = [ 'not', 'a string' ];
		$create_args  = [ 'title' => 'An attendee' ];

		$this->assertSame( $create_args, tribe( Attendees::class )->add_ticket_name( $create_args, null, $ticket ) );
	}

	public function test_moving_an_attendee_stores_the_target_ticket_name(): void {
		$post_id     = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id_1 = $this->create_tc_ticket( $post_id, 10, [ 'ticket_name' => 'Ticket A' ] );
		$ticket_id_2 = $this->create_tc_ticket( $post_id, 10, [ 'ticket_name' => 'Ticket B \\o/' ] );
		$order       = $this->create_order( [ $ticket_id_1 => 1 ] );
		$attendee    = tec_tc_attendees()->by( 'parent', $order->ID )->by( 'status', 'any' )->first();

		do_action( 'tribe_tickets_ticket_moved', $attendee->ID, $ticket_id_1, $ticket_id_2, $post_id, $post_id, get_current_user_id() );

		$this->assertSame(
			get_post_field( 'post_title', $ticket_id_2 ),
			get_post_meta( $attendee->ID, Attendees::TICKET_NAME_META_KEY, true )
		);
	}

	public function test_moving_an_attendee_to_an_untitled_ticket_clears_the_stored_name(): void {
		$post_id     = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id_1 = $this->create_tc_ticket( $post_id, 10, [ 'ticket_name' => 'Ticket A' ] );
		$ticket_id_2 = $this->create_tc_ticket( $post_id, 10 );
		wp_update_post( [ 'ID' => $ticket_id_2, 'post_title' => '' ] );
		$order    = $this->create_order( [ $ticket_id_1 => 1 ] );
		$attendee = tec_tc_attendees()->by( 'parent', $order->ID )->by( 'status', 'any' )->first();

		do_action( 'tribe_tickets_ticket_moved', $attendee->ID, $ticket_id_1, $ticket_id_2, $post_id, $post_id, get_current_user_id() );

		$this->assertSame( '', get_post_meta( $attendee->ID, Attendees::TICKET_NAME_META_KEY, true ) );
	}

	public function test_moving_something_that_is_not_a_commerce_attendee_is_ignored(): void {
		$post_id   = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		tribe( Attendees::class )->update_ticket_name_on_move( $post_id, $ticket_id, $ticket_id );

		$this->assertSame( '', get_post_meta( $post_id, Attendees::TICKET_NAME_META_KEY, true ) );
	}
}
