<?php
/**
 * Handles registering and setup for the Tickets Emails settings tab.
 *
 * @since 5.5.6
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails\Admin;

use TEC\Tickets\Emails\Email_Abstract;
use TEC\Tickets\Emails\Email_Handler;
use Tribe\Tickets\Admin\Settings as Plugin_Settings;
use Tribe__Settings_Tab;
use Tribe__Template;
use Tribe__Tickets__Main;

/**
 * Class Emails_Tab
 *
 * @since   5.5.6
 *
 * @package TEC\Tickets\Emails
 */
class Emails_Tab {

	/**
	 * Slug for the tab.
	 *
	 * @since 5.5.6
	 *
	 * @var string
	 */
	public static $slug = 'emails';

	/**
	 * Holder for template object.
	 *
	 * @since 5.5.9
	 *
	 * @var null|Tribe__Template
	 */
	protected $template;

	/**
	 * Key to determine current section.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	public static $key_current_section = 'tec_tickets_emails_current_section';

	/**
	 * Create the Tickets Commerce Emails Settings Tab.
	 *
	 * @since  5.5.6
	 * @since  5.8.4 Return the registered tab.
	 *
	 * @param string $admin_page Page ID of current admin page.
	 *
	 * @return Tribe__Settings_Tab|null The registered tab, or `null` if the tab is not for the current page.
	 */
	public function register_tab( $admin_page ): ?Tribe__Settings_Tab {
		if ( ! empty( $admin_page ) && Plugin_Settings::$settings_page_id !== $admin_page ) {
			return null;
		}

		$tab_settings = [
			'priority'  => 25,
			'fields'    => $this->get_fields(),
			'show_save' => true,
		];

		$tab_settings = apply_filters( 'tec_tickets_commerce_emails_tab_settings', $tab_settings );

		return new Tribe__Settings_Tab( static::$slug, esc_html__( 'Emails', 'event-tickets' ), $tab_settings );
	}

	/**
	 * Add the emails tab to the list of tab ids for the Tickets settings.
	 *
	 * @since 5.5.6
	 *
	 * @param  array $tabs Current array of tabs ids.
	 *
	 * @return array $tabs Filtered array of tabs ids.
	 */
	public function settings_add_tab_id( array $tabs ): array {
		$tabs[] = static::$slug;

		return $tabs;
	}

