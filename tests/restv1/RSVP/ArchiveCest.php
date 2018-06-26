<?php

namespace Tribe\Tickets\Test\REST\V1\RSVP;

use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as Ticket_Maker;
use Tribe\Tickets\Test\REST\V1\BaseRestCest;

class ArchiveCest extends BaseRestCest {
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow getting a paginated archive of tickets
	 *
	 * @test
	 */
	public function should_allow_getting_a_paginated_archive_of_tickets( Restv1Tester $I ) {
		$post_ids   = $I->haveManyPostsInDatabase( 9 );
		$ticket_ids = array_map( function ( $post_id ) {
			$this->make_ticket( $post_id );
		}, $post_ids );
		update_option( 'posts_per_page', 3 );

		$I->sendGET( $this->tickets_url, [] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$I->seeResponseContainsJson( [
			'rest_url'    => $this->tickets_url,
			'total'       => 9,
			'total_pages' => 3,
		] );

		$response = json_decode( $I->grabResponse(), true );

		$I->assertArrayHasKey( 'tickets', $response );
		$I->assertCount( 3, $response['tickets'] );
		$I->assertEquals( $ticket_ids, array_column( $response['tickets'], 'id' ) );
	}
}
