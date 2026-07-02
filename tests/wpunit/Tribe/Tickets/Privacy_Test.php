<?php
/**
 * Tests for the Tribe__Tickets__Privacy class.
 *
 * @since TBD
 */

namespace Tribe\Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\RSVP\Repositories\Attendee_Repository_Disabled;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Post_Transient;
use Tribe__Tickets__Privacy as Privacy;
use Tribe__Tickets__Tickets;
use WP_Post;

/**
 * Class Privacy_Test
 *
 * @since TBD
 */
class Privacy_Test extends WPTestCase {
	use Attendee_Maker;
	use RSVP_Ticket_Maker;

	/*
	 * The test will override this binding. This is the source of truth about the class that should be
	 * bound back at the end of the tests.
	 *
	 * @var class-string<Tribe__Repository__Interface>
	 */
	private ?string $original_attendee_repository_class = null;

	/**
	 * @before
	 */
	public function set_up_rsvp(): void {
		add_filter( 'tribe_tickets_post_types', static function () {
			return [ 'post', 'tribe_events' ];
		} );

		add_filter( 'tribe_tickets_rsvp_send_mail', '__return_false' );

		// Backup the original Attendee repository class to restore it after the tests.
		$this->original_attendee_repository_class = get_class( tribe()->get( 'tickets.attendee-repository.rsvp' ) );
	}

	/**
	 * Restore the original RSVP Attendee repository binding to avoid following tests
	 * from running on the disabled one.
	 *
	 * @after
	 */
	public function restore_rsvp_attendee_repository(): void {
		tribe()->bind( 'tickets.attendee-repository.rsvp', $this->original_attendee_repository_class );
	}

