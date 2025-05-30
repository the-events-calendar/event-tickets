<?php
/**
 * Handles the rendering and saving of fee modifiers in the ticket metabox.
 *
 * This class is responsible for managing the fee modifiers section within the
 * ticket metabox in WordPress. It allows the user to select applicable fees
 * for a ticket and saves the relationships between the ticket and the fees.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Admin
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Admin;

use TEC\Tickets\Commerce\Order_Modifiers\Controller;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Fee_Modifier_Manager as Modifier_Manager;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Fees as FeesRepository;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Fee_Types;
use Tribe__Tickets__Admin__Views as Admin_Views;
use Tribe__Tickets__Main as Main;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;
use TEC\Tickets\Commerce\Module;
use TEC\Common\Contracts\Container;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Asset;
use TEC\Common\StellarWP\Assets\Assets;
use WP_Post;
use Exception;
use TEC\Tickets\Seating\Logging;
use Tribe__Tickets__Commerce__Currency as Currency;

/**
 * Class Order_Modifier_Fee_Metabox
 *
 * Manages the fee section in the ticket metabox for applying fee modifiers to tickets.
 * This class handles the UI for displaying fee options, processing form submissions,
 * and managing relationships between tickets and their selected fees.
 *
 * @since 5.18.0
 */
class Order_Modifier_Fee_Metabox extends Controller_Contract {

	use Fee_Types;
	use Logging;

	/**
	 * The modifier type for this metabox handler.
	 *
	 * @since 5.18.0
	 * @var string
	 */
	protected string $modifier_type = 'fee';

	/**
	 * The modifier strategy instance for handling fee-specific logic.
	 *
	 * @since 5.18.0
	 * @var object
	 */
	protected $modifier_strategy;

	/**
	 * The modifier manager instance to handle relationship updates.
	 *
	 * @since 5.18.0
	 * @var Modifier_Manager
	 */
	protected Modifier_Manager $manager;

	/**
	 * The repository for interacting with the order modifiers relationships.
	 *
	 * @since 5.18.0
	 * @var Order_Modifier_Relationship
	 */
	protected Order_Modifier_Relationship $order_modifiers_relationship_repository;

	/**
	 * The order modifiers controller.
	 *
	 * @since 5.19.1
	 * @var Controller
	 */
	protected Controller $controller;

	/**
	 * Constructor to initialize dependencies and set up the modifier strategy and manager.
	 *
	 * @since 5.18.0
	 *
	 * @param Container                   $container                   The DI container.
	 * @param Controller                  $controller                  The order modifiers controller.
	 * @param Modifier_Manager            $manager                     The modifier manager instance.
	 * @param Order_Modifier_Relationship $order_modifier_relationship The repository for order modifier relationships.
	 * @param FeesRepository              $fees_repository             The repository for order modifiers of type 'fee'.
	 */
	public function __construct(
		Container $container,
		Controller $controller,
		Modifier_Manager $manager,
		Order_Modifier_Relationship $order_modifier_relationship,
		FeesRepository $fees_repository
	) {
		parent::__construct( $container );
		// Set up the modifier strategy and manager for handling fees.
		$this->controller = $controller;
		$this->manager    = $manager;

		// Set up the order modifiers repository for accessing fee data.
		$this->order_modifiers_relationship_repository = $order_modifier_relationship;

		add_action( 'init', [ $this, 'set_modifier_strategy' ] );
	}

	/**
	 * Sets the modifier strategy for applying fees.
	 *
	 * @since 5.19.1
	 */
	public function set_modifier_strategy() {
		$this->modifier_strategy = $this->controller->get_modifier( $this->modifier_type );
	}

	/**
	 * Registers the actions for adding and saving fees in the ticket metabox.
	 *
	 * This method hooks into WordPress actions to add the fee section in the ticket metabox
	 * and to save the selected fees when a ticket is saved.
	 *
	 * @since 5.18.0
	 */
	public function do_register(): void {
		add_action( 'tribe_events_tickets_metabox_edit_main', [ $this, 'add_fee_section' ], 30, 3 );
		add_action( 'tec_tickets_commerce_after_save_ticket', [ $this, 'save_ticket_fee' ], 10, 3 );
		add_action( 'tec_tickets_commerce_ticket_deleted', [ $this, 'delete_ticket_fee' ] );
		$this->register_assets();
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tribe_events_tickets_metabox_edit_main', [ $this, 'add_fee_section' ], 30 );
		remove_action( 'tec_tickets_commerce_after_save_ticket', [ $this, 'save_ticket_fee' ] );
		remove_action( 'tec_tickets_commerce_ticket_deleted', [ $this, 'delete_ticket_fee' ] );
		Assets::init()->remove( 'order-modifiers-fees-js' );
	}

