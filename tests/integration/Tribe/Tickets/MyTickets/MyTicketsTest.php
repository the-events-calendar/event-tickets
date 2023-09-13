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
		yield 'post with no rsvp or tickets purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				//create a rsvp ticket for that post.
				$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );

				return [ $post_id ];
			}
		];

		yield 'post with 1 rsvp purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				//create a rsvp ticket for that post.
				$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
				$attendee       = $this->create_attendee_for_ticket( $rsvp_ticket_id, $post_id, [ 'user_id' => get_current_user_id() ] );

				return [ $post_id ];
			}
		];

		yield 'post with 3 rsvp purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				//create a rsvp ticket for that post.
				$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
				$this->create_many_attendees_for_ticket( 3, $rsvp_ticket_id, $post_id, [ 'user_id' => get_current_user_id() ] );

				return [ $post_id ];
			}
		];

		yield 'post with 1 ticket purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				// create a tc ticket..
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				$attendee  = $this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'user_id' => get_current_user_id() ] );

				return [ $post_id ];
			}
		];

		yield 'post with 3 tickets purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				// create a tc ticket..
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				$attendee  = $this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'user_id' => get_current_user_id() ] );

				return [ $post_id ];
			}
		];

		yield 'post with 1 ticket and 1 RSVP purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				// create a tc ticket..
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				$attendee  = $this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'user_id' => get_current_user_id() ] );

				// create rsvp ticket.
				$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
				$attendee       = $this->create_attendee_for_ticket( $rsvp_ticket_id, $post_id, [ 'user_id' => get_current_user_id() ] );

				return [ $post_id ];
			}
		];

		yield 'post with 2 ticket and 3 RSVP purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				// create a tc ticket.
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				$attendee  = $this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id, [ 'user_id' => get_current_user_id() ] );

				// create rsvp ticket.
				$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
				$attendee       = $this->create_many_attendees_for_ticket( 3, $rsvp_ticket_id, $post_id, [ 'user_id' => get_current_user_id() ] );

				return [ $post_id ];
			}
		];

		yield 'post with 1 ticket and 2 RSVP purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				// create a tc ticket.
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				$attendee  = $this->create_many_attendees_for_ticket( 1, $ticket_id, $post_id, [ 'user_id' => get_current_user_id() ] );

				// create rsvp ticket.
				$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
				$attendee       = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $post_id, [ 'user_id' => get_current_user_id() ] );

				return [ $post_id ];
			}
		];

		yield 'post with 2 ticket and 1 RSVP purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				// create a tc ticket.
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				$attendee  = $this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id, [ 'user_id' => get_current_user_id() ] );

				// create rsvp ticket.
				$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
				$attendee       = $this->create_many_attendees_for_ticket( 1, $rsvp_ticket_id, $post_id, [ 'user_id' => get_current_user_id() ] );

				return [ $post_id ];
			}
		];
	}

	/**
	 * Replace dynamic ids with placeholders to avoid snapshot collision.
	 *
	 * @param string $snapshot
	 * @param array $ids
	 *
	 * @return string
	 */
	public function placehold_post_ids( string $snapshot, array $ids ): string {
		return str_replace(
			array_values( $ids ),
			array_map( static fn( string $name ) => "{{ $name }}", array_keys( $ids ) ),
			$snapshot
		);
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
		$html     = $template->template( 'blocks/attendees/view-link', [ 'post_id' => $post->ID ], false );
		$html     = $this->placehold_post_ids( $html, [
			'post_id'   => $post_id
		] );
		$this->assertMatchesHtmlSnapshot( $html );
	}
}
