<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use TEC\Tickets\Tests\Recurring_Tickets\Without_Recurrence_Tier;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * The list `get_tickets()` returns can be changed, fresh or cached, knowing which date was asked for.
 */
class Get_Tickets_Filter_Test extends WPTestCase {
	use Without_Recurrence_Tier;
	use Ticket_Rows;
	use Ticket_Maker;

	/**
	 * @test
	 */
	public function it_should_pass_every_list_through_the_filter(): void {
		$event   = $this->create_recurring_event();
		$date    = $this->get_dates( $event )[1]->provisional_id;
		$keep    = $this->create_tc_ticket( $event, 10 );
		$removed = $this->create_tc_ticket( $event, 20 );
		$calls   = [];
		add_filter(
			'tec_tickets_get_tickets',
			static function ( array $tickets, $post_id, $context, $provider ) use ( &$calls, $removed ) {
				$calls[] = [ $post_id, $context, $provider ];

				return array_values( array_filter( $tickets, static fn( Ticket_Object $ticket ) => $removed !== $ticket->ID ) );
			},
			10,
			4
		);

		$fresh  = tribe( Module::class )->get_tickets( $date );
		$cached = tribe( Module::class )->get_tickets( $date );

		$this->assertSame( [ $keep ], array_column( $fresh, 'ID' ) );
		$this->assertSame( [ $keep ], array_column( $cached, 'ID' ) );
		$this->assertCount( 2, $calls, 'The cached list is filtered too.' );
		$this->assertSame( $date, $calls[0][0], 'The date asked for, not its event.' );
		$this->assertNull( $calls[0][1] );
		$this->assertInstanceOf( Module::class, $calls[0][2] );
	}
}
