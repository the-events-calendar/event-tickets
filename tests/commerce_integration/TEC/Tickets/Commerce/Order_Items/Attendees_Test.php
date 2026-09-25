<?php

namespace TEC\Tickets\Commerce\Order_Items;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Tickets;

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

	public function test_the_attendee_shows_the_live_ticket_title_while_the_ticket_exists(): void {
		$post_id   = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		$order     = $this->create_order( [ $ticket_id => 1 ] );
		$live_name = get_post_field( 'post_title', $ticket_id ) . ' renamed';
		wp_update_post(
			[
				'ID'         => $ticket_id,
				'post_title' => $live_name,
			]
		);

		$attendee_id = tec_tc_attendees()->by( 'parent', $order->ID )->by( 'status', 'any' )->first_id();
		$service     = tribe( Attendee::class );
		$loaded      = $service->load_attendee_data( get_post( $attendee_id ) );

		$this->assertSame( esc_html( $live_name ), $service->get_product_title( get_post( $attendee_id ) ) );
		$this->assertSame( esc_html( $live_name ), $loaded->ticket );
		$this->assertSame( esc_html( $live_name ), $loaded->ticket_name );
		$this->assertSame( $live_name, tribe( Module::class )->get_attendee( $attendee_id )['ticket'] );
		$this->assertSame( $live_name, \Tribe__Tickets__Tickets::get_event_attendees( $post_id )[0]['ticket'] );
	}

	public function missing_ticket_provider(): Generator {
		yield 'ticket post removed without the ticket delete action' => [
			static function ( int $post_id, int $ticket_id, int $attendee_id, string $bought_as ): string {
				wp_delete_post( $ticket_id, true );

				return $bought_as;
			},
		];

		yield 'ticket deleted through the ticket editor' => [
			static function ( int $post_id, int $ticket_id, int $attendee_id, string $bought_as ): string {
				wp_update_post(
					[
						'ID'         => $ticket_id,
						'post_title' => $bought_as . ' renamed',
					]
				);
				tribe( Ticket::class )->delete( $post_id, $ticket_id );

				return $bought_as;
			},
		];

		yield 'attendee created before the ticket name was stored' => [
			static function ( int $post_id, int $ticket_id, int $attendee_id, string $bought_as ): string {
				delete_post_meta( $attendee_id, Attendees::TICKET_NAME_META_KEY );
				wp_update_post(
					[
						'ID'         => $ticket_id,
						'post_title' => $bought_as . ' renamed',
					]
				);
				tribe( Ticket::class )->delete( $post_id, $ticket_id );

				return $bought_as . ' renamed';
			},
		];
	}

	/**
	 * @dataProvider missing_ticket_provider
	 */
	public function test_the_attendee_falls_back_to_a_stored_name_when_the_ticket_is_gone( Closure $remove_ticket ): void {
		$post_id     = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id   = $this->create_tc_ticket( $post_id, 10 );
		$bought_as   = get_post_field( 'post_title', $ticket_id );
		$order       = $this->create_order( [ $ticket_id => 1 ] );
		$attendee_id = tec_tc_attendees()->by( 'parent', $order->ID )->by( 'status', 'any' )->first_id();

		$expected = $remove_ticket( $post_id, $ticket_id, $attendee_id, $bought_as );

		$service  = tribe( Attendee::class );
		$loaded   = $service->load_attendee_data( get_post( $attendee_id ) );
		$listed   = \Tribe__Tickets__Tickets::get_event_attendees( $post_id );
		$modelled = tribe( Module::class )->get_attendee( $attendee_id );

		$this->assertSame( $expected, $service->get_product_title( get_post( $attendee_id ) ) );
		$this->assertSame( $expected, $loaded->ticket );
		$this->assertSame( $expected, $loaded->ticket_name );
		$this->assertSame( $expected . ' (deleted)', $modelled['ticket'] );
		$this->assertCount( 1, $listed );
		$this->assertSame( $expected . ' (deleted)', $listed[0]['ticket'] );
		$this->assertFalse( $listed[0]['ticket_exists'] );
	}

	public function my_tickets_provider(): Generator {
		yield 'ticket exists' => [
			static function ( int $ticket_id, int $attendee_id, string $bought_as ): string {
				return $bought_as;
			},
		];

		yield 'ticket exists with an empty title' => [
			static function ( int $ticket_id, int $attendee_id, string $bought_as ): string {
				wp_update_post( [ 'ID' => $ticket_id, 'post_title' => '' ] );

				return $bought_as;
			},
		];

		yield 'ticket gone, name stored at purchase' => [
			static function ( int $ticket_id, int $attendee_id, string $bought_as ): string {
				wp_delete_post( $ticket_id, true );

				return $bought_as;
			},
		];

		yield 'ticket gone, bought as a ticket named 0' => [
			static function ( int $ticket_id ): string {
				wp_delete_post( $ticket_id, true );

				return '0';
			},
			'0',
		];

		yield 'ticket gone, no name stored' => [
			static function ( int $ticket_id, int $attendee_id ): string {
				delete_post_meta( $attendee_id, Attendees::TICKET_NAME_META_KEY );
				wp_delete_post( $ticket_id, true );

				return '';
			},
		];
	}

	/**
	 * @dataProvider my_tickets_provider
	 */
	public function test_my_tickets_shows_the_plain_ticket_name( Closure $arrange, string $ticket_name = '' ): void {
		$post_id     = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id   = $this->create_tc_ticket( $post_id, 10, '' === $ticket_name ? [] : [ 'ticket_name' => $ticket_name ] );
		$bought_as   = get_post_field( 'post_title', $ticket_id );
		$order       = $this->create_order( [ $ticket_id => 1 ] );
		$attendee_id = tec_tc_attendees()->by( 'parent', $order->ID )->by( 'status', 'any' )->first_id();

		$expected = $arrange( $ticket_id, $attendee_id, $bought_as );

		$attendee = Tribe__Tickets__Tickets::get_event_attendees( $post_id )[0];
		$html     = tribe( 'tickets.editor.template' )->template(
			'tickets/my-tickets/ticket-information',
			[
				'attendee' => $attendee,
				'provider' => null,
			],
			false
		);

		$this->assertStringNotContainsString( '(deleted)', $html );
		if ( '' !== $expected ) {
			$this->assertStringContainsString( '<span class="ticket-name">' . esc_html( $expected ) . '</span>', $html );
		} else {
			$this->assertStringNotContainsString( 'ticket-name', $html );
		}
	}
}
