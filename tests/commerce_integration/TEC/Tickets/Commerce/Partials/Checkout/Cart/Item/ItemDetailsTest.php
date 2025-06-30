<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart\Item;

use TEC\Tickets\Commerce\Ticket;
use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;

class ItemDetailsTest extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'checkout/cart/item/details';

	/**
	 * Test that toggle and description ARE rendered when ticket has a description and should show it.
	 *
	 * @since TBD
	 */
	public function test_should_render_cart_item_details() {
		$ticket_id           = $this->get_mock_thing( 'tickets/1.json' );
		$ticket['obj']       = tribe( Ticket::class )->get_ticket( $ticket_id );
		$ticket['ticket_id'] = $ticket_id;

		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
			'item' => $ticket,
		] ) );
	}

	/**
	 * Test that toggle and description are NOT rendered when ticket has no description.
	 *
	 * @since TBD
	 */
	public function test_should_render_cart_item_details_without_toggle_when_no_description() {
		$ticket_id           = $this->get_mock_thing( 'tickets/1.json' );
		$ticket_obj          = tribe( Ticket::class )->get_ticket( $ticket_id );
		
		// Remove the description to test the conditional logic.
		$ticket_obj->description = '';
		
		$ticket['obj']       = $ticket_obj;
		$ticket['ticket_id'] = $ticket_id;

		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
			'item' => $ticket,
		] ) );
	}

	/**
	 * Test that toggle and description are NOT rendered when ticket shouldn't show description.
	 *
	 * @since TBD
	 */
	public function test_should_render_cart_item_details_without_toggle_when_show_description_false() {
		$ticket_id           = $this->get_mock_thing( 'tickets/1.json' );
		$ticket_obj          = tribe( Ticket::class )->get_ticket( $ticket_id );
		
		// Create a mock that returns false for show_description().
		$mock_ticket              = $this->createMock( get_class( $ticket_obj ) );
		$mock_ticket->method( 'show_description' )->willReturn( false );
		$mock_ticket->description = 'This ticket has a description but should not show it';
		$mock_ticket->post_title  = $ticket_obj->post_title;
		$mock_ticket->ID          = $ticket_obj->ID;
		
		$ticket['obj']       = $mock_ticket;
		$ticket['ticket_id'] = $ticket_id;

		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
			'item' => $ticket,
		] ) );
	}
}
