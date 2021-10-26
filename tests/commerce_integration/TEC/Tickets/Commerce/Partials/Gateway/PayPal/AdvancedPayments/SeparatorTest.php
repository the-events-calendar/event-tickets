<?php

namespace TEC\Tickets\Commerce\Partials\Gateway\PayPal\AdvancedPayments;

use Tribe\Tickets\Test\Testcases\Html_Partial_Test_Case;

class SeparatorTest extends Html_Partial_Test_Case {

	protected $partial_path = 'src/views/v2/commerce/gateway/paypal/advanced-payments/separator';

	public function test_should_render() {
		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [] ) );
	}
}
