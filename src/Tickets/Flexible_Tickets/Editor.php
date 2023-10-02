<?php
/**
 * Handles the integration between Flexible Tickets and the editors.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Events_Pro\Custom_Tables\V1\Series\Relationship;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Events__Main as TEC;
use Tribe__Editor as Block_Editor_Feature_Detection;

/**
 * Class Editor.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Editor extends Controller {

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter(
			'tec_events_pro_custom_tables_v1_series_relationships_dropdown_data',
			[ $this, 'include_ticket_provider_in_series_dropdown_data' ]
		);

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_filter( 'tec_tickets_ticket_panel_data', [ $this, 'filter_ticket_panel_data' ], 10, 2 );
		add_filter( 'tribe_editor_config', [ $this, 'filter_tickets_editor_config' ] );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter(
			'tec_events_pro_custom_tables_v1_series_relationships_dropdown_data',
			[ $this, 'include_ticket_provider_in_series_dropdown_data' ]
		);
		remove_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		remove_filter( 'tec_tickets_ticket_panel_data', [ $this, 'filter_ticket_panel_data' ] );
		remove_filter( 'tribe_editor_config', [ $this, 'filter_tickets_editor_config' ] );
	}

	/**
	 * Updates the data localize for each Series option in the dropdown shown in the Events'
	 * edit page to include the ticket provider.
	 *
	 * @since TBD
	 *
	 * @param array<int,array<string,mixed>> $series_data The data for each Series option in the dropdown.
	 *
	 * @return array<int,array<string,mixed>> The updated data for each Series option in the dropdown.
	 */
	public function include_ticket_provider_in_series_dropdown_data( array $series_data ): array {
		foreach ( $series_data as $id => &$data ) {
			$data['ticket_provider'] = Tickets::get_event_ticket_provider( $id );
		}

		return $series_data;
	}

	/**
	 * Enqueues the scripts for the editor in Classic and Block Editor context.
	 *
	 * @since TBD
	 *
	 * @return void The scripts are enqueued.
	 */
	public function enqueue_admin_scripts(): void {
		// Register the correct scripts depending on the context.
		if ( ! tribe_context()->is_editing_post( TEC::POSTTYPE ) ) {
			return;
		}

		$should_load_blocks = $this->container->get( 'editor' )->should_load_blocks();
		// The Editor code will not take Classic Editor into account, reinforce with the function introduced in WP 5.0.
		$use_block_editor_for_post = use_block_editor_for_post( get_the_ID() );

		$editor_data = [
			'seriesRelationship' => [
				'fieldSelector'                   => '#' . Relationship::EVENTS_TO_SERIES_REQUEST_KEY,
				'containerSelector'               => '#tec_event_series_relationship .inside .tec-events-pro-series',
				'differentProviderNoticeSelector' => '.tec-flexible-tickets-different-ticket-provider-notice',
				'differentProviderNoticeTemplate' => _x(
				// Translators: %1$s is the event title, %2$s is the series title.
					'The event %1$s cannot be added to the Series %2$s because they use two different ecommerce' .
					' providers. Change the provider using the Sell tickets using option in the tickets settings.',
					'Notice shown when the user tries to add an event to a series that uses a different ' .
					'ticket provider.',
					'event-tickets'
				)
			],
			'classic'            => [
				'ticketPanelEditSelector'                 => '#tribe_panel_edit',
				'ticketPanelEditDefaultProviderAttribute' => 'data-current-provider',
				'ticketsMetaboxSelector'                  => '#event_tickets',
			]
		];

		if ( $should_load_blocks && $use_block_editor_for_post ) {
			tribe_asset(
				tribe( 'tickets.main' ),
				'tec-tickets-flexible-tickets-event-block-editor-js',
				'flexible-tickets/event-block-editor.js',
				[ 'jquery' ],
				null,
				[
					'groups'   => [
						'flexible-tickets',
					],
					'localize' => [
						'name' => 'TECFtEditorData',
						'data' => $editor_data,
					]
				],
			);
			tribe_asset_enqueue( 'tec-tickets-flexible-tickets-event-block-editor-js' );

			return;
		}

		tribe_asset(
			tribe( 'tickets.main' ),
			'tec-tickets-flexible-tickets-event-classic-editor-js',
			'flexible-tickets/event-classic-editor.js',
			[ 'jquery' ],
			null,
			[
				'groups'   => [
					'flexible-tickets',
				],
				'localize' => [
					'name' => 'TECFtEditorData',
					'data' => $editor_data,
				]
			],
		);
		tribe_asset_enqueue( 'tec-tickets-flexible-tickets-event-classic-editor-js' );
	}

	private function get_series_related_to_event( int $post_id = null ): ?int {
		if ( get_post_type( $post_id ) !== TEC::POSTTYPE ) {
			return null;
		}

		$series_id = tec_series()->where( 'event_post_id', $post_id )->first_id();

		if ( empty( $series_id ) ) {
			return null;
		}

		return $series_id;
	}

	/**
	 * Filters the data used to render the ticket panels to control settings related to the ticket provider.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data    The data used to render the ticket panels.
	 * @param int                 $post_id The post ID context of the metabox.
	 *
	 * @return array<string,mixed> The data used to render the ticket panels.
	 */
	public function filter_ticket_panel_data( array $data, int $post_id ): array {
		$series_id = $this->get_series_related_to_event( $post_id );

		if ( empty( $series_id ) ) {
			return $data;
		}

		if ( ! isset( $data['active_providers'] ) ) {
			return $data;
		}

		foreach ( $data['active_providers'] as &$provider ) {
			$provider['disabled'] = true;
		}
		unset( $provider );

		$edit_link = get_edit_post_link( $series_id, 'admin' ) . '#tribetickets';
		$data['multiple_providers_notice'] = sprintf(
			_x(
			// Translators: %s is the series title with a link to edit it.
				'The ecommerce provider is defined in the ticket settings for the Series %s.',
				'The notice shown when there are multiple ticket providers available and the Event is part of a Series.',
				'event-tickets'
			),
			'<a target="_blank" href="' . esc_url( $edit_link ) . '">' . esc_html( get_the_title( $series_id ) ) . '</a>'
		);

		return $data;
	}

	/**
	 * Filters the data used to render the Tickets Block Editor control to alter its state for Events that
	 * are part of a Series.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The data used to render the Tickets Block Editor control.
	 *
	 * @return array<string,mixed> The data used to render the Tickets Block Editor control.
	 */
	public function filter_tickets_editor_config( array $data ): array {
		$series_id = $this->get_series_related_to_event( get_the_ID() );

		if ( empty( $series_id ) ) {
			return $data;
		}

		$edit_link = get_edit_post_link( $series_id, 'admin' ) . '#tribetickets';

		if ( ! isset( $data['tickets'] ) ) {
			$data['tickets'] = [];
		}

		$data['tickets']['multiple_providers_notice'] = sprintf(
			_x(
			// Translators: %s is the series title with a link to edit it.
				'The ecommerce provider is defined in the ticket settings for the Series %s.',
				'The notice shown when there are multiple ticket providers available and the Event is part of a Series.',
				'event-tickets'
			),
			'<a target="_blank" href="' . esc_url( $edit_link ) . '">' . esc_html( get_the_title( $series_id ) ) . '</a>'
		);
		$data['tickets']['choice_disabled'] = true;

		return $data;
	}
}
