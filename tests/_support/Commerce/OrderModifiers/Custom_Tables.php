<?php

declare( strict_types=1 );

namespace Tribe\Tickets\Test\Commerce\OrderModifiers;

use PHPUnit\Framework\Assert;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\{
	Order_Modifier_Relationships as Relationships_Table,
	Order_Modifiers as Modifiers_Table,
	Order_Modifiers_Meta as Meta_Table
};

trait Custom_Tables {

	/**
	 * Truncates the custom tables.
	 *
	 * This method truncates the custom tables used by the order modifiers.
	 *
	 * @before
	 * @after
	 */
	public function truncate_custom_tables() {
		$increment = 9687;
		$classes   = [
			Relationships_Table::class,
			Meta_Table::class,
			Modifiers_Table::class,
		];

		foreach ( $classes as $class ) {
			/** @var Table $instance */
			$instance = tribe( $class );

			Assert::assertTrue( false !== $instance->empty_table() );
			DB::query(
				DB::prepare(
					"ALTER TABLE %i AUTO_INCREMENT = %d",
					$instance->table_name(),
					$increment
				)
			);
		}
	}
}
