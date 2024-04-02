<?php

namespace TEC\Tickets\Emails;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use TEC\Tickets\Emails\Admin\Preview_Data;
use TEC\Tickets\Emails\Email\Completed_Order;
use TEC\Tickets\Emails\Email\Purchase_Receipt;
use TEC\Tickets\Emails\Email\RSVP;
use TEC\Tickets\Emails\Email\RSVP_Not_Going;
use TEC\Tickets\Emails\Email\Ticket;

/**
 * Class TemplateTest
 *
 * @since   5.9.0
 *
 * @package TEC\Tickets\Emails\Admin
 */
class TemplateTest extends WPTestCase {
	use MatchesSnapshots;

	public function get_email_type_instances() {
		yield 'completed-order' => [ tribe( Completed_Order::class ) ];
		yield 'purchase-receipt' => [ tribe( Purchase_Receipt::class ) ];
		yield 'rsvp' => [ tribe( RSVP::class ) ];
		yield 'rsvp-not-going' => [ tribe( RSVP_Not_Going::class ) ];
		yield 'ticket' => [ tribe( Ticket::class ) ];
	}

	/**
	 * @dataProvider get_email_type_instances
	 * @test
	 */
	public function it_should_match_snapshot( $email ): void {
		$preview_context = [
			'is_preview' => true,
			'ticket_bg_color' => '#000000',
			'footer_content' => '',
			'footer_credit' => true,
			'header_bg_color' => '#000000',
			'header_image_url' => '',
			'header_image_alignment' => 'center',
			'heading' => '',
			'additional_content' => '',
		];

		foreach ( $email->get_preview_context( $preview_context ) as $key => $template_var_value ) {
			$email->set( $key, $template_var_value );
		}
		$email->set_placeholders( Preview_Data::get_placeholders() );
		$html = $email->get_content();

		$this->assertMatchesSnapshot( $html );
	}
}