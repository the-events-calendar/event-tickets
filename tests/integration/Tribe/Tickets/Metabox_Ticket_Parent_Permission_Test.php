<?php

namespace Tribe\Tickets;

use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Tickets\Commerce\Module as Commerce;
use Tribe\Tickets\Test\Traits\With_Ticket_Providers;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Metabox as Metabox;
use Tribe__Tickets__RSVP as RSVP;
use WP_Error;

/**
 * The ticket editor AJAX actions only act on a ticket through the post the ticket is attached to.
 */
class Metabox_Ticket_Parent_Permission_Test extends WPTestCase {
	use With_Ticket_Providers;

	/**
	 * @after
	 */
	public function reset_request(): void {
		$_POST = [];
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
	public function it_should_not_save_a_ticket_of_another_post( string $provider, callable $make_ticket ): void {
		$ticket_id   = $make_ticket( static::factory()->post->create() );
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
	public function it_should_save_a_ticket_through_the_post_it_is_attached_to( string $provider, callable $make_ticket ): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id   = static::factory()->post->create();
		$ticket_id = $make_ticket( $post_id );
		$new_name  = $this->faker->sentence();

		$result = $this->request_ticket_save( $post_id, $provider, $ticket_id, $new_name );

		$this->assertIsArray( $result );
		$this->assertSame( $new_name, get_post_field( 'post_title', $ticket_id, 'raw' ) );
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_not_load_a_ticket_of_another_post_for_editing( string $provider, callable $make_ticket ): void {
		$ticket_id = $make_ticket( static::factory()->post->create() );
		$own_draft = $this->log_in_as_contributor_with_own_draft();

		$result = $this->request_ticket_action( 'ajax_ticket_edit', 'edit_ticket_nonce', $own_draft, $ticket_id );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_load_a_ticket_for_editing_through_the_post_it_is_attached_to( string $provider, callable $make_ticket ): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id   = static::factory()->post->create();
		$ticket_id = $make_ticket( $post_id );

		$result = $this->request_ticket_action( 'ajax_ticket_edit', 'edit_ticket_nonce', $post_id, $ticket_id );

		$this->assertIsArray( $result );
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_not_duplicate_a_ticket_of_another_post( string $provider, callable $make_ticket ): void {
		$ticket_id = $make_ticket( static::factory()->post->create() );
		$own_draft = $this->log_in_as_contributor_with_own_draft();

		$result = $this->request_ticket_action( 'ajax_ticket_duplicate', 'duplicate_ticket_nonce', $own_draft, $ticket_id );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( [], tribe_tickets()->where( 'event', $own_draft )->get_ids() );
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_duplicate_a_ticket_through_the_post_it_is_attached_to( string $provider, callable $make_ticket ): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id   = static::factory()->post->create();
		$ticket_id = $make_ticket( $post_id );

		$result = $this->request_ticket_action( 'ajax_ticket_duplicate', 'duplicate_ticket_nonce', $post_id, $ticket_id );

		$this->assertIsArray( $result );
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
					'ticket_price'       => $this->faker->numerify( '%#' ),
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
}
