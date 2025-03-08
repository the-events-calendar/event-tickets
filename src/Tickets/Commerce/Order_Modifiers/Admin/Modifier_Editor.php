<?php
/**
 * Modifier Editor class.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Admin;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Assets\Assets;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Admin_Page;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Asset_Build;
use TEC\Tickets\Commerce\Utils\Currency;

/**
 * Class Modifier_Editor
 *
 * @since TBD
 */
class Modifier_Editor extends Controller_Contract {

	use Admin_Page;
	use Asset_Build;

	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->register_modifier_editor_assets();
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * Bound implementations should not be removed in this method!
	 *
	 * @since TBD
	 *
	 * @return void Filters and actions hooks added by the controller are be removed.
	 */
	public function unregister(): void {
		Assets::init()->remove( 'tec-tickets-order-modifier-utils' );
	}

	protected function register_modifier_editor_assets() {
		$this->add_asset(
			'tec-tickets-order-modifier-utils',
			'utils.js'
		)
			->register();

		$this->add_asset(
			'tec-tickets-order-modifier-edit-js',
			'admin/modifierEdit.js',
			(string) time()
		)
			->set_condition( fn() => $this->is_on_page() )
			->enqueue_on( 'admin_enqueue_scripts' )
			->set_dependencies(
				'jquery',
				'tribe-validation',
				'tec-tickets-order-modifier-utils',
			)
			->add_localize_script(
				'tec.tickets.orderModifiers.modifierEdit',
				fn() => $this->get_modifier_edit_localized_data()
			)
			->register();
	}

	protected function get_modifier_edit_localized_data(): array {
		$code = Currency::get_currency_code();

		return [
			'currencySymbol'    => Currency::get_currency_symbol( $code ),
			'decimalSeparator'  => Currency::get_currency_separator_decimal( $code ),
			'thousandSeparator' => Currency::get_currency_separator_thousands( $code ),
			'placement'         => Currency::get_currency_symbol_position( $code ),
			'precision'         => Currency::get_currency_precision( $code ),
		];
	}
}
