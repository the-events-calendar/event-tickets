<?php

namespace TEC\Tickets\Emails\Admin;

use Spatie\Snapshots\MatchesSnapshots;
use TEC\Tickets\Emails\Admin\Emails_Tab as Emails_Tab;
use TEC\Tickets\Emails\Email\Completed_Order;
use TEC\Tickets\Emails\Email\Purchase_Receipt;
use TEC\Tickets\Emails\Email\RSVP;
use TEC\Tickets\Emails\Email\RSVP_Not_Going;
use TEC\Tickets\Emails\Email\Ticket;
use Tribe\Tests\Traits\With_Uopz;
use Codeception\TestCase\WPTestCase;

/**
 * Class Preview_ModalTest
 *
 * @since   5.9.1
 *
 * @package TEC\Tickets\Emails\Admin
 */
class Preview_ModalTest extends WPTestCase {
	use With_Uopz;
	use MatchesSnapshots;

	/**
	 * @test
	 */
	public function it_should_render_modal_on_settings_page(): void {
		$this->set_class_fn_return( Emails_Tab::class, 'is_on_tab', true );

		$modal = new Preview_Modal;
		$this->assertTrue( $modal->should_render(), 'Modal needs to be rendered on Settings Tab' );
	}

	/**
	 * @test
	 */
	public function it_should_not_render_modal_off_settings_page(): void {
		$this->set_class_fn_return( Emails_Tab::class, 'is_on_tab', false );

		$modal = new Preview_Modal;
		$this->assertFalse( $modal->should_render(), 'Modal cannot be rendered off of the Settings Tab' );
	}

	/**
	 * @test
	 */
	public function it_should_match_snapshot_for_modal_content(): void {
		$this->set_class_fn_return( Emails_Tab::class, 'is_on_tab', true );

		$modal = new Preview_Modal;
		$this->assertMatchesSnapshot( $modal->get_modal_content() );
	}

	/**
	 * @test
	 */
	public function it_should_match_snapshot_for_modal_button(): void {
		$this->set_class_fn_return( Emails_Tab::class, 'is_on_tab', true );

		$button_html = Preview_Modal::get_modal_button( [ 'button_id' => 'mock_id' ] );
		$this->assertMatchesSnapshot( $button_html );
	}

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
	public function it_should_match_snapshot_for_individual_email_preview( $email ): void {
		$this->set_class_fn_return( Emails_Tab::class, 'is_on_tab', true );

		$modal = new Preview_Modal;
		$ajax_content = $modal->get_modal_content_ajax( '', [ 'currentEmail' => $email->get_id() ] );
		$this->assertMatchesSnapshot( $ajax_content );
	}
}