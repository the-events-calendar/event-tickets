<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use PHPUnit\Framework\Assert;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class AttendeeArchiveOrderCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow getting attendees by include order
	 *
	 * @test
	 */
	public function should_allow_getting_attendees_by_include_order( \Restv1Tester $I ) {
		Assert::markTestSkipped( 'This order criteria still needs scoping' );

		$I->generate_nonce_for_role( 'editor' );

		$post_id    = $I->havePostInDatabase();
		$ticket_ids = $this->create_many_rsvp_tickets( 2, $post_id );

		$inverted  = array_reverse( $ticket_ids );
		$attendees = array_reduce( $ticket_ids, function ( array $acc, $ticket_id ) use ( $post_id ) {
			$acc = array_merge( $acc, $this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id ) );

			return $acc;
		}, [] );

		$I->sendGET( $this->attendees_url, [ 'orderby' => 'include' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$attendee_ids       = [
			$attendees[2],
			$attendees[3],
			$attendees[0],
			$attendees[1],
		];
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', $attendee_ids )
			->order_by( 'post__in' )
			->all();

		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'orderby' => 'include' ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );
	}
}
