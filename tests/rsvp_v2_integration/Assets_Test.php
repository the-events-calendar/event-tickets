<?php

namespace TECTicketsRSVPV2Tests;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\RSVP\V2\Assets;

/**
 * Class Assets_Test
 *
 * Tests for the RSVP V2 Assets class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Assets_Test extends WPTestCase {

	/**
	 * @test
	 */
	public function it_should_be_instantiable_via_container() {
		$assets = tribe( Assets::class );

		$this->assertInstanceOf( Assets::class, $assets );
	}

	/**
	 * @test
	 */
	public function it_should_have_register_method() {
		$assets = tribe( Assets::class );

		$this->assertTrue( method_exists( $assets, 'register' ) );
	}

	/**
	 * @test
	 */
	public function it_should_have_should_enqueue_admin_assets_method() {
		$assets = tribe( Assets::class );

		$this->assertTrue( method_exists( $assets, 'should_enqueue_admin_assets' ) );
	}

	/**
	 * @test
	 */
	public function it_should_have_get_admin_localize_data_method() {
		$assets = tribe( Assets::class );

		$this->assertTrue( method_exists( $assets, 'get_admin_localize_data' ) );
	}

	/**
	 * @test
	 */
	public function it_should_have_get_frontend_localize_data_method() {
		$assets = tribe( Assets::class );

		$this->assertTrue( method_exists( $assets, 'get_frontend_localize_data' ) );
	}

	/**
	 * @test
	 */
	public function get_admin_localize_data_should_return_expected_keys() {
		$assets = tribe( Assets::class );
		$data   = $assets->get_admin_localize_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'ajaxUrl', $data );
		$this->assertArrayHasKey( 'nonces', $data );
		$this->assertArrayHasKey( 'i18n', $data );
	}

	/**
	 * @test
	 */
	public function get_admin_localize_data_should_contain_nonce_save() {
		$assets = tribe( Assets::class );
		$data   = $assets->get_admin_localize_data();

		$this->assertArrayHasKey( 'save', $data['nonces'] );
		$this->assertNotEmpty( $data['nonces']['save'] );
	}

	/**
	 * @test
	 */
	public function get_admin_localize_data_should_contain_i18n_strings() {
		$assets   = tribe( Assets::class );
		$data     = $assets->get_admin_localize_data();
		$expected = [
			'confirmDelete',
			'saving',
			'saved',
			'error',
			'unlimited',
			'capacityLabel',
			'nameRequired',
		];

		foreach ( $expected as $key ) {
			$this->assertArrayHasKey( $key, $data['i18n'], "Missing i18n key: {$key}" );
		}
	}

	/**
	 * @test
	 */
	public function get_frontend_localize_data_should_return_expected_keys() {
		$assets = tribe( Assets::class );
		$data   = $assets->get_frontend_localize_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'ajaxUrl', $data );
		$this->assertArrayHasKey( 'restUrl', $data );
		$this->assertArrayHasKey( 'nonces', $data );
		$this->assertArrayHasKey( 'i18n', $data );
	}

	/**
	 * @test
	 */
	public function get_frontend_localize_data_should_contain_rest_url() {
		$assets = tribe( Assets::class );
		$data   = $assets->get_frontend_localize_data();

		$this->assertStringContainsString( 'tribe/tickets/v1/', $data['restUrl'] );
	}

	/**
	 * @test
	 */
	public function get_frontend_localize_data_should_contain_nonce() {
		$assets = tribe( Assets::class );
		$data   = $assets->get_frontend_localize_data();

		$this->assertArrayHasKey( 'rsvpHandle', $data['nonces'] );
		$this->assertNotEmpty( $data['nonces']['rsvpHandle'] );
	}

	/**
	 * @test
	 */
	public function get_frontend_localize_data_should_contain_i18n_strings() {
		$assets   = tribe( Assets::class );
		$data     = $assets->get_frontend_localize_data();
		$expected = [
			'going',
			'notGoing',
			'submit',
			'cancel',
			'loading',
			'error',
			'success',
			'full',
			'closed',
		];

		foreach ( $expected as $key ) {
			$this->assertArrayHasKey( $key, $data['i18n'], "Missing i18n key: {$key}" );
		}
	}

	/**
	 * @test
	 */
	public function should_enqueue_admin_assets_returns_false_without_screen() {
		$assets = tribe( Assets::class );

		// Without a screen set, should return false.
		$this->assertFalse( $assets->should_enqueue_admin_assets() );
	}

	/**
	 * @test
	 */
	public function it_should_be_retrievable_from_container() {
		$assets = tribe( Assets::class );

		$this->assertInstanceOf( Assets::class, $assets );
	}
}
