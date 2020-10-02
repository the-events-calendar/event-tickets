<?php

namespace Tribe\Tickets\Partials\V2\RSVP\ARI\Sidebar;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class GuestListTest extends WPTestCase {

	use MatchesSnapshots;
	use With_Post_Remapping;

	use RSVP_Ticket_Maker;

	protected $partial_path = 'v2/rsvp/ari/sidebar/guest-list';

	/**
	 * @test
	 */
	public function test_should_render_ar_sidebar_guest_list() {
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

		$args   = [
			'rsvp'       => $ticket,
			'post_id'    => $event_id,
			'must_login' => false,
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), TRIBE_TESTS_HOME_URL );

		$driver->setTolerableDifferences(
			[
				$ticket_id,
				$event_id,
			]
		);

		$driver->setTolerableDifferencesPrefixes(
			[
				'tmpl-tribe-tickets__rsvp-ar-guest-list-item-template-',
				'tribe-tickets-rsvp-',
				'template-',
				'tribe-tickets-rsvp-',
			]
		);

		$driver->setTolerableDifferencesPostfixes(
			[
				'-guest-{{data.attendee_id + 1}}-tab',
				'-guest-{{data.attendee_id + 1}}',
				'-guest-1-tab',
				'-guest-1',
			]
		);

		$driver->setTimeDependentAttributes(
			[
				'data-rsvp-id',
				'data-product-id',
			]
		);

		$driver = new WPHtmlOutputDriver( home_url(), TRIBE_TESTS_HOME_URL );

		// Note: The tolerable differences for prefix/postfix are not working as expected here, this is a hack.
		$html = str_replace(
			[
				'-' . $ticket_id . '-',
				'-' . $ticket_id,
			],
			[
				'-TICKET_ID-',
				'-TICKET_ID',
			],
			$html
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}

}
