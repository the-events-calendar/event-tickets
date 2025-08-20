<?php

namespace TEC\Tickets\Tests\Classy;

use Closure;
use TEC\Common\StellarWP\Assets\Asset;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Classy\Controller;
use TEC\Tickets\Classy\ECP_Editor_Meta;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Events__Main as TEC;

class Controller_Test extends Controller_Test_Case {
	use With_Uopz;

	protected $controller_class = Controller::class;

	private function mock_assets_add( array &$asset_calls ) {
		$anon_class = new class {
			public ?string $script_key = null;
			public ?Closure $script_callback = null;

			public function enqueue_on( $hook ) { return $this; }

			public function set_condition( $condition ) { return $this; }

			public function add_dependency( $dependency ) { return $this; }

			public function add_to_group( $group ) { return $this; }

			public function add_localize_script( $key, $callback ) {
				$this->script_key      = $key;
				$this->script_callback = $callback;

				return $this;
			}

			public function register() { return $this; }
		};

		$this->set_class_fn_return(
			Asset::class,
			'add',
			function ( $handle, $file ) use ( &$asset_calls, &$anon_class ) {
				$asset_calls[] = [ 'handle' => $handle, 'file' => $file ];

				return $anon_class;
			},
			true
		);

		return $anon_class;
	}

	/**
	 * @covers Controller::do_register
	 */
	public function test_do_register_registers_assets_when_tec_common_assets_loaded_action_did_run(): void {
		/** @var Controller $controller */
		$controller = $this->make_controller();

		// Run the action to simulate it having run.
		remove_all_actions( 'tec_common_assets_loaded' );
		do_action( 'tec_common_assets_loaded' );

		// Mock Asset::add to capture calls.
		$asset_calls = [];
		$this->mock_assets_add( $asset_calls );

		// Verify assets were registered.
		$controller->register();
		$this->assertCount( 2, $asset_calls );
		$this->assertEquals( 'tec-classy-tickets', $asset_calls[0]['handle'] );
		$this->assertEquals( 'classy.js', $asset_calls[0]['file'] );
		$this->assertEquals( 'tec-classy-tickets-styles', $asset_calls[1]['handle'] );
		$this->assertEquals( 'style-classy.css', $asset_calls[1]['file'] );
	}

	/**
	 * @covers Controller::do_register
	 */
	public function test_do_register_adds_action_when_tec_common_assets_loaded_action_has_not_run(): void {
		/** @var Controller $controller */
		$controller = $this->make_controller();

		// Ensure the action has not run.
		global $wp_actions;
		unset( $wp_actions['tec_common_assets_loaded'] );

		// Verify actions were added.
		$controller->register();
		$this->assertTrue( has_action( 'tec_common_assets_loaded' ) );
		$this->assertTrue( has_action( 'tec_events_pro_classy_registered' ) );
		$this->assertEquals( 10, has_action( 'tec_common_assets_loaded', [ $controller, 'register_assets' ] ) );
		$this->assertEquals( 10, has_action( 'tec_events_pro_classy_registered', [ $controller, 'register_ecp_editor_meta' ] ) );
	}

	/**
	 * @covers Controller::register_assets
	 */
	public function test_register_assets_registers_script_and_styles(): void {
		/** @var Controller $controller */
		$controller = $this->make_controller();

		// Filter the supported post types.
		add_filter(
			'tec_classy_post_types',
			static fn( $post_types ) => array_unique( array_merge( $post_types, [ TEC::POSTTYPE ] ) )
		);

		// Set up the global $post object to simulate a post using Classy.
		global $post;
		$post = $this->factory()->post->create_and_get(
			[
				'post_type'   => TEC::POSTTYPE,
				'post_status' => 'publish',
			]
		);

		// Mock Asset::add to capture calls.
		$asset_calls = [];
		$this->mock_assets_add( $asset_calls );

		// Verify both script and styles were registered.
		$controller->register_assets();
		$this->assertCount( 2, $asset_calls );
		$this->assertEquals( 'tec-classy-tickets', $asset_calls[0]['handle'] );
		$this->assertEquals( 'classy.js', $asset_calls[0]['file'] );
		$this->assertEquals( 'tec-classy-tickets-styles', $asset_calls[1]['handle'] );
		$this->assertEquals( 'style-classy.css', $asset_calls[1]['file'] );
	}

