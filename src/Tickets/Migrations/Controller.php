<?php
/**
 * Main Migrations Controller.
 *
 * @since TBD
 */

namespace TEC\Tickets\Migrations;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe\Tickets\Admin\Settings as Plugin_Settings;
use Tribe__Settings as Common_Settings;
use Tribe__Settings_Tab as Tab;
use TEC\Common\StellarWP\Migrations\Admin\UI;
use TEC\Common\StellarWP\Migrations\Admin\Provider as Migrations_Admin_Provider;
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
		add_filter( 'stellarwp_migrations_tec_automatic_schedule', '__return_false' );
		migrations()->get_registry()->register( 'rsvp-to-tc', RSVP_To_Tickets_Commerce::class );
		Migrations_Admin_Provider::set_list_url( admin_url( 'admin.php?page=tec-tickets-settings&tab=migrations' ) );
		Migrations_Admin_Provider::set_parent_page( 'tec-tickets-settings' );
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
		remove_filter( 'stellarwp_migrations_tec_automatic_schedule', '__return_false' );
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
		remove_action( 'tribe_settings_form_element_open', [ tribe( Common_Settings::class ), 'settings_form_element_open' ] );
		remove_action( 'tribe_settings_form_element_close', [ tribe( Common_Settings::class ), 'settings_form_element_close' ] );
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
			'priority'         => 37,
			'display_callback' => static function (): void {
				$ui = tribe( UI::class );
				$ui->set_additional_params(
					[
						'tab' => 'migrations',
					]
				);

				// Filter the migration query to show only migrations tagged with 'event-tickets'.
				$add_tags = static function ( array $filters ): array {
					$filters['tags'] ??= [];
					$filters['tags'][] = 'event-tickets';

					return $filters;
				};
				?>
				<?php
				// Hide the tag search field and individual migration tags using inline CSS
				// to avoid overriding the StellarWP migrations templates. Strategy requested
				// these fields to be hidden from the Event Tickets migrations tab.
				?>
				<style>
					.stellarwp-migrations-filters__row,
					.stellarwp-migration-card__tags {
						display: none !important;
					}
					.tickets_page_tec-tickets-settings .tec-settings-form.tec-settings-form__migrations-tab--active {
						padding-top: 0;
					}
				</style>
				<div class="tec-settings-form tec-settings-form__migrations-tab--active" style="grid-template-columns: 1fr;">
					<?php
					add_filter( 'stellarwp_migrations_tec_filters', $add_tags );
					$ui->render_list();
					remove_filter( 'stellarwp_migrations_tec_filters', $add_tags );
					?>
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
