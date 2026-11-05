<?php

namespace Tribe\Tickets;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Traits\With_Ticket_Providers;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Every save path reaches `ticket_add()`, which only updates a ticket through the post the ticket is attached to.
 */
class Ticket_Add_Parent_Test extends WPTestCase {
	use With_Ticket_Providers;

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_not_update_a_ticket_of_another_post( string $provider, string $make_ticket ): void {
		$ticket_id   = $this->{$make_ticket}( static::factory()->post->create() );
		$ticket_name = get_post_field( 'post_title', $ticket_id, 'raw' );

		$saved = $this->module( $provider )->ticket_add(
			static::factory()->post->create(),
			[
				'ticket_id'   => $ticket_id,
				'ticket_name' => $this->faker->sentence(),
			]
		);

		$this->assertFalse( $saved );
		$this->assertSame( $ticket_name, get_post_field( 'post_title', $ticket_id, 'raw' ) );
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_not_update_a_post_that_is_not_a_ticket( string $provider ): void {
		$page = static::factory()->post->create_and_get( [ 'post_type' => 'page' ] );

		$saved = $this->module( $provider )->ticket_add(
			static::factory()->post->create(),
			[
				'ticket_id'   => $page->ID,
				'ticket_name' => $this->faker->sentence(),
			]
		);

		$this->assertFalse( $saved );
		$this->assertSame( $page->post_title, get_post_field( 'post_title', $page->ID, 'raw' ) );
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_update_a_ticket_through_the_post_it_is_attached_to( string $provider, string $make_ticket ): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->{$make_ticket}( $post_id );
		$new_name  = $this->faker->sentence();

		$saved = $this->module( $provider )->ticket_add(
			$post_id,
			[
				'ticket_id'    => $ticket_id,
				'ticket_name'  => $new_name,
				'ticket_price' => $this->faker->numberBetween( 1, 100 ),
			]
		);

		$this->assertSame( $ticket_id, $saved );
		$this->assertSame( $new_name, get_post_field( 'post_title', $ticket_id, 'raw' ) );
	}

	private function module( string $provider ): Tickets {
		return call_user_func( [ $provider, 'get_instance' ] );
	}
}