	/**
	 * @covers Controller::get_data
	 */
	public function test_get_data_returns_expected_structure(): void {
		/** @var Controller $controller */
		$controller = $this->make_controller();

		// Filter the supported post types.
		$post_types = [ 'tribe_events', 'page' ];
		add_filter(
			'tec_classy_post_types',
			static fn( $types ) => array_unique( array_merge( $types, $post_types ) )
		);

		// Mock wp_create_nonce.
		$this->set_fn_return( 'wp_create_nonce', static fn( $action ) => "nonce_{$action}", true );

		// Mock Asset::add to capture calls.
		$asset_calls = [];
		$assets      = $this->mock_assets_add( $asset_calls );

		// Ensure nothing is registered yet.
		$this->assertNull( $assets->script_key );
		$this->assertNull( $assets->script_callback );

		// Register the assets and then check the script key and callback.
		$controller->register_assets();
		$this->assertEquals( 'tec.tickets.classy.data', $assets->script_key );
		$this->assertIsCallable( $assets->script_callback );

		// Verify structure.
		$result = call_user_func( $assets->script_callback );
		$this->assertArrayHasKey( 'settings', $result );
		$this->assertArrayHasKey( 'nonces', $result );

		// Verify currency settings.
		$this->assertArrayHasKey( 'currency', $result['settings'] );
		$this->assertEquals( 'USD', $result['settings']['currency']['code'] );
		$this->assertEquals( '$', html_entity_decode( $result['settings']['currency']['symbol'] ) );
		$this->assertEquals( '.', $result['settings']['currency']['decimalSeparator'] );
		$this->assertEquals( ',', $result['settings']['currency']['thousandsSeparator'] );
		$this->assertEquals( 'prefix', $result['settings']['currency']['position'] );
		$this->assertEquals( 2, $result['settings']['currency']['precision'] );

		// Verify ticket post types.
		$this->assertArrayHasKey( 'ticketPostTypes', $result['settings'] );
		$this->assertEquals( $post_types, $result['settings']['ticketPostTypes'] );

		// Verify nonces.
		$this->assertArrayHasKey( 'deleteTicket', $result['nonces'] );
		$this->assertArrayHasKey( 'updateTicket', $result['nonces'] );
		$this->assertArrayHasKey( 'createTicket', $result['nonces'] );
		$this->assertEquals( 'nonce_remove_ticket_nonce', $result['nonces']['deleteTicket'] );
		$this->assertEquals( 'nonce_edit_ticket_nonce', $result['nonces']['updateTicket'] );
		$this->assertEquals( 'nonce_add_ticket_nonce', $result['nonces']['createTicket'] );
	}

	/**
	 * @covers Controller::register_ecp_editor_meta
	 */
	public function test_register_ecp_editor_meta_makes_and_registers_ecp_editor_meta(): void {
		/** @var Controller $controller */
		$controller = $this->make_controller();

		/*
		 * This mock is in place to ensure that the method is called, while the real method
		 * references a class that does not exist in the test environment.
		 *
		 * \Tribe\Events\Virtual\Compatibility\Event_Tickets\Event_Meta is part of Events Pro.
		 */
		$ecp_editor_meta = $this->createMock( ECP_Editor_Meta::class );
		$ecp_editor_meta->expects( $this->once() )->method( 'register' );
		$this->test_services[ ECP_Editor_Meta::class ] = $ecp_editor_meta;

		$controller->register_ecp_editor_meta();
	}
}
