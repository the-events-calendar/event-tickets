<?php

namespace TEC\Tickets\Commerce;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Tables\Webhooks as Webhooks_Table;

class Tables_Test extends Controller_Test_Case {
	protected string $controller_class = Tables::class;

	public function test_it_should_schedule_webhook_storage_clean_up(): void {
		$this->assertFalse( as_has_scheduled_action( Tables::WEBHOOK_STORAGE_CLEAN_UP_ACTION, [], Tables::TICKETS_COMMERCE_ACTION_GROUP ) );
		$this->make_controller()->schedule_webhook_storage_clean_up();
		$this->assertTrue( as_has_scheduled_action( Tables::WEBHOOK_STORAGE_CLEAN_UP_ACTION, [], Tables::TICKETS_COMMERCE_ACTION_GROUP ) );
	}

	public function test_it_should_clean_up_webhook_storage(): void {
		Webhooks_Table::insert_many([
			[
				'event_id'     => 1,
				'order_id'     => 1,
				'event_type'   => 'test',
				'event_data'   => 'test',
				'created_at'   => gmdate( 'Y-m-d H:i:s', time() - 2 ),
				'processed_at' => gmdate( 'Y-m-d H:i:s', time() ),
			],
			[
				'event_id'     => 4,
				'order_id'     => 4,
				'event_type'   => 'test',
				'event_data'   => 'test',
				'created_at'   => gmdate( 'Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS ),
				'processed_at' => gmdate( 'Y-m-d H:i:s', time() - 2 * DAY_IN_SECONDS ),
			]
		]);

		Webhooks_Table::insert_many([
			[
				'event_id'     => 2,
				'order_id'     => 2,
				'event_type'   => 'test',
				'event_data'   => 'test',
				'created_at'   => gmdate( 'Y-m-d H:i:s', time() - 2 * DAY_IN_SECONDS ),
			],
			[
				'event_id'     => 3,
				'order_id'     => 3,
				'event_type'   => 'test',
				'event_data'   => 'test',
				'created_at'   => gmdate( 'Y-m-d H:i:s', time() - 2 ),
			],
			[
				'event_id'     => 5,
				'order_id'     => 5,
				'event_type'   => 'test',
				'event_data'   => 'test',
				'created_at'   => gmdate( 'Y-m-d H:i:s', time() - 4 * DAY_IN_SECONDS ),
			],
		]);

		$this->assertEquals( 5, Webhooks_Table::get_total_items() );
		$this->make_controller()->clean_up_webhook_storage();
		$this->assertEquals( 3, Webhooks_Table::get_total_items() );
	}
}
