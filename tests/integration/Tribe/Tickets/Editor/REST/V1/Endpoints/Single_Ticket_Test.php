<?php

namespace Tribe\Tickets\Editor\REST\V1\Endpoints;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use WP_Error;

class Single_Ticket_Test extends WPTestCase {
	use Relative_Sale_Dates_Maker;
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

		$response = $this->send_block_editor_ticket_save( 'POST', '/tickets', $post_id, 'add_ticket_nonce' );

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

		$response = $this->send_block_editor_ticket_save( 'PUT', "/tickets/{$ticket_id}", $post_id, 'edit_ticket_nonce' );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'The ticket data is not valid.', $response->get_data()['message'] );
		$this->assertSame( $name, get_post( $ticket_id )->post_title );
	}
}
