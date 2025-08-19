<?php

namespace TEC\Tickets\Tests\Classy;

use Closure;
use TEC\Common\Classy\Controller as Common_Controller;
use TEC\Common\StellarWP\Assets\Asset;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Classy\Controller;
use TEC\Tickets\Classy\ECP_Editor_Meta;
use TEC\Tickets\Classy\REST\Controller as REST_Controller;
use TEC\Tickets\Commerce\Utils\Currency;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Main as ET;

class Controller_Test extends Controller_Test_Case {
	use With_Uopz;

	protected $controller_class = Controller::class;

	private function mock_assets_add( array &$asset_calls ): void {
		$anon_class = new class {
			public function add_to_group_path( $path ) { return $this; }

			public function enqueue_on( $hook ) { return $this; }

			public function set_condition( $condition ) { return $this; }

			public function add_dependency( $dependency ) { return $this; }

			public function add_to_group( $group ) { return $this; }

			public function add_localize_script( $key, $callback ) { return $this; }

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
	}

	/**
	 * @covers Controller::do_register
	 */
	public function test_do_register_registers_assets_when_tec_common_assets_loaded_action_did_run(): void {
		$controller = $this->make_controller();

		// Mock the action to have already run
		$this->set_fn_return(
			'did_action',
			function ( $action ) {
				return $action === 'tec_common_assets_loaded';
			},
			true
		);

		// Mock Asset::add to capture calls
		$asset_calls = [];
		$this->mock_assets_add( $asset_calls );

		$controller->register();

		// Verify assets were registered
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
		$controller = $this->make_controller();

		// Mock the action to have not run, and are not running.
		$this->set_fn_return( 'did_action', static fn( $action ) => false, true );
		$this->set_fn_return( 'doing_action', static fn( $action ) => false, true );

		// Mock add_action to capture calls
		$added_actions = [];
		$this->set_fn_return(
			'add_action',
			function ( $hook, $callback, $priority = 10 ) use ( &$added_actions ) {
				$added_actions[] = [ 'hook' => $hook, 'callback' => $callback, 'priority' => $priority ];
			},
			true
		);

		$controller->register();

		// Verify actions were added
		$this->assertCount( 2, $added_actions );
		$this->assertEquals( 'tec_common_assets_loaded', $added_actions[0]['hook'] );
		$this->assertEquals( [ $controller, 'register_assets' ], $added_actions[0]['callback'] );
		$this->assertEquals( 'tec_events_pro_classy_registered', $added_actions[1]['hook'] );
		$this->assertEquals( [ $controller, 'register_ecp_editor_meta' ], $added_actions[1]['callback'] );
	}

	/**
	 * @covers Controller::unregister
	 */
	public function test_unregisters_rest_controller(): void {
		$controller = $this->make_controller();

		// Mock REST controller unregister
		$rest_controller = $this->createMock( REST_Controller::class );
		$rest_controller->expects( $this->once() )->method( 'unregister' );

		$original_services                             = $this->test_services;
		$this->test_services[ REST_Controller::class ] = $rest_controller;

		$controller->unregister();
	}

	/**
	 * @covers Controller::register_assets
	 */
	public function test_register_assets_registers_script_and_styles(): void {
		/** @var Controller $controller */
		$controller = $this->make_controller();

		// Mock Common_Controller
		$common_controller = $this->createMock( Common_Controller::class );
		$common_controller->method( 'post_uses_classy' )->willReturn( true );
		$this->test_services[ Common_Controller::class ] = $common_controller;

		// Mock Asset::add to capture calls
		$asset_calls = [];
		$this->mock_assets_add( $asset_calls );
		$controller->register_assets();

		// Verify both script and styles were registered
		$this->assertCount( 2, $asset_calls );
		$this->assertEquals( 'tec-classy-tickets', $asset_calls[0]['handle'] );
		$this->assertEquals( 'classy.js', $asset_calls[0]['file'] );
		$this->assertEquals( 'tec-classy-tickets-styles', $asset_calls[1]['handle'] );
		$this->assertEquals( 'style-classy.css', $asset_calls[1]['file'] );
	}

	/**
	 * @covers Controller::get_et_class
	 */
	public function test_get_et_class_returns_et_class_name(): void {
		/** @var Controller $controller */
		$controller = $this->make_controller();

		// Use reflection to access private method
		$method = Closure::bind(
			function () {
				return $this->get_et_class();
			},
			$controller,
			$controller
		);

		$this->assertEquals( ET::class, $method() );
	}

	/**
	 * @covers Controller::get_data
	 */
	public function test_get_data_returns_expected_structure(): void {
		/** @var Controller $controller */
		$controller = $this->make_controller();

		// Mock the Currency class.
		$this->set_class_fn_return( Currency::class, 'get_currency_code', 'USD' );
		$this->set_class_fn_return( Currency::class, 'get_currency_symbol', '$' );
		$this->set_class_fn_return( Currency::class, 'get_currency_separator_decimal', '.' );
		$this->set_class_fn_return( Currency::class, 'get_currency_separator_thousands', ',' );
		$this->set_class_fn_return( Currency::class, 'get_currency_symbol_position', 'prefix' );
		$this->set_class_fn_return( Currency::class, 'get_currency_precision', 2 );

		// Mock ET main class
		$post_types = [ 'tribe_events', 'page' ];
		$et_main    = $this->createMock( ET::class );
		$et_main->method( 'post_types' )->willReturn( $post_types );
		$this->test_services[ ET::class ] = $et_main;

		// Mock wp_create_nonce
		$this->set_fn_return( 'wp_create_nonce', static fn( $action ) => "nonce_{$action}", true );

		$method = Closure::bind(
			function () {
				return $this->get_data();
			},
			$controller,
			$controller
		);

		$result = $method();

		// Verify structure
		$this->assertArrayHasKey( 'settings', $result );
		$this->assertArrayHasKey( 'nonces', $result );

		// Verify currency settings
		$this->assertArrayHasKey( 'currency', $result['settings'] );
		$this->assertEquals( 'USD', $result['settings']['currency']['code'] );
		$this->assertEquals( '$', $result['settings']['currency']['symbol'] );
		$this->assertEquals( '.', $result['settings']['currency']['decimalSeparator'] );
		$this->assertEquals( ',', $result['settings']['currency']['thousandsSeparator'] );
		$this->assertEquals( 'prefix', $result['settings']['currency']['position'] );
		$this->assertEquals( 2, $result['settings']['currency']['precision'] );

		// Verify ticket post types
		$this->assertArrayHasKey( 'ticketPostTypes', $result['settings'] );
		$this->assertEquals( $post_types, $result['settings']['ticketPostTypes'] );

		// Verify nonces
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

		// Mock ECP_Editor_Meta
		$ecp_editor_meta = $this->createMock( ECP_Editor_Meta::class );
		$ecp_editor_meta->expects( $this->once() )->method( 'register' );
		$this->test_services[ ECP_Editor_Meta::class ] = $ecp_editor_meta;

		$controller->register_ecp_editor_meta();
	}
}
