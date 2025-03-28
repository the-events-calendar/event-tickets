<?php

declare( strict_types=1 );

namespace Tribe\Tests\Tickets\Traits;

use Tribe\Tests\Traits\With_Uopz;

/**
 * Trait Tribe_URL
 *
 * @since 5.21.0
 */
trait Tribe_URL {

	use With_Uopz;

	/**
	 * @before
	 */
	public function replace_image_path() {
		$this->set_fn_return(
			'tribe_resource_url',
			function ( $path ) {
				$path = ltrim( $path, '/' );
				return "https://example.com/{$path}";
			},
			true
		);
	}
}
