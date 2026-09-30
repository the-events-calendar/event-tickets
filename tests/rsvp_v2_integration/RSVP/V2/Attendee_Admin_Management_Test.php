<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use stdClass;
use TEC\Tickets\Commerce\Gateways\Free\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Emails\Email_Abstract;
use TEC\Tickets\RSVP\V2\Cart\RSVP_Cart;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Attendee_Maker;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Ticket_Maker as TC_Ticket_Maker;
use Tribe__Tickets__RSVP as RSVP_V1;
use WP_Post;

/**
 * Managing a TC-RSVP attendee from the Attendees admin screen.
 *
 * The screen is built for tickets, and an RSVP v2 attendee differs from a ticket attendee in ways that
 * broke every step of it:
 *
 * - The RSVP repository scopes its queries to attendees carrying the RSVP status meta, so an attendee
 *   created without it exists but cannot be found. The screen looks an attendee up before offering to
 *   edit it, and a failed lookup makes the form submit as an "add", so editing quietly duplicated the
 *   attendee instead, each copy as unfindable as the last.
 * - The Going / Not going answer lives in its own meta rather than an attendee field, so it was dropped
 *   on save while the modal still reported success.
 * - Resending the attendee's email sent the ticket email, handing a QR code to someone who had just said
 *   they were not coming.
 * - The screen groups tickets by type and had no label for `tc-rsvp`, so the section heading printed the
 *   raw slug.
 *
 * Attendees are built through the real creation paths rather than inserted directly, because which path
 * runs is itself part of what broke: the screen reaches Tickets Commerce on some posts and the RSVP
 * repository on others.
 */
class Attendee_Admin_Management_Test extends WPTestCase {
	use Ticket_Maker;
	use TC_Ticket_Maker;
	use Attendee_Maker;
	use With_Uopz;

	/**
	 * A published post with a TC-RSVP ticket on it, in an admin context.
	 *
	 * @return array{post_id: int, ticket_id: int}
	 */
	private function create_post_with_rsvp_ticket(): array {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		return [ 'post_id' => $post_id, 'ticket_id' => $ticket_id ];
	}

	/**
	 * Creates an attendee the way the Attendees screen does on a post with several tickets: through
	 * Tickets Commerce, which builds a manual Order and generates the attendee from it.
	 *
	 * @return array{attendee_id: int, post_id: int, ticket_id: int}
	 */
	private function create_attendee_via_commerce(): array {
		[ 'post_id' => $post_id, 'ticket_id' => $ticket_id ] = $this->create_post_with_rsvp_ticket();

		$attendee = tribe( Module::class )->create_attendee(
			tribe( Module::class )->get_ticket( $post_id, $ticket_id ),
			[
				'full_name'         => 'Admin Attendee',
				'email'             => 'admin-attendee@example.com',
				'send_ticket_email' => false,
			]
		);

		$this->assertInstanceOf( WP_Post::class, $attendee, 'Tickets Commerce should have created an attendee.' );

		return [ 'attendee_id' => $attendee->ID, 'post_id' => $post_id, 'ticket_id' => $ticket_id ];
	}

	/**
	 * Creates an attendee through the RSVP repository, the path taken when the screen resolves the RSVP
	 * module as the provider.
	 */
	private function create_attendee_via_repository( array $args = [] ): int {
		[ 'post_id' => $post_id, 'ticket_id' => $ticket_id ] = $this->create_post_with_rsvp_ticket();

		$attendee = tribe_attendees( 'rsvp' )
			->set_args(
				array_merge(
					[
						'title'     => 'Repository Attendee',
						'full_name' => 'Repository Attendee',
						'email'     => 'repository@example.com',
						'ticket_id' => $ticket_id,
						'post_id'   => $post_id,
					],
					$args
				)
			)
			->create();

		$this->assertInstanceOf( WP_Post::class, $attendee, 'The repository should have created an attendee.' );

		return $attendee->ID;
	}

