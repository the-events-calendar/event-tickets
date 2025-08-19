<?php

namespace TEC\Tickets\Tests\Classy\REST;

use Closure;
use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Classy\REST\Controller;
use Tribe\Tests\Traits\With_Uopz;
use WP_REST_Server;

class Controller_Test extends Controller_Test_Case {
	use With_Uopz;

	protected $controller_class = Controller::class;

	/**
	 * @covers Controller::register_routes
	 */
	public function test_register_routes_registers_all_ticket_endpoints(): void {
		/** @var Controller $controller */
		$controller = $this->make_controller();

		// Mock register_rest_route to capture calls
		$registered_routes = [];
		$this->set_fn_return(
			'register_rest_route',
			function ( $namespace, $route, $args ) use ( &$registered_routes ) {
				$registered_routes[] = [
					'namespace' => $namespace,
					'route'     => $route,
					'methods'   => $args['methods'] ?? null,
				];
			},
			true
		);

		$controller->register_routes();

		// Verify all expected routes are registered
		$this->assertCount( 4, $registered_routes );

		// Check GET /tickets route
		$get_route = array_filter( $registered_routes, fn( $route ) =>
			$route['route'] === '/tickets' && $route['methods'] === WP_REST_Server::READABLE
		);
		$this->assertCount( 1, $get_route );

		// Check POST /tickets route
		$post_route = array_filter( $registered_routes, fn( $route ) =>
			$route['route'] === '/tickets' && $route['methods'] === WP_REST_Server::CREATABLE
		);
		$this->assertCount( 1, $post_route );

		// Check PUT /tickets/{id} route
		$put_route = array_filter( $registered_routes, fn( $route ) =>
			$route['route'] === '/tickets/(?P<id>[\d]+)' && $route['methods'] === WP_REST_Server::EDITABLE
		);
		$this->assertCount( 1, $put_route );

		// Check DELETE /tickets/{id} route
		$delete_route = array_filter( $registered_routes, fn( $route ) =>
			$route['route'] === '/tickets/(?P<id>[\d]+)' && $route['methods'] === WP_REST_Server::DELETABLE
		);
		$this->assertCount( 1, $delete_route );
	}

	/**
	 * @covers Controller::get_tickets_args
	 */
	public function test_get_tickets_args_returns_expected_structure(): void {
		/** @var Controller $controller */
		$controller = $this->make_controller();

		// Use reflection to access protected method
		$method = Closure::bind(
			function () {
				return $this->get_tickets_args();
			},
			$controller,
			$controller
		);

		$args = $method();

		// Verify structure
		$this->assertArrayHasKey( 'include_post', $args );
		$this->assertArrayHasKey( 'per_page', $args );
		$this->assertArrayHasKey( 'page', $args );

		// Verify include_post argument
		$this->assertArrayHasKey( 'description', $args['include_post'] );
		$this->assertArrayHasKey( 'required', $args['include_post'] );
		$this->assertArrayHasKey( 'validate_callback', $args['include_post'] );
		$this->assertArrayHasKey( 'sanitize_callback', $args['include_post'] );
		$this->assertFalse( $args['include_post']['required'] );

		// Verify per_page argument
		$this->assertArrayHasKey( 'description', $args['per_page'] );
		$this->assertArrayHasKey( 'type', $args['per_page'] );
		$this->assertArrayHasKey( 'default', $args['per_page'] );
		$this->assertArrayHasKey( 'minimum', $args['per_page'] );
		$this->assertArrayHasKey( 'maximum', $args['per_page'] );
		$this->assertArrayHasKey( 'sanitize_callback', $args['per_page'] );
		$this->assertEquals( 'integer', $args['per_page']['type'] );
		$this->assertEquals( 1, $args['per_page']['minimum'] );
		$this->assertEquals( 100, $args['per_page']['maximum'] );

		// Verify page argument
		$this->assertArrayHasKey( 'description', $args['page'] );
		$this->assertArrayHasKey( 'type', $args['page'] );
		$this->assertArrayHasKey( 'default', $args['page'] );
		$this->assertArrayHasKey( 'minimum', $args['page'] );
		$this->assertArrayHasKey( 'sanitize_callback', $args['page'] );
		$this->assertEquals( 'integer', $args['page']['type'] );
		$this->assertEquals( 1, $args['page']['default'] );
		$this->assertEquals( 1, $args['page']['minimum'] );
	}

