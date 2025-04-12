<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use \TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Controller extends Controller_Contract {

	/**
	 * Determines if this controller will register.
	 * This is present due to how UOPZ works, it will fail if method belongs to the parent/abstract class.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the controller is active or not.
	 */
	public function is_active(): bool {
		return true;
	}

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		// Register classes as singletons
		$this->container->singleton( Gateway::class );
		$this->container->singleton( Merchant::class );
		$this->container->singleton( WhoDat::class );
		$this->container->singleton( Order::class );
		$this->container->singleton( Settings::class );
		$this->container->singleton( REST::class );
		$this->container->singleton( Assets::class );

		$this->add_actions();
		$this->add_filters();

		// Register the assets
		$this->register_assets();
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->remove_actions();
		$this->remove_filters();
	}

	/**
	 * Add actions required by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function add_actions(): void {
		// Register the REST endpoints.
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );

		// Connect and disconnect AJAX handlers
		add_action( 'wp_ajax_tec_tickets_commerce_square_connect', [ $this, 'ajax_connect_account' ] );
		add_action( 'wp_ajax_tec_tickets_commerce_square_disconnect', [ $this, 'ajax_disconnect_account' ] );
	}

	/**
	 * Remove actions when the controller is unregistered.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function remove_actions(): void {
		remove_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		remove_action( 'wp_ajax_tec_tickets_commerce_square_connect', [ $this, 'ajax_connect_account' ] );
		remove_action( 'wp_ajax_tec_tickets_commerce_square_disconnect', [ $this, 'ajax_disconnect_account' ] );
	}

	/**
	 * Add filters required by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function add_filters(): void {
		// Register the gateway.
		add_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ] );

		// Add gateway-specific filters as needed
	}

	/**
	 * Remove filters when the controller is unregistered.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function remove_filters(): void {
		remove_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ] );
	}

	/**
	 * Filter the Commerce Gateways to add Square.
	 *
	 * @since TBD
	 *
	 * @param array $gateways List of gateways.
	 *
	 * @return array
	 */
	public function filter_add_gateway( array $gateways = [] ) {
		$gateways[ Gateway::get_key() ] = tribe( Gateway::class );

		return $gateways;
	}

	/**
	 * Register the REST endpoints for Square.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_endpoints(): void {
		$this->container->make( REST::class )->register_endpoints();
	}

	/**
	 * AJAX handler for connecting a Square account.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function ajax_connect_account(): void {
		// Check if the current user has permission
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'event-tickets' ) ] );
		}

		try {
			// Use WhoDat to get the connection URL
			$connect_url = $this->container->make( WhoDat::class )->connect_account();

			if ( empty( $connect_url ) ) {
				wp_send_json_error( [ 'message' => __( 'Failed to generate connection URL.', 'event-tickets' ) ] );
			}

			wp_send_json_success( [ 'url' => $connect_url ] );
		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}

	/**
	 * AJAX handler for disconnecting a Square account.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function ajax_disconnect_account(): void {
		// Check if the current user has permission
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'event-tickets' ) ] );
		}

		$merchant = tribe( Merchant::class );
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( $_POST['_wpnonce'] ) : '';

		// Verify the nonce
		if ( ! wp_verify_nonce( $nonce, $merchant->get_disconnect_action() ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'event-tickets' ) ] );
		}

		try {
			// Disconnect from Square via WhoDat API
			$response = tribe( WhoDat::class )->disconnect_account();

			// Delete local merchant data
			$merchant->delete_signup_data();

			wp_send_json_success( [ 'message' => __( 'Successfully disconnected from Square.', 'event-tickets' ) ] );
		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}

	/**
	 * Register the assets needed for the Square gateway.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_assets(): void {
		$this->container->make( Assets::class )->register();
	}
}
