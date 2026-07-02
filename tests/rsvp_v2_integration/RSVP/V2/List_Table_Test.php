<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Admin\Tickets\List_Table;
use TEC\Tickets\Commerce as TicketsCommerce;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;

/**
 * Tests for All Tickets list table behavior with TC-RSVP tickets.
 */
class List_Table_Test extends WPTestCase {
	use Ticket_Maker;

	public function test_column_name_falls_back_to_rsvp_label_for_empty_tc_rsvp_name(): void {
		add_filter(
			'tec_tickets_admin_tickets_table_provider_info',
			static function () {
				return [
					TicketsCommerce\Module::class => [
						'title'              => 'Tickets Commerce',
						'event_meta_key'     => TicketsCommerce\Attendee::$event_relation_meta_key,
						'attendee_post_type' => TicketsCommerce\Attendee::POSTTYPE,
						'ticket_post_type'   => TicketsCommerce\Ticket::POSTTYPE,
					],
				];
			}
		);

		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket(
			$post_id,
			[
				'ticket_name' => '',
			]
		);

		$ticket = tribe( Module::class )->get_ticket( $post_id, $ticket_id );
		$this->assertSame( '', $ticket->name );

		$list_table = new List_Table();
		$html       = $list_table->column_name( $ticket );

		$expected_label = _x( 'RSVP', 'Default TC-RSVP ticket name in the All Tickets list', 'event-tickets' );

		$this->assertStringContainsString( esc_html( $expected_label ), $html );
	}
}
