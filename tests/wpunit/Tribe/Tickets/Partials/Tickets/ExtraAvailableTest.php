<?php
namespace Tribe\Tickets\Partials\Tickets;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;

use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;

class ExtraAvailable extends WPTestCase {
	use MatchesSnapshots;
	use With_Post_Remapping;

	use PayPal_Ticket_Maker;

	protected $partial_path = 'blocks/tickets/extra-available';

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
	public function test_should_render_empty_wo_ticket_id() {
		$template = tribe( 'tickets.editor.template' );
		$args     = [
			'ticket' => (object) [
				'ID'   => null,
			],
		];
		$html     = $template->template( $this->partial_path, $args, false );
		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_empty_with_ticket_with_available_minus_one() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_paypal_ticket( $event_id, 10 );
		$ticket    = tribe( 'tickets.commerce.paypal' )->get_ticket( $event_id, $ticket_id );

		$args    = [
			'ticket' => $ticket,
		];

		// Return -1.
		add_filter( 'tribe_tickets_get_ticket_max_purchase', function() {
			return -1;
		} );

		$html     = $template->template( $this->partial_path, $args, false );
		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_extra_available_when_available() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_paypal_ticket( $event_id, 10 );
		$ticket    = tribe( 'tickets.commerce.paypal' )->get_ticket( $event_id, $ticket_id );

		$args    = [
			'ticket'  => $ticket,
			'post_id' => $event_id,
		];

		// Return 10.
		add_filter( 'tribe_tickets_get_ticket_max_purchase', function() {
			return 10;
		} );

		$html     = $template->template( $this->partial_path, $args, false );
		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_unlimited() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_paypal_ticket( $event_id, 10, [
			'meta_input' => [
				'_capacity'   => -1, // Setting as unlimited.
			],
		] );

		$ticket    = tribe( 'tickets.commerce.paypal' )->get_ticket( $event_id, $ticket_id );

		$args    = [
			'ticket'  => $ticket,
			'post_id' => $event_id,
			'key'     => 0,
		];

		// Hijacking get_ticket_max_purchase()
		add_filter( 'tribe_tickets_get_ticket_max_purchase', function() {
			return 10;
		} );

		$html     = $template->template( $this->partial_path, $args, false );
		$this->assertMatchesSnapshot( $html );
	}
}
