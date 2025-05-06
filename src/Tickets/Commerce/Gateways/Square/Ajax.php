<?php
/**
 * Square AJAX Hooks.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;
use TEC\Tickets\Commerce\Gateways\Square\WhoDat;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;

/**
 * Square AJAX Hooks.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Ajax extends Controller_Contract {
	/**
	 * WhoDat instance.
	 *
	 * @since TBD
	 *
	 * @var WhoDat
	 */
	private WhoDat $who_dat;

	/**
	 * Merchant instance.
	 *
	 * @since TBD
	 *
	 * @var Merchant
	 */
	private Merchant $merchant;

	/**
	 * Ajax constructor.
	 *
	 * @since TBD
	 *
	 * @param Container $container Container instance.
	 * @param WhoDat    $who_dat WhoDat instance.
	 * @param Merchant  $merchant Merchant instance.
	 */
	public function __construct( Container $container, WhoDat $who_dat, Merchant $merchant ) {
		parent::__construct( $container );
		$this->who_dat  = $who_dat;
		$this->merchant = $merchant;
	}

	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_action( 'wp_ajax_tec_tickets_commerce_square_connect', [ $this, 'ajax_connect_account' ] );
		add_action( 'wp_ajax_tec_tickets_commerce_square_disconnect', [ $this, 'ajax_disconnect_account' ] );
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'wp_ajax_tec_tickets_commerce_square_connect', [ $this, 'ajax_connect_account' ] );
		remove_action( 'wp_ajax_tec_tickets_commerce_square_disconnect', [ $this, 'ajax_disconnect_account' ] );
	}

	/**
	 * AJAX handler for connecting a Square account.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function ajax_connect_account(): void {
		// Check if the current user has permission.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'event-tickets' ) ] );
			return;
		}

		try {
			// Use WhoDat to get the connection URL.
			$connect_url = $this->who_dat->connect_account();

			if ( ! empty( $connect_url ) ) {
				wp_send_json_success( [ 'url' => $connect_url ] );
				return;
			}
		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
			return;
		}

		wp_send_json_error( [ 'message' => __( 'Failed to generate connection URL.', 'event-tickets' ) ] );
	}

	/**
	 * AJAX handler for disconnecting a Square account.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function ajax_disconnect_account(): void {
		check_ajax_referer( $this->merchant->get_disconnect_action(), '_wpnonce' );
		// Check if the current user has permission.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'event-tickets' ) ] );
		}

		try {
			// Disconnect from Square via WhoDat API.
			$this->who_dat->disconnect_account();

			// Delete local merchant data.
			$this->merchant->delete_signup_data();

			wp_send_json_success( [ 'message' => __( 'Successfully disconnected from Square.', 'event-tickets' ) ] );
		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}
}
