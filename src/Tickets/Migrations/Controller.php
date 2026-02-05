<?php
/**
 * Main Migrations Controller.
 *
 * @since TBD
 */

namespace TEC\Tickets\Migrations;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe\Tickets\Admin\Settings as Plugin_Settings;
use Tribe__Settings_Tab as Tab;
use TEC\Common\StellarWP\Migrations\Admin\UI;
use function TEC\Common\StellarWP\Migrations\migrations;

/**
 * Main controller for Migrations functionality.
 *
 * @since TBD
 */
class Controller extends Controller_Contract {
	/**
	 * Registers the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		migrations()->get_registry()->register( 'rsvp-to-tc', RSVP_To_Tickets_Commerce::class );
		add_action( 'tribe_settings_do_tabs', [ $this, 'register_migrations_tab' ], 20 );
		add_action( 'tribe_settings_below_tabs_tab_migrations', [ $this, 'remove_form_element_open_and_close' ] );
		add_filter( 'tec_tickets_settings_tabs_ids', [ $this, 'settings_add_migrations_tab_id' ] );
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		migrations()->get_registry()->offsetUnset( 'rsvp-to-tc' );
		remove_action( 'tribe_settings_do_tabs', [ $this, 'register_migrations_tab' ], 20 );
		remove_action( 'tribe_settings_below_tabs_tab_migrations', [ $this, 'remove_form_element_open_and_close' ] );
		remove_filter( 'tec_tickets_settings_tabs_ids', [ $this, 'settings_add_migrations_tab_id' ] );
	}

	/**
	 * Remove form element open and close actions for the migrations tab.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function remove_form_element_open_and_close(): void {
		remove_action( 'tribe_settings_form_element_open', [ tribe( Plugin_Settings::class ), 'settings_form_element_open' ] );
		remove_action( 'tribe_settings_form_element_close', [ tribe( Plugin_Settings::class ), 'settings_form_element_close' ] );
	}

	/**
	 * Registers the migrations tab.
	 *
	 * @since TBD
	 *
	 * @param string $admin_page The admin page ID.
	 *
	 * @return void
	 */
	public function register_migrations_tab( $admin_page ): void {
		if ( Plugin_Settings::$settings_page_id !== $admin_page ) {
			return;
		}

		$tab_settings = [
			'priority'         => 25,
			'display_callback' => static function (): void {
				$ui = tribe( UI::class );
				$ui->set_additional_params(
					[
						'tab' => 'migrations',
					]
				);
				?>
				<div class="tec-settings-form tec-settings-form__migrations-tab--active" style="grid-template-columns: 1fr;">
					<?php $ui->render_list(); ?>
				</div>
				<?php
			},
			'show_save'        => false,
		];

		new Tab( 'migrations', esc_html__( 'Migrations', 'event-tickets' ), $tab_settings );
	}

	/**
	 * Adds the migrations tab id to the list of tabs.
	 *
	 * @since TBD
	 *
	 * @param array $tabs The list of tabs.
	 *
	 * @return array
	 */
	public function settings_add_migrations_tab_id( array $tabs ): array {
		$tabs[] = 'migrations';

		return $tabs;
	}
}
