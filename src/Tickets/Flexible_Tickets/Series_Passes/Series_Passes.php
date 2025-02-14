<?php
/**
 * Handles the Series Passes integration at different levels.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Common\lucatume\DI52\Container;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Events_Pro\Custom_Tables\V1\Templates\Series_Filters;
use TEC\Tickets\Flexible_Tickets\Enums;
use TEC\Tickets\Flexible_Tickets\Metabox;
use TEC\Tickets\Flexible_Tickets\Ticket_Provider_Handler;
use Tribe__Events__Main as TEC;
use Tribe__Repository__Interface as ORM;
use Tribe__Tickets__Admin__Views as Admin_Views;
use Tribe__Tickets__Editor__Template as Template;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;
use WP_Rewrite;

/**
 * Class Repository.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes;
 */
class Series_Passes extends Controller {

	/**
	 * The ticket type handled by this class.
	 *
	 * @since 5.8.0
	 */
	public const TICKET_TYPE = 'series_pass';

	/**
	 * A reference to the labels' handler.
	 *
	 * @since 5.8.0
	 *
	 * @var Labels
	 */
	private Labels $labels;

	/**
	 * A reference to the Series Passes' meta handler.
	 *
	 * @since 5.8.0
	 *
	 * @var Meta
	 */
	private Meta $meta;

	/**
	 * A reference to the Series Passes' metabox handler.
	 *
	 * @since 5.8.0
	 *
	 * @var Metabox
	 */
	private Metabox $metabox;

	/**
	 * A reference to the Series Passes' ticket provider handler.
	 *
	 * @since 5.8.0
	 *
	 * @var Ticket_Provider_Handler
	 */
	private Ticket_Provider_Handler $ticket_provider_handler;

	/**
	 * A reference to the Series Passes' queries handler.
	 *
	 * @since 5.8.0
	 *
	 * @var Queries
	 */
	private Queries $queries;
	/**
	 * A reference to the Series Passes' edit and editor handler.
	 *
	 * @since 5.8.0
	 *
	 * @var Edit
	 */
	private Edit $edit;

	/**
	 * A reference to the Series Passes' frontend handler.
	 *
	 * @since 5.8.0
	 *
	 * @var Frontend
	 */
	private Frontend $frontend;

	/**
	 * Series_Passes constructor.
	 *
	 * since 5.8.0
	 *
	 * @param Container               $container               The dependency injection container.
	 * @param Labels                  $labels                  The labels' handler.
	 * @param Meta                    $meta                    The Series Passes' meta handler.
	 * @param Metabox                 $metabox                 The Series Passes' metabox handler.
	 * @param Ticket_Provider_Handler $ticket_provider_handler The Series Passes' ticket provider handler.
	 * @param Edit                    $edit                    The Series Passes' edit and editor handler.
	 * @param Frontend                $frontend                The Series Passes' frontend handler.
	 */
	public function __construct(
		Container $container,
		Labels $labels,
		Meta $meta,
		Metabox $metabox,
		Ticket_Provider_Handler $ticket_provider_handler,
		Queries $queries,
		Edit $edit,
		Frontend $frontend
	) {
		parent::__construct( $container );
		$this->labels                  = $labels;
		$this->meta                    = $meta;
		$this->metabox                 = $metabox;
		$this->ticket_provider_handler = $ticket_provider_handler;
		$this->queries                 = $queries;
		$this->edit                    = $edit;
		$this->frontend                = $frontend;
	}

