<?php

namespace TEC\Tickets\Seating\Frontend;

use TEC\Tickets\Seating\Frontend;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use TEC\Tickets\Seating\Meta;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use TEC\Tickets\Commerce\Tickets_View;

class Frontend_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use Ticket_Maker;
	use Series_Pass_Factory;
	
	protected string $controller_class = Frontend::class;
	
	/**
	 * it should_display_ticket_block_when_seating_is_enabled
	 *
	 * @test
	 */
	public function should_display_ticket_block_when_seating_is_not_enabled() {
		$post_id  = static::factory()->post->create(
			[
				'post_type' => 'page',
			] 
		);
		$ticket_1 = $this->create_tc_ticket( $post_id, 10 );
		$ticket_2 = $this->create_tc_ticket( $post_id, 30 );
		// Sort the tickets "manually".
		wp_update_post(
			[
				'ID'         => $ticket_1,
				'menu_order' => 1,
			] 
		);
		wp_update_post(
			[
				'ID'         => $ticket_2,
				'menu_order' => 2,
			] 
		);
		
		$this->make_controller()->register();
		
		$html = tribe( Tickets_View::class )->get_tickets_block( $post_id );
		
		// Replace the ticket IDs with placeholders.
		$html = str_replace(
			[ $post_id, $ticket_1, $ticket_2 ],
			[ '{{post_id}}', '{{ticket_1}}', '{{ticket_2}}' ],
			$html
		);
		
		$this->assertMatchesHtmlSnapshot( $html );
	}
	
	/**
	 * it should_replace_ticket_block_when_seating_is_enabled
	 *
	 * @test
	 */
	public function should_replace_ticket_block_when_seating_is_enabled() {
		$post_id  = static::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		$ticket_1 = $this->create_tc_ticket( $post_id, 20 );
		$ticket_2 = $this->create_tc_ticket( $post_id, 50 );
		// Sort the tickets "manually".
		wp_update_post(
			[
				'ID'         => $ticket_1,
				'menu_order' => 1,
			]
		);
		wp_update_post(
			[
				'ID'         => $ticket_2,
				'menu_order' => 2,
			]
		);
		
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'yes' );
		
		$this->make_controller()->register();
		
		$html = tribe( Tickets_View::class )->get_tickets_block( $post_id );
		
		// Replace the ticket IDs with placeholders.
		$html = str_replace(
			[ $post_id, $ticket_1, $ticket_2 ],
			[ '{{post_id}}', '{{ticket_1}}', '{{ticket_2}}' ],
			$html
		);
		
		$this->assertMatchesHtmlSnapshot( $html );
	}
}