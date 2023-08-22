<?php
/**
 * A Controller to register basic functionalities common to all the ticket types handled by the feature.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Contracts\Container;
use TEC\Common\Contracts\Provider\Controller;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Admin\Editor_Data;
use Tribe__Template as Template;
use TEC\Tickets\Flexible_Tickets\Templates\Admin_Views;
use Tribe__Events__Main as TEC;
use WP_Post;

/**
 * Class Base.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Base extends Controller {
	/**
	 * ${CARET}
	 *
	 * @since TBD
	 *
	 * @var Admin_Views
	 */
	private Admin_Views $admin_views;

	/**
	 * Base constructor.
	 *
	 * since TBD
	 *
	 * @param Container   $container   A reference to the Container.
	 * @param Admin_Views $admin_views A reference to the Admin Views handler for Flexible Tickets.
	 */
	public function __construct( Container $container, Admin_Views $admin_views ) {
		parent::__construct( $container );
		$this->admin_views = $admin_views;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Repositories\Ticket_Groups::class, Repositories\Ticket_Groups::class );
		$this->container->singleton( Repositories\Posts_And_Ticket_Groups::class, Repositories\Posts_And_Ticket_Groups::class );

		$series_post_type = Series_Post_Type::POSTTYPE;
		add_filter( "tec_tickets_enabled_ticket_forms_{$series_post_type}", [
			$this,
			'enable_ticket_forms_for_series'
		] );

		// Remove the warnings about Recurring Events and Tickets not being supported.
		$editor_warnings = tribe( 'tickets.editor.warnings' );
		remove_action( 'tribe_events_tickets_new_ticket_warnings', [
			$editor_warnings,
			'show_recurring_event_warning_message'
		] );

		// Prevent the "New Ticket" and "New RSVP" buttons from being shown on the editor for recurring events.
		$post_type = TEC::POSTTYPE;
		add_filter( "tec_tickets_enabled_ticket_forms_{$post_type}", [
			$this,
			'disable_tickets_on_recurring_events'
		], 10, 2 );

		// Filter the HTML template used to render Tickets on the front-end.
		add_filter( 'tribe_template_pre_html:tickets/v2/tickets/items', [
			$this,
			'classic_editor_ticket_items'
		], 10, 5 );

		tribe_asset(
			tribe( 'tickets.main' ),
			'tec-tickets-flexible-tickets-style',
			'flexible-tickets.css',
			[],
			null,
			[
				'groups' => [
					'flexible-tickets',
				],
			],
		);

		// Remove the warning about Tickets added to a Recurring Event.
		$ticket_admin_notices = tribe( 'tickets.admin.notices' );
		remove_action( 'admin_init', [
			$ticket_admin_notices,
			'maybe_display_classic_editor_ecp_recurring_tickets_notice'
		] );

		$this->series_are_ticketable();

		add_filter( 'tec_tickets_attendees_event_details_top_label', [
			$this,
			'filter_attendees_event_details_top_label'
		], 10, 2 );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$series_post_type = Series_Post_Type::POSTTYPE;
		remove_filter( "tec_tickets_enabled_ticket_forms_{$series_post_type}", [
			$this,
			'enable_ticket_forms_for_series'
		] );

		// Restore the warnings about Recurring Events and Tickets not being supported.
		$editor_warnings = tribe( 'tickets.editor.warnings' );
		add_action( 'tribe_events_tickets_new_ticket_warnings', [
			$editor_warnings,
			'show_recurring_event_warning_message'
		] );

		remove_Filter( 'tribe_template_pre_html:tickets/v2/tickets/items', [
			$this,
			'classic_editor_ticket_items'
		] );

		$post_type = TEC::POSTTYPE;
		remove_filter( "tec_tickets_enabled_ticket_forms_{$post_type}", [
			$this,
			'disable_tickets_on_recurring_events'
		] );

		// Restore the warning about Tickets added to a Recurring Event.
		$ticket_admin_notices = tribe( 'tickets.admin.notices' );
		add_action( 'admin_init', [
			$ticket_admin_notices,
			'maybe_display_classic_editor_ecp_recurring_tickets_notice'
		] );

		// Remove Series from the list of ticketable post types.
		$this->series_are_ticketable( false );

		remove_filter( 'tec_tickets_attendees_event_details_top_label', [
			$this,
			'filter_attendees_event_details_top_label'
		] );
	}

	/**
	 * Disables default ticket types for Series.
	 *
	 * @since TBD
	 *
	 * @param array<string,bool> $enabled The default enabled forms, a map from ticket types to their enabled status.
	 *
	 * @return array<string,bool> The updated enabled forms.
	 */
	public function enable_ticket_forms_for_series( array $enabled ): array {
		$enabled['default']                    = false;
		$enabled['rsvp']                       = false;
		$enabled[ Series_Passes::TICKET_TYPE ] = true;

		return $enabled;
	}

	/**
	 * Provides an alternate Tickets form on the front-end when looking at an Event part of a Series.
	 *
	 * @since TBD
	 *
	 * @param string|null         $html          The HTML code as provided by the template, initially `null`.
	 * @param string              $file          The file path to the template file, unused.
	 * @param string|string[]     $name          The name of the template, or an array of name fragments, unused.
	 * @param Template            $template      The template object, unused.
	 * @param array<string,mixed> $local_context The context to render the template, it does not include the global
	 *                                           context.
	 *
	 * @return string|null The alternate HTML code to use for rendering the Tickets form, if the current Event is part
	 *                     of a Series.
	 */
	public function classic_editor_ticket_items( ?string $html, string $file, $name, Template $template, array $local_context ): ?string {
		$context = $template->merge_context( $local_context, $file, $name );
		$post_id = $context['post_id'] ?? 0;

		if ( get_post_type( $post_id ) !== TEC::POSTTYPE ) {
			// Not an Event, bail.
			return $html;
		}

		$series = tec_series()->where( 'event_post_id', $post_id )->first_id();

		if ( $series === null ) {
			// Not part of a Series, bail.
			return $html;
		}

		tribe_asset_enqueue( 'tec-tickets-flexible-tickets-style' );

		$context['tickets_template'] = $template;
		$context['series_permalink'] = get_post_permalink( $series );
		$buffer                      = $this->admin_views->template( 'frontend/tickets/items', $context, false );

		return $buffer;
	}

	/**
	 * Disables Tickets and RSVPs on recurring events.
	 *
	 * @since TBD
	 *
	 * @param array<string,bool> $enabled The default enabled forms, a map from ticket types to their enabled status.
	 * @param int                $post_id The ID of the Event being checked.
	 *
	 * @return array<string,bool> The updated enabled forms.
	 */
	public function disable_tickets_on_recurring_events( array $enabled, int $post_id ): array {
		if ( tribe_is_recurring_event( $post_id ) ) {
			$enabled['default'] = false;
			$enabled['rsvp']    = false;
		}

		return $enabled;
	}

	/**
	 * Ensures the Series post type ticketable status.
	 *
	 * @since TBD
	 *
	 * @param bool $ticketable Whether the Series post type should be ticketable or not.
	 *
	 * @return void
	 */
	private function series_are_ticketable( bool $ticketable = true ): void {
		$ticketable_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		if ( $ticketable ) {
			$ticketable_post_types[] = Series_Post_Type::POSTTYPE;
			$ticketable_post_types   = array_values( array_unique( $ticketable_post_types ) );
			tribe_update_option( 'ticket-enabled-post-types', $ticketable_post_types );

			return;
		}

		$index = array_search( Series_Post_Type::POSTTYPE, $ticketable_post_types, true );

		if ( $index !== false ) {
			unset( $ticketable_post_types[ $index ] );
			$ticketable_post_types = array_values( $ticketable_post_types );
			tribe_update_option( 'ticket-enabled-post-types', $ticketable_post_types );
		}
	}

	/**
	 * Filters the label for the Series post type in the Attendees meta box top header.
	 *
	 * @since TBD
	 *
	 * @param string $label   The label for the Attendees meta box top header.
	 * @param int    $post_id The ID of the post Attendees are being displayed for.
	 *
	 * @return string The updated label.
	 */
	public function filter_attendees_event_details_top_label( string $label, int $post_id ): string {
		if ( get_post_type( $post_id ) !== Series_Post_Type::POSTTYPE ) {
			return $label;
		}

		// This controller will not register if ECP is not active: we can assume we'll have ECP translations available.
		return __( 'Series', 'tribe-events-calendar-pro' );
	}

	// trigger tests.
}