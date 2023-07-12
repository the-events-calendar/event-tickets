<?php

namespace TEC\Tickets\Flexible_Tickets;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;

class BaseTest extends Controller_Test_Case {
	use SnapshotAssertions;

	protected string $controller_class = Base::class;

	/**
	 * It should not render ticket type options on post
	 *
	 * @test
	 */
	public function should_not_render_ticket_type_options_on_post(): void {
		$post_id = static::factory()->post->create();

		$controller = $this->make_controller();

		$this->expectOutputString( '' );
		$controller->render_ticket_type_options( $post_id );
	}

	/**
	 * It should render ticket options in Series post type
	 *
	 * @test
	 */
	public function should_render_ticket_options_in_series_post_type(): void {
		$series_id = static::factory()->post->create( [ 'post_type' => Series_Post_Type::POSTTYPE ] );

		$controller = $this->make_controller();
		ob_start();
		$controller->render_ticket_type_options( $series_id );
		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
