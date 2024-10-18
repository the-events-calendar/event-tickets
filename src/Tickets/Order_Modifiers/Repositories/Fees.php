<?php
/**
 * Fees repository.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Repositories;

use stdClass;

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

	/**
	 * Get all automatic fees.
	 *
	 * @since TBD
	 *
	 * @return stdClass[] The array of fee objects from the database.
	 */
	public function get_all_automatic_fees() {
		return $this->find_by_modifier_type_and_meta(
			'fee_applied_to',
			[ 'all' ],
			'fee_applied_to',
			'all'
		);
	}
}
