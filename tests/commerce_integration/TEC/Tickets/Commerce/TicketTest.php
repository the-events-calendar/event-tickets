<?php
namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Ticket;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
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
	}
	
	/**
	 * @test
	 * @dataProvider is_on_sale_provider
	 */
	public function test_is_on_sale( \Closure $ticket_data_provider ): void {
		[ $post_id, $ticket_id, $expected ] = $ticket_data_provider();
		
		$provider     = tribe( Module::class );
		$ticket       = $provider->get_ticket( $post_id, $ticket_id );
		$ticket_class = tribe( Ticket::class );
		
		$this->assertEquals( $expected, $ticket_class->is_on_sale( $ticket ) );
	}
}
