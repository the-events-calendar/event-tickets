<?php
namespace TEC\Tickets\Commerce\Partials\Gateway\PayPal\AdvancedPayments\Fields;

use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;

class ExpirationDate extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'gateway/paypal/advanced-payments/fields/expiration-date';

	public function test_should_render() {
		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [] ) );
	}
}
