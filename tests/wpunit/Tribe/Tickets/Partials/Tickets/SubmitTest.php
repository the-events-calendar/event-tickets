<?php
namespace Tribe\Tickets\Partials\Tickets;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;

class SubmitTest extends WPTestCase {
	use MatchesSnapshots;
	use With_Post_Remapping;

	protected $partial_path = 'blocks/tickets/submit';

	/**
	 * @test
	 */
	public function test_should_render_submit_button() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;

		$args    = [
			'post_id'  => $event_id,
			'provider' => tribe( 'tickets.commerce.paypal' ),
		];

		$html     = $template->template( $this->partial_path, $args, false );

		$driver = new WPHtmlOutputDriver( home_url(), 'http://wp.localhost' );

		$driver->setTolerableDifferences( [ $event_id ] );
		$driver->setTolerableDifferencesPrefixes( [
			'post-',
			'tribe-block-tickets-item-',
			'tribe__details__content--',
			'tribe-tickets-attendees-list-optout-',
		] );
		$driver->setTimeDependentAttributes( [
			'data-ticket-id',
		] );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_must_login() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;

		$args    = [
			'post_id'  => $event_id,
			'provider' => tribe( 'tickets.commerce.paypal' ),
		];

		tribe_update_option( 'ticket-authentication-requirements', [ 'event-tickets_all' ] );

		$html     = $template->template( $this->partial_path, $args, false );

		$driver = new WPHtmlOutputDriver( home_url(), 'http://wp.localhost' );

		$driver->setTolerableDifferences( [ $event_id ] );
		$driver->setTolerableDifferencesPrefixes( [
			'post-',
			'tribe-block-tickets-item-',
			'tribe__details__content--',
			'tribe-tickets-attendees-list-optout-',
		] );
		$driver->setTimeDependentAttributes( [
			'data-ticket-id',
		] );

		$this->assertMatchesSnapshot( $html, $driver );
	}

}