	/**
	 * Enqueue custom JS for the Order Modifiers Fee functionality.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	protected function register_assets(): void {
		Asset::add(
			'order-modifiers-fees-js',
			'admin/order-modifiers/fees.js',
			Main::VERSION
		)
			->add_to_group_path( Main::class )
			->set_condition( [ $this, 'should_enqueue_assets' ] )
			->set_dependencies( 'jquery', 'tribe-dropdowns', 'tribe-select2' )
			->enqueue_on( 'admin_enqueue_scripts' )
			->add_to_group( 'tec-tickets-order-modifiers' )
			->register();
	}

	/**
	 * Checks if the current context is NOT Block Editor and the post type is ticket-enabled.
	 *
	 * @since 5.18.0
	 *
	 * @return bool Whether the assets should be enqueued or not.
	 */
	public function should_enqueue_assets() {
		$ticketable_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		if ( empty( $ticketable_post_types ) ) {
			return false;
		}

		$post = get_post();

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		return is_admin() &&
				in_array( $post->post_type, $ticketable_post_types, true ) &&
				! get_current_screen()->is_block_editor();
	}

	/**
	 * Adds the fee section to the ticket metabox.
	 *
	 * This method retrieves available fees and displays them as checkboxes in the ticket metabox,
	 * allowing users to select applicable fees for the current ticket.
	 *
	 * @since 5.18.0
	 *
	 * @param int      $post_id The post ID of the ticket.
	 * @param int|null $ticket_id The ticket ID.
	 * @param string   $ticket_type The ticket type.
	 *
	 * @return void
	 */
	public function add_fee_section( int $post_id, ?int $ticket_id, string $ticket_type ): void {
		// Bail if no ticket!
		if ( ! in_array( $ticket_type, [ 'default', 'ticket', 'series_pass' ], true ) ) {
			return;
		}

		$provider = Tickets::get_event_ticket_provider( $post_id );

		if ( Module::class !== $provider ) {
			return;
		}

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
		$available_fees = $this->get_all_fees();

		// Partition the fees into automatically applied fees ('all') and selectable fees (non-'all').
		$automatic_fees  = $this->get_automatic_fees( $available_fees );
		$selectable_fees = $this->get_selectable_fees( $available_fees );

		/** @var Admin_Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		// Render the fee section in the ticket metabox.
		$admin_views->template(
			'order_modifiers/classic-fee-edit',
			[
				'post_id'         => $post_id,
				'ticket_id'       => $ticket_id,
				'related_fee_ids' => $related_fee_ids,
				'automatic_fees'  => array_map( [ $this, 'format_fee_for_display' ], $automatic_fees ),
				'selectable_fees' => array_map( [ $this, 'format_fee_for_display' ], $selectable_fees ),
			]
		);
	}

	/**
	 * Formats the fees' amount for display based on TicketsCommerce Currency Settings.
	 *
	 * @since 5.18.0
	 *
	 * @param object $fee The fee object.
	 *
	 * @return object The formatted fee object.
	 */
	public function format_fee_for_display( object $fee ): object {
		if ( ! isset( $fee->raw_amount ) ) {
			return $fee;
		}

		// Format the amount based on the Currency settings for the Tickets Commerce module.
		$fee->raw_amount = tribe( Currency::class )->get_formatted_currency( (string) $fee->raw_amount, null, Module::class );

		return $fee;
	}

	/**
	 * Saves the selected fees for the ticket.
	 *
	 * This method handles the saving of selected fee modifiers when a ticket is saved.
	 * It updates the relationships between the ticket and the selected fees.
	 *
	 * @since 5.18.0
	 *
	 * @param int           $post_id  The post ID of the ticket.
	 * @param Ticket_Object $ticket   The ticket object.
	 * @param array         $raw_data The raw form data.
	 *
	 * @return void
	 */
	public function save_ticket_fee( int $post_id, Ticket_Object $ticket, array $raw_data ): void {
		$fee_ids = array_map(
			function ( $fee ) {
				if ( is_object( $fee ) && ! empty( $fee->id ) && is_numeric( $fee->id ) ) {
					return (int) filter_var( $fee->id, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0 ] ] );
				} else {
					return ! is_numeric( $fee ) ? false : (int) filter_var( $fee, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0 ] ] );
				}
			},
			(array) ( $raw_data['ticket_order_modifier_fees'] ?? [] )
		);

		$fee_ids = array_filter( array_unique( $fee_ids ), static fn ( $fee_id ) => $fee_id && is_int( $fee_id ) && $fee_id > 0 );

		try {
			$this->update_fees_for_ticket( $ticket->ID, $fee_ids );
		} catch ( Exception $e ) {
			$this->log_error(
				'Unrecognized fee id was given for a relationship with ticket.',
				[
					'source'    => __METHOD__,
					'error'     => $e->getMessage(),
					'fee_ids'   => $fee_ids,
					'ticket_id' => $ticket->ID,
				]
			);
		}
	}

	/**
	 * Deletes fee relationships for the given ticket when the ticket is deleted.
	 *
	 * This method removes all relationships between the deleted ticket and its associated fees.
	 *
	 * @since 5.18.0
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return void
	 */
	public function delete_ticket_fee( int $ticket_id ): void {
		$this->manager->delete_relationships_by_post( $ticket_id );
	}
}
