<?php

namespace TEC\Tickets\Commerce\Order_Items\Line_Items;

use Codeception\TestCase\WPTestCase;
use Generator;
use stdClass;
use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Commerce\Ticket;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Line_Item_Types_Test extends WPTestCase {
	use Ticket_Maker;

	private $decimals;

	public function setUp(): void {
		parent::setUp();
		$this->decimals = tribe_get_option( Settings::$option_currency_number_of_decimals, null );
	}

	// Restores before the parent's rollback: Tribe keeps options in memory, and after the rollback there is nothing to write.
	public function tearDown(): void {
		if ( null === $this->decimals ) {
			tribe_remove_option( Settings::$option_currency_number_of_decimals );
		} else {
			tribe_update_option( Settings::$option_currency_number_of_decimals, $this->decimals );
		}
		parent::tearDown();
	}

	private function fixture( string $name ): array {
		return include codecept_data_dir( "order-items/{$name}.php" );
	}

	private function round_trip( array $items, string $currency ): array {
		$types = tribe( Line_Item_Types::class );
		$rows  = [];

		foreach ( $items as $key => $item ) {
			$rows[] = $types->get_for_item( $item )->to_row( $key, $item, 1, $currency );
		}

		$rebuilt = [];

		foreach ( $rows as $row ) {
			[ $key, $item ]  = $types->get( $row['type'] )->from_row( $row );
			$rebuilt[ $key ] = $item;
		}

		return [ $rows, $rebuilt ];
	}

	public function fixtures_provider(): Generator {
		yield 'tickets only' => [ 'tickets', 'USD', 2 ];
		yield 'sale price' => [ 'sale-price', 'USD', 2 ];
		yield 'zero decimals' => [ 'jpy', 'JPY', 0 ];
		yield 'unknown key' => [ 'unknown-key', 'USD', 2 ];
		yield 'integer key and look-alike string key' => [ 'mixed-keys', 'USD', 2 ];
	}

	/**
	 * @dataProvider fixtures_provider
	 */
	public function test_items_survive_the_round_trip( string $fixture, string $currency, int $precision ): void {
		$items = $this->fixture( $fixture );

		[ $rows, $rebuilt ] = $this->round_trip( $items, $currency );

		$this->assertSame( $items, $rebuilt );

		foreach ( array_values( $items ) as $i => $item ) {
			$this->assertSame( (int) round( $item['price'] * 10 ** $precision ), $rows[ $i ]['price'] );
			$this->assertSame( (int) round( $item['sub_total'] * 10 ** $precision ), $rows[ $i ]['sub_total'] );
		}
	}

	public function test_row_records_purchase_time_details_and_event_defaults(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Purchase time event',
				'start_date' => '2030-01-01 10:00:00',
				'duration'   => HOUR_IN_SECONDS,
				'status'     => 'publish',
			]
		)->create()->ID;
		$ticket_id = $this->create_tc_ticket( $event_id, 10.5 );
		$ticket    = tribe( Ticket::class )->get_ticket( $ticket_id );
		$item      = array_merge(
			$this->fixture( 'tickets' )[0],
			[
				'event_id'  => $event_id,
				'ticket_id' => $ticket_id,
			]
		);

		[ $rows, $rebuilt ] = $this->round_trip( [ $item ], 'EUR' );

		$this->assertSame( [ $item ], $rebuilt );
		$this->assertSame( $event_id, $rows[0]['post_id'] );
		$this->assertNull( $rows[0]['occurrence_id'] );
		$this->assertSame( 0, $rows[0]['modifier_id'] );
		$this->assertSame( 0, $rows[0]['purchase_rule_id'] );
		$this->assertSame( 'EUR', $rows[0]['currency'] );
		$this->assertSame( '0', $rows[0]['item_key'] );
		$this->assertSame( $ticket->name, $rows[0]['name'] );
		$this->assertSame( $ticket->sku, $rows[0]['sku'] );
		$this->assertSame( $ticket->type(), $rows[0]['ticket_type'] );
		$this->assertSame( 'Purchase time event', $rows[0]['event_title'] );
		$this->assertSame( get_post_meta( $event_id, '_EventStartDate', true ), $rows[0]['event_start_date'] );
		$this->assertSame( get_post_meta( $event_id, '_EventStartDateUTC', true ), $rows[0]['event_start_date_utc'] );
	}

	public function test_usd_amounts_with_three_decimals_are_stored_in_cents_and_round_trip_exactly(): void {
		// The site's decimals setting lets a USD order carry amounts USD itself cannot hold.
		tribe_update_option( Settings::$option_currency_number_of_decimals, 3 );
		$items = $this->fixture( 'three-decimals' );

		[ $rows, $rebuilt ] = $this->round_trip( $items, 'USD' );

		$this->assertSame( $items, $rebuilt );
		$this->assertSame( (int) round( $items[0]['price'] * 100 ), $rows[0]['price'] );
		$this->assertSame( (int) round( $items[0]['sub_total'] * 100 ), $rows[0]['sub_total'] );
		$this->assertSame( $items[0]['price'], json_decode( $rows[0]['extra'], true )['raw']['price'] );
	}

	public function test_three_decimal_currency_is_stored_in_thousandths(): void {
		add_filter(
			'tec_tickets_commerce_default_currency_map',
			static fn( $map ) => $map + [ 'KWD' => [ 'decimal_precision' => 3 ] ]
		);
		$items = $this->fixture( 'three-decimals' );

		[ $rows, $rebuilt ] = $this->round_trip( $items, 'KWD' );

		$this->assertSame( $items, $rebuilt );
		$this->assertSame( (int) round( $items[0]['price'] * 1000 ), $rows[0]['price'] );
		$this->assertSame( (int) round( $items[0]['sub_total'] * 1000 ), $rows[0]['sub_total'] );
		$this->assertSame( [], json_decode( $rows[0]['extra'], true )['raw'] );
	}

	public function test_the_site_decimals_setting_does_not_change_stored_minor_units(): void {
		$items  = $this->fixture( 'sale-price' );
		$ticket = tribe( Line_Item_Types::class )->get( 'ticket' );
		$stored = [];

		foreach ( [ 0, 2, 3 ] as $decimals ) {
			tribe_update_option( Settings::$option_currency_number_of_decimals, $decimals );
			$row                 = $ticket->to_row( 0, $items[0], 1, 'USD' );
			$stored[ $decimals ] = [ $row['price'], $row['regular_price'], $row['sub_total'], $row['regular_sub_total'] ];

			$this->assertSame( [ '0', $items[0] ], $ticket->from_row( $row ) );
		}

		$cents = [
			(int) round( $items[0]['price'] * 100 ),
			(int) round( $items[0]['regular_price'] * 100 ),
			(int) round( $items[0]['sub_total'] * 100 ),
			(int) round( $items[0]['regular_sub_total'] * 100 ),
		];

		$this->assertSame( [ 0 => $cents, 2 => $cents, 3 => $cents ], $stored );
	}

	public function registered_types_provider(): Generator {
		yield 'ticket' => [ 'ticket', Ticket_Line_Item::class ];
		yield 'unregistered type' => [ 'membership', Generic_Line_Item::class ];
		yield 'no type' => [ '', Generic_Line_Item::class ];
	}

	/**
	 * @dataProvider registered_types_provider
	 */
	public function test_it_returns_the_class_registered_for_a_type( string $type, string $expected ): void {
		$this->assertSame( $expected, get_class( tribe( Line_Item_Types::class )->get( $type ) ) );
	}

	public function test_item_without_a_string_type_gets_the_generic_class(): void {
		$types = tribe( Line_Item_Types::class );

		$this->assertInstanceOf( Generic_Line_Item::class, $types->get_for_item( [ 'ticket_id' => 1 ] ) );
		$this->assertInstanceOf( Generic_Line_Item::class, $types->get_for_item( [ 'type' => null ] ) );
		$this->assertInstanceOf( Ticket_Line_Item::class, $types->get_for_item( [ 'type' => 'ticket' ] ) );
	}

	public function test_a_filtered_type_gets_its_class(): void {
		add_filter(
			'tec_tickets_commerce_order_items_line_item_types',
			static fn( $types ) => $types + [ 'membership' => Ticket_Line_Item::class ]
		);

		$this->assertSame( Ticket_Line_Item::class, get_class( tribe( Line_Item_Types::class )->get( 'membership' ) ) );
	}

	public function invalid_entries_provider(): Generator {
		yield 'built-in type, not a line item type class' => [ 'ticket', stdClass::class, Ticket_Line_Item::class ];
		yield 'built-in type, missing class' => [ 'ticket', 'Not_A_Class', Ticket_Line_Item::class ];
		yield 'built-in type, an instance' => [ 'ticket', new stdClass(), Ticket_Line_Item::class ];
		yield 'new type, not a line item type class' => [ 'membership', stdClass::class, Generic_Line_Item::class ];
	}

	/**
	 * @dataProvider invalid_entries_provider
	 */
	public function test_an_invalid_filtered_entry_is_ignored( string $type, $entry, string $expected ): void {
		add_filter(
			'tec_tickets_commerce_order_items_line_item_types',
			static fn( $types ) => array_merge( $types, [ $type => $entry ] )
		);

		$this->assertSame( $expected, get_class( tribe( Line_Item_Types::class )->get( $type ) ) );
	}

	public function test_a_filter_returning_no_list_keeps_the_built_in_types(): void {
		add_filter( 'tec_tickets_commerce_order_items_line_item_types', '__return_null' );

		$this->assertSame( Ticket_Line_Item::class, get_class( tribe( Line_Item_Types::class )->get( 'ticket' ) ) );
	}
}
