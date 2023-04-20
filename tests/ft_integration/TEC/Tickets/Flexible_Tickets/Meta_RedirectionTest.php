<?php

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Tests\Provider\Controller_Test_Case;

class Meta_RedirectionTest extends Controller_Test_Case {
	protected string $controller_class = Meta_Redirection::class;

	public function bad_redirect_metadata_input(): array {
		return [
			'empty'                                   => [
				'object_id' => '',
				'meta_key'  => '',
				'single'    => '',
			],
			'null'                                    => [
				'object_id' => null,
				'meta_key'  => null,
				'single'    => null,
			],
			'zero'                                    => [
				'object_id' => 0,
				'meta_key'  => 0,
				'single'    => 0,
			],
			'zero object_id, meta_key is string'      => [
				'object_id' => 0,
				'meta_key'  => 'foo',
				'single'    => true,
			],
			'zero object_id, single is string'        => [
				'object_id' => 0,
				'meta_key'  => 'foo',
				'single'    => 'bar',
			],
			'object_id int, meta_key is not a string' => [
				'object_id' => 1,
				'meta_key'  => 1,
				'single'    => true,
			],
		];
	}

	/**
	 * It should not redirect metadata on bad input
	 *
	 * @test
	 * @dataProvider bad_redirect_metadata_input
	 */
	public function should_not_redirect_metadata_on_bad_input( $object_id, $meta_key, $single ): void {
		$controller = $this->make_controller();
		$redirected = $controller->redirect_metadata( null, $object_id, $meta_key, $single );

		$this->assertSame( null, $redirected );
	}
}
