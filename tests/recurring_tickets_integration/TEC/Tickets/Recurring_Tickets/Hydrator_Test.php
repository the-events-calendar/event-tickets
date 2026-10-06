<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Tickets_Repository;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * A row becomes the ticket object Event Tickets expects, built from the row alone and cached.
 */
class Hydrator_Test extends WPTestCase {
	use Ticket_Rows;

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_build_the_ticket_from_the_row(): void {
		$event = $this->create_recurring_event();
		$date  = $this->get_dates( $event )[1];
		$id    = $this->insert_ticket_row(
			[
				'parent_id'        => 99,
				'post_id'          => $event,
				'occurrence_id'    => $date->occurrence_id,
				'name'             => 'Morning class',
				'description'      => 'Bring a mat.',
				'show_description' => 0,
				'sku'              => 'MORNING',
				'price'            => 10500,
				'capacity'         => 100,
				'stock'            => 97,
				'sales'            => 3,
				'start_date'       => '2026-10-01 09:00:00',
				'end_date'         => '2026-12-01 18:30:00',
				'menu_order'       => 2,
			]
		);

		$ticket = tribe( Hydrator::class )->load( $id );

		$this->assertInstanceOf( Ticket_Object::class, $ticket );
		$this->assertSame( $id, $ticket->ID );
		$this->assertSame( 'Morning class', $ticket->name );
		$this->assertSame( 'Bring a mat.', $ticket->description );
		$this->assertFalse( $ticket->show_description );
		$this->assertSame( 'MORNING', $ticket->sku );
		$this->assertSame( '10.50', $ticket->price );
		$this->assertSame( '10.50', $ticket->regular_price );
		$this->assertSame( 2, $ticket->menu_order );
		$this->assertSame( Module::class, $ticket->provider_class );
		$this->assertTrue( $ticket->manage_stock() );
		$this->assertSame( 100, $ticket->capacity() );
		$this->assertSame( 97, $ticket->stock() );
		$this->assertSame( 3, $ticket->qty_sold() );
		$this->assertSame( 0, $ticket->qty_pending() );
		$this->assertSame( '2026-10-01', $ticket->start_date );
		$this->assertSame( '09:00:00', $ticket->start_time );
		$this->assertSame( '2026-12-01', $ticket->end_date );
		$this->assertSame( '18:30:00', $ticket->end_time );
		$this->assertSame( $date->provisional_id, $ticket->get_event_id() );
	}

	/**
	 * @test
	 */
	public function it_should_build_an_unlimited_ticket(): void {
		$id = $this->insert_ticket_row( [ 'capacity' => -1, 'stock' => null ] );

		$ticket = tribe( Hydrator::class )->load( $id );

		$this->assertFalse( $ticket->manage_stock() );
		$this->assertSame( -1, $ticket->available() );
		$this->assertSame( -1, $ticket->stock() );
	}

	/**
	 * @test
	 */
	public function it_should_load_nothing_for_a_table_ticket_id_without_a_row(): void {
		$this->assertNull( tribe( Hydrator::class )->load( Ticket_ID::from_row_id( 999 ) ) );
	}

	/**
	 * @test
	 */
	public function it_should_load_nothing_for_the_base_id(): void {
		$this->assertNull( tribe( Hydrator::class )->load( Ticket_ID::base() ) );
	}

	/**
	 * @test
	 */
	public function it_should_store_prices_in_thousandths_whatever_the_currency(): void {
		$id       = $this->insert_ticket_row( [ 'price' => 10000 ] );
		$currency = Currency::get_currency_code();
		tribe_update_option( Currency::$currency_code_option, 'JPY' );

		try {
			$price = tribe( Hydrator::class )->load( $id )->price;
		} finally {
			tribe_update_option( Currency::$currency_code_option, $currency );
		}

		// Ten of the currency's unit, as the template's decimal price says, not a thousand.
		$this->assertSame( '10', $price );
	}

	/**
	 * @test
	 */
	public function it_should_let_add_ons_extend_the_ticket_as_for_a_ticket_post(): void {
		$id = $this->insert_ticket_row();
		add_filter(
			'tec_tickets_commerce_get_ticket_legacy',
			static function ( $ticket, $event_id, $ticket_id ) use ( $id ) {
				if ( $id === $ticket_id ) {
					$ticket->iac = 'required';
				}

				return $ticket;
			},
			10,
			3
		);

		$this->assertSame( 'required', tribe( Hydrator::class )->load( $id )->iac );
	}

	/**
	 * @test
	 */
	public function it_should_point_at_the_event_post_when_dates_are_not_available(): void {
		$event = $this->create_recurring_event();
		$id    = $this->insert_ticket_row( [ 'post_id' => $event, 'occurrence_id' => $this->get_dates( $event )[0]->occurrence_id ] );

		// As when ECP is deactivated: its custom tables never finish activating, so dates have no IDs.
		global $wp_actions;
		$fired = $wp_actions['tec_events_pro_custom_tables_v1_fully_activated'] ?? null;
		unset( $wp_actions['tec_events_pro_custom_tables_v1_fully_activated'] );

		try {
			$ticket = tribe( Hydrator::class )->load( $id );
		} finally {
			if ( null !== $fired ) {
				$wp_actions['tec_events_pro_custom_tables_v1_fully_activated'] = $fired;
			}
		}

		$this->assertSame( $event, $ticket->get_event_id() );
	}

	/**
	 * @test
	 */
	public function it_should_tell_whether_a_date_is_in_the_sale_window(): void {
		$id = $this->insert_ticket_row(
			[
				'start_date' => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
				'end_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '+1 day' ) ),
			]
		);

		$ticket = tribe( Hydrator::class )->load( $id );

		$this->assertTrue( $ticket->date_in_range( 'now' ) );
		$this->assertFalse( $ticket->date_in_range( '+3 days' ) );
	}

	/**
	 * @test
	 */
	public function it_should_query_the_table_once_for_two_loads(): void {
		$id      = $this->insert_ticket_row();
		$queries = $this->count_selects();

		tribe( Hydrator::class )->load( $id );
		tribe( Hydrator::class )->load( $id );

		$this->assertSame( 1, $queries() );
	}

	/**
	 * @test
	 */
	public function it_should_hydrate_a_row_in_hand_without_a_query(): void {
		$id      = $this->insert_ticket_row( [ 'name' => 'In hand' ] );
		$row     = Tickets::get_by_id( Ticket_ID::to_row_id( $id ) );
		$queries = $this->count_selects();

		$ticket = tribe( Hydrator::class )->hydrate( $row );

		$this->assertSame( 'In hand', $ticket->name );
		$this->assertSame( 0, $queries() );
	}

	/**
	 * @test
	 */
	public function it_should_see_an_override_on_the_next_load(): void {
		$id = $this->insert_ticket_row();
		tribe( Hydrator::class )->load( $id );

		tribe( Tickets_Repository::class )->override( Ticket_ID::to_row_id( $id ), [ 'price' => 15000 ] );

		$this->assertSame( '15.00', tribe( Hydrator::class )->load( $id )->price );
	}

	/**
	 * Counts the select queries run against the table from now on.
	 *
	 * @return callable(): int Returns the count so far.
	 */
	private function count_selects(): callable {
		$count = 0;
		add_filter(
			'query',
			static function ( string $query ) use ( &$count ) {
				if ( 0 === stripos( ltrim( $query ), 'SELECT' ) && false !== strpos( $query, Tickets::table_name() ) ) {
					++$count;
				}

				return $query;
			}
		);

		return static function () use ( &$count ): int {
			return $count;
		};
	}
}
