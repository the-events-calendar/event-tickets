<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class TicketArchiveByPostCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow filtering tickets based on the related post
	 *
	 * @test
	 */
	public function should_allow_filtering_tickets_based_on_the_related_post( Restv1Tester $I ) {
		// 3 posts, 2 tickets per post = 6 tickets
		$post_ids = $I->haveManyPostsInDatabase( 3 );
		$tickets  = array_reduce( $post_ids, function ( $acc, $post_id ) {
			$acc[] = $this->create_rsvp_ticket( $post_id );
			$acc[] = $this->create_paypal_ticket( $post_id, 3 );

			return $acc;
		}, [] );

		$I->sendGET( $this->tickets_url, [ 'include_post' => $post_ids[0] ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( \array_slice( $tickets, 0, 2 ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'include_post' => $post_ids[0] ], $this->tickets_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'exclude_post' => $post_ids[0] ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( \array_slice( $tickets, 2 ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'exclude_post' => $post_ids[0] ], $this->tickets_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$include_posts = \array_slice( $post_ids, 0, 2 );
		$I->sendGET( $this->tickets_url, [ 'include_post' => $include_posts ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( \array_slice( $tickets, 0, 4 ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'include_post' => implode( ',', $include_posts ) ], $this->tickets_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$excluded_posts = implode( ',', [ $post_ids[0], $post_ids[2] ] );
		$I->sendGET( $this->tickets_url, [ 'exclude_post' => $excluded_posts ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( \array_slice( $tickets, 2, 2 ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'exclude_post' => $excluded_posts ], $this->tickets_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );
	}
}
