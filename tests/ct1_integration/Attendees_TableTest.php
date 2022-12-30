<?php

namespace Tribe\Tickets;

use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events\Custom_Tables\V1\Models\Occurrence as Occurrence_Model;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Traits\CT1\CT1_Fixtures;
use Tribe__Tickets__Attendees;
use Tribe__Tickets__Attendees_Table as Attendees_Table;
use Tribe__Tickets__Data_API as Data_API;

class Attendees_TableTest extends \Codeception\TestCase\WPTestCase {
	use CT1_Fixtures;
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
	 * It should allow fetching ticket attendees by event.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_provisional_id() {
		// Faux provisional ID cleaner upper.
		$base                  = 100000000;
		$faux_provisional_hook = static function ( $id ) use ( $base ) {
			if ( is_numeric( $id ) && $id > $base ) {
				$occurrence_id = $id - $base;
				$occurrence    = Occurrence::find( $occurrence_id );

				return $occurrence instanceof Occurrence_Model ? $occurrence->post_id : $id;
			}

			return $id;
		};
		add_filter( 'tec_events_custom_tables_v1_normalize_occurrence_id', $faux_provisional_hook );
		$post       = $this->given_a_migrated_single_event();
		$post2      = $this->given_a_migrated_single_event();
		$post_id    = $post->ID;
		$post_id2   = $post2->ID;
		$occurrence = Occurrence::find_by_post_id( $post_id );

		// Create a faux provisional id.
		$provisional_id = $occurrence->occurrence_id + $base;
		$occurrence2    = Occurrence::find_by_post_id( $post_id2 );

		// Create a faux provisional id.
		$provisional_id2 = $occurrence2->occurrence_id + $base;
		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 25, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 25, $rsvp_ticket_id, $post_id );

		// Add other ticket/attendees for another post so we can confirm we only returned the correct attendees.
		$paypal_ticket_id2 = $this->create_paypal_ticket( $post_id2, 1 );
		$rsvp_ticket_id2   = $this->create_rsvp_ticket( $post_id2 );

		$paypal_attendee_ids2 = $this->create_many_attendees_for_ticket( 25, $paypal_ticket_id2, $post_id2 );
		$rsvp_attendee_ids2   = $this->create_many_attendees_for_ticket( 25, $rsvp_ticket_id2, $post_id2 );

		$_GET['event_id'] = $provisional_id;
		$table            = $this->make_instance();

		$table->prepare_items();
		$attendee_ids = wp_list_pluck( $table->items, 'attendee_id' );

		$expected_attendee_ids = array_slice( array_merge( $paypal_attendee_ids, $rsvp_attendee_ids ), 0, $table->get_pagination_arg( 'per_page' ) );

		$this->assertEqualSets( $expected_attendee_ids, $attendee_ids );
		$this->assertEquals( count( array_merge( $paypal_attendee_ids, $rsvp_attendee_ids ) ), $table->get_pagination_arg( 'total_items' ) );

		$_GET['event_id'] = $provisional_id2;
		$table->prepare_items();
		$attendee_ids2 = wp_list_pluck( $table->items, 'attendee_id' );

		$expected_attendee_ids2 = array_slice( array_merge( $paypal_attendee_ids2, $rsvp_attendee_ids2 ), 0, $table->get_pagination_arg( 'per_page' ) );

		$this->assertEqualSets( $expected_attendee_ids2, $attendee_ids2 );
		$this->assertEquals( count( array_merge( $paypal_attendee_ids2, $rsvp_attendee_ids2 ) ), $table->get_pagination_arg( 'total_items' ) );
	}
}
