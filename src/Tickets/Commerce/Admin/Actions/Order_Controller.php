<?php
/**
 * The controller that handles action requests for Commerce Orders.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Actions;
 */

namespace TEC\Tickets\Commerce\Admin\Actions;

use TEC\Common\Contracts\Provider\Controller as AbstractController;

/**
 * Class Order_Controller.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Actions;
 */
class Order_Controller extends AbstractController {
	CONST NONCE_KEY = 'et-commerce-order-action-nonce';
	public function do_register(): void {
		if(!$this->is_valid()) {
			return;
		}

		$this->handle_request();
	}

	public function handle_request() {
		switch ($_REQUEST['action']) {
			case 'refund':

				// @todo Move to refund controller.
				$response = Order::refund( $_REQUEST['order_id'], $_REQUEST['amount'] );
				if( is_wp_error( $response ) ) {
					echo wp_json_encode(
						[
							'success' => false,
							'message' => $response
						]
					);
					exit;
				}
				echo wp_json_encode(
					[
						'success' => true,
						'message' => $response
					]
				);
				exit;
				break;
			default:
				// @todo Invalid request

		}
	}

	public function is_valid() {
		$nonce = $_REQUEST[self::NONCE_KEY] ?? null;

		return is_admin() && (bool)wp_verify_nonce($nonce, self::NONCE_KEY);
	}

	public function unregister(): void {

	}

}
