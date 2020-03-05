<?php
namespace Tribe\Tickets\Partials\Tickets;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;

use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;

class Quantity extends WPTestCase {
	use MatchesSnapshots;
	use With_Post_Remapping;

	use PayPal_Ticket_Maker;

	protected $partial_path = 'blocks/tickets/quantity';

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
	public function test_should_render_quantity_empty_wo_ticket() {
		$template = tribe( 'tickets.editor.template' );
		$html     = $template->template( $this->partial_path, [], false );
		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_quantity_sold_out() {
		$template = tribe( 'tickets.editor.template' );
		$event = $this->get_mock_event( 'events/single/1.json' );

		/** @var Ticket_Repository $tickets */
		$tickets = tribe_tickets( 'tribe-commerce' );
		$ticket  = $tickets->set_args( [ 'title' => 'A test ticket' ] )->create();

		update_post_meta( $ticket->ID, '_tribe_tpp_for_event', $event->ID );

		$args    = [
			'ticket' => $ticket,
			'key'    => 0,
		];

		// Return zero. Hijack the value so it returns the "Sold Out" template.
		add_filter( 'tribe_tickets_get_ticket_max_purchase', '__return_false' );

		$html     = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_quantity_available() {
		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_paypal_ticket( $event_id, 10 );
		$ticket    = tribe( 'tickets.commerce.paypal' )->get_ticket( $event_id, $ticket_id );

		$args    = [
			'ticket' => $ticket,
			'key'    => 0,
		];

		// Return 10.
		add_filter( 'tribe_tickets_get_ticket_max_purchase', function() {
			return 10;
		} );

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}
}
