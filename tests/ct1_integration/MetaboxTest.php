<?php

namespace Tribe\Tickets;

use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use Tribe\Tickets\Test\Traits\CT1\CT1_Fixtures;
use Tribe__Tickets__Metabox;

class MetaboxTest extends \Codeception\TestCase\WPTestCase {
	use CT1_Fixtures;

	public function _setUp() {
		parent::_setUp();
		$this->enable_provisional_id_normalizer();
	}

	public function _tearDown() {
		parent::_tearDown();
		$this->disable_provisional_id_normalizer();
	}

	/**
	 * It should allow creating ticket on page using provisional ID.
	 *
	 * @test
	 */
	public function should_ajax_ticket_add_with_provisional_id() {

		$post       = $this->given_a_migrated_single_event();
		$post_id    = $post->ID;
		$occurrence = Occurrence::find_by_post_id( $post_id );
		add_filter( 'user_has_cap', function ( $allcaps, $caps ) {
			$caps['edit_event_tickets'] = true;

			return $caps;
		}, 10, 2 );
		// Create a provisional ID.
		$provisional_id = $occurrence->occurrence_id + $this->get_provisional_id_base();
		$nonce          = wp_create_nonce( 'add_ticket_nonce' );
		$_POST          = [
			'action'   => 'tribe-ticket-add',
			'data'     => 'ticket_name=test&ticket_description=&ticket_show_description=1&ticket_start_date=&ticket_start_time=&ticket_end_date=&ticket_end_time=&ticket_provider=Tribe__Tickets__RSVP&tribe-ticket%5Bcapacity%5D=&ticket_id=&ticket_menu_order=undefined',
			'post_id'  => $provisional_id,
			'nonce'    => $nonce,
			'is_admin' => 'true',
		];

		$metabox  = tribe( Tribe__Tickets__Metabox::class );
		$response = $metabox->ajax_ticket_add( true );

		// Should create ticket with the provisional ID provided.
		$this->assertIsArray( $response );
	}
}
