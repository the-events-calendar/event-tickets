<?php
namespace Tribe\Tickets\Partials;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Attendees;
use Tribe__Tickets__Attendees_Table;
use Tribe__Tickets__Tickets as Tickets;

class Attendees_Page_Header extends WPTestCase {
	use MatchesSnapshots;
	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	protected $partial_path = 'attendees/attendees-event/summary';

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );
	}

	/**
	 * @test
	 */
	function should_match_snapshot() {
		// Create new post.
		$post_id = $this->factory->post->create();

		// Add RSVP to post.
		$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );

		// Create attendee records.
		$attendee_ids = $this->create_many_attendees_for_ticket( 10, $rsvp_ticket_id, $post_id );

		// Create attendees object.
		$_GET['event_id']           = $post_id;
		$attendees                  = new Tribe__Tickets__Attendees();
		$attendees->attendees_table = new Tribe__Tickets__Attendees_Table();

		// Get HTML from template.
		$template = tribe( 'tickets.admin.views' );
		$args = $attendees->get_render_context( $post_id );
		$html = $template->template( $this->partial_path, $args, false );

		// Replace post IDs in the snapshot to stabilize it.
		// Start from the end to avoid replacing the same ID twice.
		$post_ids = [ ...$attendee_ids, $rsvp_ticket_id, $post_id ];
		$html = str_replace(
			$post_ids,
			[ ...array_fill( 0, count( $attendee_ids ), 'ATTENDEE_ID' ), 'RSVP_TICKET_ID', 'POST_ID' ],
			$html
		);

		$this->assertMatchesSnapshot( $html );
	}
}