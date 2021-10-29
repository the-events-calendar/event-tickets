<?php

namespace TEC\Tickets\Commerce\Partials\Gateway\PayPal\AdvancedPayments;

use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;

class Form extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'gateway/paypal/advanced-payments/form';

	public function test_should_render() {
		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [] ) );
	}
}
