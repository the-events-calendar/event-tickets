<?php
namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class Template_TagsTest extends \Codeception\TestCase\WPTestCase {
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
	 * it should return the post id - events support tickets by default
	 *
	 * @since TBD
	 *
	 * @covers tribe_tickets_parent_post()
	 */
	public function it_should_return_the_post_id_events_support_tickets_by_default() {
		$event_id = $this->factory()->event->create();
		$parent   = tribe_tickets_parent_post( $event_id );

		$this->assertEquals( $event_id, $parent->ID );
	}

	 /**
	 * @test
	 * it should return the non event post id if it supports tickets
	 *
	 * @since TBD
	 *
	 * @covers tribe_tickets_parent_post()
	 */
	public function it_should_return_the_non_event_post_id_if_it_supports_tickets() {
		tribe_update_option( 'ticket-enabled-post-types', [
			'tribe_events',
			'post',
		] );

		$non_event_id = wp_insert_post( ['id' => 1337] );
		$parent   = tribe_tickets_parent_post( $non_event_id );

		$this->assertEquals( $non_event_id, $parent );
	}

	/**
	* @test
	* it should return null if it doesn not supports tickets
	*
	* @since TBD
	*
	* @covers tribe_tickets_parent_post()
	*/
	public function it_should_return_null_if_it_doesn_not_supports_tickets() {
		tribe_update_option( 'ticket-enabled-post-types', [
			'tribe_events',
		] );

		$non_event_id = wp_insert_post( ['id' => 1337] );
		$parent   = tribe_tickets_parent_post( $non_event_id );

		$this->assertNull( $parent );
	}

	/**
	* @test
	* it should return true if event has tickets
	*
	* @since TBD
	*
	* @covers tribe_events_has_tickets()
	*/
	public function it_should_return_true_if_event_has_tickets() {

	}

	/**
	* @test
	* it should return true if non-event post has tickets
	*
	* @since TBD
	*
	* @covers tribe_events_has_tickets()
	*/
	public function it_should_return_true_if_non_event_post_has_tickets() {

	}

	/**
	* @test
	* it should return false if event has no tickets
	*
	* @since TBD
	*
	* @covers tribe_events_has_tickets()
	*/
	public function it_should_return_false_if_event_has_no_tickets() {

	}

}
