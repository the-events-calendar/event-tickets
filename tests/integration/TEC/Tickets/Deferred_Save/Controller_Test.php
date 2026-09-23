<?php

namespace TEC\Tickets\Deferred_Save;

use TEC\Common\Tests\Provider\Controller_Test_Case;

/**
 * Without Events Calendar Pro loaded no post is a recurring event, so the switch answers false for everything.
 */
class Controller_Test extends Controller_Test_Case {
	protected string $controller_class = Controller::class;

	/**
	 * @test
	 */
	public function it_does_not_use_deferred_save_for_an_event_without_ecp(): void {
		$this->assertFalse( function_exists( 'tribe_is_recurring_event' ), 'This suite must not load ECP.' );
		$controller = $this->make_controller();
		$controller->register();
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Event',
				'start_date' => '2020-01-01 10:00:00',
				'end_date'   => '2020-01-01 12:00:00',
			]
		)->create()->ID;

		$this->assertFalse( $controller->uses_deferred_save( $event_id ) );
	}

	/**
	 * @test
	 */
	public function it_does_not_use_deferred_save_for_a_page(): void {
		$controller = $this->make_controller();
		$controller->register();
		$page_id = static::factory()->post->create( [ 'post_type' => 'page' ] );

		$this->assertFalse( $controller->uses_deferred_save( $page_id ) );
	}
}
