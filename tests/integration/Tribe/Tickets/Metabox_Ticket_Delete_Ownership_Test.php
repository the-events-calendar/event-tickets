<?php

namespace Tribe\Tickets;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Traits\With_Ticket_Providers;
use Tribe__Tickets__Metabox as Metabox;
use WP_Error;

class Metabox_Ticket_Delete_Ownership_Test extends WPTestCase {
	use With_Ticket_Providers;

	/**
	 * @after
	 */
	public function reset_request(): void {
		unset( $_POST['post_id'], $_POST['ticket_id'], $_POST['nonce'] );
	}

	private function request_delete_as_contributor( int $ticket_id, ?int $post_id = null ) {
		$contributor = static::factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $contributor );

		$_POST['post_id']   = $post_id ?? static::factory()->post->create(
			[
				'post_author' => $contributor,
				'post_status' => 'draft',
			]
		);
		$_POST['ticket_id'] = $ticket_id;
		$_POST['nonce']     = wp_create_nonce( 'remove_ticket_nonce' );

		return tribe( Metabox::class )->ajax_ticket_delete( true );
	}

	/**
	 * @dataProvider provider_paid_ticket_makers
	 */
	public function test_contributor_cannot_delete_ticket_of_another_post( string $provider, string $make_ticket ): void {
		$victim_post = static::factory()->post->create();
		$ticket_id   = $this->{$make_ticket}( $victim_post, 10 );

		$result = $this->request_delete_as_contributor( $ticket_id );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertInstanceOf( \WP_Post::class, get_post( $ticket_id ), 'The ticket must still exist.' );
	}

	/**
	 * @dataProvider provider_paid_ticket_makers
	 */
	public function test_ticket_is_deleted_through_its_own_post_whatever_post_id_is_sent( string $provider, string $make_ticket ): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->{$make_ticket}( $post_id, 10 );

		$_POST['post_id']   = static::factory()->post->create();
		$_POST['ticket_id'] = $ticket_id;
		$_POST['nonce']     = wp_create_nonce( 'remove_ticket_nonce' );

		$result = tribe( Metabox::class )->ajax_ticket_delete( true );

		$this->assertIsArray( $result );
		$this->assertNotInstanceOf( \WP_Post::class, get_post( $ticket_id ), 'The ticket must be deleted.' );
	}

	/**
	 * @dataProvider provider_paid_ticket_makers
	 */
	public function test_ticket_without_a_parent_post_is_not_deleted( string $provider, string $make_ticket ): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->{$make_ticket}( $post_id, 10 );
		delete_post_meta( $ticket_id, tribe_tickets_get_ticket_provider( $ticket_id )->get_event_key() );

		$_POST['post_id']   = $post_id;
		$_POST['ticket_id'] = $ticket_id;
		$_POST['nonce']     = wp_create_nonce( 'remove_ticket_nonce' );

		$result = tribe( Metabox::class )->ajax_ticket_delete( true );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertInstanceOf( \WP_Post::class, get_post( $ticket_id ), 'The ticket must still exist.' );
	}
}
