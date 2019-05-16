<?php
namespace Tribe\Tickets;

use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Ticket_Object as RSVP;
use Tribe__Tickets__Tickets as Ticket;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Global_Stock as Global_Stock;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;

class Ticket_ObjectTest extends \Codeception\TestCase\WPTestCase {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	private $timezone = 'America/New_York';

	private $later_date = '';

	private $earlier_date = '';

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->factory()->event = new Event();

		$this->later_date = strtotime( '+5 days' );

		$this->earlier_date = strtotime( '-5 days' );

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * @return Ticket_Object
	 */
	private function make_instance() {
		return new Ticket_Object();
	}

	/**
	 * Wrapper function: Get ticket object from ticket ID
	 *
	 * @param int $ticket_id
	 * @return Ticket_Object
	 */
	public function get_ticket( $event_id, $ticket_id ) {
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		return $provider->get_ticket( $event_id, $ticket_id );
	}

	/**
	 * Create event and RSVP, return RSVP object.
	 * Also sets timezone for event as this is needed for some tests.
	 *
	 * @param array $args
	 * @return Tribe__Tickets__RSVP
	 */
	private function make_rsvp( $args = [] ) {
		$event_id = $this->factory()->event->create();
		update_post_meta( $event_id, '_EventTimezone', $this->timezone );
		$rsvp_id  = $this->create_rsvp_ticket( $event_id, $args );

		return $this->get_ticket( $event_id, $rsvp_id );
	}

	/**
	 * Create event and Tribe Commerce Ticket, return Ticket object.
	 * Also sets timezone for event as this is needed for some tests.
	 *
	 * @param integer $cost
	 * @param array $args
	 * @return Tribe__Tickets__Commerce__PayPal__Main
	 */
	private function make_ticket( $cost = 1, $args = [] ) {
		$event_id = $this->factory()->event->create();
		update_post_meta( $event_id, '_EventTimezone', $this->timezone );
		$ticket_id  = $this->create_paypal_ticket( $event_id, $cost, $args );

		return $this->get_ticket( $event_id, $ticket_id );
	}

	/**
	 * Create event and RSVP with shared capacity, return RSVP object.
	 * Also sets timezone for event as this is needed for some tests.
	 *
	 * @param integer $cost
	 * @param array $args
	 * @return Tribe__Tickets__Commerce__PayPal__Main
	 */
	private function make_shared_rsvp( $cost = 1, $args = [] ) {
		$event_args = [
			'meta_input' => [
				'_tribe_ticket_use_global_stock' => 1,
				'_tribe_ticket_capacity' => 100,
			]
		];

		$event_id = $this->factory()->event->create( $event_args );
		update_post_meta( $event_id, '_EventTimezone', $this->timezone );
		$ticket_id  = $this->create_rsvp_ticket( $event_id, $args );

		return $this->get_ticket( $event_id, $ticket_id );
	}

