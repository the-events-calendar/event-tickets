<?php
/**
 * Handles the rendering and saving of fee modifiers in the ticket metabox.
 *
 * This class is responsible for managing the fee modifiers section within the
 * ticket metabox in WordPress. It allows the user to select applicable fees
 * for a ticket and saves the relationships between the ticket and the fees.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Admin
 */

namespace TEC\Tickets\Order_Modifiers\Admin;

use TEC\Tickets\Order_Modifiers\Controller;
use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Manager;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifier_Relationship;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers;

/**
 * Class Order_Modifier_Fee_Metabox
 *
 * Manages the fee section in the ticket metabox for applying fee modifiers to tickets.
 * This class handles the UI for displaying fee options, processing form submissions,
 * and managing relationships between tickets and their selected fees.
 *
 * @since TBD
 */
class Order_Modifier_Fee_Metabox {

	/**
	 * The modifier type for this metabox handler.
	 *
	 * @since TBD
	 * @var string
	 */
	protected string $modifier_type = 'fee';

	/**
	 * The modifier strategy instance for handling fee-specific logic.
	 *
	 * @since TBD
	 * @var object
	 */
	protected $modifier_strategy;

	/**
	 * The modifier manager instance to handle relationship updates.
	 *
	 * @since TBD
	 * @var Modifier_Manager
	 */
	protected Modifier_Manager $manager;

	/**
	 * The repository for interacting with the order modifiers table.
	 *
	 * @since TBD
	 * @var Order_Modifiers
	 */
	protected Order_Modifiers $order_modifiers_repository;
	/**
	 * The repository for interacting with the order modifiers relationships.
	 *
	 * @since TBD
	 * @var Order_Modifier_Relationship
	 */
	protected Order_Modifier_Relationship $order_modifiers_relationship_repository;


	/**
	 * Constructor to initialize dependencies and set up the modifier strategy and manager.
	 *
	 * @since TBD
	 */
	public function __construct() {
		// Set up the modifier strategy and manager for handling fees.
		$this->modifier_strategy = tribe( Controller::class )->get_modifier( $this->modifier_type );
		$this->manager           = new Modifier_Manager( $this->modifier_strategy );

		// Set up the order modifiers repository for accessing fee data.
		$this->order_modifiers_repository              = new Order_Modifiers();
		$this->order_modifiers_relationship_repository = new Order_Modifier_Relationship();
	}

	/**
	 * Registers the actions for adding and saving fees in the ticket metabox.
	 *
	 * This method hooks into WordPress actions to add the fee section in the ticket metabox
	 * and to save the selected fees when a ticket is saved.
	 *
	 * @since TBD
	 */
	public function register(): void {
		add_action( 'tribe_events_tickets_metabox_edit_main', [ $this, 'add_fee_section' ], 10, 2 );
		add_action( 'tec_tickets_commerce_after_save_ticket', [ $this, 'save_ticket_fee' ], 10, 3 );
		add_action( 'tec_tickets_commerce_ticket_deleted', [ $this, 'delete_ticket_fee' ] );
	}

	/**
	 * Adds the fee section to the ticket metabox.
	 *
	 * This method retrieves available fees and displays them as checkboxes in the ticket metabox,
	 * allowing users to select applicable fees for the current ticket.
	 *
	 * @since TBD
	 *
	 * @param int      $post_id The post ID of the ticket.
	 * @param int|null $ticket_id The ticket ID.
	 *
	 * @return void
	 */
	public function add_fee_section( int $post_id, ?int $ticket_id ): void {
		$related_ticket_fees = [];

		// If ticket_id is provided, retrieve associated fee relationships for the ticket.
		if ( ! empty( $ticket_id ) ) {
			$related_ticket_fees = $this->order_modifiers_relationship_repository->find_by_post_id( $ticket_id );
		}

		// Ensure that $related_ticket_fees is an array and extract modifier IDs from the related fees for the ticket.
		$related_fee_ids = is_array( $related_ticket_fees ) ? array_map(
			fn( $relationship ) => $relationship->modifier_id,
			$related_ticket_fees
		) : [];

		// Retrieve all fees based on modifier type and specific meta conditions.
		$available_fees = $this->order_modifiers_repository->find_by_modifier_type_and_meta(
			$this->modifier_type,
			'fee_applied_to',
			[ 'per', 'all' ],
			'fee_applied_to',
			'all'
		);

		// Partition the fees into automatically applied fees ('all') and selectable fees (non-'all').
		$automatic_fees = array_filter(
			$available_fees,
			fn( $fee ) => empty( $fee->meta_value ) || $fee->meta_value === 'all'
		);

		$selectable_fees = array_filter(
			$available_fees,
			fn( $fee ) => ! empty( $fee->meta_value ) && $fee->meta_value !== 'all'
		);

		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		// Render the fee section in the ticket metabox.
		$admin_views->template(
			'order_modifiers/classic-fee-edit',
			[
				'post_id'         => $post_id,
				'ticket_id'       => $ticket_id,
				'related_fee_ids' => $related_fee_ids,
				'automatic_fees'  => $automatic_fees,
				'selectable_fees' => $selectable_fees,
			]
		);
	}

	/**
	 * Saves the selected fees for the ticket.
	 *
	 * This method handles the saving of selected fee modifiers when a ticket is saved.
	 * It updates the relationships between the ticket and the selected fees.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id The post ID of the ticket.
	 * @param object $ticket The ticket object.
	 * @param array  $raw_data The raw form data.
	 *
	 * @return void
	 */
	public function save_ticket_fee( int $post_id, object $ticket, array $raw_data ): void {
		// Delete existing relationships for the ticket.
		$this->manager->delete_relationships_by_post( $ticket->ID );

		// Get available fees with specific meta values.
		$fees = $this->order_modifiers_repository->find_by_modifier_type_and_meta(
			$this->modifier_type,
			'fee_applied_to',
			[ 'per', 'all' ],
			'fee_applied_to',
			'all'
		);

		// Filter fees into those automatically applied ('all') and extract their IDs.
		$automatic_fee_ids = array_map(
			function ( $fee ) {
				return $fee->id;
			},
			array_filter(
				$fees,
				function ( $fee ) {
					return empty( $fee->meta_value ) || $fee->meta_value === 'all';
				}
			)
		);

		// Assuming $raw_data['ticket_order_modifier_fees'] is an array (if not, initialize it).
		$raw_data['ticket_order_modifier_fees'] = isset( $raw_data['ticket_order_modifier_fees'] ) && is_array( $raw_data['ticket_order_modifier_fees'] ) ? $raw_data['ticket_order_modifier_fees'] : [];

		// Merge the automatic fee IDs into the ticket_order_modifier_fees array.
		$ticket_order_modifier_fees = array_merge( $raw_data['ticket_order_modifier_fees'], $automatic_fee_ids );
		$fee_ids                    = array_map( 'absint', $ticket_order_modifier_fees ); // Ensure IDs are integers.
		$this->manager->sync_modifier_relationships( $fee_ids, [ $ticket->ID ] );
	}

	/**
	 * Deletes fee relationships for the given ticket when the ticket is deleted.
	 *
	 * This method removes all relationships between the deleted ticket and its associated fees.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return void
	 */
	public function delete_ticket_fee( int $ticket_id ): void {
		// Delete all fee relationships for the ticket.
		$this->manager->delete_relationships_by_post( $ticket_id );
	}
}