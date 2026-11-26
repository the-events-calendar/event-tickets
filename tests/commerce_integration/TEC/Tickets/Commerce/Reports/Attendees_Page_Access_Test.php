<?php

namespace TEC\Tickets\Commerce\Reports;

use Codeception\TestCase\WPTestCase;
use Generator;

class Attendees_Page_Access_Test extends WPTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function tearDown() {
		unset( $_GET['event_id'], $_REQUEST['event_id'] );

		parent::tearDown();
	}

	public function event_provider(): Generator {
		yield 'own event' => [ 'own', true ];
		yield 'event of another user' => [ 'other', false ];
	}

	/**
	 * @test
	 * @dataProvider event_provider
	 *
	 * @param string $event    The requested event: `own` or `other`.
	 * @param bool   $expected Whether the page should be registered.
	 */
	public function should_register_the_page_only_for_an_event_the_user_can_access( string $event, bool $expected ): void {
		$admin_id       = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$contributor_id = static::factory()->user->create( [ 'role' => 'contributor' ] );
		$event_ids      = [
			'own'   => static::factory()->post->create(
				[
					'post_author' => $contributor_id,
					'post_status' => 'draft',
				]
			),
			'other' => static::factory()->post->create( [ 'post_author' => $admin_id ] ),
		];
		wp_set_current_user( $contributor_id );

		$_GET['event_id']     = $event_ids[ $event ];
		$_REQUEST['event_id'] = $event_ids[ $event ];

		$report = new Attendees();
		$report->register_attendees_page();

		$this->assertSame( $expected, ! empty( $report->attendees_page ) );
	}
}
