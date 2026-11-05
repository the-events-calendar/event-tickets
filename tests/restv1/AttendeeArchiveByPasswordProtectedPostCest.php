<?php

namespace Tribe\Tickets\Test\REST\V1;

use Codeception\Example;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;

class AttendeeArchiveByPasswordProtectedPostCest extends BaseRestCest {
	use Attendee_Maker;
	use Ticket_Maker;

	/**
	 * It should not return attendees of password-protected posts to a visitor.
	 *
	 * @test
	 * @dataProvider visitor_requests
	 * @covers Tribe__Tickets__REST__V1__Attendee_Repository::build_query
	 */
	public function should_not_return_attendees_of_password_protected_posts_to_visitor( Restv1Tester $I, Example $example ) {
		$attendees = $this->seed_public_and_protected_attendees( $I );
		$params    = $example['include_post'] ? [ 'include_post' => $attendees['protected_post'] ] : [];
		$expected  = $example['include_post'] ? [] : [ $attendees['public'] ];

		$I->sendGET( $this->attendees_url, $params );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'total' => count( $expected ) ] );
		$I->assertEquals( $expected, $I->grabDataFromResponseByJsonPath( '$.attendees[*].id' ) );
	}

	/**
	 * It should not return attendees of password-protected posts to a logged-in user who cannot manage attendees.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Attendee_Repository::build_query
	 */
	public function should_not_return_attendees_of_password_protected_posts_to_subscriber( Restv1Tester $I ) {
		$attendees = $this->seed_public_and_protected_attendees( $I );

		$I->generate_nonce_for_role( 'subscriber' );
		$I->sendGET( $this->attendees_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->assertEquals( [ $attendees['public'] ], $I->grabDataFromResponseByJsonPath( '$.attendees[*].id' ) );
	}

	/**
	 * It should return attendees of password-protected posts to a user who can manage attendees.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Attendee_Repository::build_query
	 */
	public function should_return_attendees_of_password_protected_posts_to_administrator( Restv1Tester $I ) {
		$attendees = $this->seed_public_and_protected_attendees( $I );
		$expected  = [ $attendees['public'], $attendees['protected'] ];

		$I->generate_nonce_for_role( 'administrator' );
		$I->sendGET( $this->attendees_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$ids = $I->grabDataFromResponseByJsonPath( '$.attendees[*].id' );
		sort( $ids );
		sort( $expected );
		$I->assertEquals( $expected, $ids );
	}

	/**
	 * Visitor requests for the whole archive and for the password-protected post only.
	 *
	 * @return array<string,array{include_post:bool}>
	 */
	protected function visitor_requests(): array {
		return [
			'whole archive'                 => [ 'include_post' => false ],
			'password-protected post only' => [ 'include_post' => true ],
		];
	}

	/**
	 * Seeds a public and a password-protected post, both published and showing their attendee list,
	 * with one RSVP attendee each.
	 *
	 * @return array{public:int,protected:int,protected_post:int} The attendee IDs and the protected post ID.
	 */
	private function seed_public_and_protected_attendees( Restv1Tester $I ): array {
		/* Despite its name, a truthy `_tribe_hide_attendees_list` means the attendee list is shown. */
		$shown_list     = [ '_tribe_hide_attendees_list' => 1 ];
		$public_post    = $I->havePostInDatabase( [ 'post_status' => 'publish', 'meta_input' => $shown_list ] );
		$protected_post = $I->havePostInDatabase( [
			'post_status'   => 'publish',
			/* Any non-empty password protects the post; its value is never sent. */
			'post_password' => 'members2026',
			'meta_input'    => $shown_list,
		] );

		return [
			'public'         => $this->create_going_attendee( $public_post ),
			'protected'      => $this->create_going_attendee( $protected_post ),
			'protected_post' => $protected_post,
		];
	}

	/**
	 * Creates an RSVP ticket on the post and one "going" attendee who did not opt out.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return int The attendee ID.
	 */
	private function create_going_attendee( int $post_id ): int {
		return $this->create_attendee_for_ticket( $this->create_rsvp_ticket( $post_id ), $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );
	}
}
