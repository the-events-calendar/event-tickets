<?php

namespace Tribe\Tickets\REST\V1;

use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Codeception\TestCase\WPTestCase;
use Tribe__Tickets__REST__V1__Post_Repository as Post_Repository;
use Tribe__Tickets__Tickets as Tickets;

class Post_RepositoryTest extends WPTestCase {
	use Ticket_Maker;
	use With_Uopz;

	/**
	 * @before
	 */
	public function ensure_posts_ticketable(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * It should return the post type in the ticket data
	 *
	 * @test
	 */
	public function should_return_the_post_type_in_the_ticket_data(): void {
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id );

		$repository = tribe( 'tickets.rest-v1.repository' );
		$data       = $repository->get_ticket_data( $ticket_id );

		$this->assertArrayHasKey( 'type', $data );
		$this->assertEquals( 'default', $data['type'] );
	}

	/**
	 * @test
	 */
	public function should_get_ticket_cost() {
		$post_id     = $this->factory()->post->create();
		$ticket_id_1 = $this->create_tc_ticket( $post_id, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post_id, 20 );

		$this->assertSame( '$10.00', tribe( 'tickets.rest-v1.repository' )->get_ticket_cost( $ticket_id_1 ) );

		$this->set_class_fn_return( Post_Repository::class, 'get_ticket_object', function ( $tid ) {
			$ticket = Tickets::load_ticket_object( $tid );
			$ticket->provider_class = 'unknown';
			$ticket->regular_price = '30.5';
			return $ticket;
		}, true );

		// Regular price should be used instead if its set and the provider is not Module.
		$this->assertSame( '$30.50', tribe( 'tickets.rest-v1.repository' )->get_ticket_cost( $ticket_id_2 ) );
	}
}
