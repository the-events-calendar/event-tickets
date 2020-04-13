<?php

namespace Tribe\Tickets\Test\REST\V1\RSVP;

use Codeception\Example;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;

class SingleAttendeeCest extends BaseRestCest {

	use Attendee_Maker;
	use Ticket_Maker;

	/**
	 * It should return an error if ET+ is not loaded.
	 *
	 * @test
	 */
	public function should_return_error_if_etplus_not_loaded( Restv1Tester $I ) {
		$post_id = $I->havePostInDatabase( [ 'post_content' => '[tribe_attendees_list]' ] );

		$I->havePostmetaInDatabase( $post_id, '_tribe_hide_attendees_list', '1' );

		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$ticket_rest_url = $this->attendees_url . "/{$ticket_id}";

		$I->sendGET( $ticket_rest_url );

		$I->seeResponseCodeIs( 401 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should allow getting a single attendee information
	 *
	 * @test
	 */
	public function should_allow_getting_a_single_attendee_information_by_post_id( \Restv1Tester $I ) {
		$post_id               = $I->havePostInDatabase();
		$ticket_id             = $this->create_rsvp_ticket( $post_id );
		$attendee_checkin_date = '2018-01-02 09:00:16';
		$attendee_checkin_time = strtotime( $attendee_checkin_date );
		$attendee_id           = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status'     => 'yes',
			'optout'          => false,
			'checkin'         => true,
			'checkin_details' => [
				'date'   => $attendee_checkin_date,
				'source' => 'bar',
				'author' => 'John Doe',
			],
		] );
		$attendee_post         = get_post( $attendee_id );
		$attendees_objects     = tribe_tickets_get_ticket_provider( $ticket_id )->get_attendees_by_id( $ticket_id );
		$attendee_object       = $attendees_objects[0];
		/** @var \Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );

		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse(), true );
		$I->assertEquals( [
			'id'                => $attendee_id,
			'post_id'           => $post_id,
			'ticket_id'         => $ticket_id,
			'global_id'         => $repository->get_attendee_global_id( $attendee_id ),
			'global_id_lineage' => $repository->get_attendee_global_id_lineage( $attendee_id ),
			'author'            => $attendee_post->post_author,
			'status'            => $attendee_post->post_status,
			'date'              => $attendee_post->post_date,
			'date_utc'          => $attendee_post->post_date_gmt,
			'modified'          => $attendee_post->post_modified,
			'modified_utc'      => $attendee_post->post_modified_gmt,
			'rest_url'          => $this->attendees_url . '/' . $attendee_id,
			'title'             => $attendee_object['holder_name'],
			'optout'            => false,
		], $response );
	}

	protected function rsvp_status_and_optout() {
		return [
			'going-opt-in'      => [ 'rsvp_status' => 'yes', 'optout' => false ],
			'going-opt-out'     => [ 'rsvp_status' => 'yes', 'optout' => 'yes' ],
			'not-going-opt-out' => [ 'rsvp_status' => 'no', 'optout' => 'yes' ],
			'not-going-opt-in'  => [ 'rsvp_status' => 'no', 'optout' => false ],
		];
	}

	/**
	 * It should show all attendee fields to user that can read private posts
	 *
	 * @dataProvider rsvp_status_and_optout
	 *
	 * @test
	 */
	public function should_show_all_attendee_fields_to_user_that_can_read_private_posts( \Restv1Tester $I, Example $example ) {
		$I->generate_nonce_for_role( 'editor' );

		$post_id               = $I->havePostInDatabase();
		$ticket_id             = $this->create_rsvp_ticket( $post_id );
		$attendee_checkin_date = '2018-01-02 09:00:16';
		$attendee_checkin_time = strtotime( $attendee_checkin_date );
		$attendee_id           = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status'     => $example['rsvp_status'],
			'optout'          => $example['optout'],
			'checkin'         => true,
			'checkin_details' => [
				'date'   => $attendee_checkin_date,
				'source' => 'bar',
				'author' => 'John Doe',
			],
		] );
		$attendee_post         = get_post( $attendee_id );
		$attendees_objects     = tribe_tickets_get_ticket_provider( $ticket_id )->get_attendees_by_id( $ticket_id );
		$attendee_object       = $attendees_objects[0];
		/** @var \Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );

		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse(), true );
		$I->assertEquals( [
			'id'                => $attendee_id,
			'post_id'           => $post_id,
			'ticket_id'         => $ticket_id,
			'global_id'         => $repository->get_attendee_global_id( $attendee_id ),
			'global_id_lineage' => $repository->get_attendee_global_id_lineage( $attendee_id ),
			'author'            => $attendee_post->post_author,
			'status'            => $attendee_post->post_status,
			'date'              => $attendee_post->post_date,
			'date_utc'          => $attendee_post->post_date_gmt,
			'modified'          => $attendee_post->post_modified,
			'modified_utc'      => $attendee_post->post_modified_gmt,
			'rest_url'          => $this->attendees_url . '/' . $attendee_id,
			'provider'          => 'rsvp',
			'order'             => $attendee_id, // they are the same!
			'sku'               => '',
			'title'             => $attendee_object['holder_name'],
			'email'             => $attendee_object['holder_email'],
			'checked_in'        => (bool) $attendee_object['check_in'],
			'checkin_details'   => [
				'date'         => $attendee_checkin_date,
				'date_details' => [
					'year'    => date( 'Y', $attendee_checkin_time ),
					'month'   => date( 'm', $attendee_checkin_time ),
					'day'     => date( 'd', $attendee_checkin_time ),
					'hour'    => date( 'H', $attendee_checkin_time ),
					'minutes' => date( 'i', $attendee_checkin_time ),
					'seconds' => date( 's', $attendee_checkin_time ),
				],
				'source'       => 'bar',
				'author'       => 'John Doe',
			],
			'rsvp_going'        => tribe_is_truthy( $example['rsvp_status'] ),
			'optout'            => tribe_is_truthy( $example['optout'] ),
		], $response );
	}

	/**
	 * It should return 400 when trying to get a single attendee by non-existing post ID
	 *
	 * @test
	 */
	public function should_return_404_when_trying_to_get_a_single_attendee_by_non_existing_post_id( \Restv1Tester $I ) {
		$I->sendGET( $this->attendees_url . "/1234" );

		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 404 );
	}

	/**
	 * It should return 401 when trying to get unpublished attendee
	 *
	 * @test
	 *
	 * @dataProvider rsvp_status_and_optout
	 */
	public function should_return_401_when_trying_to_get_unpublished_attendee( \Restv1Tester $I, Example $example ) {
		$post_id     = $I->havePostInDatabase();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'post_status' => 'private',
			'rsvp_status' => $example['rsvp_status'],
			'optout'      => $example['optout'],
		] );

		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 401 );
	}

	/**
	 * It should allow users that can read private posts to access unpublished attendees
	 *
	 * @test
	 *
	 * @dataProvider rsvp_status_and_optout
	 */
	public function should_allow_users_that_can_read_private_posts_to_access_unpublished_attendees( \Restv1Tester $I, Example $example ) {
		$I->generate_nonce_for_role( 'editor' );

		$post_id               = $I->havePostInDatabase();
		$ticket_id             = $this->create_rsvp_ticket( $post_id );
		$attendee_checkin_date = '2018-01-02 09:00:16';
		$attendee_checkin_time = strtotime( $attendee_checkin_date );
		$attendee_id           = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'post_status'     => 'private',
			'rsvp_status'     => $example['rsvp_status'],
			'optout'          => $example['optout'],
			'checkin'         => true,
			'checkin_details' => [
				'date'   => $attendee_checkin_date,
				'source' => 'bar',
				'author' => 'John Doe',
			],
		] );
		$attendee_post         = get_post( $attendee_id );
		$provider              = tribe_tickets_get_ticket_provider( $ticket_id );
		/** @var \Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );

		$attendees_objects = $provider->get_all_attendees_by_attendee_id( $attendee_id );
		$attendee_object   = $attendees_objects[0];

		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$response = json_decode( $I->grabResponse(), true );
		$I->assertEquals( [
			'id'                => $attendee_id,
			'post_id'           => $post_id,
			'ticket_id'         => $ticket_id,
			'global_id'         => $repository->get_attendee_global_id( $attendee_id ),
			'global_id_lineage' => $repository->get_attendee_global_id_lineage( $attendee_id ),
			'author'            => $attendee_post->post_author,
			'status'            => $attendee_post->post_status,
			'date'              => $attendee_post->post_date,
			'date_utc'          => $attendee_post->post_date_gmt,
			'modified'          => $attendee_post->post_modified,
			'modified_utc'      => $attendee_post->post_modified_gmt,
			'rest_url'          => $this->attendees_url . '/' . $attendee_id,
			'provider'          => 'rsvp',
			'order'             => $attendee_id, // they are the same!
			'sku'               => '',
			'title'             => $attendee_object['holder_name'],
			'email'             => $attendee_object['holder_email'],
			'checked_in'        => (bool) $attendee_object['check_in'],
			'checkin_details'   => [
				'date'         => $attendee_checkin_date,
				'date_details' => [
					'year'    => date( 'Y', $attendee_checkin_time ),
					'month'   => date( 'm', $attendee_checkin_time ),
					'day'     => date( 'd', $attendee_checkin_time ),
					'hour'    => date( 'H', $attendee_checkin_time ),
					'minutes' => date( 'i', $attendee_checkin_time ),
					'seconds' => date( 's', $attendee_checkin_time ),
				],
				'source'       => 'bar',
				'author'       => 'John Doe',
			],
			'rsvp_going'        => tribe_is_truthy( $example['rsvp_status'] ),
			'optout'            => tribe_is_truthy( $example['optout'] ),
		], $response );
	}

	/**
	 * It should return 401 when trying to get not going attendee
	 *
	 * @test
	 */
	public function should_return_401_when_trying_to_get_not_going_attendee( \Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'no',
			'optout'      => false,
		] );

		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 401 );
	}
}
