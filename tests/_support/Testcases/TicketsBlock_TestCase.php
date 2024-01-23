<?php

namespace Tribe\Tickets\Test\Testcases;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Traits\CapacityMatrix;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Editor__Template as Template;

class TicketsBlock_TestCase extends WPTestCase {
	use With_Uopz;
	use MatchesSnapshots;
	use CapacityMatrix;

	/**
	 * Whether to use v2 views.
	 *
	 * @var bool
	 */
	protected $use_v2 = false;

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post', 'tribe_events' ];
		} );

		// Fix the dialog ID to stabilize the snapshots.
		add_filter( 'tec_dialog_id', fn() => '[DIALOG_ID]' );

		// Override all nonce generation to use this one for testing purposes.
		$this->set_fn_return( 'wp_create_nonce', '2ab7cc6b39' );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );

		// Reset the template singleton.
		tribe_singleton( 'tickets.editor.template', new Template );

		if ( $this->use_v2 ) {
			add_filter( 'tribe_tickets_new_views_is_enabled', '__return_true' );
			add_filter( 'tribe_tickets_rsvp_new_views_is_enabled', '__return_true' );
		} else {
			add_filter( 'tribe_tickets_new_views_is_enabled', '__return_false' );
			add_filter( 'tribe_tickets_rsvp_new_views_is_enabled', '__return_false' );
		}

		/** @var \wpdb $wpdb */
		global $wpdb;

		// Set high initial post ID to prevent collisions with acceptable tolerances assertions.
		$wpdb->query( "INSERT INTO {$wpdb->posts} ( ID, post_title, post_type ) VALUES ( 9999, 'Temporary', '_temp' )" );
	}

	/**
	 * {@inheritdoc}
	 */
	public function tearDown() {
		/** @var \wpdb $wpdb */
		global $wpdb;

		// Delete high initial post ID.
		$wpdb->delete( $wpdb->posts, [ 'ID' => 9999 ] );
		parent::tearDown();
	}

	/**
	 * Get list of providers for test.
	 *
	 * @return array List of providers.
	 */
	protected function get_providers() {
		return [];
	}

	/**
	 * Create ticket.
	 *
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param int   $price     Ticket price.
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return int Ticket ID.
	 */
	protected function create_block_ticket( $post_id, $price, $overrides ) {
		return 1;
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
	protected function setup_block_ticket( $post_id, $matrix, $overrides = [] ) {
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

		return $this->create_block_ticket( $post_id, 5, $overrides );
	}

	/**
	 * @dataProvider _get_ticket_matrix_as_args
	 * @test
	 */
	public function test_should_render_ticket_block( $matrix ) {
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		// Get first key.
		$provider = key( $this->get_providers() );

		$post_id = $this->factory()->post->create( [
			'post_title' => 'Test post for ticket block',
			'meta_input' => [
				$tickets_handler->key_provider_field => $provider,
			],
		] );

		$ticket_id = $this->setup_block_ticket( $post_id, $matrix );

		/** @var \Tribe__Tickets__Main $tickets_main */
		$tickets_main = tribe( 'tickets.main' );
		$tickets_view = $tickets_main->tickets_view();

		$html   = $tickets_view->get_tickets_block( get_post( $post_id ) );
		$driver = new WPHtmlOutputDriver( home_url(), TRIBE_TESTS_HOME_URL );

		$driver->setTolerableDifferences( [
			$ticket_id,
			$post_id,
		] );

		$driver->setTolerableDifferencesPrefixes( [
			'post-',
			'tribe-block-tickets-item-',
			'tribe__details__content--',
			'tribe-tickets-attendees-list-optout-',
			'Test PayPal ticket for ',
			'Test PayPal ticket description for ',
			'Test Easy Digital Downloads ticket for ',
			'Test Easy Digital Downloads ticket description for ',
			'Test EDD ticket for ',
			'Test EDD ticket description for ',
			'Test WooCommerce ticket for ',
			'Test WooCommerce ticket description for ',
			'Test RSVP ticket for ',
			'Ticket RSVP ticket excerpt for ',
		] );

		$driver->setTimeDependentAttributes( [
			'data-ticket-id',
		] );

		// Remove the URL + port so it doesn't conflict with URL tolerances.
		$html = str_replace( home_url(), TRIBE_TESTS_HOME_URL, $html );

		// Handle variations that tolerances won't handle.
		$html = str_replace(
			[
				$post_id,
				$ticket_id,
			],
			[
				'[EVENT_ID]',
				'[TICKET_ID]',
			],
			$html
		);

		$this->assertNotEmpty( $html, 'Tickets block is not rendering' );
		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @dataProvider _get_ticket_update_matrix_as_args
	 * @test
	 */
	public function test_should_render_ticket_block_after_update( $matrix ) {
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		// Get first key.
		$provider = key( $this->get_providers() );

		$post_id = $this->factory()->post->create( [
			'post_title' => 'Test post for ticket block after update',
			'meta_input' => [
				$tickets_handler->key_provider_field => $provider,
			],
		] );

		// Create ticket.
		$ticket_id = $this->setup_block_ticket( $post_id, $matrix['from'] );

		// Update ticket.
		$this->setup_block_ticket( $post_id, $matrix['to'], [
			'ticket_id' => $ticket_id,
		] );

		/** @var \Tribe__Tickets__Main $tickets_main */
		$tickets_main = tribe( 'tickets.main' );
		$tickets_view = $tickets_main->tickets_view();

		$html   = $tickets_view->get_tickets_block( get_post( $post_id ) );
		$driver = new WPHtmlOutputDriver( home_url(), TRIBE_TESTS_HOME_URL );

		$driver->setTolerableDifferences( [
			$ticket_id,
			$post_id,
		] );
		$driver->setTolerableDifferencesPrefixes( [
			'post-',
			'tribe-block-tickets-item-',
			'tribe__details__content--',
			'tribe-tickets-attendees-list-optout-',
			'Test PayPal ticket for ',
			'Test PayPal ticket description for ',
			'Test Easy Digital Downloads ticket for ',
			'Test Easy Digital Downloads ticket description for ',
			'Test WooCommerce ticket for ',
			'Test WooCommerce ticket description for ',
			'Test RSVP ticket for ',
			'Ticket RSVP ticket excerpt for ',
		] );
		$driver->setTimeDependentAttributes( [
			'data-ticket-id',
		] );

		// Remove the URL + port so it doesn't conflict with URL tolerances.
		$html = str_replace( home_url(), TRIBE_TESTS_HOME_URL, $html );

		// Handle variations that tolerances won't handle.
		$html = str_replace(
			[
				$post_id,
				$ticket_id,
			],
			[
				'[EVENT_ID]',
				'[TICKET_ID]',
			],
			$html
		);

		$this->assertNotEmpty( $html, 'Tickets block is not rendering' );
		$this->assertMatchesSnapshot( $html, $driver );
	}
}
