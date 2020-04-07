<?php

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Events\Attendee_List_Display;

class Attendee_List_Display_Test extends \Codeception\TestCase\WPTestCase {

	/** @var Event */
	private $event_factory;

	/** @var Attendee_List_Display */
	private $attendee_list_display;

	public function setUp() {
		// before
		parent::setUp();

		// We will want to control when this fires in the tests
		remove_filter( 'save_post_tribe_events', [ tribe( Attendee_List_Display::class ), 'maybe_update_attendee_list_hide_meta' ] );

		// Blocks disabled by default. Overridden in each test, if needed.
		$this->disable_blocks();

		// This allows us to call deprecated filters without the tests bailing
		add_filter( "deprecated_hook_trigger_error", false );

		$this->event_factory         = new Event();
		$this->attendee_list_display = new Attendee_List_Display;
	}

	/**
	 * Activate the blocks in this test run.
	 */
	private function enable_blocks() {
		add_filter( "__internal_tribe_tickets_is_using_blocks", "__return_true" );
	}

	/**
	 * Deactivate the blocks in this test run.
	 */
	private function disable_blocks() {
		add_filter( "__internal_tribe_tickets_is_using_blocks", "__return_false" );
	}

	/**
	 * Given a post, set the meta to hide the attendee list.
	 *
	 * This meta value is inverted for historical reasons, which can be confusing.
	 * Thus this method wraps it to avoid mistakes.
	 *
	 * @param int $post_id
	 */
	private function set_meta_off( WP_Post $post ) {
		$updated = update_post_meta( $post->ID, Tribe__Tickets_Plus__Attendees_List::HIDE_META_KEY, false );

		$this->assertNotFalse( $updated, "Failed to update post meta" );
	}

	/**
	 * @see \Attendee_List_Display_Test::set_meta_off
	 *
	 * @param $post_id
	 */
	private function set_meta_on( WP_Post $post ) {
		$updated = update_post_meta( $post->ID, Tribe__Tickets_Plus__Attendees_List::HIDE_META_KEY, true );

		$this->assertNotFalse( $updated, "Failed to update post meta" );
	}

	/**
	 * Should hide by default.
	 *
	 * @test
	 */
	public function should_hide_by_default() {
		$post = $this->event_factory->create_and_get();
		$this->attendee_list_display->maybe_update_attendee_list_hide_meta( $post );

		$this->assertTrue( $this->attendee_list_display->is_event_hiding_attendee_list( $post ) );
	}

	/**
	 * Should hide if blocks are disabled and meta is off.
	 *
	 * @test
	 */
	public function should_hide_if_blocks_disabled_and_meta_off() {
		$post = $this->event_factory->create_and_get();
		$this->set_meta_off( $post );
		$this->attendee_list_display->maybe_update_attendee_list_hide_meta( $post );

		$this->assertTrue( $this->attendee_list_display->is_event_hiding_attendee_list( $post ) );
	}

	/**
	 * Should hide if blocks are enabled and meta is on.
	 *
	 * @test
	 */
	public function should_hide_if_blocks_enabled_and_meta_on() {
		$post = $this->event_factory->create_and_get();
		$this->set_meta_on( $post );
		$this->enable_blocks();
		$this->attendee_list_display->maybe_update_attendee_list_hide_meta( $post );

		$this->assertTrue( $this->attendee_list_display->is_event_hiding_attendee_list( $post ) );
	}

	/**
	 * Should hide if blocks are deactivated and has the block.
	 *
	 * @test
	 */
	public function should_hide_if_blocks_are_deactivated_and_has_the_block() {
		$post = $this->event_factory->create_and_get( [ 'post_content' => "<!-- wp:tribe/attendees --><!-- /wp:tribe/attendees -->" ] );
		$this->attendee_list_display->maybe_update_attendee_list_hide_meta( $post );

		$this->assertTrue( $this->attendee_list_display->is_event_hiding_attendee_list( $post ) );
	}

