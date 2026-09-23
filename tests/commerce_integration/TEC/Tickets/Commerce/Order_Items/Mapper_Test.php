<?php

namespace TEC\Tickets\Commerce\Order_Items;

use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Tickets\Commerce\Ticket;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Mapper_Test extends WPTestCase {
	use Ticket_Maker;

	private function fixture( string $name ): array {
		return include codecept_data_dir( "order-items/{$name}.php" );
	}

	private function round_trip( array $items, string $currency ): array {
		$mapper = tribe( Mapper::class );
		$rows   = [];

		foreach ( $items as $key => $item ) {
			$rows[] = $mapper->to_row( $key, $item, 1, $currency );
		}

		$rebuilt = [];

		foreach ( $rows as $row ) {
			[ $key, $item ]  = $mapper->to_item( $row );
			$rebuilt[ $key ] = $item;
		}

		return [ $rows, $rebuilt ];
	}

	public function fixtures_provider(): Generator {
		yield 'tickets only' => [ 'tickets', 'USD', 2 ];
		yield 'sale price' => [ 'sale-price', 'USD', 2 ];
		yield 'zero decimals' => [ 'jpy', 'JPY', 0 ];
		yield 'three decimals' => [ 'three-decimals', 'USD', 3 ];
		yield 'unknown key' => [ 'unknown-key', 'USD', 2 ];
		yield 'integer key and look-alike string key' => [ 'mixed-keys', 'USD', 2 ];
	}

	/**
	 * @dataProvider fixtures_provider
	 */
	public function test_items_survive_the_round_trip( string $fixture, string $currency, int $precision ): void {
		add_filter( 'tec_tickets_commerce_currency_precision', static fn() => $precision );
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

	public function test_precision_change_after_write_leaves_amounts_unchanged(): void {
		$items  = $this->fixture( 'sale-price' );
		$mapper = tribe( Mapper::class );
		$write  = static fn() => 2;
		add_filter( 'tec_tickets_commerce_currency_precision', $write );
		$row = $mapper->to_row( 0, $items[0], 1, 'USD' );
		remove_filter( 'tec_tickets_commerce_currency_precision', $write );

		add_filter( 'tec_tickets_commerce_currency_precision', static fn() => 0 );

		$this->assertSame( (int) round( $items[0]['price'] * 10 ** $write() ), $row['price'] );
		$this->assertSame( [ '0', $items[0] ], $mapper->to_item( $row ) );
	}
}
