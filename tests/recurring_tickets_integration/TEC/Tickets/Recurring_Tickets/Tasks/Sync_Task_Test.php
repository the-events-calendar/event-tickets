<?php

namespace TEC\Tickets\Recurring_Tickets\Tasks;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\Shepherd\Config;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * An event with more template-date pairs than Sync writes in a request is synced in the background.
 */
class Sync_Task_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;

	/**
	 * @before
	 */
	public function empty_table(): void {
		( new Tickets() )->empty_table();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_sync_an_event_above_the_inline_limit_in_one_background_task(): void {
		add_filter( 'tec_tickets_recurring_tickets_sync_inline_limit', static fn() => 4 );
		$prefix    = Config::get_hook_prefix();
		$created   = [];
		$scheduled = 0;
		add_action( "shepherd_{$prefix}_task_created", static function ( $task ) use ( &$created ) {
			$created[] = $task;
		} );
		add_action( "shepherd_{$prefix}_task_already_scheduled", static function () use ( &$scheduled ) {
			++$scheduled;
		} );
		$event = $this->create_recurring_event();
		$first = $this->create_tc_ticket( $event, 10 );
		$this->assertCount( 3, tribe( Rows::class )->get_by_template( $first ), 'Three pairs are written in the request.' );

		// Six pairs now: none is written in the request.
		$second = $this->create_tc_ticket( $event, 20 );
		$this->assertCount( 0, tribe( Rows::class )->get_by_template( $second ) );
		$this->assertCount( 1, $created );
		$this->assertInstanceOf( Sync_Task::class, $created[0] );
		$this->assertSame( [ $event ], $created[0]->get_args() );

		// A second change while the task waits schedules nothing more.
		$this->create_tc_ticket( $event, 30 );
		$this->assertCount( 1, $created );
		$this->assertSame( 1, $scheduled );

		$created[0]->process();

		$this->assertCount( 9, tribe( Rows::class )->get_by_post( $event ) );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_task_without_an_event(): void {
		$this->expectException( \InvalidArgumentException::class );

		new Sync_Task( 0 );
	}
}
