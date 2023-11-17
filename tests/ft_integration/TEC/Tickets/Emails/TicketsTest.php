<?php

namespace TEC\Tickets\Emails;

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

	public function test_tickets_email_for_series_pass() {
		$series_id = static::factory()->post->create( [
			'post_type'    => Series_Post_Type::POSTTYPE,
			'post_title'   => 'Test Series Pass Email',
			'post_excerpt' => 'Test Series for Series pass email',
		] );

		$series_pass_id_a = $this->create_tc_series_pass( $series_id, 10 )->ID;

		$this->set_fn_return( 'current_time', '2020-02-22 22:22:22' );
		$order = $this->create_order( [ $series_pass_id_a => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );

		// Generate Email content.
		$attendees   = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		$email_class = tribe( Email_Ticket::class );

		$this->assertTrue( $email_class->is_enabled() );
		$email_class->set( 'post_id', $series_id );
		$email_class->set( 'tickets', $attendees );

		$html = $email_class->get_content();

		$html = $this->placehold_post_ids( $html, [
			'series_id'        => $series_id,
			'attendee_id'      => $attendees[0]['attendee_id'],
			'series_pass_id_a' => $series_pass_id_a,
			'security_code'    => $attendees[0]['security_code'],
		] );

		// Assert that the email content is correct.
		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_legacy_email_for_series_pass() {
		$series_id = static::factory()->post->create( [
			'post_type'  => Series_Post_Type::POSTTYPE,
			'post_title' => 'Test Series Pass Email',
		] );

		$series_pass_id_a = $this->create_tc_series_pass( $series_id, 10 )->ID;

		$this->set_fn_return( 'current_time', '2020-02-22 22:22:22' );
		$order = $this->create_order( [ $series_pass_id_a => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );
		add_filter( 'tribe_events_event_schedule_details', static fn() => 'November 2020 @ 9.00 pm' );

		// Generate Email content.
		$attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		$html      = tribe( Module::class )->generate_tickets_email_content( $attendees );

		$html = $this->placehold_post_ids( $html, [
			'series_id'        => $series_id,
			'attendee_id'      => $attendees[0]['attendee_id'],
			'series_pass_id_a' => $series_pass_id_a,
			'security_code'    => $attendees[0]['security_code'],
		] );

		// Assert that the email content is correct.
		$this->assertMatchesHtmlSnapshot( $html );
	}
}