<?php

namespace TEC\Tickets\Admin\Attendees;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\DB\DB;
use Exception;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Modal_Test extends WPTestCase {
	use With_Uopz;
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * The response captured from the handler's terminating wp_send_json_* call.
	 *
	 * Static because uopz replaces the function body without a $this binding.
	 *
	 * @var array{type: string, data: mixed}|null
	 */
	public static ?array $response = null;

	/**
	 * The highest post ID before the test ran.
	 *
	 * @var int
	 */
	private int $last_post_id = 0;

	/**
	 * The ticket-enabled post types before the test ran.
	 *
	 * @var array<int,string>
	 */
	private array $enabled_types = [];

	public function setUp(): void {
		parent::setUp();

		$this->last_post_id   = absint( DB::get_var( DB::prepare( 'SELECT MAX(ID) FROM %i', DB::prefix( 'posts' ) ) ) );
		$this->enabled_types = tribe_get_option( 'ticket-enabled-post-types', [] );

		self::$response = null;

		/*
		 * The Attendees provider only hooks this for users who can manage attendees at boot, which
		 * never holds in the test bootstrap. Hook it as it would be for an Editor.
		 */
		add_filter( 'tribe_tickets_admin_manager_request', tribe_callback( Modal::class, 'get_modal_content_ajax' ), 15, 2 );
		tribe_update_option( 'ticket-enabled-post-types', [ 'post' ] );

		// wp_send_json_* end the request with wp_die(); throwing reproduces that halt.
		$capture = static function ( string $type ): callable {
			return static function ( $data = null ) use ( $type ) {
				Modal_Test::$response = [
					'type' => $type,
					'data' => $data,
				];
				throw new Exception( 'wp_send_json halted the request.' );
			};
		};

		$this->set_fn_return( 'wp_send_json_error', $capture( 'error' ), true );
		$this->set_fn_return( 'wp_send_json_success', $capture( 'success' ), true );
	}

	public function tearDown(): void {
		$_REQUEST = [];

		tribe_update_option( 'ticket-enabled-post-types', $this->enabled_types );

		/*
		 * This suite fakes transactions, so nothing is rolled back: remove what the test created or the
		 * attendee and ticket counts of the tests that run after it change.
		 */
		$ids = DB::get_col( DB::prepare( 'SELECT ID FROM %i WHERE ID > %d', DB::prefix( 'posts' ), $this->last_post_id ) );

		foreach ( $ids as $id ) {
			wp_delete_post( absint( $id ), true );
		}

		parent::tearDown();
	}

	/**
	 * Creates a post owned by the given user, with a ticket and an attendee.
	 *
	 * @param int $author_id The post author.
	 *
	 * @return array{post_id: int, ticket_id: int, attendee_id: int, security_code: string}
	 */
	private function create_attendee_on_post_owned_by( int $author_id ): array {
		$post_id       = $this->factory()->post->create( [ 'post_author' => $author_id ] );
		$ticket_id     = $this->create_tc_ticket( $post_id, 10 );
		$security_code = wp_generate_password( 32, false );
		$attendee_id   = $this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'security_code' => $security_code ] );

		return compact( 'post_id', 'ticket_id', 'attendee_id', 'security_code' );
	}

	/**
	 * Narrows who may manage attendees to the author of the attendee's event, as an ownership-only site would.
	 */
	private function limit_attendee_management_to_event_authors(): void {
		add_filter(
			'tribe_tickets_user_can_manage_attendees',
			static function ( $can, $user_id, $event_id ) {
				$event = get_post( absint( $event_id ) );

				return $event && absint( $event->post_author ) === absint( $user_id );
			},
			99,
			3
		);
	}

	/**
	 * Fires the admin manager AJAX request as the current user, with a real nonce.
	 *
	 * @param array{post_id: int, ticket_id: int, attendee_id: int} $fixture The attendee to request.
	 */
	private function request_attendee_details( array $fixture ): void {
		$_REQUEST = [
			'nonce'         => wp_create_nonce( 'tribe_tickets_admin_manager_nonce' ),
			'request'       => 'tec_tickets_attendee_details',
			'attendeeId'    => $fixture['attendee_id'],
			'eventId'       => $fixture['post_id'],
			'ticketId'      => $fixture['ticket_id'],
			'attendeeName'  => 'Spoofed Name',
			'attendeeEmail' => 'spoof@example.test',
		];

		try {
			tribe( 'tickets.admin.manager' )->ajax_handle_admin_manager();
		} catch ( Exception $e ) {
			// Expected: stands in for wp_die() ending the request.
		}
	}

	/**
	 * @test
	 */
	public function it_should_not_disclose_an_attendee_of_another_users_event_to_a_contributor(): void {
		$owner_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		$fixture  = $this->create_attendee_on_post_owned_by( $owner_id );

		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'contributor' ] ) );

		$this->request_attendee_details( $fixture );

		$this->assertSame( 'error', self::$response['type'], 'The cross-event request should be rejected.' );
		$this->assertStringNotContainsString(
			$fixture['security_code'],
			wp_json_encode( self::$response['data'] ),
			'The attendee security code must not be disclosed.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_disclose_the_attendee_to_the_author_of_the_event(): void {
		$author_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		$fixture   = $this->create_attendee_on_post_owned_by( $author_id );

		wp_set_current_user( $author_id );

		$this->request_attendee_details( $fixture );

		$this->assertSame( 'success', self::$response['type'], 'The event author must keep access.' );
		$this->assertStringContainsString( $fixture['security_code'], self::$response['data']['html'] );
	}

	/**
	 * @test
	 */
	public function it_should_disclose_the_attendee_to_an_author_role_user_who_owns_the_event(): void {
		$author_id = $this->factory()->user->create( [ 'role' => 'author' ] );
		$fixture   = $this->create_attendee_on_post_owned_by( $author_id );

		wp_set_current_user( $author_id );

		$this->request_attendee_details( $fixture );

		$this->assertSame( 'success', self::$response['type'], 'An Author manages the attendees of their own event.' );
		$this->assertStringContainsString( $fixture['security_code'], self::$response['data']['html'] );
	}

	/**
	 * @test
	 */
	public function it_should_deny_an_author_role_user_the_attendee_of_another_authors_event(): void {
		$owner_id = $this->factory()->user->create( [ 'role' => 'author' ] );
		$fixture  = $this->create_attendee_on_post_owned_by( $owner_id );

		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'author' ] ) );

		$this->request_attendee_details( $fixture );

		$this->assertSame( 'error', self::$response['type'] );
		$this->assertStringNotContainsString( $fixture['security_code'], wp_json_encode( self::$response['data'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_let_the_management_filter_deny_the_event_author(): void {
		$author_id = $this->factory()->user->create( [ 'role' => 'author' ] );
		$fixture   = $this->create_attendee_on_post_owned_by( $author_id );

		add_filter( 'tribe_tickets_user_can_manage_attendees', '__return_false' );
		wp_set_current_user( $author_id );

		$this->request_attendee_details( $fixture );

		$this->assertSame( 'error', self::$response['type'] );
		$this->assertStringNotContainsString( $fixture['security_code'], wp_json_encode( self::$response['data'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_deny_an_editor_who_does_not_own_the_attendees_event_on_an_ownership_only_site(): void {
		$owner_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		$fixture  = $this->create_attendee_on_post_owned_by( $owner_id );
		$editor   = $this->factory()->user->create( [ 'role' => 'editor' ] );

		$this->limit_attendee_management_to_event_authors();
		wp_set_current_user( $editor );

		$this->request_attendee_details( $fixture );

		$this->assertSame( 'error', self::$response['type'] );
		$this->assertStringNotContainsString( $fixture['security_code'], wp_json_encode( self::$response['data'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_deny_a_contributor_who_cannot_edit_the_event_even_when_the_management_filter_allows_everyone(): void {
		$owner_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		$fixture  = $this->create_attendee_on_post_owned_by( $owner_id );

		add_filter( 'tribe_tickets_user_can_manage_attendees', '__return_true' );
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'contributor' ] ) );

		$this->request_attendee_details( $fixture );

		$this->assertSame( 'error', self::$response['type'] );
		$this->assertStringNotContainsString( $fixture['security_code'], wp_json_encode( self::$response['data'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_disclose_the_attendee_to_an_administrator_when_management_is_denied_without_an_event(): void {
		$owner_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		$fixture  = $this->create_attendee_on_post_owned_by( $owner_id );

		// Community Events denies attendee management when no event ID is given outside the Attendees page.
		add_filter(
			'tribe_tickets_user_can_manage_attendees',
			static function ( $can, $user_id, $event_id ) {
				return $event_id ? $can : false;
			},
			10,
			3
		);
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->request_attendee_details( $fixture );

		$this->assertSame( 'success', self::$response['type'] );
		$this->assertStringContainsString( $fixture['security_code'], self::$response['data']['html'] );
	}

	/**
	 * @test
	 */
	public function it_should_ignore_the_client_supplied_event_id_when_checking_access(): void {
		$owner_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		$fixture  = $this->create_attendee_on_post_owned_by( $owner_id );
		$editor   = $this->factory()->user->create( [ 'role' => 'editor' ] );

		// The editor names an event they own while asking for someone else's attendee.
		$own_post_id = $this->factory()->post->create( [ 'post_author' => $editor ] );

		$this->limit_attendee_management_to_event_authors();
		wp_set_current_user( $editor );

		$this->request_attendee_details( array_merge( $fixture, [ 'post_id' => $own_post_id ] ) );

		$this->assertSame( 'error', self::$response['type'] );
		$this->assertStringNotContainsString( $fixture['security_code'], wp_json_encode( self::$response['data'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_show_the_stored_name_and_email_instead_of_the_request_values(): void {
		$author_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		$fixture   = $this->create_attendee_on_post_owned_by( $author_id );
		$stored    = 'stored-' . wp_generate_password( 8, false ) . '@example.test';

		update_post_meta( $fixture['attendee_id'], '_tec_tickets_commerce_email', $stored );

		wp_set_current_user( $author_id );

		$this->request_attendee_details( $fixture );

		$html = self::$response['data']['html'];

		$this->assertNotEmpty( $stored );
		$this->assertStringContainsString( $stored, $html );
		$this->assertStringNotContainsString( 'spoof@example.test', $html );
		$this->assertStringNotContainsString( 'Spoofed Name', $html );
	}

	/**
	 * @test
	 */
	public function it_should_escape_the_email_as_a_url_in_the_mailto_link(): void {
		$author_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		$fixture   = $this->create_attendee_on_post_owned_by( $author_id );

		update_post_meta( $fixture['attendee_id'], '_tec_tickets_commerce_email', 'a b@example.test?cc=c@example.test&bcc=d@example.test' );
		wp_set_current_user( $author_id );

		$this->request_attendee_details( $fixture );

		$this->assertStringContainsString(
			'href="' . esc_url( 'mailto:a b@example.test?cc=c@example.test&bcc=d@example.test' ) . '"',
			self::$response['data']['html']
		);
	}

	/**
	 * @test
	 */
	public function it_should_report_an_unknown_attendee_without_data(): void {
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'editor' ] ) );

		$this->request_attendee_details(
			[
				'post_id'     => 0,
				'ticket_id'   => 0,
				'attendee_id' => PHP_INT_MAX >> 40,
			]
		);

		$this->assertSame( 'error', self::$response['type'] );
	}
}
