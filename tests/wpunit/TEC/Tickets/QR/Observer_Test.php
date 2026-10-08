<?php

namespace TEC\Tickets\QR;

use Codeception\TestCase\WPTestCase;

class Observer_Test extends WPTestCase {

	/**
	 * A published post standing in for an attendee: the notice only needs a valid
	 * (non-trashed) post status to reach the branch that used to write the QR flag.
	 *
	 * @var int
	 */
	protected int $attendee_id = 0;

	public function setUp(): void {
		parent::setUp();

		add_filter( 'tec_tickets_qr_code_enabled', '__return_true' );

		$this->attendee_id = (int) static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$_GET['qr_checked_in'] = $this->attendee_id;
	}

	public function tearDown(): void {
		unset( $_GET['qr_checked_in'], $_GET['qr_already_checked_in'] );
		remove_filter( 'tec_tickets_qr_code_enabled', '__return_true' );

		parent::tearDown();
	}

	protected function run_notice(): void {
		ob_start();
		tribe( Observer::class )->legacy_handler_admin_notice();
		ob_end_clean();
	}

	/**
	 * @test
	 */
	public function it_should_not_write_the_qr_status_for_an_unprivileged_user(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->run_notice();

		$this->assertEmpty(
			get_post_meta( $this->attendee_id, '_tribe_qr_status', true ),
			'A Subscriber hitting the notice must not flip the QR check-in status.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_not_write_the_qr_status_even_for_an_administrator(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->run_notice();

		$this->assertEmpty(
			get_post_meta( $this->attendee_id, '_tribe_qr_status', true ),
			'The notice is display-only; the status write belongs to the authorized check-in path.'
		);
	}
}
