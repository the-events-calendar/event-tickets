<?php
/**
 * ET Hub Resource Data Class
 *
 * This file defines the ET_Hub_Resource_Data class, which implements
 * the Help_Hub_Data_Interface and provides Event Tickets specific
 * resources, FAQs, and settings for the Help Hub functionality.
 *
 * @since   TBD
 * @package TEC\Events\Admin\Help_Hub
 */

namespace TEC\Tickets\Admin\Help_Hub;

use TEC\Common\Admin\Help_Hub\Resource_Data\Help_Hub_Data_Interface;
use TEC\Common\Admin\Help_Hub\Section_Builder\Link_Section_Builder;
use TEC\Common\Telemetry\Telemetry;
use Tribe__Main;
use Tribe__PUE__Checker;

/**
 * Class TEC_Hub_Resource_Data
 *
 * Implements the Help_Hub_Data_Interface, offering resources specific
 * to The Events Calendar, including FAQs, common issues, and customization guides.
 *
 * @since   TBD
 * @package TEC\Events\Admin\Help_Hub
 */
class ET_Hub_Resource_Data implements Help_Hub_Data_Interface {

	/**
	 * Holds the URLs for the necessary icons.
	 *
	 * @since TBD
	 * @var array
	 */
	protected array $icons = [];

	/**
	 * The body class array that styles the admin page.
	 *
	 * @var array
	 */
	protected array $admin_page_body_classes = [ 'tribe_events_page_tec-events-settings' ];

	/**
	 * Constructor.
	 *
	 * Initializes the icons array with URLs.
	 *
	 * @since TBD
	 */
	public function __construct() {
		$origin ??= Tribe__Main::instance();

		$this->icons = [
			'tec_icon'     => tribe_resource_url( 'images/logo/event-tickets.svg', false, null, $origin ),
			'ea_icon'      => tribe_resource_url( 'images/logo/event-aggregator.svg', false, null, $origin ),
			'fbar_icon'    => tribe_resource_url( 'images/logo/filterbar.svg', false, null, $origin ),
			'article_icon' => tribe_resource_url( 'images/icons/file-text1.svg', false, null, $origin ),
			'stars_icon'   => tribe_resource_url( 'images/icons/stars.svg', false, null, $origin ),
			'chat_icon'    => tribe_resource_url( 'images/icons/chat-bubble.svg', false, null, $origin ),
		];

		$this->add_hooks();
	}

	/**
	 * Registers hooks for the Help Hub Resource Data class.
	 *
	 * This method registers filters and actions required for the Help Hub,
	 * such as adding custom body classes to the Help Hub page.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function add_hooks(): void {
		add_filter( 'tec_help_hub_body_classes', [ $this, 'add_admin_body_classes' ] );
		add_filter( 'tec_help_hub_resources_description', [ $this, 'add_resources_description' ] );
		add_filter( 'tec_help_hub_support_title', [ $this, 'add_support_description' ] );
	}

	/**
	 * Add resources description
	 *
	 * @since TBD
	 *
	 * @param string $description The default resources description.
	 *
	 * @return string The modified resources description.
	 */
	public function add_resources_description( $description ) {
		return _x( 'Help on setting up, customizing, and troubleshooting your tickets.', 'Help Hub resources description', 'event-tickets' );
	}

	/**
	 * Add support description
	 *
	 * @since TBD
	 *
	 * @param string $title The default support title.
	 *
	 * @return string The modified support title.
	 */
	public function add_support_description( $title ) {
		return _x( 'Help on setting up, customizing, and troubleshooting your tickets.', 'Help Hub resources description', 'event-tickets' );
	}

	/**
	 * Adds custom body classes for the Help Hub page.
	 *
	 * This method allows the addition of `$admin_page_body_classes` to
	 * the list of body classes for the Help Hub page.
	 *
	 * @since TBD
	 *
	 * @param array $classes The current array of body classes.
	 *
	 * @return array Modified array of body classes.
	 */
	public function add_admin_body_classes( array $classes ): array {
		return array_merge( $classes, $this->admin_page_body_classes );
	}

	/**
	 * Creates an array of resource sections with relevant content for each section.
	 *
	 * Each section can be filtered independently or as a complete set.
	 *
	 * @since TBD
	 *
	 * @return array The filtered resource sections array.
	 */
	public function create_resource_sections(): array {
		/** @var Link_Section_Builder $builder */
		$builder = tribe( Link_Section_Builder::class );

		$this->add_getting_started_section( $builder );
		$this->add_tickets_rsvps_section( $builder );
		$this->add_attendee_management_section( $builder );
		$this->add_ticket_emails_section( $builder );

		return $builder::get_all_sections();
	}

	/**
	 * Adds the "Getting Started" section.
	 *
	 * @since TBD
	 *
	 * @param Link_Section_Builder $builder The section builder instance.
	 */
	private function add_getting_started_section( Link_Section_Builder $builder ): void {
		$builder::make(
			_x( 'Getting started guides', 'Section title', 'event-tickets' ),
			'getting_started_guides'
		)
			->set_description( _x( 'Learn how to get started and configure the plugin for your WordPress site.', 'Section description', 'event-tickets' ) )
			->add_link(
				_x( 'Getting Started with Event Tickets', 'Getting started article', 'event-tickets' ),
				'https://evnt.is/1aot',
				$this->get_icon_url( 'tec_icon' )
			)
			->build();
	}

