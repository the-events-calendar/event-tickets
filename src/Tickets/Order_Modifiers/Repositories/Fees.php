<?php
/**
 * Fees repository.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Repositories;

/**
 * Class Fees
 *
 * @since TBD
 */
class Fees extends Order_Modifiers {

	/**
	 * Fees constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		parent::__construct( 'fee' );
	}
}
