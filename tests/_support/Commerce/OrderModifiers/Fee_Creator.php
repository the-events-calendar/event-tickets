<?php

namespace Tribe\Tickets\Test\Commerce\OrderModifiers;

use Exception;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifier_Relationships as Relationships_Table;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifiers_Meta as Meta_Table;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifiers as Modifiers_Table;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Fee;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Relationships as Relationships_Model;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers_Meta as Meta_Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship as Relationship_Repository;
use TEC\Tickets\Commerce\Values\Float_Value;
use TEC\Common\StellarWP\Models\Contracts\Model;

trait Fee_Creator {

	use Custom_Tables;

	protected static $fee_counter = 0;

	/**
	 * Resets the fee counter.
	 *
	 * This method resets the fee counter to 0.
	 *
	 * @before
	 * @after
	 */
	public function reset_counter() {
		self::$fee_counter = 0;
	}

	/**
	 * Truncates the custom tables.
	 *
	 * This method truncates the custom tables used by the order modifiers.
	 *
	 * @before
	 * @after
	 */
	public function truncate_custom_tables() {
		$this->assertTrue( false !== tribe( Relationships_Table::class )->empty_table() );
		$this->assertTrue( false !== tribe( Meta_Table::class )->empty_table() );
		$this->assertTrue( false !== tribe( Modifiers_Table::class )->empty_table() );
	}

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
		$model = $this->create_fee_relationship( $fee, $ticket_id, get_post_type( $ticket_id ) );

		$this->assertEquals( $fee->id, $model->modifier_id );
		$this->assertEquals( $ticket_id, $model->post_id );
		$this->assertEquals( get_post_type( $ticket_id ), $model->post_type );

		return $fee->id;
	}

	/**
	 * Adds a fee to a ticket.
	 *
	 * This method adds a fee to a ticket by creating a relationship between the fee and the ticket.
	 *
	 * @param int $fee_id    The ID of the fee to add to the ticket.
	 * @param int $ticket_id The ID of the ticket to add the fee to.
	 */
	protected function add_fee_to_ticket( int $fee_id, int $ticket_id ) {
		$fee = Fee::find( $fee_id );
		$model = $this->create_fee_relationship( $fee, $ticket_id, get_post_type( $ticket_id ) );

		$this->assertEquals( $fee_id, $model->modifier_id );
		$this->assertEquals( $ticket_id, $model->post_id );
		$this->assertEquals( get_post_type( $ticket_id ), $model->post_type );
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

		self::$fee_counter++;

		$args = array_merge(
			[
				'sub_type'     => 'flat',
				'raw_amount'   => Float_Value::from_number( 5 ),
				'slug'         => 'test-fee-' . self::$fee_counter,
				'display_name' => 'test fee ' . self::$fee_counter,
				'status'       => 'active',
				'start_time'   => null,
				'end_time'     => null,
			],
			$args
		);

		return Fee::create( $args );
	}

	/**
	 * Creates a fee that applies to all tickets.
	 *
	 * This method creates a fee with the provided arguments and associates it with all tickets.
	 * The fee is created with a default sub_type of 'flat' and a raw_amount of 5.
	 *
	 * @param array $args The arguments to use when creating the fee.
	 *
	 * @return int The ID of the created fee.
	 */
	protected function create_fee_for_all( array $args = [] ): int {
		$fee = $this->create_fee( $args );
		$this->set_fee_application( $fee, 'all' );

		return $fee->id;
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
					'meta_value'        => $applied_to,
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
		$meta = tribe( Meta_Repository::class )->find_by_order_modifier_id( $fee->id );

		if ( $meta->meta_key === 'fee_applied_to' && $meta->meta_value !== 'per' ) {
			throw new Exception( 'You can only use this method to create relationships for fees that apply per ticket.' );
		}

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
