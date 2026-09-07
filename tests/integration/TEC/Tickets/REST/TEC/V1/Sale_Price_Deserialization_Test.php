<?php

namespace TEC\Tickets\Tests\Integration\REST\TEC\V1;

use Codeception\TestCase\WPTestCase;
use TEC\Common\REST\TEC\V1\Exceptions\InvalidRestArgumentException;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\REST\TEC\V1\Endpoints\Ticket;

class Sale_Price_Deserialization_Test extends WPTestCase {

	protected function filter_params( $sale_price ): array {
		$event_id = static::factory()->post->create( [ 'post_type' => 'post', 'post_status' => 'publish' ] );

		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );

		return tribe( Ticket::class )->filter_upsert_params(
			[
				'event'      => $event_id,
				'title'      => 'Ticket',
				'price'      => 10,
				'sale_price' => $sale_price,
			]
		);
	}

	/**
	 * @test
	 */
	public function it_should_reject_a_serialized_object_sale_price() {
		$payload = 'O:45:"Tribe\Events\Collections\Lazy_Post_Collection":2:{s:8:"callback";s:6:"system";s:3:"ids";a:1:{i:0;s:2:"id";}}';

		$this->expectException( InvalidRestArgumentException::class );
		$this->filter_params( $payload );
	}

	/**
	 * @test
	 */
	public function it_should_accept_a_numeric_sale_price() {
		$result = $this->filter_params( 5 );

		$this->assertSame( 5, $result['ticket_params']['ticket_sale_price'] );
	}

	/**
	 * @test
	 */
	public function it_should_accept_a_serialized_value_object() {
		$result = $this->filter_params( serialize( Value::create( 5 ) ) );

		$this->assertSame( '5', (string) $result['ticket_params']['ticket_sale_price'] );
	}
}
