<?php

namespace Tribe\Tickets\Partials\V2\RSVP\Messages;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;


class MustLoginTest extends WPTestCase {

	use MatchesSnapshots;
	use With_Post_Remapping;

	use RSVP_Ticket_Maker;

	protected $partial_path = 'v2/rsvp/messages/must-login';

	/**
	 * @test
	 */
	public function test_should_render_must_login() {
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
			'must_login' => true,
			'login_url'  => 'http://wordpress.test/wp-login.php',
			'rsvp'       => $ticket,
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );
		$driver->setTolerableDifferences( [ $ticket_id, $event_id ] );

		// Remove the URL + port so it doesn't conflict with URL tolerances.
		$html = str_replace( 'http://localhost:8080', 'http://wordpress.test', $html );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_empty_if_must_login_is_false() {
		$template = tribe( 'tickets.editor.template' );

		$html   = $template->template( $this->partial_path, [ 'must_login' => false ], false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
