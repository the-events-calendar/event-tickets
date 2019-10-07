<?php

namespace Tribe\Tickets\Ticket_Object;

use Tribe\Tickets\Test\Testcases\Ticket_Object_TestCase;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

class UTCTest extends Ticket_Object_TestCase {

	protected $timezone = 'UTC';

	/**
	 * @test
	 * it should return the correct start date
	 *
	 * @covers Ticket_Object::start_date
	 */
	public function it_should_return_the_correct_start_date() {
		$start_date = strtotime( '-10 minutes' );
		$meta       = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', $start_date ),
			],
		];

		$rsvp = $this->make_rsvp( $meta );

		$this->assertEquals( $start_date, $rsvp->start_date(), 'Incorrect start date returned on RSVP by start_date().' );

		$ticket = $this->make_ticket( 1, $meta );

		$this->assertEquals( $start_date, $ticket->start_date(), 'Incorrect start date returned on ticket by start_date().' );
	}

	/**
	 * @test
	 * it should return the correct end date
	 *
	 * @covers Ticket_Object::end_date
	 */
	public function it_should_return_the_correct_end_date() {
		$end_date = strtotime( '-10 minutes' );
		$meta     = [
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
	 * @covers Ticket_Object::get_date
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
	 * @covers Ticket_Object::get_date
	 */
	public function it_returns_timestamp_when_given_no_second_param() {
		$rsvp = $this->make_rsvp();

		$this->assertTrue( is_numeric( $rsvp->get_date( 'now' ) ), 'Should return a timestamp - RSVP.' );

		$ticket = $this->make_ticket();

		$this->assertTrue( is_numeric( $ticket->get_date( 'now' ) ), 'Should return a timestamp - Ticket.' );
	}

	/**
	 * @test
	 * it returns DateTime when second param is false
	 *
	 * @covers Ticket_Object::get_date
	 */
	public function it_returns_datetime_when_second_param_is_false() {
		$rsvp = $this->make_rsvp();
		// don't forget - $date needs to be a string!
		$r_date = $rsvp->get_date( date( 'm/d/y', $rsvp->start_date() ), false );

		$this->assertInstanceOf( 'DateTime', $r_date, 'Should return a datetime object - RSVP.' );

		$ticket = $this->make_ticket();
		$t_date = $ticket->get_date( date( 'm/d/y', $ticket->start_date() ), false );

		$this->assertInstanceOf( 'DateTime', $t_date, 'Should return a datetime object - Ticket.' );
	}

	/**
	 * @test
	 * it should get the correct timezone
	 *
	 * @covers Ticket_Object::get_event_timezone
	 */
	public function it_should_get_the_correct_timezone() {
		$rsvp = $this->make_rsvp();
		$tz   = $rsvp->get_event_timezone();

		$this->assertEquals( $this->timezone, $tz->getName() );
	}

	/**
	 * @test
	 * it correctly identifies a date in range
	 *
	 * @covers Ticket_Object::date_in_range
	 */
	public function it_correctly_identifies_a_date_in_range_with_string() {
		$rsvp          = $this->make_rsvp();
		$date_in_range = $rsvp->date_in_range( date( 'Y-m-d H:i:s', $this->now_date ) );

		$this->assertTrue( $date_in_range, 'Misidentified RSVP date in range as out of range.' );

		$ticket        = $this->make_ticket();
		$date_in_range = $ticket->date_in_range( date( 'Y-m-d H:i:s', $this->now_date ) );

		$this->assertTrue( $date_in_range, 'Misidentified Ticket date in range as out of range.' );
	}

	/**
	 * @test
	 * it correctly identifies a date in range
	 *
	 * @covers Ticket_Object::date_in_range
	 */
	public function it_correctly_identifies_a_date_in_range_with_timestamp() {
		$rsvp          = $this->make_rsvp();
		$date_in_range = $rsvp->date_in_range( $this->now_date );

		$this->assertTrue( $date_in_range, 'Misidentified RSVP date in range as out of range.' );

		$ticket        = $this->make_ticket();
		$date_in_range = $ticket->date_in_range( $this->now_date );

		$this->assertTrue( $date_in_range, 'Misidentified Ticket date in range as out of range.' );
	}

	/**
	 * @test
	 * it correctly identifies a date earlier than range
	 *
	 * @covers Ticket_Object::date_in_range
	 */
	public function it_correctly_identifies_a_date_earlier_than_range() {
		$rsvp          = $this->make_rsvp();
		$date_in_range = $rsvp->date_in_range( $this->earlier_date - MINUTE_IN_SECONDS );

		$this->assertFalse( $date_in_range, 'Misidentified RSVP date earlier than range.' );

		$ticket        = $this->make_ticket();
		$date_in_range = $ticket->date_in_range( $this->earlier_date - MINUTE_IN_SECONDS );

		$this->assertFalse( $date_in_range, 'Misidentified Ticket date earlier than range.' );
	}

	/**
	 * @test
	 * it correctly identifies a date later than range
	 *
	 * @covers Ticket_Object::date_in_range
	 */
	public function it_correctly_identifies_a_date_later_than_range() {
		$rsvp          = $this->make_rsvp();
		$date_in_range = $rsvp->date_in_range( $this->later_date + MINUTE_IN_SECONDS );

		$this->assertFalse( $date_in_range, 'Misidentified RSVP date later than range.' );

		$ticket        = $this->make_ticket();
		$date_in_range = $ticket->date_in_range( $this->later_date + MINUTE_IN_SECONDS );

		$this->assertFalse( $date_in_range, 'Misidentified Ticket date later than range.' );
	}

	/**
	 * @test
	 * it should return true when date is before event date
	 *
	 * @covers Ticket_Object::date_is_earlier
	 */
	public function it_should_return_true_when_date_is_before_event_date() {
		$rsvp         = $this->make_rsvp();
		$date_earlier = $rsvp->date_is_earlier( $this->earlier_date - MINUTE_IN_SECONDS );

		$this->assertTrue( $date_earlier, 'Misidentified RSVP date earlier than event.' );

		$ticket       = $this->make_ticket();
		$date_earlier = $ticket->date_is_earlier( $this->earlier_date - MINUTE_IN_SECONDS );

		$this->assertTrue( $date_earlier, 'Misidentified Ticket date earlier than event.' );
	}

	/**
	 * @test
	 * it should return true when date is before event date in future
	 *
	 * @covers Ticket_Object::date_is_earlier
	 */
	public function it_should_return_true_when_date_is_before_event_date_in_future() {
		$args = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', $this->later_date ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', $this->later_date + HOUR_IN_SECONDS ),
			],
		];

		$rsvp         = $this->make_rsvp( $args );
		$date_earlier = $rsvp->date_is_earlier( $this->earlier_date - MINUTE_IN_SECONDS );

		$this->assertTrue( $date_earlier, 'Misidentified RSVP date earlier than event.' );

		$ticket       = $this->make_ticket( 1, $args );
		$date_earlier = $ticket->date_is_earlier( $this->earlier_date - MINUTE_IN_SECONDS );

		$this->assertTrue( $date_earlier, 'Misidentified Ticket date earlier than event.' );
	}

	/**
	 * @test
	 * it should return false when date is equal to event start date
	 *
	 * @covers Ticket_Object::date_is_earlier
	 */
	public function it_should_return_false_when_date_is_equal_to_event_start_date() {
		$rsvp         = $this->make_rsvp();
		$date_earlier = $rsvp->date_is_earlier( $this->earlier_date );

		$this->assertFalse( $date_earlier, 'Misidentified RSVP date earlier than event.' );

		$ticket       = $this->make_ticket();
		$date_earlier = $ticket->date_is_earlier( $this->earlier_date );

		$this->assertFalse( $date_earlier, 'Misidentified Ticket date earlier than event.' );
	}

	/**
	 * @test
	 * it should return false when date is not before event date
	 *
	 * @covers Ticket_Object::date_is_earlier
	 */
	public function it_should_return_false_when_date_is_not_before_event_date() {
		$rsvp         = $this->make_rsvp();
		$date_earlier = $rsvp->date_is_earlier( $this->later_date );

		$this->assertFalse( $date_earlier, 'Misidentified RSVP date earlier than event.' );

		$ticket       = $this->make_ticket();
		$date_earlier = $ticket->date_is_earlier( $this->later_date );

		$this->assertFalse( $date_earlier, 'Misidentified Ticket date earlier than event.' );
	}

	/**
	 * @test
	 * it should return false when date is not before event date in past
	 *
	 * @covers Ticket_Object::date_is_earlier
	 */
	public function it_should_return_false_when_date_is_not_before_event_date_in_past() {
		$args = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', $this->earlier_date - HOUR_IN_SECONDS ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', $this->earlier_date ),
			],
		];

		$rsvp         = $this->make_rsvp( $args );
		$date_earlier = $rsvp->date_is_earlier( $this->later_date );

		$this->assertFalse( $date_earlier, 'Misidentified RSVP date earlier than event.' );

		$ticket       = $this->make_ticket( 1, $args );
		$date_earlier = $ticket->date_is_earlier( $this->later_date );

		$this->assertFalse( $date_earlier, 'Misidentified Ticket date earlier than event.' );
	}

	/**
	 * @test
	 * it should return true when date is after event date
	 *
	 * @covers Ticket_Object::date_is_later
	 */
	public function it_should_return_true_when_date_is_after_event_date() {
		$rsvp       = $this->make_rsvp();
		$date_later = $rsvp->date_is_later( $this->later_date + MINUTE_IN_SECONDS );

		$this->assertTrue( $date_later, 'Misidentified RSVP date later than event.' );

		$ticket     = $this->make_ticket();
		$date_later = $ticket->date_is_later( $this->later_date + MINUTE_IN_SECONDS );

		$this->assertTrue( $date_later, 'Misidentified Ticket date later than event.' );
	}

	/**
	 * @test
	 * it should return true when date is after event date in past
	 *
	 * @covers Ticket_Object::date_is_later
	 */
	public function it_should_return_true_when_date_is_after_event_date_in_past() {
		$args = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', $this->earlier_date - HOUR_IN_SECONDS ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', $this->earlier_date ),
			],
		];

		$rsvp       = $this->make_rsvp( $args );
		$date_later = $rsvp->date_is_later();

		$this->assertTrue( $date_later, 'Misidentified RSVP date later than event.' );

		$ticket     = $this->make_ticket( 1, $args );
		$date_later = $ticket->date_is_later();

		$this->assertTrue( $date_later, 'Misidentified Ticket date later than event.' );
	}

	/**
	 * @test
	 * it should return false when date is equal to event end date
	 *
	 * @covers Ticket_Object::date_is_later
	 */
	public function it_should_return_false_when_date_is_equal_to_event_end_date() {
		$rsvp         = $this->make_rsvp();
		$date_earlier = $rsvp->date_is_later( $this->later_date );

		$this->assertFalse( $date_earlier, 'Misidentified RSVP date later than event.' );

		$ticket       = $this->make_ticket();
		$date_earlier = $ticket->date_is_later( $this->later_date );

		$this->assertFalse( $date_earlier, 'Misidentified Ticket date later than event.' );
	}

	/**
	 * @test
	 * it should return false when date is not after event date
	 *
	 * @covers Ticket_Object::date_is_later
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
	 * it should return false when date is not after event date in future
	 *
	 * @covers Ticket_Object::date_is_later
	 */
	public function it_should_return_false_when_date_is_not_after_event_date_in_future() {
		$args = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', $this->later_date ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', $this->later_date + HOUR_IN_SECONDS ),
			],
		];

		$rsvp         = $this->make_rsvp( $args );
		$date_earlier = $rsvp->date_is_later();

		$this->assertFalse( $date_earlier, 'Misidentified RSVP date later than event.' );

		$ticket       = $this->make_ticket( 1, $args );
		$date_earlier = $ticket->date_is_later();

		$this->assertFalse( $date_earlier, 'Misidentified Ticket date later than event.' );
	}

	/**
	 * @test
	 * it should return correct availability slug
	 *
	 * @covers Ticket_Object::availability_slug
	 */
	public function it_should_return_correct_availability_slug() {
		$rsvp = $this->make_rsvp();

		$this->assertEquals( 'available', $rsvp->availability_slug(), 'Failed to get correct availability slug on RSVP (available).' );
		$this->assertEquals( 'availability-future', $rsvp->availability_slug( $this->earlier_date - MINUTE_IN_SECONDS ), 'Failed to get correct availability slug on RSVP (availability-future).' );
		$this->assertEquals( 'availability-past', $rsvp->availability_slug( $this->later_date + MINUTE_IN_SECONDS ), 'Failed to get correct availability slug on RSVP (availability-past).' );

		$ticket = $this->make_ticket();

		$this->assertEquals( 'available', $ticket->availability_slug(), 'Failed to get correct availability slug on Ticket (available).' );
		$this->assertEquals( 'availability-future', $ticket->availability_slug( $this->earlier_date - MINUTE_IN_SECONDS ), 'Failed to get correct availability slug on Ticket (availability-future).' );
		$this->assertEquals( 'availability-past', $ticket->availability_slug( $this->later_date + MINUTE_IN_SECONDS ), 'Failed to get correct availability slug on Ticket (availability-past).' );
	}

	/**
	 * @test
	 * it should return correct availability slug in past
	 *
	 * @covers Ticket_Object::availability_slug
	 */
	public function it_should_return_correct_availability_slug_in_past() {
		$args = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', $this->earlier_date - HOUR_IN_SECONDS ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', $this->earlier_date ),
			],
		];

		$rsvp = $this->make_rsvp( $args );

		$this->assertEquals( 'availability-past', $rsvp->availability_slug(), 'Failed to get correct availability slug on RSVP (availability-past).' );

		$ticket = $this->make_ticket( 1, $args );

		$this->assertEquals( 'availability-past', $ticket->availability_slug(), 'Failed to get correct availability slug on Ticket (availability-past).' );
	}

	/**
	 * @test
	 * it should return correct availability slug in future
	 *
	 * @covers Ticket_Object::availability_slug
	 */
	public function it_should_return_correct_availability_slug_in_future() {
		$args = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', $this->later_date ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', $this->later_date + HOUR_IN_SECONDS ),
			],
		];

		$rsvp = $this->make_rsvp( $args );

		$this->assertEquals( 'availability-future', $rsvp->availability_slug(), 'Failed to get correct availability slug on RSVP (availability-future).' );

		$ticket = $this->make_ticket( 1, $args );

		$this->assertEquals( 'availability-future', $ticket->availability_slug(), 'Failed to get correct availability slug on Ticket (availability-future).' );
	}

	/**
	 * @test
	 * it should return correct availability slug with date string
	 *
	 * @covers Ticket_Object::availability_slug
	 */
	public function it_should_return_correct_availability_slug_with_date_string() {
		$rsvp = $this->make_rsvp();

		$this->assertEquals( 'available', $rsvp->availability_slug( date( 'Y-m-d H:i:s' ) ), 'Failed to get correct availability slug on RSVP (available).' );
		$this->assertEquals( 'availability-future', $rsvp->availability_slug( date( 'Y-m-d H:i:s', $this->earlier_date - MINUTE_IN_SECONDS ) ), 'Failed to get correct availability slug on RSVP (availability-future).' );
		$this->assertEquals( 'availability-past', $rsvp->availability_slug( date( 'Y-m-d H:i:s', $this->later_date + MINUTE_IN_SECONDS ) ), 'Failed to get correct availability slug on RSVP (availability-past).' );

		$ticket = $this->make_ticket();

		$this->assertEquals( 'available', $ticket->availability_slug( date( 'Y-m-d H:i:s' ) ), 'Failed to get correct availability slug on Ticket (available).' );
		$this->assertEquals( 'availability-future', $ticket->availability_slug( date( 'Y-m-d H:i:s', $this->earlier_date - MINUTE_IN_SECONDS ) ), 'Failed to get correct availability slug on Ticket (availability-future).' );
		$this->assertEquals( 'availability-past', $ticket->availability_slug( date( 'Y-m-d H:i:s', $this->later_date + MINUTE_IN_SECONDS ) ), 'Failed to get correct availability slug on Ticket (availability-past).' );
	}

	/**
	 * @test
	 * it should allow filtering the availability slug
	 *
	 * @covers Ticket_Object::availability_slug
	 */
	public function it_should_allow_filtering_the_availability_slug() {
		add_filter( 'event_tickets_availability_slug', static function () {
			return 'slug_test';
		} );
		$rsvp = $this->make_rsvp();

		$this->assertEquals( 'slug_test', $rsvp->availability_slug(), 'Failed to filter availability slug on RSVP.' );
		$this->assertEquals( 'slug_test', $rsvp->availability_slug( $this->earlier_date - MINUTE_IN_SECONDS ), 'Failed to filter availability slug on RSVP.' );
		$this->assertEquals( 'slug_test', $rsvp->availability_slug( $this->later_date + MINUTE_IN_SECONDS ), 'Failed to filter availability slug on RSVP.' );

		$ticket = $this->make_ticket();

		$this->assertEquals( 'slug_test', $ticket->availability_slug(), 'Failed to filter availability slug on Ticket.' );
		$this->assertEquals( 'slug_test', $ticket->availability_slug( $this->earlier_date - MINUTE_IN_SECONDS ), 'Failed to filter availability slug on Ticket.' );
		$this->assertEquals( 'slug_test', $ticket->availability_slug( $this->later_date + MINUTE_IN_SECONDS ), 'Failed to filter availability slug on Ticket.' );
	}
}
