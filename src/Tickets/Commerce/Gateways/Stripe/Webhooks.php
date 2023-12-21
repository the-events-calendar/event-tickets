<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Merchant;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Webhooks;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\Webhook_Endpoint;

use Tribe__Settings_Manager as Settings_Manager;

/**
 * Class Webhooks
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Webhooks extends Abstract_Webhooks {

	/**
	 * Option key that determines if the webhooks are valid.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $option_is_valid_webhooks = 'tickets-commerce-stripe-is-valid-webhooks';

	/**
	 * Option key that determines if the webhooks are valid.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $nonce_key_handle_validation = 'tickets-commerce-stripe-webhook-handle_validation';

	/**
	 * Option key that we use to allow customers to copy.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $option_webhooks_value = 'tickets-commerce-stripe-webhooks-value';

	/**
	 * Option name for the option to store the webhook signing key
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $option_webhooks_signing_key = 'tickets-commerce-stripe-webhooks-signing-key';

	/**
	 * @inheritDoc
	 */
	public function get_gateway(): Abstract_Gateway {
		return tribe( Gateway::class );
	}

	/**
	 * @inheritDoc
	 */
	public function get_merchant(): Abstract_Merchant {
		return tribe( Merchant::class );
	}

	/**
	 * Attempts to get the database option for the valid key from Stripe
	 * This function was introduced to enable a cache-free polling of the database for the Valid Key, it will include a
	 * filter to the WordPress All Options and remove the WordPress request cache for the option we are looking at.
	 *
	 * This will also check every half a second instead of a flat time. Allowing us in the future to chance how much we
	 * are waiting without much work.
	 *
	 * @since 5.7.1 Modified from a simple `sleep(10)` it speeds the process by increasing the amount of times it checks the database.
	 *
	 * @param int $max_attempts Number of attempts we will try to poll the database option.
	 *
	 * @return string|bool|null
	 */
	protected function pool_to_get_valid_key( int $max_attempts = 20 ) {
		$attempts  = 0;
		$valid_key = tribe_get_option( static::$option_is_valid_webhooks, false );

		$remove_settings_from_wp_all_options_cache = static function ( $all_options ) {
			if ( isset( $all_options[ \Tribe__Main::OPTIONNAME ] ) ) {
				unset( $all_options[ \Tribe__Main::OPTIONNAME ] );
			}

			return $all_options;
		};

		add_filter( 'alloptions', $remove_settings_from_wp_all_options_cache, 15 );
		while (
			(
				empty( $valid_key )
				|| ! is_string( $valid_key )
			)
			&& $attempts < $max_attempts
		) {
			usleep( 500000 ); // Wait half a second.

			// Resets the cache since we will want to attempt again.
			tribe_set_var( Settings_Manager::OPTION_CACHE_VAR_NAME, [] );
			wp_cache_delete( \Tribe__Main::OPTIONNAME, 'options' );

			$valid_key = tribe_get_option( static::$option_is_valid_webhooks, false );

			$attempts ++;
		}
		remove_filter( 'alloptions', $remove_settings_from_wp_all_options_cache, 15 );

		return $valid_key;
	}

	/**
	 * Testing if given Signing Key is valid on an AJAX request.
	 *
	 * @since 5.3.0
	 *
	 * @return void
	 */
	public function handle_validation(): void {
		$nonce  = tribe_get_request_var( 'tc_nonce' );
		$status = esc_html__( 'Webhooks not validated yet.', 'event-tickets' );

		if ( ! wp_verify_nonce( $nonce, static::$nonce_key_handle_validation ) ) {
			wp_send_json_error(
				[
					'updated' => false,
					'status'  => $status,
				]
			);
			exit;
		}

		$signing_key    = trim( tribe_get_request_var( 'signing_key' ) );
		$stored_key     = tribe_get_option( static::$option_webhooks_signing_key, false );
		$current_status = tribe_get_option( static::$option_is_valid_webhooks, false );

		if ( empty( $signing_key ) ) {
			$status = esc_html__( 'Signing Secret cannot be empty.', 'event-tickets' );
			wp_send_json_success(
				[
					'is_valid_webhook' => false,
					'updated'          => false,
					'status'           => $status,
				]
			);
			exit;
		}

		if ( $signing_key === $stored_key && $current_status === md5( $signing_key ) ) {
			$status = esc_html__( 'Webhooks were properly validated for sales.', 'event-tickets' );
			wp_send_json_success(
				[
					'is_valid_webhook' => true,
					'updated'          => false,
					'status'           => $status,
				]
			);
			exit;
		}

		// backwards compat
		if ( $signing_key === $stored_key && true === $current_status ) {
			$status = esc_html__( 'Webhooks were properly validated for sales.', 'event-tickets' );
			tribe_update_option( Webhooks::$option_is_valid_webhooks, md5( tribe_get_option( Webhooks::$option_webhooks_signing_key ) ) );
			wp_send_json_success(
				[
					'is_valid_webhook' => true,
					'updated'          => false,
					'status'           => $status,
				]
			);
			exit;
		}

		// at this point, either webhooks were not yet validated with the current key, or we're changing keys so we can start over.
		// replace stored key
		tribe_update_option( static::$option_webhooks_signing_key, $signing_key );
		// wipe success indicator
		tribe_update_option( Webhooks::$option_is_valid_webhooks, false );

		// create a test payment
		if ( true !== Payment_Intent::test_creation( [ 'card' ] ) ) {
			// payment creation failed
			$status = esc_html__( 'Could not connect to Stripe for validation. Please check your connection configuration.', 'event-tickets' );
			tribe_update_option( static::$option_webhooks_signing_key, $stored_key );
			wp_send_json_success(
				[
					'is_valid_webhook' => false,
					'updated'          => false,
					'status'           => $status,
				]
			);
			exit;
		}

		/**
		 * Allows changing the amount of attempts Stripe will check for the validated key on our database
		 *
		 * @since 5.7.1
		 *
		 * @param int $max_attempts How many attempts, each one takes half a second. Defaults to 20, total of 10 seconds of polling.
		 */
		$max_attempts = (int) apply_filters( 'tec_tickets_commerce_gateway_stripe_webhook_valid_key_polling_attempts', 20 );
		$valid_key    = $this->pool_to_get_valid_key( $max_attempts );

		if ( false === $valid_key ) {
			$status   = esc_html__( 'We have not received any Stripe events yet. Please wait a few seconds and refresh the page.', 'event-tickets' );
			$is_valid = false;
		} elseif ( $valid_key === md5( $signing_key ) ) {
			$status   = esc_html__( 'Webhooks were properly validated for sales.', 'event-tickets' );
			$is_valid = true;
		} else {
			$status   = esc_html__( 'This key has not been used in the latest events received. If you are setting up a new key, this status will be properly updated as soon as a new event is received.', 'event-tickets' );
			$is_valid = false;
			$updated  = true;
		}

		wp_send_json_success( [ 'is_valid_webhook' => $is_valid, 'updated' => $updated, 'status' => $status ] );
		exit;
	}

	/**
	 * Testing the current Signing Key has been verified with success.
	 *
	 * @since 5.5.6
	 *
	 * @return void
	 */
	public function handle_verification(): void {
		$nonce  = tribe_get_request_var( 'tc_nonce' );
		$status = esc_html__( 'The signing key appears to be invalid. Please check your webhook configuration in the Stripe Dashboard.', 'event-tickets' );

		if ( ! wp_verify_nonce( $nonce, static::$nonce_key_handle_validation ) ) {
			wp_send_json_error( [ 'updated' => false, 'status' => $status ] );
			exit;
		}

		$stored_key     = tribe_get_option( static::$option_webhooks_signing_key, false );
		$current_status = tribe_get_option( static::$option_is_valid_webhooks, false );

		if ( $current_status === md5( $stored_key ) ) {
			$status = esc_html__( 'Webhooks were properly validated for sales.', 'event-tickets' );
			wp_send_json_success( [ 'is_valid_webhook' => true, 'updated' => false, 'status' => $status ] );
			exit;
		}

		wp_send_json_success( [ 'is_valid_webhook' => false, 'updated' => false, 'status' => $status ] );
		exit;
	}

	/**
	 * Includes a Copy button to the webhook UI.
	 *
	 * @since 5.3.0
	 *
	 * @param string        $html
	 * @param \Tribe__Field $field
	 *
	 * @return string
	 */
	public function include_webhooks_copy_button( string $html, \Tribe__Field $field ): string {
		if ( static::$option_webhooks_value !== $field->id ) {
			return $html;
		}
		$copy_button = '<button class="tribe-field-tickets-commerce-stripe-webhooks-copy button-secondary" data-clipboard-target=".tribe-field-tickets-commerce-stripe-webhooks-copy-value"><span class="dashicons dashicons-clipboard"></span></button>';

		return $copy_button . $html;
	}

	/**
	 * Return the fields related to webhooks.
	 *
	 * @since 5.3.0
	 *
	 * @return array
	 */
	public function get_fields(): array {
		// The webhook value should always be empty.
		tribe_remove_option( static::$option_webhooks_value );
		$has_singing_key      = tribe_get_option( static::$option_webhooks_signing_key );
		$is_valid_signing_key = tribe_get_option( static::$option_is_valid_webhooks, false );

		if ( ! $has_singing_key || ! $is_valid_signing_key ) {
			$signing_key_tooltip = '<span class="dashicons dashicons-no"></span><span class="tribe-field-tickets-commerce-stripe-webhooks-signing-key-status">' . esc_html__( 'Webhooks not validated yet.', 'event-tickets' ) . '</span>';
		} else {
			$signing_key_tooltip = '<span class="dashicons dashicons-yes"></span><span class="tribe-field-tickets-commerce-stripe-webhooks-signing-key-status">' . esc_html__( 'Webhooks were properly validated for sales.', 'event-tickets' ) . '</span>';
		}

		return [
			'tickets-commerce-gateway-settings-group-start-webhook'       => [
				'type' => 'html',
				'html' => '<div class="tribe-settings-form-wrap">',
			],
			'tickets-commerce-gateway-settings-group-header-webhook'      => [
				'type' => 'html',
				'html' => '<h4 class="tec-tickets__admin-settings-tickets-commerce-gateway-group-header">' . esc_html__( 'Webhooks', 'event-tickets' ) . '</h4><div class="clear"></div>',
			],
			'tickets-commerce-gateway-settings-group-description-webhook' => [
				'type' => 'html',
				'html' => '<p class="tec-tickets__admin-settings-tickets-commerce-gateway-group-description-stripe-webhooks contained">' .
							wp_kses_post(
								sprintf(
									// Translators: %1$s A link to the KB article. %2$s closing `</a>` link.
									__( 'Setting up webhooks will enable you to receive notifications on charge statuses and keep order information up to date for asynchronous payments. %1$sLearn more%2$s', 'event-tickets' ),
									'<a target="_blank" rel="noopener noreferrer" href="https://evnt.is/1b3p">',
									'</a>'
								)
							)
							. '</p><div class="clear"></div>',
			],
			static::$option_webhooks_value                                => [
				'type'       => 'text',
				'label'      => esc_html__( 'Webhooks URL', 'event-tickets' ),
				'tooltip'    => '',
				'size'       => 'large',
				'default'    => tribe( Webhook_Endpoint::class )->get_route_url(),
				'attributes' => [
					'readonly' => 'readonly',
					'class'    => 'tribe-field-tickets-commerce-stripe-webhooks-copy-value',
				],
			],
			static::$option_webhooks_signing_key                          => [
				'type'                => 'text',
				'label'               => esc_html__( 'Signing Secret', 'event-tickets' ),
				'tooltip'             => $signing_key_tooltip,
				'size'                => 'large',
				'default'             => '',
				'validation_callback' => 'is_string',
				'validation_type'     => 'textarea',
				'attributes'          => [
					'data-ajax-nonce'         => wp_create_nonce( static::$nonce_key_handle_validation ),
					'data-loading-text'       => esc_attr__( 'Validating signing key with Stripe, please wait. This can take up to one minute.', 'event-tickets' ),
					'data-ajax-action'        => 'tec_tickets_commerce_gateway_stripe_test_webhooks',
					'data-ajax-action-verify' => 'tec_tickets_commerce_gateway_stripe_verify_webhooks',
				],
			],
			'tickets-commerce-gateway-settings-group-end-webhook'         => [
				'type' => 'html',
				'html' => '<div class="clear"></div></div>',
			],
		];
	}
}
