<?php

declare( strict_types=1 );

namespace Tribe\Tickets\Test\Commerce\OrderModifiers;

use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Float_Value;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Percent_Value;

trait Coupon_Creator {

	use Custom_Tables;

	/**
	 * @var int $coupon_counter
	 */
	static int $coupon_counter = 0;

	protected function create_coupon( array $args = [] ): Coupon {
		if ( isset( $args['raw_amount'] ) && is_numeric( $args['raw_amount'] ) ) {
			$args['raw_amount'] = Float_Value::from_number( $args['raw_amount'] );
		}

		self::$coupon_counter++;

		$defaults = [
			'raw_amount'   => new Percent_Value( 10 ),
			'sub_type'     => 'percent',
			'slug'         => sprintf( 'test-coupon-%d', self::$coupon_counter ),
			'display_name' => sprintf( 'Test Coupon %d', self::$coupon_counter ),
			'status'       => 'active',
			'start_time'   => null,
			'end_time'     => null,
		];

		$args = wp_parse_args( $args, $defaults );

		return Coupon::create( $args );
	}
}
