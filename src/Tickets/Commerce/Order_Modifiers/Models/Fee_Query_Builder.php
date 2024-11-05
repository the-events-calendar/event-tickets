<?php
/**
 * Fee Query Builder.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Models;

use TEC\Common\StellarWP\Models\ModelQueryBuilder;

/**
 * Class FeeQueryBuilder
 *
 * @since TBD
 */
class Fee_Query_Builder extends ModelQueryBuilder {

	/**
	 * FeeQueryBuilder constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		parent::__construct( Fee::class );
	}

	/**
	 * Get row as model
	 *
	 * @since TBD
	 *
	 * @param object $row The raw row from the database to convert to a model.
	 *
	 * @return Fee The model instance.
	 */
	protected function getRowAsModel( $row ) {
		return Fee::fromQueryBuilderObject( $row );
	}
}
