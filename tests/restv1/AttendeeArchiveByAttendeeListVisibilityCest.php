<?php

namespace Tribe\Tickets\Test\REST\V1;

use Codeception\Example;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;

class AttendeeArchiveByAttendeeListVisibilityCest extends BaseRestCest {
	use Attendee_Maker;
	use Ticket_Maker;

	/**
	 * It should not return attendees of posts that do not show the attendee list to a visitor.
	 *
	 * @test
	 * @dataProvider not_shown_list_visitor_requests
	 * @covers Tribe__Tickets__Attendee_Repository::filter_by_show_attendees
	 */
	public function should_not_return_attendees_of_posts_not_showing_the_list_to_visitor( Restv1Tester $I, Example $example ) {
		$attendees = $this->seed_shown_and_not_shown_attendees( $I, $example['not_shown_meta'] );
		$params    = $example['include_post'] ? [ 'include_post' => $attendees['not_shown_post'] ] : [];
		$expected  = $example['include_post'] ? [] : [ $attendees['shown'] ];

		$I->sendGET( $this->attendees_url, $params );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'total' => count( $expected ) ] );
		$I->assertEquals( $expected, $I->grabDataFromResponseByJsonPath( '$.attendees[*].id' ) );
	}

	/**
	 * It should not return to a visitor attendees of a post the `tribe_tickets_hide_attendees_list` filter hides.
	 *
	 * @test
	 * @covers Tribe__Tickets__Attendee_Repository::filter_by_show_attendees
	 */
	public function should_not_return_attendees_of_posts_the_filter_hides_to_visitor( Restv1Tester $I ) {
		$shown_meta  = [ 'post_status' => 'publish', 'meta_input' => [ '_tribe_hide_attendees_list' => 1 ] ];
		$shown_post  = $I->havePostInDatabase( $shown_meta );
		$hidden_post = $I->havePostInDatabase( $shown_meta );
		$I->haveMuPlugin(
			'hide-attendees-list-filter.php',
			"add_filter( 'tribe_tickets_hide_attendees_list', static function ( \$is_hidden, \$post ) {
				return {$hidden_post} === \$post->ID ? true : \$is_hidden;
			}, 10, 2 );"
		);
		$shown_attendee = $this->create_going_attendee( $shown_post );
		$this->create_going_attendee( $hidden_post );

		$I->sendGET( $this->attendees_url, [ 'per_page' => 1 ] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		/* `total` and `total_pages` come from the query, so they show whether the post was left out of it. */
		$I->seeResponseContainsJson( [ 'total' => 1, 'total_pages' => 1 ] );
		$I->assertEquals( [ $shown_attendee ], $I->grabDataFromResponseByJsonPath( '$.attendees[*].id' ) );
	}

	/**
	 * Visitor requests, for the whole archive and for the post not showing its list only, against each
	 * attendee list meta state that does not show the list.
	 *
	 * @return array<string,array{not_shown_meta:array<string,int|string>,include_post:bool}>
	 */
	protected function not_shown_list_visitor_requests(): array {
		$not_shown_meta = [
			'meta not set'       => [],
			'list hidden (0)'    => [ '_tribe_hide_attendees_list' => 0 ],
			/* The classic ticket settings save stores an unchecked box as boolean false, which WordPress writes as ''. */
			'list hidden (\'\')' => [ '_tribe_hide_attendees_list' => '' ],
		];

		$requests = [];
		foreach ( $not_shown_meta as $label => $meta ) {
			$requests[ "{$label}, whole archive" ]  = [ 'not_shown_meta' => $meta, 'include_post' => false ];
			$requests[ "{$label}, that post only" ] = [ 'not_shown_meta' => $meta, 'include_post' => true ];
		}

		return $requests;
	}

	/**
	 * Seeds two published posts with one going RSVP attendee each: one showing its attendee list, one
	 * carrying the given attendee list meta.
	 *
	 * @param Restv1Tester             $I              The REST tester.
	 * @param array<string,int|string> $not_shown_meta The attendee list meta of the post that does not show it.
	 *
	 * @return array{shown:int,not_shown:int,not_shown_post:int} The attendee IDs and the not-shown post ID.
	 */
	private function seed_shown_and_not_shown_attendees( Restv1Tester $I, array $not_shown_meta ): array {
		/* Despite its name, a truthy `_tribe_hide_attendees_list` means the attendee list is shown. */
		$shown_post     = $I->havePostInDatabase( [ 'post_status' => 'publish', 'meta_input' => [ '_tribe_hide_attendees_list' => 1 ] ] );
		$not_shown_args = [ 'post_status' => 'publish' ];
		/* WPDb cannot insert an empty `meta_input`, so the "meta not set" case leaves the key out. */
		if ( $not_shown_meta ) {
			$not_shown_args['meta_input'] = $not_shown_meta;
		}
		$not_shown_post = $I->havePostInDatabase( $not_shown_args );

		return [
			'shown'          => $this->create_going_attendee( $shown_post ),
			'not_shown'      => $this->create_going_attendee( $not_shown_post ),
			'not_shown_post' => $not_shown_post,
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
