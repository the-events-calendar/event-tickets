<?php

namespace TEC\Tickets\Emails;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Values\Currency_Value;
use TEC\Tickets\Emails\Admin\Preview_Data;
use TEC\Tickets\Emails\Email\Purchase_Receipt;

/**
 * Class PurchaseReceiptTemplateTest
 *
 * Simple integration tests to ensure fees and coupons appear in Purchase Receipt emails.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Emails
 */
class PurchaseReceiptTemplateTest extends WPTestCase {

			/**
	 * Tests that fees display in purchase receipt email.
	 *
	 * @test
	 */
	public function test_purchase_receipt_shows_fees(): void {
		$order = Preview_Data::get_order();
		$order->fees = [
			[
				'display_name' => 'Processing Fee',
				'sub_total'    => 5.00,
				'quantity'     => 1,
			],
		];
		// Ensure total_value is Currency_Value type
		$order->total_value = Currency_Value::create_from_float( 105.00 );

		/** @var Purchase_Receipt $email */
		$email = tribe( Purchase_Receipt::class );

		$preview_context = [
			'is_preview'  => true,
			'order'       => $order,
		];

		foreach ( $email->get_preview_context( $preview_context ) as $key => $template_var_value ) {
			$email->set( $key, $template_var_value );
		}

		$html = $email->get_content();
		$this->assertStringContainsString( 'Fees:', $html );
	}

		/**
	 * Tests that coupons display in purchase receipt email.
	 *
	 * @test
	 */
	public function test_purchase_receipt_shows_coupons(): void {
		$order = Preview_Data::get_order();
		$order->coupons = [
			'SAVE10' => [
				'sub_total' => Value::create( '10.00' ),
			],
		];
		// Ensure total_value is Currency_Value type
		$order->total_value = Currency_Value::create_from_float( 90.00 );

		/** @var Purchase_Receipt $email */
		$email = tribe( Purchase_Receipt::class );

		$preview_context = [
			'is_preview'  => true,
			'order'       => $order,
		];

		foreach ( $email->get_preview_context( $preview_context ) as $key => $template_var_value ) {
			$email->set( $key, $template_var_value );
		}

		$html = $email->get_content();
		$this->assertStringContainsString( 'Discount:', $html );
	}
}