	/**
	 * Should hide if blocks are active but does not has the block.
	 *
	 * @test
	 */
	public function should_hide_if_blocks_are_active_but_does_not_has_the_block() {
		$post = $this->event_factory->create_and_get( [ 'post_content' => "<!-- wp:tribe/not-attendees --><!-- /wp:tribe/not-attendees -->" ] );
		$this->enable_blocks();
		$this->attendee_list_display->maybe_update_attendee_list_hide_meta( $post );

		$this->assertTrue( $this->attendee_list_display->is_event_hiding_attendee_list( $post ) );
	}

	/**
	 * Should not hide if blocks are disabled and meta is on.
	 *
	 * @test
	 */
	public function should_not_hide_if_blocks_disabled_and_meta_on() {
		$post = $this->event_factory->create_and_get();
		$this->set_meta_on( $post );
		$this->attendee_list_display->maybe_update_attendee_list_hide_meta( $post );

		$this->assertFalse( $this->attendee_list_display->is_event_hiding_attendee_list( $post ) );
	}

	/**
	 * Should not hide if has the shortcode with blocks.
	 *
	 * @test
	 */
	public function should_not_hide_if_has_the_shortcode_with_blocks() {
		$post = $this->event_factory->create_and_get( [ 'post_content' => '[tribe_attendees_list]' ] );
		$this->enable_blocks();
		$this->attendee_list_display->maybe_update_attendee_list_hide_meta( $post );

		$this->assertFalse( $this->attendee_list_display->is_event_hiding_attendee_list( $post ) );
	}

	/**
	 * Should not hide if has the shortcode without blocks.
	 *
	 * @test
	 */
	public function should_not_hide_if_has_the_shortcode_without_blocks() {
		$post = $this->event_factory->create_and_get( [ 'post_content' => '[tribe_attendees_list]' ] );
		$this->attendee_list_display->maybe_update_attendee_list_hide_meta( $post );

		$this->assertFalse( $this->attendee_list_display->is_event_hiding_attendee_list( $post ) );
	}

	/**
	 * Should not hide if blocks are active and has the block.
	 *
	 * @test
	 */
	public function should_not_hide_if_blocks_are_active_and_has_the_block() {
		$post = $this->event_factory->create_and_get( [ 'post_content' => "<!-- wp:tribe/attendees --><!-- /wp:tribe/attendees -->" ] );
		$this->enable_blocks();
		$this->attendee_list_display->maybe_update_attendee_list_hide_meta( $post );

		$this->assertFalse( $this->attendee_list_display->is_event_hiding_attendee_list( $post ) );
	}

	/**
	 * Should hide list after removing shortcode
	 *
	 * @test
	 */
	public function should_hide_list_after_removing_shortcode() {
		$post = $this->event_factory->create_and_get( [ 'post_content' => '[tribe_attendees_list]' ] );
		$this->attendee_list_display->maybe_update_attendee_list_hide_meta( $post );

		$this->assertFalse( $this->attendee_list_display->is_event_hiding_attendee_list( $post ) );

		$post->post_content = 'Not the shortcode anymore';

		$post_updated = wp_update_post( $post );
		$this->assertNotWPError( $post_updated );

		$this->attendee_list_display->maybe_update_attendee_list_hide_meta( $post );
		$this->assertTrue( $this->attendee_list_display->is_event_hiding_attendee_list( $post ) );
	}

	/**
	 * Should hide list after removing shortcode
	 *
	 * @test
	 */
	public function should_not_hide_list_after_removing_shortcode_if_meta_was_originally_on() {
		$post = $this->event_factory->create_and_get( [ 'post_content' => '[tribe_attendees_list]' ] );
		$this->set_meta_on( $post );
		$this->attendee_list_display->maybe_update_attendee_list_hide_meta( $post );

		$this->assertFalse( $this->attendee_list_display->is_event_hiding_attendee_list( $post ) );

		$post_updated = wp_update_post( [
			'ID'           => $post->ID,
			'post_content' => 'Not the shortcode anymore',
		] );
		$this->assertNotWPError( $post_updated );

		$this->attendee_list_display->maybe_update_attendee_list_hide_meta( $post );
		$this->assertFalse( $this->attendee_list_display->is_event_hiding_attendee_list( $post ) );
	}

}