	public function test_rsvp_exporter_returns_attendee_data(): void {
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$email     = 'exporter-test@example.com';

		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'email'     => $email,
			'full_name' => 'Test Exporter User',
		] );

		$privacy = new Privacy();
		$result  = $privacy->rsvp_exporter( $email );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'done', $result );
		$this->assertCount( 1, $result['data'] );

		$export_item = $result['data'][0];

		$this->assertSame( 'rsvp-attendees', $export_item['group_id'] );
		$this->assertSame( 'Event Tickets RSVP Attendee Data', $export_item['group_label'] );
		$this->assertSame( "tribe_rsvp_attendees-{$attendee_id}", $export_item['item_id'] );

		$data_names = array_column( $export_item['data'], 'name' );

		$this->assertContains( 'RSVP Title', $data_names );
		$this->assertContains( 'Full Name', $data_names );
		$this->assertContains( 'Email', $data_names );
		$this->assertContains( 'Date', $data_names );
	}

	public function test_rsvp_exporter_pagination_done_flag(): void {
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$email     = 'pagination-done-test@example.com';

		$this->create_many_attendees_for_ticket( 3, $ticket_id, $post_id, [
			'email' => $email,
		] );

		$privacy = new Privacy();

		$result = $privacy->rsvp_exporter( $email, 1 );

		$this->assertTrue( $result['done'] );
		$this->assertCount( 3, $result['data'] );
	}

	public function test_rsvp_exporter_returns_empty_when_no_attendees(): void {
		$privacy = new Privacy();
		$result  = $privacy->rsvp_exporter( 'nonexistent@example.com' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'done', $result );
		$this->assertEmpty( $result['data'] );
		$this->assertTrue( $result['done'] );
	}

	public function test_rsvp_exporter_filter_receives_wp_post_object(): void {
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$email     = 'filter-wppost-test@example.com';

		$this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'email' => $email,
		] );

		$received_attendee = null;

		add_filter(
			'tribe_tickets_personal_data_export_rsvp',
			static function ( $data, $attendee ) use ( &$received_attendee ) {
				$received_attendee = $attendee;

				return $data;
			},
			10,
			2
		);

		$privacy = new Privacy();
		$privacy->rsvp_exporter( $email );

		$this->assertInstanceOf( WP_Post::class, $received_attendee );
	}

	public function test_rsvp_exporter_filter_is_applied(): void {
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$email     = 'filter-applied-test@example.com';

		$this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'email' => $email,
		] );

		add_filter(
			'tribe_tickets_personal_data_export_rsvp',
			static function ( $data ) {
				$data[] = [
					'name'  => 'Custom Field',
					'value' => 'Custom Value',
				];

				return $data;
			}
		);

		$privacy = new Privacy();
		$result  = $privacy->rsvp_exporter( $email );

		$export_item = $result['data'][0];
		$data_names  = array_column( $export_item['data'], 'name' );

		$this->assertContains( 'Custom Field', $data_names );
	}

	public function test_rsvp_eraser_deletes_attendees(): void {
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$email     = 'eraser-test@example.com';

		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'email' => $email,
		] );

		$this->assertInstanceOf( WP_Post::class, get_post( $attendee_id ) );

		$privacy = new Privacy();
		$result  = $privacy->rsvp_eraser( $email );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['items_removed'] );
		$this->assertFalse( $result['items_retained'] );
		$this->assertEmpty( $result['messages'] );
		$this->assertTrue( $result['done'] );

		$this->assertNull( get_post( $attendee_id ) );
	}

	public function test_rsvp_eraser_clears_cache_on_success(): void {
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$email     = 'cache-clear-test@example.com';

		$this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'email' => $email,
		] );

		$post_transient = Tribe__Post_Transient::instance();
		$post_transient->set( $post_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE, 'cached_value', 300 );

		$cached_before = $post_transient->get( $post_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );

		$this->assertSame( 'cached_value', $cached_before );

		$privacy = new Privacy();
		$privacy->rsvp_eraser( $email );

		$cached_after = $post_transient->get( $post_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );

		$this->assertFalse( $cached_after );
	}

	public function test_rsvp_eraser_returns_early_for_empty_email(): void {
		$privacy = new Privacy();
		$result  = $privacy->rsvp_eraser( '' );

		$this->assertIsArray( $result );
		$this->assertFalse( $result['items_removed'] );
		$this->assertFalse( $result['items_retained'] );
		$this->assertEmpty( $result['messages'] );
		$this->assertTrue( $result['done'] );
	}

	public function test_rsvp_eraser_pagination_done_flag(): void {
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$email     = 'eraser-pagination-test@example.com';

		$this->create_many_attendees_for_ticket( 3, $ticket_id, $post_id, [
			'email' => $email,
		] );

		$privacy = new Privacy();

		$result = $privacy->rsvp_eraser( $email, 1 );

		$this->assertTrue( $result['done'] );
		$this->assertTrue( $result['items_removed'] );
	}

	public function test_rsvp_exporter_works_with_disabled_repository(): void {
		$disabled_repository = new Attendee_Repository_Disabled();

		/*
		 * Override the original binding to bind the disabled repository.
		 * The tests will restore the original binding after themselves.
		 */
		tribe()->bind( 'tickets.attendee-repository.rsvp', $disabled_repository );

		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$email     = 'disabled-exporter@example.com';

		$this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'email' => $email,
		] );

		$privacy = new Privacy();
		$result  = $privacy->rsvp_exporter( $email );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'done', $result );
		$this->assertEmpty( $result['data'] );
		$this->assertTrue( $result['done'] );
	}

	public function test_rsvp_eraser_works_with_disabled_repository(): void {
		$disabled_repository = new Attendee_Repository_Disabled();

		/*
		 * Override the original binding to bind the disabled repository.
		 * The tests will restore the original binding after themselves.
		 */
		tribe()->bind( 'tickets.attendee-repository.rsvp', $disabled_repository );

		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$email     = 'disabled-eraser@example.com';

		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'email' => $email,
		] );

		$privacy = new Privacy();
		$result  = $privacy->rsvp_eraser( $email );

		$this->assertIsArray( $result );
		$this->assertFalse( $result['items_removed'] );
		$this->assertFalse( $result['items_retained'] );
		$this->assertEmpty( $result['messages'] );
		$this->assertTrue( $result['done'] );

		$this->assertInstanceOf( WP_Post::class, get_post( $attendee_id ) );
	}
}
