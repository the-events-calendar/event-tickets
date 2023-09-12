<?php

namespace Tribe\Tickets\MyTickets;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class MyTicketsTest extends WPTestCase {
	use SnapshotAssertions;
	use Ticket_Maker;
	use RSVP_Ticket_Maker;
	use With_Uopz;
	use Attendee_Maker;

	/**
	 * Setup a user to avoid 0 user ID.
	 *
	 * @before
	 */
	public function setup_preconditions(): void {
		// we should create and use a user to avoid 0 as user ID.
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $user_id );
	}

	public function get_purchased_ticket_data(): Generator {
		yield 'post with no rsvp or tickets purchased' => [ function (): array {
			$post_id = $this->factory()->post->create();
			//create a rsvp ticket for that post.
			$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
			return [ $post_id ];
		} ];

		yield 'post with 1 rsvp purchased' => [ function (): array {
			$post_id = $this->factory()->post->create();
			//create a rsvp ticket for that post.
			$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );

			$this->create_attendee_for_ticket( $rsvp_ticket_id, $post_id, [ 'user_id' => get_current_user_id() ] );
			return [ $post_id ];
		} ];

		yield 'post with 3 rsvp purchased' => [ function (): array {
			$post_id = $this->factory()->post->create();
			//create a rsvp ticket for that post.
			$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );

			$this->create_many_attendees_for_ticket( 3, $rsvp_ticket_id, $post_id, [ 'user_id' => get_current_user_id() ] );
			return [ $post_id ];
		} ];
	}

	/**
	 * @test
	 * @dataProvider get_purchased_ticket_data
	 */
	public function test_my_tickets_view_link( Closure $fixture ): void {
		[ $post_id ] = $fixture();

		global $post;
		$post = get_post( $post_id );

		/** @var \Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );
		$html = $template->template( 'blocks/attendees/view-link', [], false );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
