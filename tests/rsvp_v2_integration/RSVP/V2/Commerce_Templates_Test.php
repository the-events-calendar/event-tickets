<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe__Tickets__RSVP as Legacy_RSVP;

/**
 * Integration snapshot tests for TC-RSVP commerce templates rendered via production code paths.
 */
class Commerce_Templates_Test extends WPTestCase {
	use SnapshotAssertions;
	use Ticket_Maker;

	/**
	 * Replaces post IDs with placeholders for stable snapshots.
	 *
	 * @param string $snapshot The snapshot HTML.
	 * @param array  $ids      Map of placeholder name to post ID value.
	 *
	 * @return string The snapshot with placeholders.
	 */
	private function placehold_post_ids( string $snapshot, array $ids ): string {
		$ids = array_filter( $ids, static fn( $id ) => $id !== null );

		$html = str_replace(
			array_map( 'strval', array_values( $ids ) ),
			array_map( static fn( string $name ) => "{{ $name }}", array_keys( $ids ) ),
			$snapshot
		);

		return preg_replace( '/id="tc-rsvp[^"]+"/', 'id="{{ BLOCK_HTML_ID }}"', $html );
	}

	public function test_rsvp_wrapper_outputs_data_iac_attribute(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );
		$rsvp      = tribe( Module::class )->get_ticket( $post_id, $ticket_id );

		add_filter(
			'tec_tickets_commerce_get_ticket_legacy',
			static function ( $ticket ) {
				$ticket->iac = 'required';

				return $ticket;
			},
			10,
			3
		);

		$template = tribe( 'tickets.editor.template' );
		// Mirrors Tickets_View::get_rsvp_block() global context before the frontend filter runs.
		$template->set( 'threshold', tribe( 'tickets.editor.blocks.rsvp' )->get_threshold( $post_id ) );

		$html = apply_filters(
			'tec_tickets_front_end_rsvp_form_template_content',
			'',
			[
				'active_rsvps' => [ $rsvp ],
			],
			$template,
			get_post( $post_id ),
			false
		);

		$this->assertNotEmpty( $html, 'Frontend RSVP template should render TC-RSVP output.' );

		$html = $this->placehold_post_ids(
			$html,
			[
				'POST_ID'   => $post_id,
				'TICKET_ID' => $ticket_id,
			]
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_details_title_uses_rsvp_label_singular(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'ticket_name' => 'Custom Ticket Name' ] );

		$html = tribe( Legacy_RSVP::class )->render_rsvp_step( $ticket_id, 'rsvp' );

		$this->assertNotEmpty( $html, 'render_rsvp_step should render TC-RSVP commerce content.' );

		$html = $this->placehold_post_ids(
			$html,
			[
				'POST_ID'   => $post_id,
				'TICKET_ID' => $ticket_id,
			]
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
