<?php

namespace Tribe\Tickets;

use Codeception\TestCase\WPTestCase;
use Faker\Factory;
use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Events\Attendees_List;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Editor__Template as Editor_Template;
use Tribe__Tickets__Tickets_View as Tickets_View;

/**
 * @covers Tribe__Tickets__Tickets_View::get_tickets_block
 */
class Tickets_ViewTest extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

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
		add_filter(
			'tribe_tickets_get_modules',
			static function ( array $modules ): array {
				$modules[ Module::class ] = Module::class;

				return $modules;
			}
		);
		add_filter( 'tribe_tickets_new_views_is_enabled', '__return_true' );
		tribe( Cart::class )->clear_cart();

		add_shortcode( $this->tag, static fn() => 'probe-output' );

		// The template singleton keeps the context of every render; give this test its own.
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
	public function it_should_print_attendee_names_as_text_in_the_tickets_block_when_the_content_runs_shortcodes(): void {
		$name    = '[' . $this->tag . ' label=' . Factory::create()->firstName() . ']';
		$post_id = $this->factory()->post->create();
		// The stored flag is inverted: 1 shows the attendee list.
		update_post_meta( $post_id, Attendees_List::HIDE_META_KEY, 1 );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );
		$this->create_order( [ $ticket_id => 1 ], [ 'purchaser_full_name' => $name ] );
		// Attendees are created opted out; the list only shows the ones who opted in.
		foreach ( tribe_attendees()->where( 'event', $post_id )->get_ids() as $attendee_id ) {
			update_post_meta( $attendee_id, Attendee::$optout_meta_key, 0 );
		}

		$content = do_shortcode( ( new Tickets_View() )->get_tickets_block( $post_id, false ) );

		$this->assertStringNotContainsString( 'probe-output', $content );
		$this->assertSame( 1, preg_match( "/<img alt='([^']*)'/", $content, $matches ) );
		$this->assertSame( $name, html_entity_decode( $matches[1], ENT_QUOTES ) );
	}
}
