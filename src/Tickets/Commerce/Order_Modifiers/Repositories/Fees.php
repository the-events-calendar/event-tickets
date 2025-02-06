<?php
/**
 * Fees repository.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Repositories;

use stdClass;

/**
 * Class Fees
 *
 * @since 5.18.0
 */
class Fees extends Order_Modifiers {

	/**
	 * Fees constructor.
	 *
	 * @since 5.18.0
	 */
	public function __construct() {
		parent::__construct( 'fee' );
	}

	/**
	 * Get all automatic fees.
	 *
	 * @since 5.18.0
	 *
	 * @return stdClass[] The array of fee objects from the database.
	 */
	public function get_all_automatic_fees() {
		return $this->get_modifier_by_applied_to( [ 'all' ] );
	}
}
