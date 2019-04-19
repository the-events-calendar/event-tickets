<?php

namespace Tribe\Tickets;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe__Tickets__Attendees_Table as Attendees_Table;

class Attendees_TableTest extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );

		$GLOBALS['hook_suffix'] = 'tribe_events_page_tickets-attendees';
	}

	private function make_instance() {
		return new Attendees_Table();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Attendees_Table::class, $sut );
	}

	/**
	 * It should allow fetching ticket attendees.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees() {
		$post_id  = $this->factory->post->create();
		$post_id2 = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket( $post_id2, 1 );
		$rsvp_ticket_id2   = $this->create_rsvp_ticket( $post_id2 );

		$paypal_attendee_ids2 = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id2, $post_id2 );
		$rsvp_attendee_ids2   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id2, $post_id2 );

		$sut = $this->make_instance();

		$_GET['event_id'] = $post_id;
		$sut->prepare_items();
		$attendee_ids = wp_list_pluck( $sut->items, 'attendee_id' );

		$this->assertEqualSets( array_merge( $paypal_attendee_ids, $rsvp_attendee_ids ), $attendee_ids );
		$this->assertEquals( count( array_merge( $paypal_attendee_ids, $rsvp_attendee_ids ) ), $sut->get_pagination_arg( 'total_items' ) );

		$_GET['event_id'] = $post_id2;
		$sut->prepare_items();
		$attendee_ids2 = wp_list_pluck( $sut->items, 'attendee_id' );

		$this->assertEqualSets( array_merge( $paypal_attendee_ids2, $rsvp_attendee_ids2 ), $attendee_ids2 );
		$this->assertEquals( count( array_merge( $paypal_attendee_ids2, $rsvp_attendee_ids2 ) ), $sut->get_pagination_arg( 'total_items' ) );
	}

}
