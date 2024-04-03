<?php
/**
 * A Controller to register basic functionalities common to all the ticket types handled by the feature.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use TEC\Common\Contracts\Container;
use TEC\Common\Contracts\Provider\Controller;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Events_Pro\Custom_Tables\V1\Templates\Provider as CT_Templates_Provider;
use TEC\Tickets\Admin\Upsell as Ticket_Upsell;
use TEC\Tickets\Commerce\Reports\Data\Order_Summary;
use TEC\Tickets\Flexible_Tickets\Repositories\Event_Repository;
use TEC\Tickets\Flexible_Tickets\Templates\Admin_Views;
use Tribe__Events__Main as TEC;
use Tribe__Main;
use Tribe__Template as Template;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;

/**
 * Class Base.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
 */
class Base extends Controller {
	/**
	 * ${CARET}
	 *
	 * @since 5.8.0
	 *
	 * @var Admin_Views
	 */
	private Admin_Views $admin_views;

	/**
	 * A reference to the Reports handler.
	 *
	 * @since 5.8.0
	 *
	 * @var Reports
	 */
	private Reports $reports;

	/**
	 * Base constructor.
	 *
	 * since 5.8.0
	 *
	 * @param Container   $container   A reference to the Container.
	 * @param Admin_Views $admin_views A reference to the Admin Views handler for Flexible Tickets.
	 * @param Reports     $reports     A reference to the Reports handler for Flexible Tickets.
	 */
	public function __construct(
		Container $container,
		Admin_Views $admin_views,
		Reports $reports
	) {
		parent::__construct( $container );
		$this->admin_views = $admin_views;
		$this->reports     = $reports;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$series_post_type = Series_Post_Type::POSTTYPE;
		add_filter( "tec_tickets_enabled_ticket_forms_{$series_post_type}", [
			$this,
			'enable_ticket_forms_for_series'
		] );

		// Filter the HTML template used to render Tickets on the front-end.
		add_filter( 'tribe_template_pre_html:tickets/v2/tickets/items', [
			$this,
			'classic_editor_ticket_items'
		], 10, 5 );

		// Remove the warning about Tickets added to a Recurring Event.
		$ticket_admin_notices = tribe( 'tickets.admin.notices' );
		remove_action( 'admin_init', [
			$ticket_admin_notices,
			'maybe_display_classic_editor_ecp_recurring_tickets_notice'
		] );

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
		add_action( 'tribe_tickets_attendees_event_details_list_top', [
			$this,
			'render_series_details_on_attendee_report'
		], 50 );
		add_action( 'tribe_tickets_report_event_details_list_top', [
			$this,
			'render_series_details_on_order_report'
		], 50 );
		add_filter( 'tec_tickets_commerce_order_report_summary_label_for_type', [ $this, 'filter_series_type_label' ] );
		add_filter( 'tec_tickets_commerce_order_report_summary_should_include_event_sales_data', [
			$this,
			'filter_out_series_type_tickets_from_order_report'
		], 10, 4 );

		add_filter( 'tribe_template_pre_html:tickets/admin-views/editor/panel/header-image', [
			$this,
			'hide_header_image_option_from_ticket_settings'
		], 10, 5 );
		add_filter( 'tribe_get_start_date', [ $this, 'filter_start_date_for_series' ], 10, 4 );
		add_filter( 'tribe_get_end_date', [ $this, 'filter_end_date_for_series' ], 10, 4 );

		// TicketsCommerce Checkout handlers for Series.
		add_filter( 'tec_tickets_commerce_shortcode_checkout_page_template_vars', [
			$this,
			'filter_tc_checkout_template_args'
		] );
		add_filter( 'tribe_template_pre_html:tickets/v2/commerce/checkout/cart/footer', [
			$this,
			'hide_non_series_cart_footer_html'
		], 10, 5 );
		add_filter( 'tec_events_pro_custom_tables_v1_block_editor_ajax_series_data', [
			$this,
			'filter_series_ajax_data'
		], 10, 2 );

		add_action( 'template_redirect', [ $this, 'skip_rendering_series_title_on_my_tickets_page' ] );
		add_action( 'tribe_template_after_include:tickets/tickets/my-tickets/title', [
			$this,
			'show_series_link_after_ticket_type_title'
		], 10, 3 );

        add_filter(
            'tribe_template_pre_html:tickets/admin-views/editor/panel/settings-button',
            [
                $this,
                'remove_settings_button_from_classic_metabox',
            ],
            10,
            5
        );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$series_post_type = Series_Post_Type::POSTTYPE;
		remove_filter( "tec_tickets_enabled_ticket_forms_{$series_post_type}", [
			$this,
			'enable_ticket_forms_for_series'
		] );

		remove_Filter( 'tribe_template_pre_html:tickets/v2/tickets/items', [
			$this,
			'classic_editor_ticket_items'
		] );

		// Restore the warning about Tickets added to a Recurring Event.
		$ticket_admin_notices = tribe( 'tickets.admin.notices' );
		if ( ! has_action( 'admin_init',
			[ $ticket_admin_notices, 'maybe_display_classic_editor_ecp_recurring_tickets_notice' ] )
		) {
			add_action( 'admin_init', [
				$ticket_admin_notices,
				'maybe_display_classic_editor_ecp_recurring_tickets_notice'
			] );
		}

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
		remove_action( 'tribe_tickets_attendees_event_details_list_top', [
			$this,
			'render_series_details_on_attendee_report'
		], 50 );
		remove_action( 'tribe_tickets_report_event_details_list_top', [
			$this,
			'render_series_details_on_order_report'
		], 50 );
		remove_filter( 'tec_tickets_commerce_order_report_summary_label_for_type', [
			$this,
			'filter_series_type_label'
		] );
		remove_filter( 'tec_tickets_commerce_order_report_summary_should_include_event_sales_data', [
			$this,
			'filter_out_series_type_tickets_from_order_report'
		], 10, 4 );

		remove_filter( 'tribe_template_pre_html:tickets/admin-views/editor/panel/header-image', [
			$this,
			'hide_header_image_option_from_ticket_settings'
		], 10, 5 );

		remove_filter( 'tribe_get_start_date', [ $this, 'filter_start_date_for_series' ], 10, 4 );
		remove_filter( 'tribe_get_end_date', [ $this, 'filter_end_date_for_series' ], 10, 4 );

		// Remove the TicketsCommerce Checkout handlers for Series.
		remove_filter( 'tec_tickets_commerce_shortcode_checkout_page_template_vars', [
			$this,
			'filter_tc_checkout_template_args'
		] );
		remove_filter( 'tribe_template_pre_html:tickets/v2/commerce/checkout/cart/footer', [
			$this,
			'hide_non_series_cart_footer_html'
		], 10, 5 );
		remove_filter( 'tec_events_pro_custom_tables_v1_block_editor_ajax_series_data', [
			$this,
			'filter_series_ajax_data'
		] );

		remove_action( 'template_redirect', [ $this, 'skip_rendering_series_title_on_my_tickets_page' ] );
		remove_action( 'tribe_template_after_include:tickets/tickets/my-tickets/title', [
			$this,
			'show_series_link_after_ticket_type_title'
		], 10, 3 );

        remove_filter(
            'tribe_template_pre_html:tickets/admin-views/editor/panel/settings-button',
            [
                $this,
                'remove_settings_button_from_classic_metabox',
            ],
            10,
            5
        );
	}