	/**
	 * Gets the template instance used to setup the rendering html.
	 *
	 * @since 5.5.6
	 *
	 * @return Tribe__Template
	 */
	public function get_template(): Tribe__Template {
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
	 * @since 5.5.6
	 *
	 * @param array $args Which query args we are adding.
	 *
	 * @return string
	 */
	public function get_url( array $args = [] ): string {
		// Force the `emails` tab.
		$args['tab'] = static::$slug;

		// Use the settings page get_url to build the URL.
		return tribe( Plugin_Settings::class )->get_url( $args );
	}

	/**
	 * Determine if is on "tab".
	 *
	 * @since 5.5.7
	 *
	 * @return boolean True when on `emails` tab.
	 */
	public function is_on_tab(): bool {
		$settings = tribe( Plugin_Settings::class );

		return $settings->is_on_tab( self::$slug );
	}

	/**
	 * Gets the top level settings for Tickets Commerce.
	 *
	 * @since 5.5.6
	 *
	 * @return array[]
	 */
	public function get_fields(): array {
		// Check to see if we're editing an email, first.
		if ( $this->is_editing_email() ) {
			return $this->get_email_settings();
		}

		$fields = [];
		$fields['tribe-form-content-start'] = [
			'type' => 'html',
			'html' => '<div class="tribe-settings-form-wrap">',
		];
		$fields['tribe-tickets-emails-header'] = [
			'type' => 'html',
			'html' => '<h2 class="tec-tickets__admin-settings-tab-heading">' . esc_html__( 'Tickets Emails', 'event-tickets' ) . '</h2>',
		];
		$kb_link_html = sprintf( '<a href="%s" target="_blank" rel="nofollow">%s</a>',
			'https://evnt.is/event-tickets-emails',
			esc_html__( 'Knowledgebase', 'event-tickets' )
		);

		if ( tribe_installed_before( Tribe__Tickets__Main::class, '5.6.0' ) ) {
			$description_text = sprintf(
				// Translators: %s Link to knowledgebase article.
				esc_html__( 'Customize your customer communications when tickets are purchased, RSVPs are submitted, and for Tickets Commerce order notifications. Enabling Tickets Emails will overwrite any manual customization that has been done to our previous email templates. Learn more about Event Tickets and Tickets Commerce communications in our %s.', 'event-tickets' ),
				$kb_link_html
			);
		} else {
			$description_text = sprintf(
				// Translators: %s Link to knowledgebase article.
				esc_html__( 'Customize your customer communications when tickets are purchased, RSVPs are submitted, and for Tickets Commerce order notifications. Learn more about Event Tickets and Tickets Commerce communications in our %s.', 'event-tickets' ),
				$kb_link_html
			);
		}

		$fields['tribe-tickets-emails-description'] = [
			'type' => 'html',
			'html' => sprintf( '<p>%s</p>', $description_text ),
		];

		/**
		 * Hook to modify the settings fields for Tickets Emails.
		 *
		 * @since 5.5.6
		 *
		 * @param array[] $fields Top level settings.
		 */
		return apply_filters( 'tec_tickets_emails_settings_fields', $fields );
	}

	/**
	 * Check if currently editing email.
	 *
	 * @since 5.5.9
	 *
	 * @param ?Email_Abstract $email
	 *
	 * @return boolean
	 */
	public function is_editing_email( ?Email_Abstract $email = null ): bool {
		// Get `section` query string from URL.
		$editing_email = tribe_get_request_var( 'section' );

		// If email wasn't passed, just return whether the string is empty.
		if ( empty( $email ) ) {
			return ! empty( $editing_email );
		}

		// Otherwise, return whether the supplied email is being edited.
		return $email->id === $editing_email;
	}

	/**
	 * Get email settings.
	 *
	 * @since 5.5.9
	 *
	 * @return array Settings array
	 */
	public function get_email_settings(): array {
		$email_id = tribe_get_request_var( 'section' );
		$email    = tribe( Email_Handler::class )->get_email_by_id( $email_id );

		$back_link = [
			[
				'type' => 'html',
				'html' => $this->get_template()->template( 'back-link',
					[
						'text' => __( 'Back to Email Settings', 'event-tickets' ),
						'url'  => $this->get_url(),
					],
					false ),
			]
		];

		if ( ! $email ) {
			return array_merge( $back_link, [
				[
					'type' => 'html',
					'html' => '<p>' . esc_html__( 'Invalid email id selected.', 'event-tickets' ) . '</p>',
				]
			] );
		}

		$hidden_fields = [
			[
				'type' => 'html',
				'html' => sprintf(
					'<input type="hidden" name="%s" id="%s" value="%s" />',
					esc_attr( static::$key_current_section ),
					esc_attr( static::$key_current_section ),
					esc_attr( $email_id )
				)
			]
		];

		$settings = $email->get_settings();

		return array_merge( $back_link, $settings, $hidden_fields );
	}

	/**
	 * Filters the redirect URL to include section, if applicable.
	 *
	 * @since 5.5.9
	 *
	 * @param string $url URL of redirection.
	 *
	 * @return string
	 */
	public function filter_redirect_url( $url ) {
		if ( ! is_admin() ) {
			return $url;
		}

		$tab  = tribe_get_request_var( 'tab' );
		$page = tribe_get_request_var( 'page' );

		if ( empty( $tab ) || empty( $page ) ) {
			return $url;
		}

		if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			return $url;
		}

		if ( Plugin_Settings::$settings_page_id !== $page ) {
			return $url;
		}

		if ( static::$slug !== $tab ) {
			return $url;
		}

		$email_id = tribe_get_request_var( 'section' );
		if ( empty( $email_id ) ) {
			return $url;
		}

		return add_query_arg( [
			'section'            => $email_id,
		], $url );
	}
}
