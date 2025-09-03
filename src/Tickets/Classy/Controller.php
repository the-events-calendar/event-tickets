<?php
/**
 * The main Classy feature controller for Event Tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Classy;
 */

declare( strict_types=1 );

namespace TEC\Tickets\Classy;

use TEC\Common\Classy\Controller as Common_Controller;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Assets\Asset;
use TEC\Tickets\Commerce\Utils\Currency;
use Tribe__Tickets__Main as ET;

/**
 * Class Controller
 *
 * @since TBD
 */
class Controller extends Controller_Contract {
	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		// Register the main assets entry point.
		if ( did_action( 'tec_common_assets_loaded' ) ) {
			$this->register_assets();
		} else {
			add_action( 'tec_common_assets_loaded', [ $this, 'register_assets' ] );
		}

		$this->register_ecp_integrations();
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
		remove_action( 'tec_common_assets_loaded', [ $this, 'register_assets' ] );
		remove_action( 'tec_events_pro_classy_registered', [ $this, 'register_ecp_editor_meta' ] );
	}

	/**
	 * Registers the assets required to extend the Classy application with TEC functionality.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_assets() {
		$post_uses_classy = fn() => $this
			->container
			->get( Common_Controller::class )
			->post_uses_classy( get_post_type() );

		// Register the main Classy script.
		Asset::add(
			'tec-classy-tickets',
			'classy.js'
		)->add_to_group_path( "{$this->get_et_class()}-packages" )
			// @todo this should be dynamic depending on the loading context.
			->enqueue_on( 'enqueue_block_editor_assets' )
			->set_condition( $post_uses_classy )
			->add_dependency( 'tec-classy' )
			->add_to_group( 'tec-classy' )
			->add_localize_script( 'tec.tickets.classy.data', fn() => $this->get_data() )
			->register();

		// Register the main Classy styles.
		Asset::add(
			'tec-classy-tickets-styles',
			'style-classy.css'
		)->add_to_group_path( "{$this->get_et_class()}-packages" )
			// @todo this should be dynamic depending on the loading context.
			->enqueue_on( 'enqueue_block_editor_assets' )
			->set_condition( $post_uses_classy )
			->add_dependency( 'tec-classy-style' )
			->add_to_group( 'tec-classy' )
			->register();
	}

	/**
	 * Get the ET class name.
	 *
	 * @return class-string
	 */
	private function get_et_class(): string {
		return ET::class;
	}

	/**
	 * Get the data to be localized in the Classy script.
	 *
	 * @since TBD
	 *
	 * @return array The data to be localized in the Classy script.
	 */
	private function get_data(): array {
		$code = Currency::get_currency_code();

		/** @var ET $et_main */
		$et_main = $this->container->get( ET::class );

		// todo: Ensure we can allow for Woo currency settings to be used here if needed.
		return [
			'settings' => [
				'currency'        => [
					'code'               => $code,
					'symbol'             => Currency::get_currency_symbol( $code ),
					'decimalSeparator'   => Currency::get_currency_separator_decimal( $code ),
					'thousandsSeparator' => Currency::get_currency_separator_thousands( $code ),
					'position'           => Currency::get_currency_symbol_position( $code ),
					'precision'          => Currency::get_currency_precision( $code ),
				],
				'ticketPostTypes' => $et_main->post_types(),
			],
			'nonces'   => [
				'deleteTicket' => wp_create_nonce( 'remove_ticket_nonce' ),
				'updateTicket' => wp_create_nonce( 'edit_ticket_nonce' ),
				'createTicket' => wp_create_nonce( 'add_ticket_nonce' ),
			],
		];
	}

	/**
	 * Registers the meta fields supported by Event Tickets when Events Pro is active.
	 *
	 * @internal
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_ecp_editor_meta(): void {
		$this->container->make( ECP_Editor_Meta::class )->register();
	}

	/**
	 * Registers Events Pro integrations, if the plugin is active.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_ecp_integrations(): void {
		$this->container->singleton( ECP_Editor_Meta::class );

		if (
			did_action( 'tec_events_pro_classy_registered' )
			|| doing_action( 'tec_events_pro_classy_registered' )
		) {
			$this->register_ecp_editor_meta();
		} else {
			add_action( 'tec_events_pro_classy_registered', [ $this, 'register_ecp_editor_meta' ] );
		}
	}
}
