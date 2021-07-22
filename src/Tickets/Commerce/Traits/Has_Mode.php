<?php

namespace TEC\Tickets\Commerce\Traits;

/**
 * Trait Has_Mode.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Traits
 */
trait Has_Mode {

	/**
	 * The current working mode: live or sandbox.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $mode;

	/**
	 * Valid modes.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $valid_modes = [
		'sandbox', // Default.
		'live',
	];

	/**
	 * Sets the mode for the Merchant for handling operations.
	 *
	 * @since TBD
	 *
	 * @param string $mode
	 *
	 * @return $this
	 */
	public function set_mode( $mode ) {
		if ( ! in_array( $mode, $this->valid_modes, true ) ) {
			$mode = reset( $this->valid_modes );
		}

		$this->mode = $mode;

		return $this;
	}

	/**
	 * Gets the mode for Merchant for handling operations.
	 *
	 * @since TBD
	 *
	 * @return string Which mode we are using the Merchant.
	 */
	public function get_mode() {
		return $this->mode;
	}

	/**
	 * Determines if we are using sandbox mode.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_sandbox() {
		return 'sandbox' === $this->get_mode();
	}
}
