<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\REST;

class OrderEndpointTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @dataProvider order_item_name_provider
	 */
	public function test_format_order_item_name( string $text, string $expected ): void {
		$order_endpoint = new Order_Endpoint();
		$result         = $order_endpoint->format_order_item_name( $text );
		$this->assertSame( $expected, $result );
	}

	public function order_item_name_provider() {
		yield "Short text should not be truncated" => [ "Short text", "Short text" ];
		yield "Text with exact max length should not be truncated" => [
			str_repeat( "A", 127 ),
			str_repeat( "A", 127 )
		];
		yield "Long text without spaces should be truncated at max length minus ellipsis" => [
			str_repeat( "A", 137 ),
			substr( str_repeat( "A", 137 ), 0, 124 ) . '...'
		];
		yield "Long text with spaces should be truncated at the last space within limit" => [
			"This is a very long text that exceeds the maximum character length and needs to be truncated. We need to extend the text a little bit longer.",
			"This is a very long text that exceeds the maximum character length and needs to be truncated. We need to extend the text a..."
		];
		yield "Empty text should not be altered" => [ "", "" ];
	}
}