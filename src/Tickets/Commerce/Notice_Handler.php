<?php

namespace TEC\Tickets\Commerce;

/**
 * Notice Handler for managing Admin view notices.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce
 */
class Notice_Handler {
	/**
	 * Resets the cache with the key from `\Tribe_Admin_Notices->get_transients()` so that
	 * we can display a given notice on the same page that we are triggering it.
	 *
	 * This is here because of a possible bug in common.
	 *
	 * @since TBD
	 */
	private function clear_request_cache() {
		// Clear the existing notices cache.
		$cache = tribe( 'cache' );
		unset( $cache['transient_admin_notices'] );
	}

	/**
	 * Fetches the expiration in seconds for the transient notice.
	 *
	 * @see   tribe_transient_notice()
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	protected function get_expiration() {
		return 10;
	}

	/**
	 * Fetches the array of all messages available to display.
	 *
	 * @since TBD
	 *
	 * @return array[]
	 */
	public function get_messages() {
		$messages = [
			[
				'slug'     => 'tc-paypal-signup-complete',
				'content'  => __( 'PayPal is now connected.', 'event-tickets' ),
				'type'     => 'info',
				'priority' => 10,
			],
			[
				'slug'     => 'tc-paypal-disconnect-failed',
				'content'  => __( 'Failed to disconnect PayPal account.', 'event-tickets' ),
				'type'     => 'error',
				'priority' => 10,
			],
			[
				'slug'     => 'tc-paypal-disconnect',
				'content'  => __( 'Disconnect PayPal account.', 'event-tickets' ),
				'type'     => 'info',
				'priority' => 10,
			],
			[
				'slug'     => 'tc-paypal-refresh-token-failed',
				'content'  => __( 'Failed to refresh PayPal access token.', 'event-tickets' ),
				'type'     => 'error',
				'priority' => 10,
			],
			[
				'slug'     => 'tc-paypal-refresh-token',
				'content'  => __( 'PayPal access token was refresh successfully.', 'event-tickets' ),
				'type'     => 'info',
				'priority' => 10,
			],
			[
				'slug'     => 'tc-paypal-refresh-user-info-failed',
				'content'  => __( 'Failed to refresh PayPal user info.', 'event-tickets' ),
				'type'     => 'error',
				'priority' => 10,
			],
			[
				'slug'     => 'tc-paypal-refresh-user-info',
				'content'  => __( 'PayPal user info was refresh successfully.', 'event-tickets' ),
				'type'     => 'info',
				'priority' => 10,
			],
		];

		/**
		 * Filters the available notice messages.
		 *
		 * @since TBD
		 *
		 * @param array $messages Array of notice messages.
		 */
		return (array) apply_filters( 'tec_tickets_commerce_notice_messages', $messages );
	}

	/**
	 * Determines if a message exists with a given slug.
	 *
	 * @since TBD
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	public function message_slug_exists( $slug ) {
		$message = array_values( wp_list_filter( $this->get_messages(), [ 'slug' => $slug ] ) );

		return ! empty( $message[0] );
	}

	/**
	 * Gets a given message data by it's slug.
	 *
	 * @since TBD
	 *
	 * @param string $slug
	 *
	 * @return array|null
	 */
	public function get_message_data( $slug ) {
		if ( ! $this->message_slug_exists( $slug ) ) {
			return null;
		}

		$default_args = [
			'expire' => true,
			'wrap'   => 'p',
		];
		$message      = array_values( wp_list_filter( $this->get_messages(), [ 'slug' => $slug ] ) )[0];

		return array_merge( $default_args, $message );
	}

	/**
	 * Merges the content of a given set of Notice slugs.
	 *
	 * @since TBD
	 *
	 * @param array $slugs
	 *
	 * @return string
	 */
	public function merge_contents( array $slugs ) {
		$messages = array_map( [ $this, 'get_message_data' ], $slugs );

		$html[] = '<ul>';
		foreach ( $messages as $message ) {
			$list_class = sanitize_html_class( 'tec-tickets-commerce-notice-item-' . $message['slug'] );
			$html[]     = "<li class='{$list_class}'>";
			$html[]     = $message['content'];
			$html[]     = '</li>';
		}
		$html[] = '</ul>';

		return implode( "\n", $html );
	}

	/**
	 * Add an admin notice that should only show once.
	 *
	 * @since TBD
	 *
	 * @param string $slug            Slug to store the notice.
	 * @param string $message_content Content to display as notice.
	 * @param string $message_type    Type of notice; Supported types: success | error | info | warning.
	 */
	public function trigger_admin( $slug, $message_content = null, $message_type = null ) {
		$default_message = [
			'content' => $message_content,
			'type'    => $message_type
		];
		$message         = $this->get_message_data( $slug );

		if ( ! $message ) {
			$message = $default_message;
		} else {
			if ( null !== $message_content ) {
				$message['content'] = $message_content;
			}
			if ( null !== $message_type ) {
				$message['type'] = $message_content;
			}
		}

		if ( empty( $message['type'] ) ) {
			$message['type'] = 'error';
		}

		tribe_transient_notice( $slug, $message['content'], $message, $this->get_expiration() );

		// This is here because of a possible bug in common.
		$this->clear_request_cache();
	}
}