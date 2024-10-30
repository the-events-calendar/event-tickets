<?php

namespace TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies;

use Codeception\TestCase\WPTestCase;
use TEC\Events\Custom_Tables\V1\Migration\Migration_Exception;
use TEC\Events_Pro\Custom_Tables\V1\Events\Recurrence;
use TEC\Tickets\Commerce\Module as Commerce;
use Tribe\Events_Pro\Tests\Traits\CT1\CT1_Fixtures;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;

class Ticketed_Multi_Rule_Event_Migration_Strategy_Test extends WPTestCase {
	use CT1_Fixtures;

	/**
	 * @before
	 */
	public function enable_ticket_providers(): void {
		add_filter( 'tribe_tickets_get_modules', static function ( array $modules ): array {
			$modules[ PayPal::class ]   = 'PayPal';
			$modules[ Commerce::class ] = 'Commerce';

			return $modules;
		} );
	}

	/**
	 * It should throw if constructed on a non-event post
	 *
	 * @test
	 */
	public function should_throw_if_constructed_on_a_non_event_post(): void {
		$post_id = static::factory()->post->create();

		$this->expectException( Migration_Exception::class );

		new Ticketed_Multi_Rule_Event_Migration_Strategy( $post_id, true );
	}

	/**
	 * It should throw if trying to build on non-recurring event
	 *
	 * @test
	 */
	public function should_throw_if_trying_to_build_on_non_recurring_event(): void {
		$event = $this->given_a_non_migrated_single_event();

		$this->expectException( Migration_Exception::class );

		new Ticketed_Multi_Rule_Event_Migration_Strategy( $event->ID, true );
	}

	private function given_a_non_migrated_multi_rule_recurring_event(): \WP_Post {
		$recurrence = static function ( int $id ): array {
			return ( new Recurrence() )
				->with_start_date( get_post_meta( $id, '_EventStartDate', true ) )
				->with_end_date( get_post_meta( $id, '_EventEndDate', true ) )
				->with_weekly_recurrence()
				->with_end_after( 50 )
				->with_monthly_recurrence( 1, false, 23 )
				->with_end_after( 5 )
				->to_event_recurrence();
		};

		return $this->given_a_non_migrated_recurring_event( $recurrence );
	}


	/**
	 * It should throw if recurring event has no tickets
	 *
	 * @test
	 */
	public function should_throw_if_recurring_event_has_no_tickets(): void {
		$event = $this->given_a_non_migrated_multi_rule_recurring_event();

		$this->expectException( Migration_Exception::class );

		new Ticketed_Multi_Rule_Event_Migration_Strategy( $event->ID, true );
	}
}