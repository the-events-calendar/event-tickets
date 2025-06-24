<?php
/**
 * Handles the payments step of the onboarding wizard.
 *
 * @since 5.23.0
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */

namespace TEC\Tickets\Admin\Onboarding\Steps;

use TEC\Common\Admin\Onboarding\Steps\Abstract_Step;
use WP_REST_Response;
use TEC\Tickets\Commerce\Gateways\Stripe\Signup;
use TEC\Tickets\Commerce\Payments_Tab;
use TEC\Tickets\Admin\Onboarding\API;
use TEC\Tickets\Commerce\Gateways\PayPal\Signup as PayPalSignup;
use TEC\Tickets\Commerce\Gateways\Square\WhoDat;

/**
 * Class Payments
 *
 * @since 5.23.0
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */
class Payments extends Abstract_Step {
	/**
	 * The tab number for this step.
	 *
	 * @since 5.23.0
	 *
	 * @var int
	 */
	public const TAB_NUMBER = 2;

	/**
	 * Passes the request and data to the handler.
	 *
	 * @since 5.23.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_REST_Request  $request  The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function handle( $response, $request ): WP_REST_Response {
		// If it's already an error, bail.
		if ( $response->is_error() ) {
			return $response;
		}

		return $this->process( $response, $request );
	}

	/**
	 * Process the payments data.
	 *
	 * @since 5.23.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_REST_Request  $request  The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function process( $response, $request ): WP_REST_Response {
		$params  = $request->get_params();
		$updated = tribe( API::class )->update_wizard_settings( $params );
		$action  = $params['action'] ?? '';

		if ( 'connect' === $action ) {
			return $this->handle_payment_gateway_connection( $response, $request );
		}

		if ( ! empty( $params['stripeConnected'] ) || ! empty( $params['squareConnected'] ) ) {
			$success = tribe( Payments_Tab::class )->maybe_auto_generate_checkout_page();
			$success = tribe( Payments_Tab::class )->maybe_auto_generate_order_success_page() || $success;

			return $this->add_message( $response, __( 'Stripe checkout and order pages created.', 'event-tickets' ) );
		}

		return $this->add_message( $response, __( 'Payment gateway connection not requested.', 'event-tickets' ) );
	}

	/**
	 * Handle the connection to payment gateways (Stripe and Square).
	 *
	 * @since 5.23.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_REST_Request  $request  The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function handle_payment_gateway_connection( $response, $request ): WP_REST_Response {
		$params = $request->get_params();

		if ( ! isset( $params['gateway'] ) ) {
			return $this->add_message( $response, __( 'Payment gateway connection not requested.', 'event-tickets' ) );
		}

		switch ( $params['gateway'] ) {
			case 'stripe':
				return $this->handle_stripe_connection( $response, $params );
			case 'square':
				return $this->handle_square_connection( $response, $params );
			case 'paypal':
				return $this->handle_paypal_connection( $response, $params );
			default:
				return $this->add_fail_message( $response, __( 'Invalid payment gateway specified.', 'event-tickets' ) );
		}
	}

	/**
	 * Handle the Stripe connection process.
	 *
	 * @since 5.23.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param array            $params   The request parameters.
	 *
	 * @return WP_REST_Response
	 */
	private function handle_stripe_connection( $response, $params ): WP_REST_Response {

		// Use the existing Stripe signup URL generation.
		$signup_url = tribe( Signup::class )->generate_signup_url();

		// Add the signup URL to the response data.
		$data               = $response->get_data();
		$data['signup_url'] = $signup_url;
		$response->set_data( $data );

		// Return the response with the redirect message.
		return $this->add_message( $response, __( 'Redirecting to Stripe...', 'event-tickets' ) );
	}

	/**
	 * Handle the Square connection process.
	 *
	 * @since 5.24.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param array            $params   The request parameters.
	 *
	 * @return WP_REST_Response
	 */
	private function handle_square_connection( $response, $params ): WP_REST_Response {
		// Use the existing Square signup URL generation.
		$signup_url = tribe( WhoDat::class )->connect_account( true );

		// Add the signup URL to the response data.
		$data               = $response->get_data();
		$data['signup_url'] = $signup_url;
		$response->set_data( $data );

		// Return the response with the redirect message.
		return $this->add_message( $response, __( 'Redirecting to Square...', 'event-tickets' ) );
	}

	/**
	 * Handle the PayPal connection process.
	 *
	 * @since 5.24.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param array            $params   The request parameters.
	 *
	 * @return WP_REST_Response
	 */
	private function handle_paypal_connection( $response, $params ): WP_REST_Response {
		// Use the existing PayPal signup URL generation.
		$signup_url = tribe( PayPalSignup::class )->generate_url( $params['country'], true );

		// Add the signup URL to the response data.
		$data               = $response->get_data();
		$data['signup_url'] = $signup_url;
		$response->set_data( $data );

		// Return the response with the redirect message.
		return $this->add_message( $response, __( 'Redirecting to PayPal...', 'event-tickets' ) );
	}
}
