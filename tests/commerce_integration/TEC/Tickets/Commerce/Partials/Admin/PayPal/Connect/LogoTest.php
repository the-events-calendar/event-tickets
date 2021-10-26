<?php
namespace TEC\Tickets\Commerce\Partials\Admin\PayPal\Connect;

use Tribe\Tickets\Test\Testcases\Html_Partial_Test_Case;

class LogoTest extends Html_Partial_Test_Case {

	protected $partial_path = 'settings/tickets-commerce/paypal/connect/logo';
	protected $folder_path = 'src/admin-views';

	/**
	 * Test render cart footer
	 */
	public function test_should_render() {
		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'is_merchant_active' => false,
			]
		) );
	}

	public function test_should_render_without_list() {
		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'is_merchant_active' => true,
			]
		) );
	}
}
