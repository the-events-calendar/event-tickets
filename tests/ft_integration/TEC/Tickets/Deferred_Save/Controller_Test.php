<?php

namespace TEC\Tickets\Deferred_Save;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;

class Controller_Test extends Controller_Test_Case {
	protected string $controller_class = Controller::class;

	private function create_recurring_event(): int {
		return tribe_events()->set_args(
			[
				'title'      => 'Recurring Event',
				'start_date' => '2020-01-01 10:00:00',
				'end_date'   => '2020-01-01 12:00:00',
				'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
			]
		)->create()->ID;
	}

	private function create_single_event(): int {
		return tribe_events()->set_args(
			[
				'title'      => 'Single Event',
				'start_date' => '2020-01-01 10:00:00',
				'end_date'   => '2020-01-01 12:00:00',
			]
		)->create()->ID;
	}

	/**
	 * @test
	 */
	public function it_uses_deferred_save_for_a_recurring_event(): void {
		$controller = $this->make_controller();
		$controller->register();

		$this->assertTrue( $controller->uses_deferred_save( $this->create_recurring_event() ) );
	}

	/**
	 * @test
	 */
	public function it_uses_deferred_save_for_an_occurrence_id_of_a_recurring_event(): void {
		$controller = $this->make_controller();
		$controller->register();
		$event_id = $this->create_recurring_event();

		$occurrence = Occurrence::where( 'post_id', '=', $event_id )->first();
		$this->assertNotNull( $occurrence );
		$this->assertNotSame( $event_id, $occurrence->provisional_id );

		$this->assertTrue( $controller->uses_deferred_save( $occurrence->provisional_id ) );
	}

	/**
	 * @test
	 */
	public function it_does_not_use_deferred_save_for_a_single_event(): void {
		$controller = $this->make_controller();
		$controller->register();

		$this->assertFalse( $controller->uses_deferred_save( $this->create_single_event() ) );
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

	/**
	 * @test
	 */
	public function it_does_not_use_deferred_save_for_an_unknown_post_id(): void {
		$controller = $this->make_controller();
		$controller->register();

		$this->assertFalse( $controller->uses_deferred_save( 999999999 ) );
	}

	/**
	 * @test
	 */
	public function the_filter_can_widen_the_set_to_a_single_event(): void {
		$controller = $this->make_controller();
		$controller->register();
		$event_id = $this->create_single_event();

		add_filter(
			'tec_tickets_deferred_save_enabled',
			static function ( bool $enabled, int $post_id ) use ( $event_id ): bool {
				return $post_id === $event_id ? true : $enabled;
			},
			10,
			2
		);

		$this->assertTrue( $controller->uses_deferred_save( $event_id ) );
	}

	/**
	 * @test
	 */
	public function the_filter_can_switch_a_recurring_event_off(): void {
		$controller = $this->make_controller();
		$controller->register();

		add_filter( 'tec_tickets_deferred_save_enabled', '__return_false' );

		$this->assertFalse( $controller->uses_deferred_save( $this->create_recurring_event() ) );
	}
}
