<?php

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class SingleTicketBaseCest extends BaseRestCest {
	use RSVP_Ticket_Maker;

	/**
	 * It should allow getting a ticket information by ticket post ID
	 *
	 * @test
	 */
	public function should_allow_getting_a_ticket_information_by_ticket_post_id( Restv1Tester $I ) {
		$post_id         = $I->havePostInDatabase();
		$ticket_id       = $this->make_RSVP_ticket( $post_id, [
			'meta_input' => [
				'total_sales' => 23,
				'_stock'      => 30,
				'_capacity'   => 30,
			]
		] );
		$ticket_post     = get_post( $ticket_id );
		$ticket_rest_url = $this->tickets_url . "/{$ticket_id}";
		/** @var Tribe__Tickets__Tickets_Handler $handler */
		$handler  = tribe( 'tickets.handler' );
		$image_id = $I->factory()->attachment->create_upload_object( codecept_data_dir( 'images/test-image-1.jpg' ) );
		update_post_meta( $post_id, $handler->key_image_header, $image_id );

		/** @var Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );

		$I->sendGET( $ticket_rest_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$expectedJson = array(
			'id'                      => $ticket_id,
			'post_id'                 => $post_id,
			'global_id'               => $repository->get_ticket_global_id( $ticket_id ),
			'global_id_lineage'       => $repository->get_ticket_global_id_lineage( $ticket_id ),
			'author'                  => $ticket_post->post_author,
			'status'                  => $ticket_post->post_status,
			'date'                    => $ticket_post->post_date,
			'date_utc'                => $ticket_post->post_date_gmt,
			'modified'                => $ticket_post->post_modified,
			'modified_utc'            => $ticket_post->post_modified_gmt,
			'rest_url'                => $ticket_rest_url,
			'provider'                => 'rsvp',
			'title'                   => $ticket_post->post_title,
			'description'             => $ticket_post->post_content,
			'image'                   => $repository->get_ticket_header_image( $ticket_id ),
			'available_from'          => $repository->get_ticket_start_date( $ticket_id ),
			'available_from_details'  => $repository->get_ticket_start_date( $ticket_id, true ),
			'available_until'         => $repository->get_ticket_end_date( $ticket_id ),
			'available_until_details' => $repository->get_ticket_end_date( $ticket_id, true ),
			'capacity'                => 30,
			'capacity_details'        => [
				'available_percentage' => floor( ( 7 / 30 ) * 100 ),
				'max'                  => 30,
				'available'            => 7,
				'sold'                 => 23,
				'pending'              => 0,
			],
			'is_available'            => true,
			'cost' => $repository->get_ticket_cost($ticket_id),
			'cost_details' => $repository->get_ticket_cost($ticket_id, true),
		);
		$I->seeResponseContainsJson( $expectedJson );
	}

}
