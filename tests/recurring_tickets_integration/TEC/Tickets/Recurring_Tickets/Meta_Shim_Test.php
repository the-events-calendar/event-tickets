<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Tickets_Repository;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * Post meta of a table ticket ID comes from its row, or its template for anything else, and never from wp_postmeta.
 */
class Meta_Shim_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_answer_the_row_keys_from_the_row(): void {
		$event = $this->create_recurring_event();
		$date  = $this->get_dates( $event )[1];
		$id    = $this->insert_ticket_row(
			[
				'post_id'          => $event,
				'occurrence_id'    => $date->occurrence_id,
				'sku'              => 'ROW-SKU',
				'price'            => 10500,
				'capacity'         => 100,
				'stock'            => 97,
				'sales'            => 3,
				'start_date'       => '2026-10-01 09:00:00',
				'end_date'         => '2026-12-01 18:30:00',
				'show_description' => 1,
			]
		);

		$expected = [
			'_price'                         => '10.50',
			'_tribe_ticket_capacity'         => '100',
			'_manage_stock'                  => 'yes',
			'_stock'                         => '97',
			'_global_stock_mode'             => 'own',
			'total_sales'                    => '3',
			'_type'                          => 'recurring',
			'_sku'                           => 'ROW-SKU',
			'_tec_tickets_commerce_event'    => (string) $date->provisional_id,
			'_ticket_start_date'             => '2026-10-01',
			'_ticket_start_time'             => '09:00:00',
			'_ticket_end_date'               => '2026-12-01',
			'_ticket_end_time'               => '18:30:00',
			'_tribe_ticket_show_description' => 'yes',
		];

		foreach ( $expected as $key => $value ) {
			$this->assertSame( $value, get_post_meta( $id, $key, true ), $key );
			$this->assertSame( [ $value ], get_post_meta( $id, $key ), $key );
			$this->assertTrue( metadata_exists( 'post', $id, $key ), $key );
		}
	}

	/**
	 * @test
	 */
	public function it_should_answer_an_unlimited_row_as_tickets_commerce_stores_one(): void {
		$id = $this->insert_ticket_row( [ 'capacity' => -1, 'stock' => null ] );

		$this->assertSame( '-1', get_post_meta( $id, '_tribe_ticket_capacity', true ) );
		$this->assertSame( 'no', get_post_meta( $id, '_manage_stock', true ) );
		$this->assertSame( '', get_post_meta( $id, '_stock', true ) );
		$this->assertSame( [], get_post_meta( $id, '_stock' ) );
		$this->assertFalse( metadata_exists( 'post', $id, '_stock' ) );
		$this->assertFalse( metadata_exists( 'post', $id, '_global_stock_mode' ) );
	}

	/**
	 * @test
	 */
	public function it_should_answer_any_other_key_from_the_template(): void {
		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 20 );
		$fields   = [ [ 'type' => 'text', 'label' => 'Shirt size', 'slug' => 'shirt-size' ] ];
		update_post_meta( $template, '_tribe_tickets_meta', $fields );
		$id = $this->insert_ticket_row( [ 'parent_id' => $template, 'post_id' => $event ] );

		$this->assertSame( $fields, get_post_meta( $id, '_tribe_tickets_meta', true ) );
		$this->assertTrue( metadata_exists( 'post', $id, '_tribe_tickets_meta' ) );
		$this->assertSame( '', get_post_meta( $id, '_not_on_the_template', true ) );
		$this->assertSame( [], get_post_meta( $id, '_not_on_the_template' ) );
		$this->assertFalse( metadata_exists( 'post', $id, '_not_on_the_template' ) );
	}

	/**
	 * @test
	 */
	public function it_should_answer_the_iac_setting_from_the_row_and_otherwise_from_the_template(): void {
		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 20 );
		update_post_meta( $template, '_tribe_tickets_ar_iac', 'allowed' );
		$dates = $this->get_dates( $event );
		$own   = $this->insert_ticket_row( [ 'parent_id' => $template, 'post_id' => $event, 'occurrence_id' => $dates[0]->occurrence_id, 'iac_settings' => [ 'iac' => 'required' ] ] );
		$none  = $this->insert_ticket_row( [ 'parent_id' => $template, 'post_id' => $event, 'occurrence_id' => $dates[1]->occurrence_id ] );

		$this->assertSame( 'required', get_post_meta( $own, '_tribe_tickets_ar_iac', true ) );
		$this->assertSame( 'allowed', get_post_meta( $none, '_tribe_tickets_ar_iac', true ) );
	}

	/**
	 * @test
	 */
	public function it_should_merge_the_template_and_the_row_when_no_key_is_given(): void {
		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 20 );
		update_post_meta( $template, '_tribe_tickets_meta', [ 'from' => 'template' ] );
		$id = $this->insert_ticket_row( [ 'parent_id' => $template, 'post_id' => $event, 'price' => 10500 ] );

		$all = get_post_meta( $id );

		$this->assertSame( [ '10.50' ], $all['_price'] );
		$this->assertSame( [ 'recurring' ], $all['_type'] );
		$this->assertSame( [ maybe_serialize( [ 'from' => 'template' ] ) ], $all['_tribe_tickets_meta'] );
	}

	/**
	 * @test
	 */
	public function it_should_answer_nothing_for_a_table_ticket_id_without_a_row(): void {
		$id = Ticket_ID::from_row_id( 999 );

		$this->assertSame( '', get_post_meta( $id, '_price', true ) );
		$this->assertSame( [], get_post_meta( $id ) );
	}

	/**
	 * @test
	 */
	public function it_should_read_a_table_tickets_meta_with_the_one_query_for_its_row(): void {
		$id          = $this->insert_ticket_row();
		$rows        = $this->count_queries( Tickets::table_name() );
		$post_meta   = $this->count_queries( 'postmeta' );
		$occurrences = $this->count_queries( 'occurrences' );

		get_post_meta( $id, '_price', true );
		get_post_meta( $id, '_stock', true );
		metadata_exists( 'post', $id, '_manage_stock' );

		$this->assertSame( 1, $rows(), 'The row is read once.' );
		$this->assertSame( 0, $post_meta(), 'A table ticket ID has no post meta to read.' );
		$this->assertSame( 0, $occurrences(), 'ECP must not look the ID up as a date.' );
	}

	/**
	 * @test
	 */
	public function it_should_add_no_query_to_a_post_meta_read(): void {
		$post = static::factory()->post->create();
		update_post_meta( $post, '_price', '5' );
		get_post_meta( $post, '_price', true );
		$queries = $this->count_queries( '' );

		$this->assertSame( '5', get_post_meta( $post, '_price', true ) );
		$this->assertSame( 0, $queries() );
	}

	/**
	 * @test
	 */
	public function it_should_answer_an_override_on_the_next_read(): void {
		$id = $this->insert_ticket_row( [ 'price' => 10500 ] );
		get_post_meta( $id, '_price', true );

		tribe( Tickets_Repository::class )->override( Ticket_ID::to_row_id( $id ), [ 'price' => 15000 ] );

		$this->assertSame( '15.00', get_post_meta( $id, '_price', true ) );
	}

	/**
	 * Counts the queries run from now on whose text contains a string.
	 *
	 * @param string $contains The string, empty for every query.
	 *
	 * @return callable(): int Returns the count so far.
	 */
	private function count_queries( string $contains ): callable {
		$count = 0;
		add_filter(
			'query',
			static function ( string $query ) use ( &$count, $contains ) {
				if ( '' === $contains || false !== strpos( $query, $contains ) ) {
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
