<?php

namespace Tribe\src;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Base;

class BaseTest extends Controller_Test_Case {
	protected string $controller_class = Base::class;

	/**
	 * It should filter ticket post types correctly
	 *
	 * @test
	 */
	public function should_filter_ticket_post_types_correctly(): void {
		$controller = $this->make_controller();

		$this->assertEquals( 'foo', $controller->update_ticket_post_types( 'foo' ) );

		$this->assertEquals( [
			'foo',
			'bar',
			Series_Post_Type::POSTTYPE
		], $controller->update_ticket_post_types( [ 'foo', 'bar' ] ) );
	}
}
