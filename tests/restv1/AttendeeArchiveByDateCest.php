<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class AttendeeArchiveByDateCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow getting attendees by date
	 *
	 * @test
	 */
	public function should_allow_getting_attendees_by_date( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$post_ids = $I->haveManyPostsInDatabase( 3 );
		// 3 posts, 1 ticket per post, 2 attendees per ticket => 6 attendees
		$attendees = array_reduce( $post_ids, function ( array $attendees, $post_id ) {
			$ticket_id = $this->create_rsvp_ticket( $post_id );
			$attendees = array_merge(
				$attendees,
				$this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id )
			);

			return $attendees;
		}, [] );

		// distance attendees to be 1 day apart
		$date = new \DateTime( '-1 month', new \DateTimeZone( 'UTC' ) );

		$one_day_apart = function ( $id ) use ( &$date ) {
			/** @var \DateTime $date */
			$date->add( new \DateInterval( 'P1D' ) );
			$new_post_date = $date->format( 'Y-m-d H:i:s' );

			codecept_debug( "Setting post_date of attendee {$id} to {$new_post_date}" );

			return $new_post_date;
		};

		tribe_attendees()
			->where( 'post__in', $attendees )
			->set( 'post_date_gmt', $one_day_apart )
			->save();

		clean_post_cache( $attendees[2] );
		$third_date = get_post( $attendees[2] )->post_date;

		$expected_attendees = tribe_attendees( 'restv1' )->where( 'post__in', \array_slice( $attendees, 2 ) )->all();
		$I->sendGET( $this->attendees_url, [ 'after' => $third_date ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'after' => $third_date ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		$expected_attendees = tribe_attendees( 'restv1' )->where( 'post__in', \array_splice( $attendees, 0, 3 ) )->all();
		$I->sendGET( $this->attendees_url, [ 'before' => $third_date ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'before' => $third_date ], $this->attendees_url . '/' ),
			'total'       => 3,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );
	}

	/**
	 * It should return 400 when trying to get attendees by bad date
	 *
	 * @test
	 */
	public function should_return_400_when_trying_to_get_attendees_by_bad_date( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$post_id     = $I->havePostInDatabase();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		foreach ( [ 'foo', 'foo bar', '', 'my_birthday' ] as $bad_date ) {
			$I->sendGET( $this->attendees_url, [ 'after' => $bad_date ] );
			$I->seeResponseCodeIs( 400 );
			$I->seeResponseIsJson();

			$I->sendGET( $this->attendees_url, [ 'before' => $bad_date ] );
			$I->seeResponseCodeIs( 400 );
			$I->seeResponseIsJson();
		}
	}

	/**
	 * It should allow using natural language to fetch by date
	 *
	 * @test
	 */
	public function should_allow_using_natural_language_to_fetch_by_date( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$post_ids = $I->haveManyPostsInDatabase( 3 );
		// 3 posts, 1 ticket per post, 2 attendees per ticket => 6 attendees
		$attendees = array_reduce( $post_ids, function ( array $attendees, $post_id ) {
			$ticket_id = $this->create_rsvp_ticket( $post_id );
			$attendees = array_merge(
				$attendees,
				$this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id )
			);

			return $attendees;
		}, [] );

		// distance attendees to be 1 week apart
		$date = new \DateTime( '-2 months', new \DateTimeZone( 'UTC' ) );

		$one_day_apart = function ( $id ) use ( &$date ) {
			/** @var \DateTime $date */
			$date->add( new \DateInterval( 'P1W' ) );
			$new_post_date = $date->format( 'Y-m-d H:i:s' );

			codecept_debug( "Setting post_date of attendee {$id} to {$new_post_date}" );

			return $new_post_date;
		};

		tribe_attendees()
			->where( 'post__in', $attendees )
			->set( 'post_date_gmt', $one_day_apart )
			->save();

		clean_post_cache( $attendees[2] );
		$third_date = get_post( $attendees[2] )->post_date;

		$expected_attendees = tribe_attendees( 'restv1' )->where( 'post__in', \array_slice( $attendees, 4 ) )->all();
		$I->sendGET( $this->attendees_url, [ 'after' => '-1 month' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'after' => '-1 month' ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		$expected_attendees = tribe_attendees( 'restv1' )->where( 'post__in', \array_splice( $attendees, 0, 3 ) )->all();
		$I->sendGET( $this->attendees_url, [ 'before' => '-1 month' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'before' => '-1 month' ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );
	}
}
