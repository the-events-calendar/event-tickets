<?php
/**
 * Tells the admin which staged tickets the server rejected, after the page reload.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Classic
 */

namespace TEC\Tickets\Deferred_Save\Classic;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Deferred_Save\Payload;
use TEC\Tickets\Deferred_Save\Result;

/**
 * Class Notices.
 *
 * A classic save reloads the page, so the commit result has to survive the redirect. It is kept in a
 * transient for the user who saved, for one minute, and rendered once on their next post edit screen.
 * Per user rather than per post, because Events Calendar Pro may redirect a split save to another post.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Classic
 */
class Notices extends Controller_Contract {
	/**
	 * The transient key prefix; the user ID is appended.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const TRANSIENT_PREFIX = 'tec_tickets_deferred_save_result_';

	/**
	 * Hooks the commit and the admin notices.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tec_tickets_deferred_save_classic_committed', [ $this, 'remember' ], 10, 2 );
		add_action( 'admin_notices', [ $this, 'render' ] );
	}

	/**
	 * Unhooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_deferred_save_classic_committed', [ $this, 'remember' ], 10 );
		remove_action( 'admin_notices', [ $this, 'render' ] );
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

		$post_title = get_the_title( (int) ( $remembered['post_id'] ?? 0 ) );

		echo '<div class="notice notice-error is-dismissible tec-tickets-deferred-save-notice"><p>';
		echo esc_html(
			sprintf(
				/* translators: %s: the post title. */
				__( 'Some ticket changes for "%s" were not saved. The post and the other tickets were saved; enter these changes again.', 'event-tickets' ),
				$post_title
			)
		);
		echo '</p><ul>';

		foreach ( (array) $remembered['errors'] as $error ) {
			echo '<li>' . esc_html( $this->describe( (array) $error, (int) ( $remembered['post_id'] ?? 0 ) ) ) . '</li>';
		}

		echo '</ul></div>';
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

		if ( Payload::CREATE === $part ) {
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
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		if ( ! $provider instanceof \Tribe__Tickets__Tickets || get_post_type( $ticket_id ) !== $provider->ticket_object ) {
			return '';
		}

		$ticket = $provider->get_ticket( $post_id, $ticket_id );

		if ( ! $ticket instanceof \Tribe__Tickets__Ticket_Object || (int) $ticket->get_event_id() !== $post_id ) {
			return '';
		}

		return (string) $ticket->name;
	}
}