	/**
	 * Disables default ticket types for Series.
	 *
	 * @since 5.8.0
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
	 * @since 5.8.0
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
	 * @since 5.8.0
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
	 * Filters the label for the Series post type in the Attendees meta box top header.
	 *
	 * @since 5.8.0
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
	 * @since 5.8.0
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
	 * @since 5.8.0
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
		$ordered_by_types[ Series_Passes::TICKET_TYPE ] = $tickets_by_types[ Series_Passes::TICKET_TYPE ] ?? [];

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
	 * @since 5.8.0
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
	 * @since 5.8.0
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
	 * @since 5.8.0
	 *
	 * @param array<string,mixed> $context The context used to render the Attendees Report.
	 *
	 * @return array<string,mixed> The updated context.
	 */
	public function filter_attendees_report_context( array $context = [] ): array {
		return $this->reports->filter_attendees_report_context( $context );
	}

	/**
	 * Renders the series details on attendee report page for an event attached to a series.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The ID of the post being displayed.
	 *
	 * @return void
	 */
	public function render_series_details_on_attendee_report( int $post_id ): void {
		$this->reports->render_series_details_on_attendee_report( $post_id );
	}

	/**
	 * Renders the series details on order report page for an event attached to a series.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The ID of the post being displayed.
	 *
	 * @return void
	 */
	public function render_series_details_on_order_report( int $post_id ): void {
		$this->reports->render_series_details_on_order_report( $post_id );
	}