	/**
	 * @covers Controller::get_ticket_args
	 */
	public function test_get_ticket_args_returns_expected_structure(): void {
		/** @var Controller $controller */
		$controller = $this->make_controller();

		// Use reflection to access protected method
		$method = Closure::bind(
			function () {
				return $this->get_ticket_args();
			},
			$controller,
			$controller
		);

		$args = $method();

		// Verify basic fields
		$expected_fields = [
			'name', 'description', 'price', 'show_description', 'start_date',
			'start_time', 'end_date', 'end_time', 'sku', 'iac', 'ticket'
		];

		foreach ( $expected_fields as $field ) {
			$this->assertArrayHasKey( $field, $args );
		}

		// Verify string fields
		$string_fields = [ 'name', 'description', 'price', 'show_description', 'sku', 'iac' ];
		foreach ( $string_fields as $field ) {
			$this->assertEquals( 'string', $args[ $field ]['type'] );
		}

		// Verify date-time fields
		$datetime_fields = [ 'start_date', 'start_time', 'end_date', 'end_time' ];
		foreach ( $datetime_fields as $field ) {
			$this->assertEquals( 'string', $args[ $field ]['type'] );
			$this->assertEquals( 'date-time', $args[ $field ]['format'] );
		}

		// Verify ticket object structure
		$this->assertEquals( 'object', $args['ticket']['type'] );
		$this->assertArrayHasKey( 'properties', $args['ticket'] );

		$ticket_properties = $args['ticket']['properties'];
		$this->assertArrayHasKey( 'mode', $ticket_properties );
		$this->assertArrayHasKey( 'capacity', $ticket_properties );
		$this->assertArrayHasKey( 'event_capacity', $ticket_properties );
		$this->assertArrayHasKey( 'sale_price', $ticket_properties );
		$this->assertArrayHasKey( 'seating', $ticket_properties );

		// Verify sale_price object structure
		$this->assertEquals( 'object', $ticket_properties['sale_price']['type'] );
		$this->assertArrayHasKey( 'properties', $ticket_properties['sale_price'] );

		$sale_price_properties = $ticket_properties['sale_price']['properties'];
		$this->assertArrayHasKey( 'checked', $sale_price_properties );
		$this->assertArrayHasKey( 'price', $sale_price_properties );
		$this->assertArrayHasKey( 'start_date', $sale_price_properties );
		$this->assertArrayHasKey( 'end_date', $sale_price_properties );

		// Verify seating object structure
		$this->assertEquals( 'object', $ticket_properties['seating']['type'] );
		$this->assertArrayHasKey( 'properties', $ticket_properties['seating'] );

		$seating_properties = $ticket_properties['seating']['properties'];
		$this->assertArrayHasKey( 'enabled', $seating_properties );
		$this->assertArrayHasKey( 'seatType', $seating_properties );
		$this->assertArrayHasKey( 'layoutId', $seating_properties );
	}

