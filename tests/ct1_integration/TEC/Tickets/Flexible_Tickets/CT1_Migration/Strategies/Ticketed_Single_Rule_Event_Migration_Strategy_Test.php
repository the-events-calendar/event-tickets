<?php

namespace TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies;

use Codeception\TestCase\WPTestCase;
use TEC\Events\Custom_Tables\V1\Migration\Migration_Exception;
use Tribe\Events_Pro\Tests\Traits\CT1\CT1_Fixtures;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use TEC\Tickets\Commerce\Module as Commerce;

class Ticketed_Single_Rule_Event_Migration_Strategy_Test extends WPTestCase {
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

		new Ticketed_Single_Rule_Event_Migration_Strategy( $post_id, true );
	}

	/**
	 * It should throw if trying to build on non-recurring event
	 *
	 * @test
	 */
	public function should_throw_if_trying_to_build_on_non_recurring_event(): void {
		$event = $this->given_a_non_migrated_single_event();

		$this->expectException( Migration_Exception::class );

		new Ticketed_Single_Rule_Event_Migration_Strategy( $event->ID, true );
	}

	/**
	 * It should throw if recurring event has no tickets
	 *
	 * @test
	 */
	public function should_throw_if_recurring_event_has_no_tickets(): void {
		$event = $this->given_a_non_migrated_recurring_event();

		$this->expectException( Migration_Exception::class );

		new Ticketed_Single_Rule_Event_Migration_Strategy( $event->ID, true );
	}
}