	/**
	 * The entire provider should not be active if Series are not ticketable.
	 *
	 * @since 5.8.0
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		$ticketable_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		return in_array( Series_Post_Type::POSTTYPE, $ticketable_post_types, true );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Repository::class, Repository::class );
		$this->container->singleton( Metadata::class, Metadata::class );

		$this->container->register( Attendees::class );

		add_filter( 'the_content', [ $this, 'reorder_series_content' ], 0 );
		add_filter( 'the_content', [ $this, 'skip_rendering_series_content_for_my_tickets_page' ], 1 );

		add_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'render_form_toggle' ] );
		add_action( 'admin_menu', [ $this, 'enable_reports' ], 20 );
		add_filter( 'tec_tickets_ticket_panel_data', [ $this, 'update_panel_data' ], 10, 3 );

		// Subscribe to the ticket post updates.
		foreach ( Enums\Ticket_Post_Types::all() as $post_type ) {
			add_action( "save_post_{$post_type}", [ $this, 'update_pass' ], 20 );
			add_action( "edit_post_{$post_type}", [ $this, 'update_pass' ], 20 );
		}

		// Subscribe to Tickets' metadata updates.
		add_action( 'added_post_meta', [ $this, 'update_pass_meta' ], 20, 4 );
		add_action( 'updated_post_meta', [ $this, 'update_pass_meta' ], 20, 4 );
		add_action( 'tribe_tickets_ticket_add', [ $this, 'update_pass_meta_on_save' ], 10, 2 );

		// An Event is attached to a Series.
		add_action(
			'tec_events_pro_custom_tables_v1_event_relationship_updated',
			[
				$this,
				'update_passes_for_event',
			],
			20,
			2
		);

		// Multiple Events are attached to a Series.
		add_action(
			'tec_events_pro_custom_tables_v1_series_relationships_updated',
			[
				$this,
				'update_passes_for_series',
			]
		);

		// Event Occurrences have been updated
		add_action( 'tec_events_custom_tables_v1_after_save_occurrences', [ $this, 'update_passes_for_event' ] );

		add_action( 'tec_tickets_panels_before', [ $this, 'start_filtering_labels' ], 10, 3 );
		add_action( 'tec_tickets_panels_after', [ $this->labels, 'stop_filtering_labels' ] );
		add_action( 'tribe_events_tickets_new_ticket_warnings', [ $this, 'display_pass_notice_in_warnings' ], 5, 2 );
		add_action(
			'tec_tickets_editor_list_table_before',
			[
				$this,
				'display_pass_notice_before_passes_list',
			],
			10,
			3
		);

		add_filter( 'tec_tickets_repository_filter_by_event_id', [ $this, 'add_series_to_searched_events' ], 10, 2 );
		add_action( 'added_post_meta', [ $this, 'propagate_ticket_provider_from_series' ], 20, 4 );
		add_action( 'updated_post_meta', [ $this, 'propagate_ticket_provider_from_series' ], 20, 4 );
		add_action( 'deleted_post_meta', [ $this, 'delete_ticket_provider_from_series' ], 20, 4 );

		add_action( 'tec_tickets_list_row_edit', [ $this, 'render_link_to_series' ], 10, 2 );
		add_filter( 'tec_tickets_editor_list_ticket_types', [ $this, 'display_series_passes_list' ] );
		$ticket_type = self::TICKET_TYPE;
		add_filter( "tec_tickets_editor_list_table_data_{$ticket_type}", [ $this, 'update_table_data' ] );
		add_action(
			"tec_tickets_editor_list_table_title_icon_{$ticket_type}",
			[
				$this,
				'print_series_pass_icon',
			]
		);
		add_action( 'tribe_template_before_include:tickets/admin-views/editor/panel/fields/dates', [ $this, 'render_type_header' ], 10, 3 );

		add_filter(
			'tec_tickets_ticket_type_default_header_description',
			[
				$this,
				'filter_ticket_type_default_header_description',
			],
			10,
			2
		);

		add_filter( 'tribe_get_event_meta', [ $this, 'add_pass_costs_to_event_cost' ], 10, 4 );

		add_filter( 'tec_tickets_query_ticketed_status_subquery', [ $this, 'filter_ticketed_status_query' ], 10, 3 );
		add_filter( 'tec_tickets_query_ticketed_count_query', [ $this, 'filter_ticketed_count_query' ], 10, 2 );
		add_filter( 'tec_tickets_query_unticketed_count_query', [ $this, 'filter_unticketed_count_query' ], 10, 2 );

		add_filter( 'tec_tickets_panel_list_helper_text', [ $this, 'filter_tickets_panel_list_helper_text' ], 10, 2 );

		add_filter( 'tribe_template_after_include:tickets/v2/tickets/title', [ $this, 'render_series_passes_header_in_frontend_ticket_form' ], 10, 3 );
		add_filter( 'tec_tickets_flexible_tickets_editor_data', [ $this, 'filter_editor_data' ] );
		add_filter(
			'tec_tickets_editor_configuration_localized_data',
			[
				$this,
				'filter_editor_configuration_data',
			]
		);
		add_filter( 'tec_tickets_is_ticket_editable_from_post', [ $this, 'is_ticket_editable_from_post' ], 10, 3 );
		add_filter( 'tec_tickets_my_tickets_link_ticket_count_by_type', [ $this, 'filter_my_tickets_link_data' ], 10, 3 );
		add_filter( 'tec_tickets_my_tickets_page_rewrite_rules', [ $this, 'include_rewrite_rules_for_series_my_tickets_page' ] );

		/**
		 * The FT feature will only be available if the CT1 feature is active: this implies Recurring Events
		 * will always be part of a Series.
		 */
		add_filter( 'tec_tickets_allow_tickets_on_recurring_events', [ $this, 'allow_tickets_on_recurring_events' ] );

