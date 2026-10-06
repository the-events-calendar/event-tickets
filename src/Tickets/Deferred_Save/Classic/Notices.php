<?php
/**
 * Tells the admin which staged tickets the server rejected, after the page reload.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Classic
 */

namespace TEC\Tickets\Deferred_Save\Classic;

use TEC\Common\StellarWP\AdminNotices\AdminNotice;
use TEC\Common\StellarWP\AdminNotices\AdminNotices;
use TEC\Tickets\Deferred_Save\Payload\Parser;
use TEC\Tickets\Deferred_Save\Result;
use TEC\Tickets\Event;
use TEC\Tickets\Ticket_Data;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Notices.
 *
 * A classic save reloads the page, so the commit result has to survive the redirect. It is kept in a
 * transient for the user who saved, for one minute, and rendered once on their next post edit screen.
 * Per user rather than per post, because Events Calendar Pro may redirect a split save to another post.
 * The feature Controller hooks both steps.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Classic
 */
final class Notices {
	/**
	 * The transient key prefix; the user ID is appended.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const TRANSIENT_PREFIX = 'tec_tickets_deferred_save_result_';

	/**
	 * The domain's door to tickets, which the checks read them through too.
	 *
	 * @since TBD
	 *
	 * @var Ticket_Data
	 */
	private Ticket_Data $ticket_data;

	/**
	 * Notices constructor.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Data $ticket_data The domain's door to tickets.
	 */
	public function __construct( Ticket_Data $ticket_data ) {
		$this->ticket_data = $ticket_data;
	}

	/**
	 * Keeps the errors of a commit for the current user until their next edit screen.
	 *
	 * @since TBD
	 *
	 * @param Result $result  The commit result.
	 * @param int    $post_id The post that was saved.
	 *
	 * @return void
	 */
	public function remember( Result $result, int $post_id ): void {
		$user_id = get_current_user_id();

		if ( ! $user_id || [] === $result->get_errors() ) {
			return;
		}

		set_transient(
			self::TRANSIENT_PREFIX . $user_id,
			[
				'post_id' => $post_id,
				'errors'  => $result->get_errors(),
			],
			MINUTE_IN_SECONDS
		);
	}

	/**
	 * Renders the remembered errors once, on a post edit screen.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function render(): void {
		$user_id = get_current_user_id();
		$screen  = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $user_id || ! $screen || 'post' !== $screen->base ) {
			return;
		}

		$remembered = get_transient( self::TRANSIENT_PREFIX . $user_id );

		if ( ! is_array( $remembered ) || empty( $remembered['errors'] ) ) {
			return;
		}

		delete_transient( self::TRANSIENT_PREFIX . $user_id );

		$post_id = (int) ( $remembered['post_id'] ?? 0 );
		$errors  = array_map( static fn( $error ) => (array) $error, (array) $remembered['errors'] );
		// An `applied` error is a change that was saved before something after it failed: entering it again would repeat it.
		$applied = array_filter( $errors, static fn( array $error ) => ! empty( $error['applied'] ) );
		$refused = array_diff_key( $errors, $applied );
		$content = $this->section(
			/* translators: %s: the post title. */
			__( 'Some ticket changes for "%s" were not saved. The post and the other tickets were saved; enter these changes again.', 'event-tickets' ),
			$refused,
			$post_id
		) . $this->section(
			/* translators: %s: the post title. */
			__( 'Some ticket changes for "%s" were saved, but something that runs after them failed. There is no need to enter them again.', 'event-tickets' ),
			$applied,
			$post_id
		);

		// One notice per save, shown once: not dismissible, so no dismissal is stored for an ID that never returns.
		$notice = ( new AdminNotice( 'tec-tickets-deferred-save-' . $user_id . '-' . md5( $content ), $content ) )
			->urgency( [] === $refused ? 'warning' : 'error' )
			->dismissible( false )
			->autoParagraph( false )
			->withWrapper();

		AdminNotices::render( $notice );
	}

	/**
	 * A heading naming the saved post, and one line per error under it; nothing when there are no errors.
	 *
	 * @since TBD
	 *
	 * @param string       $heading The heading, with `%s` for the post title.
	 * @param array<array> $errors  The errors.
	 * @param int          $post_id The post that was saved.
	 *
	 * @return string The section's HTML, escaped.
	 */
	private function section( string $heading, array $errors, int $post_id ): string {
		if ( [] === $errors ) {
			return '';
		}

		$content = '<p>' . esc_html( sprintf( $heading, get_the_title( $post_id ) ) ) . '</p><ul>';

		foreach ( $errors as $error ) {
			$content .= '<li>' . esc_html( $this->describe( $error, $post_id ) ) . '</li>';
		}

		return $content . '</ul>';
	}

	/**
	 * Turns an error into one line naming the ticket and what went wrong.
	 *
	 * A ticket is named only when it is a ticket on the saved post. The key of a rejected entry is
	 * whatever the payload sent, so looking up any other post's title here would let a payload probe
	 * titles the user may not be allowed to see.
	 *
	 * @since TBD
	 *
	 * @param array{part?: string|null, key?: int|string|null, message?: string} $error   The error.
	 * @param int                                                                $post_id The post that was saved.
	 *
	 * @return string The line.
	 */
	private function describe( array $error, int $post_id ): string {
		$message = (string) ( $error['message'] ?? '' );
		$part    = $error['part'] ?? null;
		$key     = $error['key'] ?? null;

		if ( null === $part || null === $key ) {
			return $message;
		}

		if ( Parser::CREATE === $part ) {
			/* translators: %1$d: the position of the new ticket in the list, %2$s: the error. */
			return sprintf( __( 'New ticket %1$d: %2$s', 'event-tickets' ), (int) $key + 1, $message );
		}

		$title = is_numeric( $key ) ? $this->ticket_name_on_post( (int) $key, $post_id ) : '';

		if ( '' === $title ) {
			/* translators: %1$s: the ticket ID, %2$s: the error. */
			return sprintf( __( 'Ticket %1$s: %2$s', 'event-tickets' ), (string) $key, $message );
		}

		/* translators: %1$s: the ticket name, %2$s: the error. */
		return sprintf( __( '"%1$s": %2$s', 'event-tickets' ), $title, $message );
	}

	/**
	 * The name of a ticket, when the ID is a ticket attached to the post; an empty string otherwise.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ID the payload named.
	 * @param int $post_id   The post that was saved.
	 *
	 * @return string The ticket name, or an empty string.
	 */
	private function ticket_name_on_post( int $ticket_id, int $post_id ): string {
		// The rule `Checks` applies: a ticket of its provider's type, whose event is the saved post once both are normalised.
		$ticket = $this->ticket_data->load_ticket_object( $ticket_id );

		if (
			! $ticket instanceof Ticket_Object
			|| (int) Event::filter_event_id( (int) $ticket->get_event_id(), 'deferred_save' ) !== (int) Event::filter_event_id( $post_id, 'deferred_save' )
		) {
			return '';
		}

		return (string) $ticket->name;
	}
}
