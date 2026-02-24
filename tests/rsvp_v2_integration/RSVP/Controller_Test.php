<?php

namespace TEC\Tickets\RSVP;

use lucatume\WPBrowser\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Settings;
use Tribe\Tests\Traits\With_Uopz;

class Controller_Test extends WPTestCase {
	use With_Uopz;

	private function reset_settings_cache(): void {
		tribe_set_var( \Tribe__Settings_Manager::OPTION_CACHE_VAR_NAME, null );
	}

	public function test_activates_tickets_commerce(): void {
		$this->assertTrue( tec_tickets_commerce_is_enabled() );
	}

	public function test_allows_overriding_tickets_commerce_activation_with_filter(): void {
		// The Controller will filter Tickets Commerce active at priority 10, filter later to deactivate it.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_false', 20 );
		// This will be already set by the suite setup, but we're explicitly doing it here for clarity.
		add_filter( 'tec_tickets_rsvp_version', static fn() => Controller::VERSION_2 );

		Controller::maybe_activate_tickets_commerce();

		$this->assertFalse( apply_filters( 'tec_tickets_commerce_is_enabled', true ) );
	}

	public function test_maybe_activate_tickets_commerce_does_not_add_filter_for_v1(): void {
		remove_all_filters( 'tec_tickets_commerce_is_enabled' );
		add_filter( 'tec_tickets_rsvp_version', static fn() => Controller::VERSION_1, 20 );

		Controller::maybe_activate_tickets_commerce();

		// The filter should not have been added, so the value should pass through unchanged.
		$this->assertFalse( apply_filters( 'tec_tickets_commerce_is_enabled', false ) );
	}

	public function test_maybe_activate_tickets_commerce_creates_checkout_page_when_option_not_set(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->reset_settings_cache();
		tribe_remove_option( Settings::$option_checkout_page );

		remove_all_filters( 'tec_tickets_commerce_is_enabled' );

		Controller::maybe_activate_tickets_commerce();

		// Trigger the filter, which calls enable_tickets_commerce().
		apply_filters( 'tec_tickets_commerce_is_enabled', false );

		$this->reset_settings_cache();
		$checkout_page_id = tribe_get_option( Settings::$option_checkout_page );

		$this->assertNotEmpty( $checkout_page_id, 'Checkout page should have been created.' );
		$this->assertInstanceOf( \WP_Post::class, get_post( $checkout_page_id ) );
	}

	public function test_maybe_activate_tickets_commerce_creates_success_page_when_option_not_set(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->reset_settings_cache();
		tribe_remove_option( Settings::$option_success_page );

		remove_all_filters( 'tec_tickets_commerce_is_enabled' );

		Controller::maybe_activate_tickets_commerce();

		// Trigger the filter, which calls enable_tickets_commerce().
		apply_filters( 'tec_tickets_commerce_is_enabled', false );

		$this->reset_settings_cache();
		$success_page_id = tribe_get_option( Settings::$option_success_page );

		$this->assertNotEmpty( $success_page_id, 'Success page should have been created.' );
		$this->assertInstanceOf( \WP_Post::class, get_post( $success_page_id ) );
	}

	public function test_maybe_activate_tickets_commerce_skips_checkout_page_when_option_set(): void {
		$existing_page_id = static::factory()->post->create( [ 'post_type' => 'page' ] );

		$this->reset_settings_cache();
		tribe_update_option( Settings::$option_checkout_page, $existing_page_id );

		remove_all_filters( 'tec_tickets_commerce_is_enabled' );

		Controller::maybe_activate_tickets_commerce();

		// Trigger the filter, which calls enable_tickets_commerce().
		apply_filters( 'tec_tickets_commerce_is_enabled', false );

		$this->reset_settings_cache();
		$checkout_page_id = tribe_get_option( Settings::$option_checkout_page );

		$this->assertEquals( $existing_page_id, $checkout_page_id, 'Checkout page option should not have changed.' );
	}

	public function test_maybe_activate_tickets_commerce_skips_success_page_when_option_set(): void {
		$existing_page_id = static::factory()->post->create( [ 'post_type' => 'page' ] );

		$this->reset_settings_cache();
		tribe_update_option( Settings::$option_success_page, $existing_page_id );

		remove_all_filters( 'tec_tickets_commerce_is_enabled' );

		Controller::maybe_activate_tickets_commerce();

		// Trigger the filter, which calls enable_tickets_commerce().
		apply_filters( 'tec_tickets_commerce_is_enabled', false );

		$this->reset_settings_cache();
		$success_page_id = tribe_get_option( Settings::$option_success_page );

		$this->assertEquals( $existing_page_id, $success_page_id, 'Success page option should not have changed.' );
	}