	/**
	 * Filters the label for the Series post type for the report pages.
	 *
	 * @since 5.8.0
	 *
	 * @param string $type The type of ticket.
	 *
	 * @return string The updated label.
	 */
	public function filter_series_type_label( $type ): string {
		return $this->reports->filter_series_type_label( $type );
	}

	/**
	 * Filters the order report to remove the series passes from the event sales data.
	 *
	 * @since 5.8.0
	 *
	 * @param bool              $include            Whether to include the event sales data.
	 * @param Ticket_Object     $ticket             The ticket object.
	 * @param array<string,int> $quantity_by_status The quantity of tickets by status.
	 * @param Order_Summary     $order_summary      The order summary object.
	 *
	 * @return bool Whether to include the event sales data.
	 */
	public function filter_out_series_type_tickets_from_order_report( $include, $ticket, $quantity_by_status, $order_summary ): bool {
		return $this->reports->filter_out_series_type_tickets_from_order_report( $include, $ticket, $quantity_by_status, $order_summary );
	}

	/**
	 * Filters the HTML for the ticket editor to hide the header image option from the ticket settings.
	 *
	 * @since 5.8.0
	 *
	 * @param null|string         $html     The initial HTML.
	 * @param string              $file     Complete path to include the PHP File.
	 * @param string[]            $name     Template name.
	 * @param Template            $template Current instance of the Tribe__Template
	 * @param array<string,mixed> $context  The context data passed to the template.
	 *
	 * @return null|bool The filtered HTML, or `false` to hide the option.
	 */
	public function hide_header_image_option_from_ticket_settings( string $html = null, string $file, array $name, Template $template, array $context ): ?bool {
		if ( ! isset( $context['post_id'] ) || get_post_type( $context['post_id'] ) !== Series_Post_Type::POSTTYPE ) {
			return $html;
		}

		return false;
	}

	/**
	 * Filters the start date for a series to use the start date of the first event in the series.
	 *
	 * @since 5.8.0
	 * @since 5.8.1 Removed strict type casting from signature.
	 *
	 * @param string  $start_date   The start date.
	 * @param WP_Post $series       The series post object.
	 * @param bool    $display_time Whether to display the time.
	 * @param string  $date_format  The date format.
	 *
	 * @return string The updated start date.
	 */
	public function filter_start_date_for_series( $start_date, $series, $display_time, $date_format ) {
		if ( get_post_type( $series ) !== Series_Post_Type::POSTTYPE ) {
			return $start_date;
		}

		if ( ! is_string( $start_date ) || ! is_bool( $display_time ) || ! is_string( $date_format ) ) {
			return $start_date;
		}

		$first_event = tribe_events()->where( 'series', $series->ID )
		                             ->order_by( 'event_date', 'ASC' )
		                             ->per_page( - 1 )
		                             ->first();

		if ( empty( $first_event ) ) {
			return '';
		}

		return tribe_format_date( $first_event->start_date, $display_time, $date_format );
	}

	/**
	 * Filters the end date for a series to use the start date of the last event in the series.
	 *
	 * @since 5.8.0
	 * @since 5.8.1 Removed strict type casting from signature.
	 *
	 * @param string  $end_date     The end date.
	 * @param WP_Post $series       The series post object.
	 * @param bool    $display_time Whether to display the time.
	 * @param string  $date_format  The date format.
	 *
	 * @return string The updated end date.
	 */
	public function filter_end_date_for_series( $end_date, $series, $display_time, $date_format ) {
		if ( get_post_type( $series ) !== Series_Post_Type::POSTTYPE ) {
			return $end_date;
		}

		if ( ! is_string( $end_date ) || ! is_bool( $display_time ) || ! is_string( $date_format ) ) {
			return $end_date;
		}

		$last_event = tribe_events()->where( 'series', $series->ID )
		                            ->order_by( 'event_date', 'ASC' )
		                            ->per_page( - 1 )
		                            ->last();

		if ( empty( $last_event ) ) {
			return '';
		}

		return tribe_format_date( $last_event->start_date, $display_time, $date_format );
	}

