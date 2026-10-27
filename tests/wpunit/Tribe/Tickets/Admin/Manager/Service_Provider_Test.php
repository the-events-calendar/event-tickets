<?php

namespace Tribe\Tickets\Admin\Manager;

use Codeception\TestCase\WPTestCase;
use Exception;
use Generator;
use Tribe\Tests\Traits\With_Uopz;

class Service_Provider_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * The response captured from the handler's terminating wp_send_json_* call.
	 *
	 * Static because uopz replaces the function body without a $this binding.
	 *
	 * @var array{type: string, data: mixed}|null
	 */
	public static ?array $response = null;

	/**
	 * Whether the request filter, standing in for a registered handler, ran.
	 *
	 * @var bool
	 */
	private bool $handler_ran = false;

	public function setUp(): void {
		parent::setUp();

		self::$response    = null;
		$this->handler_ran = false;

		// wp_send_json_* end the request with wp_die(); throwing reproduces that halt.
		$capture = static function ( string $type ): callable {
			return static function ( $data = null ) use ( $type ) {
				Service_Provider_Test::$response = [
					'type' => $type,
					'data' => $data,
				];
				throw new Exception( 'wp_send_json halted the request.' );
			};
		};

		$this->set_fn_return( 'wp_send_json_error', $capture( 'error' ), true );
		$this->set_fn_return( 'wp_send_json_success', $capture( 'success' ), true );

		add_filter(
			'tribe_tickets_admin_manager_request',
			function () {
				$this->handler_ran = true;

				return '<p>handler</p>';
			}
		);
	}

	public function tearDown(): void {
		$_REQUEST = [];

		parent::tearDown();
	}

	private function request_as( string $role, string $request ): void {
		wp_set_current_user( 'visitor' === $role ? 0 : $this->factory()->user->create( [ 'role' => $role ] ) );

		$_REQUEST = [
			'nonce'   => wp_create_nonce( 'tribe_tickets_admin_manager_nonce' ),
			'request' => $request,
		];

		try {
			tribe( 'tickets.admin.manager' )->ajax_handle_admin_manager();
		} catch ( Exception $e ) {
			// Expected: stands in for wp_die() ending the request.
		}
	}

	public function request_matrix(): Generator {
		yield 'visitor, attendee details' => [ 'visitor', 'tec_tickets_attendee_details', false ];
		yield 'visitor, email preview' => [ 'visitor', 'tec_tickets_preview_email', false ];
		yield 'subscriber, attendee details' => [ 'subscriber', 'tec_tickets_attendee_details', false ];
		yield 'subscriber, email preview' => [ 'subscriber', 'tec_tickets_preview_email', false ];
		yield 'contributor, attendee details' => [ 'contributor', 'tec_tickets_attendee_details', true ];
		yield 'contributor, email preview' => [ 'contributor', 'tec_tickets_preview_email', false ];
		yield 'editor, attendee details' => [ 'editor', 'tec_tickets_attendee_details', true ];
		yield 'editor, email preview' => [ 'editor', 'tec_tickets_preview_email', false ];
		yield 'administrator, attendee details' => [ 'administrator', 'tec_tickets_attendee_details', true ];
		yield 'administrator, email preview' => [ 'administrator', 'tec_tickets_preview_email', true ];
		yield 'visitor, unmapped type' => [ 'visitor', 'qa_unmapped_type', false ];
		yield 'subscriber, unmapped type' => [ 'subscriber', 'qa_unmapped_type', false ];
		yield 'contributor, unmapped type' => [ 'contributor', 'qa_unmapped_type', true ];
	}

	/**
	 * @test
	 * @dataProvider request_matrix
	 */
	public function it_should_only_run_the_handler_for_users_holding_the_request_types_capability( string $role, string $request, bool $allowed ): void {
		$this->request_as( $role, $request );

		$this->assertSame( $allowed, $this->handler_ran, 'The handler must run only for an allowed user.' );
		$this->assertSame( $allowed ? 'success' : 'error', self::$response['type'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_register_the_logged_out_ajax_action(): void {
		set_current_screen( 'dashboard' );

		( new Service_Provider( tribe() ) )->register();

		$this->assertFalse( has_action( 'wp_ajax_nopriv_tribe_tickets_admin_manager' ) );
		$this->assertNotFalse( has_action( 'wp_ajax_tribe_tickets_admin_manager' ) );
	}
}
