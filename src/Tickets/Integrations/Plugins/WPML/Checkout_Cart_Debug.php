<?php
/**
 * Debug helper for checkout cart issues with WPML.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Integrations\WPML
 */

namespace TEC\Tickets\Integrations\Plugins\WPML;

/**
 * Class Checkout_Cart_Debug.
 *
 * Provides debug logging for checkout cart issues when WPML is active.
 * Enable via: add_filter( 'tec_tickets_wpml_debug_checkout_cart', '__return_true' );
 *
 * @since TBD
 */
class Checkout_Cart_Debug {

	/**
	 * Log debug information about cart loading.
	 *
	 * @since TBD
	 *
	 * @param string $context Context (e.g., 'cart_load', 'ticket_load').
	 * @param array  $data    Data to log.
	 *
	 * @return void
	 */
	public static function log( string $context, array $data ): void {
		if ( ! apply_filters( 'tec_tickets_wpml_debug_checkout_cart', false ) ) {
			return;
		}

		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$message = sprintf(
			'[WPML Checkout Debug] %s: %s',
			$context,
			wp_json_encode( $data, JSON_PRETTY_PRINT )
		);

		error_log( $message );
	}

	/**
	 * Log cart hash and cookie information.
	 *
	 * @since TBD
	 *
	 * @param string $cart_hash Cart hash.
	 *
	 * @return void
	 */
	public static function log_cart_hash( string $cart_hash ): void {
		$current_lang = apply_filters( 'wpml_current_language', null );
		$cookie_value = $_COOKIE[ \TEC\Tickets\Commerce\Cart::get_cart_hash_cookie_name() ] ?? 'not set';
		$query_param  = tribe_get_request_var( \TEC\Tickets\Commerce\Cart::$cookie_query_arg, 'not set' );

		self::log( 'cart_hash', [
			'cart_hash'     => $cart_hash,
			'current_lang'  => $current_lang,
			'cookie_value' => $cookie_value,
			'query_param'   => $query_param,
		] );
	}

	/**
	 * Log ticket loading attempt.
	 *
	 * @since TBD
	 *
	 * @param int    $ticket_id Ticket ID.
	 * @param bool   $found     Whether ticket was found.
	 * @param string $reason    Reason if not found.
	 *
	 * @return void
	 */
	public static function log_ticket_load( int $ticket_id, bool $found, string $reason = '' ): void {
		$current_lang = apply_filters( 'wpml_current_language', null );
		$ticket_lang  = '';
		$post         = get_post( $ticket_id );

		if ( $post ) {
			$ticket_lang = apply_filters( 'wpml_element_language_code', false, [
				'element_id'   => $ticket_id,
				'element_type' => get_post_type( $ticket_id ),
			] );
		}

		$post_exists = is_object( $post ) && isset( $post->ID );

		self::log( 'ticket_load', [
			'ticket_id'    => $ticket_id,
			'found'        => $found,
			'reason'       => $reason,
			'current_lang' => $current_lang,
			'ticket_lang'  => $ticket_lang,
			'post_exists'  => $post_exists,
			'post_type'    => $post ? get_post_type( $post ) : 'N/A',
		] );
	}

	/**
	 * Log cart items.
	 *
	 * @since TBD
	 *
	 * @param array $items Cart items.
	 *
	 * @return void
	 */
	public static function log_cart_items( array $items ): void {
		$current_lang = apply_filters( 'wpml_current_language', null );

		self::log( 'cart_items', [
			'current_lang' => $current_lang,
			'item_count'   => count( $items ),
			'items'         => array_map( function ( $item ) {
				return [
					'ticket_id' => $item['ticket_id'] ?? 'N/A',
					'quantity'  => $item['quantity'] ?? 0,
					'has_obj'   => isset( $item['obj'] ),
				];
			}, $items ),
		] );
	}
}

