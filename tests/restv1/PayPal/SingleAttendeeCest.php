<?php

namespace Tribe\Tickets\Test\REST\V1\PayPal;

use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker;
use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;

class SingleAttendeeCest extends BaseRestCest {

	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should return an error if ET+ is not loaded.
	 *
	 * @test
	 */
	public function should_return_error_if_etplus_not_loaded( Restv1Tester $I ) {
		$post_id = $I->havePostInDatabase( [ 'post_content' => '[tribe_attendees_list]' ] );

		$I->havePostmetaInDatabase( $post_id, '_tribe_hide_attendees_list', '1' );

		$attendees_count = 7;
		$ticket_id       = $this->create_paypal_ticket_basic( $post_id, 5, [
			'meta_input' => [
				'total_sales' => $attendees_count,
				'_stock'      => 30 - $attendees_count,
				'_capacity'   => 30,
			],
		] );
		$attendees_id    = $this->create_many_attendees_for_ticket( $attendees_count, $ticket_id, $post_id );

		$ticket_rest_url = $this->attendees_url . "/{$ticket_id}";

		$I->sendGET( $ticket_rest_url );

		$I->seeResponseCodeIs( 401 );
		$I->seeResponseIsJson();
	}
}
