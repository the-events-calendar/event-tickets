<?php
/**
 * Handles the Series Passes integration at different levels.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use Exception;
use TEC\Common\Provider\Controller;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Events_Pro\Custom_Tables\V1\Templates\Series_Filters;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Metadata;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Repository as Series_Passes_Repository;
use TEC\Tickets\Flexible_Tickets\Templates\Admin_Views;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Ticket_Object as Ticket;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;

/**
 * Class Repository.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Series_Passes extends Controller {
	/**
	 * The ticket type handled by this class.
	 *
	 * @since TBD
	 */
	public const HANDLED_TICKET_TYPE = 'series_pass';

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'render_form_toggle' ] );
		// add_action( 'tec_tickets_ticket_add', [ $this, 'insert_pass_custom_tables_data' ], 10, 3 );
		// add_action( 'event_tickets_attendee_ticket_deleted', [ $this, 'delete_pass_custom_tables_data' ], 5, 2 );
		// add_action( 'tec_tickets_ticket_update', [ $this, 'update_pass_custom_tables_data' ], 10, 3 );
		add_filter( 'the_content', [ $this, 'reorder_series_content' ], 0 );
		add_filter( 'tec_tickets_ticket_panel_data', [ $this, 'update_panel_data' ], 10, 3 );

		$this->container->singleton( Series_Passes\Repository::class, Series_Passes\Repository::class );
		$this->container->singleton( Series_Passes\Metadata::class, Series_Passes\Metadata::class );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'render_form_toggle' ] );
		// remove_action( 'tec_tickets_ticket_add', [ $this, 'insert_pass_custom_tables_data' ] );
		// remove_action( 'event_tickets_attendee_ticket_deleted', [ $this, 'delete_pass_custom_tables_data' ], 5 );
		// remove_action( 'tec_tickets_ticket_update', [ $this, 'update_pass_custom_tables_data' ] );
		remove_filter( 'the_content', [ $this, 'reorder_series_content' ], 0 );
		remove_filter( 'tec_tickets_ticket_panel_data', [ $this, 'update_panel_data' ] );
	}

	/**
	 * Adds the toggle to the new ticket form.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void The toggle is added to the new ticket form.
	 */
	public function render_form_toggle( $post_id ): void {
		if ( ! ( is_numeric( $post_id ) && $post_id > 0 ) ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! ( $post instanceof WP_Post && $post->post_type === Series_Post_Type::POSTTYPE ) ) {
			return;
		}

		$ticket_providing_modules = array_diff_key( Tickets::modules(), [ RSVP::class => true ] );
		$admin_views              = $this->container->get( Admin_Views::class );
		$admin_views->template( 'series-pass-form-toggle', [
			'disabled' => count( $ticket_providing_modules ) === 0,
		] );
	}

	/**
	 * Parses the data passed as input to insert or update a Series Pass to make sure
	 * it's correct.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id The post ID the ticket has been created or updated for.
	 * @param Ticket $ticket  The created or updated ticket object.
	 * @param array  $data    The data to insert or update for the ticket.
	 *
	 * @return bool Whether the data is correct.
	 */
	private function check_upsert_data( $post_id, $ticket, $data ): bool {
		return is_int( $post_id ) && $post_id > 0
		       && (
			       ( $series = get_post( $post_id ) ) instanceof WP_Post
			       && $series->post_type === Series_Post_Type::POSTTYPE
		       )
		       && $ticket instanceof Ticket
		       && ( $ticket->type() ?? 'default' ) === self::HANDLED_TICKET_TYPE
		       && is_array( $data );
	}

	/**
	 * Inserts or updates the data to the custom tables for the series pass.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id The Series post ID to add the pass to.
	 * @param Ticket $ticket  A reference to the ticket object.
	 *
	 * @return bool Whether the data was added successfully.
	 * @throws Exception If the data could not be added.
	 */
	public function insert_pass_custom_tables_data( $post_id, $ticket, $data ): bool {
		if ( ! $this->check_upsert_data( $post_id, $ticket, $data ) ) {
			return false;
		}

		$inserted = $this->container->get( Series_Passes_Repository::class )
		                            ->insert_pass_data( $post_id, $ticket, $data );

		if ( $inserted ) {
			$this->debug( "Added Series Pass custom tables data for Ticket {$ticket->ID} and Series {$post_id}" );
		}

		return $inserted;
	}

	/**
	 * Deletes data from the custom tables when a Series Pass is deleted.
	 *
	 * @since TBD
	 *
	 * @param int $post_id   The Series post ID to delete the pass from.
	 * @param int $ticket_id The ticket ID to delete the pass from.
	 *
	 * @return bool Whether the data was deleted successfully.
	 *
	 * @throws Exception If the data could not be deleted.
	 */
	public function delete_pass_custom_tables_data( $post_id, $ticket_id ): bool {
		$check_args = is_int( $post_id ) && $post_id > 0
		              && (
			              ( $series = get_post( $post_id ) ) instanceof WP_Post
			              && $series->post_type === Series_Post_Type::POSTTYPE
		              )
		              && is_int( $ticket_id ) && $ticket_id > 0
		              && ( $ticket = Tickets::load_ticket_object( $ticket_id ) ) instanceof Ticket
		              && $ticket->type() === self::HANDLED_TICKET_TYPE;

		if ( ! $check_args ) {
			return false;
		}

		$deleted = $this->container->get( Series_Passes_Repository::class )
		                           ->delete_pass_data( $post_id, $ticket_id );

		if ( $deleted ) {
			$this->debug( 'Series Pass custom tables data deleted for Ticket ' . $ticket_id );
		} else {
			$this->debug( 'No Series Pass custom tables data found to delete for Ticket ' . $ticket_id );
		}

		// The method is idem-potent: the data was deleted, just not this time.
		return true;
	}

	/**
	 * Updates data in the custom tables when a Series Pass is updated.
	 *
	 * @since TBD
	 *
	 * @param int                 $post_id The Series post ID to update the pass for.
	 * @param Ticket              $ticket  The ticket object to update the pass for.
	 * @param array<string,mixed> $data    The data to update the pass with.
	 *
	 * @return bool Whether the data was updated successfully.
	 *
	 * @throws Exception If the data could not be updated.
	 */
	public function update_pass_custom_tables_data( $post_id, $ticket, $data ): bool {
		if ( ! $this->check_upsert_data( $post_id, $ticket, $data ) ) {
			return false;
		}

		$updated = $this->container->get( Series_Passes_Repository::class )
		                           ->update_pass_data( (int) $post_id, $ticket, $data );

		if ( $updated ) {
			$this->debug( "Updated Series Pass custom tables data for Ticket {$ticket->ID} and Series {$post_id}" );
		}

		return $updated;
	}

	/**
	 * Re-orders the Series content filter to run after the ticket content filter to
	 * have the tickets display after the Series content and before the Series list
	 * of Events.
	 *
	 * This method uses `the_content` filter priority 0 to run once before the Series or Ticket
	 * logic run
	 *
	 * @since TBD
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
	 * Adds Series Passes' admin strings to the list of admin strings.
	 *
	 * @since TBD
	 *
	 * @param array<string> $data      The panel data to filter.
	 * @param int           $post_id   The post ID the panel is being displayed for.
	 * @param int|null      $ticket_id The ticket ID the panel is being displayed for, if any.
	 *
	 * @return array<string> The list of admin strings.
	 */
	public function update_panel_data( array $data, int $post_id, ?int $ticket_id ): array {
		$meta_redirection = $this->container->get( Meta_Redirection::class );

		// Stop the meta redirection to avoid infinite loops.
		$meta_redirection->stop();

		if ( get_post_meta( $ticket_id, '_type', true ) !== self::HANDLED_TICKET_TYPE ) {
			$meta_redirection->resume();

			return $data;
		}

		$data['ticket_end_date_help_text'] = esc_attr_x(
			'If you do not set an end sale date, passes will be available until the last event in the Series.',
			'Help text for the end date field in the Series Passes meta box.',
			'event-tickets'
		);

		$set_end_date = get_post_meta( $ticket_id, '_ticket_end_date', true );
		$set_end_time = get_post_meta( $ticket_id, '_ticket_end_time', true );

		$meta_redirection->resume();

		if ( ! $set_end_date ) {
			$data['ticket_end_date'] = '';
		}

		if ( ! $set_end_time ) {
			$data['ticket_end_time'] = '';
		}

		return $data;
	}

	/**
	 * Get the ticket metadata.
	 *
	 * This method will run while the Meta Redirection controller is not filtering calls.
	 *
	 * @param mixed  $value     The original value to be filtered.
	 * @param int    $ticket_id The ticket post ID.
	 * @param string $meta_key  The meta key.
	 *
	 * @return mixed Either the original value or the filtered value.
	 */
	public function get_ticket_metadata( $value, int $ticket_id, string $meta_key ) {
		if ( ! in_array( $meta_key, [ '_ticket_end_date', '_ticket_end_time' ], true ) ) {
			return $value;
		}

		$metadata = $this->container->get( Metadata::class );

		if ( $meta_key === '_ticket_end_date' ) {
			return $metadata->get_ticket_end_date( $ticket_id );
		}

		return $metadata->get_ticket_end_time( $ticket_id );
	}
}