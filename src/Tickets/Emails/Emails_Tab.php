<?php
/**
 * Handles registering and setup for the Tickets Emails settings tab.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;
use TEC\Tickets\Commerce\Shortcodes\Success_Shortcode;
use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway as Gateway;
use TEC\Tickets\Settings as Tickets_Commerce_Settings;
use Tribe\Tickets\Admin\Settings as Plugin_Settings;
use \tad_DI52_ServiceProvider;
use \Tribe__Template;
use Tribe__Tickets__Main;

/**
 * Class Emails_Tab
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Emails
 */
class Emails_Tab extends tad_DI52_ServiceProvider {

	/**
	 * Slug for the tab.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $slug = 'emails';

	/**
	 * @inheritdoc
	 */
	public function register() {
		$this->container->singleton( static::class, $this );
	}

	/**
	 * Create the Tickets Commerce Emails Settings Tab.
	 *
	 * @since  TBD
	 */
	public function register_tab( $admin_page ) {
		if ( ! empty( $admin_page ) && Plugin_Settings::$settings_page_id !== $admin_page ) {
			return;
		}

		$tab_settings = [
			'priority'  => 25,
			'fields'    => $this->get_fields(),
			'show_save' => true,
		];

		$tab_settings = apply_filters( 'tec_tickets_commerce_emails_tab_settings', $tab_settings );

		new \Tribe__Settings_Tab( static::$slug, esc_html__( 'Emails', 'event-tickets' ), $tab_settings );
	}

	/**
	 * Add the emails tab to the list of tab ids for the Tickets settings.
	 *
	 * @since TBD
	 *
	 * @param array $tabs Array containing the tabs ids for Event Tickets settings.
	 *
	 * @return array $tabs Array containing the tabs ids for Event Tickets settings.
	 */
	public function settings_add_tab_id( $tabs ) {
		$tabs[] = static::$slug;

		return $tabs;
	}

	/**
	 * Gets the template instance used to setup the rendering html.
	 *
	 * @since TBD
	 *
	 * @return Tribe__Template
	 */
	public function get_template() {
		if ( empty( $this->template ) ) {
			$this->template = new Tribe__Template();
			$this->template->set_template_origin( Tribe__Tickets__Main::instance() );
			$this->template->set_template_folder( 'src/admin-views/settings' );
			$this->template->set_template_context_extract( true );
		}

		return $this->template;
	}

	/**
	 * Gets the URL for the Emails Tab.
	 *
	 * @since TBD
	 *
	 * @param array $args Which query args we are adding.
	 *
	 * @return string
	 */
	public function get_url( array $args = [] ): string {
		// Force the payment tab.
		$args['tab'] = static::$slug;

		// Use the settings page get_url to build the URL.
		return tribe( Plugin_Settings::class )->get_url( $args );
	}

	/**
	 * Gets the top level settings for Tickets Commerce.
	 *
	 * @since TBD
	 *
	 * @return array[]
	 */
	public function get_fields(): array {

		$fields = [];
		$fields['tribe-form-content-start'] = [
			'type' => 'html',
			'html' => '<div class="tribe-settings-form-wrap">',
		];
		$fields['tribe-tickets-emails-header'] = [
			'type' => 'html',
			'html' => '<h2>' . esc_html__( 'Tickets Emails', 'event-tickets' ) . '</h2>',
		];
		$kb_link_html = sprintf( '<a href="%s" target="_blank" rel="nofollow">%s</a>',
			'https://www.theeventscalendar.com',
			esc_html__( 'Knowledgebase', 'event-tickets' )
		);
		$description_text = sprintf(
			// Translators: %s Link to knowledgebase article.
			esc_html__( 'Customize your customer communications when tickets are purchased, RSVPs are submitted, and for Tickets Commerce order notifications.  Learn More about Tickets Commerce communications in our %s.', 'event-tickets' ),
			$kb_link_html
		);
		$fields['tribe-tickets-emails-description'] = [
			'type' => 'html',
			'html' => sprintf( '<p>%s</p>', $description_text ),
		];

		/**
		 * Hook to modify the settings fields for Tickets Emails.
		 *
		 * @since TBD
		 *
		 * @param array[] $fields Top level settings.
		 */
		return apply_filters( 'tec_tickets_emails_settings_fields', $fields );
	}
}
