<?php

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Traits\With_Square_Sync_Enabled;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Controller as Syncs_Controller;
use ActionScheduler_SimpleSchedule;
use ActionScheduler_Store;
use DateTime;
use Closure;
use Generator;

class Regulator_Test extends Controller_Test_Case {
	use With_Uopz;
	use With_Square_Sync_Enabled;
	use Ticket_Maker;
	use Order_Maker;

	protected string $controller_class = Regulator::class;

	/**
	 * @test
	 */
	public function it_should_reset_rate_limited_storage(): void {
		$controller = $this->make_controller();

		tribe_update_option( 'square_rate_limited', [ 'test_arg' => 'test_value' ] );

		$this->assertNotEmpty( tribe_get_option( 'square_rate_limited' ) );

		$controller->reset_rate_limited_storage();

		$this->assertEmpty( tribe_get_option( 'square_rate_limited' ) );
	}

	/**
	 * @test
	 */
	public function it_should_schedule_and_unschedule_actions(): void {
		$controller = $this->make_controller();

		$args = [ 'test_arg' => 'test_value' ];

		$this->assertFalse( as_has_scheduled_action( 'test_action', $args, Syncs_Controller::AS_SYNC_ACTION_GROUP ) );

		$controller->schedule( 'test_action', $args );

		$this->assertTrue( as_has_scheduled_action( 'test_action', $args, Syncs_Controller::AS_SYNC_ACTION_GROUP ) );

		$controller->unschedule( 'test_action', $args );

		$this->assertFalse( as_has_scheduled_action( 'test_action', $args, Syncs_Controller::AS_SYNC_ACTION_GROUP ) );
	}

	/**
	 * @test
	 */
	public function it_should_respect_unique_flag(): void {
		$controller = $this->make_controller();

		$args = [ 'test_arg' => 'test_value' ];

		$this->assertFalse( as_has_scheduled_action( 'test_action', $args, Syncs_Controller::AS_SYNC_ACTION_GROUP ) );

		$controller->schedule( 'test_action', $args, 0, true );

		$this->assertTrue( as_has_scheduled_action( 'test_action', $args, Syncs_Controller::AS_SYNC_ACTION_GROUP ) );

		$this->assertCount( 1, $this->get_scheduled_actions( 'test_action', $args ) );

		$controller->schedule( 'test_action', $args, 0, true );

		$this->assertCount( 1, $this->get_scheduled_actions( 'test_action', $args ) );

		$controller->schedule( 'test_action', $args, 0, false );

		$this->assertCount( 2, $this->get_scheduled_actions( 'test_action', $args ) );

		$controller->unschedule( 'test_action', $args );

		$this->assertCount( 1, $this->get_scheduled_actions( 'test_action', $args ) );

		$controller->unschedule( 'test_action', $args );

		$this->assertCount( 0, $this->get_scheduled_actions( 'test_action', $args ) );
	}

	/**
	 * @test
	 * @dataProvider schedule_with_delays_generator
	 */
	public function it_should_schedule_with_delays_when_rate_limited( Closure $fixture ): void {
		[ $min, $max ] = $fixture();

		$controller = $this->make_controller();

		$args = [ 'test_arg' => 'test_value' ];

		$this->assertFalse( as_has_scheduled_action( 'test_action', $args, Syncs_Controller::AS_SYNC_ACTION_GROUP ) );

		$controller->schedule( 'test_action', $args );

		$actions = $this->get_scheduled_actions( 'test_action', $args );
		$action = reset( $actions );

		$schedule = $action->get_schedule();

		$this->assertInstanceOf( ActionScheduler_SimpleSchedule::class, $schedule );

		$date = $schedule->get_date();

		$this->assertInstanceOf( DateTime::class, $date );

		$min_human_readable = gmdate( 'Y-m-d H:i:s', time() + $min );
		$max_human_readable = gmdate( 'Y-m-d H:i:s', time() + $max );
		$actual_human_readable = gmdate( 'Y-m-d H:i:s', $date->getTimestamp() );

		$this->assertGreaterThanOrEqual( time() + $min, $date->getTimestamp(), "Expected minimum delay of $min_human_readable, got $actual_human_readable" );
		$this->assertLessThanOrEqual( time() + $max, $date->getTimestamp(), "Expected maximum delay of $max_human_readable, got $actual_human_readable" );
		$this->assertLessThanOrEqual( time() + Regulator::MAX_DELAY, $date->getTimestamp(), "Expected maximum delay of " . gmdate( 'Y-m-d H:i:s', time() + Regulator::MAX_DELAY ) . ", got $actual_human_readable" );
	}

