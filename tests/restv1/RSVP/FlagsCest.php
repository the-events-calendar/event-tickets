<?php

namespace Tribe\Tickets\Test\REST\V1\RSVP;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;

class FlagsCest extends BaseRestCest {
	use Ticket_Maker;

	/**
	 * It should flag posts that have tickets assigned as ticketed
	 *
	 * @test
	 */
	private $provider = 'rsvp';

	public function should_flag_posts_that_have_tickets_assigned_as_tickets( \Restv1Tester $I ) {
		tribe_update_option( 'ticket-enabled-post-types', [ 'post' ] );
		$ticketed_post_id   = $I->havePostInDatabase();
		$unticketed_post_id = $I->havePostInDatabase();
		$ticket_id          = $this->create_rsvp_ticket( $ticketed_post_id );

		$I->sendGET( $this->wp_rest_url . "posts/{$ticketed_post_id}" );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'id' => $ticketed_post_id, 'ticketed' => [ $this->provider ] ] );

		$I->sendGET( $this->wp_rest_url . "posts/{$unticketed_post_id}" );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'id' => $unticketed_post_id, 'ticketed' => false ] );
	}

	/**
	 * It should not flag disabled posts that have tickets assigned as ticketed
	 *
	 * @test
	 */
	public function should_not_flag_disabled_posts_that_have_tickets_assigned_as_ticketed( \Restv1Tester $I ) {
		tribe_update_option( 'ticket-enabled-post-types', [ 'page' ] );
		$ticketed_post_id   = $I->havePostInDatabase();
		$unticketed_post_id = $I->havePostInDatabase();
		$ticket_id          = $this->create_rsvp_ticket( $ticketed_post_id );

		$I->sendGET( $this->wp_rest_url . "posts/{$ticketed_post_id}" );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse(), true );
		$I->assertArrayNotHasKey( 'ticketed', $response );

		$I->sendGET( $this->wp_rest_url . "posts/{$unticketed_post_id}" );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse(), true );
		$I->assertArrayNotHasKey( 'ticketed', $response );
	}

	/**
	 * It should flag events that have tickets assigned as ticketed
	 *
	 * @test
	 */
	public function should_flag_events_that_have_tickets_assigned_as_ticketed( \Restv1Tester $I ) {
		tribe_update_option( 'ticket-enabled-post-types', [ 'tribe_events' ] );
		$ticketed_event_id   = $I->havePostInDatabase( $this->event_data() );
		$unticketed_event_id = $I->havePostInDatabase( $this->event_data() );
		$ticket_id           = $this->create_rsvp_ticket( $ticketed_event_id );

		$I->sendGET( $this->tec_rest_url . "events/{$ticketed_event_id}" );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'id' => $ticketed_event_id, 'ticketed' => [ $this->provider ] ] );

		$I->sendGET( $this->tec_rest_url . "events/{$unticketed_event_id}" );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'id' => $unticketed_event_id, 'ticketed' => false ] );
	}

	/**
	 * @return array
	 */
	protected function event_data(): array {
		return $event_data = [
			'post_type'  => 'tribe_events',
			'meta_input' => [
				'_EventStartDate'    => '2018-01-01 21:44:54',
				'_EventEndDate'      => '2018-01-01 23:44:54',
				'_EventStartDateUTC' => '2018-01-01 21:44:54',
				'_EventEndDateUTC'   => '2018-01-01 23:44:54',
			]
		];
	}

	/**
	 * It should not flag disabled that have tickets as ticketed
	 *
	 * @test
	 */
	public function should_not_flag_disabled_that_have_tickets_as_ticketed( \Restv1Tester $I ) {
		tribe_update_option( 'ticket-enabled-post-types', [ 'page' ] );
		$ticketed_event_id   = $I->havePostInDatabase( $this->event_data() );
		$unticketed_event_id = $I->havePostInDatabase( $this->event_data() );
		$ticket_id           = $this->create_rsvp_ticket( $ticketed_event_id );

		$I->sendGET( $this->tec_rest_url . "events/{$ticketed_event_id}" );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse(), true );
		$I->assertArrayNotHasKey( 'ticketed', $response );

		$I->sendGET( $this->tec_rest_url . "events/{$unticketed_event_id}" );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = json_decode( $I->grabResponse(), true );
		$I->assertArrayNotHasKey( 'ticketed', $response );
	}
}
