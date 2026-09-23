<?php

namespace TEC\Tickets\Deferred_Save\Security;

use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Tickets\Deferred_Save\Classic\Notices;
use TEC\Tickets\Deferred_Save\Classic_Save;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe\Tests\Tickets\Traits\Deferred_Save_Attacks;

/**
 * Attacks through the classic post form: `$_POST` set as WordPress leaves it, then `wp_update_post()`.
 */
class Classic_Entry_Point_Test extends WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;
	use With_Tickets_Commerce;
	use Deferred_Save_Attacks;

	public function setUp(): void {
		parent::setUp();
		$this->enable_switch_filter();
	}

	public function tearDown(): void {
		$_POST                = [];
		$this->deferred_posts = [];
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Submits the form for a post: the payload, the deferred save nonce and the post ID, slashed as WordPress does.
	 */
	protected function submit_form( int $post_id, array $payload, array $overrides = [] ): void {
		$post = array_merge(
			[
				'tec_tickets'            => $payload,
				'post_ID'                => $post_id,
				Classic_Save::NONCE_FIELD => wp_create_nonce( Classic_Save::NONCE_ACTION ),
			],
			$overrides
		);
		$_POST = wp_slash( $post );
		wp_update_post( [ 'ID' => $post_id, 'post_title' => get_the_title( $post_id ) . ' saved' ] );
	}

	protected function remembered_errors(): array {
		$remembered = get_transient( Notices::TRANSIENT_PREFIX . get_current_user_id() );

		return is_array( $remembered ) ? (array) ( $remembered['errors'] ?? [] ) : [];
	}

	public function roles_that_cannot_edit_someone_elses_post(): Generator {
		yield 'subscriber' => [ 'subscriber' ];
		yield 'contributor' => [ 'contributor' ];
		yield 'author' => [ 'author' ];
	}

	/**
	 * @test
	 * @dataProvider roles_that_cannot_edit_someone_elses_post
	 */
	public function a_user_who_cannot_edit_the_post_changes_nothing( string $role ): void {
		$this->given_two_posts_with_tickets();
		$this->log_in_as( $role );
		$before = $this->snapshot();

		$this->submit_form( $this->post_a, $this->hostile_payload() );

		$this->assert_world_unchanged( $before, "A $role changed something through the classic form." );
		$this->assertNotEmpty( $this->remembered_errors(), 'The refusal is reported.' );
	}

	/**
	 * @test
	 */
	public function without_a_valid_nonce_nothing_changes_and_nothing_is_reported(): void {
		$this->given_two_posts_with_tickets();
		wp_set_current_user( $this->owner_id );
		$before = $this->snapshot();

		$this->submit_form( $this->post_a, [ 'create' => [ $this->ticket_data( 'No nonce' ) ] ], [ Classic_Save::NONCE_FIELD => '' ] );
		$this->submit_form( $this->post_a, [ 'create' => [ $this->ticket_data( 'Bad nonce' ) ] ], [ Classic_Save::NONCE_FIELD => 'forged' ] );

		$this->assert_world_unchanged( $before );
		$this->assertSame( [], $this->remembered_errors() );
	}

	/**
	 * @test
	 */
	public function a_form_for_another_post_changes_nothing(): void {
		$this->given_two_posts_with_tickets();
		wp_set_current_user( $this->owner_id );
		$before = $this->snapshot();

		// The form says it is for post B while post A is the one being saved: neither receives the payload.
		$this->submit_form( $this->post_a, [ 'create' => [ $this->ticket_data( 'Wrong post' ) ] ], [ 'post_ID' => $this->post_b ] );

		$this->assert_world_unchanged( $before );
	}

	/**
	 * @test
	 */
	public function an_editor_of_post_a_cannot_reach_post_b_through_any_part(): void {
		$this->given_two_posts_with_tickets();
		wp_set_current_user( $this->owner_id );
		// The owner may edit both posts; make B someone else's so the move destination is refused too.
		wp_update_post( [ 'ID' => $this->post_b, 'post_author' => static::factory()->user->create( [ 'role' => 'administrator' ] ) ] );
		$author_of_a = $this->log_in_as( 'author' );
		wp_update_post( [ 'ID' => $this->post_a, 'post_author' => $author_of_a ] );
		$before = $this->snapshot();

		$this->submit_form( $this->post_a, $this->hostile_payload() );

		$this->assert_b_untouched_and_a_created( $before );
		$keys = array_map( fn( array $e ) => [ $e['part'], $e['key'] ], $this->remembered_errors() );
		// The same foreign ticket in `update` and `delete` is one error, keyed to `update`, by the payload contract.
		$this->assertEqualSets(
			[
				[ 'update', $this->ticket_b ],
				[ 'update', $this->attendee_b ],
				[ 'update', $this->post_b ],
				[ 'move', $this->ticket_a ],
			],
			$keys
		);
	}

	/**
	 * @test
	 */
	public function a_payload_over_the_cap_changes_nothing(): void {
		$this->given_two_posts_with_tickets();
		wp_set_current_user( $this->owner_id );
		add_filter( 'tec_tickets_deferred_save_max_entries', static fn() => 3 );
		$before = $this->snapshot();

		$this->submit_form( $this->post_a, [ 'create' => array_fill( 0, 4, $this->ticket_data( 'Flood' ) ) ] );

		$this->assert_world_unchanged( $before );
		$this->assertCount( 1, $this->remembered_errors() );
	}

	/**
	 * @test
	 */
	public function data_is_sanitized_exactly_as_the_ajax_save_sanitizes_it(): void {
		$this->given_two_posts_with_tickets();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$data = $this->ticket_data(
			'<b>Bold</b> <script>alert(1)</script> name',
			[
				'ticket_description' => '<p>Kept</p><script>alert(2)</script><img src=x onerror=alert(3)>',
				'ticket_sku'         => 'SKU <i>x</i>',
			]
		);

		// Today's path, on a post of its own so the panels it renders have nothing else to load.
		$post_c = static::factory()->post->create();
		$_POST  = [
			'post_id'     => $post_c,
			'data'        => $data,
			'ticket_type' => 'default',
			'nonce'       => wp_create_nonce( 'add_ticket_nonce' ),
		];
		$this->assertIsArray( tribe( 'tickets.metabox' )->ajax_ticket_add( true ) );
		$ajax_ids = tribe_tickets()->where( 'event', $post_c )->get_ids();
		$ajax_id  = (int) reset( $ajax_ids );

		// The deferred path.
		$this->submit_form( $this->post_a, [ 'create' => [ $data ] ] );
		$deferred_ids = array_diff( tribe_tickets()->where( 'event', $this->post_a )->get_ids(), [ $this->ticket_a ] );
		$deferred_id  = (int) reset( $deferred_ids );

		$this->assertGreaterThan( 0, $ajax_id );
		$this->assertGreaterThan( 0, $deferred_id );
		$stored = fn( int $id ) => [
			'title'   => get_post_field( 'post_title', $id, 'raw' ),
			'excerpt' => get_post_field( 'post_excerpt', $id, 'raw' ),
			'sku'     => get_post_meta( $id, '_sku', true ),
			'price'   => get_post_meta( $id, '_price', true ),
		];
		$this->assertSame( $stored( $ajax_id ), $stored( $deferred_id ) );
		$this->assertStringNotContainsString( '<script>', $stored( $deferred_id )['title'] );
	}
}
