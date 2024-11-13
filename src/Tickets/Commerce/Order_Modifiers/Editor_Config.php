<?php
/**
 * Editor configuration for order modifiers.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers;

use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Fee;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Modifier_Manager as Manager;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship as Relationships;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers as Modifiers;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Fee_Types;
use TEC\Tickets\Registerable;

/**
 * Class Editor_Config
 *
 * @since TBD
 */
class Editor_Config implements Registerable {

	use Fee_Types;

	/**
	 * Relationships repository.
	 *
	 * @var Relationships
	 */
	private Relationships $relationships;

	/**
	 * Modifiers manager repository.
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Editor_Config constructor.
	 *
	 * @since TBD
	 *
	 * @param ?Modifiers     $modifiers     The repository for interacting with the order modifiers.
	 * @param ?Relationships $relationships The repository for interacting with the order modifiers relationships.
	 * @param ?Manager       $manager       The manager for the order modifiers.
	 */
	public function __construct(
		?Modifiers $modifiers = null,
		?Relationships $relationships = null,
		?Manager $manager = null
	) {
		$this->modifiers_repository = $modifiers ?? new Modifiers( 'fee' );
		$this->relationships        = $relationships ?? new Relationships();
		$this->manager              = $manager ?? new Manager( new Fee() );
	}

	/**
	 * Registers the class with WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void The method does not return any value.
	 */
	public function register(): void {
		add_filter(
			'tribe_editor_config',
			fn( array $config ) => $this->filter_editor_config( $config ),
			20
		);
	}

	/**
	 * Filters the editor configuration to add fee data.
	 *
	 * @since TBD
	 *
	 * @param array $config The existing editor configuration.
	 *
	 * @return array The modified editor configuration.
	 */
	protected function filter_editor_config( array $config ) {
		// Get the existing tickets array or an empty array.
		$tickets = $config['tickets'] ?? [];

		// Set up the fee data.
		$fees            = $this->get_all_fees();
		$tickets['fees'] = [
			'automatic_fees' => $this->get_automatic_fees( $fees ),
			'available_fees' => $this->get_selectable_fees( $fees ),
		];

		// Replace the existing tickets array with the new one.
		$config['tickets'] = $tickets;

		return $config;
	}
}
