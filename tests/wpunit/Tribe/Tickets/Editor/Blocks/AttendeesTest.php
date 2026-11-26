<?php

namespace Tribe\Tickets\Editor\Blocks;

use Codeception\TestCase\WPTestCase;
use Faker\Factory;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Editor__Template as Editor_Template;

/**
 * @covers Tribe__Tickets__Editor__Blocks__Attendees::render
 */
class AttendeesTest extends WPTestCase {
	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * @var string
	 */
	private string $tag = 'tec_tickets_attendee_name_probe';

	/**
	 * @var Editor_Template
	 */
	private Editor_Template $original_template;

	public function setUp(): void {
		parent::setUp();

		add_filter(
			'tribe_tickets_post_types',
			static function () {
				return [ 'post' ];
			}
		);

		add_shortcode( $this->tag, static fn() => 'probe-output' );

		/*
		 * The template singleton keeps the context of every render, so the block's title would
		 * reach the partials that later tests render. Render with a fresh one and put it back after.
		 */
		$this->original_template = tribe( 'tickets.editor.template' );
		tribe_singleton( 'tickets.editor.template', new Editor_Template() );
	}

	public function tearDown(): void {
		remove_shortcode( $this->tag );
		tribe_singleton( 'tickets.editor.template', $this->original_template );

		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_should_print_attendee_names_as_text_when_the_content_runs_shortcodes(): void {
		$faker     = Factory::create();
		$name      = '[' . $this->tag . ' label=' . $faker->firstName() . ']';
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'full_name' => $name ] );

		tribe( 'tickets.editor.template' )->add_template_globals( [ 'post_id' => $post_id ] );
		$content = do_shortcode( tribe( 'tickets.editor.blocks.attendees' )->render() );

		$this->assertStringNotContainsString( 'probe-output', $content );
		$this->assertSame( 1, preg_match( "/<img alt='([^']*)'/", $content, $matches ) );
		$this->assertSame( $name, html_entity_decode( $matches[1], ENT_QUOTES ) );
	}
}
