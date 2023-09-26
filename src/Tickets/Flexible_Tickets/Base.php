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
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Admin\Editor_Data;
use TEC\Tickets\Flexible_Tickets\Repositories\Event_Repository;
use Tribe__Template as Template;
use TEC\Tickets\Flexible_Tickets\Templates\Admin_Views;
use Tribe__Events__Main as TEC;
use Tribe__Main;
use Tribe__Tickets__Tickets as Tickets;

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

		// Filter the columns displayed in the series editor events list.
		add_filter(
			'tec_events_pro_custom_tables_v1_series_occurrent_list_columns', [
				$this,
				'filter_series_editor_occurrence_list_columns'
		] );

		add_action( 'tec_events_pro_custom_tables_v1_series_occurrent_list_column_ticket_types', [
			$this,
			'render_series_editor_occurrence_list_column_ticket_types'
		] );

		add_filter( 'tec_tickets_find_ticket_type_host_posts_query_args', [
			$this,
			'include_all_events_in_move_ticket_choices'
		] );
		add_filter( 'tribe_events_event_repository_map', [ $this, 'filter_events_repository_map' ], 50 );
		add_filter( 'tribe_template_context:tickets/admin-views/attendees', [
			$this,
			'filter_attendees_report_context'
		] );

		add_action( 'tribe_tickets_attendees_event_details_list_top', [ $this, 'render_series_details_for_attached_event' ], 50 );
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

		// Remove the columns displayed in the series editor event List.
		remove_filter(
			'tec_events_pro_custom_tables_v1_series_occurrent_list_columns', [
			$this,
			'filter_series_editor_occurrence_list_columns'
		] );

		remove_action( 'tec_events_pro_custom_tables_v1_series_occurrent_list_column_ticket_types', [
			$this,
			'render_series_editor_occurrence_list_column_ticket_types'
		] );

		remove_filter( 'tec_tickets_find_ticket_type_host_posts_query_args', [
			$this,
			'include_all_events_in_move_ticket_choices'
		] );
		remove_filter( 'tribe_events_event_repository_map', [ $this, 'filter_events_repository_map' ], 50 );
		remove_filter( 'tribe_template_context:tickets/admin-views/attendees', [
			$this,
			'filter_attendees_report_context'
		] );

		remove_action( 'tribe_tickets_attendees_event_details_list_top', [ $this, 'render_series_details_for_attached_event' ], 50 );
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

	/**
	 * Filters the columns displayed in the Series editor events List.
	 *
	 * @since TBD
	 *
	 * @param array<string,string> $columns The list of columns to filter.
	 *
	 * @return array<string,string> The filtered list of columns.
	 */
	public function filter_series_editor_occurrence_list_columns( array $columns ): array {
		return Tribe__Main::array_insert_before_key( 'actions',
			$columns,
			[
				'ticket_types' => sprintf(
					// translators: %s Ticket singular label text.
					__( 'Attached %s', 'event-tickets' ),
					tribe_get_ticket_label_plural()
				),
			]
		);
	}

	/**
	 * Renders the content of the "Attached Ticket Types" column in the Series editor events List.
	 *
	 * @since TBD
	 *
	 * @param Occurrence $occurrence
	 *
	 * @return void
	 */
	public function render_series_editor_occurrence_list_column_ticket_types( Occurrence $occurrence ) {
		$event_id = $occurrence->post_id;
		$tickets  = Tickets::get_event_tickets( $event_id );

		if ( empty( $tickets ) ) {
			echo '&mdash;';
			return;
		}

		$tickets_by_types = [];
		foreach ( $tickets as $ticket ) {
			$tickets_by_types[ $ticket->type ][] = $ticket;
		}

		// Order the tickets by types.
		$ordered_by_types = [
			'rsvp'    => $tickets_by_types['rsvp'] ?? [],
			'default' => $tickets_by_types['default'] ?? [],
		];

		// Place all other ticket types in between.
		foreach ( $tickets_by_types as $type => $tickets ) {
			if ( isset( $ordered_by_types[ $type ] ) || Series_Passes::TICKET_TYPE === $type ) {
				continue;
			}
			$ordered_by_types[ $type ] = $tickets;
		}

		// Series passes should always be placed at the end.
		$ordered_by_types[Series_Passes::TICKET_TYPE] = $tickets_by_types[Series_Passes::TICKET_TYPE] ?? [];

		$admin_views = new Admin_Views();
		$admin_views->template( 'ticket-types-column/types', [
			'tickets_by_types' => $ordered_by_types,
			'admin_views'      => $admin_views,
		] );
	}

	/**
	 * Updates the query arguments used to fetch the available Events when moving Tickets to remove the argument that
	 * would prevent, in CT1 context, Events in Series from being included.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $query_args The query arguments used to fetch the available Events.
	 *
	 * @return array<string,mixed> The updated query arguments.
	 */
	public function include_all_events_in_move_ticket_choices( array $query_args ): array {
		if ( array_key_exists( 'post__not_recurring', $query_args ) ) {
			$query_args['post__not_recurring'] = null;
		}

		return $query_args;
	}

	/**
	 * Overrides the default Events repository to use the one provided by the Flexible Tickets feature.
	 *
	 * The repository provided is one that will decorate either the Event Tickets or Event Tickets Plus repository
	 * depending on which is available.
	 *
	 * @since TBD
	 *
	 * @param array<string,string> $map A map from repository aliases to repository classes.
	 *
	 * @return array<string,string> The updated map.
	 */
	public function filter_events_repository_map( array $map ): array {
		$map['default'] = Event_Repository::class;

		return $map;
	}

	/**
	 * Filters the context used to render the Attendees Report to add the data needed to support the additional ticket
	 * types.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $context The context used to render the Attendees Report.
	 *
	 * @return array<string,context> The updated context.
	 */
	public function filter_attendees_report_context( array $context = [] ): array {
		if ( ! isset( $context['type_icon_classes'] ) ) {
			$context['type_icon_classes'] = [];
		}
		$context['type_icon_classes'][ Series_Passes::TICKET_TYPE ] = 'tec-tickets__admin-attendees-overview-ticket-type-icon--series-pass';

		if ( ! isset( $context['type_labels'] ) ) {
			$context['type_labels'] = [];
		}
		$context['type_labels'][ Series_Passes::TICKET_TYPE ] = tec_tickets_get_series_pass_plural_uppercase( 'Attendees Report' );

		return $context;
	}

	/**
	 * Renders the series details for an event attached to a series.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The ID of the post being displayed.
	 *
	 * @return void
	 */
	public function render_series_details_for_attached_event( int $post_id ): void {
		if ( get_post_type( $post_id ) === Series_Post_Type::POSTTYPE ) {
			return;
		}

		// Check if event is part of a series.
		$series_id = tec_series()->where( 'event_post_id', $post_id )->first_id();

		if ( ! $series_id ) {
			return;
		}

		// Generate series summary.
		$title                = get_the_title( $series_id );
		$edit_url             = get_edit_post_link( $series_id );
		$edit_link            = sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $edit_url ), $title );
		$attendee_report_link = tribe( 'tickets.attendees' )->get_report_link( get_post( $series_id ) );
		$action_links         = [
			sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $edit_url ), __( 'Edit Series', 'event-tickets' ) ),
			sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( get_permalink( $series_id ) ), __( 'View Series', 'event-tickets' ) ),
			sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $attendee_report_link ), __( 'Series Attendees', 'event-tickets' ) ),
		];

		// Render series details.
		$this->admin_views->template( 'admin/attendees/series-summary', [
			'title'        => $title,
			'edit_link'    => $edit_link,
			'action_links' => $action_links
		] );
	}
}