	public static function include_post_validation_data_provider(): Generator {
		yield 'valid single post ID' => [
			function (): array {
				$post_id = static::factory()->post->create();
				return [
					'value'    => $post_id,
					'expected' => true,
				];
			},
		];

		yield 'valid multiple post IDs' => [
			function (): array {
				$post_ids = [
					static::factory()->post->create(),
					static::factory()->post->create(),
				];
				return [
					'value'    => implode( ',', $post_ids ),
					'expected' => true,
				];
			},
		];

		yield 'valid array of post IDs' => [
			function (): array {
				$post_ids = [
					static::factory()->post->create(),
					static::factory()->post->create(),
				];
				return [
					'value'    => $post_ids,
					'expected' => true,
				];
			},
		];

		yield 'invalid non-existent post ID' => [
			function (): array {
				return [
					'value'    => 99999,
					'expected' => false,
				];
			},
		];

		yield 'invalid mixed valid and invalid post IDs' => [
			function (): array {
				$valid_post_id = static::factory()->post->create();
				return [
					'value'    => [ $valid_post_id, 99999 ],
					'expected' => false,
				];
			},
		];

		yield 'invalid non-numeric value' => [
			function (): array {
				return [
					'value'    => 'not-a-number',
					'expected' => false,
				];
			},
		];

		yield 'empty value' => [
			function (): array {
				return [
					'value'    => '',
					'expected' => false,
				];
			},
		];
	}

	/**
	 * @dataProvider include_post_validation_data_provider
	 * @covers Controller::get_tickets_args
	 */
	public function test_include_post_validation( Closure $fixture ): void {
		/** @var Controller $controller */
		$controller = $this->make_controller();

		// Use reflection to access protected method
		$method = Closure::bind(
			function () {
				return $this->get_tickets_args();
			},
			$controller,
			$controller
		);

		$args = $method();
		$validate_callback = $args['include_post']['validate_callback'];

		[ 'value' => $value, 'expected' => $expected ] = $fixture();

		$result = $validate_callback( $value );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @covers Controller::get_tickets_args
	 */
	public function test_include_post_sanitization(): void {
		/** @var Controller $controller */
		$controller = $this->make_controller();

		// Use reflection to access protected method
		$method = Closure::bind(
			function () {
				return $this->get_tickets_args();
			},
			$controller,
			$controller
		);

		$args = $method();
		$sanitize_callback = $args['include_post']['sanitize_callback'];

		// Test string to array conversion
		$result = $sanitize_callback( '1,2,3' );
		$this->assertEquals( [ '1', '2', '3' ], $result );

		// Test array remains array
		$result = $sanitize_callback( [ '1', '2', '3' ] );
		$this->assertEquals( [ '1', '2', '3' ], $result );

		// Test single value to array
		$result = $sanitize_callback( '1' );
		$this->assertEquals( [ '1' ], $result );
	}

	/**
	 * @covers Controller::get_tickets_args
	 */
	public function test_per_page_sanitization(): void {
		/** @var Controller $controller */
		$controller = $this->make_controller();

		// Use reflection to access protected method
		$method = Closure::bind(
			function () {
				return $this->get_tickets_args();
			},
			$controller,
			$controller
		);

		$args = $method();
		$sanitize_callback = $args['per_page']['sanitize_callback'];

		// Test absint functionality
		$this->assertEquals( 5, $sanitize_callback( 5 ) );
		$this->assertEquals( 5, $sanitize_callback( '5' ) );
		$this->assertEquals( 5, $sanitize_callback( -5 ) );
		$this->assertEquals( 0, $sanitize_callback( 'abc' ) );
	}

	/**
	 * @covers Controller::get_tickets_args
	 */
	public function test_page_sanitization(): void {
		/** @var Controller $controller */
		$controller = $this->make_controller();

		// Use reflection to access protected method
		$method = Closure::bind(
			function () {
				return $this->get_tickets_args();
			},
			$controller,
			$controller
		);

		$args = $method();
		$sanitize_callback = $args['page']['sanitize_callback'];

		// Test absint functionality
		$this->assertEquals( 1, $sanitize_callback( 1 ) );
		$this->assertEquals( 1, $sanitize_callback( '1' ) );
		$this->assertEquals( 1, $sanitize_callback( -1 ) );
		$this->assertEquals( 0, $sanitize_callback( 'abc' ) );
	}
}