		add_filter( 'tribe_template_context:tickets/admin-views/editor/recurring-warning', [ $this, 'filter_recurring_warning_message' ], 10, 4 );
		add_filter( 'tec_tickets_commerce_provider_missing_warning_message', [ $this, 'filter_no_commerce_provider_warning_message' ] );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'the_content', [ $this, 'reorder_series_content' ], 0 );
		remove_filter( 'the_content', [ $this, 'skip_rendering_series_content_for_my_tickets_page' ], 1 );
		remove_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'render_form_toggle' ] );
		remove_action( 'admin_menu', [ $this, 'enable_reports' ], 20 );
		remove_filter( 'tec_tickets_ticket_panel_data', [ $this, 'update_panel_data' ], 10, 3 );
		foreach ( Enums\Ticket_Post_Types::all() as $post_type ) {
			remove_action( "save_post_{$post_type}", [ $this, 'update_pass' ], 20 );
			remove_action( "edit_post_{$post_type}", [ $this, 'update_pass' ], 20 );
		}
		remove_action( 'added_post_meta', [ $this, 'update_pass_meta' ], 20 );
		remove_action( 'updated_post_meta', [ $this, 'update_pass_meta' ], 20 );
		remove_action( 'tribe_tickets_ticket_add', [ $this, 'update_pass_meta_on_save' ] );
		remove_action(
			'tec_events_pro_custom_tables_v1_event_relationship_updated',
			[
				$this,
				'update_passes_for_event',
			],
			20
		);
		remove_action(
			'tec_events_pro_custom_tables_v1_series_relationships_updated',
			[
				$this,
				'update_passes_for_series',
			]
		);
		remove_action( 'tec_events_custom_tables_v1_after_save_occurrences', [ $this, 'update_passes_for_event' ] );
		remove_action( 'tec_tickets_panels_before', [ $this, 'start_filtering_labels' ] );
		remove_action( 'tec_tickets_panels_after', [ $this->labels, 'stop_filtering_labels' ] );
		remove_action( 'tribe_events_tickets_new_ticket_warnings', [ $this, 'display_pass_notice_in_warnings' ], 5, 2 );
		remove_action(
			'tec_tickets_editor_list_table_before',
			[
				$this,
				'display_pass_notice_before_passes_list',
			]
		);
		remove_filter( 'tec_tickets_repository_filter_by_event_id', [ $this, 'add_series_to_searched_events' ] );
		remove_action( 'added_post_meta', [ $this, 'propagate_ticket_provider_from_series' ], 20, 4 );
		remove_action( 'updated_post_meta', [ $this, 'propagate_ticket_provider_from_series' ], 20, 4 );
		remove_action( 'deleted_post_meta', [ $this, 'delete_ticket_provider_from_series' ], 20, 4 );
		remove_action( 'tec_tickets_list_row_edit', [ $this, 'render_link_to_series' ], );
		remove_filter( 'tec_tickets_editor_list_ticket_types', [ $this, 'display_series_passes_list' ] );
		$ticket_type = self::TICKET_TYPE;
		remove_filter( "tec_tickets_editor_list_table_data_{$ticket_type}", [ $this, 'update_table_data' ] );
		remove_action(
			"tec_tickets_editor_list_table_title_icon_{$ticket_type}",
			[
				$this,
				'print_series_pass_icon',
			]
		);
		remove_action( 'tribe_template_before_include:tickets/admin-views/editor/panel/fields/dates', [ $this, 'render_type_header' ], 10, 3 );
		remove_filter(
			'tec_tickets_ticket_type_default_header_description',
			[
				$this,
				'filter_ticket_type_default_header_description',
			]
		);
		remove_filter( 'tribe_get_event_meta', [ $this, 'add_pass_costs_to_event_cost' ] );

		remove_filter( 'tec_tickets_query_ticketed_status_subquery', [ $this, 'filter_ticketed_status_query' ] );
		remove_filter( 'tec_tickets_query_ticketed_count_query', [ $this, 'filter_ticketed_count_query' ] );
		remove_filter( 'tec_tickets_query_unticketed_count_query', [ $this, 'filter_unticketed_count_query' ] );
		remove_filter(
			'tec_tickets_panel_list_helper_text',
			[
				$this,
				'filter_tickets_panel_list_helper_text',
			],
			10,
			2
		);

		remove_filter( 'tribe_template_after_include:tickets/v2/tickets/title', [ $this, 'render_series_passes_header_in_frontend_ticket_form' ], 10, 3 );
		remove_filter( 'tec_tickets_flexible_tickets_editor_data', [ $this, 'filter_editor_data' ] );
		remove_filter(
			'tec_tickets_editor_configuration_localized_data',
			[
				$this,
				'filter_editor_configuration_data',
			]
		);
		remove_filter( 'tec_tickets_is_ticket_editable_from_post', [ $this, 'is_ticket_editable_from_post' ] );
		remove_filter( 'tec_tickets_my_tickets_link_ticket_count_by_type', [ $this, 'filter_my_tickets_link_data' ], 10, 3 );
		remove_filter( 'tec_tickets_allow_tickets_on_recurring_events', [ $this, 'allow_tickets_on_recurring_events' ] );
		remove_filter( 'tec_tickets_my_tickets_page_rewrite_rules', [ $this, 'include_rewrite_rules_for_series_my_tickets_page' ] );
		remove_filter( 'tribe_template_context:tickets/admin-views/editor/recurring-warning', [ $this, 'filter_recurring_warning_message' ], 10, 4 );
		remove_filter( 'tec_tickets_commerce_provider_missing_warning_message', [ $this, 'filter_no_commerce_provider_warning_message' ] );

		$this->container->get( Attendees::class )->unregister();
	}

	/**
	 * Adds the toggle to the new ticket form.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void The toggle is added to the new ticket form.
	 */
	public function render_form_toggle( $post_id ): void {
		if ( ! ( is_numeric( $post_id ) && $post_id > 0 ) ) {
			return;
		}

		$this->metabox->render_form_toggle( $post_id );
	}

	/**
	 * Re-orders the Series content filter to run after the ticket content filter to
	 * have the tickets display after the Series content and before the Series list
	 * of Events.
	 *
	 * This method uses `the_content` filter priority 0 to run once before the Series or Ticket
	 * logic run
	 *
	 * @since 5.8.0
	 *
	 * @param string $content The post content.
	 *
	 * @return string The filtered post content.
	 */
	public function reorder_series_content( $content ) {
		$series_filters = $this->container->make( Series_Filters::class );
		// Move the Series content filter from its default priority of 10 to 20; tickest are injected at 11.
		remove_filter( 'the_content', [ $series_filters, 'inject_content' ] );
		add_filter( 'the_content', [ $series_filters, 'inject_content' ], 20 );
		// It's enough to run this once.
		remove_filter( 'the_content', [ $this, 'reorder_series_content' ], 0 );

		return $content;
	}

	/**
	 * Skip rendering the Series content when on the My Tickets page.
	 *
	 * @since 5.8.0
	 *
	 * @param string $content The post content.
	 *
	 * @return string The filtered post content.
	 */
	public function skip_rendering_series_content_for_my_tickets_page( string $content ): string {
		return $this->frontend->skip_rendering_series_content_for_my_tickets_page( $content );
	}

	/**
	 * Adds Series Passes' admin strings to the list of admin strings.
	 *
	 * @since 5.8.0
	 *
	 * @param array<string> $data      The panel data to filter.
	 * @param int           $post_id   The post ID the panel is being displayed for.
	 * @param int|null      $ticket_id The ticket ID the panel is being displayed for, if any.
	 *
	 * @return array<string> The list of admin strings.
	 */
	public function update_panel_data( array $data, int $post_id, ?int $ticket_id ): array {
		if ( get_post_meta( $ticket_id, '_type', true ) !== self::TICKET_TYPE ) {
			return $data;
		}

		return $this->metabox->update_panel_data( $data, $ticket_id );
	}

	/**
	 * Enables the reports for the Series Passes for all the possible ticket providers.
	 *
	 * Since providers can be set per-Series, all are pre-emptively activated.
	 *
	 * @since 5.8.0
	 *
	 * @return void Reports are enabled for the Series Passes.
	 */
	public function enable_reports(): void {
		global $_registered_pages;

		if ( ! is_array( $_registered_pages ) ) {
			return;
		}

		// The post type is the Event one because in the menu Series are listed under Events.
		$event_post_type = TEC::POSTTYPE;

		// Attendee reports for all providers (ET).
		$_registered_pages[ $event_post_type . '_page_tickets-attendees' ] = true;

		// Order reports for Tickets Commerce provider (ET).
		$_registered_pages[ $event_post_type . '_page_tickets-commerce-orders' ] = true;

		// Order reports for PayPal Tickets provider (ET).
		$_registered_pages[ $event_post_type . '_page_tpp-orders' ] = true;

		// Order reports for WooCommerce provider (ET+).
		$_registered_pages[ $event_post_type . '_page_tickets-orders' ] = true;

		// Order reports for Easy Digital Downloads provider (ET+).
		$_registered_pages[ $event_post_type . '_page_edd-orders' ] = true;
	}

	/*
	 * Updates a Series Pass' end date meta dynamic flag and values, if needed.
	 *
	 * The method wraps the Meta low-level operation to unregister and re-register the provider as required.
	 *
	 * @since 5.8.0
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return void The ticket end date meta and dynamic flag is updated.
	 */
	private function update_ticket_end_meta( int $ticket_id, string $meta_key, bool $dynamic ): void {
		// Unregister to avoid infinite loops.
		remove_action( 'added_post_meta', [ $this, 'update_pass_meta' ], 20 );
		remove_action( 'updated_post_meta', [ $this, 'update_pass_meta' ], 20 );

		$this->meta->update_end_meta( $ticket_id, $meta_key, $dynamic );

		// Re-register the controller.
		add_action( 'added_post_meta', [ $this, 'update_pass_meta' ], 20, 4 );
		add_action( 'updated_post_meta', [ $this, 'update_pass_meta' ], 20, 4 );
	}

	/**
	 * Updates the Series Passes meta when its meta is updated.
	 *
	 * @since 5.8.0
	 *
	 * @param int|null $meta_id    The meta ID, unused.
	 * @param int      $ticket_id  The ticket ID.
	 * @param string   $meta_key   The meta key.
	 * @param mixed    $meta_value The meta value.
	 *
	 * @return void The meta is updated.
	 */
	public function update_pass_meta( $meta_id, $ticket_id, $meta_key, $meta_value ): void {
		if ( get_post_meta( $ticket_id, '_type', true ) !== self::TICKET_TYPE ) {
			return;
		}

		$this->meta->update_pass_meta( $ticket_id, $meta_key, $meta_value );
	}

	/**
	 * Updates a Series Pass post or custom fields when it's saved.
	 *
	 * @since 5.8.0
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return void The Series Pass post is updated.
	 */
	public function update_pass( $ticket_id ): void {
		if ( get_post_meta( $ticket_id, '_type', true ) !== self::TICKET_TYPE ) {
			return;
		}

		$end_date_is_dynamic = get_post_meta( $ticket_id, '_dynamic_end_date', true )
			|| empty( get_post_meta( $ticket_id, '_ticket_end_date', true ) );
		$this->update_ticket_end_meta( $ticket_id, '_ticket_end_date', $end_date_is_dynamic );

		// End time follows end date: either they're both dynamic or both manually set.
		$this->update_ticket_end_meta( $ticket_id, '_ticket_end_time', $end_date_is_dynamic );
	}

	/**
	 * Updates a Series Pass meta when created or edited.
	 *
	 * @since 5.8.0
	 *
	 * @param int           $post_id The ID of the post the Ticket is being saved for.
	 * @param Ticket_Object $ticket  The Ticket being saved.
	 *
	 * @return void The Series Pass meta is updated, if the Ticket is a Series Pass and it's required.
	 */
	public function update_pass_meta_on_save( $post_id, Ticket_Object $ticket = null ): void {
		if ( $ticket === null ) {
			return;
		}

		$this->update_pass( $ticket->ID );
	}

	/**
	 * Updates the Series Passes when a Series relationships with Events are updated.
	 *
	 * @since 5.8.0
	 *
	 * @param int $series_id The Series post ID.
	 *
	 * @return void The Series Passes are updated.
	 */
	public function update_passes_for_series( int $series_id ): void {
		foreach ( tribe_tickets()->where( 'event', $series_id )->get_ids( true ) as $pass ) {
			$this->update_pass( $pass );
		}

		$this->ticket_provider_handler->update_from_series( $series_id );
	}

	/**
	 * Updates the Series Passes when an Event relationship with Series are updated.
	 *
	 * @since 5.8.0
	 *
	 * @param int             $event_id   The Event post ID.
	 * @param array<int>|null $series_ids The Series post IDs, if known.
	 *
	 * @return void The Series Passes are updated.
	 */
	public function update_passes_for_event( int $event_id, array $series_ids = null ): void {
		// Get the Series the Event belongs to if not provided.
		$series_ids = $series_ids ?? tec_series()->where( 'event_post_id', $event_id )->fields( 'ids' )->get_ids();

		if ( empty( $series_ids ) ) {
			return;
		}

		$this->update_passes_for_series( reset( $series_ids ) );
	}

	/**
	 * Starts filtering the ticket labels during panel rendering.
	 *
	 * @since 5.8.0
	 *
	 * @param int|WP_Post|null $post        The post the panel is being rendered for.
	 * @param int|null         $ticket_id   The ticket ID the panel is being rendered for, if any.
	 * @param string|null      $ticket_type The ticket type the panel is being rendered for, if any.
	 *
	 * @return void
	 */
	public function start_filtering_labels( $post = null, $ticket_id = null, $ticket_type = null ): void {
		if ( $ticket_type !== self::TICKET_TYPE ) {
			return;
		}

		$this->labels->start_filtering_labels();
	}

	/**
	 * Renders the notice about Series Passes being managed from the Series in the context of Events related to the
	 * Series.
	 *
	 * The notice will render if the Event has no other tickets, and the Event is related to a Series.
	 *
	 * @since 5.8.0
	 *
	 * @param int                  $post_id The post ID.
	 * @param array<Ticket_Object> $tickets The tickets for the post.
	 *
	 * @return void The notice is rendered, if the post is an Event related to a Series.
	 */
	public function display_pass_notice_in_warnings( int $post_id, array $tickets ): void {
		if ( count( $tickets ) > 0 || get_post_type( $post_id ) !== TEC::POSTTYPE ) {
			return;
		}

		$this->metabox->display_pass_notice( $post_id );
	}

	public function print_series_pass_icon(): void {
		$this->metabox->print_series_pass_icon();
	}

	/**
	 * Renders the notice about Series Passes being managed from the Series before printing the list of Series
	 * Passes in the context of Events related to the Series.
	 *
	 * The notice will render if the Event has other tickets, and the Event is related to a Series.
	 *
	 * @since 5.8.0
	 *
	 * @param int    $post_id
	 * @param array  $tickets
	 * @param string $ticket_type
	 *
	 * @return void
	 */
	public function display_pass_notice_before_passes_list( int $post_id, array $tickets, string $ticket_type ): void {
		if ( $ticket_type !== self::TICKET_TYPE ) {
			return;
		}

		$this->metabox->display_pass_notice( $post_id );
	}

	/**
	 * Adds the Series post ID to the list of IDs that should be searched for tickets when, in the context
	 * of a repository query, searching for an Event's tickets.
	 *
	 * @since 5.8.0
	 *
	 * @param array<int>|int $post_id The Event post ID, or a list of IDs.
	 *
	 * @return array<int> The list of IDs to search for tickets.
	 */
	public function add_series_to_searched_events( $post_id, ORM $repository ) {
		// Bail if the context of the fetch is not one we want to interfere with.
		if ( $repository->get_request_context() === 'manual-attendees' ) {
			return $post_id;
		}

		// At least one of the IDs must be an Event.
		$event_ids = array_filter(
			(array) $post_id,
			function ( $id ) {
				return get_post_type( $id ) === TEC::POSTTYPE;
			}
		);

		if ( empty( $event_ids ) ) {
			return (array) $post_id;
		}

		$series_ids = tec_series()->where( 'event_post_id', $event_ids )->get_ids();

		if ( ! count( $series_ids ) ) {
			return $post_id;
		}

		$series = reset( $series_ids );

		return [ $series, ...(array) $post_id ];
	}

	/**
	 * Updates the ticket provider when a Series ticket provider is updated.
	 *
	 * @since 5.8.0
	 *
	 * @param array<int> $meta_ids   Unused, the meta IDs that were updated.
	 * @param int        $object_id  The post ID.
	 * @param string     $meta_key   The meta key.
	 * @param mixed      $meta_value The meta value.
	 *
	 * @return void The ticket provider is updated.
	 */
	public function propagate_ticket_provider_from_series( $meta_ids, $object_id, $meta_key, $meta_value ): void {
		$this->update_ticket_provider_from_series( $object_id, $meta_key, $meta_value );
	}

	/**
	 * Deleted the ticket provider when a Series ticket provider is deleted.
	 *
	 * @since 5.8.0
	 *
	 * @param array<int> $meta_ids   Unused, the meta IDs that were deleted.
	 * @param int        $object_id  The post ID.
	 * @param string     $meta_key   The meta key.
	 * @param mixed      $meta_value The meta value.
	 *
	 * @return void The ticket provider is deleted.
	 */
	public function delete_ticket_provider_from_series( $meta_ids, $object_id, $meta_key, $meta_value ): void {
		$this->update_ticket_provider_from_series( $object_id, $meta_key, $meta_value, true );
	}

	/**
	 * Updates the ticket provider when a Series ticket provider is updated or deleted.
	 *
	 * @since 5.8.0
	 *
	 * @param int    $object_id  The post ID.
	 * @param string $meta_key   The meta key.
	 * @param mixed  $meta_value The meta value.
	 * @param bool   $delete     Whether the meta was deleted.
	 *
	 * @return void The ticket provider is updated.
	 */
	private function update_ticket_provider_from_series( $object_id, $meta_key, $meta_value, $delete = false ): void {
		if ( ! (
			// Hard-coding the meta key to avoid having to retrieve, and possibly build, a controller for it.
			$meta_key === '_tribe_default_ticket_provider'
			&& get_post_type( $object_id ) === Series_Post_Type::POSTTYPE )
		) {
			return;
		}

		if ( $delete ) {
			$this->ticket_provider_handler->delete_from_series( $object_id );
		} else {
			$this->ticket_provider_handler->update_from_series( $object_id, $meta_value );
		}
	}

	/**
	 * Renders a link to the Series edit screen in place of the edit controls for Series Passes
	 * when displaying the Series Pass in the context of a Series' Event.
	 *
	 * @since 5.8.0
	 *
	 * @param Ticket_Object $ticket  The ticket object.
	 * @param int|null      $post_id The post ID the ticket is being rendered for, if any.
	 *
	 * @return void The link is rendered.
	 */
	public function render_link_to_series( Ticket_object $ticket, int $post_id = null ): void {
		if ( $post_id === $ticket->get_event_id() ) {
			// Let the default controls render.
			return;
		}

		if ( get_post_meta( $ticket->ID, '_type', true ) !== self::TICKET_TYPE ) {
			// Not a Series Pass, bail.
			return;
		}

		$this->metabox->render_link_to_series( $ticket->get_event_id() );
	}

	/**
	 * Adds the Series Pass ticket type to the list of ticket types to display a table for.
	 *
	 * @since 5.8.0
	 *
	 * @param array<string,array<Ticket_Object>> $ticket_types A map from ticket type to list of tickets
	 *                                                         for that type.
	 *
	 * @return array<string,array<Ticket_Object>> The updated map.
	 */
	public function display_series_passes_list( array $ticket_types ): array {
		if ( empty( $ticket_types[ self::TICKET_TYPE ] ) ) {
			$ticket_types[ self::TICKET_TYPE ] = [];
		}

		return $ticket_types;
	}

	/**
	 * Updates the data displayed in the Series Pass list table to use the Series Pass plural name
	 * for the title.
	 *
	 * @since 5.8.0
	 *
	 * @param array<string,mixed> $table_data The table data.
	 *
	 * @return array<string,mixed> The updated table data.
	 */
	public function update_table_data( array $table_data ): array {
		$table_data['table_title'] = tec_tickets_get_series_pass_plural_uppercase( 'ticket_list_title' );

		return $table_data;
	}

	/**
	 * Renders the Series Pass type header in the context of the Ticket add and edit form.
	 *
	 * @since 5.8.0
	 *
	 * @param string      $file        The file being rendered.
	 * @param array       $name        The name of the file being rendered.
	 * @param Admin_Views $admin_views The admin views instance.
	 *
	 * @return void
	 */
	public function render_type_header( string $file, array $name, Admin_Views $admin_views ): void {
		$context     = $admin_views->get_values();
		$ticket_type = $context['ticket_type'] ?? '';
		$post_id     = $context['post_id'] ?? '';

		if ( self::TICKET_TYPE !== $ticket_type || empty( $post_id ) ) {
			return;
		}

		$this->metabox->render_type_header();
	}

	/**
	 * Filters the default Ticket type description in the context of Events part of a Series.
	 *
	 * @since 5.8.0
	 *
	 * @param string $description The default Ticket type description.
	 * @param int    $post_id     The post ID the description is being rendered for.
	 *
	 * @return string The updated description.
	 */
	public function filter_ticket_type_default_header_description( string $description, int $post_id ): string {
		if ( get_post_type( $post_id ) !== TEC::POSTTYPE ) {
			return $description;
		}

		$series = tec_series()->where( 'event_post_id', $post_id )->first_id();

		if ( $series === null ) {
			return $description;
		}

		return $this->metabox->get_default_ticket_type_header_description( $post_id, $series );
	}
	/**
	 * Filters the costs of an Event to include the costs of Series Passes if the Event is part of a Series.
	 *
	 * @since 5.8.0
	 *
	 * @param mixed|null  $meta_value The meta value.
	 * @param int         $post_id    The Event post ID.
	 * @param string|null $meta_key   The meta key, or `null` if the current request is to fetch all meta.
	 * @param bool        $single     Whether to return a single value.
	 *
	 * @return mixed The original meta value, or the updated meta value if the Event is part of a Series and the key
	 *               is `_EventCost`.
	 */
	public function add_pass_costs_to_event_cost( $meta_value = null, $post_id = null, $meta_key = false, bool $single = true ) {
		if ( $meta_key !== '_EventCost' || $single ) {
			return $meta_value;
		}

		$series = tec_series()->where( 'event_post_id', $post_id )->first_id();

		if ( $series === null ) {
			return $meta_value;
		}

		$passes = Tickets::get_all_event_tickets( $series );

		return array_merge(
			(array) $meta_value,
			array_map(
				static fn( Ticket_Object $ticket) => $ticket->price,
				$passes
			)
		);
	}

	/**
	 * Filters the query to fetch post types by their ticketed status to include Events that are ticketed "by proxy"
	 * by being associated with a Series that has one or more Series Passes.
	 *
	 * @since 5.8.0
	 *
	 * @param string|null   $query       The SQL query to filter.
	 * @param bool          $has_tickets Whether to filter by ticketed or unticketed status.
	 * @param array<string> $post_types  The list of post types to filter.
	 *
	 * @return string|null The filtered SQL query, if required.
	 */
	public function filter_ticketed_status_query( string $query = null, bool $has_tickets, array $post_types ): ?string {
		// Filter if working with Events alone.
		if ( $post_types !== [ TEC::POSTTYPE ] ) {
			return $query;
		}

		return $this->queries->filter_ticketed_status_query();
	}

	/**
	 * Filters the query used to get the number of ticketed posts of a certain type to include Events that are ticketed
	 * "by proxy" by being associated with a Series that has one or more Series Passes.
	 *
	 * @since 5.8.0
	 *
	 * @param string|null $query     The SQL query to filter.
	 * @param string      $post_type The post type the unticketed count is being calculated for.
	 *
	 * @return string|null The filtered SQL query, if required.
	 */
	public function filter_ticketed_count_query( string $query = null, string $post_type ): ?string {
		if ( $post_type !== TEC::POSTTYPE ) {
			return $query;
		}

		return $this->queries->filter_ticketed_count_query();
	}

	/**
	 * Filters the query used to get the number of unticketed posts of a certain type to include Events that are
	 * ticketed "by proxy" by being associated with a Series that has one or more Series Passes.
	 *
	 * @since 5.8.0
	 *
	 * @param string|null $query     The SQL query to filter.
	 * @param string      $post_type The post type the unticketed count is being calculated for.
	 *
	 * @return string|null The filtered SQL query, if required.
	 */
	public function filter_unticketed_count_query( string $query = null, string $post_type ): ?string {
		if ( $post_type !== TEC::POSTTYPE ) {
			return $query;
		}

		return $this->queries->filter_unticketed_count_query();
	}

	/**
	 * Filter the meta box helper text for Series post type.
	 *
	 * @since 5.8.0
	 *
	 * @param string  $text The helper text with link.
	 * @param WP_Post $post The Post object.
	 *
	 * @return string The helper text with link
	 */
	public function filter_tickets_panel_list_helper_text( string $text, WP_Post $post ): string {
		if ( Series_Post_Type::POSTTYPE != $post->post_type ) {
			return $text;
		}

		return $this->metabox->get_tickets_panel_list_helper_text( $text, $post );
	}

	/**
	 * Renders the Series Pass header for Ticket form in the frontend.
	 *
	 * @since 5.8.0
	 *
	 * @param string   $file     The file to render.
	 * @param array    $name     The name of the file to render.
	 * @param Template $template The template instance.
	 *
	 * @return void The header is rendered.
	 */
	public function render_series_passes_header_in_frontend_ticket_form( string $file, array $name, Template $template ): void {
		$context = $template->get_values();

		// Check if the current post is a Series.
		if ( ! isset( $context['post_id'] ) || get_post_type( $context['post_id'] ) !== Series_Post_Type::POSTTYPE ) {
			return;
		}

		$context['header'] = tec_tickets_get_series_pass_plural_uppercase( 'ticket form header' );

		$template->template( 'v2/tickets/series-pass/header', $context );
	}

	/**
	 * Filters the editor data localized by Flexible Tickets.
	 *
	 * @since 5.8.0
	 *
	 * @param array<string,mixed> $editor_data The editor data.
	 *
	 * @return array<string,mixed> The updated editor data.
	 */
	public function filter_editor_data( array $editor_data ): array {
		return $this->edit->filter_editor_data( $editor_data );
	}

	/**
	 * Filters the editor configuration data to add the information required to correctly represent
	 * Series Passes in the editor.
	 *
	 * @since 5.8.0
	 *
	 * @param array<string,mixed> $data The editor configuration data.
	 *
	 * @return array<string,mixed> The editor configuration data with the information required to correctly represent
	 *                             Series Passes.
	 */
	public function filter_editor_configuration_data( array $data ): array {
		return $this->edit->filter_configuration_data( $data );
	}

	/**
	 * Prevent Series Passes from being edited outside the context of Series.
	 *
	 * @since 5.8.0
	 *
	 * @param bool $is_ticket_editable Whether the ticket is editable in the context of the post.
	 * @param int  $ticket_id          The ticket ID.
	 * @param int  $post_id            The post ID.
	 *
	 * @return bool Whether the ticket is editable in the context of the post.
	 */
	public function is_ticket_editable_from_post( bool $is_ticket_editable, int $ticket_id, int $post_id ): bool {
		return $this->edit->is_ticket_editable_from_post( $is_ticket_editable, $ticket_id, $post_id );
	}

	/**
	 * Filters the data for the "My Tickets" link.
	 *
	 * @since 5.8.0
	 *
	 * @param array<string, mixed> $data     The data for the "My Tickets" link.
	 * @param int                  $event_id The event ID.
	 * @param int                  $user_id  The user ID.
	 *
	 * @return array<string, array> The updated data.
	 */
	public function filter_my_tickets_link_data( array $data, int $event_id, int $user_id ): array {
		return $this->frontend->filter_my_tickets_link_data( $data, $event_id, $user_id );
	}

	/**
	 * Filter whether tickets can be added to recurring events or not.
	 *
	 * This method will return `true` to allow tickets on recurring events by default and by controlling the
	 * relevant parts of the UI using Javascript.
	 *
	 * @since 5.8.0
	 * @since 5.9.1 The method is now only allowing tickets on recurring events for admin view only.
	 *
	 * @param bool $allow Whether to allow tickets on recurring events.
	 *
	 * @return bool Whether to allow tickets on recurring events.
	 */
	public function allow_tickets_on_recurring_events( bool $allow ): bool {
		// Allow tickets on recurring events for admin view only and not on FE.
		return is_admin();
	}

	/**
	 * Generate rewrite rules for the series my tickets page.
	 *
	 * @since 5.8.0
	 *
	 * @since 5.8.2 Filter my tickets rewrite rules to include the series page.
	 *
	 * @param array<string> $rules Rewrite rules array.
	 *
	 * @return array<string> The updated rewrite rules array.
	 */
	public function include_rewrite_rules_for_series_my_tickets_page( array $rules ): array {
		$post_type = get_post_type_object( Series_Post_Type::POSTTYPE );
		$slug      = $post_type->rewrite['slug'];

		$series_rules = [
			'(?:' . $slug . ')/([^/]+)/(?:tickets)/?$' => 'index.php?' . $post_type->name . '=$matches[1]&post_type=' . $post_type->name . '&eventDisplay=tickets',
		];

		return array_merge( $rules, $series_rules );
	}

	/**
	 * Include series pass message in the ticket editor.
	 *
	 * @since 5.8.2
	 *
	 * @param array<string,mixed> $context   Local Context array of data.
	 * @param string              $file      Complete path to include the PHP File.
	 * @param array<string,mixed> $name      Template name.
	 * @param Template            $template  Current instance of the Tribe__Template.
	 *
	 * @return array<string,mixed> The updated context.
	 */
	public function filter_recurring_warning_message( array $context, string $file, array $name, $template ): array {
		if ( ! isset( $context['messages'] ) ) {
			return $context;
		}

		return $this->metabox->get_recurring_warning_message( $context );
	}

	/**
	 * Filter the message to display when no commerce provider is active.
	 *
	 * @since 5.8.2
	 *
	 * @param string $message The message to filter.
	 *
	 * @return string The filtered message.
	 */
	public function filter_no_commerce_provider_warning_message( string $message ): string {
		if ( get_post_type() !== Series_Post_Type::POSTTYPE ) {
			return $message;
		}

		return $this->metabox->get_no_commerce_provider_warning_message();
	}

	/**
	 * Filters the post IDs used to fetch an Event attendees to include the Series the Event belongs to and,
	 * thus, include Series Passes into the results.
	 *
	 * @since 5.8.0
	 * @since 5.8.2 Method moved to the Attendees controller.
	 *
	 * @param int|array<int> $post_id The post ID or IDs.
	 *
	 * @return int|array<int> The updated post ID or IDs.
	 *
	 * @deprecated 5.8.2 Use the Attendees::include_series_to_fetch_attendees method instead.
	 */
	public function include_series_to_fetch_attendees( $post_id ): array {
		return tribe( Attendees::class )->include_series_to_fetch_attendees( $post_id );
	}

	/**
	 * Filters the JavaScript configuration for the Attendees report to include the confirmation strings for
	 * Series Passes.
	 *
	 * @since 5.8.0
	 * @since 5.8.2 Method moved to the Attendees controller.
	 *
	 * @param array<string,mixed> $config_data The JavaScript configuration.
	 *
	 * @return array<string,mixed> The updated JavaScript configuration.
	 *
	 * @deprecated 5.8.2 Use the Attendees::filter_tickets_attendees_report_js_config method instead.
	 */
	public function filter_tickets_attendees_report_js_config( array $config_data ): array {
		return tribe( Attendees::class )->filter_tickets_attendees_report_js_config( $config_data );
	}
}
