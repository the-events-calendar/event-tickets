<?php

namespace TEC\Tickets\Commerce\Reports;

use Codeception\TestCase\WPTestCase;

/**
 * Verifies the capability gate on the Attendees CSV export.
 *
 * The export is protected by `current_user_can( 'edit_post', $event_id )`. The previous check used
 * the generic `edit_posts` capability, which ignores the passed post ID, so any user able to edit
 * their own posts (e.g. an Author) could export the attendee list - names, emails, purchaser data -
 * of ANY event, including events owned by other users. The `edit_post` meta capability is evaluated
 * against the specific event and closes that hole.
 *
 * @covers \TEC\Tickets\Commerce\Reports\Attendees::maybe_generate_csv
 */
class Attendees_Csv_Permission_Test extends WPTestCase {

	/**
	 * Prepares the $_GET payload the CSV endpoint expects for a given event.
	 *
	 * @param int $event_id The event whose attendees would be exported.
	 */
	private function given_a_csv_export_request( int $event_id ): void {
		$_GET['attendees_csv']       = 1;
		$_GET['event_id']            = $event_id;
		// The nonce is created as - and for - the current user.
		$_GET['attendees_csv_nonce'] = wp_create_nonce( 'attendees_csv_nonce' );
	}

	/**
	 * Runs maybe_generate_csv() and reports whether it passed the permission gate.
	 *
	 * Passing the gate is observed via the `tribe_events_tickets_generate_filtered_attendees_list`
	 * action, which only fires once generation begins. The CSV items are forced empty so the method
	 * returns instead of streaming a file and calling exit().
	 *
	 * @return bool Whether generation was reached (i.e. the permission gate was passed).
	 */
	private function attempt_export(): bool {
		$reached_generation = false;

		add_action(
			'tribe_events_tickets_generate_filtered_attendees_list',
			static function () use ( &$reached_generation ) {
				$reached_generation = true;
			}
		);

		// Prevent the actual file streaming + exit() at the end of the method.
		add_filter( 'tribe_events_tickets_attendees_csv_items', static fn() => [] );

		( new Attendees() )->maybe_generate_csv();

		return $reached_generation;
	}

	/**
	 * @test
	 */
	public function it_denies_csv_export_to_a_non_owner_with_only_generic_edit_posts(): void {
		// An event owned by an administrator.
		$owner_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$event_id = self::factory()->post->create(
			[
				'post_author' => $owner_id,
				'post_status' => 'publish',
			]
		);

		// The acting user is an Author: has the generic `edit_posts` capability but does not own
		// this event and lacks `edit_others_posts`.
		$author_id = self::factory()->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $author_id );

		// The crux of the vulnerability: the old check would have passed, the new one denies.
		$this->assertTrue(
			current_user_can( 'edit_posts', $event_id ),
			'The old, insecure check (edit_posts) ignores the event ID and lets a non-owner through.'
		);
		$this->assertFalse(
			current_user_can( 'edit_post', $event_id ),
			'The new, secure check (edit_post) is event-specific and denies the non-owner.'
		);

		$this->given_a_csv_export_request( $event_id );

		$this->assertFalse(
			$this->attempt_export(),
			'A non-owner Author must NOT be able to export another user\'s attendee CSV.'
		);
	}

	/**
	 * @test
	 */
	public function it_allows_csv_export_to_a_user_who_can_edit_the_event(): void {
		// An event owned by another user; the acting Editor can still edit it via edit_others_posts.
		$owner_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$event_id = self::factory()->post->create(
			[
				'post_author' => $owner_id,
				'post_status' => 'publish',
			]
		);

		$editor_id = self::factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor_id );

		$this->assertTrue(
			current_user_can( 'edit_post', $event_id ),
			'An Editor can edit_post any event via edit_others_posts.'
		);

		$this->given_a_csv_export_request( $event_id );

		$this->assertTrue(
			$this->attempt_export(),
			'A user with edit rights over the event must be allowed to export its attendee CSV.'
		);
	}
}
