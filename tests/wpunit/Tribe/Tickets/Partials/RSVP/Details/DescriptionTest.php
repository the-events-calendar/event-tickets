<?php
namespace Tribe\Tickets\Partials\RSVP\Details;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use tad\WP\Snapshots\WPHtmlOutputDriver;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class Description extends WPTestCase {
	use MatchesSnapshots;
	use With_Post_Remapping;

	use RSVP_Ticket_Maker;

	protected $partial_path = 'blocks/rsvp/details/description';

	/**
	 * @test
	 */
	public function test_should_render_empty_wo_show_description() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		update_post_meta( $ticket_id, tribe( 'tickets.handler' )->key_show_description, false );

		$ticket    = tribe( 'tickets.rsvp' )->get_ticket( $event_id, $ticket_id );

		$args    = [
			'ticket'  => $ticket,
			'post_id' => $event_id,
		];

		$html     = $template->template( $this->partial_path, $args, false );
		$driver   = new WPHtmlOutputDriver( getenv( 'WP_URL' ), 'http://wp.localhost' );

		$driver->setTolerableDifferences( [ $ticket_id, $event_id ] );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_description() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$ticket    = tribe( 'tickets.rsvp' )->get_ticket( $event_id, $ticket_id );

		$args    = [
			'ticket'  => $ticket,
			'post_id' => $event_id,
		];

		$html     = $template->template( $this->partial_path, $args, false );
		$driver   = new WPHtmlOutputDriver( getenv( 'WP_URL' ), 'http://wp.localhost' );

		$driver->setTolerableDifferences( [ $ticket_id, $event_id ] );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
