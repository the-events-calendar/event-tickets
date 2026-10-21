<?php

namespace TEC\Tickets\Commerce\Editor;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Metabox_Test extends WPTestCase {
	use Ticket_Maker;
	use PayPal_Ticket_Maker;

	/**
	 * @test
	 */
	public function it_should_render_no_sale_price_fields_for_a_ticket_another_provider_sells_on_a_tickets_commerce_post(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_paypal_ticket( $post_id, 20 );
		// Another provider keeps its sale price in the same meta, as a string.
		update_post_meta( $ticket_id, Ticket::$sale_price_key, '15' );

		$html = $this->render_sale_price_fields( $ticket_id, $post_id );

		$this->assertSame( '', $html );
	}

	/**
	 * @test
	 */
	public function it_should_render_the_sale_price_fields_of_a_tickets_commerce_ticket_on_a_post_another_provider_sells(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 20 );

		$html = $this->render_sale_price_fields( $ticket_id, $post_id, tribe( 'tickets.commerce.paypal' ) );

		$this->assertStringContainsString( 'name="ticket_sale_price"', $html );
	}

	/**
	 * @test
	 */
	public function it_should_render_the_sale_price_fields_of_a_new_ticket_of_a_tickets_commerce_post(): void {
		$post_id = static::factory()->post->create();

		$html = $this->render_sale_price_fields( 0, $post_id );

		$this->assertStringContainsString( 'name="ticket_sale_price"', $html );
	}

	/**
	 * Renders the sale price fields of a ticket form.
	 *
	 * @param int                          $ticket_id The ticket ID, `0` for a new ticket.
	 * @param int                          $post_id   The post ID.
	 * @param \Tribe__Tickets__Tickets|null $provider  The provider of the post, Tickets Commerce when `null`.
	 *
	 * @return string The HTML.
	 */
	private function render_sale_price_fields( int $ticket_id, int $post_id, $provider = null ): string {
		ob_start();
		tribe( Metabox::class )->render_sale_price_fields( $ticket_id, $post_id, [ 'provider' => $provider ?? tribe( Module::class ) ] );

		return ob_get_clean();
	}
}
