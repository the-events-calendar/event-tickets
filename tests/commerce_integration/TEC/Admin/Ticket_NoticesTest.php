<?php

namespace TEC\Admin;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Admin\Order_Notices\Status_Change_Failed;
use TEC\Tickets\Commerce\Admin\Order_Notices\Gateway_Partial_Refund_Failed;
use TEC\Tickets\Commerce\Admin\Order_Notices\Gateway_Refund_Failed;
use TEC\Tickets\Commerce\Admin\Order_Notices\Partially_Refunded;
use TEC\Tickets\Commerce\Admin\Order_Notices\Refund_Failed;
use TEC\Tickets\Commerce\Admin\Order_Notices\Status_Change_Success;
use TEC\Tickets\Commerce\Admin\Order_Notices\Status_Refunded;
use TEC\Tickets\Commerce\Admin\Order_Notices\Successfully_Refunded;

class Ticket_NoticesTest extends \Codeception\TestCase\WPTestCase {
	use SnapshotAssertions;

	public function message_params_data_provider() {
		$messages = [
			Status_Change_Failed::class,
			Status_Change_Success::class,
			Gateway_Partial_Refund_Failed::class,
			Gateway_Refund_Failed::class,
			Partially_Refunded::class,
			Refund_Failed::class,
			Status_Change_Failed::class,
			Status_Change_Success::class,
			Status_Refunded::class,
			Successfully_Refunded::class,
		];

		foreach ( $messages as $message_class ) {
			yield [ $message_class, '123', 'A', 'B', 'C', 'D' ];
		}
	}

	/**
	 * @test
	 * @dataProvider message_params_data_provider
	 */
	public function messages_should_match( $notice_class, ...$params ) {
		$this->assertMatchesHtmlSnapshot(
			$notice_class::get_message(
				...$params
			)
		);
		$this->assertMatchesStringSnapshot( $notice_class::get_type() );
	}
}
