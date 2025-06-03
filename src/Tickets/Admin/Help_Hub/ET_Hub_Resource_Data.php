<?php
/**
 * ET Hub Resource Data Class
 *
 * This file defines the ET_Hub_Resource_Data class, which implements
 * the Help_Hub_Data_Interface and provides Event Tickets specific
 * resources, FAQs, and settings for the Help Hub functionality.
 *
 * @since 5.24.0
 * @package TEC\Events\Admin\Help_Hub
 */

namespace TEC\Tickets\Admin\Help_Hub;

use TEC\Common\Admin\Help_Hub\Hub;
use TEC\Common\Admin\Help_Hub\Resource_Data\Help_Hub_Data_Interface;
use TEC\Common\Admin\Help_Hub\Section_Builder\Link_Section_Builder;
use TEC\Common\Telemetry\Telemetry;
use Tribe__Main;
use Tribe__PUE__Checker;
use Tribe__Tickets__Main;
use Tribe\Tickets\Admin\Settings;

/**
 * Class ET_Hub_Resource_Data
 *
 * Handles the Help Hub resource data and page registration for Event Tickets.
 *
 * @since 5.24.0
 */
class ET_Hub_Resource_Data implements Help_Hub_Data_Interface {
	/**
	 * The ID of the help hub page.
	 *
	 * @since 5.24.0
	 * @var string
	 */
	const HELP_HUB_PAGE_ID = 'tickets_page_tec-tickets-help-hub';

	/**
	 * The slug of the help hub page.
	 *
	 * @since 5.24.0
	 * @var string
	 */
	const HELP_HUB_SLUG = 'tec-tickets-help-hub';

	/**
	 * Holds the URLs for the necessary icons.
	 *
	 * @since 5.24.0
	 * @var array<string,string>
	 */
	protected array $icons = [];

	/**
	 * The body class array that styles the admin page.
	 *
	 * @since 5.24.0
	 * @var array<string>
	 */
	protected array $admin_page_body_classes = [ 'tribe_events_page_tec-events-settings' ];

	/**
	 * Whether the class has been initialized.
	 *
	 * @since 5.24.0
	 * @var bool
	 */
	protected bool $initialized = false;

	/**
	 * Constructor.
	 *
	 * Sets up the initialization hooks.
	 *
	 * @since 5.24.0
	 */
	public function __construct() {
		add_action( 'load-' . self::HELP_HUB_PAGE_ID, [ $this, 'initialize' ] );
		add_action( 'tec_help_hub_before_iframe_render', [ $this, 'register_with_hub' ] );
	}

	/**
	 * Registers this data instance with the Help Hub.
	 *
	 * @since 5.24.0
	 *
	 * @param Hub $help_hub The current Help Hub instance to register with.
	 *
	 * @return void
	 */
	public function register_with_hub( Hub $help_hub ): void {
		$page = tec_get_request_var( 'page' );
		if ( self::HELP_HUB_PAGE_ID !== $page ) {
			return;
		}
		$this->initialize();
		$help_hub->set_data( $this );
	}

