<?php

namespace Tribe\Tickets\Partials;

use Codeception\TestCase\WPTestCase;
use Faker\Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;

class Editor_Fieldset_Price_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * Event Tickets Plus 6.11.2 writes the posted `ticket_sale_price` to the WooCommerce product on every classic save,
	 * an empty one when the field is not posted, so the field must post the sale price that is running.
	 *
	 * @test
	 */
	public function should_post_the_sale_price_of_another_provider_ticket_on_sale(): void {
		$html = $this->render_on_sale_ticket( false );

		$this->assertRegExp( '/<input[^>]*id="ticket_sale_price"[^>]*name="ticket_sale_price"/s', $html );
	}

	/**
	 * The field shows the member price then, which a save must not write to the product as its sale price.
	 *
	 * @test
	 */
	public function should_not_post_the_member_price_of_a_ticket_with_a_member_discount(): void {
		$html = $this->render_on_sale_ticket( true );

		$this->assertStringContainsString( 'id="ticket_sale_price"', $html );
		$this->assertStringNotContainsString( 'name="ticket_sale_price"', $html );
	}

	/**
	 * Renders the price fields of a ticket on sale that a provider other than Tickets Commerce sells, such as
	 * WooCommerce.
	 *
	 * @param bool $has_member_discount Whether the current user gets a WooCommerce Memberships discount on the ticket.
	 *
	 * @return string The price fields HTML.
	 */
	private function render_on_sale_ticket( bool $has_member_discount ): string {
		$this->set_fn_return( 'tribe_tickets_ticket_in_wc_membership_for_user', $has_member_discount );

		$regular_price = Factory::create()->randomFloat( 2, 20, 40 );
		$ticket        = new Ticket_Object(
			[
				'on_sale'       => true,
				'regular_price' => number_format( $regular_price, 2, '.', '' ),
				'price'         => number_format( $regular_price / 2, 2, '.', '' ),
			]
		);

		// No provider other than Tickets Commerce and RSVP runs in this suite.
		$provider = $this->createMock( Tickets::class );
		$provider->method( 'get_ticket' )->willReturn( $ticket );

		return tribe( 'tickets.admin.views' )->template(
			'editor/fieldset/price',
			[
				'post_id'   => static::factory()->post->create(),
				'ticket_id' => 0,
				'provider'  => $provider,
			],
			false
		);
	}
}
