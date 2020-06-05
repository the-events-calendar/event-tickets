<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

/**
 * Class TicketArchivePermissionsCest
 *
 * Ticket archive testing if correct information is returned based on user permissions.
 *
 * @package Tribe\Tickets\Test\REST\V1
 */
class TicketArchivePermissionsCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	private function get_multi_posts_with_multi_tickets_with_multi_attendees( Restv1Tester $I ) {
		// 3 posts, 2 tickets per post, varying number of attendees
		$post_ids         = $I->haveManyPostsInDatabase( 3 );
		$attendees_counts = [ 1, 2, 3, 4, 5, 6 ];
		$i                = 0;
		$tickets          = array_reduce( $post_ids, function ( array $acc, $post_id ) use ( &$i, $attendees_counts ) {
			$acc[] = $rsvp_ticket = $this->create_rsvp_ticket( $post_id );
			$acc[] = $paypal_ticket = $this->create_paypal_ticket_basic( $post_id, 2 );
			$this->create_many_attendees_for_ticket( $attendees_counts[ $i ++ ], $rsvp_ticket, $post_id );
			$this->create_many_attendees_for_ticket( $attendees_counts[ $i ++ ], $paypal_ticket, $post_id );

			return $acc;
		}, [] );

		return $tickets;
	}

	/**
	 * It should include a Ticket's Attendee Information if request is from an Admin.
	 *
	 * @test
	 */
	public function should_contain_attendee_info_if_admin( Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'administrator' );

		$this->get_multi_posts_with_multi_tickets_with_multi_attendees( $I );

		$I->sendGET( $this->tickets_url );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );

		// includes 'attendees' array per ticket, even if empty
		$expected_tickets = tribe_tickets( 'restv1' )->all();

		foreach( $expected_tickets as $ticket ) {
			$I->assertNotEmpty( $ticket['attendees'] );
		}

		$I->seeResponseContainsJson(
			[
				'rest_url'  => trailingslashit( $this->tickets_url ),
				'tickets'   => $expected_tickets,
			]
		);
	}

	/**
	 * It should not include a Ticket's Attendee Information if request is from an Editor.
	 *
	 * @test
	 */
	public function should_not_contain_attendee_info_if_editor( Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$this->get_multi_posts_with_multi_tickets_with_multi_attendees( $I );

		$I->sendGET( $this->tickets_url );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );

		// includes 'attendees' array per ticket, even if empty
		$expected_tickets = tribe_tickets( 'restv1' )->all();

		foreach( $expected_tickets as $ticket ) {
			$I->assertEmpty( $ticket['attendees'] );
		}

		$I->seeResponseContainsJson(
			[
				'rest_url'  => trailingslashit( $this->tickets_url ),
				'tickets'   => $expected_tickets,
			]
		);
	}

	/**
	 * It should not include a Ticket's Attendee Information if request is from a Contributor.
	 *
	 * @test
	 */
	public function should_not_contain_attendee_info_if_contributor( Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'contributor' );

		$this->get_multi_posts_with_multi_tickets_with_multi_attendees( $I );

		$I->sendGET( $this->tickets_url );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );

		// includes 'attendees' array per ticket, even if empty
		$expected_tickets = tribe_tickets( 'restv1' )->all();

		foreach( $expected_tickets as $ticket ) {
			$I->assertEmpty( $ticket['attendees'] );
		}

		$I->seeResponseContainsJson(
			[
				'rest_url'  => trailingslashit( $this->tickets_url ),
				'tickets'   => $expected_tickets,
			]
		);
	}
}