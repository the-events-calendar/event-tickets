<?php
/**
 * Tests for the V2 Ticket class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;

/**
 * Class Ticket_Test
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Ticket_Test extends WPTestCase {

	/**
	 * @test
	 */
	public function it_should_be_instantiable(): void {
		$ticket = tribe( Ticket::class );

		$this->assertInstanceOf( Ticket::class, $ticket );
	}

	/**
	 * @test
	 */
	public function it_should_return_the_ticket_type(): void {
		$ticket = tribe( Ticket::class );

		$this->assertSame( Constants::TC_RSVP_TYPE, $ticket->get_type() );
	}
}