	public function schedule_with_delays_generator(): Generator {
		yield 'no delay' => [ fn (): array => [ 0, 0 ] ];
		yield 'second-stage' => [ function (): array {
			tribe_update_option( 'square_rate_limited', [ time() ] );
			Regulator::reset_random_delays();
			return [ 30, 90 ];
		} ];
		yield 'third-stage' => [ function (): array {
			tribe_update_option( 'square_rate_limited', [ time() - 20, time() ] );
			Regulator::reset_random_delays();
			return [ 120, 360 ];
		} ];
		yield 'fourth-stage' => [ function (): array {
			tribe_update_option( 'square_rate_limited', [ time() - 140, time() - 120, time() - 100, time() - 80, time() ] );
			Regulator::reset_random_delays();
			return [ 900, 1500 ];
		} ];
		yield 'fourth-stage - multiple rate limited' => [ function (): array {
			tribe_update_option( 'square_rate_limited', [ time() - 100, time() - 60, time() - 50, time() - 40, time() ] );
			Regulator::reset_random_delays();
			return [ 600, 1200 ];
		} ];
		yield 'fifth-stage' => [ function (): array {
			tribe_update_option( 'square_rate_limited', [ time() - 170, time() - 160, time() - 150, time() - 140, time() - 130, time() - 120, time() - 110, time() - 100, time() - 60, time() - 55, time() - 50, time() ] );
			Regulator::reset_random_delays();
			return [ 1800, 2700 ];
		} ];

		yield 'not-above-2-hours' => [ function (): array {
			tribe_update_option( 'square_rate_limited', [ time() - 310, time() - 290, time() - 280, time() - 270, time() - 260, time() - 250, time() - 240, time() - 230, time() - 220, time() - 210, time() - 200, time() - 190, time() - 184, time() - 182, time() - 181, time() - 1800, time() ] );
			Regulator::reset_random_delays();
			return [ 105 * MINUTE_IN_SECONDS, 2 * HOUR_IN_SECONDS ];
		} ];
	}

	/**
	 * @test
	 */
	public function it_should_schedule_sync_for_each_post_type(): void {
		$controller = $this->make_controller();

		$this->assertFalse( as_has_scheduled_action( Items_Sync::HOOK_SYNC_ACTION, [ 'post' ], Syncs_Controller::AS_SYNC_ACTION_GROUP ) );
		$this->assertFalse( as_has_scheduled_action( Items_Sync::HOOK_SYNC_ACTION, [ 'page' ], Syncs_Controller::AS_SYNC_ACTION_GROUP ) );
		$this->assertFalse( as_has_scheduled_action( Items_Sync::HOOK_SYNC_ACTION, [ 'tribe_events' ], Syncs_Controller::AS_SYNC_ACTION_GROUP ) );

		$this->assertFalse( Syncs_Controller::is_sync_completed() );
		$this->assertFalse( Syncs_Controller::is_sync_in_progress() );

		$controller->schedule_sync_for_each_post_type();

		$this->assertTrue( as_has_scheduled_action( Items_Sync::HOOK_SYNC_ACTION, [ 'post' ], Syncs_Controller::AS_SYNC_ACTION_GROUP ) );
		$this->assertTrue( as_has_scheduled_action( Items_Sync::HOOK_SYNC_ACTION, [ 'page' ], Syncs_Controller::AS_SYNC_ACTION_GROUP ) );
		$this->assertTrue( as_has_scheduled_action( Items_Sync::HOOK_SYNC_ACTION, [ 'tribe_events' ], Syncs_Controller::AS_SYNC_ACTION_GROUP ) );

		$this->assertFalse( Syncs_Controller::is_sync_completed() );
		$this->assertTrue( Syncs_Controller::is_sync_in_progress() );
	}

	/**
	 * @test
	 */
	public function it_should_syncs_tickets_inventory(): void {}

	protected function get_scheduled_actions( string $hook, ?array $args = null, ?string $status = null ): array {
		$args = [
			'hook'  => $hook,
			'group' => Syncs_Controller::AS_SYNC_ACTION_GROUP,
			'args'  => $args,
			'status' => $status ?? ActionScheduler_Store::STATUS_PENDING,
		];

		return as_get_scheduled_actions( $args );
	}

	protected function assert_rescheduled_after_rate_limit() {}
}
