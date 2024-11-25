<?php

namespace Tribe\Tickets\Test\Commerce\OrderModifiers;

use TEC\Tickets\Commerce\Order_Modifiers\Models\Fee;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Relationships as Relationships_Model;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers_Meta as Meta_Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship as Relationship_Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Float_Value;
use TEC\Common\StellarWP\Models\Contracts\Model;

trait Fee_Creator {

	/**
	 * Creates a fee for a ticket.
	 *
	 * This method creates a fee with the provided arguments and associates it with a ticket.
	 * The fee is created with a default sub_type of 'flat' and a raw_amount of 5.
	 *
	 * @param int   $ticket_id The ticket ID to associate the fee with.
	 * @param array $args      The arguments to use when creating the fee.
	 *
	 * @return int The ID of the created fee.
	 */
	protected function create_fee_for_ticket( int $ticket_id, array $args = [] ): int {
		$fee = $this->create_fee( $args );
		$this->set_fee_application( $fee, 'per' );
		$this->create_fee_relationship( $fee, $ticket_id, get_post_type( $ticket_id ) );

		return $fee->id;
	}

	/**
	 * @param array $args The arguments to use when creating the fee.
	 *
	 * @return Fee The created fee.
	 *
	 */
	protected function create_fee( array $args = [] ): Fee {
		if ( isset( $args['raw_amount'] ) && is_numeric( $args['raw_amount'] ) ) {
			$args['raw_amount'] = Float_Value::from_number( $args['raw_amount'] );
		}

		$args = array_merge(
			[
				'sub_type'     => 'flat',
				'raw_amount'   => Float_Value::from_number( 5 ),
				'slug'         => 'test-fee',
				'display_name' => 'test fee',
				'status'       => 'active',
				'start_time'   => null,
				'end_time'     => null,
			],
			$args
		);

		return Fee::create( $args );
	}

	/**
	 * @param Fee   $fee        The fee to set the application for.
	 * @param mixed $applied_to The value to set the fee application to.
	 *
	 */
	protected function set_fee_application( Fee $fee, $applied_to ) {
		tribe( Meta_Repository::class )->upsert_meta(
			new Order_Modifier_Meta(
				[
					'order_modifier_id' => $fee->id,
					'meta_key'          => 'fee_applied_to',
					'meta_value'        => $applied_to, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'priority'          => 0,
				]
			)
		);
	}

	/**
	 * Creates a relationship between a fee and a ticket.
	 *
	 * This method establishes a link between a Fee and a ticket (or other post type)
	 * by inserting the relationship into the repository.
	 * Only really needed if the fee application is set to `per`.
	 *
	 * @param Fee    $fee       The fee instance to associate.
	 * @param int    $ticket    The ticket (post) ID to associate with the fee.
	 * @param string $post_type The type of post to associate the fee with. Default is 'post'.
	 *
	 * @return bool True if the relationship was successfully created; false otherwise.
	 */
	protected function create_fee_relationship( Fee $fee, int $ticket, string $post_type = 'post' ): Model {
		// Create the relationship model with the provided data.
		$relationship = new Relationships_Model(
			[
				'modifier_id' => $fee->id,
				'post_id'     => $ticket,
				'post_type'   => $post_type,
			]
		);

		// Insert the relationship into the repository.
		return tribe( Relationship_Repository::class )->insert( $relationship );
	}

}
