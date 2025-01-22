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

		// If the order is not found, clear the template variables and bail.
		if ( empty( $order ) ) {
			$this->template_vars = [];

			return;
		}
		
		if ( ! $this->can_view_order_details( $order ) ) {
			$this->template_vars          = [];
			$this->template_vars['order'] = null;
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
	 * Checks below conditions to determine whether the HTML should be rendered:
	 * - If the order is a guest order and created less than 1 hour ago.
	 * - If the current user matches the order's purchaser.
	 * - If the current user has admin capabilities.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @param \WP_Post $order The order object.
	 *
	 * @return bool Whether the current user can view the order details.
	 */
	public function can_view_order_details( WP_Post $order ): bool {
		// Show if the user has admin capabilities.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		
		// Get the purchaser's user ID or default to 0 for guests.
		$owner_id = $order->purchaser['user_id'] ?? 0;
		
		// Show if the current user matches the order's purchaser.
		if ( 0 !== $owner_id && get_current_user_id() === $owner_id ) {
			return true;
		}
		
		// Show always for guest orders.
		return 0 === $owner_id;
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