	/**
	 * Records email types dispatched from this point on, and stops delivery.
	 *
	 * Records the slug off the dispatcher rather than matching subject text, so rewording the default
	 * copy will not break these assertions.
	 */
	private function record_emails(): stdClass {
		$sent        = new stdClass();
		$sent->slugs = [];

		add_filter(
			'tec_tickets_emails_dispatcher',
			static function ( $dispatcher, $email ) use ( $sent ) {
				if ( $email instanceof Email_Abstract ) {
					$sent->slugs[] = $email->slug;
				}

				return $dispatcher;
			},
			10,
			2
		);

		$this->set_fn_return( 'wp_mail', true, false );

		return $sent;
	}

	/* ---------------------------------------------------------------------
	 * An attendee must be findable after it is created.
	 * ------------------------------------------------------------------ */

	public function test_a_created_attendee_carries_the_rsvp_status_meta(): void {
		$attendee_id = $this->create_attendee_via_repository();

		$this->assertTrue(
			metadata_exists( 'post', $attendee_id, Constants::RSVP_STATUS_META_KEY ),
			'An attendee created without an explicit status still needs one, or no RSVP query will match it.'
		);
		$this->assertSame( 'yes', get_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, true ) );
	}

	public function test_a_created_attendee_is_findable_by_the_repository(): void {
		$attendee_id = $this->create_attendee_via_repository();

		$found = tribe_attendees( 'rsvp' )->by( 'status', 'any' )->by( 'id', $attendee_id )->first();

		$this->assertInstanceOf(
			WP_Post::class,
			$found,
			'The repository must be able to find the attendee it just created.'
		);
		$this->assertEquals( $attendee_id, $found->ID );
	}

	public function test_a_supplied_attendee_status_is_preserved_on_create(): void {
		// The Attendees screen's add form supplies the answer under this name.
		$attendee_id = $this->create_attendee_via_repository( [ 'attendee_status' => 'no' ] );

		$this->assertSame(
			'no',
			get_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, true ),
			'A Not Going answer must not be overwritten by the default.'
		);
	}

	public function test_an_attendee_created_through_tickets_commerce_is_findable(): void {
		$fixture = $this->create_attendee_via_commerce();

		$this->assertTrue(
			metadata_exists( 'post', $fixture['attendee_id'], Constants::RSVP_STATUS_META_KEY ),
			'An attendee added from the Attendees screen still needs the RSVP status meta.'
		);

		$found = tribe_attendees( 'rsvp' )->by( 'status', 'any' )->by( 'id', $fixture['attendee_id'] )->first();

		$this->assertInstanceOf(
			WP_Post::class,
			$found,
			'The RSVP repository must find an attendee added from the Attendees screen.'
		);
	}

	/* ---------------------------------------------------------------------
	 * Looking the attendee up, which is what gates edit versus add.
	 * ------------------------------------------------------------------ */

	/**
	 * Under RSVP v2 the RSVP attendee repository serves `tec_tc_attendee` posts while
	 * `Tribe__Tickets__RSVP` remains the module surfacing RSVP tickets, so its `get_attendee()` fetches a
	 * v2 attendee and must accept it rather than rejecting anything that is not the v1 post type.
	 */
	public function test_the_rsvp_module_resolves_a_tc_rsvp_attendee(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		/*
		 * Build the order through RSVP_Cart, the way Order_Endpoint::process_rsvp_step() does, then stamp
		 * the status meta as that endpoint does once the order completes. Creating a plain ticket and
		 * retyping it afterwards leaves the attendee outside what the RSVP repository returns, which is a
		 * different failure than the one under test.
		 */
		$cart = tribe( RSVP_Cart::class );
		$cart->clear();
		$cart->upsert_item( $ticket_id, 1, [ 'type' => Constants::TC_RSVP_TYPE, 'order_status' => 'yes' ] );
		$cart->save();

		$orders = tribe( Order::class );
		$order  = $orders->create_from_cart(
			tribe( Gateway::class ),
			[
				'purchaser_user_id'    => 0,
				'purchaser_full_name'  => 'Test Attendee',
				'purchaser_first_name' => 'Test',
				'purchaser_last_name'  => 'Attendee',
				'purchaser_email'      => 'attendee@example.com',
			],
			Constants::TC_RSVP_TYPE
		);
		$orders->modify_status( $order->ID, Pending::SLUG );
		$orders->modify_status( $order->ID, Completed::SLUG );
		$cart->clear();

		$attendee_id = (int) tribe( Module::class )->get_attendees_by_order_id( $order->ID )[0]['attendee_id'];
		update_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, 'yes' );

		$attendee = tribe( RSVP_V1::class )->get_attendee( $attendee_id, $post_id );

		$this->assertIsArray(
			$attendee,
			'The RSVP module must resolve an attendee served by the repository it is bound to.'
		);
		$this->assertEquals( $attendee_id, $attendee['attendee_id'] );
		$this->assertEquals( $ticket_id, $attendee['product_id'] );
		$this->assertSame( 'attendee@example.com', $attendee['holder_email'] );
	}

	public function test_the_rsvp_module_still_rejects_a_post_that_is_not_an_attendee(): void {
		$fixture         = $this->create_attendee_via_commerce();
		$not_an_attendee = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->assertFalse(
			tribe( RSVP_V1::class )->get_attendee( $not_an_attendee, $fixture['post_id'] ),
			'A post of an unrelated type must not be treated as an attendee.'
		);
	}

	/* ---------------------------------------------------------------------
	 * Saving the Going / Not going answer from the edit modal.
	 * ------------------------------------------------------------------ */

	public function test_updating_an_attendee_to_not_going_persists(): void {
		$fixture = $this->create_attendee_via_commerce();

		tribe( RSVP_V1::class )->update_attendee(
			$fixture['attendee_id'],
			[ 'full_name' => 'Admin Attendee', 'email' => 'admin-attendee@example.com', 'attendee_status' => 'no' ]
		);

		$this->assertSame(
			'no',
			get_post_meta( $fixture['attendee_id'], Constants::RSVP_STATUS_META_KEY, true ),
			'A Not Going choice from the Edit Attendee modal must reach the RSVP status meta.'
		);
	}

	public function test_updating_an_attendee_back_to_going_persists(): void {
		$fixture = $this->create_attendee_via_commerce();
		update_post_meta( $fixture['attendee_id'], Constants::RSVP_STATUS_META_KEY, 'no' );

		tribe( RSVP_V1::class )->update_attendee(
			$fixture['attendee_id'],
			[ 'full_name' => 'Admin Attendee', 'email' => 'admin-attendee@example.com', 'attendee_status' => 'yes' ]
		);

		$this->assertSame( 'yes', get_post_meta( $fixture['attendee_id'], Constants::RSVP_STATUS_META_KEY, true ) );
	}

	/**
	 * On a post with more than one ticket the screen resolves Tickets Commerce as the provider, so the
	 * update runs through `Module::update_attendee()` rather than the RSVP repository.
	 */
	public function test_updating_through_tickets_commerce_persists(): void {
		$fixture = $this->create_attendee_via_commerce();

		tribe( Module::class )->update_attendee(
			$fixture['attendee_id'],
			[ 'full_name' => 'Admin Attendee', 'email' => 'admin-attendee@example.com', 'attendee_status' => 'no' ]
		);

		$this->assertSame(
			'no',
			get_post_meta( $fixture['attendee_id'], Constants::RSVP_STATUS_META_KEY, true ),
			'Tickets Commerce must persist a Not Going answer for a TC-RSVP attendee.'
		);
	}

	public function test_updating_a_non_rsvp_attendee_does_not_gain_rsvp_status(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$attendee = tribe( Module::class )->create_attendee(
			tribe( Module::class )->get_ticket( $post_id, $ticket_id ),
			[ 'full_name' => 'Plain Attendee', 'email' => 'plain@example.com', 'send_ticket_email' => false ]
		);

		tribe( Module::class )->update_attendee(
			$attendee->ID,
			[ 'full_name' => 'Plain Attendee', 'email' => 'plain@example.com', 'attendee_status' => 'no' ]
		);

		$this->assertFalse(
			metadata_exists( 'post', $attendee->ID, Constants::RSVP_STATUS_META_KEY ),
			'A regular ticket attendee must not pick up RSVP status meta.'
		);
	}

	/* ---------------------------------------------------------------------
	 * Resending the attendee's email.
	 * ------------------------------------------------------------------ */

	public function test_a_not_going_answer_resends_the_not_going_email(): void {
		$fixture = $this->create_attendee_via_commerce();
		$sent    = $this->record_emails();

		tribe( Module::class )->update_attendee(
			$fixture['attendee_id'],
			[
				'full_name'         => 'Admin Attendee',
				'email'             => 'admin-attendee@example.com',
				'attendee_status'   => 'no',
				'send_ticket_email' => true,
			]
		);

		$this->assertSame(
			[ 'rsvp-not-going' ],
			$sent->slugs,
			'Someone who answered Not Going must not be sent a ticket with a QR code.'
		);
	}

	public function test_a_going_answer_resends_the_rsvp_email(): void {
		$fixture = $this->create_attendee_via_commerce();
		$sent    = $this->record_emails();

		tribe( Module::class )->update_attendee(
			$fixture['attendee_id'],
			[
				'full_name'         => 'Admin Attendee',
				'email'             => 'admin-attendee@example.com',
				'attendee_status'   => 'yes',
				'send_ticket_email' => true,
			]
		);

		$this->assertSame( [ 'rsvp' ], $sent->slugs );
	}

	public function test_a_regular_ticket_still_resends_the_ticket_email(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$attendee = tribe( Module::class )->create_attendee(
			tribe( Module::class )->get_ticket( $post_id, $ticket_id ),
			[ 'full_name' => 'Plain Attendee', 'email' => 'plain@example.com', 'send_ticket_email' => false ]
		);

		$sent = $this->record_emails();

		tribe( Module::class )->update_attendee(
			$attendee->ID,
			[ 'full_name' => 'Plain Attendee', 'email' => 'plain@example.com', 'send_ticket_email' => true ]
		);

		$this->assertNotContains( 'rsvp', $sent->slugs, 'A regular ticket must not send an RSVP email.' );
		$this->assertNotContains( 'rsvp-not-going', $sent->slugs );
	}

	/* ---------------------------------------------------------------------
	 * How the screen labels the RSVP group.
	 * ------------------------------------------------------------------ */

	public function test_the_ticket_overview_labels_a_tc_rsvp_group(): void {
		[ 'post_id' => $post_id ] = $this->create_post_with_rsvp_ticket();

		$context = tribe( 'tickets.attendees' )->get_render_context( $post_id );

		$this->assertArrayHasKey(
			Constants::TC_RSVP_TYPE,
			$context['tickets_by_type'],
			'A TC-RSVP ticket should form its own group on the Attendees screen.'
		);
		$this->assertArrayHasKey(
			Constants::TC_RSVP_TYPE,
			$context['type_labels'],
			'Without a label the heading falls back to the raw "tc-rsvp" slug.'
		);
		$this->assertSame(
			tribe_get_rsvp_label_plural( 'attendee overview' ),
			$context['type_labels'][ Constants::TC_RSVP_TYPE ]
		);
		$this->assertArrayHasKey(
			Constants::TC_RSVP_TYPE,
			$context['type_icon_classes'],
			'Without an icon class the group heading renders with no icon.'
		);
	}

	public function test_the_existing_ticket_overview_labels_are_left_alone(): void {
		[ 'post_id' => $post_id ] = $this->create_post_with_rsvp_ticket();

		$context = tribe( 'tickets.attendees' )->get_render_context( $post_id );

		$this->assertArrayHasKey( 'default', $context['type_labels'] );
		$this->assertArrayHasKey( 'rsvp', $context['type_labels'] );
	}
}
