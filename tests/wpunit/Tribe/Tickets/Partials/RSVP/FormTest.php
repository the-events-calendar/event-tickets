<?php
namespace Tribe\Tickets\Partials\RSVP;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use tad\WP\Snapshots\WPHtmlOutputDriver;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;

class Form extends WPTestCase {
	use MatchesSnapshots;
	use With_Post_Remapping;

	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	protected $partial_path = 'blocks/rsvp/form';

	/**
	 * @test
	 */
	public function test_should_render_form() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$ticket    = tribe( 'tickets.rsvp' )->get_ticket( $event_id, $ticket_id );

		$args    = [
			'ticket'  => $ticket,
			'post_id' => $event_id,
			'going'   => true,
		];

		$html     = $template->template( $this->partial_path, $args, false );

		$driver = new WPHtmlOutputDriver( getenv( 'WP_URL' ), 'http://wp.localhost' );

		$driver->setTolerableDifferences( [ $ticket_id, $event_id ] );
		$driver->setTimeDependentAttributes( [
			'data-rsvp-id',
			'data-product-id'
		] );

		// Remove pesky SVG.
		$html = preg_replace( '/<svg.*<\/svg>/Ums', '', $html );

		$this->assertMatchesSnapshot( $html, $driver );
	}


}
