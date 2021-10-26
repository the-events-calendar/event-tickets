<?php

namespace TEC\Tickets\Commerce\Partials\Gateway\PayPal;

use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;

class AdvancedPayments extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'gateway/paypal/advanced-payments';

	public function test_should_render() {
		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'must_login'               => false,
				'supports_custom_payments' => true,
				'active_custom_payments'   => true,
			]
		) );
	}

	public function test_should_render_empty_if_no_custom_payments_support() {
		$this->assertEmpty( $this->get_partial_html( [
				'must_login'               => false,
				'supports_custom_payments' => true,
				'active_custom_payments'   => false,
			]
		) );
	}
}
