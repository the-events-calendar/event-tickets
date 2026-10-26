<?php

namespace Tribe\Tickets;

use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Tickets\Commerce\Module as Commerce;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Traits\With_Ticket_Providers;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Metabox as Metabox;
use Tribe__Tickets__RSVP as RSVP;
use WP_Error;
use WPDieException;

/**
 * The ticket editor AJAX actions only act on a ticket through the post the ticket is attached to.
 */
class Metabox_Ticket_Parent_Permission_Test extends WPTestCase {
	use With_Ticket_Providers;
	use With_Uopz;

	/**
	 * @after
	 */
	public function reset_request(): void {
		$_POST    = [];
		$_REQUEST = [];
	}

	public function provider_ticket_providers(): Generator {
		yield 'Tickets Commerce' => [ Commerce::class ];
		yield 'PayPal' => [ PayPal::class ];
		yield 'RSVP' => [ RSVP::class ];
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_providers
	 */
	public function it_should_not_save_a_post_that_is_not_a_ticket( string $provider ): void {
		$page      = static::factory()->post->create_and_get( [ 'post_type' => 'page' ] );
		$own_draft = $this->log_in_as_contributor_with_own_draft();

		$result = $this->request_ticket_save( $own_draft, $provider, $page->ID, $this->faker->sentence() );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $page->post_title, get_post_field( 'post_title', $page->ID, 'raw' ) );
		$this->assertSame( $page->post_excerpt, get_post_field( 'post_excerpt', $page->ID, 'raw' ) );
		$this->assertFalse( metadata_exists( 'post', $page->ID, '_type' ) );
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_not_save_a_ticket_of_another_post( string $provider, string $make_ticket ): void {
		$ticket_id   = $this->{$make_ticket}( static::factory()->post->create() );
		$ticket_name = get_post_field( 'post_title', $ticket_id, 'raw' );
		$own_draft   = $this->log_in_as_contributor_with_own_draft();

		$result = $this->request_ticket_save( $own_draft, $provider, $ticket_id, $this->faker->sentence() );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $ticket_name, get_post_field( 'post_title', $ticket_id, 'raw' ) );
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_save_a_ticket_through_the_post_it_is_attached_to( string $provider, string $make_ticket ): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->{$make_ticket}( $post_id );
		$new_name  = $this->faker->sentence();

		$result = $this->request_ticket_save( $post_id, $provider, $ticket_id, $new_name );

		$this->assertIsArray( $result );
		$this->assertSame( $new_name, get_post_field( 'post_title', $ticket_id, 'raw' ) );
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_let_an_editor_save_edit_and_duplicate_a_ticket_of_another_authors_post( string $provider, string $make_ticket ): void {
		$post_id   = $this->log_in_as_editor_of_another_authors_post();
		$ticket_id = $this->{$make_ticket}( $post_id );
		$new_name  = $this->faker->sentence();

		$saved      = $this->request_ticket_save( $post_id, $provider, $ticket_id, $new_name );
		$edited     = $this->request_ticket_action( 'ajax_ticket_edit', 'edit_ticket_nonce', $post_id, $ticket_id );
		$duplicated = $this->request_ticket_action( 'ajax_ticket_duplicate', 'duplicate_ticket_nonce', $post_id, $ticket_id );

		$this->assertIsArray( $saved );
		$this->assertSame( $new_name, get_post_field( 'post_title', $ticket_id, 'raw' ) );
		$this->assertIsArray( $edited );
		$this->assertIsArray( $duplicated );
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_not_load_a_ticket_of_another_post_for_editing( string $provider, string $make_ticket ): void {
		$ticket_id = $this->{$make_ticket}( static::factory()->post->create() );
		$own_draft = $this->log_in_as_contributor_with_own_draft();

		$result = $this->request_ticket_action( 'ajax_ticket_edit', 'edit_ticket_nonce', $own_draft, $ticket_id );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_load_a_ticket_for_editing_through_the_post_it_is_attached_to( string $provider, string $make_ticket ): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->{$make_ticket}( $post_id );

		$result = $this->request_ticket_action( 'ajax_ticket_edit', 'edit_ticket_nonce', $post_id, $ticket_id );

		$this->assertIsArray( $result );
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_not_duplicate_a_ticket_of_another_post( string $provider, string $make_ticket ): void {
		$ticket_id = $this->{$make_ticket}( static::factory()->post->create() );
		$own_draft = $this->log_in_as_contributor_with_own_draft();

		$result = $this->request_ticket_action( 'ajax_ticket_duplicate', 'duplicate_ticket_nonce', $own_draft, $ticket_id );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( [], tribe_tickets()->where( 'event', $own_draft )->get_ids() );
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_duplicate_a_ticket_through_the_post_it_is_attached_to( string $provider, string $make_ticket ): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->{$make_ticket}( $post_id );

		$result = $this->request_ticket_action( 'ajax_ticket_duplicate', 'duplicate_ticket_nonce', $post_id, $ticket_id );

		$this->assertIsArray( $result );
	}

	/**
	 * @test
	 */
	public function it_should_only_reorder_the_tickets_of_the_post_when_refreshing_the_panels(): void {
		$own_draft    = $this->log_in_as_contributor_with_own_draft();
		$own_ticket   = $this->create_tc_ticket( $own_draft );
		$other_post   = static::factory()->post->create( [ 'menu_order' => 3 ] );
		$other_ticket = $this->create_tc_ticket( static::factory()->post->create() );
		$other_order  = get_post_field( 'menu_order', $other_ticket );

		$refreshed = $this->request_panels(
			$own_draft,
			wp_create_nonce( 'add_ticket_nonce' ),
			[
				$own_ticket   => [ 'order' => 7 ],
				$other_ticket => [ 'order' => 8 ],
				$other_post   => [ 'order' => 9 ],
			]
		);

		$this->assertTrue( $refreshed );
		$this->assertSame( 7, get_post_field( 'menu_order', $own_ticket ) );
		$this->assertSame( $other_order, get_post_field( 'menu_order', $other_ticket ) );
		$this->assertSame( 3, get_post_field( 'menu_order', $other_post ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_refresh_the_panels_without_the_ticket_editor_nonce(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id );
		$order     = get_post_field( 'menu_order', $ticket_id );

		$refreshed = $this->request_panels( $post_id, 'not-a-nonce', [ $ticket_id => [ 'order' => $order + 5 ] ] );

		$this->assertFalse( $refreshed );
		$this->assertSame( $order, get_post_field( 'menu_order', $ticket_id ) );
	}

	private function request_ticket_save( int $post_id, string $provider, int $ticket_id, string $name ) {
		$_POST = [
			'action'  => 'tribe-ticket-add',
			'post_id' => $post_id,
			'nonce'   => wp_create_nonce( 'add_ticket_nonce' ),
			// The classic editor sends the form as one URL-encoded string.
			'data'    => http_build_query(
				[
					'ticket_provider'    => $provider,
					'ticket_id'          => $ticket_id,
					'ticket_name'        => $name,
					'ticket_description' => $this->faker->paragraph(),
					'ticket_price'       => $this->faker->numberBetween( 1, 100 ),
				]
			),
		];

		return tribe( Metabox::class )->ajax_ticket_add( true );
	}

	private function request_ticket_action( string $action, string $nonce_action, int $post_id, int $ticket_id ) {
		$_POST = [
			'post_id'   => $post_id,
			'ticket_id' => $ticket_id,
			'nonce'     => wp_create_nonce( $nonce_action ),
		];

		return tribe( Metabox::class )->{$action}( true );
	}

	/**
	 * @param array<int,array{order: int}> $list The ticket order the editor sends, by ticket ID.
	 *
	 * @return bool Whether the handler answered with success.
	 */
	private function request_panels( int $post_id, string $nonce, array $list ): bool {
		$_POST    = [
			'action'  => 'tribe-ticket-panels',
			'post_id' => $post_id,
			'nonce'   => $nonce,
			'data'    => http_build_query( [ 'tribe-tickets' => [ 'list' => $list ] ] ),
		];
		$_REQUEST = $_POST;

		// Both end the request in production; stop the handler where they are called.
		$this->set_fn_return( 'wp_send_json_success', static function () { throw new WPDieException( 'success' ); }, true );
		$this->set_fn_return( 'wp_send_json_error', static function () { throw new WPDieException( 'error' ); }, true );

		try {
			tribe( Metabox::class )->ajax_panels();
		} catch ( WPDieException $sent ) {
			return 'success' === $sent->getMessage();
		}

		return false;
	}
}
