<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\Traits;

use InvalidArgumentException;

trait HasMode {

	/**
	 * The current working mode: live or sandbox
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	protected $mode;

	/**
	 * Sets the mode for the repository for handling operations
	 *
	 * @since 5.1.6
	 *
	 * @param $mode
	 *
	 * @return $this
	 */
	public function setMode( $mode ) {
		if ( ! in_array( $mode, [ 'live', 'sandbox' ], true ) ) {
			throw new InvalidArgumentException( "Must be either 'live' or 'sandbox', received: $mode" );
		}

		$this->mode = $mode;

		return $this;
	}
}
