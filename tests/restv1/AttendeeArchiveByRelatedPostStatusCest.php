<?php

namespace Tribe\Tickets\Test\REST\V1;

use Codeception\Example;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;

class AttendeeArchiveByRelatedPostStatusCest extends BaseRestCest {
	use Attendee_Maker;
	use Ticket_Maker;

	/**
	 * It should not return attendees of non-public posts to a visitor mixing public and non-public statuses.
	 *
	 * @test
	 * @dataProvider visitor_status_requests
	 * @covers Tribe__Tickets__Attendee_Repository::filter_by_event_status
	 */
	public function should_not_return_attendees_of_non_public_posts_to_visitor_mixing_statuses( Restv1Tester $I, Example $example ) {
		$attendees = $this->seed_public_and_private_attendees( $I );

		$I->sendGET( $this->attendees_url, [ 'post_status' => $example['post_status'] ] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'total' => 1 ] );
		$I->assertEquals( [ $attendees['public'] ], $I->grabDataFromResponseByJsonPath( '$.attendees[*].id' ) );
	}

	/**
	 * It should return no attendees to a visitor requesting only non-public statuses.
	 *
	 * @test
	 * @covers Tribe__Tickets__Attendee_Repository::filter_by_event_status
	 */
	public function should_return_no_attendees_to_visitor_requesting_only_non_public_statuses( Restv1Tester $I ) {
		$this->seed_public_and_private_attendees( $I );

		$I->sendGET( $this->attendees_url, [ 'post_status' => 'private,draft' ] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'total' => 0, 'attendees' => [] ] );
	}

	/**
	 * It should return the attendees of private posts only to users who can read private posts.
	 *
	 * @test
	 * @dataProvider roles_and_readable_attendees
	 * @covers Tribe__Tickets__Attendee_Repository::filter_by_event_status
	 */
	public function should_return_attendees_of_private_posts_only_to_users_who_can_read_them( Restv1Tester $I, Example $example ) {
		$attendees = $this->seed_public_and_private_attendees( $I );
		$expected  = array_values( array_intersect_key( $attendees, array_flip( $example['readable'] ) ) );

		$I->generate_nonce_for_role( $example['role'] );
		$I->sendGET( $this->attendees_url, [ 'post_status' => 'publish,private' ] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$ids = $I->grabDataFromResponseByJsonPath( '$.attendees[*].id' );
		sort( $ids );
		sort( $expected );
		$I->assertEquals( $expected, $ids );
	}

	/**
	 * It should not return attendees of private posts to an API key request mixing statuses.
	 *
	 * An API key grants manage access, but the request runs without a user, so it cannot read private posts:
	 * the same rule that maps its default `any` to `publish`.
	 *
	 * @test
	 * @covers Tribe__Tickets__Attendee_Repository::filter_by_event_status
	 */
	public function should_not_return_attendees_of_private_posts_to_api_key_request_mixing_statuses( Restv1Tester $I ) {
		$attendees = $this->seed_public_and_private_attendees( $I );
		$api_key   = 'svul-181-api-key';
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );

		$I->sendGET( $this->attendees_url, [ 'post_status' => 'publish,private', 'api_key' => $api_key ] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->assertEquals( [ $attendees['public'] ], $I->grabDataFromResponseByJsonPath( '$.attendees[*].id' ) );
	}

	/**
	 * Requests a visitor can make that mix public and non-public statuses.
	 *
	 * @return array<string,array{post_status:string|string[]}>
	 */
	protected function visitor_status_requests(): array {
		return [
			'publish,private'         => [ 'post_status' => 'publish,private' ],
			'private,publish'         => [ 'post_status' => 'private,publish' ],
			'with spaces'             => [ 'post_status' => 'publish, private' ],
			'array'                   => [ 'post_status' => [ 'publish', 'private' ] ],
			'every non-public status' => [ 'post_status' => 'publish,private,draft,pending,future' ],
			'any'                     => [ 'post_status' => 'any' ],
		];
	}

	/**
	 * Roles and the attendees they can read, by key of `seed_public_and_private_attendees()`.
	 *
	 * @return array<string,array{role:string,readable:string[]}>
	 */
	protected function roles_and_readable_attendees(): array {
		return [
			'subscriber'    => [ 'role' => 'subscriber', 'readable' => [ 'public' ] ],
			'author'        => [ 'role' => 'author', 'readable' => [ 'public' ] ],
			'editor'        => [ 'role' => 'editor', 'readable' => [ 'public', 'private' ] ],
			'administrator' => [ 'role' => 'administrator', 'readable' => [ 'public', 'private' ] ],
		];
	}

	/**
	 * Seeds a public and a private post, both showing their attendee list, with one RSVP attendee each.
	 *
	 * @return array{public:int,private:int} The attendee IDs.
	 */
	private function seed_public_and_private_attendees( Restv1Tester $I ): array {
		/* Despite its name, a truthy `_tribe_hide_attendees_list` means the attendee list is shown. */
		$shown_list = [ '_tribe_hide_attendees_list' => 1 ];
		$attendees  = [];

		foreach ( [ 'public' => 'publish', 'private' => 'private' ] as $key => $status ) {
			$post_id           = $I->havePostInDatabase( [ 'post_status' => $status, 'meta_input' => $shown_list ] );
			$attendees[ $key ] = $this->create_attendee_for_ticket( $this->create_rsvp_ticket( $post_id ), $post_id, [
				'rsvp_status' => 'yes',
				'optout'      => false,
			] );
		}

		return $attendees;
	}
}
