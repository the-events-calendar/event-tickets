<?php
namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Ticket_Object;
use DateTime;

class TicketTest extends WPTestCase {
	use Ticket_Maker;

	public function is_on_sale_provider(): \Generator {
		yield 'regular ticket => false' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 1 );

				return [
					$post_id,
					$ticket_id,
					false,
				];
			},
		];

		yield 'ticket with sale price => true' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 20 );

				update_post_meta( $ticket_id, Ticket::$sale_price_checked_key, true );
				update_post_meta( $ticket_id, Ticket::$sale_price_key, 10 );

				return [
					$post_id,
					$ticket_id,
					true,
				];
			},
		];

		yield 'ticket has sale price but inactive sale check => false' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 20 );

				update_post_meta( $ticket_id, Ticket::$sale_price_checked_key, false );
				update_post_meta( $ticket_id, Ticket::$sale_price_key, 10 );

				return [
					$post_id,
					$ticket_id,
					false,
				];
			},
		];

		yield 'if sale start date is in past => true' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 20 );

				update_post_meta( $ticket_id, Ticket::$sale_price_checked_key, true );
				update_post_meta( $ticket_id, Ticket::$sale_price_key, 10 );
				update_post_meta( $ticket_id, Ticket::$sale_price_start_date_key, '2010-03-01' );

				return [
					$post_id,
					$ticket_id,
					true,
				];
			},
		];

		yield 'if sale start date is in future => false' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 20 );

				update_post_meta( $ticket_id, Ticket::$sale_price_checked_key, true );
				update_post_meta( $ticket_id, Ticket::$sale_price_key, 10 );
				update_post_meta( $ticket_id, Ticket::$sale_price_start_date_key, '2040-03-01' );

				return [
					$post_id,
					$ticket_id,
					false,
				];
			},
		];

		yield 'if sale end date is in past => false' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 20 );

				update_post_meta( $ticket_id, Ticket::$sale_price_checked_key, true );
				update_post_meta( $ticket_id, Ticket::$sale_price_key, 10 );
				update_post_meta( $ticket_id, Ticket::$sale_price_end_date_key, '2010-03-01' );

				return [
					$post_id,
					$ticket_id,
					false,
				];
			},
		];

		yield 'if sale end date is in future => true' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 20 );

				update_post_meta( $ticket_id, Ticket::$sale_price_checked_key, true );
				update_post_meta( $ticket_id, Ticket::$sale_price_key, 10 );
				update_post_meta( $ticket_id, Ticket::$sale_price_end_date_key, '2040-03-01' );

				return [
					$post_id,
					$ticket_id,
					true,
				];
			},
		];

		yield 'if sale start date is in past and end date is in future => true' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 20 );

				update_post_meta( $ticket_id, Ticket::$sale_price_checked_key, true );
				update_post_meta( $ticket_id, Ticket::$sale_price_key, 10 );
				update_post_meta( $ticket_id, Ticket::$sale_price_start_date_key, '2010-03-01' );
				update_post_meta( $ticket_id, Ticket::$sale_price_end_date_key, '2040-03-01' );

				return [
					$post_id,
					$ticket_id,
					true,
				];
			},
		];

		yield 'sale start date and end date in past => false' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 20 );

				update_post_meta( $ticket_id, Ticket::$sale_price_checked_key, true );
				update_post_meta( $ticket_id, Ticket::$sale_price_key, 10 );
				update_post_meta( $ticket_id, Ticket::$sale_price_start_date_key, '2010-03-01' );
				update_post_meta( $ticket_id, Ticket::$sale_price_end_date_key, '2010-03-01' );

				return [
					$post_id,
					$ticket_id,
					false,
				];
			},
		];

		yield 'sale start date in future, end date in past => false' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 20 );

				update_post_meta( $ticket_id, Ticket::$sale_price_checked_key, true );
				update_post_meta( $ticket_id, Ticket::$sale_price_key, 10 );
				update_post_meta( $ticket_id, Ticket::$sale_price_start_date_key, '2040-03-01' );
				update_post_meta( $ticket_id, Ticket::$sale_price_end_date_key, '2010-03-01' );

				return [
					$post_id,
					$ticket_id,
					false,
				];
			},
		];

		yield 'sale start date and end date is today => true' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 20 );

				$today            = tribe( Tribe__Tickets__Ticket_Object::class )->get_date( 'today' );
				$today_in_an_hour = $today + HOUR_IN_SECONDS;

				$today_obj            = new DateTime( "@$today" );
				$today_in_an_hour_obj = new DateTime( "@$today_in_an_hour" );

				update_post_meta( $ticket_id, Ticket::$sale_price_checked_key, true );
				update_post_meta( $ticket_id, Ticket::$sale_price_key, 10 );
				update_post_meta( $ticket_id, Ticket::$sale_price_start_date_key, $today_obj->format( 'Y-m-d H:i:s' ) );
				update_post_meta( $ticket_id, Ticket::$sale_price_end_date_key, $today_in_an_hour_obj->format( 'Y-m-d H:i:s' ) );

				return [
					$post_id,
					$ticket_id,
					true,
				];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider is_on_sale_provider
	 *
	 * @covers    Ticket::is_on_sale
	 */
	public function test_is_on_sale( \Closure $ticket_data_provider ): void {
		[ $post_id, $ticket_id, $expected ] = $ticket_data_provider();

		$provider     = tribe( Module::class );
		$ticket       = $provider->get_ticket( $post_id, $ticket_id );
		$ticket_class = tribe( Ticket::class );

		$this->assertEquals( $expected, $ticket_class->is_on_sale( $ticket ) );
	}

	/**
	 * Provides data for the process_sale_price_data test.
	 *
	 * @return \Generator
	 */
	public function process_sale_price_data_provider(): \Generator {
		yield 'sale price data not added' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket(
					$post_id,
					20,
				);

				return [
					$post_id,
					$ticket_id,
					false,
					false,
					false,
					false,
				];
			},
		];

		yield 'sale price data added' => [
			function (): array {
				$post_id   = static::factory()->post->create();
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

				return [
					$post_id,
					$ticket_id,
					true,
					Value::create( 10 ),
					'2010-03-01',
					'2040-03-01',
				];
			},
		];

		yield 'sale price data added but sale price option unchecked' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket(
					$post_id,
					20,
					[
						'ticket_sale_price'      => 10,
						'ticket_sale_start_date' => '2010-03-01',
						'ticket_sale_end_date'   => '2040-03-01',
					]
				);

				return [
					$post_id,
					$ticket_id,
					false,
					false,
					false,
					false,
				];
			},
		];

		yield 'sale price data is greater than regular price' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket(
					$post_id,
					20,
					[
						'ticket_add_sale_price'  => 'on',
						'ticket_sale_price'      => 30,
						'ticket_sale_start_date' => '2010-03-01',
						'ticket_sale_end_date'   => '2040-03-01',
					]
				);

				// The sale price is greater than the regular price, so it should be ignored and not saved.
				return [
					$post_id,
					$ticket_id,
					false,
					false,
					false,
					false,
				];
			},
		];

		yield 'added a ticket with sale price then saved again with unchecked sale price' => [
			function (): array {
				$post_id   = static::factory()->post->create();
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

				// Save the ticket again with the sale price option unchecked, this should remove all previously saved sale price data.
				$this->create_tc_ticket(
					$post_id,
					20,
					[
						'ticket_id'              => $ticket_id,
						'ticket_sale_price'      => 10,
						'ticket_sale_start_date' => '2010-03-01',
						'ticket_sale_end_date'   => '2040-03-01',
					]
				);

				return [
					$post_id,
					$ticket_id,
					false,
					false,
					false,
					false,
				];
			},
		];
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Commerce\Ticket::process_sale_price_data
	 * @covers \TEC\Tickets\Commerce\Ticket::process_sale_price_dates
	 *
	 * @dataProvider process_sale_price_data_provider
	 */
	public function test_process_sale_price_data( \Closure $data ): void {
		[ $post_id, $ticket_id, $expected_checked, $expected_price, $expected_start_date, $expected_end_date ] = $data();

		$this->assertEquals( $expected_checked, get_post_meta( $ticket_id, Ticket::$sale_price_checked_key, true ) );
		$this->assertEquals( $expected_price, get_post_meta( $ticket_id, Ticket::$sale_price_key, true ) );
		$this->assertEquals( $expected_start_date, get_post_meta( $ticket_id, Ticket::$sale_price_start_date_key, true ) );
		$this->assertEquals( $expected_end_date, get_post_meta( $ticket_id, Ticket::$sale_price_end_date_key, true ) );
	}
}
