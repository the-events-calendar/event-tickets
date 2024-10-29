<?php
/**
 * Associated Events controller class.
 */

namespace TEC\Tickets\Seating\Admin\Events;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Arrays\Arr;
use TEC\Events_Pro\Custom_Tables\V1\Models\Series_Relationship;
use TEC\Tickets\Seating\Admin\Template;
use TEC\Common\lucatume\DI52\Container;
use TEC\Tickets\Seating\Tables\Layouts;
use TEC\Common\StellarWP\DB\DB;
use WP_Post;

/**
 * Class Events Controller.
 *
 * @since 5.16.0
 */
class Controller extends Controller_Contract {
	/**
	 * A reference to the template instance used to render the templates.
	 *
	 * @since 5.16.0
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * Events Controller constructor.
	 *
	 * @since 5.16.0
	 *
	 * @param Container $container The container instance.
	 * @param Template  $template The template instance.
	 */
	public function __construct( Container $container, Template $template ) {
		parent::__construct( $container );
		$this->template = $template;
	}

	/**
	 * Register actions.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'admin_menu', [ $this, 'add_events_list_page' ], 20 );
		add_action( 'load-' . Associated_Events::PAGE, [ $this, 'setup_events_list_screen' ] );
		add_filter( 'set_screen_option_' . Associated_Events::OPTION_PER_PAGE, [ $this, 'save_per_page_option' ], 10, 3 );
		add_filter( 'tec_events_pro_custom_tables_v1_add_to_series_available_events', [ $this, 'exclude_seating_events_from_series_list' ] );
		add_filter( 'filter_block_editor_meta_boxes', [ $this, 'filter_block_editor_series_meta_box' ] );
		add_action( 'tec_events_pro_custom_tables_v1_event_relationship_updated', [ $this, 'remove_event_relationship_for_seated_events' ] );
		add_action( 'tec_events_pro_custom_tables_v1_series_relationships_updated', [ $this, 'remove_series_relationship_for_seated_events' ], 10, 2 );
	}

	/**
	 * Remove actions.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'admin_menu', [ $this, 'add_events_list_page' ], 20 );
		remove_action( 'load-' . Associated_Events::PAGE, [ $this, 'setup_events_list_screen' ] );
		remove_filter( 'set_screen_option_' . Associated_Events::OPTION_PER_PAGE, [ $this, 'save_per_page_option' ] );
		remove_filter( 'tec_events_pro_custom_tables_v1_add_to_series_available_events', [ $this, 'exclude_seating_events_from_series_list' ] );
		remove_filter( 'filter_block_editor_meta_boxes', [ $this, 'filter_block_editor_series_meta_box' ] );
		remove_action( 'tec_events_pro_custom_tables_v1_event_relationship_updated', [ $this, 'remove_event_relationship_for_seated_events' ] );
		remove_action( 'tec_events_pro_custom_tables_v1_series_relationships_updated', [ $this, 'remove_series_relationship_for_seated_events' ] );
	}

	/**
	 * Setup Event listing screen.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function setup_events_list_screen() {
		$screen = get_current_screen();
		if ( Associated_Events::PAGE !== $screen->id ) {
			return;
		}

		$screen->add_option(
			'per_page',
			[
				'label'   => __( 'Events per page', 'event-tickets' ),
				'default' => 10,
				'option'  => Associated_Events::OPTION_PER_PAGE,
			]
		);
	}

	/**
	 * Save per page option.
	 *
	 * @since 5.16.0
	 *
	 * @param mixed  $screen_option The value to save instead of the option value. Default false (to skip saving the current option).
	 * @param string $option The option name.
	 * @param int    $value The option value.
	 *
	 * @return mixed The screen option value.
	 */
	public function save_per_page_option( $screen_option, $option, $value ) {
		if ( Associated_Events::OPTION_PER_PAGE !== $option ) {
			return $screen_option;
		}

		return $value;
	}

	/**
	 * Register Event listing page.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function add_events_list_page() {
		add_submenu_page(
			'',
			__( 'Events', 'event-tickets' ),
			'',
			'manage_options',
			Associated_Events::SLUG,
			[ $this, 'render' ]
		);
	}

	/**
	 * Render the Associated Events list table.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function render() {
		$events_table = new Associated_Events();
		$events_table->prepare_items();

		$layout_id = tribe_get_request_var( 'layout', false );
		$layout    = DB::table( Layouts::table_name( false ) )->where( 'id', $layout_id )->get();

		if ( empty( $layout ) ) {
			echo esc_html( _x( 'Layout ID is not valid!', 'Associated events list layout id', 'event-tickets' ) );
			return;
		}

		$header = sprintf(
			/* translators: %s: Layout name. */
			_x( 'Associated Events for %s', 'Associated events list header', 'event-tickets' ),
			$layout->name
		);

		$this->template->template(
			'events/list',
			[
				'header'       => $header,
				'events_table' => $events_table,
			]
		);
	}

	/**
	 * Exclude seating events from series list.
	 *
	 * @since 5.16.0
	 *
	 * @param array $event_ids The event IDs.
	 *
	 * @return array
	 */
	public function exclude_seating_events_from_series_list( array $event_ids ): array {
		return array_filter(
			$event_ids,
			static function ( $event_id ) {
				return ! tec_tickets_seating_enabled( $event_id );
			}
		);
	}

	/**
	 * Filter the block editor series meta box to show restriction message.
	 *
	 * @since 5.16.0
	 *
	 * @param array $wp_meta_boxes The meta boxes.
	 *
	 * @return array
	 */
	public function filter_block_editor_series_meta_box( $wp_meta_boxes ) {
		global $post;

		if ( ! $post instanceof WP_Post ) {
			return $wp_meta_boxes;
		}

		if ( ! tec_tickets_seating_enabled( $post->ID ) ) {
			return $wp_meta_boxes;
		}

		$series_meta_box = Arr::get( $wp_meta_boxes, [ 'tribe_events', 'side', 'default', 'tec_event_series_relationship' ] );

		if ( $series_meta_box ) {
			$wp_meta_boxes['tribe_events']['side']['default']['tec_event_series_relationship']['callback'] = static function () {
				echo esc_html( _x( 'Events using Seating for ticket capacity are not supported by Series at this time.', 'Seating events series meta box message', 'event-tickets' ) );
			};
		}

		return $wp_meta_boxes;
	}

	/**
	 * Remove event relationship for seated events.
	 *
	 * @since 5.16.0
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return void
	 */
	public function remove_event_relationship_for_seated_events( int $event_id ): void {
		if ( ! tec_tickets_seating_enabled( $event_id ) ) {
			return;
		}

		// Remove the relationship between the event and the series for seating events.
		Series_Relationship::where( 'event_post_id', '=', $event_id )->delete();
	}

	/**
	 * Remove series relationship for seated events.
	 *
	 * @since 5.16.0
	 *
	 * @param int        $series_id The series ID.
	 * @param array<int> $events The event ids.
	 *
	 * @return void
	 */
	public function remove_series_relationship_for_seated_events( int $series_id, array $events ): void {
		$seated_events = array_filter(
			$events,
			static function ( $event_id ) {
				return tec_tickets_seating_enabled( $event_id );
			}
		);

		if ( empty( $seated_events ) ) {
			return;
		}

		// Remove the relationship between the series and the events for seating events.
		Series_Relationship::where_in( 'event_post_id', $seated_events )->delete();
	}
}