	public function test_enable_tickets_commerce_creates_pages_immediately_when_init_did_run(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->reset_settings_cache();
		tribe_remove_option( Settings::$option_checkout_page );
		tribe_remove_option( Settings::$option_success_page );

		// Simulate that `init` has already fired.
		$this->set_fn_return(
			'did_action',
			static fn( string $action ) => $action === 'init' ? 1 : did_action( $action ),
			true
		);
		$this->set_fn_return( 'doing_action', static fn( string $action ) => ! ( $action === 'init' ) && doing_action(
				$action
			), true );

		Controller::enable_tickets_commerce();

		$this->reset_settings_cache();
		$checkout_page_id = tribe_get_option( Settings::$option_checkout_page );
		$success_page_id  = tribe_get_option( Settings::$option_success_page );

		$this->assertNotEmpty( $checkout_page_id, 'Checkout page should have been created immediately.' );
		$this->assertInstanceOf( \WP_Post::class, get_post( $checkout_page_id ) );
		$this->assertNotEmpty( $success_page_id, 'Success page should have been created immediately.' );
		$this->assertInstanceOf( \WP_Post::class, get_post( $success_page_id ) );
	}

	public function test_enable_tickets_commerce_creates_pages_immediately_when_doing_init(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->reset_settings_cache();
		tribe_remove_option( Settings::$option_checkout_page );
		tribe_remove_option( Settings::$option_success_page );

		// Simulate that `init` is currently running.
		$this->set_fn_return(
			'did_action',
			static fn( string $action ) => $action === 'init' ? 0 : did_action( $action ),
			true
		);
		$this->set_fn_return( 'doing_action', static fn( string $action ) => $action === 'init' || doing_action(
				$action
			), true );

		Controller::enable_tickets_commerce();

		$this->reset_settings_cache();
		$checkout_page_id = tribe_get_option( Settings::$option_checkout_page );
		$success_page_id  = tribe_get_option( Settings::$option_success_page );

		$this->assertNotEmpty( $checkout_page_id, 'Checkout page should have been created immediately during init.' );
		$this->assertInstanceOf( \WP_Post::class, get_post( $checkout_page_id ) );
		$this->assertNotEmpty( $success_page_id, 'Success page should have been created immediately during init.' );
		$this->assertInstanceOf( \WP_Post::class, get_post( $success_page_id ) );
	}

	public function test_enable_tickets_commerce_defers_page_creation_before_init(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->reset_settings_cache();
		tribe_remove_option( Settings::$option_checkout_page );
		tribe_remove_option( Settings::$option_success_page );

		// Simulate that `init` has not fired yet.
		$this->set_fn_return( 'did_action', static fn( string $action ) => $action === 'init' ? 0 : did_action( $action ), true );
		$this->set_fn_return( 'doing_action', static fn( string $action ) => ! ( $action === 'init' ) && doing_action(
				$action
			), true );

		// Remove all callbacks, including the ones on `init`.
		global $wp_filter;
		$wp_filter = [];

		Controller::enable_tickets_commerce();

		$this->assertEquals( 10, has_action( 'init', [ Controller::class, 'maybe_create_tickets_commerce_pages' ] ) );

		// Pages should not exist yet.
		$this->reset_settings_cache();
		$this->assertEmpty( tribe_get_option( Settings::$option_checkout_page ), 'Checkout page should not exist before init.' );
		$this->assertEmpty( tribe_get_option( Settings::$option_success_page ), 'Success page should not exist before init.' );

		// Do the `init` action safely: the Controller will be the only callback.
		do_action( 'init' );

		$this->reset_settings_cache();
		$checkout_page_id = tribe_get_option( Settings::$option_checkout_page );
		$success_page_id  = tribe_get_option( Settings::$option_success_page );

		$this->assertNotEmpty( $checkout_page_id, 'Checkout page should have been created after init.' );
		$this->assertInstanceOf( \WP_Post::class, get_post( $checkout_page_id ) );
		$this->assertNotEmpty( $success_page_id, 'Success page should have been created after init.' );
		$this->assertInstanceOf( \WP_Post::class, get_post( $success_page_id ) );
	}
}
