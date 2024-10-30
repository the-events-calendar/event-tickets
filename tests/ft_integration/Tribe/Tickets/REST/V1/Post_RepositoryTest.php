<?php

namespace Tribe\Tickets\REST\V1;

use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Post_RepositoryTest extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;
	use Series_Pass_Factory;

	/**
	 * @before
	 */
	public function ensure_ticketable_post_types(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		$ticketable[] = Series::POSTTYPE;
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
	 * It should return series pass type in the ticket data
	 *
	 * @test
	 */
	public function should_return_series_pass_type_in_the_ticket_data(): void {
		$post_id   = $this->factory()->post->create( [
			'post_type' => Series::POSTTYPE,
		] );
		$ticket_id = $this->create_tc_series_pass( $post_id );

		$repository = tribe( 'tickets.rest-v1.repository' );
		$data       = $repository->get_ticket_data( $ticket_id );

		$this->assertArrayHasKey( 'type', $data );
		$this->assertEquals( Series_Passes::TICKET_TYPE, $data['type'] );
	}
}
