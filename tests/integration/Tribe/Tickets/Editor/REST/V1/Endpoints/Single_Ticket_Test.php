<?php

namespace Tribe\Tickets\Editor\REST\V1\Endpoints;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Single_Ticket_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * @before
	 */
	public function reject_all_ticket_data(): void {
		add_filter(
			'tec_tickets_ticket_data_validation',
			static fn() => new WP_Error( 'tec_tests_invalid_ticket_data', 'The ticket data is not valid.', [ 'status' => 400 ] )
		);
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * @test
	 */
	public function should_not_create_a_ticket_whose_data_fails_validation(): void {
		$post_id = static::factory()->post->create();

		$response = $this->send( 'POST', '/tickets', $post_id, 'add_ticket_nonce' );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'The ticket data is not valid.', $response->get_data()['message'] );
		$this->assertSame( [], tribe( Module::class )->get_tickets_ids( $post_id ) );
	}

	/**
	 * @test
	 */
	public function should_not_change_a_ticket_whose_update_fails_validation(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id );
		$name      = get_post( $ticket_id )->post_title;

		$response = $this->send( 'PUT', "/tickets/{$ticket_id}", $post_id, 'edit_ticket_nonce' );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'The ticket data is not valid.', $response->get_data()['message'] );
		$this->assertSame( $name, get_post( $ticket_id )->post_title );
	}

	/**
	 * Sends a ticket save the way the block editor does.
	 *
	 * @param string $method       The HTTP method.
	 * @param string $route        The route, relative to the tickets namespace.
	 * @param int    $post_id      The ticketed post ID.
	 * @param string $nonce_action The nonce action the endpoint checks.
	 *
	 * @return WP_REST_Response The response.
	 */
	private function send( string $method, string $route, int $post_id, string $nonce_action ): WP_REST_Response {
		$request = new WP_REST_Request( $method, '/' . tribe( 'tickets.rest-v1.main' )->get_events_route_namespace() . $route );
		$request->set_body_params(
			[
				'post_id'          => $post_id,
				$nonce_action      => wp_create_nonce( $nonce_action ),
				'provider'         => Module::class,
				'name'             => 'Block editor ticket',
				'description'      => '',
				'price'            => '10',
				'show_description' => 'yes',
				'start_date'       => '',
				'start_time'       => '',
				'end_date'         => '',
				'end_time'         => '',
				'sku'              => '',
				'iac'              => 'none',
				'menu_order'       => 0,
				'ticket'           => [
					'mode'     => 'own',
					'capacity' => 50,
				],
			]
		);

		return rest_do_request( $request );
	}
}