	/**
	 * Adds the "Tickets & RSVPs" section.
	 *
	 * @since TBD
	 *
	 * @param Link_Section_Builder $builder The section builder instance.
	 */
	private function add_tickets_rsvps_section( Link_Section_Builder $builder ): void {
		$builder::make(
			_x( 'Tickets & RSVPs', 'Section title', 'event-tickets' ),
			'tickets_rsvps'
		)
			->set_description( _x( 'Now that you\'re set up, you\'re ready to create your first ticket or RSVP for an event!', 'Section description', 'event-tickets' ) )
			->add_link(
				_x( 'Using RSVPs', 'RSVPs article', 'event-tickets' ),
				'https://evnt.is/1aox',
				$this->get_icon_url( 'article_icon' )
			)
			->add_link(
				_x( 'Setting Up E-Commerce for Selling Tickets', 'E-Commerce article', 'event-tickets' ),
				'https://evnt.is/1ap0',
				$this->get_icon_url( 'article_icon' )
			)
			->add_link(
				_x( 'Creating Tickets', 'Creating tickets article', 'event-tickets' ),
				'https://evnt.is/1ap2',
				$this->get_icon_url( 'article_icon' )
			)
			->add_link(
				_x( 'Moving Tickets', 'Moving tickets article', 'event-tickets' ),
				'https://evnt.is/ap10',
				$this->get_icon_url( 'article_icon' )
			)
			->build();
	}

	/**
	 * Adds the "Attendee Management" section.
	 *
	 * @since TBD
	 *
	 * @param Link_Section_Builder $builder The section builder instance.
	 */
	private function add_attendee_management_section( Link_Section_Builder $builder ): void {
		$builder::make(
			_x( 'Attendee Management', 'Section title', 'event-tickets' ),
			'attendee_management'
		)
			->set_description(
				_x( 'Collect key details during registration, publicly display attendees, and generate reports.', 'Section description', 'event-tickets' )
			)
			->add_link(
				_x( 'Attendee Registration Settings', 'Registration settings article', 'event-tickets' ),
				'https://evnt.is/1ap11',
				$this->get_icon_url( 'article_icon' )
			)
			->add_link(
				_x( 'Enabling Attendee Information for Tickets', 'Attendee information article', 'event-tickets' ),
				'https://evnt.is/1ap12',
				$this->get_icon_url( 'article_icon' )
			)
			->add_link(
				_x( 'Managing Orders and Attendees', 'Managing orders article', 'event-tickets' ),
				'https://evnt.is/1aoy',
				$this->get_icon_url( 'article_icon' )
			)
			->add_link(
				_x( 'Refunding and Canceling Ticket Orders', 'Refunding tickets article', 'event-tickets' ),
				'https://evnt.is/1ars',
				$this->get_icon_url( 'article_icon' )
			)
			->build();
	}

	/**
	 * Adds the "Ticket Emails" section.
	 *
	 * @since TBD
	 *
	 * @param Link_Section_Builder $builder The section builder instance.
	 */
	private function add_ticket_emails_section( Link_Section_Builder $builder ): void {
		$builder::make(
			_x( 'Ticket Emails', 'Section title', 'event-tickets' ),
			'ticket_emails'
		)
			->set_description(
				_x( 'Learn all about how to customize the ticket email without any coding knowledge.', 'Section description', 'event-tickets' )
			)
			->add_link(
				_x( 'Create Custom Email Marketing', 'Email marketing article', 'event-tickets' ),
				'https://evnt.is/1ap13',
				$this->get_icon_url( 'article_icon' )
			)
			->add_link(
				_x( 'All About Event Ticket Emails', 'Ticket emails article', 'event-tickets' ),
				'https://evnt.is/1ap14',
				$this->get_icon_url( 'article_icon' )
			)
			->add_link(
				_x( 'Customizing the Ticket Email', 'Email customization article', 'event-tickets' ),
				'https://evnt.is/1ap15',
				$this->get_icon_url( 'article_icon' )
			)
			->build();
	}

	/**
	 * Retrieves the URL for a specified icon.
	 *
	 * @since TBD
	 *
	 * @param string $icon_name The name of the icon to retrieve.
	 *
	 * @return string The URL of the specified icon, or an empty string if the icon does not exist.
	 */
	public function get_icon_url( string $icon_name ): string {
		return $this->icons[ $icon_name ] ?? '';
	}

	/**
	 * Get the license validity and telemetry opt-in status.
	 *
	 * @since TBD
	 *
	 * @return array Contains 'has_valid_license' and 'is_opted_in' status.
	 */
	public function get_license_and_opt_in_status(): array {
		$has_valid_license = Tribe__PUE__Checker::is_any_license_valid();
		$common_telemetry  = tribe( Telemetry::class );
		$is_opted_in       = $common_telemetry->calculate_optin_status();

		return [
			'has_valid_license' => $has_valid_license,
			'is_opted_in'       => $is_opted_in,
		];
	}
}
