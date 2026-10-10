<?php

namespace TEC\Tickets\Commerce\Order_Items;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

class Attendees_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	/**
	 * The Order Items kill switch; spelled out because this change does not depend on the Order Items controller.
	 */
	private const ORDER_ITEMS_DISABLED = 'TEC_TICKETS_COMMERCE_ORDER_ITEMS_DISABLED';

	public function tearDown(): void {
		putenv( self::ORDER_ITEMS_DISABLED );

		parent::tearDown();
	}

	public function order_items_switch_provider(): Generator {
		yield 'order items on' => [ static function (): void {} ];

		yield 'order items off by filter' => [
			static function (): void {
				add_filter( 'tec_tickets_commerce_order_items_active', '__return_false' );
			},
		];

		yield 'order items off by environment' => [
			static function (): void {
				putenv( self::ORDER_ITEMS_DISABLED . '=1' );
			},
		];
	}

	/**
	 * @dataProvider order_items_switch_provider
	 */
	public function test_it_stores_the_ticket_name_on_each_new_attendee( Closure $switch ): void {
		$switch();
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
}
