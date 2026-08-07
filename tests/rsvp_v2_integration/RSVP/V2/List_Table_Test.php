<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Admin\Tickets\List_Table;
use TEC\Tickets\Commerce as TicketsCommerce;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use WP_Query;

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

	public function test_exclude_rsvp_tickets_from_list_table_adds_type_meta_query(): void {
		$args = [
			'post_type'   => TicketsCommerce\Ticket::POSTTYPE,
			'post_status' => 'any',
		];

		$filtered = tribe( Repository_Filters::class )->exclude_rsvp_tickets_from_list_table( $args );

		$this->assertArrayHasKey( 'meta_query', $filtered );
		$this->assertIsArray( $filtered['meta_query'] );
		$this->assertArrayHasKey( Constants::TYPE_META_QUERY_KEY, $filtered['meta_query'] );
		$this->assertSame(
			[
				'key'     => '_type',
				'compare' => '!=',
				'value'   => Constants::TC_RSVP_TYPE,
			],
			$filtered['meta_query'][ Constants::TYPE_META_QUERY_KEY ]
		);
	}

	public function test_exclude_rsvp_tickets_from_list_table_is_idempotent(): void {
		$args = [
			'post_type'   => TicketsCommerce\Ticket::POSTTYPE,
			'post_status' => 'any',
		];

		$once  = tribe( Repository_Filters::class )->exclude_rsvp_tickets_from_list_table( $args );
		$twice = tribe( Repository_Filters::class )->exclude_rsvp_tickets_from_list_table( $once );

		$this->assertCount( 1, $twice['meta_query'] );
		$this->assertSame(
			$once['meta_query'][ Constants::TYPE_META_QUERY_KEY ],
			$twice['meta_query'][ Constants::TYPE_META_QUERY_KEY ]
		);
	}

	public function test_list_table_query_args_exclude_rsvp_tickets(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		// Create one TC-RSVP ticket and one regular TC ticket.
		$rsvp_ticket_id    = $this->create_tc_rsvp_ticket( $post_id );
		$regular_ticket_id = $this->create_tc_ticket( $post_id, 10 );

		// Run the same query path the List_Table uses (raw WP_Query, filtered args).
		$args  = apply_filters(
			'tec_tickets_admin_tickets_table_query_args',
			[
				'post_type'      => TicketsCommerce\Ticket::POSTTYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
			]
		);
		$query = new WP_Query( $args );
		$ids   = wp_list_pluck( $query->posts, 'ID' );

		$this->assertContains( $regular_ticket_id, $ids, 'Regular TC ticket should appear in the All Tickets list' );
		$this->assertNotContains( $rsvp_ticket_id, $ids, 'TC-RSVP ticket should be excluded from the All Tickets list' );
	}
}
