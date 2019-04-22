<?php
namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class Template_TagTest extends \Codeception\TestCase\WPTestCase {
	use RSVP_Ticket_Maker;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->factory()->event = new Event();
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * it not should allow tickets on posts by default
	 *
	 * @since TBD
	 *
	 * @covers tribe_tickets_post_type_enabled()
	 */
	public function it_should_not_allow_tickets_on_posts_by_default() {
		$allowed = tribe_tickets_post_type_enabled( 'post' );

		$this->assertFalse( $allowed );
	}

	/**
	 * @test
	 * it should allow tickets on posts when enabled
	 *
	 * @since TBD
	 *
	 * @covers tribe_tickets_post_type_enabled()
	 */
	public function it_should_allow_tickets_on_posts_when_enabled() {
		tribe_update_option( 'ticket-enabled-post-types', [
			'tribe_events',
			'post',
		] );

		$allowed = tribe_tickets_post_type_enabled( 'post' );

		$this->assertTrue( $allowed );
	}

	/**
	 * @test
	 * it returns the parent event of a ticket
	 *
	 * @since TBD
	 *
	 * @covers tribe_tickets_parent_post()
	 */
	public function it_should_return_the_parent_event_of_a_ticket() {
		$event_id  = $this->factory()->event->create();
		$rsvp_id   = $this->create_rsvp_ticket( $event_id );
		$parent_id = tribe_tickets_parent_post( $rsvp_id );

		$this->assertEquals( $rsvp_id, $event_id );
	}


	 /**
	 * @test
	 * it returns the parent non-event post of a ticket
	 *
	 * @since TBD
	 *
	 * @covers tribe_tickets_parent_post()
	 */
	public function it_should_return_the_parent_non_event_post_of_a_ticket() {
		$this->markTestSkipped("Test not finished");
	}


}
