<?php
namespace Tribe\Tickets\Partials\Tickets;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;

use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;

class ContentDescription extends WPTestCase {
	use MatchesSnapshots;
	use With_Post_Remapping;

	use PayPal_Ticket_Maker;

	protected $partial_path = 'blocks/tickets/content-description';

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post', 'tribe_events' ];
		} );

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	/**
	 * @test
	 */
	public function test_should_render_empty_wo_show_description() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_paypal_ticket( $event_id, 10, [
			// Setting false to show description.
			'show_description' => false,
		] );

		$ticket    = tribe( 'tickets.commerce.paypal' )->get_ticket( $event_id, $ticket_id );

		$args    = [
			'ticket'   => $ticket,
			'post_id'  => $event_id,
			'is_modal' => false,
		];

		$html     = $template->template( $this->partial_path, $args, false );

		$driver = new WPHtmlOutputDriver( home_url(), TRIBE_TESTS_HOME_URL );

		$driver->setTolerableDifferences( [ $ticket_id, $event_id ] );
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
	public function test_should_render_regular_wo_modal() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_paypal_ticket( $event_id, 10, [
			// Setting to show description.
			'show_description' => true,
		] );

		$ticket    = tribe( 'tickets.commerce.paypal' )->get_ticket( $event_id, $ticket_id );

		$args    = [
			'ticket'   => $ticket,
			'post_id'  => $event_id,
			'is_modal' => false,
		];

		$html     = $template->template( $this->partial_path, $args, false );

		$driver = new WPHtmlOutputDriver( home_url(), TRIBE_TESTS_HOME_URL );

		$driver->setTolerableDifferences( [ $ticket_id, $event_id ] );
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
	public function test_should_render_with_modal() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_paypal_ticket( $event_id, 10, [
			// Setting to show description.
			'show_description' => true,
		] );

		$ticket    = tribe( 'tickets.commerce.paypal' )->get_ticket( $event_id, $ticket_id );

		$args    = [
			'ticket'   => $ticket,
			'post_id'  => $event_id,
			'is_modal' => true,
		];

		$html     = $template->template( $this->partial_path, $args, false );

		$driver = new WPHtmlOutputDriver( home_url(), TRIBE_TESTS_HOME_URL );

		$driver->setTolerableDifferences( [ $ticket_id, $event_id ] );
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
