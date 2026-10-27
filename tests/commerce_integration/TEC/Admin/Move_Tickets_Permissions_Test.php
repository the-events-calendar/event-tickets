<?php

namespace TEC\Admin;

use Codeception\TestCase\WPTestCase;
use Faker\Factory as Faker;
use TEC\Tickets\Commerce\Module;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Main as Tickets_Main;
use RuntimeException;
use WPDieException;

/**
 * Moving tickets and attendees requires the right to change both the post they leave and the post they land on.
 */
class Move_Tickets_Permissions_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	private int $admin_id;
	private int $contributor_id;
	private int $admin_event_id;
	private int $admin_ticket_id;
	private array $admin_attendee_ids;
	private string $permission_error;

	/**
	 * @before
	 */
	public function create_fixtures(): void {
		$this->permission_error = 'You do not have permission to perform this action. Please reload the page.';
		$this->admin_id         = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$this->contributor_id   = self::factory()->user->create( [ 'role' => 'contributor' ] );

		wp_set_current_user( $this->admin_id );
		$this->admin_event_id  = ( new Event() )->create(
			[
				'post_author' => $this->admin_id,
				'post_title'  => 'Administrator gala',
			]
		);
		$this->admin_ticket_id = $this->create_tc_ticket( $this->admin_event_id, 10 );
		$this->create_order( [ $this->admin_ticket_id => 2 ] );
		$this->admin_attendee_ids = $this->attendee_ids( $this->admin_event_id );

		$this->assertNotEmpty( $this->admin_attendee_ids, 'The order should create attendees to move.' );
	}

	/**
	 * @after
	 */
	public function reset_request(): void {
		$_POST    = [];
		$_GET     = [];
		$_REQUEST = [];
		wp_set_current_user( 0 );
	}

	/**
	 * @test
	 */
	public function it_should_not_move_a_ticket_type_off_an_event_the_user_cannot_edit(): void {
		$contributor_event_id = $this->create_event_as( $this->contributor_id );

		$response = $this->request_as(
			$this->contributor_id,
			[ Tickets_Main::instance()->move_ticket_types(), 'move_ticket_type_requests' ],
			[
				'ticket_type_id' => $this->admin_ticket_id,
				'target_post_id' => $contributor_event_id,
				'src_post_id'    => $contributor_event_id,
			]
		);

		$this->assertFalse( $response['success'] );
		$this->assertSame( $this->admin_event_id, $this->ticket_event_id( $this->admin_ticket_id ) );
		$this->assertEqualSets( $this->admin_attendee_ids, $this->attendee_ids( $this->admin_event_id ) );
		$this->assertSame( [], $this->attendee_ids( $contributor_event_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_move_a_ticket_type_to_a_post_the_user_cannot_edit(): void {
		$contributor_event_id  = $this->create_event_as( $this->contributor_id );
		$contributor_ticket_id = $this->create_tc_ticket( $contributor_event_id, 5 );

		$response = $this->request_as(
			$this->contributor_id,
			[ Tickets_Main::instance()->move_ticket_types(), 'move_ticket_type_requests' ],
			[
				'ticket_type_id' => $contributor_ticket_id,
				'target_post_id' => $this->admin_event_id,
				'src_post_id'    => $contributor_event_id,
			]
		);

		$this->assertFalse( $response['success'] );
		$this->assertSame( $contributor_event_id, $this->ticket_event_id( $contributor_ticket_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_move_a_ticket_type_to_a_post_type_that_cannot_have_tickets(): void {
		$post_id = self::factory()->post->create( [ 'post_type' => 'post' ] );

		$this->assertNotContains( 'post', Tickets_Main::instance()->post_types() );

		$response = $this->request_as(
			$this->admin_id,
			[ Tickets_Main::instance()->move_ticket_types(), 'move_ticket_type_requests' ],
			[
				'ticket_type_id' => $this->admin_ticket_id,
				'target_post_id' => $post_id,
			]
		);

		$this->assertFalse( $response['success'] );
		$this->assertSame( $this->admin_event_id, $this->ticket_event_id( $this->admin_ticket_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_move_a_ticket_type_between_events_the_user_can_edit(): void {
		$first_event_title = Faker::create()->unique()->sentence( 4 );
		$first_event_id    = $this->create_event_as( $this->contributor_id, [ 'post_title' => $first_event_title ] );
		$second_event_id   = $this->create_event_as( $this->contributor_id );
		$ticket_id       = $this->create_tc_ticket( $first_event_id, 5 );

		$response = $this->request_as(
			$this->contributor_id,
			[ Tickets_Main::instance()->move_ticket_types(), 'move_ticket_type_requests' ],
			[
				'ticket_type_id' => $ticket_id,
				'target_post_id' => $second_event_id,
				'src_post_id'    => $this->admin_event_id,
			]
		);

		$this->assertTrue( $response['success'] );
		$this->assertSame( $second_event_id, $this->ticket_event_id( $ticket_id ) );
		$this->assertStringContainsString( esc_html( $first_event_title ), $response['data']['message'] );
		$this->assertStringNotContainsString( 'Administrator gala', $response['data']['message'], 'The source post comes from the ticket, not the request.' );
	}

	/**
	 * @test
	 */
	public function it_should_move_a_ticket_type_between_published_events_of_its_author(): void {
		$author_id       = self::factory()->user->create( [ 'role' => 'author' ] );
		$first_event_id  = $this->create_event_as( $author_id, [ 'post_status' => 'publish' ] );
		$second_event_id = $this->create_event_as( $author_id, [ 'post_status' => 'publish' ] );
		$ticket_id       = $this->create_tc_ticket( $first_event_id, 5 );

		$response = $this->request_as(
			$author_id,
			[ Tickets_Main::instance()->move_ticket_types(), 'move_ticket_type_requests' ],
			[
				'ticket_type_id' => $ticket_id,
				'target_post_id' => $second_event_id,
			]
		);

		$this->assertTrue( $response['success'] );
		$this->assertSame( $second_event_id, $this->ticket_event_id( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_move_attendees_for_a_user_who_cannot_manage_them(): void {
		$contributor_event_id  = $this->create_event_as( $this->contributor_id );
		$contributor_ticket_id = $this->create_tc_ticket( $contributor_event_id, 5 );

		$response = $this->request_as(
			$this->contributor_id,
			[ Tickets_Main::instance()->move_tickets(), 'move_tickets_request' ],
			[
				'ticket_ids'     => $this->admin_attendee_ids,
				'target_type_id' => $contributor_ticket_id,
				'src_post_id'    => $this->admin_event_id,
				'target_post_id' => $contributor_event_id,
			]
		);

		$this->assertFalse( $response['success'] );
		$this->assertEqualSets( $this->admin_attendee_ids, $this->attendee_ids( $this->admin_event_id ) );
		$this->assertSame( [], $this->attendee_ids( $contributor_event_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_move_attendees_off_an_event_the_user_cannot_manage(): void {
		$target_event_id  = $this->create_event_as( $this->admin_id );
		$target_ticket_id = $this->create_tc_ticket( $target_event_id, 5 );
		$this->deny_managing_attendees_of( $this->admin_event_id );

		$response = $this->request_as(
			$this->admin_id,
			[ Tickets_Main::instance()->move_tickets(), 'move_tickets_request' ],
			[
				'ticket_ids'     => $this->admin_attendee_ids,
				'target_type_id' => $target_ticket_id,
				'src_post_id'    => $this->admin_event_id,
				'target_post_id' => $target_event_id,
			]
		);

		$this->assertFalse( $response['success'] );
		$this->assertEqualSets( $this->admin_attendee_ids, $this->attendee_ids( $this->admin_event_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_move_attendees_to_an_event_the_user_cannot_manage(): void {
		$target_event_id  = $this->create_event_as( $this->admin_id );
		$target_ticket_id = $this->create_tc_ticket( $target_event_id, 5 );
		$this->deny_managing_attendees_of( $target_event_id );

		$response = $this->request_as(
			$this->admin_id,
			[ Tickets_Main::instance()->move_tickets(), 'move_tickets_request' ],
			[
				'ticket_ids'     => $this->admin_attendee_ids,
				'target_type_id' => $target_ticket_id,
				'src_post_id'    => $this->admin_event_id,
				'target_post_id' => $target_event_id,
			]
		);

		$this->assertFalse( $response['success'] );
		$this->assertEqualSets( $this->admin_attendee_ids, $this->attendee_ids( $this->admin_event_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_move_attendees_without_a_source_event(): void {
		$target_ticket_id = $this->create_tc_ticket( $this->admin_event_id, 5 );
		$this->grant_managing_attendees_of( 0 );

		$response = $this->request_as(
			$this->contributor_id,
			[ Tickets_Main::instance()->move_tickets(), 'move_tickets_request' ],
			[
				'ticket_ids'     => $this->admin_attendee_ids,
				'target_type_id' => $target_ticket_id,
				'src_post_id'    => 0,
				'target_post_id' => 0,
			]
		);

		$this->assertFalse( $response['success'] );
		$this->assertSame( $this->permission_error, $response['data']['message'] );
		$this->assertEqualSets( $this->admin_attendee_ids, $this->attendee_ids( $this->admin_event_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_move_attendees_of_an_event_other_than_the_source(): void {
		$managed_event_id  = $this->create_event_as( $this->admin_id );
		$managed_ticket_id = $this->create_tc_ticket( $managed_event_id, 5 );
		$this->deny_managing_attendees_of( $this->admin_event_id );

		$response = $this->request_as(
			$this->admin_id,
			[ Tickets_Main::instance()->move_tickets(), 'move_tickets_request' ],
			[
				'ticket_ids'     => $this->admin_attendee_ids,
				'target_type_id' => $managed_ticket_id,
				'src_post_id'    => $managed_event_id,
				'target_post_id' => $managed_event_id,
			]
		);

		$this->assertFalse( $response['success'] );
		$this->assertSame( $this->permission_error, $response['data']['message'] );
		$this->assertEqualSets( $this->admin_attendee_ids, $this->attendee_ids( $this->admin_event_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_move_attendees_to_a_ticket_of_another_event(): void {
		$target_event_id = $this->create_event_as( $this->admin_id );
		$other_event_id  = $this->create_event_as( $this->admin_id );
		$other_ticket_id = $this->create_tc_ticket( $other_event_id, 5 );

		$response = $this->request_as(
			$this->admin_id,
			[ Tickets_Main::instance()->move_tickets(), 'move_tickets_request' ],
			[
				'ticket_ids'     => $this->admin_attendee_ids,
				'target_type_id' => $other_ticket_id,
				'src_post_id'    => $this->admin_event_id,
				'target_post_id' => $target_event_id,
			]
		);

		$this->assertFalse( $response['success'] );
		$this->assertEqualSets( $this->admin_attendee_ids, $this->attendee_ids( $this->admin_event_id ) );
		$this->assertSame(
			0,
			Tickets_Main::instance()->move_tickets()->move_tickets( $this->admin_attendee_ids, $other_ticket_id, $this->admin_event_id, $target_event_id )
		);
	}

	/**
	 * @test
	 */
	public function it_should_move_attendees_between_events_the_user_can_manage(): void {
		$target_event_id  = $this->create_event_as( $this->admin_id );
		$target_ticket_id = $this->create_tc_ticket( $target_event_id, 5 );

		$response = $this->request_as(
			$this->admin_id,
			[ Tickets_Main::instance()->move_tickets(), 'move_tickets_request' ],
			[
				'ticket_ids'     => $this->admin_attendee_ids,
				'target_type_id' => $target_ticket_id,
				'src_post_id'    => $this->admin_event_id,
				'target_post_id' => $target_event_id,
			]
		);

		$this->assertTrue( $response['success'] );
		$this->assertEqualSets( $this->admin_attendee_ids, $this->attendee_ids( $target_event_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_list_the_tickets_of_an_event_the_user_cannot_manage(): void {
		$response = $this->request_as(
			$this->contributor_id,
			[ Tickets_Main::instance()->move_tickets(), 'get_ticket_types' ],
			[
				'post_id'    => $this->admin_event_id,
				'provider'   => Module::class,
				'ticket_ids' => [],
			]
		);

		$this->assertFalse( $response['success'] );

		$response = $this->request_as(
			$this->admin_id,
			[ Tickets_Main::instance()->move_tickets(), 'get_ticket_types' ],
			[
				'post_id'    => $this->admin_event_id,
				'provider'   => Module::class,
				'ticket_ids' => [],
			]
		);

		$this->assertTrue( $response['success'] );
		$this->assertSame( [ $this->admin_ticket_id ], array_keys( $response['data']['posts'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_only_offer_destinations_the_user_may_move_to(): void {
		$contributor_event_id = $this->create_event_as( $this->contributor_id );
		$admin_draft_id       = $this->create_event_as( $this->admin_id );

		// Admin AJAX requests run with `is_admin()`, which makes WP_Query include drafts.
		set_current_screen( 'dashboard' );

		try {
			$response = $this->request_as(
				$this->contributor_id,
				[ Tickets_Main::instance()->move_tickets(), 'get_post_choices' ],
				[ 'post_type' => 'all' ]
			);
		} finally {
			set_current_screen( 'front' );
		}

		$this->assertTrue( $response['success'] );
		$this->assertContains( $contributor_event_id, $response['data']['posts'] );
		$this->assertNotContains( $admin_draft_id, $response['data']['posts'] );
		$this->assertNotContains( $this->admin_event_id, $response['data']['posts'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_open_the_move_dialog_for_a_post_the_user_can_neither_edit_nor_manage(): void {
		$this->assertFalse( $this->dialog_renders_as( $this->contributor_id, $this->admin_event_id ) );
		$this->assertTrue( $this->dialog_renders_as( $this->admin_id, $this->admin_event_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_open_the_move_dialog_for_a_post_the_user_can_edit(): void {
		$contributor_event_id = $this->create_event_as( $this->contributor_id );

		$this->assertTrue( $this->dialog_renders_as( $this->contributor_id, $contributor_event_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_open_the_move_dialog_for_a_post_whose_attendees_the_user_manages(): void {
		$this->grant_managing_attendees_of( $this->admin_event_id );

		$this->assertTrue( $this->dialog_renders_as( $this->contributor_id, $this->admin_event_id ) );
		$this->assertFalse( current_user_can( 'edit_post', $this->admin_event_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_offer_destinations_whose_attendees_the_user_manages(): void {
		$admin_draft_id = $this->create_event_as( $this->admin_id );
		$this->grant_managing_attendees_of( $admin_draft_id );

		set_current_screen( 'dashboard' );

		try {
			$response = $this->request_as(
				$this->contributor_id,
				[ Tickets_Main::instance()->move_tickets(), 'get_post_choices' ],
				[ 'post_type' => 'all' ]
			);
		} finally {
			set_current_screen( 'front' );
		}

		$this->assertFalse( current_user_can( 'edit_post', $admin_draft_id ) );
		$this->assertContains( $admin_draft_id, $response['data']['posts'] );
	}

	private function create_event_as( int $user_id, array $args = [] ): int {
		return ( new Event() )->create(
			array_merge(
				[
					'post_author' => $user_id,
					'post_status' => 'draft',
				],
				$args
			)
		);
	}

	private function request_as( int $user_id, callable $handler, array $post ): array {
		wp_set_current_user( $user_id );
		$_POST = wp_slash( array_merge( [ 'check' => wp_create_nonce( 'move_tickets' ) ], $post ) );

		// `wp_send_json()` dies through the AJAX handler; throwing stops the request where production would exit.
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter(
			'wp_die_ajax_handler',
			static function () {
				return static function () {
					throw new WPDieException();
				};
			}
		);

		ob_start();

		try {
			$handler();
		} catch ( WPDieException $e ) {
			// The response was sent.
		} finally {
			$output = ob_get_clean();
		}

		$response = json_decode( $output, true );
		$this->assertIsArray( $response, 'The handler should send a JSON response.' );

		return $response;
	}

	private function dialog_renders_as( int $user_id, int $event_id ): bool {
		wp_set_current_user( $user_id );
		$_GET     = [
			'dialog'   => Tickets_Main::instance()->move_tickets()->dialog_name(),
			'check'    => wp_create_nonce( 'move_tickets' ),
			'event_id' => $event_id,
		];
		$_REQUEST = $_GET;

		// The dialog prints an iframe and exits, so stop it where it reads its template variables.
		add_filter(
			'tribe_tickets_move_tickets_template_vars',
			static function () {
				throw new RuntimeException( 'The dialog would render.' );
			}
		);

		try {
			Tickets_Main::instance()->move_tickets()->dialog();
		} catch ( RuntimeException $e ) {
			return true;
		}

		return false;
	}

	private function grant_managing_attendees_of( int $event_id ): void {
		add_filter(
			'tribe_tickets_user_can_manage_attendees',
			static function ( $user_can, $user_id, $checked_event_id ) use ( $event_id ) {
				return absint( $checked_event_id ) === $event_id ? true : $user_can;
			},
			10,
			3
		);
	}

	private function deny_managing_attendees_of( int $event_id ): void {
		add_filter(
			'tribe_tickets_user_can_manage_attendees',
			static function ( $user_can, $user_id, $checked_event_id ) use ( $event_id ) {
				return absint( $checked_event_id ) === $event_id ? false : $user_can;
			},
			10,
			3
		);
	}

	private function ticket_event_id( int $ticket_id ): int {
		clean_post_cache( $ticket_id );

		return absint( get_post_meta( $ticket_id, tribe( Module::class )->get_event_key(), true ) );
	}

	private function attendee_ids( int $event_id ): array {
		tribe_cache()->reset();

		return array_map( 'intval', tribe_attendees()->where( 'event', $event_id )->get_ids() );
	}
}
