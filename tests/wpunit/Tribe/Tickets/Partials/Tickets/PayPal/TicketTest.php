<?php

namespace Tribe\Tickets\Partials\Tickets\PayPal;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\FunctionMocker\FunctionMocker as Test;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Traits\CapacityMatrix;
use Tribe__Tickets__Data_API as Data_API;

class TicketTest extends WPTestCase {

	use MatchesSnapshots;
	use With_Post_Remapping;

	use CapacityMatrix;
	use PayPal_Ticket_Maker;

	protected $partial_path = 'blocks/tickets';

	/**
	 * Get list of providers for test.
	 *
	 * @return array List of providers.
	 */
	protected function get_providers() {
		return [
			'Tribe__Tickets__Commerce__PayPal__Main' => 'tribe-commerce',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
		Test::setUp();

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

		// Override all nonce generation to use this one for testing purposes.
		Test::replace( 'wp_create_nonce', '2ab7cc6b39' );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	/**
	 * {@inheritdoc}
	 */
	public function tearDown() {
		Test::tearDown();
		parent::tearDown();
	}

	/**
	 * Setup ticket.
	 *
	 * @param int   $post_id   Post ID.
	 * @param array $matrix    Matrix data to setup with.
	 * @param array $overrides Overrides for ticket data.
	 *
	 * @return int Ticket ID.
	 */
	protected function setup_ticket( $post_id, $matrix, $overrides = [] ) {
		$mode           = $matrix['ticket']['mode'] ?? null;
		$capacity       = $matrix['ticket']['capacity'] ?? null;
		$event_capacity = $matrix['ticket']['event_capacity'] ?? null;

		unset( $matrix['ticket'], $matrix['provider'] );

		$overrides = array_merge( [
			'tribe-ticket' => [
				'mode'     => $mode,
				'capacity' => $capacity,
			],
		], $overrides );

		if ( null !== $event_capacity ) {
			$overrides['tribe-ticket']['event_capacity'] = $event_capacity;
		}

		foreach ( $matrix as $arg => $value ) {
			if ( 0 !== strpos( $arg, 'ticket_' ) ) {
				$arg = 'ticket_' . $arg;
			}

			$overrides[ $arg ] = $value;
		}

		return $this->create_paypal_ticket( $post_id, 5, $overrides );
	}

	/**
	 * @dataProvider _get_ticket_matrix_as_args
	 * @test
	 */
	public function test_should_render_ticket_block( $matrix ) {
		/** @var \Tribe__Tickets__Tickets $provider_class */
		$provider_class = tribe( $this->get_paypal_ticket_provider() );

		$post_id = $this->factory()->post->create();

		$ticket_id = $this->setup_ticket( $post_id, $matrix );

		/** @var \Tribe__Tickets__Main $tickets_main */
		$tickets_main = tribe( 'tickets.main' );
		$tickets_view = $tickets_main->tickets_view();

		$html = $tickets_view->get_tickets_block( get_post( $post_id ) );

		$driver = new WPHtmlOutputDriver( getenv( 'WP_URL' ), 'http://wp.localhost' );

		$driver->setTolerableDifferences( [ $ticket_id, $post_id ] );
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
	 * @dataProvider _get_ticket_update_matrix_as_args
	 * @test
	 */
	public function test_should_render_ticket_block_after_update( $matrix ) {
		/** @var \Tribe__Tickets__Tickets $provider_class */
		$provider_class = tribe( $this->get_paypal_ticket_provider() );

		$post_id = $this->factory()->post->create();

		// Create ticket.
		$ticket_id = $this->setup_ticket( $post_id, $matrix['from'] );

		// Update ticket.
		$this->setup_ticket( $post_id, $matrix['to'], [
			'ticket_id' => $ticket_id,
		] );

		/** @var \Tribe__Tickets__Main $tickets_main */
		$tickets_main = tribe( 'tickets.main' );
		$tickets_view = $tickets_main->tickets_view();

		$html = $tickets_view->get_tickets_block( get_post( $post_id ) );

		$driver = new WPHtmlOutputDriver( getenv( 'WP_URL' ), 'http://wp.localhost' );

		$driver->setTolerableDifferences( [ $ticket_id, $post_id ] );
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
