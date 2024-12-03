<?php

namespace TEC\Tickets\Commerce\Order_Modifiers;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Common\StellarWP\Schema\Schema;

class Tables_Test extends Controller_Test_Case {
	protected string $controller_class = Tables::class;

	/**
	 * @test
	 */
	public function it_should_have_custom_tables_registered() {
		$this->make_controller()->register();
		$this->assertTrue( Schema::tables()->offsetExists( Custom_Tables\Order_Modifiers::table_name( false ) ) );
		$this->assertTrue( Schema::tables()->offsetExists( Custom_Tables\Order_Modifiers_Meta::table_name( false ) ) );
		$this->assertTrue( Schema::tables()->offsetExists( Custom_Tables\Order_Modifier_Relationships::table_name( false ) ) );
	}

	/**
	 * @test
	 */
	public function it_should_create_tables_in_the_db_with_their_indexes() {
		$this->make_controller()->register();
		$this->assertTrue( tribe( Custom_Tables\Order_Modifiers::class )->exists() );
		$this->assertTrue( tribe( Custom_Tables\Order_Modifiers_Meta::class )->exists() );
		$this->assertTrue( tribe( Custom_Tables\Order_Modifier_Relationships::class )->exists() );

		$this->assertTrue( tribe( Custom_Tables\Order_Modifiers::class )->has_index( 'tec_order_modifier_indx_slug' ) );
		$this->assertTrue( tribe( Custom_Tables\Order_Modifiers::class )->has_index( 'tec_order_modifier_indx_modifier_type' ) );
		$this->assertTrue( tribe( Custom_Tables\Order_Modifiers::class )->has_index( 'tec_order_modifier_indx_status_modifier_type' ) );
		$this->assertTrue( tribe( Custom_Tables\Order_Modifiers_Meta::class )->has_index( 'tec_order_modifier_meta_inx_order_modifier_id' ) );
		$this->assertTrue( tribe( Custom_Tables\Order_Modifiers_Meta::class )->has_index( 'tec_order_modifier_meta_inx_meta_key' ) );
		$this->assertTrue( tribe( Custom_Tables\Order_Modifier_Relationships::class )->has_index( 'tec_order_modifier_relationship_indx_modifier_id' ) );
		$this->assertTrue( tribe( Custom_Tables\Order_Modifier_Relationships::class )->has_index( 'tec_order_modifier_relationship_indx_post_id' ) );
	}
}
