<?php

declare( strict_types=1 );

namespace Tribe\Tickets\Test\Commerce\OrderModifiers;

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
		$this->assertTrue( tribe( Relationships_Table::class )->truncate() );
		$this->assertTrue( tribe( Meta_Table::class )->truncate() );
		$this->assertTrue( tribe( Modifiers_Table::class )->truncate() );
	}
}
