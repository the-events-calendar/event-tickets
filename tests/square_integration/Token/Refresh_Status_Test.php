<?php

namespace TEC\Tickets\Commerce\Gateways\Square\Token;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;

class Refresh_Status_Test extends WPTestCase {
	/**
	 * @after
	 */
	public function restore_status(): void {
		tribe( Refresh_Status::class )->delete();
	}

	/**
	 * @test
	 */
	public function it_should_report_an_empty_status_for_a_fresh_connection(): void {
		$status = $this->status()->get();

		$this->assertSame( 0, $status['failures'] );
		$this->assertSame( '', $status['invalid_at'] );
		$this->assertFalse( $this->status()->is_invalid() );
		$this->assertNull( $this->status()->get_last_attempt_timestamp() );
		$this->assertNull( $this->status()->get_first_failure_timestamp() );
	}

	/**
	 * @test
	 */
	public function it_should_count_consecutive_transient_failures(): void {
		$status = $this->status();

		$this->assertSame( 1, $status->record_transient_failure() );
		$this->assertSame( 2, $status->record_transient_failure() );
		$this->assertSame( 2, $status->get_failure_count() );

		// The run started with the first one, not the latest.
		$first_failure = $status->get_first_failure_timestamp();

		// Seconds of tolerance, so a slow run between the write and the assertion cannot fail this.
		$this->assertEqualsWithDelta( time(), $first_failure, 5 );

		$status->record_transient_failure();

		$this->assertSame( $first_failure, $status->get_first_failure_timestamp() );
	}

	/**
	 * @test
	 */
	public function it_should_clear_the_failure_run_on_success(): void {
		$status = $this->status();

		$status->record_transient_failure();
		$status->record_permanent_failure( 401 );

		$this->assertTrue( $status->is_invalid() );

		$status->record_success();

		$this->assertFalse( $status->is_invalid() );
		$this->assertSame( 0, $status->get_failure_count() );
		$this->assertNull( $status->get_first_failure_timestamp() );
	}

	/**
	 * The last attempt is the only throttle a connection with no known expiration has, so a success
	 * must not clear it along with the failure bookkeeping.
	 *
	 * @test
	 */
	public function it_should_keep_the_last_attempt_across_a_success(): void {
		$status = $this->status();

		$status->record_attempt();
		$recorded = $status->get_last_attempt_timestamp();

		// Seconds of tolerance, as above.
		$this->assertEqualsWithDelta( time(), $recorded, 5 );

		$status->record_success();

		$this->assertSame( $recorded, $status->get_last_attempt_timestamp() );
	}

	/**
	 * @test
	 */
	public function it_should_read_a_corrupt_stored_status_as_empty(): void {
		$status = $this->status();

		$status->update( [ 'failures' => 3 ] );

		$this->assertSame( 3, $status->get_failure_count() );

		update_option( $this->stored_option_name(), 'not-an-array' );

		$this->assertSame( 0, $status->get_failure_count() );
	}

	/**
	 * The sandbox and live connections fail independently, so neither may read the other's state.
	 *
	 * @test
	 */
	public function it_should_scope_the_status_to_the_gateway_mode(): void {
		$merchant = tribe( Merchant::class );
		$original = $merchant->get_mode();
		$status   = $this->status();

		try {
			$merchant->set_mode( 'live' );
			$status->update( [ 'failures' => 4 ] );

			$merchant->set_mode( 'sandbox' );

			$this->assertSame( 0, $status->get_failure_count() );

			$status->delete();
			$merchant->set_mode( 'live' );

			$this->assertSame( 4, $status->get_failure_count() );
		} finally {
			$status->delete();
			$merchant->set_mode( $original );
		}
	}

	/**
	 * The only place the stored option name is spelled out, so that a test can seed a value the write
	 * API cannot produce.
	 */
	private function stored_option_name(): string {
		return 'tec_tickets_commerce_square_token_status_' . tribe( Merchant::class )->get_mode();
	}

	protected function status(): Refresh_Status {
		return tribe( Refresh_Status::class );
	}

}
