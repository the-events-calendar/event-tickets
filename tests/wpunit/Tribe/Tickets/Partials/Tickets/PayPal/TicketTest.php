<?php
namespace Tribe\Tickets\Partials\Tickets\PayPal;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;

use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Traits\CapacityMatrix;
use Tribe__Tickets__Data_API as Data_API;
use tad\FunctionMocker\FunctionMocker as Test;

class TicketTest extends WPTestCase {
	use MatchesSnapshots;
	use With_Post_Remapping;

	use CapacityMatrix;
	use PayPal_Ticket_Maker;

	protected $partial_path = 'blocks/tickets';

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

		// Override all nonce generation to use this one for testing purposes.
		Test::replace( 'wp_create_nonce', '2ab7cc6b39' );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	/**
	 * @dataProvider _get_ticket_matrix
	 * @test
	 */
	public function test_should_render_ticket_block( $matrix ) {
		// @todo Do the setup for $matrix.

		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_paypal_ticket( $event_id, 10, [
			'meta_input' => [
				'_tribe_ticket_show_description' => false, // Setting false to show description.
			],
		] );

		$ticket    = tribe( 'tickets.commerce.paypal' )->get_ticket( $event_id, $ticket_id );

		$args    = [
			'ticket'   => $ticket,
			'post_id'  => $event_id,
			'is_modal' => false,
		];

		$html     = $template->template( $this->partial_path, $args, false );
		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @dataProvider _get_ticket_update_matrix
	 * @test
	 */
	public function test_should_render_ticket_block_after_update( $matrix ) {
		// @todo Do the setup for $matrix.
		// $matrix['from'] and $matrix['to'] have the variations (from = create, to = update).

		$template  = tribe( 'tickets.editor.template' );
		$event     = $this->get_mock_event( 'events/single/1.json' );
		$event_id  = $event->ID;
		$ticket_id = $this->create_paypal_ticket( $event_id, 10, [
			'meta_input' => [
				'_tribe_ticket_show_description' => false, // Setting false to show description.
			],
		] );

		$ticket    = tribe( 'tickets.commerce.paypal' )->get_ticket( $event_id, $ticket_id );

		$args    = [
			'ticket'   => $ticket,
			'post_id'  => $event_id,
			'is_modal' => false,
		];

		$html     = $template->template( $this->partial_path, $args, false );
		$this->assertMatchesSnapshot( $html );
	}
}
