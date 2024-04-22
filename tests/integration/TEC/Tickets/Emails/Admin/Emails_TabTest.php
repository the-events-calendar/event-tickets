<?php

namespace TEC\Tickets\Emails\Admin;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use TEC\Tickets\Emails\Admin\Emails_Tab;
use TEC\Tickets\Emails\Email\Completed_Order;
use TEC\Tickets\Emails\Email\Purchase_Receipt;
use TEC\Tickets\Emails\Email\RSVP;
use TEC\Tickets\Emails\Email\RSVP_Not_Going;
use TEC\Tickets\Emails\Email\Ticket;
use Tribe\Tests\Traits\With_Uopz;

/**
 * Class Emails_TabTest
 *
 * @since   5.9.1
 *
 * @package TEC\Tickets\Emails\Admin
 */
class Emails_TabTest extends WPTestCase {
	use With_Uopz;
	use MatchesSnapshots;

	public function get_email_type_instances() {
		yield 'completed-order' => [ tribe( Completed_Order::class ) ];
		yield 'purchase-receipt' => [ tribe( Purchase_Receipt::class ) ];
		yield 'rsvp' => [ tribe( RSVP::class ) ];
		yield 'rsvp-not-going' => [ tribe( RSVP_Not_Going::class ) ];
		yield 'ticket' => [ tribe( Ticket::class ) ];
	}

	/**
	 * @test
	 */
	public function is_editing_email_should_verify_section_against_email_id(): void {
		$tab = new Emails_Tab();

		$_GET['section'] = 'invalid_id';
		$this->assertTrue( $tab->is_editing_email(), 'Invalid section IDs should still tell us we are editing email when no param is passed.' );

		unset( $_GET['section'] );
		$this->assertFalse( $tab->is_editing_email(), 'No section passed should always fail.' );

		$email           = new RSVP_Not_Going();
		$_GET['section'] = $email->get_id();
		$this->assertTrue( $tab->is_editing_email( $email ), 'A Correct ID should return true' );
	}

	/**
	 * @dataProvider get_email_type_instances
	 * @test
	 */
	public function it_should_match_stored_json_for_settings( $email ): void {
		$tab = new Emails_Tab();

		$_GET['section'] = $email->get_id();

		$settings              = $tab->get_email_settings();
		$json_encoded_settings = wp_json_encode( $settings, JSON_PRETTY_PRINT );

		$this->assertMatchesSnapshot( $json_encoded_settings );
	}

	/**
	 * @test
	 */
	public function it_should_match_stored_json_for_settings_with_invalid_id(): void {
		$tab = new Emails_Tab();

		$_GET['section'] = 'invalid_id';

		$settings              = $tab->get_email_settings();
		$json_encoded_settings = wp_json_encode( $settings, JSON_PRETTY_PRINT );

		$this->assertMatchesSnapshot( $json_encoded_settings );
	}

	/**
	 * @test
	 */
	public function it_should_match_stored_json_for_general_settings(): void {
		$tab = new Emails_Tab();

		// Force a certain version set;
		tribe_update_option( 'previous_event_tickets_versions', [] );

		$settings              = $tab->get_fields();
		$json_encoded_settings = wp_json_encode( $settings, JSON_PRETTY_PRINT );

		$this->assertMatchesSnapshot( $json_encoded_settings );
	}

	/**
	 * @test
	 */
	public function it_should_match_stored_json_for_rsvp_without_using_ticket_settings(): void {
		$tab = new Emails_Tab();

		$rsvp_email      = tribe( RSVP::class );
		$_GET['section'] = $rsvp_email->get_id();

		// Set the RSVP email to use ticket settings.
		$this->set_class_fn_return( RSVP::class, 'is_using_ticket_email_settings', false );

		$settings              = $tab->get_email_settings();
		$json_encoded_settings = wp_json_encode( $settings, JSON_PRETTY_PRINT );

		$this->assertMatchesSnapshot( $json_encoded_settings );
	}
}
