<?php

namespace TEC\Tickets\Commerce\Admin;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Traits\With_Globals;
use Tribe\Tickets\Test\Traits\With_Test_Orders;

class Edit_PurchaserTest extends \Codeception\TestCase\WPTestCase {

	use SnapshotAssertions;
	use With_Uopz;
	use With_Globals;
	use With_Test_Orders;

	protected static $ids = [];
	public static function tearDownAfterClass() {
		foreach (self::$ids as $id) {
			wp_delete_post($id);
		}
	}

	/**
	 * @test
	 * @dataProvider handle_request_data_provider
	 */
	public function it_should_handle_ajax_as_expected($method, $data) {
		// Mock validators to test responses.
		$this->set_fn_return('is_admin', true);
		$this->set_fn_return('wp_verify_nonce', 1);

		// So we don't die.
		$this->set_fn_return('_ajax_wp_die_handler', 'print');
		$this->set_fn_return('wp_doing_ajax', true);


		// Mock globals.
		$_SERVER['REQUEST_METHOD'] = $method;
		switch ($method) {
			case 'GET':
				$_GET = $data;
				break;
			case 'POST':
				$_POST = $data;
				break;
		}

		$purchaser_provider = tribe(Edit_Purchaser_Provider::class);
		$purchaser_provider->register();
		ob_start();
		$purchaser_provider->ajax_handle_request();
		$json = ob_get_clean();
		$this->assertMatchesJsonSnapshot($json);
	}

	public function handle_request_data_provider() {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Event 1'  ,
				'status'     => 'publish',
				'start_date' => '2024-01-01 10:00:00',
				'duration'   =>   HOUR_IN_SECONDS,
			]
		)->create()->ID;
		self::$ids[] = $event_id;
		$ticket_id = $this->create_tc_ticket($event_id);
		self::$ids[] = $ticket_id;
		$order_id = $this->create_order([$ticket_id => 1], [
			'purchaser_email' => 'test@test.com',
		])->ID;
		self::$ids[] = $order_id;

		yield 'GET -> invalid ID' => [
			'GET',
			[
				'ID' => 0
			]
		];

		yield 'GET -> valid ID' => [
			'GET',
			[
				'ID' => $order_id
			]
		];

		yield 'POST -> invalid ID' => [
			'POST',
			[
				'ID' => 0,
				'email' => 'test123@test.com',
				'name' => 'Billy Bob',
			]
		];

		yield 'POST -> missing name' => [
			'POST',
			[
				'ID' => $order_id,
				'email' => 'test123@test.com',
			]
		];

		yield 'POST -> missing email' => [
			'POST',
			[
				'ID' => $order_id,
				'name' => 'Billy Bob',
			]
		];

		yield 'POST -> valid ID -> valid update' => [
			'POST',
			[
				'ID' => $order_id,
				'email' => 'test123@test.com',
				'name' => 'Bobby Bob',
			]
		];

		yield 'POST -> valid ID -> valid update -> send email' => [
			'POST',
			[
				'ID' => $order_id,
				'email' => 'test@test.com',
				'name' => 'Bobby Bob',
				'send_email' => true,
			]
		];
	}
}
