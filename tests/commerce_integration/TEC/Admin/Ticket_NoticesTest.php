<?php

namespace TEC\Admin;

use Spatie\Snapshots\MatchesSnapshots;
use TEC\Tickets\Commerce\Admin\Singular_Order_Notices as Notices;

class Ticket_NoticesTest extends \Codeception\TestCase\WPTestCase {
	use MatchesSnapshots;

	public function message_params_data_provider() {
		$notices  = new Notices();
		$messages = array_merge( $notices->get_success_keys(), $notices->get_error_keys() );

		foreach ( $messages as $message ) {
			yield [ $message, '123', 'A', 'B', 'C', 'D' ];
		}
	}

	/**
	 * @test
	 * @dataProvider message_params_data_provider
	 */
	public function messages_should_match( $message_key, ...$params ) {
		$notices = new Notices();

		$this->assertMatchesSnapshot(
			$notices->get_message(
				$message_key,
				...$params
			)
		);
	}
}
