<?php

namespace TEC\Tickets\Seating\Orders;

use Closure;
use Generator;
use lucatume\WPBrowser\TestCase\WPTestCase;
use PHPUnit\Framework\Assert;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\Service;
use Tribe\Tests\Traits\Service_Locator_Mocks;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use TEC\Common\StellarWP\Uplink\Resources\License;

class Seats_Report_Test extends WPTEstCase {
	use With_Tickets_Commerce;
	use Ticket_Maker;
	use Service_Locator_Mocks;
	use With_Uopz;
	use SnapshotAssertions;
	use Order_Maker;

	public function render_page_data_provider(): Generator {
		yield 'no_tickets' => [
			function (): array {
				$post_id = self::factory()->post->create(
					[
						'post_title' => 'The Event',
					]
				);
				$ticket  = $this->create_tc_ticket( $post_id, 10 );

				return [ $post_id ];
			},
		];

		yield '1_ticket_1_attendee' => [
			function (): array {
				$post_id      = self::factory()->post->create(
					[
						'post_title' => 'The Event',
					]
				);
				$ticket       = $this->create_tc_ticket( $post_id, 10 );
				$order        = $this->create_order( [ $ticket => 1 ] );
				[ $attendee ] = tribe_attendees()->where( 'event_id', $post_id )->get_ids();

				return [ $post_id, $ticket, $attendee ];
			},
		];

		yield '2_tickets_3_attendees' => [
			function (): array {
				$post_id                                  = self::factory()->post->create(
					[
						'post_title' => 'The Event',
					]
				);
				$ticket_1                                 = $this->create_tc_ticket( $post_id, 10 );
				$ticket_2                                 = $this->create_tc_ticket( $post_id, 20 );
				$ticket_1_order                           = $this->create_order( [ $ticket_1 => 1 ] );
				$ticket_2_order                           = $this->create_order( [ $ticket_2 => 2 ] );
				[ $attendee_1, $attendee_2, $attendee_3 ] = tribe_attendees()->where( 'event_id', $post_id )->get_ids();

				return [
					$post_id,
					$ticket_1,
					$ticket_2,
					$ticket_1_order,
					$ticket_2_order,
					$attendee_1,
					$attendee_2,
					$attendee_3,
				];
			},
		];
	}

	/**
	 * @dataProvider render_page_data_provider
	 */
	public function test_render_page( Closure $fixture ): void {
		$ids     = $fixture();
		$post_id = array_shift( $ids );
		update_post_meta( $post_id, Meta::META_KEY_UUID, 'some-post-uuid' );
		update_post_meta( $post_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'layout-uuid' );
		$_GET['post_id'] = $post_id;
		$this->mock_singleton_service(
			Service::class,
			[
				'get_ephemeral_token' => function ( $expiration, $scope ) {
					Assert::assertEquals( 6 * HOUR_IN_SECONDS, $expiration );
					Assert::assertEquals( 'admin', $scope );
					return 'some-ephemeral-token';
				},
			]
		);

		$seats_report = tribe( Seats_Report::class );

		$this->set_class_fn_return( License::class, 'is_valid', true );

		ob_start();
		$seats_report->render_page();
		$html = ob_get_clean();

		$ids = array_map(
			function ( $id ) {
				return is_object( $id ) ? $id->ID : (int) $id;
			},
			$ids
		);

		arsort( $ids );

		$html = str_replace( [ ...$ids, $post_id ], '{{ID}}', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * @dataProvider render_page_data_provider
	 */
	public function test_upsell_render_page( Closure $fixture ): void {
		$ids     = $fixture();
		$post_id = array_shift( $ids );
		update_post_meta( $post_id, Meta::META_KEY_UUID, 'some-post-uuid' );
		update_post_meta( $post_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'layout-uuid' );
		$_GET['post_id'] = $post_id;
		$this->mock_singleton_service(
			Service::class,
			[
				'get_ephemeral_token' => function ( $expiration, $scope ) {
					Assert::assertEquals( 6 * HOUR_IN_SECONDS, $expiration );
					Assert::assertEquals( 'admin', $scope );
					return 'some-ephemeral-token';
				},
			]
		);

		$seats_report = tribe( Seats_Report::class );

		test_remove_service_status_ok_callback();
		$this->set_class_fn_return( License::class, 'is_valid', false );
		$this->set_class_fn_return( License::class, 'is_expired', false );

		ob_start();
		$seats_report->render_page();
		$html = ob_get_clean();

		$ids = array_map(
			function ( $id ) {
				return is_object( $id ) ? $id->ID : (int) $id;
			},
			$ids
		);

		arsort( $ids );

		$html = str_replace( [ ...$ids, $post_id ], '{{ID}}', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
