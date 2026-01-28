<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Order_Modifiers_Integration\Models;

use PHPUnit\Framework\Assert;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers_Meta;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Coupon_Creator;

class Coupon_Test extends Controller_Test_Case {

	use Coupon_Creator;

	/**
	 * @test
	 */
	public function it_should_return_total_order_as_default_for_existing_coupons_without_meta(): void {
		// Create a coupon without the apply_discount_to meta field.
		$coupon = $this->create_coupon();

		// For backwards compatibility, existing coupons should default to 'total_order'.
		Assert::assertEquals( 'total_order', $coupon->get_apply_discount_to() );
		Assert::assertTrue( $coupon->applies_to_fees() );
	}

	/**
	 * @test
	 */
	public function it_should_return_tickets_only_for_new_coupons(): void {
		// Create a coupon and set it to tickets_only.
		$coupon = $this->create_coupon();
		$coupon->set_apply_discount_to( 'tickets_only' );

		Assert::assertEquals( 'tickets_only', $coupon->get_apply_discount_to() );
		Assert::assertFalse( $coupon->applies_to_fees() );
	}

	/**
	 * @test
	 */
	public function it_should_return_total_order_when_explicitly_set(): void {
		// Create a coupon and set it to total_order.
		$coupon = $this->create_coupon();
		$coupon->set_apply_discount_to( 'total_order' );

		Assert::assertEquals( 'total_order', $coupon->get_apply_discount_to() );
		Assert::assertTrue( $coupon->applies_to_fees() );
	}

	/**
	 * @test
	 */
	public function it_should_persist_apply_discount_to_setting(): void {
		// Create a coupon and set it to tickets_only.
		$coupon = $this->create_coupon();
		$coupon->set_apply_discount_to( 'tickets_only' );

		// Reload the coupon from the database.
		$reloaded_coupon = Coupon::find( $coupon->id );

		Assert::assertNotNull( $reloaded_coupon );
		Assert::assertEquals( 'tickets_only', $reloaded_coupon->get_apply_discount_to() );
		Assert::assertFalse( $reloaded_coupon->applies_to_fees() );
	}

	/**
	 * @test
	 */
	public function it_should_handle_invalid_meta_values_gracefully(): void {
		// Create a coupon.
		$coupon = $this->create_coupon();

		// Manually set an invalid meta value.
		$meta_repo = tribe( Order_Modifiers_Meta::class );
		$meta_repo->upsert_meta(
			new Order_Modifier_Meta(
				[
					'order_modifier_id' => $coupon->id,
					'meta_key'          => 'apply_discount_to',
					'meta_value'        => 'invalid_value',
				]
			)
		);

		// Should default to total_order for invalid values (backwards compatibility).
		Assert::assertEquals( 'total_order', $coupon->get_apply_discount_to() );
		Assert::assertTrue( $coupon->applies_to_fees() );
	}

	/**
	 * @test
	 */
	public function it_should_migrate_existing_coupons_to_total_order(): void {
		// Create a coupon without apply_discount_to meta (simulating existing coupon).
		$coupon = $this->create_coupon();

		// Verify it defaults to total_order.
		Assert::assertEquals( 'total_order', $coupon->get_apply_discount_to() );
		Assert::assertTrue( $coupon->applies_to_fees() );

		// Run the migration.
		$controller = tribe( \TEC\Tickets\Commerce\Order_Modifiers\Controller::class );
		$controller->migrate_existing_coupons_apply_discount_to();

		// Verify the meta was set.
		$meta_repo = tribe( Order_Modifiers_Meta::class );
		$meta      = $meta_repo->find_by_order_modifier_id_and_meta_key( $coupon->id, 'apply_discount_to' );
		Assert::assertNotNull( $meta );
		Assert::assertEquals( 'total_order', $meta->meta_value );

		// Verify migration doesn't run twice.
		$controller->migrate_existing_coupons_apply_discount_to();
		$meta_after = $meta_repo->find_by_order_modifier_id_and_meta_key( $coupon->id, 'apply_discount_to' );
		Assert::assertEquals( 'total_order', $meta_after->meta_value );
	}
}
