<?php

namespace Tribe\Tickets\Commerce\PayPal;

use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Testcases\TicketsBlock_TestCase;
use Tribe__Tickets__Data_API as Data_API;

/**
 * @group block
 * @group block-paypal
 * @group editor
 * @group editor-paypal
 * @group capacity
 * @group capacity-paypal
 */
class TicketsBlockTest extends TicketsBlock_TestCase {

	use PayPal_Ticket_Maker;

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( $this->get_paypal_ticket_provider() )->plugin_name;

			return $modules;
		} );
	}

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
	 * Create ticket.
	 *
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param int   $price     Ticket price.
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return int Ticket ID.
	 */
	protected function create_block_ticket( $post_id, $price, $overrides ) {
		/*
		 * An update checks the ticket belongs to the post through the ticket's provider, which the Data API only
		 * resolves once rebuilt with PayPal. The snapshots render with the original Data API, so it is put back.
		 */
		$data_api = tribe( 'tickets.data_api' );
		tribe_singleton( 'tickets.data_api', new Data_API );

		$ticket_id = $this->create_paypal_ticket( $post_id, $price, $overrides );

		tribe_singleton( 'tickets.data_api', $data_api );

		return $ticket_id;
	}
}
