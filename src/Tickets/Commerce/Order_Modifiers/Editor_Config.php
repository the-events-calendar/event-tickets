<?php
/**
 * Editor configuration for order modifiers.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers;

use TEC\Common\Contracts\Container;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Fee_Modifier_Manager as Manager;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship as Relationships;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Fees as Modifiers;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Fee_Types;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Editor_Config
 *
 * @since TBD
 */
class Editor_Config extends Controller_Contract {

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
	 * @param Container     $container     The DI container.
	 * @param Modifiers     $modifiers     The repository for interacting with the order modifiers.
	 * @param Relationships $relationships The repository for interacting with the order modifiers relationships.
	 * @param Manager       $manager       The manager for the order modifiers.
	 */
	public function __construct(
		Container $container,
		Modifiers $modifiers,
		Relationships $relationships,
		Manager $manager
	) {
		parent::__construct( $container );
		$this->modifiers_repository = $modifiers;
		$this->relationships        = $relationships;
		$this->manager              = $manager;
	}

	/**
	 * Registers the class with WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void The method does not return any value.
	 */
	public function do_register(): void {
		add_filter( 'tribe_editor_config', $this->get_filter_editor_config_callback(), 20 );
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tribe_editor_config', $this->get_filter_editor_config_callback(), 20 );
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

	/**
	 * Gets the callback for filtering the editor configuration.
	 *
	 * @since TBD
	 *
	 * @return callable The callback for filtering the editor configuration.
	 */
	protected function get_filter_editor_config_callback(): callable {
		static $callback = null;
		if ( null === $callback ) {
			$callback = fn( array $config ) => $this->filter_editor_config( $config );
		}

		return $callback;
	}
}
