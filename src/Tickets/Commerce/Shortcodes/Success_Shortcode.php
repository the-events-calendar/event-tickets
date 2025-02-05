<?php
/**
 * Shortcode [tec_tickets_success].
 *
 * @since   5.1.9
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce\Shortcodes;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Success;
use WP_Post;

/**
 * Class for Shortcode Tribe_Tickets_Checkout.
 *
 * @since   5.1.9
 * @package Tribe\Tickets\Shortcodes
 */
class Success_Shortcode extends Shortcode_Abstract {

	/**
	 * Id of the current shortcode for filtering purposes.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $shortcode_id = 'success';

	/**
	 * {@inheritDoc}
	 */
	public function setup_template_vars() {
		$order_id = tribe_get_request_var( Success::$order_id_query_arg );
		$order    = tribe( Order::class )->get_from_gateway_order_id( $order_id );

		// Bail early if the order is empty or the user cannot view its details.
		if ( empty( $order ) || ! $this->can_view_order_details( $order ) ) {
			$this->template_vars = [ 'order' => null ]; // Reset template variables.
			return;
		}

		$attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		// Sort the Attendees by ID.
		$attendee_ids = array_column( $attendees, 'ID' );
		array_multisort( $attendee_ids, SORT_ASC, $attendees );

		$args = [
			'provider_id'    => Module::class,
			'provider'       => tribe( Module::class ),
			'order_id'       => $order_id,
			'order'          => $order,
			'is_tec_active'  => defined( 'TRIBE_EVENTS_FILE' ) && class_exists( 'Tribe__Events__Main' ),
			'payment_method' => tribe( Order::class )->get_gateway_label( $order ),
			'attendees'      => $attendees,
		];

		$this->template_vars = $args;
	}

	/**
	 * Get the HTML for the shortcode.
	 *
	 * @since 5.19.1
	 *
	 * @return string The rendered HTML or an empty string if conditions are not met.
	 */
	public function get_html() {
		$context = tribe_context();

		// Bail if in admin and not handling an AJAX request.
		if ( is_admin() && ! $context->doing_ajax() ) {
			return '';
		}

		// Bail if in the blocks editor context.
		if ( $context->doing_rest() ) {
			return '';
		}

		$args = $this->get_template_vars();

		$this->get_template()->add_template_globals( $args );

		$this->enqueue_assets();

		return $this->get_template()->template( 'success', $args, false );
	}

	/**
	 * Determine if the current user can view the order details.
	 *
	 * @since 5.19.1
	 *
	 * @param \WP_Post $order The order object.
	 *
	 * @return bool Whether the current user can view the order details.
	 */
	public function can_view_order_details( WP_Post $order ): bool {
		// Get the purchaser's user ID or default to 0 for guests.
		$owner_id = (int) ( $order->purchaser['user_id'] ?? 0 );

		// Show always for guest orders.
		if ( 0 === $owner_id ) {
			return true;
		}

		// Show if current user matches the purchaser.
		if ( get_current_user_id() === $owner_id ) {
			return true;
		}

		// Show if current user has admin capabilities.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// The order is tied to a user, but current user is not the owner or admin.
		return false;
	}

	/**
	 * Enqueue the assets related to this shortcode.
	 *
	 * @since 5.2.0
	 */
	public static function enqueue_assets() {
		$context = tribe_context();

		// Bail if we're in the blocks editor context.
		if ( $context->doing_rest() ) {
			return;
		}

		// Enqueue assets.
		tribe_asset_enqueue_group( 'tribe-tickets-commerce' );
	}
}