	/**
	 * Initializes the Help Hub Resource Data.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function initialize(): void {

		if ( $this->initialized ) {
			return;
		}

		$origin = Tribe__Main::instance();

		$this->icons = [
			'tec_icon'     => tribe_resource_url( 'images/logo/the-events-calendar.svg', false, null, $origin ),
			'et_icon'      => tribe_resource_url( 'images/logo/event-tickets.svg', false, null, $origin ),
			'ea_icon'      => tribe_resource_url( 'images/logo/event-aggregator.svg', false, null, $origin ),
			'fbar_icon'    => tribe_resource_url( 'images/logo/filterbar.svg', false, null, $origin ),
			'article_icon' => tribe_resource_url( 'images/icons/file-text1.svg', false, null, $origin ),
			'stars_icon'   => tribe_resource_url( 'images/icons/stars.svg', false, null, $origin ),
			'chat_icon'    => tribe_resource_url( 'images/icons/chat-bubble.svg', false, null, $origin ),
		];

		$this->add_hooks();
		$this->initialized = true;
	}

	/**
	 * Registers hooks for the Help Hub Resource Data class.
	 *
	 * This method registers filters and actions required for the Help Hub,
	 * such as adding custom body classes to the Help Hub page.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function add_hooks(): void {
		add_filter( 'tec_help_hub_body_classes', [ $this, 'add_admin_body_classes' ] );
		add_filter( 'tec_help_hub_resources_description', [ $this, 'add_resources_description' ] );
		add_filter( 'tec_help_hub_support_title', [ $this, 'add_support_title' ] );
		add_filter( 'tec_help_hub_support_description', [ $this, 'add_support_description' ] );
		add_filter( 'tec_help_hub_header_logo_src', [ $this, 'add_header_logo_src' ] );
		add_filter( 'tec_help_hub_header_logo_alt', [ $this, 'add_header_logo_alt' ] );
		add_filter( 'tec_help_hub_pages', [ $this, 'add_help_hub_pages' ] );
		add_filter( 'tec_help_hub_telemetry_opt_in_link', [ $this, 'add_telemetry_opt_in_link' ] );
	}
	/**
	 * Filters the telemetry opt-in link to use a custom Help Hub-specific URL.
	 *
	 * @since 5.24.0
	 *
	 * @param string $url The original telemetry opt-in link.
	 *
	 * @return string The filtered telemetry opt-in link.
	 */
	public function add_telemetry_opt_in_link( $url ) {
		if ( ! $this->is_help_hub_page() ) {
			return $url;
		}

		return add_query_arg(
			[
				'page' => 'tec-tickets-settings',
			],
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Add resources description
	 *
	 * @since 5.24.0
	 *
	 * @param string $description The default resources description.
	 *
	 * @return string The modified resources description.
	 */
	public function add_resources_description( $description ): string {
		return _x( 'Help on setting up, customizing, and troubleshooting your tickets.', 'Help Hub resources description', 'event-tickets' );
	}

	/**
	 * Add support description
	 *
	 * @since 5.24.0
	 *
	 * @param string $description The default support description.
	 *
	 * @return string The modified support description.
	 */
	public function add_support_description( $description ): string {
		return _x( 'Help on setting up, customizing, and troubleshooting your tickets.', 'Help Hub support description', 'event-tickets' );
	}
	/**
	 * Add support title
	 *
	 * @since 5.24.0
	 *
	 * @param string $title The default support title.
	 *
	 * @return string The modified support description.
	 */
	public function add_support_title( $title ): string {
		return _x( 'Event Tickets (TEC) Support Hub', 'Help Hub support title', 'event-tickets' );
	}

	/**
	 * Adds custom body classes for the Help Hub page.
	 *
	 * This method allows the addition of `$admin_page_body_classes` to
	 * the list of body classes for the Help Hub page.
	 *
	 * @since 5.24.0
	 *
	 * @param array<string> $classes The current array of body classes.
	 *
	 * @return array<string> Modified array of body classes.
	 */
	public function add_admin_body_classes( array $classes ): array {
		return array_merge( $classes, $this->admin_page_body_classes );
	}

	/**
	 * Creates an array of resource sections with relevant content for each section.
	 *
	 * Each section can be filtered independently or as a complete set.
	 *
	 * @since 5.24.0
	 *
	 * @return array<string, mixed> The filtered resource sections array.
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
	 * @since 5.24.0
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
				$this->get_icon_url( 'et_icon' )
			)
			->build();
	}

	/**
	 * Adds the "Tickets & RSVPs" section.
	 *
	 * @since 5.24.0
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
	 * @since 5.24.0
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
	 * @since 5.24.0
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
	 * @since 5.24.0
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
	 * @since 5.24.0
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

	/**
	 * Filters the logo source URL for the Help Hub header.
	 *
	 * @since 5.24.0
	 *
	 * @param string $src The default logo source URL.
	 *
	 * @return string The filtered logo source URL.
	 */
	public function add_header_logo_src( $src ) {
		$origin = Tribe__Tickets__Main::instance();

		return tribe_resource_url( 'images/tec-tickets-logo.svg', false, null, $origin );
	}

	/**
	 * Filters the logo alt text for the Help Hub header.
	 *
	 * @since 5.24.0
	 *
	 * @param string $alt The default logo alt text.
	 *
	 * @return string The filtered logo alt text.
	 */
	public function add_header_logo_alt( $alt ): string {
		return __( 'Event Tickets logo', 'event-tickets' );
	}

	/**
	 * Adds the help hub page ID to the list of help pages.
	 *
	 * @since 5.24.0
	 *
	 * @param array<string> $help_pages The current array of help pages.
	 *
	 * @return array<string> Modified array of help pages.
	 */
	public function add_help_hub_pages( $help_pages ): array {
		$help_pages[] = self::HELP_HUB_PAGE_ID;
		return $help_pages;
	}

	/**
	 * Determines if the current admin page is the Help Hub page.
	 *
	 * Checks the 'page' request variable against the Help Hub settings slug to confirm
	 * if the user is currently viewing the Help Hub admin page.
	 *
	 * @since 5.24.0
	 *
	 * @return bool True if the current page is the Help Hub, false otherwise.
	 */
	public function is_help_hub_page(): bool {
		$page = tec_get_request_var( 'page' );

		return $this->get_help_hub_slug() === $page;
	}

	/**
	 * Get the Help Hub page ID.
	 *
	 * @return string
	 */
	public function get_help_hub_id(): string {
		return self::HELP_HUB_PAGE_ID;
	}

	/**
	 * Retrieve the Help Hub slug.
	 *
	 * @return string The slug for the Help Hub.
	 */
	public function get_help_hub_slug(): string {
		return Settings::$help_hub_slug;
	}
}
