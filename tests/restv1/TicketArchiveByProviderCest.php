<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class TicketArchiveByProviderCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow getting tickets by provider
	 *
	 * @test
	 */
	public function should_allow_getting_tickets_by_provider( Restv1Tester $I ) {
		$post_ids = $I->haveManyPostsInDatabase( 2 );
		$rsvp     = array_reduce( $post_ids, function ( $acc, $post_id ) {
			$acc[] = $this->create_rsvp_ticket( $post_id );
			$acc[] = $this->create_rsvp_ticket( $post_id );

			return $acc;
		}, [] );
		$paypal   = array_reduce( $post_ids, function ( $acc, $post_id ) {
			$acc[] = $this->create_paypal_ticket( $post_id, 3 );
			$acc[] = $this->create_paypal_ticket( $post_id, 3 );

			return $acc;
		}, [] );

		// all providers
		$I->sendGET( $this->tickets_url );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', array_merge( $rsvp, $paypal ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => $this->tickets_url . '/',
			'total'       => 8,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'provider' => [ 'rsvp', 'tribe-commerce' ] ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'provider' => 'rsvp,tribe-commerce' ], $this->tickets_url . '/' ),
			'total'       => 8,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'provider' => 'rsvp' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', $rsvp )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'provider' => 'rsvp' ], $this->tickets_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'provider' => 'tribe-commerce' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', $paypal )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'provider' => 'tribe-commerce' ], $this->tickets_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'provider' => 'tribe-commerce,woot' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'provider' => 'tribe-commerce,woot' ], $this->tickets_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'provider' => 'zop,zap' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'provider' => 'zop,zap' ], $this->tickets_url . '/' ),
			'total'       => 0,
			'total_pages' => 0,
			'tickets'     => [],
		] );
	}
}
