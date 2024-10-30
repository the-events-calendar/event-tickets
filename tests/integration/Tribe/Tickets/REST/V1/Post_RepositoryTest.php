<?php

namespace Tribe\Tickets\REST\V1;

use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Post_RepositoryTest extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;

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
}
