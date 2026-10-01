<?php

namespace TEC\Tickets\RSVP\V2;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\RSVP\RSVP_Disabled;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;

class Controller_Test extends Controller_Test_Case {

	use Ticket_Maker;

	protected string $controller_class = Controller::class;

	/**
	 * @test
	 */
	public function it_should_hide_the_header_image_option_for_posts_with_tc_rsvp_tickets(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->create_tc_rsvp_ticket( $post_id );

		$controller = $this->make_controller();
		$controller->register();

		$html = tribe( 'tickets.admin.views' )->template(
			'editor/panel/header-image',
			[ 'post_id' => $post_id ],
			false
		);

		$this->assertFalse( $html );
	}

	/**
	 * @test
	 */
	public function it_should_render_the_header_image_option_for_posts_without_tc_rsvp_tickets(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$controller = $this->make_controller();
		$controller->register();

		$html = tribe( 'tickets.admin.views' )->template(
			'editor/panel/header-image',
			[ 'post_id' => $post_id ],
			false
		);

		$this->assertIsString( $html );
		$this->assertStringContainsString( 'tribe-tickets-image', $html );
	}
}
