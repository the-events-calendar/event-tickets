<?php

namespace Tribe\Tickets\Partials\V2\RSVP;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class ActionsTest extends WPTestCase {

	use MatchesSnapshots;
	use With_Post_Remapping;

	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	protected $partial_path = 'v2/rsvp/actions';

	/**
	 * @test
	 */
	public function test_should_render_success() {
		$_GET['step'] = 'success';
		$template   = tribe( 'tickets.editor.template' );
		$event      = $this->get_mock_event( 'events/single/1.json' );
		$event_id   = $event->ID;
		$ticket_id  = $this->create_rsvp_ticket(
			$event_id,
			[
				'meta_input' => [
					'_capacity' => 5,
				],
			]
		);

		$ticket = tribe( 'tickets.rsvp' )->get_ticket( $event_id, $ticket_id );

		$args = [
			'rsvp'       => $ticket,
			'post_id'    => $event_id,
			'must_login' => false,
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		$driver->setTolerableDifferences(
			[
				$ticket_id,
				$event_id,
			]
		);
		$driver->setTolerableDifferencesPrefixes(
			[
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
	public function test_should_render_full() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_rsvp_ticket(
			$event_id,
			[
				'meta_input' => [
					'_capacity' => 3,
				],
			]
		);

		$this->create_many_attendees_for_ticket( 5, $ticket_id, $event_id );

		$ticket = tribe( 'tickets.rsvp' )->get_ticket( $event_id, $ticket_id );

		$args = [
			'rsvp'       => $ticket,
			'post_id'    => $event_id,
			'must_login' => false,
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		$driver->setTolerableDifferences(
			[
				$ticket_id,
				$event_id,
			]
		);
		$driver->setTolerableDifferencesPrefixes(
			[
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
	public function test_should_render_rsvp_going() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_rsvp_ticket(
			$event_id,
			[
				'meta_input' => [
					'_capacity' => 5,
				],
			]
		);

		$ticket = tribe( 'tickets.rsvp' )->get_ticket( $event_id, $ticket_id );

		$args = [
			'rsvp'       => $ticket,
			'post_id'    => $event_id,
			'must_login' => false,
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		$driver->setTolerableDifferences(
			[
				$ticket_id,
				$event_id,
			]
		);
		$driver->setTolerableDifferencesPrefixes(
			[
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
	public function test_should_render_rsvp_going_and_not_going() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_rsvp_ticket(
			$event_id,
			[
				'meta_input' => [
					'_capacity' => 5,
				],
			]
		);

		$ticket = tribe( 'tickets.rsvp' )->get_ticket( $event_id, $ticket_id );

		update_post_meta( $ticket_id, '_tribe_ticket_show_not_going', true );

		$args = [
			'rsvp'       => $ticket,
			'post_id'    => $event_id,
			'must_login' => false,
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		$driver->setTolerableDifferences(
			[
				$ticket_id,
				$event_id,
			]
		);
		$driver->setTolerableDifferencesPrefixes(
			[
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
}
