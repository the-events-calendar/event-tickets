<?php
namespace TEC\Tickets\Commerce\Partials\Gateway\PayPal\AdvancedPayments\Fields;

use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;

class Cvv extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'gateway/paypal/advanced-payments/fields/cvv';

	public function test_should_render() {
		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [] ) );
	}
}