	/**
	 * Filters the template arguments used to render the checkout page.
	 *
	 * @since 5.8.0
	 *
	 * @param array<string,mixed> $args The template arguments.
	 *
	 * @return array<string,mixed> The updated template arguments.
	 */
	public function filter_tc_checkout_template_args( array $args ): array {
		$sections = $args['sections'] ?? [];

		// If there is only one section, we don't need to do anything.
		if ( count( $sections ) < 2 ) {
			return $args;
		}

		// Filter out the series id from the section values.
		$series_items = array_values( array_filter( $sections, fn( $event_id ) => get_post_type( $event_id ) === Series_Post_Type::POSTTYPE ) );

		if ( empty( $series_items ) ) {
			return $args;
		}

		$args['series_id'] = $series_items[0];

		return $args;
	}

	/**
	 * Filters HTML to hide the footer for regular event cart section when series pass is added in the cart.
	 *
	 * @since 5.8.0
	 *
	 * @param string               $html     The initial HTML or null.
	 * @param string               $file     Complete path to include the PHP File.
	 * @param array<string,string> $name     Template name.
	 * @param Template             $template Current instance of the Tribe__Template.
	 * @param array<string,mixed>  $context  The context data passed to the template.
	 *
	 * @return string|null The filtered HTML, or `false` to hide the option.
	 */
	public function hide_non_series_cart_footer_html( string $html = null, string $file, array $name, Template $template, array $context ) {
		$template_data = $template->get_values();

		// If the template data is not set, return the html.
		if ( ! isset( $template_data['series_id'] ) || ! isset( $template_data['section'] ) ) {
			return $html;
		}

		// If the current section is not the series id, return empty string to hide the footer.
		if ( (int) $template_data['section'] !== (int) $template_data['series_id'] ) {
			return '';
		}

		return $html;
	}

	/**
	 * Filters the data returned by the AJAX request to fetch the selected Series data.
	 *
	 * @since 5.8.0
	 *
	 * @param array   $data        The data to be returned.
	 * @param WP_Post $series_post The Series post object.
	 *
	 * @return array
	 */
	public function filter_series_ajax_data( array $data, \WP_Post $series_post ): array {
		$data['ticket_provider'] = Tickets::get_event_ticket_provider( $series_post->ID );

		return $data;
	}

	/**
	 * Skips rendering the series title on the My Tickets page.
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function skip_rendering_series_title_on_my_tickets_page(): void {
		$is_ticket_edit_page = (bool) get_query_var( 'tribe-edit-orders', false );
		$displaying_tickets  = 'tickets' === get_query_var( 'eventDisplay', false );

		if ( ! $is_ticket_edit_page && ! $displaying_tickets ) {
			return;
		}

		remove_filter( 'tribe_the_notices', [
			tribe( CT_Templates_Provider::class ),
			'add_single_series_text_marker'
		], 15, 2 );
	}

	/**
	 * Shows the series link after ticket type title.
	 *
	 * @since 5.8.0
	 *
	 * @param string        $file     Complete path to include the PHP File.
	 * @param array<string> $name     Template name.
	 * @param Template      $template Current instance of the Tribe__Template.
	 *
	 * @return void
	 */
	public function show_series_link_after_ticket_type_title( string $file, array $name, Template $template ): void {
		$template_data = $template->get_values();

		if ( ! isset( $template_data['ticket_type'] ) || Series_Passes::TICKET_TYPE !== $template_data['ticket_type'] ) {
			return;
		}

		$post_id = get_the_ID();
		$series  = tec_series()->where( 'event_post_id', $post_id )->first_id();

		if ( $series === null ) {
			// Not part of a Series, bail.
			return;
		}

		$series_link = sprintf(
			'<a href="%1$s" target="_blank">%2$s</a>',
			get_post_permalink( $series ),
			__( 'See all the events in this series.', 'event-tickets' )
		);

		echo '<span class="tec-tickets__my-tickets-list__series-link">' . $series_link . '</span>';
	}

	/**
	 * Hides the settings button from showing up for the classic editor metabox.
	 *
	 * @since 5.8.0
	 *
	 * @param null|string         $html     The initial HTML.
	 * @param string              $file     Complete path to include the PHP File.
	 * @param string[]            $name     Template name.
	 * @param Template            $template Current instance of the Tribe__Template
	 * @param array<string,mixed> $context  The context data passed to the template.
	 *
	 * @return null|bool The filtered HTML, or `false` to hide the option.
	 */
	public function remove_settings_button_from_classic_metabox( string $html = null, string $file, array $name, Template $template, array $context ): ?bool {
		if ( ! isset( $context['post_id'] ) || get_post_type( $context['post_id'] ) !== Series_Post_Type::POSTTYPE ) {
			return $html;
		}

		return false;
	}
}
