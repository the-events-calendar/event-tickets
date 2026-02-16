<?php
/**
 * Tests for the RSVP V2 attendees template `is_going` guard.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP\V2;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Attendee_Maker;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe__Template;
use Tribe__Tickets__Main;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Attendees_Template_Test
 *
 * @since TBD
 */
class Attendees_Template_Test extends WPTestCase {
	use SnapshotAssertions;
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * Creates a Tribe__Template configured for commerce RSVP views.
	 *
	 * @return Tribe__Template
	 */
	private function get_template(): Tribe__Template {
		$template = new Tribe__Template();
		$template->set_template_origin( Tribe__Tickets__Main::instance() );
		$template->set_template_folder( 'src/views' );
		$template->set_template_context_extract( true );
		$template->set_template_folder_lookup( true );

		return $template;
	}

	/**
	 * Replaces post IDs with placeholders for stable snapshots.
	 *
	 * @since TBD
	 *
	 * @param string $snapshot The snapshot HTML.
	 * @param array  $ids      Map of placeholder name to post ID value.
	 *
	 * @return string The snapshot with placeholders.
	 */
	private function placehold_post_ids( string $snapshot, array $ids ): string {
		$ids = array_filter( $ids, static fn( $id ) => $id !== null );

		return str_replace(
			array_map( 'strval', array_values( $ids ) ),
			array_map( static fn( string $name ) => "{{ $name }}", array_keys( $ids ) ),
			$snapshot
		);
	}

	public function attendees_template_data_provider(): Generator {
		yield 'attendees going' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

				$attendee_ids = $this->create_going_tc_rsvp_attendees( 2, $ticket_id, $post_id, [
					'full_name' => 'Test Attendee Going',
				] );

				// Set legacy full_name meta for the name sub-template.
				foreach ( $attendee_ids as $attendee_id ) {
					update_post_meta( $attendee_id, '_tribe_rsvp_full_name', 'Test Attendee Going' );
				}

				return [ $post_id, $ticket_id, $attendee_ids, true, true ];
			},
		];

		yield 'attendees not going' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

				$attendee_ids = $this->create_not_going_tc_rsvp_attendees( 2, $ticket_id, $post_id, [
					'full_name' => 'Test Attendee Not Going',
				] );

				return [ $post_id, $ticket_id, $attendee_ids, false, false ];
			},
		];
	}

	/**
	 * @test
	 * @dataProvider attendees_template_data_provider
	 */
	public function it_should_respect_is_going_in_attendees_template( Closure $fixture ): void {
		[ $post_id, $ticket_id, $attendee_ids, $is_going, $has_output ] = $fixture();

		$rsvp = tribe( Module::class )->get_ticket( $post_id, $ticket_id );
		$this->assertInstanceOf( Ticket_Object::class, $rsvp, 'Should load ticket object.' );

		$template = $this->get_template();
		$html     = $template->template( 'v2/commerce/rsvp/attendees', [
			'attendees' => $attendee_ids,
			'is_going'  => $is_going,
			'rsvp'      => $rsvp,
		], false );

		if ( ! $has_output ) {
			$this->assertEmpty( trim( $html ) );
			return;
		}

		$html = $this->placehold_post_ids(
			$html,
			[
				'POST_ID'    => $post_id,
				'TICKET_ID'  => $ticket_id,
				'ATTENDEE_1' => $attendee_ids[0],
				'ATTENDEE_2' => $attendee_ids[1],
			]
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
