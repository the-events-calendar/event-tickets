<?php

namespace Tribe\Tickets;

use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Tickets\Commerce\Module as Commerce;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as TC_Ticket_Maker;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Metabox as Metabox;
use WP_Error;

class Metabox_Ticket_Delete_Ownership_Test extends WPTestCase {
	use TC_Ticket_Maker;
	use PayPal_Ticket_Maker;

	/**
	 * @before
	 */
	public function enable_providers(): void {
		tribe_update_option( 'ticket-enabled-post-types', [ 'post' ] );
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', [ $this, 'add_modules' ] );
		tribe_singleton( 'tickets.data_api', new Data_API() );
	}

	/**
	 * @after
	 */
	public function reset_providers(): void {
		remove_filter( 'tribe_tickets_get_modules', [ $this, 'add_modules' ] );
		tribe_singleton( 'tickets.data_api', new Data_API() );
		unset( $_POST['post_id'], $_POST['ticket_id'], $_POST['nonce'] );
	}

	public function add_modules( array $modules ): array {
		$modules[ Commerce::class ] = 'Tickets Commerce';
		$modules[ PayPal::class ]   = tribe( 'tickets.commerce.paypal' )->plugin_name;

		return $modules;
	}

	public function provider_ticket_makers(): Generator {
		yield 'Tickets Commerce' => [ 'create_tc_ticket' ];
		yield 'PayPal' => [ 'create_paypal_ticket' ];
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
	 * @dataProvider provider_ticket_makers
	 */
	public function test_contributor_cannot_delete_ticket_of_another_post( string $make_ticket ): void {
		$victim_post = static::factory()->post->create();
		$ticket_id   = $this->{$make_ticket}( $victim_post, 10 );

		$result = $this->request_delete_as_contributor( $ticket_id );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertInstanceOf( \WP_Post::class, get_post( $ticket_id ), 'The ticket must still exist.' );
	}

	/**
	 * @dataProvider provider_ticket_makers
	 */
	public function test_ticket_is_deleted_through_its_own_post( string $make_ticket ): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->{$make_ticket}( $post_id, 10 );

		$_POST['post_id']   = $post_id;
		$_POST['ticket_id'] = $ticket_id;
		$_POST['nonce']     = wp_create_nonce( 'remove_ticket_nonce' );

		$result = tribe( Metabox::class )->ajax_ticket_delete( true );

		$this->assertIsArray( $result );
		$this->assertNotInstanceOf( \WP_Post::class, get_post( $ticket_id ), 'The ticket must be deleted.' );
	}
}
