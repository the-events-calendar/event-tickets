<?php

namespace TEC\Tickets\Emails;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Emails\Admin\Preview_Data;
use TEC\Tickets\Emails\Email\Completed_Order;
use TEC\Tickets\Emails\Email\Purchase_Receipt;
use TEC\Tickets\Emails\Email\RSVP;
use TEC\Tickets\Emails\Email\RSVP_Not_Going;
use TEC\Tickets\Emails\Email\Ticket;

/**
 * Class TemplateTest
 *
 * @since   5.9.1
 *
 * @package TEC\Tickets\Emails\Admin
 */
class TemplateTest extends WPTestCase {
	use MatchesSnapshots;

	public function get_email_type_instances() {
		yield 'completed-order' => [ tribe( Completed_Order::class ), false ];
		yield 'purchase-receipt' => [ tribe( Purchase_Receipt::class ), false ];
		yield 'rsvp' => [ tribe( RSVP::class ), false ];
		yield 'rsvp-not-going' => [ tribe( RSVP_Not_Going::class ), false ];
		yield 'ticket' => [ tribe( Ticket::class ), false ];
		yield 'free-completed-order' => [ tribe( Completed_Order::class ), true ];
		yield 'free-purchase-receipt' => [ tribe( Purchase_Receipt::class ), true ];
	}

	/**
	 * @dataProvider get_email_type_instances
	 * @test
	 */
	public function it_should_match_snapshot( $email, $is_free = false ): void {
		$preview_context = [
			'is_preview'             => true,
			'ticket_bg_color'        => '#000000',
			'footer_content'         => '',
			'footer_credit'          => true,
			'header_bg_color'        => '#000000',
			'header_image_url'       => '',
			'header_image_alignment' => 'center',
			'heading'                => '',
			'additional_content'     => '',
			'add_event_links'        => true,
		];

		if ( $is_free ) {
			$preview_context['order'] = $this->get_free_order();
		}

		foreach ( $email->get_preview_context( $preview_context ) as $key => $template_var_value ) {
			$email->set( $key, $template_var_value );
		}
		$email->set_placeholders( Preview_Data::get_placeholders() );
		$html = $email->get_content();

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * Creates a free order.
	 *
	 * @return WP_Post The free order
	 */
	private function get_free_order() {
		$order                        = Preview_Data::get_order();
		$total_value                  = Value::create( '0' );
		$order->total                 = $total_value;
		$order->total_value           = $total_value;
		$order->items[0]['price']     = 0.0;
		$order->items[0]['sub_total'] = 0.0;

		return $order;
	}
}
