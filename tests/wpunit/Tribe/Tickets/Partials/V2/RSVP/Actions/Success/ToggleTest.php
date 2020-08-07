<?php

namespace Tribe\Tickets\Partials\V2\RSVP\Actions\Success;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class ToggleTest extends WPTestCase {

	use MatchesSnapshots;
	use With_Post_Remapping;

	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	protected $partial_path = 'v2/rsvp/actions/success/toggle';

	/**
	 * @test
	 */
	public function test_should_render_success_toggle() {
		$template  = tribe( 'tickets.editor.template' );

		$event        = $this->get_mock_event( 'events/single/1.json' );
		$event_id     = $event->ID;
		$ticket_id    = $this->create_rsvp_ticket(
			$event_id,
			[
				'meta_input' => [
					'_capacity' => 5,
				],
			]
		);

		// Get ticket.
		$ticket = tribe( 'tickets.rsvp' )->get_ticket( $event_id, $ticket_id );

		$args = [
			'rsvp'                 => $ticket,
			'opt_in_toggle_hidden' => false,
			'opt_in_checked'       => false,
			'opt_in_attendee_ids'  => '',
			'opt_in_nonce'         => '',
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://test.tribe.dev' );

		$driver->setTolerableDifferences(
			[
				$ticket_id,
				$event_id,
			]
		);
		$driver->setTolerableDifferencesPrefixes(
			[
				'#tribe-tickets-tooltip-content-',
				'tribe-tickets-tooltip-content-',
				'toggle-rsvp-',
				'rsvp-',
			]
		);
		$driver->setTimeDependentAttributes(
			[
				'data-rsvp-id',
			]
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_success_toggle() {
		$template  = tribe( 'tickets.editor.template' );

		$event        = $this->get_mock_event( 'events/single/1.json' );
		$event_id     = $event->ID;
		$ticket_id    = $this->create_rsvp_ticket(
			$event_id,
			[
				'meta_input' => [
					'_capacity' => 5,
				],
			]
		);
		$ticket_id    = $this->create_rsvp_ticket(
			$event_id,
			[
				'meta_input' => [
					'_capacity' => 5,
				],
			]
		);

		// Get ticket.
		$ticket = tribe( 'tickets.rsvp' )->get_ticket( $event_id, $ticket_id );

		$args = [
			'rsvp'                 => $ticket,
			'opt_in_toggle_hidden' => true,
			'opt_in_checked'       => false,
			'opt_in_attendee_ids'  => '',
			'opt_in_nonce'         => '',
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://test.tribe.dev' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

}
