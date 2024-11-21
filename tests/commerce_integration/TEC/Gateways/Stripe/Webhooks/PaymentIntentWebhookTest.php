<?php

namespace TEC\Gateways\Stripe\Webhooks;

use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks\Payment_Intent_Webhook;
use TEC\Tickets\Commerce\Gateways\Stripe\Status;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Action_Required;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Refunded;

class PaymentIntentWebhookTest extends \Codeception\TestCase\WPTestCase {
	/**
	 * @dataProvider data_provider_should_payment_intent_be_updated
	 */
	public function test_should_payment_intent_be_updated( array $payment_intent_received, array $payment_intents_stored, bool $expected ) {
		$actual = Payment_Intent_Webhook::should_payment_intent_be_updated( $payment_intent_received, $payment_intents_stored );
		$this->assertEquals( $expected, $actual );
	}

	public function data_provider_should_payment_intent_be_updated() {
		return [
			// Scenario 1: Payment intent was reset or processing restarted
			[
				['status' => Status::REQUIRES_PAYMENT_METHOD],
				[
					Status::REQUIRES_PAYMENT_METHOD => [['id' => 'pi_1']],
					Status::SUCCEEDED => [['id' => 'pi_2']]
				],
				true
			],
			// Scenario 2: Payment intent already processed and updated to refunded
			[
				['id' => 'pi_2', 'status' => Status::CANCELED],
				[
					Refunded::SLUG => [['id' => 'pi_2']]
				],
				false
			],
			// Scenario 3: Payment intent pending and action required.
			[
				['id' => 'pi_3', 'status' => Status::REQUIRES_PAYMENT_METHOD],
				[
					Pending::SLUG => [['id' => 'pi_3']],
					Action_Required::SLUG => [['id' => 'pi_3']]
				],
				true
			],
			// Scenario 4: Payment intent pending and completed.
			[
				['id' => 'pi_3', 'status' => Status::SUCCEEDED],
				[
					Pending::SLUG => [['id' => 'pi_3']],
					Completed::SLUG => [['id' => 'pi_3']]
				],
				false
			]
		];
	}
}

