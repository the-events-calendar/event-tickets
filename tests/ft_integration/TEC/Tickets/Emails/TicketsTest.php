<?php

namespace TEC\Tickets\Emails;

use Closure;
use Generator;
use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Emails\Email\Ticket as Email_Ticket;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;

class TicketsTest extends WPTestCase {

	use SnapshotAssertions;
	use With_Uopz;
	use Ticket_Maker;
	use Order_Maker;
	use Series_Pass_Factory;

	public function placehold_post_ids( string $snapshot, array $ids ): string {
		return str_replace(
			array_values( $ids ),
			array_map( static fn( string $name ) => "{{ $name }}", array_keys( $ids ) ),
			$snapshot
		);
	}

	public function series_data_provider(): Generator {
		yield 'series with single series pass but no events' => [
			function (): array {
				$series_id = static::factory()->post->create( [
					'post_type'    => Series_Post_Type::POSTTYPE,
					'post_title'   => 'Test series with single series pass but no events',
					'post_excerpt' => 'Test Series for Series pass email',
				] );

				$series_pass_id = $this->create_tc_series_pass( $series_id, 10 )->ID;
				$order = $this->create_order( [ $series_pass_id => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );

				return [ $series_id, $series_pass_id, $order->ID ];
			}
		];

		yield 'series with single series pass and 3 attached events' => [
			function (): array {
				$series_id = static::factory()->post->create( [
					'post_type'    => Series_Post_Type::POSTTYPE,
					'post_title'   => 'Test series with single series pass and 3 attached events',
					'post_excerpt' => 'Test Series for Series pass email',
				] );

				$series_pass_id = $this->create_tc_series_pass( $series_id, 10 )->ID;
				$order = $this->create_order( [ $series_pass_id => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );

				$event_a = tribe_events()->set_args( [
					'title'      => 'Event A',
					'status'     => 'publish',
					'start_date' => '2019-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
					'series'     => $series_id,
				] )->create()->ID;

				$event_b = tribe_events()->set_args( [
					'title'      => 'Event B',
					'status'     => 'publish',
					'start_date' => '2020-01-02 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
					'series'     => $series_id,
				] )->create()->ID;

				$event_c = tribe_events()->set_args( [
					'title'      => 'Event C',
					'status'     => 'publish',
					'start_date' => '2021-01-03 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
					'series'     => $series_id,
				] )->create()->ID;

				return [ $series_id, $series_pass_id, $order->ID ];
			}
		];
	}

	/**
	 * @dataProvider series_data_provider
	 */
	public function test_tickets_email_for_series_pass( Closure $fixture ) {

		[ $series_id, $series_pass_id, $order_id ] = $fixture();
		// Generate Email content.
		$attendees   = tribe( Module::class )->get_attendees_by_order_id( $order_id );
		$email_class = tribe( Email_Ticket::class );

		$this->assertTrue( $email_class->is_enabled() );
		$email_class->set( 'post_id', $series_id );
		$email_class->set( 'tickets', $attendees );

		$html = $email_class->get_content();

		$html = $this->placehold_post_ids( $html, [
			'series_id'        => $series_id,
			'attendee_id'      => $attendees[0]['attendee_id'],
			'series_pass_id_a' => $series_pass_id,
			'security_code'    => $attendees[0]['security_code'],
		] );

		// Assert that the email content is correct.
		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * @dataProvider series_data_provider
	 */
	public function test_legacy_email_for_series_pass( Closure $fixture ) {
		[ $series_id, $series_pass_id, $order_id ] = $fixture();
		// Generate Email content.
		$attendees = tribe( Module::class )->get_attendees_by_order_id( $order_id );
		$html      = tribe( Module::class )->generate_tickets_email_content( $attendees );

		$html = $this->placehold_post_ids( $html, [
			'series_id'        => $series_id,
			'attendee_id'      => $attendees[0]['attendee_id'],
			'series_pass_id_a' => $series_pass_id,
			'security_code'    => $attendees[0]['security_code'],
		] );

		// Assert that the email content is correct.
		$this->assertMatchesHtmlSnapshot( $html );
	}
}