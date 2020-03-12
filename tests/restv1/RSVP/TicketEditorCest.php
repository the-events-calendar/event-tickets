<?php

namespace Tribe\Tickets\Test\REST\V1\RSVP;

use Codeception\Example;
use Restv1Tester;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe\Tickets\Test\Testcases\REST\V1\BaseTicketEditorCest;

/**
 * @group block
 * @group block-rsvp
 * @group editor
 * @group editor-rsvp
 * @group capacity
 * @group capacity-rsvp
 */
class TicketEditorCest extends BaseTicketEditorCest {

	use Ticket_Maker;

	/**
	 * Get list of providers for test.
	 *
	 * @return array List of providers.
	 */
	protected function get_providers() {
		return $this->get_rsvp_providers();
	}

	/**
	 * Get ticket matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_ticket_matrix() {
		return $this->_get_rsvp_matrix();
	}

	/**
	 * Get ticket update matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_ticket_update_matrix() {
		return $this->_get_rsvp_update_matrix();
	}

	/**
	 * Create a ticket via admin-ajax.php.
	 *
	 * @param Restv1Tester $I         API tester.
	 * @param array        $variation Variation data.
	 * @param null|array   $override  List of arguments to override with.
	 *
	 * @return array Ticket args.
	 */
	protected function create_ticket_using_ajax( Restv1Tester $I, array $variation, array $override = [] ) {
		return $this->create_rsvp_using_ajax( $I, $variation, $override );
	}

	/**
	 * It should allow creating a RSVP.
	 *
	 * @test
	 * @dataProvider _get_ticket_matrix
	 */
	public function should_allow_creating_a_ticket( Restv1Tester $I, Example $variation ) {
		// REST needs to be tested later.
	}

	/**
	 * It should allow creating a RSVP and updating the post.
	 *
	 * @test
	 * @dataProvider _get_shared_ticket_matrix
	 */
	public function should_allow_creating_a_ticket_and_updating_post( Restv1Tester $I, Example $variation ) {
		// REST needs to be tested later.
	}

	/**
	 * It should allow updating a RSVP.
	 *
	 * @test
	 * @dataProvider _get_ticket_update_matrix
	 */
	public function should_allow_updating_a_ticket( Restv1Tester $I, Example $variation ) {
		// REST needs to be tested later.
	}
}