	/**
	 * reate event and Tribe Commerce Ticket with shared capacity, return Ticket object.
	 * Also sets timezone for event as this is needed for some tests.
	 *
	 * @param integer $cost
	 * @param array $args
	 * @return Tribe__Tickets__Commerce__PayPal__Main
	 */
	private function make_shared_ticket( $cost = 1, $args = [] ) {
		$event_args = [
			'meta_input' => [
				'_tribe_ticket_use_global_stock' => 1,
				'_tribe_ticket_capacity' => 100,
			]
		];

		$event_id = $this->factory()->event->create( $event_args );
		update_post_meta( $event_id, '_EventTimezone', $this->timezone );
		$ticket_id  = $this->create_paypal_ticket( $event_id, $cost, $args );

		return $this->get_ticket( $event_id, $ticket_id );
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Ticket_Object::class, $sut, 'Object not instantiatable.' );
	}

	/**
	 * @test
	 * it should return the correct event ID
	 */
	public function it_should_return_the_correct_ID() {
		$event_id = $this->factory()->event->create();
		$rsvp_id  = $this->create_rsvp_ticket( $event_id, [] );
		$rsvp     = $this->get_ticket( $event_id, $rsvp_id );

		$this->assertEquals( $event_id, $rsvp->get_event_id(), 'Incorrect event ID reported for RSVP.' );

	}

	/**
	 * @test
	 * it should return the correct start date
	 *
	 * @covers start_date
	 */
	public function it_should_return_the_correct_start_date() {
		$start_date = strtotime( '-1 day' );
		$meta = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', $start_date ),
			],
		];

		$rsvp = $this->make_rsvp( $meta );

		$this->assertEquals( $start_date, $rsvp->start_date(), 'Incorrect start date returned on rsVP by start_date().' );

		$ticket = $this->make_ticket( 1, $meta );

		$this->assertEquals( $start_date, $ticket->start_date(), 'Incorrect start date returned on ticket by start_date().' );
	}

	/**
	 * @test
	 * it should return the correct end date
	 *
	 * @covers end_date
	 */
	public function it_should_return_the_correct_end_date() {
		$end_date = strtotime( '-1 day' );
		$meta = [
			'meta_input' => [
				'_ticket_end_date' => date( 'Y-m-d H:i:s', $end_date ),
			],
		];

		$rsvp = $this->make_rsvp( $meta );

		$this->assertEquals( $end_date, $rsvp->end_date(), 'Incorrect end date returned on RSVP by end_date().' );

		$ticket = $this->make_ticket( 1, $meta );

		$this->assertEquals( $end_date, $ticket->end_date(), 'Incorrect end date returned on ticket by end_date().' );
	}

	/**
	 * @test
	 * it returns false when given no params
	 *
	 * @covers get_date
	 */
	public function it_returns_false_when_given_no_params() {
		$rsvp = $this->make_rsvp();

		$this->assertFalse( $rsvp->get_date(), 'Should return false when Not given params - RSVP.' );

		$ticket = $this->make_ticket();

		$this->assertFalse( $ticket->get_date(), 'Should return false when Not given params - Ticket.' );
	}

	/**
	 * @test
	 * it returns timestamp when given no second param
	 *
	 * @covers get_date
	 */
	public function it_returns_timestamp_when_given_no_second_param() {
		$rsvp = $this->make_rsvp();

		$this->assertIsNumeric( $rsvp->get_date( 'now' ), 'Should return a timestamp - RSVP.' );

		$ticket = $this->make_ticket();

		$this->assertIsNumeric( $ticket->get_date( 'now' ), 'Should return a timestamp - Ticket.' );
	}

	/**
	 * @test
	 * it returns DateTime when second param is false
	 *
	 * @covers get_date
	 */
	public function it_returns_datetime_when_second_param_is_false() {
		$rsvp = $this->make_rsvp();
		// don't forget - $date needs to be a string!
		$r_date = $rsvp->get_date(
			date( 'm/d/y', $rsvp->start_date() ),
			false
		);

		$this->assertInstanceOf( 'DateTime', $r_date, 'Should return a datetime object - RSVP.' );

		$ticket = $this->make_ticket();
		$t_date = $ticket->get_date(
			date( 'm/d/y', $ticket->start_date() ),
			false
		);

		$this->assertInstanceOf( 'DateTime', $t_date, 'Should return a datetime object - Ticket.' );
	}

	/**
	 * @test
	 * it should get the correct timezone
	 *
	 * @covers get_event_timezone
	 */
	 public function it_should_get_the_correct_timezone() {
		$rsvp = $this->make_rsvp();
		$tz = $rsvp->get_event_timezone();

		$this->assertEquals( $this->timezone, $tz->getName() );
	 }

	/**
	 * @test
	 * it correctly identifys a date in range
	 *
	 * @covers date_in_range
	 */
	public function it_correctly_identifys_a_date_in_range() {
		$rsvp          = $this->make_rsvp();
		$date_in_range = $rsvp->date_in_range( strtotime( 'now' ) );

		$this->assertTrue( $date_in_range, 'Misidentified RSVP date in range as out of range.' );

		$ticket        = $this->make_ticket();
		$date_in_range = $ticket->date_in_range( strtotime( 'now' ) );

		$this->assertTrue( $date_in_range, 'Misidentified Ticket date in range as out of range.' );
	}

	/**
	 * @test
	 * it correctly identifies a date earlier than range
	 *
	 * @covers date_in_range
	 */
	public function it_correctly_identifies_a_date_earlier_than_range() {
		$rsvp          = $this->make_rsvp();
		$date_in_range = $rsvp->date_in_range( $this->earlier_date );

		$this->assertFalse( $date_in_range, 'Misidentified RSVP date earlier than range.' );

		$ticket        = $this->make_ticket();
		$date_in_range = $ticket->date_in_range( $this->earlier_date );

		$this->assertFalse( $date_in_range, 'Misidentified Ticket date earlier than range.' );
	}

	/**
	 * @test
	 * it correctly identifies a date later than range
	 *
	 * @covers date_in_range
	 */
	public function it_correctly_identifies_a_date_later_than_range() {
		$rsvp          = $this->make_rsvp();
		$date_in_range = $rsvp->date_in_range( $this->later_date );

		$this->assertFalse( $date_in_range, 'Misidentified RSVP date later than range.' );

		$ticket        = $this->make_ticket();
		$date_in_range = $ticket->date_in_range( $this->later_date );

		$this->assertFalse( $date_in_range, 'Misidentified Ticket date later than range.' );
	}

	/**
	 * @test
	 * it should return true when date is before event date
	 *
	 * @covers date_is_earlier
	 */
	public function it_should_return_true_when_date_is_before_event_date() {
		$rsvp         = $this->make_rsvp();
		$date_earlier = $rsvp->date_is_earlier( $this->earlier_date );

		$this->assertTrue( $date_earlier, 'Misidentified RSVP date earlier than event.' );

		$ticket       = $this->make_ticket();
		$date_earlier = $ticket->date_is_earlier( $this->earlier_date );

		$this->assertTrue( $date_earlier, 'Misidentified Ticket date earlier than event.' );
	}

	/**
	 * @test
	 * it should return false when date is not before event date
	 *
	 * @covers date_is_earlier
	 */
	public function it_should_return_false_when_date_is_not_before_event_date() {
		$rsvp         = $this->make_rsvp();
		$date_earlier = $rsvp->date_is_earlier( $this->later_date );

		$this->assertFalse( $date_earlier, 'Misidentified RSVP date later than event.' );

		$ticket       = $this->make_ticket();
		$date_earlier = $ticket->date_is_earlier( $this->later_date );

		$this->assertFalse( $date_earlier, 'Misidentified Ticket date later than event.' );
	}

	/**
	 * @test
	 * it should return true when date is after event date
	 *
	 * @covers date_is_later
	 */
	public function it_should_return_true_when_date_is_after_event_date() {
		$rsvp         = $this->make_rsvp();
		$date_later = $rsvp->date_is_later( $this->later_date );

		$this->assertTrue( $date_later, 'Misidentified RSVP date earlier than event.' );

		$ticket       = $this->make_ticket();
		$date_later = $ticket->date_is_later( $this->later_date );

		$this->assertTrue( $date_later, 'Misidentified Ticket date earlier than event.' );
	}

	/**
	 * @test
	 * it should return false when date is not after event date
	 *
	 * @covers date_is_later
	 */
	public function it_should_return_false_when_date_is_not_after_event_date() {
		$rsvp         = $this->make_rsvp();
		$date_earlier = $rsvp->date_is_later( $this->earlier_date );

		$this->assertFalse( $date_earlier, 'Misidentified RSVP date later than event.' );

		$ticket       = $this->make_ticket();
		$date_earlier = $ticket->date_is_later( $this->earlier_date );

		$this->assertFalse( $date_earlier, 'Misidentified Ticket date later than event.' );
	}

	/**
	 * @test
	 * it should return correct availability slug
	 *
	 * @covers availability_slug
	 */
	public function it_should_return_correct_availability_slug() {
		$rsvp = $this->make_rsvp();

		$this->assertEquals( 'available', $rsvp->availability_slug(), 'Failed to get correct availability slug on RSVP (available).' );
		$this->assertEquals( 'availability-future', $rsvp->availability_slug( $this->earlier_date ), 'Failed to get correct availability slug on RSVP (availability-future).' );
		$this->assertEquals( 'availability-past', $rsvp->availability_slug( $this->later_date ), 'Failed to get correct availability slug on RSVP (availability-past).' );

		$ticket = $this->make_ticket();

		$this->assertEquals( 'available', $ticket->availability_slug(), 'Failed to get correct availability slug on Ticket (available).' );
		$this->assertEquals( 'availability-future', $ticket->availability_slug( $this->earlier_date ), 'Failed to get correct availability slug on Ticket (availability-future).' );
		$this->assertEquals( 'availability-past', $ticket->availability_slug( $this->later_date ), 'Failed to get correct availability slug on Ticket (availability-past).' );
	}

	/**
	 * @test
	 * it should allow filtering the availability slug
	 *
	 * @covers availability_slug
	 */
	public function it_should_allow_filtering_the_availability_slug() {
		add_filter( 'event_tickets_availability_slug', function() { return 'slug_test'; } );
		$rsvp = $this->make_rsvp();

		$this->assertEquals( 'slug_test', $rsvp->availability_slug(), 'Failed to filter availability slug on RSVP.' );

		$ticket = $this->make_ticket();

		$this->assertEquals( 'slug_test', $ticket->availability_slug(), 'Failed to filter availability slug on Ticket.' );
	}

	/**
	 * @test
	 * it should return the original stock/capacity
	 *
	 * @covers original_stock
	 */
	public function it_should_return_the_original_stock() {
		$rsvp = $this->make_rsvp(
			[
				'meta_input' => [
					'_capacity'   => 10,
					'_stock'      => 5,
					'total_sales' => 5,
				],
			]
		);

		$this->assertEquals( 10, $rsvp->original_stock() );

		$ticket = $this->make_ticket(
			1,
			[
				'meta_input' => [
					'_capacity'   => 10,
					'_stock'      => 5,
					'total_sales' => 5,
				],
			]
		);

		$this->assertEquals( 10, $ticket->original_stock() );
	}

	/**
	 * @test
	 * it should return correct current inventory
	 * Note: inventory is based on attendees! So we can't just manually set it (as stock)
	 *
	 * @covers inventory
	 */
	public function it_should_return_correct_current_inventory() {
		$rsvp = $this->make_rsvp(
			[
				'meta_input' => [
					'_capacity'   => 10,
				],
			]
		);

		$this->create_many_attendees_for_ticket( 5, $rsvp->ID, $rsvp->get_event_id() );

		$this->assertEquals( 5, $rsvp->inventory(), 'Incorrect inventory reported for RSVP.' );

		$ticket = $this->make_ticket(
			1,
			[
				'meta_input' => [
					'_capacity'   => 10,
				],
			]
		);

		$this->create_many_attendees_for_ticket( 5, $ticket->ID, $ticket->get_event_id() );

		$this->assertEquals( 5, $ticket->inventory(), 'Incorrect inventory reported for Ticket.' );
	}

	/**
	 * @test
	 * it should return correct "own" capacity
	 *
	 * @covers capacity
	 */
	public function it_should_return_correct_own_capacity() {
		$rsvp = $this->make_rsvp(
			[
				'meta_input' => [
					'_capacity'   => 10,
				],
			]
		);

		$this->assertEquals( 10, $rsvp->capacity(), 'Incorrect capacity reported for new RSVP.' );

		$this->create_many_attendees_for_ticket( 5, $rsvp->ID, $rsvp->get_event_id() );

		$this->assertEquals( 10, $rsvp->capacity(), 'Incorrect capacity reported for RSVP with attendees.' );

		$ticket = $this->make_ticket(
			1,
			[
				'meta_input' => [
					'_capacity'   => 10,
				],
			]
		);

		$this->assertEquals( 10, $ticket->capacity(), 'Incorrect capacity reported for new ticket.' );

		$this->create_many_attendees_for_ticket( 5, $ticket->ID, $ticket->get_event_id() );

		$this->assertEquals( 10, $ticket->capacity(), 'Incorrect capacity reported for ticket with attendees.' );
	}

	/**
	 * @test
	 * it should return correct "unlimited" capacity
	 *
	 * @covers capacity
	 */
	public function it_should_return_correct_unlimited_capacity() {
		$rsvp = $this->make_rsvp(
			[
				'meta_input' => [
					'_capacity'   => -1,
				],
			]
		);

		$this->assertEquals( -1, $rsvp->capacity(), 'Incorrect capacity reported for new RSVP.' );

		$this->create_many_attendees_for_ticket( 5, $rsvp->ID, $rsvp->get_event_id() );

		$this->assertEquals( -1, $rsvp->capacity(), 'Incorrect capacity reported for RSVP with attendees.' );

		$ticket = $this->make_ticket(
			1,
			[
				'meta_input' => [
					'_capacity'   => -1,
				],
			]
		);

		$this->assertEquals( -1, $ticket->capacity(), 'Incorrect capacity reported for new ticket.' );

		$this->create_many_attendees_for_ticket( 5, $ticket->ID, $ticket->get_event_id() );

		$this->assertEquals( -1, $ticket->capacity(), 'Incorrect capacity reported for ticket with attendees.' );
	}
}
