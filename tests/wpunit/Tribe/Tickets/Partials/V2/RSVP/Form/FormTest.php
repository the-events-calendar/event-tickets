<?php

namespace Tribe\Tickets\Partials\V2\RSVP\Form;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class Form extends WPTestCase {

	use MatchesSnapshots;
	use With_Post_Remapping;

	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	protected $partial_path = 'v2/rsvp/form/form';

	/**
	 * @test
	 */
	public function test_should_render_form_going() {
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
			'rsvp'    => $ticket,
			'post_id' => $event_id,
			'going'   => 'going',
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );
		$driver->setTolerableDifferences( [ $ticket_id, $event_id ] );
		$driver->setTolerableDifferencesPrefixes( [
			'quantity_',
		] );

		$driver->setTimeDependentAttributes( [
			'data-rsvp-id',
			'data-product-id',
		] );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_form_not_going() {
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
			'rsvp'    => $ticket,
			'post_id' => $event_id,
			'going'   => 'not-going',
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );
		$driver->setTolerableDifferences( [ $ticket_id, $event_id ] );
		$driver->setTolerableDifferencesPrefixes( [
			'quantity_',
		] );

		$driver->setTimeDependentAttributes( [
			'data-rsvp-id',
			'data-product-id',
		] );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
