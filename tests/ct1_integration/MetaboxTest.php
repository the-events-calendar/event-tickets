<?php

namespace Tribe\Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use Tribe\Events\Test\Traits\CT1\CT1_Fixtures;
use Tribe__Tickets__Metabox;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use WP_Post;

class MetaboxTest extends WPTestCase {
	use CT1_Fixtures;
	use Ticket_Maker;

	public function _setUp() {
		parent::_setUp();
		$user = static::factory()->user->create(['role' => 'administrator']);
		wp_set_current_user( $user );
	}

	public function given_an_event_with_ticket_request( $ticket_request, $nonce_action ) {
		$post       = $this->given_a_migrated_single_event();
		$post_id    = $post->ID;
		$occurrence = Occurrence::find_by_post_id( $post_id );
		add_filter( 'user_has_cap', function ( $allcaps, $caps ) {
			$caps['edit_event_tickets'] = true;
			$caps['edit_others_posts']  = true;

			return $caps;
		}, 10, 2 );
		// Create a provisional ID.
		$provisional_id = $occurrence->provisional_id;
		$nonce          = wp_create_nonce( $nonce_action );
		$_POST          = array_merge(
			[ 'post_id' => $provisional_id, 'nonce' => $nonce ],
			$ticket_request
		);

		$this->assertInstanceOf( WP_Post::class, get_post( $provisional_id ) );

		return $post_id;
	}

	/**
	 * It should allow creating ticket on page using provisional ID.
	 *
	 * @test
	 */
	public function should_ajax_ticket_add_with_provisional_id() {
		$this->given_an_event_with_ticket_request( [
			'is_admin' => 'true',
			'action'   => 'tribe-ticket-add',
			'data'     => 'ticket_name=test&ticket_description=&ticket_show_description=1&ticket_start_date=&ticket_start_time=&ticket_end_date=&ticket_end_time=&ticket_provider=Tribe__Tickets__RSVP&tribe-ticket%5Bcapacity%5D=&ticket_id=&ticket_menu_order=undefined',
		], 'add_ticket_nonce' );


		$metabox  = tribe( Tribe__Tickets__Metabox::class );
		$response = $metabox->ajax_ticket_add( true );

		// Should create ticket with the provisional ID provided.
		$this->assertIsArray( $response );
	}

	/**
	 * Should successfully locate and edit a ticket.
	 *
	 * @test
	 */
	public function should_ajax_ticket_edit_with_provisional_id() {
		$post_id            = $this->given_an_event_with_ticket_request( [
			'is_admin' => 'true',
			'action'   => 'tribe-ticket-edit',
			'data'     => 'ticket_name=test&ticket_description=&ticket_show_description=1&ticket_start_date=&ticket_start_time=&ticket_end_date=&ticket_end_time=&ticket_provider=Tribe__Tickets__RSVP&tribe-ticket%5Bcapacity%5D=12&ticket_id=&ticket_menu_order=undefined',
		], 'edit_ticket_nonce' );
		$ticket_a_id        = $this->create_rsvp_ticket( $post_id );
		$_POST['ticket_id'] = $ticket_a_id;

		$metabox  = tribe( Tribe__Tickets__Metabox::class );
		$response = $metabox->ajax_ticket_edit( true );

		// Should create ticket with the provisional ID provided.
		$this->assertIsArray( $response );
	}

	/**
	 * Should successfully locate and remove a ticket.
	 *
	 * @test
	 */
	public function should_ajax_ticket_delete_with_provisional_id() {
		$post_id            = $this->given_an_event_with_ticket_request( [
			'is_admin' => 'true',
			'action'   => 'tribe-ticket-delete',
			'data'     => 'ticket_name=test&ticket_description=&ticket_show_description=1&ticket_start_date=&ticket_start_time=&ticket_end_date=&ticket_end_time=&ticket_provider=Tribe__Tickets__RSVP&tribe-ticket%5Bcapacity%5D=12&ticket_id=&ticket_menu_order=undefined',
		], 'remove_ticket_nonce' );
		$ticket_a_id        = $this->create_rsvp_ticket( $post_id );
		$_POST['ticket_id'] = $ticket_a_id;

		$metabox  = tribe( Tribe__Tickets__Metabox::class );
		$response = $metabox->ajax_ticket_delete( true );

		// Should create ticket with the provisional ID provided.
		$this->assertIsArray( $response );
	}

	/**
	 * Should successfully locate and duplicate a ticket.
	 *
	 * @test
	 */
	public function should_ajax_ticket_duplicate_with_provisional_id() {
		$post_id            = $this->given_an_event_with_ticket_request( [
			'is_admin' => 'true',
			'action'   => 'tribe-ticket-duplicate',
			'data'     => 'ticket_name=test&ticket_description=&ticket_show_description=1&ticket_start_date=&ticket_start_time=&ticket_end_date=&ticket_end_time=&ticket_provider=Tribe__Tickets__RSVP&tribe-ticket%5Bcapacity%5D=12&ticket_id=&ticket_menu_order=undefined',
		], 'duplicate_ticket_nonce' );
		$ticket_a_id        = $this->create_rsvp_ticket( $post_id );
		$_POST['ticket_id'] = $ticket_a_id;

		$metabox  = tribe( Tribe__Tickets__Metabox::class );
		$response = $metabox->ajax_ticket_duplicate( true );

		// Should create ticket with the provisional ID provided.
		$this->assertIsArray( $response );
	}
}
