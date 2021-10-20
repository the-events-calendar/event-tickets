<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use Exception;
use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\Webhooks;
use Tribe__Settings;

/**
 * Class On_Boarding_Redirect_Handler
 *
 * @todo This whole file will stop exsiting once we deprecate all Give's code usage.
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 *
 */
class On_Boarding_Redirect_Handler {
	/**
	 * Return on boarding trouble notice.
	 *
	 * @TODO this method needs to be completely refactored into an admin page action.
	 *
	 * @since 5.1.6
	 */
	public function on_boarding_trouble_notice() {

		$action_list = sprintf(
			'<ol><li>%1$s</li><li>%2$s</li><li>%3$s %4$s</li></ol>',
			esc_html__( 'Make sure to complete the entire PayPal process. Do not close the window you have finished the process.', 'event-tickets' ),
			esc_html__( 'The last screen of the PayPal connect process includes a button to be sent back to your site. It is important you click this and do not close the window yourself.', 'event-tickets' ),
			esc_html__( 'If you’re still having problems connecting:', 'event-tickets' ),
			$this->settings->get_guidance_html()
		);

		$standard_error = sprintf(
			'<div id="give-paypal-onboarding-trouble-notice" class="tribe-common-a11y-hidden"><p class="error-message">%1$s</p><p>%2$s</p></div>',
			esc_html__( 'Having trouble connecting to PayPal?', 'event-tickets' ),
			$action_list
		);

		wp_send_json_success( $standard_error );
	}

	/**
	 * Sets up the webhook for the connected account
	 *
	 * @since 5.1.6
	 */
	private function set_up_webhook() {
		if ( ! is_ssl() ) {
			return;
		}

		try {
			$webhook_config = $this->webhooks_repository->create_webhook( $this->merchant->get_access_token() );

			$this->webhooks_repository->save_webhook_config( $webhook_config );
		} catch ( Exception $ex ) {
			tribe( 'logger' )->log_error( $ex->getMessage(), 'tickets-commerce-gateway-paypal' );

			$errors = [];

			$errors[] = esc_html__( 'There was a problem with creating webhook on PayPal. A gateway error log also added to get details information about PayPal response.', 'event-tickets' );

			// Log error messages.
			array_map( static function ( $error_message ) {
				$error_message = is_array( $error_message ) ? $error_message['message'] . ' ' . $error_message['value'] : $error_message;
				tribe( 'logger' )->log_error( $error_message, 'tickets-commerce-gateway-paypal' );
			}, $errors );

			$this->merchant->save_account_errors( $errors );
			$this->redirect_when_on_boarding_fail();
		}
	}

	/**
	 * Validate rest api credential.
	 *
	 * @since 5.1.6
	 *
	 * @param array $array
	 *
	 */
	private function did_we_get_valid_seller_rest_api_credentials( $array ) {
		$required = [ 'client_id', 'client_secret' ];
		$array    = array_filter( $array ); // Remove empty values.

		$errors = [];

		if ( array_diff( $required, array_keys( $array ) ) ) {
			$errors[] = [
				'type'    => 'json',
				'message' => esc_html__( 'PayPal client access token API request response is:', 'event-tickets' ),
				'value'   => wp_json_encode( $this->settings->get_access_token() ),
			];

			$errors[] = [
				'type'    => 'json',
				'message' => esc_html__( 'PayPal client rest API credentials API request response is:', 'event-tickets' ),
				'value'   => wp_json_encode( $array ),
			];

			$errors[] = esc_html__( 'There was a problem with PayPal client rest API request and we could not find valid client id and secret.', 'event-tickets' );

			// Log error messages.
			array_map( static function ( $error_message ) {
				$error_message = is_array( $error_message ) ? $error_message['message'] . ' ' . $error_message['value'] : $error_message;
				tribe( 'logger' )->log_error( $error_message, 'tickets-commerce-gateway-paypal' );
			}, $errors );

			$this->merchant->save_account_errors( $errors );
			//$this->redirect_when_on_boarding_fail();
		}
	}

}
