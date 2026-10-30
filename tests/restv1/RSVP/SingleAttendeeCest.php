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
	 * It should return the attendee response.
	 *
	 * @test
	 */
	public function should_return_attendee_response( Restv1Tester $I ) {
		$post_id = $I->havePostInDatabase( [ 'post_content' => '[tribe_attendees_list]' ] );

		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendees = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$attendee_id     = $attendees[0]['attendee_id'];
		$ticket_rest_url = $this->attendees_url . "/{$attendee_id}";

		$I->sendGET( $ticket_rest_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should block unauthenticated access to an attendee on a private event.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::get
	 */
	public function should_block_unauthenticated_access_to_attendee_on_private_event( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase( [ 'post_status' => 'private' ] );
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseCodeIs( 401 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'code' => 'attendee-not-accessible' ] );
	}

	/**
	 * It should block unauthenticated access to an attendee on a password-protected event.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::get
	 */
	public function should_block_unauthenticated_access_to_attendee_on_password_protected_event( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase( [ 'post_password' => 'topsecret' ] );
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseCodeIs( 401 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'code' => 'attendee-not-accessible' ] );
	}

	/**
	 * It should allow unauthenticated access to an attendee on a password-protected event when the correct password is supplied.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::get
	 */
	public function should_allow_unauthenticated_access_to_attendee_on_password_protected_event_with_correct_password( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase(
			[
				'post_password' => 'secret',
				'meta_input'    => [ '_tribe_hide_attendees_list' => 1 ],
			]
		);
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		// Simulate the cookie WordPress sets after the correct password is submitted.
		// Post passwords use phpass ($P$B) via post_password_required(), independent of
		// wp_hash_password() which switched to bcrypt in WP 6.8. Mirror WP's postpass handler exactly.
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$hasher = new \PasswordHash( 8, true );
		$I->setCookie( 'wp-postpass_' . COOKIEHASH, $hasher->HashPassword( 'secret' ) );
		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should block subscriber access to an attendee on a private event.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::get
	 */
	public function should_block_subscriber_access_to_attendee_on_private_event( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase( [ 'post_status' => 'private' ] );
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$I->generate_nonce_for_role( 'subscriber' );
		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseCodeIs( 401 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'code' => 'attendee-not-accessible' ] );
	}

	/**
	 * It should allow an administrator to access an attendee on a private event.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::get
	 */
	public function should_allow_administrator_to_access_attendee_on_private_event( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase( [ 'post_status' => 'private' ] );
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$I->generate_nonce_for_role( 'administrator' );
		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should allow unauthenticated access to an attendee on a public event.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::get
	 */
	public function should_allow_unauthenticated_access_to_attendee_on_public_event( Restv1Tester $I ) {
		/* Despite its name, a truthy `_tribe_hide_attendees_list` means the attendee list is shown. */
		$post_id     = $I->havePostInDatabase(
			[
				'post_status' => 'publish',
				'meta_input'  => [ '_tribe_hide_attendees_list' => 1 ],
			]
		);
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should block unauthenticated access to an attendee on a public event that does not show the attendee list.
	 *
	 * @test
	 * @dataProvider event_not_showing_attendee_list_meta
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::get
	 */
	public function should_block_unauthenticated_access_to_attendee_on_event_not_showing_attendee_list( Restv1Tester $I, Example $example ) {
		$post_id     = $I->havePostInDatabase( array_merge( [ 'post_status' => 'publish' ], $example['post_args'] ) );
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseCodeIs( 401 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'code' => 'attendee-not-accessible' ] );
	}

	/**
	 * It should block unauthenticated access to an attendee of a non-public post even when the post shows its attendee list.
	 *
	 * @test
	 * @dataProvider non_public_post_statuses
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::get
	 */
	public function should_block_unauthenticated_access_to_attendee_of_non_public_post_showing_attendee_list( Restv1Tester $I, Example $example ) {
		$post_id     = $I->havePostInDatabase(
			[
				'post_status' => $example['post_status'],
				'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '+1 week' ) ),
				'meta_input'  => [ '_tribe_hide_attendees_list' => 1 ],
			]
		);
		$attendee_id = $this->create_attendee_for_ticket( $this->create_rsvp_ticket( $post_id ), $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseCodeIs( 401 );
		$I->seeResponseContainsJson( [ 'code' => 'attendee-not-accessible' ] );
	}

	/**
	 * It should let the `tribe_tickets_hide_attendees_list` filter decide the attendee list visibility.
	 *
	 * @test
	 * @dataProvider attendee_list_filter_overrides
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::get
	 */
	public function should_let_the_attendee_list_filter_decide_the_visibility( Restv1Tester $I, Example $example ) {
		$I->haveMuPlugin(
			'svul-180-hide-attendees-list-filter.php',
			"add_filter( 'tribe_tickets_hide_attendees_list', '__return_{$example['filter_returns']}' );"
		);
		$post_id     = $I->havePostInDatabase( array_merge( [ 'post_status' => 'publish' ], $example['post_args'] ) );
		$attendee_id = $this->create_attendee_for_ticket( $this->create_rsvp_ticket( $post_id ), $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseCodeIs( $example['status'] );
	}

	/**
	 * It should let an API key request read an attendee of a post that does not show its attendee list.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::get
	 */
	public function should_let_api_key_request_read_attendee_of_post_not_showing_attendee_list( Restv1Tester $I ) {
		$api_key = 'svul-180-api-key';
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );
		$post_id     = $I->havePostInDatabase( [ 'post_status' => 'publish' ] );
		$attendee_id = $this->create_attendee_for_ticket( $this->create_rsvp_ticket( $post_id ), $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$I->sendGET( $this->attendees_url . "/{$attendee_id}", [ 'api_key' => $api_key ] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [ 'id' => $attendee_id ] );
	}

	/**
	 * It should block unauthenticated access to an attendee whose post was deleted, and to an ID that is not an attendee.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::get
	 */
	public function should_block_unauthenticated_access_to_orphaned_attendee_and_non_attendee( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase( [ 'post_status' => 'publish', 'meta_input' => [ '_tribe_hide_attendees_list' => 1 ] ] );
		$attendee_id = $this->create_attendee_for_ticket( $this->create_rsvp_ticket( $post_id ), $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );
		$I->dontHavePostInDatabase( [ 'ID' => $post_id ] );
		$non_attendee_id = $I->havePostInDatabase( [ 'post_status' => 'publish', 'meta_input' => [ '_tribe_hide_attendees_list' => 1 ] ] );

		foreach ( [ $attendee_id, $non_attendee_id ] as $id ) {
			$I->sendGET( $this->attendees_url . "/{$id}" );
			$I->seeResponseCodeIs( 401 );
			$I->seeResponseContainsJson( [ 'code' => 'attendee-not-accessible' ] );
		}
	}

	/**
	 * It should block unauthenticated access to an attendee the public list leaves out, on a post that shows its list.
	 *
	 * @test
	 * @dataProvider attendees_left_out_of_the_public_list
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::get
	 */
	public function should_block_unauthenticated_access_to_attendee_left_out_of_the_public_list( Restv1Tester $I, Example $example ) {
		$post_id     = $I->havePostInDatabase( [ 'post_status' => 'publish', 'meta_input' => [ '_tribe_hide_attendees_list' => 1 ] ] );
		$attendee_id = $this->create_attendee_for_ticket( $this->create_rsvp_ticket( $post_id ), $post_id, $example['attendee'] );

		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseCodeIs( 401 );
		$I->seeResponseContainsJson( [ 'code' => 'attendee-not-accessible' ] );
	}

	/**
	 * It should block unauthenticated access to an attendee of a post showing the list only through the shortcode.
	 *
	 * The stored setting decides, as for the attendee archive: the classic ticket settings checkbox, or a save with
	 * blocks enabled, stores it.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::get
	 */
	public function should_block_unauthenticated_access_to_attendee_of_post_with_only_the_shortcode( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase( [ 'post_status' => 'publish', 'post_content' => '[tribe_attendees_list]' ] );
		$attendee_id = $this->create_attendee_for_ticket( $this->create_rsvp_ticket( $post_id ), $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$I->sendGET( $this->attendees_url . "/{$attendee_id}" );

		$I->seeResponseCodeIs( 401 );
		$I->seeResponseContainsJson( [ 'code' => 'attendee-not-accessible' ] );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::validate_check_in
	 */
	public function test_should_not_allow_check_in_for_non_complete_order_status( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase( [ 'post_content' => '[tribe_attendees_list]' ] );
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'no',
			'optout'      => false,
		] );

		$I->generate_nonce_for_role( 'administrator' );
		$ticket_rest_url = $this->attendees_url . "/{$attendee_id}";

		$I->sendPost( $ticket_rest_url, [ 'check_in' => 1 ] );
		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();

		$expected_json = [
			'code'    => 'tec-et-attendee-invalid-check-in',
			'message' => 'Attendee Order status is not authorized for check-in.',
		];
		$I->seeResponseContainsJson( $expected_json );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::validate_check_in
	 */
	public function test_should_allow_check_in_for_complete_order_status( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$I->generate_nonce_for_role( 'administrator' );
		$ticket_rest_url = $this->attendees_url . "/{$attendee_id}";

		$I->sendPost( $ticket_rest_url, [ 'check_in' => 1 ] );
		$I->seeResponseCodeIs( 201 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'checked_in' => true ] );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::validate_check_in
	 */
	public function should_not_allow_check_in_for_already_checked_in_attendee( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$I->generate_nonce_for_role( 'administrator' );
		$ticket_rest_url = $this->attendees_url . "/{$attendee_id}";

		$I->sendPost( $ticket_rest_url, [ 'check_in' => 1 ] );
		$I->seeResponseCodeIs( 201 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'checked_in' => true ] );

		// Try to check in again.
		$I->sendPost( $ticket_rest_url, [ 'check_in' => 1 ] );
		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();

		$expected_json = [
			'code'    => 'tec-et-attendee-already-checked-in',
			'message' => 'Attendee is already checked in.',
		];
		$I->seeResponseContainsJson( $expected_json );
	}

	/**
	 * Attendee list meta states that do not show the attendee list publicly.
	 *
	 * @return array<string,array{post_args:array{meta_input?:array<string,int>}}>
	 */
	protected function event_not_showing_attendee_list_meta(): array {
		return [
			'meta not set'       => [ 'post_args' => [] ],
			'list hidden (0)'    => [ 'post_args' => [ 'meta_input' => [ '_tribe_hide_attendees_list' => 0 ] ] ],
			/* The classic ticket settings save stores an unchecked box as boolean false, which WordPress writes as ''. */
			'list hidden (\'\')' => [ 'post_args' => [ 'meta_input' => [ '_tribe_hide_attendees_list' => '' ] ] ],
		];
	}

	/**
	 * Attendees the public attendee list leaves out.
	 *
	 * @return array<string,array{attendee:array{rsvp_status:string,optout:bool}}>
	 */
	protected function attendees_left_out_of_the_public_list(): array {
		return [
			'opted out'     => [ 'attendee' => [ 'rsvp_status' => 'yes', 'optout' => true ] ],
			'not attending' => [ 'attendee' => [ 'rsvp_status' => 'no', 'optout' => false ] ],
		];
	}

	/**
	 * Post statuses an anonymous visitor cannot read.
	 *
	 * @return array<string,array{post_status:string}>
	 */
	protected function non_public_post_statuses(): array {
		return [
			'draft'   => [ 'post_status' => 'draft' ],
			'pending' => [ 'post_status' => 'pending' ],
			'future'  => [ 'post_status' => 'future' ],
			'private' => [ 'post_status' => 'private' ],
		];
	}

	/**
	 * Filter overrides of the stored attendee list setting, and the response they lead to.
	 *
	 * @return array<string,array{filter_returns:string,post_args:array{meta_input?:array<string,int>},status:int}>
	 */
	protected function attendee_list_filter_overrides(): array {
		return [
			'filter hides a shown list'     => [
				'filter_returns' => 'true',
				'post_args'      => [ 'meta_input' => [ '_tribe_hide_attendees_list' => 1 ] ],
				'status'         => 401,
			],
			'filter shows a list never set' => [
				'filter_returns' => 'false',
				'post_args'      => [],
				'status'         => 200,
			],
		];
	}
}
