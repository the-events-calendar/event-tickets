<?php

namespace Tribe\Tickets\Editor\REST\V1\Endpoints;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Traits\With_Ticket_Providers;
use WP_Error;
use WP_REST_Request;

/**
 * The block editor ticket endpoint only saves a ticket through the post the ticket is attached to.
 */
class Single_Ticket_Parent_Permission_Test extends WPTestCase {
	use With_Ticket_Providers;

	/**
	 * @before
	 */
	public function register_paypal_types(): void {
		// PayPal only registers its post types on `init` when active, and the REST response reads them.
		tribe( 'tickets.commerce.paypal' )->register_types();
	}

	/**
	 * @test
	 * @dataProvider provider_paid_ticket_makers
	 */
	public function it_should_not_update_a_ticket_of_another_post( string $provider, callable $make_ticket ): void {
		$ticket_id   = $make_ticket( static::factory()->post->create() );
		$ticket_name = get_post_field( 'post_title', $ticket_id, 'raw' );
		$own_draft   = $this->log_in_as_contributor_with_own_draft();

		$result = $this->update_request( $ticket_id, $own_draft, $this->faker->sentence() );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 403, $result->get_error_data()['status'] );
		$this->assertSame( $ticket_name, get_post_field( 'post_title', $ticket_id, 'raw' ) );
	}

	/**
	 * @test
	 * @dataProvider provider_paid_ticket_makers
	 */
	public function it_should_not_save_a_ticket_of_another_post_through_the_create_route( string $provider, callable $make_ticket ): void {
		$ticket_id   = $make_ticket( static::factory()->post->create() );
		$ticket_name = get_post_field( 'post_title', $ticket_id, 'raw' );
		$own_draft   = $this->log_in_as_contributor_with_own_draft();

		$result = $this->create_request( $provider, $ticket_id, $own_draft, $this->faker->sentence() );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 403, $result->get_error_data()['status'] );
		$this->assertSame( $ticket_name, get_post_field( 'post_title', $ticket_id, 'raw' ) );
	}

	/**
	 * @test
	 * @dataProvider provider_paid_ticket_makers
	 */
	public function it_should_update_a_ticket_through_the_post_it_is_attached_to( string $provider, callable $make_ticket ): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id   = static::factory()->post->create();
		$ticket_id = $make_ticket( $post_id );
		$new_name  = $this->faker->sentence();

		$result = $this->update_request( $ticket_id, $post_id, $new_name );

		$this->assertSame( 202, $result->get_status() );
		$this->assertSame( $new_name, get_post_field( 'post_title', $ticket_id, 'raw' ) );
	}

	private function get_defaults( array $args ): array {
		return array_filter( array_map( static fn( array $arg ) => $arg['default'] ?? null, $args ), static fn( $value ) => null !== $value );
	}

	private function update_request( int $ticket_id, int $post_id, string $name ) {
		$endpoint = tribe( 'tickets.editor.rest-v1.endpoints.tickets-single' );
		$request  = new WP_REST_Request( 'PUT', '' );
		$request->set_url_params( [ 'id' => $ticket_id ] );
		$request->set_body_params(
			[
				'post_id'           => $post_id,
				'edit_ticket_nonce' => wp_create_nonce( 'edit_ticket_nonce' ),
				'name'              => $name,
				'price'             => $this->faker->numerify( '%#' ),
				'menu_order'        => 0,
				'ticket'            => [],
			]
		);
		$request->set_default_params( $this->get_defaults( $endpoint->ticket_args() ) );

		return $endpoint->update( $request );
	}

	private function create_request( string $provider, int $ticket_id, int $post_id, string $name ) {
		$endpoint = tribe( 'tickets.editor.rest-v1.endpoints.tickets-single' );
		$request  = new WP_REST_Request( 'POST', '' );
		$request->set_body_params(
			[
				'id'               => $ticket_id,
				'post_id'          => $post_id,
				'provider'         => $provider,
				'add_ticket_nonce' => wp_create_nonce( 'add_ticket_nonce' ),
				'name'             => $name,
				'price'            => $this->faker->numerify( '%#' ),
				'menu_order'       => 0,
				'ticket'           => [],
			]
		);
		$request->set_default_params( $this->get_defaults( $endpoint->ticket_args() ) );

		return $endpoint->create( $request );
	}
}
