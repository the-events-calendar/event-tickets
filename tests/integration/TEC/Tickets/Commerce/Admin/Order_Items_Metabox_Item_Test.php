<?php

namespace TEC\Tickets\Commerce\Admin;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Traits\With_Test_Orders;

class Order_Items_Metabox_Item_Test extends WPTestCase {

	use With_Test_Orders;

	/**
	 * Render the single order item template for an attendee with the given meta.
	 *
	 * @param array $meta The attendee meta to render.
	 *
	 * @return string The rendered HTML.
	 */
	protected function render_item_with_meta( array $meta ): string {
		$event_id  = $this->create_test_events( 1 )[0];
		$ticket_id = $this->create_tc_ticket( $event_id );
		$order     = tec_tc_get_order( $this->create_order( [ $ticket_id => 1 ] )->ID );
		$item      = $order->items[ array_key_first( $order->items ) ];
		$ticket    = tribe( Module::class )->get_ticket( 0, $item['ticket_id'] );

		return tribe( Singular_Order_Page::class )->template(
			'order-items-metabox-item',
			[
				'order'    => $order,
				'ticket'   => $ticket,
				'item'     => $item,
				'attendee' => [ 'meta' => $meta ],
			],
			false
		);
	}

	/**
	 * @test
	 */
	public function it_should_not_interpret_attendee_meta_as_a_format_string() {
		$html = $this->render_item_with_meta( [ 'field' => 'value_%1$s_marker' ] );

		// The literal survives only if the meta is treated as data, not as a sprintf format string.
		$this->assertStringContainsString( 'value_%1$s_marker', $html );
	}

	/**
	 * @test
	 */
	public function it_should_not_render_markup_injected_through_attendee_meta() {
		// The .54/.53 offsets are tied to the Edit link markup; they slice raw < / > out of it on the old code.
		$payload = "%1\$.54simg src=x onerror=eval(atob('PAYLOAD')) %1\$.53s";
		$html    = $this->render_item_with_meta( [ 'field' => $payload ] );

		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringContainsString( esc_html( $payload ), $html );
	}

	/**
	 * @test
	 */
	public function it_should_render_literal_percent_in_meta_without_error() {
		$html = $this->render_item_with_meta( [ 'field' => '50% off' ] );

		// On the old code this line fataled with ArgumentCountError.
		$this->assertStringContainsString( '50% off', $html );
	}

	/**
	 * @test
	 */
	public function it_should_join_meta_values_and_render_the_edit_link_once() {
		$html = $this->render_item_with_meta( [ 'a' => 'First', 'b' => 'Second' ] );

		$this->assertSame( 1, substr_count( $html, 'dashicons-edit' ) );
		$this->assertLessThan( strpos( $html, 'dashicons-edit' ), strpos( $html, 'First' ) );
		$this->assertLessThan( strpos( $html, 'Second' ), strpos( $html, 'dashicons-edit' ) );
	}

	/**
	 * @test
	 */
	public function it_should_still_render_benign_meta_with_the_edit_link() {
		$html = $this->render_item_with_meta( [ 'field' => 'Jane Doe' ] );

		$this->assertStringContainsString( 'Jane Doe', $html );
		$this->assertStringContainsString( 'dashicons-edit', $html );
	}
}
