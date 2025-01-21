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

		$empty = '<div class="has-text-align-center">' . esc_html__( 'No order information is available.', 'event-tickets' ) . '</div>';
		$args  = $this->get_template_vars();

		// Bail if the order is not found.
		if ( ! isset( $args['order'] ) ) {
			return $empty;
		}

		// Get the purchaser's user ID or default to 0 for guests.
		$owner_id = $args['order']->purchaser['user_id'] ?? 0;

		// Show for guest orders created within the last hour.
		if ( 0 === $owner_id ) {
			$current = new \DateTime();
			$order   = new \DateTime( $args['order']->post_date ?? null );

			if ( $current->getTimestamp() - $order->getTimestamp() < 3600 ) {
				return $this->render_html( $args );
			}
		}

		// Show if the current user matches the order's purchaser.
		if ( 0 !== $owner_id && get_current_user_id() === $owner_id ) {
			return $this->render_html( $args );
		}

		// Show if the user has admin capabilities.
		if ( current_user_can( 'manage_options' ) ) {
			return $this->render_html( $args );
		}

		return $empty;
	}

	/**
	 * Render the HTML for the shortcode.
	 *
	 * @since TBD
	 *
	 * @param array $args The arguments for the shortcode.
	 * @return string The rendered HTML.
	 */
	private function render_html( $args ) {
		$this->get_template()->add_template_globals( $args );
		$this->enqueue_assets();
		return $this->get_template()->template( 'success', $args, false );
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
