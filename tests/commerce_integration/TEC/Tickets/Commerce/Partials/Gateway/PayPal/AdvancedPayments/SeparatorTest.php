<?php

namespace TEC\Tickets\Commerce\Partials\Gateway\PayPal\AdvancedPayments;

use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;

class SeparatorTest extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'gateway/paypal/advanced-payments/separator';

	public function test_should_render() {
		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [] ) );
	}
}
