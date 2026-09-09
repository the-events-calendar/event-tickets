<?php

namespace TEC\Tickets\Commerce\Gateways\Square\Token;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;

class Refresh_Lock_Test extends WPTestCase {
	/**
	 * @after
	 */
	public function restore_merchant_data(): void {
		delete_option( $this->get_lock_key() );
		tribe( Merchant::class )->save_signup_data( tec_tickets_tests_get_fake_merchant_data() );
	}

	/**
	 * @test
	 */
	public function it_should_take_a_free_lock(): void {
		$this->assertTrue( $this->lock()->acquire() );
		$this->assertNotEmpty( get_option( $this->get_lock_key() ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_take_a_lock_another_process_holds(): void {
		add_option( $this->get_lock_key(), time() . ':somebody-else', '', 'no' );

		$this->assertFalse( $this->lock()->acquire() );
	}

	/**
	 * A process that died mid-refresh must not hold refreshes off forever.
	 *
	 * @test
	 */
	public function it_should_take_over_an_abandoned_lock(): void {
		// Older than Refresh_Lock's 60 second timeout, by enough that a slow run cannot close the gap.
		add_option( $this->get_lock_key(), ( time() - 300 ) . ':abandoned', '', 'no' );

		$this->assertTrue( $this->lock()->acquire() );
	}

	/**
	 * @test
	 */
	public function it_should_release_a_lock_it_holds(): void {
		$lock = $this->lock();

		$this->assertTrue( $lock->acquire() );

		$lock->release();

		$this->assertFalse( get_option( $this->get_lock_key() ) );
	}

	/**
	 * A process whose lock was taken over must not delete the row the new holder is working under, or a
	 * third process walks straight in while the second is still mid-refresh.
	 *
	 * @test
	 */
	public function it_should_not_release_a_lock_it_no_longer_holds(): void {
		$lock = $this->lock();

		$this->assertTrue( $lock->acquire() );

		// Whoever took over stamped its own value over this process's.
		$takeover = time() . ':somebody-else';
		update_option( $this->get_lock_key(), $takeover );

		$lock->release();

		$this->assertSame( $takeover, get_option( $this->get_lock_key() ) );
	}

	/**
	 * Under load every concurrent request finds the same expired token. If losing the lock race were an
	 * immediate failure, one shopper would get the refresh and the rest a failed checkout.
	 *
	 * @test
	 */
	public function it_should_wait_for_the_lock_holder_to_commit_new_credentials(): void {
		$merchant   = tribe( Merchant::class );
		$option_key = 'tec_tickets_commerce_square_signup_data_' . $merchant->get_mode();

		$committed                 = tec_tickets_tests_get_fake_merchant_data();
		$committed['access_token'] = 'renewed-by-the-winner';

		/*
		 * Stands in for the process holding the lock committing its refreshed credentials. Written
		 * straight to the row so this process's object cache stays stale, which is the state a waiter
		 * is actually in; going through update_option() would prime the cache here and let the wait
		 * succeed without ever re-reading anything.
		 */
		DB::query(
			DB::prepare(
				'UPDATE %i SET option_value = %s WHERE option_name = %s',
				DB::prefix( 'options' ),
				maybe_serialize( $committed ),
				$option_key
			)
		);

		$this->assertTrue( $this->lock()->wait_for_holder( tec_tickets_tests_get_fake_merchant_data()['access_token'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_give_up_waiting_on_a_lock_holder_that_never_finishes(): void {
		$merchant = tribe( Merchant::class );

		$this->assertFalse( $this->lock()->wait_for_holder( $merchant->get_access_token() ) );
	}

	protected function get_lock_key(): string {
		return 'tec_tickets_commerce_square_token_refresh_lock_' . tribe( Merchant::class )->get_mode();
	}

	protected function lock(): Refresh_Lock {
		return tribe( Refresh_Lock::class );
	}

}
